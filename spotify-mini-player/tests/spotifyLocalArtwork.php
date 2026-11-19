<?php
// Run with: php -d disable_functions=exec tests/spotifyLocalArtwork.php
// Synthetic Chromium cache entries only; no real Spotify cache or network.
chdir(dirname(__DIR__));
require './src/functions.php';

function checkLocalArtwork($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function cacheFixture($directory, $url, $body, $corrupt = false, $version = 5, $status = 200)
{
    $key = '1/0/_dk_https://xpui.app.spotify.com ' . $url;
    $headers = "HTTP/1.1 " . $status . " Status\0Content-Type: application/octet-stream\0";
    $footer = hex2bin('d8410d97456ffaf4');
    $bodyEnd = $footer . pack('V4', 1, hexdec(hash('crc32b', $body)), 0, 0);
    $end = $footer . pack('V4', 3, hexdec(hash('crc32b', $headers)), strlen($headers), 0);
    if ($corrupt) {
        $body[strlen($body) - 1] = chr(ord($body[strlen($body) - 1]) ^ 1);
    }
    $data = hex2bin('305c72a71b6dfbfc') . pack('V4', $version, strlen($key), 0, 0) .
        $key . $body . $bodyEnd . $headers . hash('sha256', $key, true) . $end;
    $path = $directory . '/' . substr(hash('sha256', $url), 0, 16) . '_0';
    file_put_contents($path, $data);
    return $path;
}

function metadataFixture($directory, $number, $album, $image, $query = '', $bodyGid = null)
{
    $gid = sprintf('%032x', $number);
    $object = array('gid' => $bodyGid ?? $gid, 'album' => array(
        'gid' => sprintf('%032x', $album),
        'cover_group' => array('image' => array(array('file_id' => $image))),
    ));
    return cacheFixture($directory, 'https://spclient.wg.spotify.com/metadata/4/track/' . $gid . $query,
        gzencode(json_encode($object)));
}

$temporary = sys_get_temp_dir() . '/spotify-local-cache-test-' . bin2hex(random_bytes(8));
foreach (array('', '/data', '/cache', '/spotify-cache') as $suffix) {
    mkdir($temporary . $suffix);
}
putenv('alfred_workflow_data=' . $temporary . '/data');
putenv('alfred_workflow_cache=' . $temporary . '/cache');
$w = new Workflows('com.vdesabou.spotify.mini.player');
$cache = $temporary . '/spotify-cache';
$image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aav8AAAAASUVORK5CYII=');
$ids = array();
for ($i = 0; $i < 10; ++$i) {
    $ids[] = 'ab67616d00004851' . str_repeat((string) $i, 24);
}
$url = 'https://i.scdn.co/image/';
$uri = function ($type, $number) { return spotifyUriFromGid($type, sprintf('%032x', $number)); };

try {
    $db = artworkUrlDatabase($w);
    foreach (array('track', 'album', 'show', 'episode') as $type) {
        $db->exec('CREATE TABLE ' . $type . 's (' . $type . '_uri TEXT PRIMARY KEY, already_fetched INTEGER)');
    }
    $db->exec('CREATE TABLE artists (artist_uri TEXT PRIMARY KEY, artist_name TEXT, already_fetched INTEGER)');
    $library = new PDO('sqlite:' . $w->data() . '/library.db');
    $library->exec('CREATE TABLE tracks (uri TEXT, album_uri TEXT)');
    $library->prepare('INSERT INTO tracks VALUES (?,?)')->execute(array($uri('track', 4), $uri('album', 2)));
    foreach (array(1, 4, 5) as $number) {
        $db->prepare('INSERT INTO tracks VALUES (?,0)')->execute(array($uri('track', $number)));
    }
    $db->prepare('INSERT INTO albums VALUES (?,0)')->execute(array($uri('album', 2)));
    $db->prepare('INSERT INTO artists VALUES (?,?,0)')->execute(array($uri('artist', 3), 'Fixture Artist'));
    cacheFixture($cache, $url . $ids[0], $image);
    cacheFixture($cache, $url . $ids[1], $image);
    cacheFixture($cache, $url . $ids[2], $image);
    metadataFixture($cache, 1, 2, $ids[0]);
    cacheFixture($cache, 'https://spclient.wg.spotify.com/metadata/4/artist/' . sprintf('%032x', 3),
        json_encode(array('gid' => sprintf('%032x', 3), 'portrait_group' => array('image' => array(array('file_id' => $ids[1]))))));
    rememberArtworkObject($w, (object) array('uri' => $uri('track', 5), 'images' => array((object) array('url' => $url . $ids[2]))));

    // Conflicting metadata for the same entity must not choose a cover arbitrarily.
    cacheFixture($cache, $url . $ids[3], $image);
    cacheFixture($cache, $url . $ids[4], $image);
    metadataFixture($cache, 6, 60, $ids[3], '?market=first');
    metadataFixture($cache, 6, 60, $ids[4], '?market=second');
    metadataFixture($cache, 7, 70, $ids[0], '', sprintf('%032x', 8)); // wrong GID
    $broken = metadataFixture($cache, 9, 90, $ids[0]);
    file_put_contents($broken, substr(file_get_contents($broken), 0, 50)); // truncated
    cacheFixture($cache, $url . $ids[5], $image, true); // invalid body CRC
    metadataFixture($cache, 10, 100, $ids[5]);
    cacheFixture($cache, 'https://untrusted.invalid/image/' . $ids[6], $image);
    metadataFixture($cache, 11, 110, $ids[6]);
    cacheFixture($cache, $url . $ids[7], $image, false, 99); // unknown cache version
    metadataFixture($cache, 12, 120, $ids[7]);
    cacheFixture($cache, $url . str_replace('00004851', '0000b273', $ids[8]), $image);
    metadataFixture($cache, 13, 130, $ids[8]); // similar image ID is not an exact match
    cacheFixture($cache, $url . $ids[9], 'not an image');
    metadataFixture($cache, 14, 140, $ids[9]);
    $errorImage = 'ab67616d00004851' . str_repeat('a', 24);
    cacheFixture($cache, $url . $errorImage, $image, false, 5, 404);
    metadataFixture($cache, 15, 150, $errorImage);
    cacheFixture($cache, 'https://spclient.wg.spotify.com/metadata/4/track/' . sprintf('%032x', 16),
        json_encode(array('gid' => sprintf('%032x', 16), 'album' => array('gid' => sprintf('%032x', 160),
        'cover_group' => array('image' => array(array('file_id' => $ids[0])))))), false, 5, 404);

    $zstandard = base64_decode('KLUv/SCutQIAUgQQF4BZB1u4Is5nWFF9DNohQxEF4amDGR0B3V301QyBWl1hEzrLteOT4C+GXkMkjOzElUDj5ct/Mjn0cqPA+aqxzwcAuQ0AIcC4PUi9VVBVVdIbyAE=');
    if (function_exists('zstd_uncompress') || spotifyArtworkZstdCommand() !== null) {
        $decoded = spotifyLocalCacheJson($zstandard);
        checkLocalArtwork($decoded !== null && $decoded['gid'] === sprintf('%032x', 21), 'Decode Zstandard metadata when a decoder is available');
    } else {
        checkLocalArtwork(spotifyLocalCacheJson($zstandard) === null, 'Skip optional Zstandard metadata without a decoder');
    }
    checkLocalArtwork(spotifyLocalCacheJson(substr($zstandard, 0, 8)) === null, 'Reject a truncated compressed frame');
    checkLocalArtwork($uri('track', 1) === 'spotify:track:0000000000000000000001', 'Convert 128-bit Spotify IDs without floating point');
    checkLocalArtwork($uri('track', 62) === 'spotify:track:0000000000000000000010', 'Use Spotify base62 alphabet');
    checkLocalArtwork(importSpotifyLocalArtwork($w, array($cache)) === 5, 'Match exact image URLs, entity IDs, and album relationships');
    checkLocalArtwork(is_file($w->cache() . '/spotify-artwork-metadata.json'), 'Cache decoded metadata for subsequent processes');
    checkLocalArtwork(remainingArtworkCount($w) === 0, 'Complete only reliably matched rows');
    $result = processArtworkQueue($w, new stdClass(), true, array($cache));
    checkLocalArtwork($result['completed'] === 5, 'A fully local queue never needs a Spotify API client');
    foreach (array(6, 7, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19) as $number) {
        $db->prepare('INSERT INTO tracks VALUES (?,0)')->execute(array($uri('track', $number)));
    }
    $library->prepare('INSERT INTO tracks VALUES (?,?)')->execute(array($uri('track', 19), $uri('album', 2)));
    $library->prepare('INSERT INTO tracks VALUES (?,?)')->execute(array($uri('track', 19), $uri('album', 60)));
    $db->prepare('INSERT INTO artists VALUES (?,?,0)')->execute(array($uri('artist', 17), 'Fixture Artist'));
    rememberArtworkObject($w, (object) array('uri' => $uri('track', 17), 'images' => array((object) array('url' => 'https://untrusted.invalid/image/' . $ids[0]))));
    checkLocalArtwork(importSpotifyLocalArtwork($w, array($cache)) === 0, 'Skip ambiguous, corrupt, unsupported and untrusted cache entries');
    checkLocalArtwork(remainingArtworkCount($w) === 13, 'Leave unmatched IDs, HTTP errors and conflicting relationships pending');
    rememberArtworkObject($w, (object) array('uri' => $uri('track', 6), 'images' => array((object) array('url' => $url . $ids[3]))));
    checkLocalArtwork(importSpotifyLocalArtwork($w, array($cache)) === 1, 'An exact known image URL resolves otherwise ambiguous metadata');
    checkLocalArtwork(importSpotifyLocalArtwork($w, array($temporary . '/missing')) === 0, 'A missing local cache is optional');
    foreach (array(1, 4, 5) as $number) {
        $target = artworkTarget($w, 'track', array('track_uri' => $uri('track', $number)));
        checkLocalArtwork(file_get_contents($target) === $image, 'Extract only the verified image body');
    }
    echo "PASS: local cache lookup, exact IDs/URLs, album reuse, ambiguous metadata, corruption, and no-network completion\n";
} finally {
    $db = null;
    $library = null;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->isDir()) { rmdir($file->getPathname()); }
        else { unlink($file->getPathname()); }
    }
    rmdir($temporary);
}
