<?php
// Run with: php -d disable_functions=exec tests/artwork.php
// Fake Spotify API, local image server, and temporary databases only.
chdir(dirname(__DIR__));
require './src/functions.php';

function checkArtwork($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function artworkResumeRow($w)
{
    $settings = new Workflows('com.vdesabou.spotify.mini.player');
    addArtworkResumeResult($settings, false);
    $results = json_decode($settings->tojson(), true);
    foreach ($results['items'] ?? array() as $item) {
        if (strpos($item['title'], 'Resume artwork downloads') === 0) {
            return $item;
        }
    }
    return null;
}

class ArtworkFixtureApi
{
    public $limited = true;
    public $calls = 0;
    public $imageUrl;
    public $savedOffsets = array();
    public $playlistOffsets = array();

    public function getRequest() { return $this; }
    public function getLastResponse() { return array('headers' => array('retry-after' => '120')); }
    public function getTrack($id)
    {
        ++$this->calls;
        if ($this->limited) {
            throw new SpotifyWebAPI\SpotifyWebAPIException('Too many requests', 429);
        }
        return $this->track($id);
    }
    public function track($id)
    {
        return (object) array(
            'uri' => 'spotify:track:' . $id,
            'album' => (object) array(
                'uri' => 'spotify:album:' . $id,
                'images' => array((object) array('url' => $this->imageUrl)),
            ),
        );
    }
    public function getMySavedTracks($options)
    {
        $this->savedOffsets[] = $options['offset'];
        $all = array();
        for ($i = 0; $i < 51; ++$i) {
            $all[] = (object) array('track' => $this->track('saved-' . $i));
        }
        return (object) array('items' => array_slice($all, $options['offset'], $options['limit']), 'total' => 51);
    }
    public function getMySavedAlbums($options)
    {
        return (object) array('items' => array(), 'total' => 0);
    }
    public function getPlaylistItems($uri, $options)
    {
        $this->playlistOffsets[] = $options['offset'];
        $all = array();
        for ($i = 0; $i < 101; ++$i) {
            $all[] = (object) array('item' => $this->track('playlist-' . $i));
        }
        return (object) array('items' => array_slice($all, $options['offset'], $options['limit']), 'total' => 101);
    }
}

$temporary = sys_get_temp_dir() . '/spotify-artwork-test-' . bin2hex(random_bytes(8));
mkdir($temporary);
mkdir($temporary . '/data');
mkdir($temporary . '/cache');
putenv('alfred_workflow_data=' . $temporary . '/data');
putenv('alfred_workflow_cache=' . $temporary . '/cache');
$server = null;
try {
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    checkArtwork($socket !== false, 'Reserve a local test port');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $router = '<?php
        file_put_contents(' . var_export($temporary . '/requests.log', true) . ', $_SERVER["REQUEST_URI"]."\n", FILE_APPEND | LOCK_EX);
        if ($_SERVER["REQUEST_URI"] === "/bad") { http_response_code(503); exit("Unavailable"); }
        header("Content-Type: image/png");
        echo base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aav8AAAAASUVORK5CYII=");';
    file_put_contents($temporary . '/router.php', $router);
    $server = proc_open(array(PHP_BINARY, '-S', $address, $temporary . '/router.php'), array(
        0 => array('pipe', 'r'), 1 => array('file', $temporary . '/server.log', 'a'),
        2 => array('file', $temporary . '/server.log', 'a'),
    ), $pipes);
    checkArtwork(is_resource($server), 'Start the local image server');
    fclose($pipes[0]);
    for ($i = 0; $i < 100; ++$i) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.05);
        if ($connection !== false) {
            fclose($connection);
            break;
        }
        usleep(10000);
    }
    $url = 'http://' . $address . '/cover';
    $w = new Workflows('com.vdesabou.spotify.mini.player');
    $api = new ArtworkFixtureApi();
    $api->imageUrl = $url;
    $db = artworkUrlDatabase($w);
    foreach (array('tracks', 'albums', 'shows', 'episodes') as $table) {
        $type = rtrim($table, 's');
        $db->exec('CREATE TABLE ' . $table . ' (' . $type . '_uri TEXT PRIMARY KEY, already_fetched INTEGER)');
    }
    $db->exec('CREATE TABLE artists (artist_uri TEXT PRIMARY KEY, artist_name TEXT, already_fetched INTEGER)');
    foreach (array('first', 'second') as $id) {
        $db->exec("INSERT INTO tracks VALUES ('spotify:track:$id',0)");
        rememberArtworkObject($w, $api->track($id));
    }
    $db->exec("INSERT INTO albums VALUES ('spotify:album:first',0)");
    putenv('__use_artworks=1');
    checkArtwork(remainingArtworkCount($w) === 3, 'Count the saved pending queue');
    $resume = artworkResumeRow($w);
    checkArtwork($resume !== null && strpos($resume['title'], '3 remaining') !== false, 'Offer resume for an interrupted download');
    $arguments = unserialize($resume['arg']);
    checkArtwork(count($arguments) === 22 && $arguments[7] === 'resume_artwork_downloads', 'Wire the resume action through the normal workflow arguments');
    checkArtwork(artworkUri('playlist', 'spotify:user:test:playlist:abc') === 'spotify:playlist:abc', 'Normalize legacy playlist URIs');
    checkArtwork(getArtworkURL($w, 'track', 'first') === $url, 'UI reads cached image URLs without calling Spotify');
    $result = processArtworkQueue($w, $api, false, array());
    checkArtwork($result['completed'] === 3, 'Complete all three queue rows');
    unlink($temporary . '/data/download_artworks_in_progress');
    checkArtwork(artworkResumeRow($w) === null, 'Hide resume when every queued image is complete');
    checkArtwork($api->calls === 0, 'Cached URLs require no metadata API calls');
    checkArtwork(count(file($temporary . '/requests.log')) === 1, 'Download a shared cover just once');

    $db->exec("INSERT INTO tracks VALUES ('spotify:track:quota',0)");
    $db->exec("INSERT INTO albums VALUES ('spotify:album:available',0)");
    rememberArtworkObject($w, (object) array('uri' => 'spotify:album:available', 'images' => array((object) array('url' => $url . '-other'))));
    $result = processArtworkQueue($w, $api, false, array());
    checkArtwork($api->calls === 1, 'Stop metadata requests after the first 429');
    checkArtwork($result['completed'] === 4 && $result['retry_at'] > time(), 'Download known images during a Spotify cooldown');
    checkArtwork($db->query("SELECT already_fetched FROM tracks WHERE track_uri='spotify:track:quota'")->fetchColumn() == 0, 'A rate-limited row remains pending');
    unlink($temporary . '/data/download_artworks_in_progress');
    checkArtwork(artworkResumeRow($w) !== null, 'Keep resume available after stopping a rate-limited download');
    processArtworkQueue($w, $api, false, array());
    checkArtwork($api->calls === 1, 'Respect the persisted retry deadline on another run');
    file_put_contents($temporary . '/data/artwork_retry_after.json', '{"retry_at":0}');
    $api->limited = false;
    $result = processArtworkQueue($w, $api, false, array());
    checkArtwork($result['completed'] === 5, 'Resume the pending row after cooldown');
    checkArtwork(count(file($temporary . '/requests.log')) === 2, 'Reuse downloaded covers across separate runs');

    $db->exec("INSERT INTO albums VALUES ('spotify:album:bad',0)");
    rememberArtworkObject($w, (object) array('uri' => 'spotify:album:bad', 'images' => array((object) array('url' => 'http://' . $address . '/bad'))));
    processArtworkQueue($w, $api, false, array());
    checkArtwork($db->query("SELECT already_fetched FROM albums WHERE album_uri='spotify:album:bad'")->fetchColumn() == 0, 'An HTTP failure remains pending');
    checkArtwork(!file_exists(artworkTarget($w, 'album', array('album_uri' => 'spotify:album:bad'))), 'Failed transfers do not leave a fake completed image');

    $library = new PDO('sqlite:' . $temporary . '/data/library.db');
    $library->exec('CREATE TABLE playlists (uri TEXT, ownedbyuser INTEGER, collaborative INTEGER)');
    $library->exec("INSERT INTO playlists VALUES ('spotify:playlist:test',1,0)");
    seedArtworkUrls($w, $api);
    seedArtworkUrls($w, $api);
    checkArtwork($api->savedOffsets === array(0,50), 'Seed saved-track URLs with pagination and resume state');
    checkArtwork($api->playlistOffsets === array(0,100), 'Seed playlist URLs with pagination without repeating a completed seed');
    checkArtwork(cachedArtworkUrl($db, 'spotify:track:playlist-100') === $url, 'Cache modern playlist item image URLs');
    checkArtwork(cachedArtworkUrl($db, 'spotify:album:saved-50') === $url, 'Cache album URLs from saved-track metadata');
    echo "PASS: shared image reuse, HTTP failures, quota backoff/resume, URL seeding and pagination\n";
} finally {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
    $db = null;
    $library = null;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) { rmdir($file->getPathname()); }
        else { unlink($file->getPathname()); }
    }
    rmdir($temporary);
}
