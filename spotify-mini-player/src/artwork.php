<?php

function artworkUri($type, $uri)
{
    $parts = explode(':', $uri);
    return 'spotify:' . $type . ':' . end($parts);
}

function artworkUrlDatabase($w)
{
    $db = openSqliteDatabase($w->data() . '/fetch_artworks.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS artwork_urls (uri TEXT PRIMARY KEY, url TEXT NOT NULL)');
    return $db;
}

function remainingArtworkCount($w)
{
    $file = $w->data() . '/fetch_artworks.db';
    if (!is_file($file)) {
        return 0;
    }
    $db = openSqliteDatabase($file);
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $remaining = 0;
    foreach (array('tracks', 'albums', 'artists', 'shows', 'episodes') as $table) {
        if (in_array($table, $tables, true)) {
            $remaining += (int) $db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE already_fetched=0')->fetchColumn();
        }
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
    if (isset($object->album)) {
        rememberArtworkObject($w, $object->album);
    }
    $images = $object->images ?? $object->album->images ?? null;
    if (!isset($object->uri) || $images === null) {
        return;
    }
    $url = '';
    foreach (array_reverse($images) as $image) {
        if (!empty($image->url)) {
            $url = $image->url;
            break;
        }
    }
    $db = artworkUrlDatabase($w);
    $stmt = $db->prepare('INSERT INTO artwork_urls (uri,url) VALUES (:uri,:url)
        ON CONFLICT(uri) DO UPDATE SET url=excluded.url WHERE url != excluded.url');
    $stmt->execute(array(':uri' => $object->uri, ':url' => $url));
}

function cachedArtworkUrl($db, $uri)
{
    $stmt = $db->prepare('SELECT url FROM artwork_urls WHERE uri=:uri');
    $stmt->execute(array(':uri' => $uri));
    return $stmt->fetchColumn();
}

function artworkTarget($w, $type, $row)
{
    $name = $type == 'artist'
        ? truncateStr(urlencode(escapeQuery($row['artist_name'])), 250)
        : substr($row[$type . '_uri'], strrpos($row[$type . '_uri'], ':') + 1);
    return $w->data() . '/artwork/' . md5($name . '.png') . '/' . $name . '.png';
}

function artworkBackoff($w)
{
    $path = $w->data() . '/artwork_retry_after.json';
    return is_file($path) ? (json_decode(file_get_contents($path), true) ?: array()) : array();
}

function writeArtworkProgress($w, $completed, $total, $label = 'Download Artworks')
{
    $path = $w->data() . '/download_artworks_in_progress';
    $old = is_file($path) ? explode('▹', file_get_contents($path)) : array();
    $retryAt = artworkBackoff($w)['retry_at'] ?? 0;
    if ($retryAt > time()) {
        $label = 'Artwork downloads paused by Spotify';
    }
    $w->write($label . '▹' . $completed . '▹' . $total . '▹' . ($old[3] ?? time()) . '▹' . $retryAt, 'download_artworks_in_progress');
}

function setArtworkBackoff($w, $api)
{
    $response = $api->getRequest()->getLastResponse();
    $seconds = max(1, (int) ($response['headers']['retry-after'] ?? 30));
    $state = array('retry_at' => time() + $seconds);
    file_put_contents($w->data() . '/artwork_retry_after.json', json_encode($state), LOCK_EX);
    logMsg($w, 'Artwork downloads paused by Spotify for ' . $seconds . ' seconds');
    return $state['retry_at'];
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
            if ($e->getCode() != 403 && $e->getCode() != 404) {
                throw $e;
            }
            $page = (object) array('items' => array(), 'total' => 0);
        }
        foreach ($page->items as $item) {
            rememberArtworkObject($w, $item);
        }
        $progress = $w->read('download_artworks_in_progress');
        if (is_string($progress)) {
            $fields = explode('▹', $progress);
            writeArtworkProgress($w, $fields[1] ?? 0, $fields[2] ?? 0, 'Preparing artwork image URLs');
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
    if (strpos($id, 'fake') === 0) {
        return '';
    }
    $methods = array('artist' => 'getArtist', 'track' => 'getTrack', 'album' => 'getAlbum', 'show' => 'getShow', 'episode' => 'getEpisode');
    $object = $api->{$methods[$type]}($id);
    rememberArtworkObject($w, $object);
    return cachedArtworkUrl(artworkUrlDatabase($w), $uri);
}

// One request per distinct image URL, with four concurrent CDN transfers.
// Temporary files prevent cancelled/failed downloads from looking complete.
function downloadArtworkUrls($w, $jobs, $onDownload = null)
{
    $multi = curl_multi_init();
    $waiting = array_values($jobs);
    $active = array();
    $results = array();
    while ($waiting || $active) {
        while ($waiting && count($active) < 4) {
            $job = array_shift($waiting);
            $destination = $w->data() . '/artwork/by-url/' . hash('sha256', $job['url']);
            if (is_file($destination) && filesize($destination) > 0 && @getimagesize($destination) !== false) {
                $results[$job['url']] = $destination;
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
            ));
            curl_multi_add_handle($multi, $handle);
            $active[spl_object_id($handle)] = array($handle, $file, $temporary, $destination, $job['url']);
        }
        do {
            $status = curl_multi_exec($multi, $running);
        } while ($status == CURLM_CALL_MULTI_PERFORM);
        while ($finished = curl_multi_info_read($multi)) {
            $handle = $finished['handle'];
            list(, $file, $temporary, $destination, $url) = $active[spl_object_id($handle)];
            fclose($file);
            $http = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($finished['result'] == CURLE_OK && $http >= 200 && $http < 300 && filesize($temporary) > 0
                && @getimagesize($temporary) !== false) {
                rename($temporary, $destination);
                $results[$url] = $destination;
                if ($onDownload) {
                    $onDownload($url, $destination);
                }
            } else {
                unlink($temporary);
                $results[$url] = false;
            }
            curl_multi_remove_handle($multi, $handle);
            unset($active[spl_object_id($handle)]);
        }
        if ($active && curl_multi_select($multi, 1.0) == -1) {
            usleep(10000);
        }
    }
    return $results;
}

function processArtworkQueue($w, $api, $seed = true)
{
    $db = artworkUrlDatabase($w);
    $backoff = artworkBackoff($w);
    $canRequest = ($backoff['retry_at'] ?? 0) <= time();
    if ($seed && $canRequest && is_file($w->data() . '/library.db')) {
        try {
            seedArtworkUrls($w, $api);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->getCode() != 429) {
                throw $e;
            }
            setArtworkBackoff($w, $api);
            $canRequest = false;
        }
    }
    $jobs = array();
    $completed = 0;
    $total = 0;
    $updates = array();
    foreach (array('track', 'album', 'artist', 'show', 'episode') as $type) {
        $table = $type == 'show' ? 'shows' : $type . 's';
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
                    $url = fetchQueuedArtworkUrl($w, $api, $type, $uri);
                } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                    if ($e->getCode() == 429) {
                        setArtworkBackoff($w, $api);
                        $canRequest = false;
                    } elseif ($e->getCode() == 403 || $e->getCode() == 404) {
                        $url = '';
                    } else {
                        throw $e;
                    }
                }
            }
            if ($url === false) {
                continue;
            }
            $job = array('type' => $type, 'uri' => $uri, 'destination' => $destination);
            if ($url === '') {
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0700, true);
                }
                if (!copy('./images/' . $table . '.png', $destination)) {
                    continue;
                }
                $updates[$type]->execute(array(':uri' => $uri));
                ++$completed;
            } else {
                $jobs[$url]['url'] = $url;
                $jobs[$url]['targets'][] = $job;
            }
        }
    }
    writeArtworkProgress($w, $completed, $total);
    downloadArtworkUrls($w, $jobs, function ($url, $path) use ($jobs, $updates, $w, &$completed, $total) {
        foreach ($jobs[$url]['targets'] as $target) {
            if (!is_dir(dirname($target['destination']))) {
                mkdir(dirname($target['destination']), 0700, true);
            }
            $temporary = tempnam(dirname($target['destination']), '.download-');
            if (!copy($path, $temporary)) {
                unlink($temporary);
                continue;
            }
            rename($temporary, $target['destination']);
            $updates[$target['type']]->execute(array(':uri' => $target['uri']));
            ++$completed;
        }
        writeArtworkProgress($w, $completed, $total);
    });
    return array('completed' => $completed, 'total' => $total, 'retry_at' => artworkBackoff($w)['retry_at'] ?? 0);
}

