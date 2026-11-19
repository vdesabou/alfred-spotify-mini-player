<?php

// Spotify Desktop uses Chromium's Simple Cache. Read only its HTTP cache:
// https://github.com/chromium/chromium/blob/main/net/disk_cache/simple/simple_entry_format.h
function spotifyLocalCacheEntry($path)
{
    $file = @fopen($path, 'rb');
    if ($file === false) {
        return null;
    }
    try {
        $stat = fstat($file);
        $size = $stat['size'];
        $header = fread($file, 24);
        if (strlen($header) != 24 || substr($header, 0, 8) !== hex2bin('305c72a71b6dfbfc')) {
            return null;
        }
        $fields = unpack('Vversion/Vkey_length', substr($header, 8, 8));
        if ($fields['version'] != 5 || $fields['key_length'] < 1 || $fields['key_length'] > 65536 || $size < $fields['key_length'] + 72) {
            return null;
        }
        $key = fread($file, $fields['key_length']);
        if (!preg_match_all('~https?://[^\x00-\x20]+~', $key, $urls)) {
            return null;
        }
        fseek($file, -24, SEEK_END);
        $footer = fread($file, 24);
        if (substr($footer, 0, 8) !== hex2bin('d8410d97456ffaf4')) {
            return null;
        }
        $end = unpack('Vflags/Vcrc/Vlength', substr($footer, 8, 12));
        $hashLength = ($end['flags'] & 2) ? 32 : 0;
        if ($hashLength) {
            fseek($file, -56, SEEK_END);
            if (!hash_equals(hash('sha256', $key, true), fread($file, 32))) {
                return null;
            }
        }
        $offset = 24 + $fields['key_length'];
        $bodyEnd = $size - 24 - $hashLength - $end['length'] - 24;
        if ($bodyEnd < $offset || $bodyEnd - $offset > 16777216 || $end['length'] < 1 || $end['length'] > 262144) {
            return null;
        }
        fseek($file, $bodyEnd + 24);
        $response = fread($file, $end['length']);
        if (strlen($response) != $end['length'] || !preg_match('~HTTP/[0-9.]+ ([0-9]{3})~', $response, $status)
            || (int) $status[1] < 200 || (int) $status[1] >= 300) {
            return null;
        }
        if (($end['flags'] & 1) && hash('crc32b', $response) !== sprintf('%08x', $end['crc'])) {
            return null;
        }
        fseek($file, $bodyEnd);
        $bodyFooter = fread($file, 24);
        if (substr($bodyFooter, 0, 8) !== hex2bin('d8410d97456ffaf4')) {
            return null;
        }
        $bodyInfo = unpack('Vflags/Vcrc', substr($bodyFooter, 8, 8));
        return array(
            'path' => $path, 'url' => end($urls[0]),
            'offset' => $offset, 'length' => $bodyEnd - $offset,
            'crc' => ($bodyInfo['flags'] & 1) ? sprintf('%08x', $bodyInfo['crc']) : null,
            'modified' => $stat['mtime'],
        );
    } finally {
        fclose($file);
    }
}

function spotifyLocalCacheBody($entry)
{
    // Recheck the key in case Spotify replaced this file after it was indexed.
    $current = spotifyLocalCacheEntry($entry['path']);
    if ($current === null || $current['url'] !== $entry['url']) {
        return null;
    }
    $body = @file_get_contents($current['path'], false, null, $current['offset'], $current['length']);
    if ($body === false || strlen($body) != $current['length']) {
        return null;
    }
    if ($current['crc'] !== null && hash('crc32b', $body) !== $current['crc']) {
        return null;
    }
    return $body;
}

function spotifyArtworkZstdCommand()
{
    static $checked = false;
    static $command = null;
    if (!$checked) {
        $checked = true;
        $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        $paths = array_merge($paths, array('/opt/homebrew/bin', '/usr/local/bin'));
        foreach (array_unique($paths) as $path) {
            if ($path !== '' && is_executable($path . '/zstd')) {
                $command = $path . '/zstd';
                break;
            }
        }
    }
    return $command;
}

