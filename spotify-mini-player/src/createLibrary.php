<?php
require_once './src/workflows.php';
require_once './src/functions.php';
require_once './vendor/autoload.php';

/**
 * createLibrary function.
 *
 * @param mixed $w
 */
function createLibrary($w) {
    touch($w->data() . '/update_library_in_progress');
    $w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time() . '▹' . 'starting', 'update_library_in_progress');
    $in_progress_data = $w->read('update_library_in_progress');

    // Read settings from JSON
    $settings = getSettings($w);
    $country_code = $settings->country_code;
    $userid = $settings->userid;
    $use_artworks = $settings->use_artworks;

    $words = explode('▹', $in_progress_data);

    $iso = new Matriphe\ISO639\ISO639;

    // move legacy artwork files in hash directories if needed
    if (file_exists($w->data() . '/artwork')) {
        $folder = $w->data() . '/artwork';
        if ($handle = opendir($folder)) {
            while (false !== ($file = readdir($handle))) {
                if (stristr($file, '.png')) {
                    exec("mkdir '" . $w->data() . '/artwork/' . hash('md5', $file) . "'");
                    rename($folder . '/' . $file, $folder . '/' . hash('md5', $file) . '/' . $file);
                }
            }
            closedir($handle);
        }
    }

    putenv('LANG=fr_FR.UTF-8');
    ini_set('memory_limit', '512M');
    if (file_exists($w->data() . '/library.db')) {
        rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    }
    deleteTheFile($w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';
    touch($dbfile);

    try {
        $db = new PDO("sqlite:$dbfile", '', '', array(
            PDO::ATTR_PERSISTENT => true,
        ));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->query('PRAGMA synchronous = OFF');
        $db->query('PRAGMA journal_mode = OFF');
        $db->query('PRAGMA temp_store = MEMORY');
        $db->query('PRAGMA count_changes = OFF');
        $db->query('PRAGMA PAGE_SIZE = 4096');
        $db->query('PRAGMA default_cache_size=700000');
        $db->query('PRAGMA cache_size=700000');
        $db->query('PRAGMA compile_options');
        // Problems with search on russian language #210
        // thanks to https://blog.amartynov.ru/php-sqlite-case-insensitive-like-utf8/
        $db->sqliteCreateFunction('like', "lexa_ci_utf8_like", 2);
    }
    catch(PDOException $e) {
        logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $db = null;

        return false;
    }

    if ($use_artworks) {
        // db for fetch artworks
        // kill previous process if running
        $pid = exec("ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'");
        if ($pid != '') {
            $ret = exec("kill -9 \"$pid\"");
        }
        $dbfile = $w->data() . '/fetch_artworks.db';
        if (file_exists($dbfile)) {
            deleteTheFile($dbfile);
            touch($dbfile);
        }
        if (file_exists($w->data() . '/download_artworks_in_progress')) {
            deleteTheFile($w->data() . '/download_artworks_in_progress');
        }
        try {
            $dbartworks = new PDO("sqlite:$dbfile", '', '', array(
                PDO::ATTR_PERSISTENT => true,
            ));
            $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        // DB artowrks
        try {
            $dbartworks->exec('create table artists (artist_uri text PRIMARY KEY NOT NULL, artist_name text, already_fetched boolean)');
            $dbartworks->exec('create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
            $dbartworks->exec('create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
            $dbartworks->exec('create table shows (show_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
            $dbartworks->exec('create table episodes (episode_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
    }

    // get the total number of tracks
    $nb_tracktotal = 0;
    $nb_skipped = 0;
    $savedListPlaylist = array();
    try {
        $api = getSpotifyWebAPI($w);
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleSpotifyWebAPIException($w, $e);

        return false;
    }

    // Check missing scope for podcasts
    $episode = $api->getEpisode('4aFURijFNhCP3n1pfQtQaM');

    if (! isset($episode->resume_point)) {
        logMsg("ERROR: the worfkflow was missing scope user-read-playback-position");
        updateSetting($w, 'oauth_access_token', '');
        updateSetting($w, 'oauth_refresh_token', '');
        displayNotificationWithArtwork($w, 'Relaunch the workflow to re-authenticate', './images/settings.png', 'Info');
        handleSpotifyPermissionException($w, 'Relaunch the workflow to re-authenticate');
        return false;
    }

    $offsetGetUserPlaylists = 0;
    $limitGetUserPlaylists = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userPlaylists = $api->getUserPlaylists(urlencode($userid) , array(
                    'limit' => $limitGetUserPlaylists,
                    'offset' => $offsetGetUserPlaylists,
                ));
                $retry = false;
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getUserPlaylists): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');
                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()
                        ->getLastResponse();
                    if (isset($lastResponse['headers']['Retry-After'])) {
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                    }
                    else {
                        $retryAfter = 1;
                    }
                    sleep($retryAfter);
                }
                else if ($e->getCode() == 404) {
                    // skip
                    break;
                }
                else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        foreach ($userPlaylists->items as $playlist) {
            $tracks = $playlist->tracks;
            $nb_tracktotal += $tracks->total;
            if ($playlist->name != '') {
                $savedListPlaylist[] = $playlist;
            }
        }
        $offsetGetUserPlaylists += $limitGetUserPlaylists;
    }
    while ($offsetGetUserPlaylists < $userPlaylists->total);

    $savedMySavedTracks = array();
    $offsetGetMySavedTracks = 0;
    $limitGetMySavedTracks = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userMySavedTracks = $api->getMySavedTracks(array(
                    'limit' => $limitGetMySavedTracks,
                    'offset' => $offsetGetMySavedTracks,
                    'market' => $country_code,
                ));
                $retry = false;
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getMySavedTracks): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()
                        ->getLastResponse();
                    if (isset($lastResponse['headers']['Retry-After'])) {
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                    }
                    else {
                        $retryAfter = 1;
                    }
                    sleep($retryAfter);
                }
                else if ($e->getCode() == 404) {
                    // skip
                    break;
                }
                else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        foreach ($userMySavedTracks->items as $track) {
            $savedMySavedTracks[] = $track->track;
            $nb_tracktotal += 1;
        }

        $offsetGetMySavedTracks += $limitGetMySavedTracks;
    }
    while ($offsetGetMySavedTracks < $userMySavedTracks->total);

    $savedMySavedShows = array();
    $offsetGetMySavedShows = 0;
    $limitGetMySavedShows = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userMySavedShows = $api->getMySavedShows(array(
                    'limit' => $limitGetMySavedShows,
                    'offset' => $offsetGetMySavedShows,
                    'market' => $country_code,
                ));
                $retry = false;
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getMySavedShows): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()
                        ->getLastResponse();
                    if (isset($lastResponse['headers']['Retry-After'])) {
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                    }
                    else {
                        $retryAfter = 1;
                    }
                    sleep($retryAfter);
                }
                else if ($e->getCode() == 404) {
                    // skip
                    break;
                }
                else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        foreach ($userMySavedShows->items as $show) {
            if(isset($show->show->uri) && $show->show->uri != '') {
                if (!checkIfShowDuplicate($savedMySavedShows, $show)) {
                    $savedMySavedShows[] = $show;
                }
            }
            $nb_tracktotal += 1;
        }

        $offsetGetMySavedShows += $limitGetMySavedShows;
    }
    while ($offsetGetMySavedShows < $userMySavedShows->total);

    $savedMySavedEpisodes = array();

    // Handle Episodes
    foreach ($savedMySavedShows as $item) {

        $show = $item->show;

        $offsetGetMySavedEpisodes = 0;
        $limitGetMySavedEpisodes = 50;
        do {
            $retry = true;
            $nb_retry = 0;
            while ($retry) {
                try {
                    // refresh api
                    $api = getSpotifyWebAPI($w, $api);
                    $userMySavedEpisodes = $api->getShowEpisodes($show->uri, array(
                        'limit' => $limitGetMySavedEpisodes,
                        'offset' => $offsetGetMySavedEpisodes,
                        'market' => $country_code,
                    ));
                    $retry = false;
                }
                catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                    logMsg('Error(getShowEpisodes): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                    if ($e->getCode() == 429) { // 429 is Too Many Requests
                        $lastResponse = $api->getRequest()
                            ->getLastResponse();
                        if (isset($lastResponse['headers']['Retry-After'])) {
                            $retryAfter = $lastResponse['headers']['Retry-After'];
                        }
                        else {
                            $retryAfter = 1;
                        }
                        sleep($retryAfter);
                    }
                    else if ($e->getCode() == 404) {
                        // skip
                        break;
                    }
                    else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                        // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                        // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                        // retry any SSL error
                        ++$nb_retry;
                    }
                    else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                        // retry
                        if ($nb_retry > 2) {
                            handleSpotifyWebAPIException($w, $e);
                            $retry = false;

                            return false;
                        }
                        ++$nb_retry;
                        sleep(5);
                    }
                    else {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                }
            }

            foreach ($userMySavedEpisodes->items as $show_episode) {
                $episode = getEpisode($w, $show_episode->uri);
                if (isset($episode->uri) && $episode->uri != '') $savedMySavedEpisodes[] = $episode;
                $nb_tracktotal += 1;
            }

            $offsetGetMySavedEpisodes += $limitGetMySavedEpisodes;
        }
        while ($offsetGetMySavedEpisodes < $userMySavedEpisodes->total);
    }

    // Handle followed artists
    $savedMyFollowedArtists = array();
    $cursorAfter = '';
    $limitGetUserFollowedArtists = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                if ($cursorAfter != '') {
                    $userFollowedArtists = $api->getUserFollowedArtists(array(
                        'type' => 'artist',
                        'limit' => $limitGetUserFollowedArtists,
                        'after' => $cursorAfter,
                    ));
                }
                else {
                    $userFollowedArtists = $api->getUserFollowedArtists(array(
                        'type' => 'artist',
                        'limit' => $limitGetUserFollowedArtists,
                    ));
                }

                $retry = false;
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getUserFollowedArtists): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()
                        ->getLastResponse();
                    if (isset($lastResponse['headers']['Retry-After'])) {
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                    }
                    else {
                        $retryAfter = 1;
                    }
                    sleep($retryAfter);
                }
                else if ($e->getCode() == 404) {
                    // skip
                    break;
                }
                else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        foreach ($userFollowedArtists
            ->artists->items as $artist) {
            $savedMyFollowedArtists[] = $artist;
        }
        if (isset($userFollowedArtists->cursors) && isset($userFollowedArtists
            ->cursors
            ->after) && $userFollowedArtists
            ->cursors->after != '') {
            $cursorAfter = $userFollowedArtists
                ->cursors->after;
        }
        else {
            $cursorAfter = '';
        }

    }
    while ($cursorAfter != '');

    $savedMySavedAlbums = array();
    $offsetGetMySavedAlbums = 0;
    $limitGetMySavedAlbums = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userMySavedAlbums = $api->getMySavedAlbums(array(
                    'limit' => $limitGetMySavedAlbums,
                    'offset' => $offsetGetMySavedAlbums,
                    'market' => $country_code,
                ));
                $retry = false;
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getMySavedAlbums): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()
                        ->getLastResponse();
                    if (isset($lastResponse['headers']['Retry-After'])) {
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                    }
                    else {
                        $retryAfter = 1;
                    }
                    sleep($retryAfter);
                }
                else if ($e->getCode() == 404) {
                    // skip
                    break;
                }
                else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        $allMySavedAlbumsTracks = array();
        foreach ($userMySavedAlbums->items as $item) {
            $album = $item->album;
            $tracks = $album->tracks;
            $nb_tracktotal += $tracks->total;
            if ($album->name != '') {
                $savedMySavedAlbums[] = $album;

                $offsetGetMySavedTracks = 0;
                $limitGetMySavedTracks = 50;
                do {
                    $retry = true;
                    $nb_retry = 0;
                    while ($retry) {
                        try {
                            // refresh api
                            $api = getSpotifyWebAPI($w, $api);
                            $tmp = explode(':', $album->uri);
                            $albumTracks = $api->getAlbumTracks($tmp[2], array(
                                'limit' => $limitGetMySavedTracks,
                                'offset' => $offsetGetMySavedTracks,
                                'market' => $country_code,
                            ));

                            foreach ($albumTracks->items as $track) {
                                // add album details as it is a simplified track
                                $myalbum = new stdClass();
                                $myalbum->uri = $album->uri;
                                $myalbum->name = $album->name;
                                $myalbum->album_type = $album->album_type;
                                $myalbum->yourmusic_album = 1;
                                $track->album = $myalbum;
                                $allMySavedAlbumsTracks[] = $track;
                                $nb_tracktotal += 1;
                            }
                            $retry = false;
                        }
                        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                            logMsg('Error(getAlbumTracks): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                            if ($e->getCode() == 429) { // 429 is Too Many Requests
                                $lastResponse = $api->getRequest()
                                    ->getLastResponse();
                                if (isset($lastResponse['headers']['Retry-After'])) {
                                    $retryAfter = $lastResponse['headers']['Retry-After'];
                                }
                                else {
                                    $retryAfter = 1;
                                }
                                sleep($retryAfter);
                            }
                            else if ($e->getCode() == 404) {
                                // skip
                                break;
                            }
                            else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                                // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                                // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                                // retry any SSL error
                                ++$nb_retry;
                            }
                            else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                                // retry
                                if ($nb_retry > 2) {
                                    handleSpotifyWebAPIException($w, $e);
                                    $retry = false;

                                    return false;
                                }
                                ++$nb_retry;
                                sleep(5);
                            }
                            else {
                                handleSpotifyWebAPIException($w, $e);
                                $retry = false;

                                return false;
                            }
                        }
                    }
                    $offsetGetMySavedTracks += $limitGetMySavedTracks;
                }
                while ($offsetGetMySavedTracks < $albumTracks->total);
            }
        }

        $offsetGetMySavedAlbums += $limitGetMySavedAlbums;
    } while ($offsetGetMySavedAlbums < $userMySavedAlbums->total);

    // Handle playlists
    $w->write('Create Library▹0▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . 'starting', 'update_library_in_progress');

    $nb_track = 0;

    try {
        $db->exec('create table tracks (yourmusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration text, nb_times_played int, local_track boolean, yourmusic_album boolean)');
        $db->exec('CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)');
        $db->exec('CREATE INDEX IndexArtistName ON tracks (artist_name)');
        $db->exec('CREATE INDEX IndexAlbumName ON tracks (album_name)');
        $db->exec('create table counters (all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int, episodes int)');
        $db->exec('create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean, nb_playable_tracks int, duration_playlist text, nb_times_played int, collaborative boolean, public boolean)');

        $db->exec('create table followed_artists (uri text PRIMARY KEY NOT NULL, name text, artist_artwork_path text)');

        $db->exec('create table shows (uri text PRIMARY KEY NOT NULL, name text, description text, media_type text, show_artwork_path text, explicit boolean, added_at text, languages text, nb_times_played int, is_externally_hosted boolean, nb_episodes int)');

        $db->exec('create table episodes (uri text PRIMARY KEY NOT NULL, name text, show_uri text, show_name text, description text, episode_artwork_path text, is_playable boolean, languages text, nb_times_played int, is_externally_hosted boolean, duration_ms int, explicit boolean, release_date text, release_date_precision text, audio_preview_url text, fully_played boolean, resume_position_ms int)');

        $insertPlaylist = 'insert into playlists values (:uri,:name,:nb_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser,:nb_playable_tracks,:duration_playlist,:nb_times_played,:collaborative,:public)';
        $stmtPlaylist = $db->prepare($insertPlaylist);

        $insertFollowedArtists = 'insert into followed_artists values (:uri,:name,:artist_artwork_path)';
        $stmtFollowedArtists = $db->prepare($insertFollowedArtists);

        $insertShow = 'insert into shows values (:uri,:name,:description,:media_type,:show_artwork_path,:explicit,:added_at,:languages,:nb_times_played,:is_externally_hosted, :nb_episodes)';
        $stmtInsertShow = $db->prepare($insertShow);

        $insertEpisode = 'insert or ignore into episodes values (:uri,:name,:show_uri,:show_name,:description,:episode_artwork_path,:is_playable,:languages,:nb_times_played,:is_externally_hosted,:duration_ms,:explicit,:release_date,:release_date_precision,:audio_preview_url,:fully_played,:resume_position_ms)';
        $stmtInsertEpisode = $db->prepare($insertEpisode);

        $insertTrack = 'insert into tracks values (:yourmusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration,:nb_times_played,:local_track,:yourmusic_album)';
        $stmtTrack = $db->prepare($insertTrack);
    }
    catch(PDOException $e) {
        logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return false;
    }

    if ($use_artworks) {
        try {
            // artworks
            $insertArtistArtwork = 'insert or ignore into artists values (:artist_uri, :artist_name,:already_fetched)';
            $stmtArtistArtwork = $dbartworks->prepare($insertArtistArtwork);

            $insertTrackArtwork = 'insert or ignore into tracks values (:track_uri,:already_fetched)';
            $stmtTrackArtwork = $dbartworks->prepare($insertTrackArtwork);

            $insertAlbumArtwork = 'insert or ignore into albums values (:album_uri,:already_fetched)';
            $stmtAlbumArtwork = $dbartworks->prepare($insertAlbumArtwork);

            $insertShowArtwork = 'insert or ignore into shows values (:show_uri,:already_fetched)';
            $stmtShowArtwork = $dbartworks->prepare($insertShowArtwork);

            $insertEpisodeArtwork = 'insert or ignore into episodes values (:episode_uri,:already_fetched)';
            $stmtEpisodeArtwork = $dbartworks->prepare($insertEpisodeArtwork);
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
        $artworksToDownload = false;
    }

    foreach ($savedListPlaylist as $playlist) {
        $duration_playlist = 0;
        $nb_track_playlist = 0;
        $tracks = $playlist->tracks;
        $owner = $playlist->owner;

        $playlist_artwork_path = getPlaylistArtwork($w, $playlist->uri, true, true, $use_artworks);

        if ('-' . $owner->id . '-' == '-' . $userid . '-') {
            $ownedbyuser = 1;
        }
        else {
            $ownedbyuser = 0;
        }

        try {
            $api = getSpotifyWebAPI($w);
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            logMsg('Error(getPlaylistTracks): playlist id ' . $playlist->id . ' (exception ' . jTraceEx($e) . ')');
            handleSpotifyWebAPIException($w, $e);

            return false;
        }
        $offsetGetUserPlaylistTracks = 0;
        $limitGetUserPlaylistTracks = 100;
        do {
            $retry = true;
            $nb_retry = 0;
            while ($retry) {
                try {
                    // refresh api
                    $api = getSpotifyWebAPI($w, $api);
                    $userPlaylistTracks = $api->getPlaylistTracks($playlist->id, array(
                        'fields' => array(
                            'total',
                            'items(added_at)',
                            'items(is_local)',
                            'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
                            'items.track.album(album_type,images,uri,name)',
                            'items.track.artists(name,uri)',
                        ) ,
                        'limit' => $limitGetUserPlaylistTracks,
                        'offset' => $offsetGetUserPlaylistTracks,
                        'market' => $country_code,
                    ));
                    $retry = false;
                }
                catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                    logMsg('Error(getUserPlaylists): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

                    if ($e->getCode() == 429) { // 429 is Too Many Requests
                        $lastResponse = $api->getRequest()
                            ->getLastResponse();
                        if (isset($lastResponse['headers']['Retry-After'])) {
                            $retryAfter = $lastResponse['headers']['Retry-After'];
                        }
                        else {
                            $retryAfter = 1;
                        }
                        sleep($retryAfter);
                    }
                    else if ($e->getCode() == 404) {
                        // skip
                        break;
                    }
                    else if (strpos(strtolower($e->getMessage()) , 'ssl') !== false) {
                        // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                        // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                        // retry any SSL error
                        ++$nb_retry;
                    }
                    else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 404) {
                        // retry
                        if ($nb_retry > 2) {
                            handleSpotifyWebAPIException($w, $e);
                            $retry = false;

                            return false;
                        }
                        ++$nb_retry;
                        sleep(5);
                    }
                    else {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                }
            }

            foreach ($userPlaylistTracks->items as $item) {
                if (!isset($item->track)) {
                    continue;
                }
                $track = $item->track;
                $artists = $track->artists;
                $artist = $artists[0];
                $album = $track->album;

                $playable = 0;
                $local_track = 0;
                if (isset($track->is_playable) && $track->is_playable) {
                    $playable = 1;
                    if (isset($track->linked_from) && isset($track
                        ->linked_from
                        ->uri)) {
                        $track->uri = $track
                            ->linked_from->uri;
                    }
                }
                if (isset($item->is_local) && $item->is_local) {
                    $playable = 1;
                    $local_track = 1;
                }

                try {

                    // Download artworks in Fetch later mode
                    $thetrackuri = 'spotify:track:faketrackuri';
                    if ($local_track == 0 && isset($track->uri)) {
                        $thetrackuri = $track->uri;
                    }
                    if ($use_artworks) {
                        list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true, false, $use_artworks);
                        if ($already_present == false) {
                            $artworksToDownload = true;
                            $stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
                            $stmtTrackArtwork->bindValue(':already_fetched', 0);
                            $stmtTrackArtwork->execute();
                        }
                    }
                    else {
                        $track_artwork_path = getTrackOrAlbumArtwork($w, $thetrackuri, false, false, false, $use_artworks);
                    }

                    $theartistname = 'fakeartist';
                    if (isset($artist->name)) {
                        $theartistname = $artist->name;
                    }
                    $theartisturi = 'spotify:artist:fakeartisturi';
                    if (isset($artist->uri)) {
                        $theartisturi = $artist->uri;
                    }
                    if ($use_artworks) {
                        list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi, $theartistname, true, true, false, $use_artworks);
                        if ($already_present == false) {
                            $artworksToDownload = true;
                            $stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
                            $stmtArtistArtwork->bindValue(':artist_name', $theartistname);
                            $stmtArtistArtwork->bindValue(':already_fetched', 0);
                            $stmtArtistArtwork->execute();
                        }
                    }
                    else {
                        $artist_artwork_path = getArtistArtwork($w, $theartisturi, $theartistname, false, false, false, $use_artworks);
                    }

                    $thealbumuri = 'spotify:album:fakealbumuri';
                    if (isset($album->uri)) {
                        $thealbumuri = $album->uri;
                    }
                    if ($use_artworks) {
                        list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true, false, $use_artworks);
                        if ($already_present == false) {
                            $artworksToDownload = true;
                            $stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
                            $stmtAlbumArtwork->bindValue(':already_fetched', 0);
                            $stmtAlbumArtwork->execute();
                        }
                    }
                    else {
                        $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, false, false, false, $use_artworks);
                    }
                }
                catch(PDOException $e) {
                    logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($dbartworks, $w);
                    $dbartworks = null;
                    $db = null;

                    return false;
                }

                $duration_playlist += $track->duration_ms;

                try {
                    $stmtTrack->bindValue(':yourmusic', 0);
                    $stmtTrack->bindValue(':popularity', $track->popularity);
                    $stmtTrack->bindValue(':uri', $track->uri);
                    $stmtTrack->bindValue(':album_uri', $album->uri);
                    $stmtTrack->bindValue(':artist_uri', $artist->uri);
                    $stmtTrack->bindValue(':track_name', escapeQuery($track->name));
                    $stmtTrack->bindValue(':album_name', escapeQuery($album->name));
                    $stmtTrack->bindValue(':artist_name', escapeQuery($artist->name));
                    $stmtTrack->bindValue(':album_type', $album->album_type);
                    $stmtTrack->bindValue(':track_artwork_path', $track_artwork_path);
                    $stmtTrack->bindValue(':artist_artwork_path', $artist_artwork_path);
                    $stmtTrack->bindValue(':album_artwork_path', $album_artwork_path);
                    $stmtTrack->bindValue(':playlist_name', escapeQuery($playlist->name));
                    $stmtTrack->bindValue(':playlist_uri', $playlist->uri);
                    $stmtTrack->bindValue(':playable', $playable);
                    $stmtTrack->bindValue(':added_at', $item->added_at);
                    $stmtTrack->bindValue(':duration', beautifyTime($track->duration_ms / 1000));
                    $stmtTrack->bindValue(':nb_times_played', 0);
                    $stmtTrack->bindValue(':local_track', $local_track);
                    $stmtTrack->bindValue(':yourmusic_album', 0);
                    $stmtTrack->execute();
                }
                catch(PDOException $e) {
                    logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return false;
                }
                ++$nb_track;
                ++$nb_track_playlist;
                if ($nb_track % 10 === 0) {
                    $w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . escapeQuery($playlist->name) , 'update_library_in_progress');
                }
            }

            $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
        }
        while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

        try {
            $stmtPlaylist->bindValue(':uri', $playlist->uri);
            $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
            $stmtPlaylist->bindValue(':nb_tracks', $playlist
                ->tracks
                ->total);
            $stmtPlaylist->bindValue(':owner', $owner->id);
            $stmtPlaylist->bindValue(':username', $owner->id);
            $stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
            $stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
            $stmtPlaylist->bindValue(':nb_playable_tracks', $nb_track_playlist);
            $stmtPlaylist->bindValue(':duration_playlist', beautifyTime($duration_playlist / 1000, true));
            $stmtPlaylist->bindValue(':nb_times_played', 0);
            $stmtPlaylist->bindValue(':collaborative', $playlist->collaborative);
            $stmtPlaylist->bindValue(':public', $playlist->public);
            $stmtPlaylist->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
    }

    // Handle Your Music
    // merge allMySavedAlbumsTracks and savedMySavedTracks to handle all Your Music tracks
    $mergedMySavedTracks = array_merge($allMySavedAlbumsTracks, $savedMySavedTracks);

    foreach ($mergedMySavedTracks as $track) {
        $artists = $track->artists;
        $artist = $artists[0];
        $album = $track->album;

        $playable = 0;
        $local_track = 0;
        if (isset($track->is_playable) && $track->is_playable) {
            $playable = 1;
            if (isset($track->linked_from) && isset($track
                ->linked_from
                ->uri)) {
                $track->uri = $track
                    ->linked_from->uri;
            }
        }
        if (isset($item->is_local) && $item->is_local) {
            $playable = 1;
            $local_track = 1;
        }

        try {

            // Download artworks in Fetch later mode
            $thetrackuri = 'spotify:track:faketrackuri';
            if ($local_track == 0 && isset($track->uri)) {
                $thetrackuri = $track->uri;
            }
            if ($use_artworks) {
                list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
                    $stmtTrackArtwork->bindValue(':already_fetched', 0);
                    $stmtTrackArtwork->execute();
                }
            }
            else {
                $track_artwork_path = getTrackOrAlbumArtwork($w, $thetrackuri, false, false, false, $use_artworks);
            }

            $theartistname = 'fakeartist';
            if (isset($artist->name)) {
                $theartistname = $artist->name;
            }
            $theartisturi = 'spotify:artist:fakeartisturi';
            if (isset($artist->uri)) {
                $theartisturi = $artist->uri;
            }
            if ($use_artworks) {
                list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi, $theartistname, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
                    $stmtArtistArtwork->bindValue(':artist_name', $theartistname);
                    $stmtArtistArtwork->bindValue(':already_fetched', 0);
                    $stmtArtistArtwork->execute();
                }
            }
            else {
                $artist_artwork_path = getArtistArtwork($w, $theartisturi, $theartistname, false, false, false, $use_artworks);
            }

            $thealbumuri = 'spotify:album:fakealbumuri';
            if (isset($album->uri)) {
                $thealbumuri = $album->uri;
            }
            if ($use_artworks) {
                list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
                    $stmtAlbumArtwork->bindValue(':already_fetched', 0);
                    $stmtAlbumArtwork->execute();
                }
            }
            else {
                $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, false, false, false, $use_artworks);
            }
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        try {
            if (isset($album->yourmusic_album)) {
                $stmtTrack->bindValue(':yourmusic', 0);
            }
            else {
                $stmtTrack->bindValue(':yourmusic', 1);
            }
            if (isset($track->popularity)) {
                $stmtTrack->bindValue(':popularity', $track->popularity);
            }
            else {
                $stmtTrack->bindValue(':popularity', 0);
            }
            $stmtTrack->bindValue(':uri', $track->uri);
            $stmtTrack->bindValue(':album_uri', $album->uri);
            $stmtTrack->bindValue(':artist_uri', $artist->uri);
            $stmtTrack->bindValue(':track_name', escapeQuery($track->name));
            $stmtTrack->bindValue(':album_name', escapeQuery($album->name));
            $stmtTrack->bindValue(':artist_name', escapeQuery($artist->name));
            $stmtTrack->bindValue(':album_type', $album->album_type);
            $stmtTrack->bindValue(':track_artwork_path', $track_artwork_path);
            $stmtTrack->bindValue(':artist_artwork_path', $artist_artwork_path);
            $stmtTrack->bindValue(':album_artwork_path', $album_artwork_path);
            $stmtTrack->bindValue(':playlist_name', '');
            $stmtTrack->bindValue(':playlist_uri', '');
            $stmtTrack->bindValue(':playable', $playable);
            $stmtTrack->bindValue(':added_at', $item->added_at);
            $stmtTrack->bindValue(':duration', beautifyTime($track->duration_ms / 1000));
            $stmtTrack->bindValue(':nb_times_played', 0);
            $stmtTrack->bindValue(':local_track', $local_track);
            if (isset($album->yourmusic_album)) {
                $stmtTrack->bindValue(':yourmusic_album', 1);
            }
            else {
                $stmtTrack->bindValue(':yourmusic_album', 0);
            }
            $stmtTrack->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        ++$nb_track;
        if ($nb_track % 10 === 0) {
            $w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . 'Your Music', 'update_library_in_progress');
        }
    }

    // Handle Shows
    foreach ($savedMySavedShows as $item) {

        $show = $item->show;
        try {

            // Download artworks in Fetch later mode
            if ($use_artworks) {
                list($already_present, $show_artwork_path) = getShowArtwork($w, $show->uri, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtShowArtwork->bindValue(':show_uri', $show->uri);
                    $stmtShowArtwork->bindValue(':already_fetched', 0);
                    $stmtShowArtwork->execute();
                }
            }
            else {
                $show_artwork_path = getShowArtwork($w, $show->uri, false, false, false, $use_artworks);
            }
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        try {
            $stmtInsertShow->bindValue(':uri', $show->uri);
            $stmtInsertShow->bindValue(':name', escapeQuery($show->name));
            $stmtInsertShow->bindValue(':description', escapeQuery($show->description));
            $stmtInsertShow->bindValue(':media_type', escapeQuery($show->media_type));
            $stmtInsertShow->bindValue(':show_artwork_path', $show_artwork_path);
            $stmtInsertShow->bindValue(':explicit', $show->explicit);
            $array_languages = array();
            foreach ($show->languages as $language) {
                if (strpos($language, '-') !== false) {
                    $language = strstr($language, '-', true);
                }
                $array_languages[] = $iso->languageByCode1($language);
            }
            $stmtInsertShow->bindValue(':languages', implode(",", $array_languages));
            $stmtInsertShow->bindValue(':added_at', $item->added_at);
            $stmtInsertShow->bindValue(':nb_times_played', 0);
            $stmtInsertShow->bindValue(':added_at', $show->is_externally_hosted);
            $stmtInsertShow->bindValue(':nb_episodes', getNumberOfEpisodesForShow($w, $show->uri, $country_code));
            $stmtInsertShow->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
    }

    // Handle Show Episodes
    foreach ($savedMySavedEpisodes as $episode) {

        try {
            // Download artworks in Fetch later mode
            if ($use_artworks) {
                list($already_present, $episode_artwork_path) = getEpisodeArtwork($w, $episode->uri, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtEpisodeArtwork->bindValue(':episode_uri', $episode->uri);
                    $stmtEpisodeArtwork->bindValue(':already_fetched', 0);
                    $stmtEpisodeArtwork->execute();
                }
            }
            else {
                $episode_artwork_path = getEpisodeArtwork($w, $episode->uri, false, false, false, $use_artworks);
            }
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        try {
            $stmtInsertEpisode->bindValue(':uri', $episode->uri);
            $stmtInsertEpisode->bindValue(':name', escapeQuery($episode->name));
            $stmtInsertEpisode->bindValue(':show_uri', $episode
                ->show
                ->uri);
            $stmtInsertEpisode->bindValue(':show_name', escapeQuery($episode
                ->show
                ->name));
            $stmtInsertEpisode->bindValue(':description', escapeQuery($episode->description));
            $stmtInsertEpisode->bindValue(':episode_artwork_path', $episode_artwork_path);
            $stmtInsertEpisode->bindValue(':is_playable', $episode->is_playable);
            $array_languages = array();
            foreach ($episode->languages as $language) {
                if (strpos($language, '-') !== false) {
                    $language = strstr($language, '-', true);
                }
                $array_languages[] = $iso->languageByCode1($language);
            }
            $stmtInsertEpisode->bindValue(':languages', implode(",", $array_languages));
            $stmtInsertEpisode->bindValue(':nb_times_played', 0);
            $stmtInsertEpisode->bindValue(':is_externally_hosted', $episode->is_externally_hosted);
            $stmtInsertEpisode->bindValue(':duration_ms', $episode->duration_ms);
            $stmtInsertEpisode->bindValue(':explicit', $episode->explicit);
            $stmtInsertEpisode->bindValue(':release_date', $episode->release_date);
            $stmtInsertEpisode->bindValue(':release_date_precision', $episode->release_date_precision);
            $stmtInsertEpisode->bindValue(':audio_preview_url', $episode->audio_preview_url);
            if (isset($episode->resume_point)) {
                $resume_point = $episode->resume_point;
                if (isset($resume_point->fully_played)) {
                    $stmtInsertEpisode->bindValue(':fully_played', $resume_point->fully_played);
                }
                else {
                    $stmtInsertEpisode->bindValue(':fully_played', 0);
                }

                $stmtInsertEpisode->bindValue(':resume_position_ms', $resume_point->resume_position_ms);
            }
            else {
                $stmtInsertEpisode->bindValue(':fully_played', 0);
                $stmtInsertEpisode->bindValue(':resume_position_ms', 0);
            }
            $stmtInsertEpisode->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
    }

    // Handle Followed Artists
    foreach ($savedMyFollowedArtists as $artist) {

        try {

            // Download artworks in Fetch later mode
            if ($use_artworks) {
                list($already_present, $artist_artwork_path) = getArtistArtwork($w, $artist->uri, $artist->name, true, true, false, $use_artworks);
                if ($already_present == false) {
                    $artworksToDownload = true;
                    $stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
                    $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
                    $stmtArtistArtwork->bindValue(':already_fetched', 0);
                    $stmtArtistArtwork->execute();
                }
            }
            else {
                $artist_artwork_path = getArtistArtwork($w, $artist->uri, $artist->name, false, false, false, $use_artworks);
            }
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        try {
            $stmtFollowedArtists->bindValue(':uri', $artist->uri);
            $stmtFollowedArtists->bindValue(':name', escapeQuery($artist->name));
            $stmtFollowedArtists->bindValue(':artist_artwork_path', $artist_artwork_path);
            $stmtFollowedArtists->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
    }

    // update counters
    try {
        $getCount = 'select count(distinct uri) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_tracks = $stmt->fetch();

        $getCount = 'select count(distinct uri) from tracks where yourmusic=1 and yourmusic_album=0';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_tracks = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_artists = $stmt->fetch();

        $getCount = 'select count(distinct name) from followed_artists';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_artists = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_albums = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks where yourmusic_album=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_albums = $stmt->fetch();

        $getCount = 'select count(*) from playlists';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $playlists_count = $stmt->fetch();

        $getCount = 'select count(*) from shows';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $shows_count = $stmt->fetch();

        $getCount = 'select count(*) from episodes';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $episodes_count = $stmt->fetch();

        $insertCounter = 'insert into counters values (:all_tracks,:yourmusic_tracks,:all_artists,:yourmusic_artists,:all_albums,:yourmusic_albums,:playlists,:shows,:episodes)';
        $stmt = $db->prepare($insertCounter);

        $stmt->bindValue(':all_tracks', $all_tracks[0]);
        $stmt->bindValue(':yourmusic_tracks', $yourmusic_tracks[0]);
        $stmt->bindValue(':all_artists', $all_artists[0]);
        $stmt->bindValue(':yourmusic_artists', $yourmusic_artists[0]);
        $stmt->bindValue(':all_albums', $all_albums[0]);
        $stmt->bindValue(':yourmusic_albums', $yourmusic_albums[0]);
        $stmt->bindValue(':playlists', $playlists_count[0]);
        $stmt->bindValue(':shows', $shows_count[0]);
        $stmt->bindValue(':episodes', $episodes_count[0]);
        $stmt->execute();
    }
    catch(PDOException $e) {
        logMsg('Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return false;
    }

    $elapsed_time = time() - $words[3];
    if ($nb_skipped == 0) {
        displayNotificationWithArtwork($w, ' ' . $nb_track . ' tracks - took ' . beautifyTime($elapsed_time, true) , './images/recreate.png', 'Library (re-)created');
    }
    else {
        displayNotificationWithArtwork($w, ' ' . $nb_track . ' tracks / ' . $nb_skipped . ' skipped - took ' . beautifyTime($elapsed_time, true) , './images/recreate.png', 'Library (re-)created');
    }

    if (file_exists($w->data() . '/library_old.db')) {
        deleteTheFile($w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');

    // remove legacy spotify app if needed
    if (file_exists(exec('printf $HOME') . '/Spotify/spotify-app-miniplayer')) {
        exec('rm -rf ' . exec('printf $HOME') . '/Spotify/spotify-app-miniplayer');
    }
    // remove legacy settings.db if needed
    if (file_exists($w->data() . '/settings.db')) {
        deleteTheFile($w->data() . '/settings.db');
    }

    // Download artworks in background
    if ($use_artworks) {
        if ($artworksToDownload == true) {
            exec('php -f ./src/action.php -- "" "DOWNLOAD_ARTWORKS" "DOWNLOAD_ARTWORKS" >> "' . $w->cache() . '/action.log" 2>&1 & ');
        }
    }
    deleteTheFile($w->data() . '/update_library_in_progress');

    // in case of new user, force creation of links and current_user.json
    getCurrentUser($w);
}

