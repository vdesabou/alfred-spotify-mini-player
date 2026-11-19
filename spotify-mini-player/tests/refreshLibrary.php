<?php
// Run with: php -d disable_functions=exec tests/refreshLibrary.php
// Uses a fake Spotify API and a temporary SQLite library; no account or Alfred changes.
if (function_exists('exec')) {
    throw new RuntimeException('Run with php -d disable_functions=exec tests/refreshLibrary.php');
}
chdir(dirname(__DIR__));
require './src/refreshLibrary.php';

function check($condition, $message) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function fixtureTrack($id, $legacy = false) {
    $track = (object) array(
        'uri' => 'spotify:track:' . $id, 'name' => $id, 'duration_ms' => 1000,
        'is_playable' => true, 'type' => 'track',
        'artists' => array((object) array('uri' => 'spotify:artist:test', 'name' => 'Artist')),
        'album' => (object) array('uri' => 'spotify:album:test', 'name' => 'Album', 'album_type' => 'album'),
    );
    return (object) array(
        $legacy ? 'track' : 'item' => $track,
        'added_at' => '2026-01-01T00:00:00Z', 'is_local' => false,
    );
}

function fixturePlaylist($id, $total, $legacy = false) {
    return (object) array(
        'id' => $id, 'uri' => 'spotify:playlist:' . $id, 'name' => $id,
        'owner' => (object) array('id' => 'test-user'),
        'collaborative' => false, 'public' => true, 'snapshot_id' => 'original',
        $legacy ? 'tracks' : 'items' => (object) array('total' => $total),
    );
}

class FixtureSpotify {
    public $playlists = array();
    public $items = array();
    public $errors = array();
    public $playlistError;
    public $playlistOffsets = array();
    public $itemOffsets = array();

    public function me() {
        return (object) array('id' => 'test-user');
    }

    public function getMySavedAlbums($options) {
        return (object) array('items' => array(), 'total' => 0);
    }

    public function getUserFollowedArtists($options) {
        return (object) array('artists' => (object) array('items' => array()));
    }

    public function getMySavedTracks($options) {
        return (object) array('items' => array(), 'total' => 0);
    }

    public function getMyPlaylists($options) {
        $this->playlistOffsets[] = $options['offset'];
        if ($this->playlistError !== null) {
            throw new SpotifyWebAPI\SpotifyWebAPIException('Unavailable', $this->playlistError);
        }
        return (object) array(
            'items' => array_slice($this->playlists, $options['offset'], $options['limit']),
            'total' => count($this->playlists),
        );
    }

    public function getPlaylistItems($id, $options) {
        $this->itemOffsets[$id][] = $options['offset'];
        if (isset($this->errors[$id])) {
            throw new SpotifyWebAPI\SpotifyWebAPIException('Unavailable', $this->errors[$id]);
        }
        $items = $this->items[$id] ?? array();
        return (object) array(
            'items' => array_slice($items, $options['offset'], $options['limit']),
            'total' => count($items),
        );
    }

    public function __call($name, $arguments) {
        throw new RuntimeException('Unexpected API call: ' . $name);
    }
}

$temporary = sys_get_temp_dir() . '/spotify-refresh-test-' . bin2hex(random_bytes(8));
mkdir($temporary);
mkdir($temporary . '/data');
mkdir($temporary . '/cache');
putenv('alfred_workflow_data=' . $temporary . '/data');
putenv('alfred_workflow_cache=' . $temporary . '/cache');
foreach (array(
    '__userid' => 'test-user', '__country_code' => 'US', '__use_artworks' => '0',
    '__debug' => '0', '__podcasts_enabled' => '0', '__only_refresh_selected_playlists' => '0',
) as $key => $value) {
    putenv($key . '=' . $value);
}