function spotifyLocalCacheJson($body)
{
    if ($body === null) {
        return null;
    }
    if (substr($body, 0, 2) === "\x1f\x8b") {
        $body = @gzdecode($body, 4194304);
    } elseif (substr($body, 0, 4) === "\x28\xb5\x2f\xfd") {
        // Optional: a PHP Zstandard extension or the zstd executable. Without
        // either, these metadata entries are skipped; exact URL hits still work.
        if (function_exists('zstd_uncompress')) {
            try {
                $body = @zstd_uncompress($body);
            } catch (Throwable $e) {
                return null;
            }
        } else {
            $command = spotifyArtworkZstdCommand();
            if ($command === null || !function_exists('proc_open') || strlen($body) > 4194304) {
                return null;
            }
            $input = tmpfile();
            $output = tmpfile();
            $errors = tmpfile();
            fwrite($input, $body);
            rewind($input);
            $process = @proc_open(array($command, '-d', '-q', '-c', '--memory=16MB'),
                array(0 => $input, 1 => $output, 2 => $errors), $pipes);
            if (!is_resource($process)) {
                fclose($input);
                fclose($output);
                fclose($errors);
                return null;
            }
            $deadline = microtime(true) + 3;
            do {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    break;
                }
                if (microtime(true) > $deadline || fstat($output)['size'] > 4194304) {
                    proc_terminate($process);
                    break;
                }
                usleep(1000);
            } while (true);
            proc_close($process);
            rewind($output);
            $body = $status['exitcode'] == 0 ? stream_get_contents($output, 4194305) : false;
            fclose($input);
            fclose($output);
            fclose($errors);
        }
    }
    if (!is_string($body) || strlen($body) > 4194304) {
        return null;
    }
    $object = json_decode($body, true, 64);
    return is_array($object) ? $object : null;
}

function spotifyUriFromGid($type, $gid)
{
    if (!is_string($gid) || !preg_match('/^[0-9a-f]{32}$/i', $gid)) {
        return null;
    }
    $bytes = array_values(unpack('C*', hex2bin($gid)));
    $alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $encoded = '';
    do {
        $carry = 0;
        foreach ($bytes as &$byte) {
            $value = $carry * 256 + $byte;
            $byte = intdiv($value, 62);
            $carry = $value % 62;
        }
        unset($byte);
        $encoded = $alphabet[$carry] . $encoded;
    } while (array_sum($bytes) > 0);
    return 'spotify:' . $type . ':' . str_pad($encoded, 22, '0', STR_PAD_LEFT);
}

function spotifyLocalImageIds($object, $type)
{
    $group = array('track' => 'cover_group', 'album' => 'cover_group', 'artist' => 'portrait_group',
        'show' => 'cover_image', 'episode' => 'cover_image')[$type];
    $images = $object[$group]['image'] ?? array();
    $other = $object[$type == 'artist' ? 'portrait' : 'cover'] ?? array();
    $images = array_merge(is_array($images) ? $images : array(), is_array($other) ? $other : array());
    $ids = array();
    foreach ($images as $image) {
        if (is_array($image) && is_string($image['file_id'] ?? null) && preg_match('/^[0-9a-f]{40}$/i', $image['file_id'])) {
            $ids[] = strtolower($image['file_id']);
        }
    }
    return array_values(array_unique($ids));
}

function spotifyAssociateLocalImages(&$index, $uri, $ids)
{
    if ($uri === null || !$ids) {
        return;
    }
    if (array_key_exists($uri, $index['uris'])) {
        if ($index['uris'][$uri] === false) {
            return;
        }
        // Cached responses may disagree: retain only IDs they agree on.
        $ids = array_values(array_intersect($index['uris'][$uri], $ids));
        $index['uris'][$uri] = $ids ?: false;
    } else {
        $index['uris'][$uri] = $ids;
    }
}

function spotifyLocalImageId($url)
{
    if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !spotifyLocalImageHost(strtolower(parse_url($url, PHP_URL_HOST) ?: ''))) {
        return null;
    }
    return preg_match('~/image/([0-9a-f]{40})(?:[/?]|$)~i', $url, $match) ? strtolower($match[1]) : null;
}

function spotifyLocalImageHost($host)
{
    return preg_match('/^(i\.scdn\.co|mosaic\.scdn\.co|image-cdn-[a-z0-9-]+\.spotifycdn\.com|pickasso\.spotifycdn\.com|seed-mix-image\.spotifycdn\.com)$/', $host) === 1;
}

