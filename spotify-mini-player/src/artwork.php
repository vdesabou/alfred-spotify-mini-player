<?php

// Artwork download queue (fetch_artworks.db).
//
// - Image URLs seen during library refresh are remembered in artwork_urls, so most queued
//   artworks need no metadata request at all.
// - Covers shared by several tracks/albums are downloaded once, with four parallel transfers.
// - Covers already in Spotify Desktop's local cache are reused (see spotifyLocalArtwork.php).
// - A Spotify 429 pauses metadata requests until Retry-After (persisted across runs), while
//   images whose URL is already known keep downloading.
//
// Based on vdesabou/alfred-spotify-mini-player PR #659 by nhooyr.

require_once './src/spotifyLocalArtwork.php';

// Number of failed attempts (transfer or metadata errors) after which the placeholder
// image is used, so that the queue always completes.
define('ARTWORK_MAX_ATTEMPTS', 3);

function artworkUri($type, $uri)
{
    $parts = explode(':', $uri);
    return 'spotify:' . $type . ':' . end($parts);
}

/**
 * Return a PDO handle on fetch_artworks.db with the artwork_urls/artwork_failures tables.
 * The handle is cached per process; it is reopened when the file was recreated
 * (createLibrary deletes fetch_artworks.db).
 */
function artworkUrlDatabase($w)
{
    static $cache = array();
    $file = $w->data() . '/fetch_artworks.db';
    clearstatcache(true, $file);
    $inode = @fileinode($file);
    if (isset($cache[$file]) && $inode !== false && $cache[$file]['inode'] === $inode) {
        return $cache[$file]['db'];
    }
    $db = openSqliteDatabase($file);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS artwork_urls (uri TEXT PRIMARY KEY, url TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS artwork_failures (uri TEXT PRIMARY KEY, attempts INTEGER NOT NULL)');
    clearstatcache(true, $file);
    $cache[$file] = array('db' => $db, 'inode' => @fileinode($file));
    return $db;
}

/**
 * Queue tables present in fetch_artworks.db, as type => table.
 */
function artworkQueueTables($db)
{
    $existing = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $tables = array();
    foreach (array('track', 'album', 'artist', 'show', 'episode') as $type) {
        if (in_array($type . 's', $existing, true)) {
            $tables[$type] = $type . 's';
        }
    }
    return $tables;
}

function remainingArtworkCount($w)
{
    if (!is_file($w->data() . '/fetch_artworks.db')) {
        return 0;
    }
    $db = artworkUrlDatabase($w);
    $remaining = 0;
    foreach (artworkQueueTables($db) as $table) {
        $remaining += (int) $db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE already_fetched=0')->fetchColumn();
    }
    return $remaining;
}

function addArtworkResumeResult($w, $update_in_progress)
{
    if (!getSetting($w, 'use_artworks') || $update_in_progress || is_file($w->data() . '/download_artworks_in_progress')) {
        return;
    }
    $remaining = remainingArtworkCount($w);
    if ($remaining > 0) {
        $arguments = array_fill(0, 22, '');
        $arguments[7] = 'resume_artwork_downloads'; // other_action
        $w->result(null, serialize($arguments), 'Resume artwork downloads (' . $remaining . ' remaining)',
            'Continue the saved queue without rebuilding your library; any Spotify cooldown is respected',
            './images/artworks.png', 'yes', null, '');
    }
}

/**
 * Remember the smallest image URL of a Spotify object (and of its nested item/track/show/episode/album).
 * Writes are buffered and flushed in a single transaction (every 500 URLs, on read, and at exit),
 * so that calling this for every track during a library refresh stays cheap.
 */
function rememberArtworkObject($w, $object)
{
    if (!is_object($object)) {
        return;
    }
    foreach (array('item', 'track', 'show', 'episode') as $field) {
        if (isset($object->$field) && is_object($object->$field)) {
            rememberArtworkObject($w, $object->$field);
        }
    }
    if (isset($object->album) && is_object($object->album)) {
        rememberArtworkObject($w, $object->album);
    }
    $images = null;
    if (isset($object->images) && is_array($object->images)) {
        $images = $object->images;
    } elseif (isset($object->album->images) && is_array($object->album->images)) {
        $images = $object->album->images;
    }
    if (!isset($object->uri) || $object->uri == '' || $images === null) {
        return;
    }
    $url = '';
    foreach (array_reverse($images) as $image) {
        if (!empty($image->url)) {
            $url = $image->url;
            break;
        }
    }
    if (!isset($GLOBALS['artwork_url_buffer'])) {
        $GLOBALS['artwork_url_buffer'] = array();
        register_shutdown_function('flushArtworkUrls', $w);
    }
    $GLOBALS['artwork_url_buffer'][$object->uri] = $url;
    if (count($GLOBALS['artwork_url_buffer']) >= 500) {
        flushArtworkUrls($w);
    }
}

function flushArtworkUrls($w)
{
    if (empty($GLOBALS['artwork_url_buffer'])) {
        return;
    }
    $buffer = $GLOBALS['artwork_url_buffer'];
    $GLOBALS['artwork_url_buffer'] = array();
    try {
        $db = artworkUrlDatabase($w);
        $ownTransaction = !$db->inTransaction();
        if ($ownTransaction) {
            $db->beginTransaction();
        }
        $stmt = $db->prepare('INSERT INTO artwork_urls (uri,url) VALUES (:uri,:url)
            ON CONFLICT(uri) DO UPDATE SET url=excluded.url WHERE url != excluded.url');
        foreach ($buffer as $uri => $url) {
            $stmt->execute(array(':uri' => $uri, ':url' => $url));
        }
        if ($ownTransaction) {
            $db->commit();
        }
    } catch (PDOException $e) {
        if (isset($db) && isset($ownTransaction) && $ownTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        logMsg($w, 'Error(flushArtworkUrls): (exception ' . jTraceEx($e) . ')');
    }
}

function cachedArtworkUrl($db, $uri)
{
    if (isset($GLOBALS['artwork_url_buffer'][$uri])) {
        return $GLOBALS['artwork_url_buffer'][$uri];
    }
    $stmt = $db->prepare('SELECT url FROM artwork_urls WHERE uri=:uri');
    $stmt->execute(array(':uri' => $uri));
    return $stmt->fetchColumn();
}

/**
 * Image URL remembered for $uri, or false. Never creates fetch_artworks.db.
 */
function lookupCachedArtworkUrl($w, $uri)
{
    if (isset($GLOBALS['artwork_url_buffer'][$uri])) {
        return $GLOBALS['artwork_url_buffer'][$uri];
    }
    if (!is_file($w->data() . '/fetch_artworks.db')) {
        return false;
    }
    try {
        return cachedArtworkUrl(artworkUrlDatabase($w), $uri);
    } catch (PDOException $e) {
        return false;
    }
}

function artworkTarget($w, $type, $row)
{
    $name = $type == 'artist'
        ? truncateStr(urlencode(escapeQuery($row['artist_name'])), 250)
        : substr($row[$type . '_uri'], strrpos($row[$type . '_uri'], ':') + 1);
    return $w->data() . '/artwork/' . md5($name . '.png') . '/' . $name . '.png';
}

/**
 * Directory of images downloaded/extracted once and copied to every artwork that uses them.
 * It survives pauses (Spotify cooldowns, interrupted runs) and is removed when the queue completes.
 */
function artworkSharedDirectory($w)
{
    return $w->data() . '/artwork/by-url';
}

function removeArtworkSharedDirectory($w)
{
    $directory = artworkSharedDirectory($w);
    if (!is_dir($directory)) {
        return;
    }
    foreach (glob($directory . '/{,.}*', GLOB_BRACE) ?: array() as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
    @rmdir($directory);
}

function artworkBackoff($w)
{
    $path = $w->data() . '/artwork_retry_after.json';
    return is_file($path) ? (json_decode(file_get_contents($path), true) ?: array()) : array();
}

function artworkRetryAt($w)
{
    $backoff = artworkBackoff($w);
    return isset($backoff['retry_at']) ? (int) $backoff['retry_at'] : 0;
}

function writeArtworkProgress($w, $completed, $total, $label = 'Download Artworks')
{
    $path = $w->data() . '/download_artworks_in_progress';
    $old = is_file($path) ? explode('▹', file_get_contents($path)) : array();
    $retryAt = artworkRetryAt($w);
    if ($retryAt > time()) {
        $label = 'Artwork downloads paused by Spotify';
    }
    $started = isset($old[3]) && $old[3] != '' ? $old[3] : time();
    $w->write($label . '▹' . $completed . '▹' . $total . '▹' . $started . '▹' . $retryAt, 'download_artworks_in_progress');
}

function setArtworkBackoff($w, $api)
{
    $response = $api->getRequest()->getLastResponse();
    $seconds = isset($response['headers']['retry-after']) ? (int) $response['headers']['retry-after'] : 30;
    $seconds = max(1, $seconds);
    $state = array('retry_at' => time() + $seconds);
    file_put_contents($w->data() . '/artwork_retry_after.json', json_encode($state), LOCK_EX);
    logMsg($w, 'Artwork downloads paused by Spotify for ' . $seconds . ' seconds');
    return $state['retry_at'];
}

/**
 * Record a failed attempt for $uri. Returns true when the placeholder should now be used.
 */
function recordArtworkFailure($db, $uri)
{
    $db->prepare('INSERT INTO artwork_failures (uri,attempts) VALUES (:uri,1)
        ON CONFLICT(uri) DO UPDATE SET attempts=attempts+1')->execute(array(':uri' => $uri));
    $stmt = $db->prepare('SELECT attempts FROM artwork_failures WHERE uri=:uri');
    $stmt->execute(array(':uri' => $uri));
    return (int) $stmt->fetchColumn() >= ARTWORK_MAX_ATTEMPTS;
}

/**
 * Copy the placeholder image of $type to $destination and mark $uri as fetched.
 */
function publishArtworkPlaceholder($w, $db, $update, $type, $uri, $destination)
{
    if (!is_dir(dirname($destination))) {
        @mkdir(dirname($destination), 0700, true);
    }
    if (!@copy('./images/' . $type . 's.png', $destination)) {
        return false;
    }
    $update->execute(array(':uri' => $uri));
    $db->prepare('DELETE FROM artwork_failures WHERE uri=:uri')->execute(array(':uri' => $uri));
    return true;
}

// Older queues have only IDs. Recover image URLs from paged library responses,
// rather than making a separate metadata request for every queued track/album.
function seedArtworkUrls($w, $api)
{
    $path = $w->data() . '/artwork_url_seed.json';
    $state = is_file($path) ? json_decode(file_get_contents($path), true) : null;
    if (!is_array($state)) {
        $state = array('stage' => 0, 'offset' => 0, 'playlist' => 0);
    }
    if ($state['stage'] >= 3) {
        return;
    }
    $library = openSqliteDatabase($w->data() . '/library.db');
    $playlists = $library->query('SELECT uri FROM playlists WHERE ownedbyuser=1 OR collaborative=1')->fetchAll(PDO::FETCH_COLUMN);
    while ($state['stage'] < 3) {
        if ($state['stage'] == 2 && $state['playlist'] >= count($playlists)) {
            $state['stage'] = 3;
            file_put_contents($path, json_encode($state), LOCK_EX);
            break;
        }
        $options = array('limit' => $state['stage'] == 2 ? 100 : 50, 'offset' => $state['offset']);
        try {
            if ($state['stage'] == 0) {
                $page = $api->getMySavedTracks($options);
            } elseif ($state['stage'] == 1) {
                $page = $api->getMySavedAlbums($options);
            } else {
                $page = $api->getPlaylistItems($playlists[$state['playlist']], $options);
            }
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->getCode() == 429) {
                throw $e;
            }
            // any other error: skip this page, the remaining rows are fetched one by one
            logMsg($w, 'Error(seedArtworkUrls): stage ' . $state['stage'] . ' skipped (exception ' . $e->getCode() . ' ' . $e->getMessage() . ')');
            $page = (object) array('items' => array(), 'total' => 0);
        }
        foreach ($page->items as $item) {
            rememberArtworkObject($w, $item);
        }
        flushArtworkUrls($w);
        $progress = $w->read('download_artworks_in_progress');
        if (is_string($progress)) {
            $fields = explode('▹', $progress);
            writeArtworkProgress($w, isset($fields[1]) ? $fields[1] : 0, isset($fields[2]) ? $fields[2] : 0, 'Preparing artwork image URLs');
        }
        $state['offset'] += $options['limit'];
        if ($state['offset'] >= $page->total) {
            $state['offset'] = 0;
            if ($state['stage'] < 2) {
                ++$state['stage'];
            } else {
                ++$state['playlist'];
            }
        }
        file_put_contents($path, json_encode($state), LOCK_EX);
    }
}

function fetchQueuedArtworkUrl($w, $api, $type, $uri)
{
    $id = substr($uri, strrpos($uri, ':') + 1);
    if ($id === '' || strpos($id, 'fake') === 0) {
        return '';
    }
    $methods = array('artist' => 'getArtist', 'track' => 'getTrack', 'album' => 'getAlbum', 'show' => 'getShow', 'episode' => 'getEpisode');
    $object = $api->{$methods[$type]}($id);
    rememberArtworkObject($w, $object);
    flushArtworkUrls($w);
    $url = cachedArtworkUrl(artworkUrlDatabase($w), $uri);
    // the object has no usable image: use the placeholder
    return $url === false ? '' : $url;
}

// One request per distinct image URL, with four concurrent CDN transfers.
// Temporary files prevent cancelled/failed downloads from looking complete.
// Returns url => array('path' => file|false, 'http' => status code).
function downloadArtworkUrls($w, $jobs, $onDownload = null)
{
    $multi = curl_multi_init();
    $waiting = array_values($jobs);
    $active = array();
    $results = array();
    $next = 0;
    while ($waiting || $active) {
        while ($waiting && count($active) < 4) {
            $job = array_shift($waiting);
            $destination = artworkSharedDirectory($w) . '/' . hash('sha256', $job['url']);
            if (is_file($destination) && filesize($destination) > 0 && @getimagesize($destination) !== false) {
                $results[$job['url']] = array('path' => $destination, 'http' => 200);
                if ($onDownload) {
                    $onDownload($job['url'], $destination);
                }
                continue;
            }
            if (!is_dir(dirname($destination))) {
                mkdir(dirname($destination), 0700, true);
            }
            $temporary = tempnam(dirname($destination), '.download-');
            $file = fopen($temporary, 'wb');
            $handle = curl_init($job['url']);
            curl_setopt_array($handle, array(
                CURLOPT_FILE => $file, CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20,
                CURLOPT_FAILONERROR => true,
                CURLOPT_PRIVATE => (string) $next,
            ));
            curl_multi_add_handle($multi, $handle);
            $active[(string) $next] = array($handle, $file, $temporary, $destination, $job['url']);
            ++$next;
        }
        do {
            $status = curl_multi_exec($multi, $running);
        } while ($status == CURLM_CALL_MULTI_PERFORM);
        while ($finished = curl_multi_info_read($multi)) {
            $handle = $finished['handle'];
            $key = curl_getinfo($handle, CURLINFO_PRIVATE);
            list(, $file, $temporary, $destination, $url) = $active[$key];
            fclose($file);
            $http = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($finished['result'] == CURLE_OK && $http >= 200 && $http < 300 && filesize($temporary) > 0
                && @getimagesize($temporary) !== false) {
                rename($temporary, $destination);
                $results[$url] = array('path' => $destination, 'http' => $http);
                if ($onDownload) {
                    $onDownload($url, $destination);
                }
            } else {
                @unlink($temporary);
                $results[$url] = array('path' => false, 'http' => $http);
            }
            curl_multi_remove_handle($multi, $handle);
            unset($active[$key]);
        }
        if ($active && curl_multi_select($multi, 1.0) == -1) {
            usleep(10000);
        }
    }
    curl_multi_close($multi);
    return $results;
}

/**
 * Separate API client for the downloader: no automatic retry (429 is handled with a persisted
 * backoff) and short timeouts. It shares the session (tokens) of getSpotifyWebAPI() but has its
 * own Request, so the timeouts do not leak to other API calls of the process.
 */
function artworkDownloaderApi($w)
{
    getSpotifyWebAPI($w);
    $request = new SpotifyWebAPI\Request(array('curl_options' => array(CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20)));
    $api = new SpotifyWebAPI\SpotifyWebAPI(array('auto_refresh' => true, 'auto_retry' => false), spotifySessionHolder(), $request);
    return $api;
}

function processArtworkQueue($w, $api, $seed = true, $localCacheDirectories = null)
{
    // the queries below join artwork_urls: write buffered URLs first
    flushArtworkUrls($w);
    $imported = importSpotifyLocalArtwork($w, $localCacheDirectories);
    if ($imported > 0) {
        logMsg($w, 'Reused ' . $imported . ' artwork files from Spotify Desktop cache');
    }
    $db = artworkUrlDatabase($w);
    $tables = artworkQueueTables($db);
    $canRequest = artworkRetryAt($w) <= time();
    $missingUrls = false;
    foreach ($tables as $type => $table) {
        if ($db->query('SELECT 1 FROM ' . $table . ' AS a LEFT JOIN artwork_urls AS u ON u.uri=a.' . $type .
            '_uri WHERE a.already_fetched=0 AND u.uri IS NULL LIMIT 1')->fetchColumn() !== false) {
            $missingUrls = true;
            break;
        }
    }
    if ($seed && $canRequest && $missingUrls && is_file($w->data() . '/library.db')) {
        try {
            $api = $api !== null ? $api : artworkDownloaderApi($w);
            seedArtworkUrls($w, $api);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->getCode() == 429) {
                setArtworkBackoff($w, $api);
            } else {
                logMsg($w, 'Error(processArtworkQueue): cannot seed artwork URLs (exception ' . jTraceEx($e) . ')');
            }
            $canRequest = false;
        }
    }
    $jobs = array();
    $completed = 0;
    $total = 0;
    $updates = array();
    $consecutiveErrors = 0;
    foreach ($tables as $type => $table) {
        $total += (int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        $completed += (int) $db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE already_fetched=1')->fetchColumn();
        $updates[$type] = $db->prepare('UPDATE ' . $table . ' SET already_fetched=1 WHERE ' . $type . '_uri=:uri');
        foreach ($db->query('SELECT * FROM ' . $table . ' WHERE already_fetched=0')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uri = $row[$type . '_uri'];
            $destination = artworkTarget($w, $type, $row);
            if (is_file($destination) && filesize($destination) > 0 && @getimagesize($destination) !== false) {
                $updates[$type]->execute(array(':uri' => $uri));
                ++$completed;
                continue;
            }
            $url = cachedArtworkUrl($db, $uri);
            if ($url === false && $canRequest) {
                try {
                    $api = $api !== null ? $api : artworkDownloaderApi($w);
                    $url = fetchQueuedArtworkUrl($w, $api, $type, $uri);
                    $consecutiveErrors = 0;
                } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                    $code = $e->getCode();
                    if ($code == 429) {
                        setArtworkBackoff($w, $api);
                        $canRequest = false;
                    } elseif ($code == 400 || $code == 403 || $code == 404) {
                        // invalid or unavailable object: use the placeholder
                        $url = '';
                    } else {
                        // transient (network, 5xx...): retry on a later run, placeholder after ARTWORK_MAX_ATTEMPTS
                        logMsg($w, 'Error(processArtworkQueue): ' . $uri . ' (exception ' . $code . ' ' . $e->getMessage() . ')');
                        if (recordArtworkFailure($db, $uri)) {
                            $url = '';
                        }
                        if (++$consecutiveErrors >= 5) {
                            // probably offline or Spotify is down: stop requesting for this run
                            $canRequest = false;
                        }
                    }
                }
            }
            if ($url === false) {
                continue;
            }
            if ($url === '') {
                if (publishArtworkPlaceholder($w, $db, $updates[$type], $type, $uri, $destination)) {
                    ++$completed;
                }
            } else {
                $jobs[$url]['url'] = $url;
                $jobs[$url]['targets'][] = array('type' => $type, 'uri' => $uri, 'destination' => $destination);
            }
        }
    }
    writeArtworkProgress($w, $completed, $total);
    $results = downloadArtworkUrls($w, $jobs, function ($url, $path) use ($jobs, $updates, $w, &$completed, $total) {
        foreach ($jobs[$url]['targets'] as $target) {
            if (!is_dir(dirname($target['destination']))) {
                mkdir(dirname($target['destination']), 0700, true);
            }
            $temporary = tempnam(dirname($target['destination']), '.download-');
            if (!copy($path, $temporary)) {
                @unlink($temporary);
                continue;
            }
            rename($temporary, $target['destination']);
            $updates[$target['type']]->execute(array(':uri' => $target['uri']));
            ++$completed;
        }
        writeArtworkProgress($w, $completed, $total);
    });
    // failed transfers: placeholder for permanent CDN errors or after ARTWORK_MAX_ATTEMPTS
    foreach ($results as $url => $result) {
        if ($result['path'] !== false) {
            continue;
        }
        $permanent = in_array($result['http'], array(400, 403, 404, 410), true);
        foreach ($jobs[$url]['targets'] as $target) {
            if ($permanent || recordArtworkFailure($db, $target['uri'])) {
                if (publishArtworkPlaceholder($w, $db, $updates[$target['type']], $target['type'], $target['uri'], $target['destination'])) {
                    ++$completed;
                }
            }
        }
    }
    writeArtworkProgress($w, $completed, $total);
    return array('completed' => $completed, 'total' => $total, 'retry_at' => artworkRetryAt($w));
}

function downloadQueuedArtworks($w, $silent)
{
    // nothing to do is not an error
    if (!getSetting($w, 'use_artworks') || !is_file($w->data() . '/fetch_artworks.db')) {
        return true;
    }
    if (!$w->internet()) {
        if (!$silent) {
            displayNotificationWithArtwork($w, 'Download Artworks, No internet connection', './images/warning.png');
        }
        return false;
    }
    $lock = fopen($w->data() . '/download_artworks.lock', 'c');
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        // another download is running (possibly waiting for a Spotify cooldown)
        fclose($lock);
        return true;
    }
    putenv('LANG=fr_FR.UTF-8');
    ini_set('memory_limit', '512M');
    $started = time();
    try {
        $db = artworkUrlDatabase($w);
        $total = 0;
        $completed = 0;
        foreach (artworkQueueTables($db) as $table) {
            $total += (int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
            $completed += (int) $db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE already_fetched=1')->fetchColumn();
        }
        if (!$silent && $total > $completed) {
            displayNotificationWithArtwork($w, 'Start downloading ' . ($total - $completed) . ' artworks', './images/artworks.png', 'Artworks');
        }
        $w->write('Preparing artwork image URLs▹' . $completed . '▹' . $total . '▹' . $started . '▹' . artworkRetryAt($w), 'download_artworks_in_progress');
        do {
            $result = processArtworkQueue($w, null);
            $waiting = $result['retry_at'] > time();
            $label = $waiting ? 'Artwork downloads paused by Spotify' : 'Download Artworks';
            $w->write($label . '▹' . $result['completed'] . '▹' . $result['total'] . '▹' . $started . '▹' . $result['retry_at'], 'download_artworks_in_progress');
            if ($result['completed'] >= $result['total']) {
                @unlink($w->data() . '/download_artworks_in_progress');
                removeArtworkSharedDirectory($w);
                updateArtworkFolderSize($w);
                if (!$silent && $result['total'] > $completed) {
                    displayNotificationWithArtwork($w, 'All artworks have been downloaded (' . ($result['total'] - $completed) . ' artworks) - took ' . beautifyTime(time() - $started, true), './images/artworks.png', 'Artworks');
                }
                return true;
            }
            if (!$waiting) {
                // some transfers or requests failed: they are retried on the next run
                // (the placeholder is used after ARTWORK_MAX_ATTEMPTS)
                @unlink($w->data() . '/download_artworks_in_progress');
                $remaining = $result['total'] - $result['completed'];
                logMsg($w, 'Download Artworks: ' . $remaining . ' artworks could not be downloaded, they will be retried next time');
                if (!$silent) {
                    displayNotificationWithArtwork($w, $remaining . ' artworks could not be downloaded, they will be retried next time', './images/artworks.png', 'Artworks');
                }
                return true;
            }
            sleep(min(30, max(1, $result['retry_at'] - time())));
        } while (true);
    } catch (PDOException $e) {
        logMsg($w, 'Error(downloadQueuedArtworks): (exception ' . jTraceEx($e) . ')');
        @unlink($w->data() . '/download_artworks_in_progress');
        return false;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function updateArtworkFolderSize($w)
{
    if (!file_exists($w->data() . '/artwork')) {
        updateSetting($w, 'artwork_folder_size', '0');
        return;
    }
    exec("/usr/bin/du -sh '" . $w->data() . "/artwork'", $retArr, $retVal);
    if ($retVal == 0 && isset($retArr[0])) {
        $size = $retArr[0];
        $size = substr($size, 0, strpos($size, "\t"));
        updateSetting($w, 'artwork_folder_size', trim($size));
    }
}