function downloadQueuedArtworks($w, $silent)
{
    if (!getSetting($w, 'use_artworks') || !is_file($w->data() . '/fetch_artworks.db')) {
        return;
    }
    $lock = fopen($w->data() . '/download_artworks.lock', 'c');
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        return;
    }
    $started = time();
    $db = artworkUrlDatabase($w);
    $total = 0;
    $completed = 0;
    foreach (array('tracks', 'albums', 'artists', 'shows', 'episodes') as $table) {
        $total += (int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        $completed += (int) $db->query('SELECT COUNT(*) FROM ' . $table . ' WHERE already_fetched=1')->fetchColumn();
    }
    writeArtworkProgress($w, $completed, $total, 'Preparing artwork image URLs');
    $api = clone getSpotifyWebAPI($w);
    $api->setOptions(array('auto_retry' => false));
    $api->getRequest()->setOptions(array('curl_options' => array(CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20)));
    try {
        do {
            $result = processArtworkQueue($w, $api);
            $waiting = $result['retry_at'] > time();
            $label = $waiting ? 'Artwork downloads paused by Spotify' : 'Download Artworks';
            $w->write($label . '▹' . $result['completed'] . '▹' . $result['total'] . '▹' . $started . '▹' . $result['retry_at'], 'download_artworks_in_progress');
            if ($result['completed'] >= $result['total']) {
                unlink($w->data() . '/download_artworks_in_progress');
                $size = 0;
                if (is_dir($w->data() . '/artwork')) {
                    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($w->data() . '/artwork', FilesystemIterator::SKIP_DOTS));
                    foreach ($files as $file) {
                        $size += $file->getSize();
                    }
                }
                updateSetting($w, 'artwork_folder_size', round($size / 1048576, 1) . ' MB');
                if (!$silent) {
                    displayNotificationWithArtwork($w, 'All artworks have been downloaded', './images/artworks.png', 'Artworks');
                }
                return true;
            }
            if (!$waiting) {
                // A transfer failed. Preserve the pending rows for the next run.
                return false;
            }
            sleep(min(30, max(1, $result['retry_at'] - time())));
        } while (true);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