function spotifyLocalMetadataAssociations($object, $type, $gid)
{
    $index = array('uris' => array());
    if ($object === null || !is_string($object['gid'] ?? null) || strtolower($object['gid']) !== strtolower($gid)) {
        return array();
    }
    $uri = spotifyUriFromGid($type, $gid);
    if ($type == 'track') {
        $album = $object['album'] ?? array();
        if (!is_array($album)) {
            return array();
        }
        $ids = spotifyLocalImageIds($album, 'album');
        spotifyAssociateLocalImages($index, $uri, $ids);
        spotifyAssociateLocalImages($index, spotifyUriFromGid('album', $album['gid'] ?? null), $ids);
        $artists = $object['artist'] ?? array();
        $albumArtists = $album['artist'] ?? array();
        foreach (array_merge(is_array($artists) ? $artists : array(), is_array($albumArtists) ? $albumArtists : array()) as $artist) {
            if (is_array($artist)) {
                spotifyAssociateLocalImages($index, spotifyUriFromGid('artist', $artist['gid'] ?? null), spotifyLocalImageIds($artist, 'artist'));
            }
        }
    } else {
        spotifyAssociateLocalImages($index, $uri, spotifyLocalImageIds($object, $type));
    }
    return $index['uris'];
}

function spotifyLocalArtworkIndex($directories, $metadataFile = null)
{
    static $cached = array();
    $key = ($metadataFile ?? '') . "\n" . implode("\n", $directories);
    if (isset($cached[$key]) && time() - $cached[$key]['time'] < 300) {
        return $cached[$key]['index'];
    }
    $index = array('urls' => array(), 'ids' => array(), 'uris' => array());
    $metadata = $metadataFile !== null && is_file($metadataFile)
        ? json_decode(file_get_contents($metadataFile, false, null, 0, 8388608), true) : array();
    $metadata = is_array($metadata) ? $metadata : array();
    $usedMetadata = array();
    $changed = false;
    foreach ($directories as $directory) {
        foreach (glob($directory . '/*_0') ?: array() as $path) {
            $entry = spotifyLocalCacheEntry($path);
            if ($entry === null) {
                continue;
            }
            $url = parse_url($entry['url']);
            if (($url['scheme'] ?? '') !== 'https') {
                continue;
            }
            $host = strtolower($url['host'] ?? '');
            $urlPath = $url['path'] ?? '';
            if (spotifyLocalImageHost($host)) {
                $index['urls'][$entry['url']][] = $entry;
                $id = spotifyLocalImageId($entry['url']);
                if ($id !== null) {
                    $index['ids'][$id][] = $entry;
                }
                continue;
            }
            if ($host !== 'spclient.wg.spotify.com' && !preg_match('/(^|[-.])spclient\.spotify\.com$/', $host)) {
                continue;
            }
            if (preg_match('~^/metadata/4/(track|album|artist|show|episode)/([0-9a-f]{32})$~i', $urlPath, $match)) {
                $signature = hash('sha256', json_encode(array(1, $entry['url'], $entry['length'], $entry['crc'], $entry['modified'])));
                if (($metadata[$path]['signature'] ?? null) !== $signature || !is_array($metadata[$path]['images'] ?? null)) {
                    $object = spotifyLocalCacheJson(spotifyLocalCacheBody($entry));
                    $metadata[$path] = array('signature' => $signature,
                        'images' => spotifyLocalMetadataAssociations($object, strtolower($match[1]), $match[2]));
                    $changed = true;
                }
                $usedMetadata[$path] = $metadata[$path];
                foreach ($metadata[$path]['images'] as $uri => $ids) {
                    if ($ids === false) {
                        $index['uris'][$uri] = false;
                    } else {
                        spotifyAssociateLocalImages($index, $uri, $ids);
                    }
                }
            } elseif (preg_match('~^/color-lyrics/v2/track/([0-9a-zA-Z]{22})/image/(.+)$~', $urlPath, $match)) {
                $image = rawurldecode($match[2]);
                $id = spotifyLocalImageId($image);
                if ($id === null && preg_match('/^spotify:image:([0-9a-f]{40})$/i', $image, $imageMatch)) {
                    $id = strtolower($imageMatch[1]);
                }
                if ($id !== null) {
                    spotifyAssociateLocalImages($index, 'spotify:track:' . $match[1], array($id));
                }
            }
        }
    }
    $changed = $changed || count($metadata) !== count($usedMetadata);
    if ($changed && $metadataFile !== null) {
        $temporary = tempnam(dirname($metadataFile), '.spotify-metadata-');
        if ($temporary !== false) {
            file_put_contents($temporary, json_encode($usedMetadata));
            rename($temporary, $metadataFile);
        }
    }
    $cached[$key] = array('time' => time(), 'index' => $index);
    return $index;
}