try {
    $db = new PDO('sqlite:' . $temporary . '/data/library.db');
    $db->exec('CREATE TABLE tracks (yourmusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration text, nb_times_played int, local_track boolean, yourmusic_album boolean, track_name_deburr text, album_name_deburr text, artist_name_deburr text)');
    $db->exec('CREATE TABLE playlists (uri text PRIMARY KEY, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean, nb_playable_tracks int, duration_playlist text, nb_times_played int, collaborative boolean, public boolean, name_deburr text, snapshot_id text)');
    $db->exec('CREATE TABLE counters (id int PRIMARY KEY, all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int, episodes int)');
    $db->exec('CREATE TABLE followed_artists (uri PRIMARY KEY, name, artist_artwork_path, name_deburr)');
    $db->exec('CREATE TABLE shows (uri PRIMARY KEY, name, description, media_type, show_artwork_path, explicit, added_at, languages, nb_times_played, is_externally_hosted, nb_episodes, name_deburr)');
    $db->exec('CREATE TABLE episodes (uri PRIMARY KEY, name, show_uri, show_name, description, episode_artwork_path, is_playable, languages, nb_times_played, is_externally_hosted, duration_ms, explicit, release_date, release_date_precision, audio_preview_url, fully_played, resume_position_ms, name_deburr, show_name_deburr)');
    $db = null;

    $api = new FixtureSpotify();
    $api->playlists = array(
        fixturePlaylist('forbidden', 20), fixturePlaylist('large', 101),
        fixturePlaylist('legacy', 1, true), fixturePlaylist('missing', 1),
        fixturePlaylist('revoked', 1),
    );
    for ($i = 0; $i < 46; ++$i) {
        $api->playlists[] = fixturePlaylist('empty-' . $i, 0);
    }
    $api->playlists[] = fixturePlaylist('last', 1);
    $api->items['large'] = array_map('fixtureTrack', range(1, 101));
    $api->items['legacy'] = array(fixtureTrack('legacy-track', true));
    $api->items['revoked'] = array(fixtureTrack('cached-track'));
    $api->items['last'] = array(fixtureTrack('last-track'));
    $api->errors = array('forbidden' => 403, 'missing' => 404);
    $w = new Workflows('com.vdesabou.spotify.mini.player');
    $w->setApi($api);
    refreshLibrary($w, true);

    $db = new PDO('sqlite:' . $temporary . '/data/library.db');
    check($db->query('SELECT COUNT(*) FROM playlists')->fetchColumn() == 51, 'Keep forbidden playlist metadata and continue after a missing playlist');
    check($db->query("SELECT nb_tracks FROM playlists WHERE name='forbidden'")->fetchColumn() == 20, 'Retain the advertised track count for a forbidden playlist');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE playlist_name='large'")->fetchColumn() == 101, 'Import every page using item objects');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE playlist_name='legacy'")->fetchColumn() == 1, 'Support legacy track objects');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE playlist_name='last'")->fetchColumn() == 1, 'Reach the last playlist after errors');
    check($api->playlistOffsets == array(0, 50), 'Fetch both playlist pages');
    check($api->itemOffsets['large'] == array(0, 100), 'Fetch both item pages');
    check($db->query('SELECT COUNT(*) FROM tracks WHERE popularity IS NULL')->fetchColumn() == 0, 'Handle the removed popularity field');
    $db = null;

    $api->playlists[1]->snapshot_id = 'changed';
    $api->playlists[2]->snapshot_id = 'changed';
    $api->playlists[4]->snapshot_id = 'changed';
    $api->items['large'][0] = fixtureTrack('replacement-track');
    $api->items['legacy'][0] = fixtureTrack('replacement-legacy-track', true);
    $api->errors['revoked'] = 403;
    $api->itemOffsets = array();
    // Each Alfred invocation is a separate process. Use a distinct path here
    // so PHP's persistent PDO connection cannot reuse the renamed first database.
    mkdir($temporary . '/refreshed');
    copy($temporary . '/data/library.db', $temporary . '/refreshed/library.db');
    putenv('alfred_workflow_data=' . $temporary . '/refreshed');
    $w = new Workflows('com.vdesabou.spotify.mini.player');
    $w->setApi($api);
    refreshLibrary($w, true);

    $db = new PDO('sqlite:' . $temporary . '/refreshed/library.db');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE playlist_name='large'")->fetchColumn() == 101, 'Refresh without duplicates');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE uri='spotify:track:replacement-track'")->fetchColumn() == 1, 'Refresh renamed item objects');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE uri='spotify:track:replacement-legacy-track'")->fetchColumn() == 1, 'Refresh legacy track objects');
    check($db->query("SELECT COUNT(*) FROM tracks WHERE uri='spotify:track:cached-track'")->fetchColumn() == 1, 'Preserve cached tracks when access is revoked');
    check(!file_exists($temporary . '/refreshed/update_library_in_progress'), 'Complete the refresh and clear its marker');
    $db = null;

    mkdir($temporary . '/failed');
    copy($temporary . '/refreshed/library.db', $temporary . '/failed/library.db');
    $before = hash_file('sha256', $temporary . '/failed/library.db');
    putenv('alfred_workflow_data=' . $temporary . '/failed');
    $w = new Workflows('com.vdesabou.spotify.mini.player');
    $api->playlistError = 403;
    $w->setApi($api);
    check(refreshLibrary($w, true) === false, 'Report a failed playlist request');
    check(hash_file('sha256', $temporary . '/failed/library.db') === $before, 'Restore the entire previous library when playlist listing fails');
    check(!file_exists($temporary . '/failed/update_library_in_progress'), 'Clear the failed refresh marker');
    echo "PASS: playlist pagination, item pagination, legacy responses, forbidden/missing playlists, incremental refresh, and failed-list recovery\n";
} finally {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($temporary);
}