function spotifyLocalArtworkCandidates($index, $uri, $url, $album)
{
    // Treat image IDs as opaque. Never match names, prefixes, or ID suffixes.
    if (is_string($url) && isset($index['urls'][$url])) {
        return $index['urls'][$url];
    }
    $knownId = is_string($url) ? spotifyLocalImageId($url) : null;
    if ($knownId !== null && isset($index['ids'][$knownId])) {
        return $index['ids'][$knownId];
    }
    if (array_key_exists($uri, $index['uris'])) {
        $ids = $index['uris'][$uri];
    } elseif ($album !== null && isset($index['uris'][$album])) {
        $ids = $index['uris'][$album];
    } else {
        return array();
    }
    if ($ids === false) {
        return array();
    }
    $entries = array();
    foreach ($ids as $id) {
        foreach ($index['ids'][$id] ?? array() as $entry) {
            $entries[$entry['path']] = $entry;
        }
    }
    usort($entries, function ($left, $right) { return $left['length'] <=> $right['length']; });
    return $entries;
}

function spotifyPublishLocalArtwork($data, $destination)
{
    if (!is_dir(dirname($destination)) && !@mkdir(dirname($destination), 0700, true)) {
        return false;
    }
    $temporary = tempnam(dirname($destination), '.spotify-local-');
    if ($temporary === false) {
        return false;
    }
    if (file_put_contents($temporary, $data) !== strlen($data) || !rename($temporary, $destination)) {
        @unlink($temporary);
        return false;
    }
    return true;
}

function importSpotifyLocalArtwork($w, $directories = null)
{
    if ($directories === null) {
        $root = getenv('HOME') . '/Library/Caches/com.spotify.client/';
        $directories = array($root . 'Browser/Cache/Cache_Data', $root . 'Default/Cache/Cache_Data');
    }
    $directories = array_values(array_filter($directories, 'is_dir'));
    if (!$directories) {
        return 0;
    }
    $index = spotifyLocalArtworkIndex($directories, $w->cache() . '/spotify-artwork-metadata.json');
    $db = artworkUrlDatabase($w);
    $albums = array();
    if (is_file($w->data() . '/library.db')) {
        $library = openSqliteDatabase($w->data() . '/library.db');
        foreach ($library->query('SELECT DISTINCT uri,album_uri FROM tracks')->fetchAll(PDO::FETCH_NUM) as $row) {
            if (array_key_exists($row[0], $albums) && $albums[$row[0]] !== $row[1]) {
                $albums[$row[0]] = null;
            } else {
                $albums[$row[0]] = $row[1];
            }
        }
    }
    $count = 0;
    $bodies = array();
    $saveUrl = $db->prepare('INSERT INTO artwork_urls(uri,url) VALUES(:uri,:url)
        ON CONFLICT(uri) DO UPDATE SET url=excluded.url');
    foreach (array('track', 'album', 'artist', 'show', 'episode') as $type) {
        $table = $type . 's';
        $update = $db->prepare('UPDATE ' . $table . ' SET already_fetched=1 WHERE ' . $type . '_uri=:uri');
        foreach ($db->query('SELECT * FROM ' . $table . ' WHERE already_fetched=0')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $uri = $row[$type . '_uri'];
            $destination = artworkTarget($w, $type, $row);
            if (is_file($destination) && @getimagesize($destination) !== false) {
                continue;
            }
            $candidates = spotifyLocalArtworkCandidates($index, $uri, cachedArtworkUrl($db, $uri), $albums[$uri] ?? null);
            foreach ($candidates as $entry) {
                if (!array_key_exists($entry['path'], $bodies)) {
                    $body = spotifyLocalCacheBody($entry);
                    if ($body !== null && substr($body, 0, 2) === "\x1f\x8b") {
                        $body = @gzdecode($body, 16777216);
                    }
                    $image = is_string($body) ? @getimagesizefromstring($body) : false;
                    $shared = $w->data() . '/artwork/by-url/' . hash('sha256', $entry['url']);
                    $bodies[$entry['path']] = $image !== false && in_array($image['mime'], array('image/jpeg', 'image/png', 'image/webp'), true)
                        && spotifyPublishLocalArtwork($body, $shared) ? $shared : null;
                }
                $shared = $bodies[$entry['path']];
                if ($shared === null) {
                    continue;
                }
                if (!spotifyPublishLocalArtwork(file_get_contents($shared), $destination)) {
                    continue;
                }
                $saveUrl->execute(array(':uri' => $uri, ':url' => $entry['url']));
                $update->execute(array(':uri' => $uri));
                ++$count;
                break;
            }
        }
    }
    return $count;
}
