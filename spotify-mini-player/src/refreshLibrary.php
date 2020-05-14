<?php
require_once './src/workflows.php';
require_once './src/functions.php';
require './vendor/autoload.php';

/**
 * refreshLibrary function.
 *
 * @param mixed $w
 */
function refreshLibrary($w) {
    if (!file_exists($w->data() . '/library.db')) {
        displayNotificationWithArtwork($w, 'Refresh library called while library does not exist', './images/warning.png');

        return;
    }

    $iso = new Matriphe\ISO639\ISO639;

    touch($w->data() . '/update_library_in_progress');
    $w->write('InitRefreshLibrary▹' . 0 . '▹' . 0 . '▹' . time() . '▹' . 'starting', 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');

    // Read settings from JSON
    $settings = getSettings($w);

    $country_code = $settings->country_code;
    $userid = $settings->userid;
    $use_artworks = $settings->use_artworks;

    $words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    $nb_playlist = 0;
    $nb_playlist_total = 0;

    if ($use_artworks) {
        // db for fetch artworks
        $fetch_artworks_existed = true;
        $dbfile = $w->data() . '/fetch_artworks.db';
        if (!file_exists($dbfile)) {
            touch($dbfile);
            $fetch_artworks_existed = false;
        }
        // kill previous process if running
        $pid = exec("ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'");
        if ($pid != '') {
            $ret = exec("kill -9 \"$pid\"");
        }
        if (file_exists($w->data() . '/download_artworks_in_progress')) {
            deleteTheFile($w->data() . '/download_artworks_in_progress');
        }

        try {
            $dbartworks = new PDO("sqlite:$dbfile", '', '', array(PDO::ATTR_PERSISTENT => true,));
            $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }

        // DB artowrks
        if ($fetch_artworks_existed == false) {
            try {
                $dbartworks->exec('create table artists (artist_uri text PRIMARY KEY NOT NULL, artist_name text, already_fetched boolean)');
                $dbartworks->exec('create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
                $dbartworks->exec('create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
                $dbartworks->exec('create table shows (show_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
                $dbartworks->exec('create table episodes (episode_uri text PRIMARY KEY NOT NULL, already_fetched boolean)');
            }
            catch(PDOException $e) {
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                handleDbIssuePdoEcho($dbartworks, $w);
                $dbartworks = null;
                $db = null;

                return false;
            }
        }

        try {
            // artworks
            $insertArtistArtwork = 'insert or ignore into artists values (:artist_uri,:artist_name,:already_fetched)';
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
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
        $artworksToDownload = false;
    }

    rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    copy($w->data() . '/library_old.db', $w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';

    $nb_added_playlists = 0;
    $nb_removed_playlists = 0;
    $nb_updated_playlists = 0;
    $nb_added_shows = 0;
    $nb_removed_shows = 0;
    $nb_updated_shows = 0;
    $nb_added_albums = 0;
    $nb_removed_albums = 0;
    $nb_removed_followed_artists = 0;
    $nb_added_followed_artists = 0;

    try {
        $db = new PDO("sqlite:$dbfile", '', '', array(PDO::ATTR_PERSISTENT => true,));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec('drop table counters');
        $db->exec('create table counters (all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int)');

        $getPlaylists = 'select * from playlists where uri=:uri';
        $stmtGetPlaylists = $db->prepare($getPlaylists);

        $insertPlaylist = 'insert into playlists values (:uri,:name,:nb_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser,:nb_playable_tracks,:duration_playlist,:nb_times_played,:collaborative,:public)';
        $stmtPlaylist = $db->prepare($insertPlaylist);

        $insertTrack = 'insert into tracks values (:yourmusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration,:nb_times_played,:local_track,:yourmusic_album)';
        $stmtTrack = $db->prepare($insertTrack);

        $deleteFromTracks = 'delete from tracks where playlist_uri=:playlist_uri';
        $stmtDeleteFromTracks = $db->prepare($deleteFromTracks);

        $updatePlaylistsNbTracks = 'update playlists set nb_tracks=:nb_tracks,nb_playable_tracks=:nb_playable_tracks,duration_playlist=:duration_playlist,public=:public where uri=:uri';
        $stmtUpdatePlaylistsNbTracks = $db->prepare($updatePlaylistsNbTracks);

        $deleteFromTracksYourMusic = 'delete from tracks where yourmusic=:yourmusic and yourmusic_album=0';
        $stmtDeleteFromTracksYourMusic = $db->prepare($deleteFromTracksYourMusic);

        $getShows = 'select * from shows where uri=:uri';
        $stmtGetShows = $db->prepare($getShows);

        $getFollowedArtists = 'select * from followed_artists where uri=:uri';
        $stmtGetFollowedArtists = $db->prepare($getFollowedArtists);

        $insertFollowedArtists = 'insert into followed_artists values (:uri,:name,:artist_artwork_path)';
        $stmtFollowedArtists = $db->prepare($insertFollowedArtists);

        $insertShow = 'insert into shows values (:uri,:name,:description,:media_type,:show_artwork_path,:explicit,:added_at,:languages,:nb_times_played,:is_externally_hosted, :nb_episodes)';
        $stmtInsertShow = $db->prepare($insertShow);

        $insertEpisode = 'insert or ignore into episodes values (:uri,:name,:show_uri,:show_name,:description,:episode_artwork_path,:is_playable,:languages,:nb_times_played,:is_externally_hosted,:duration_ms,:explicit,:release_date,:release_date_precision,:audio_preview_url,:fully_played,:resume_position_ms)';
        $stmtInsertEpisode = $db->prepare($insertEpisode);

        $deleteFromEpisodes = 'delete from episodes where show_uri=:show_uri';
        $stmtDeleteFromEpisodes = $db->prepare($deleteFromEpisodes);

        $updateShowsNbEpisodes = 'update shows set nb_episodes=:nb_episodes where uri=:uri';
        $stmtUpdateShowsNbEpisodes = $db->prepare($updateShowsNbEpisodes);

        $getYourMusicAlbums = 'select * from tracks where yourmusic_album=1 and album_uri=:album_uri group by album_uri';
        $stmtYourMusicAlbums = $db->prepare($getYourMusicAlbums);
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    try {
        $api = getSpotifyWebAPI($w);
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                $userMySavedAlbums = $api->getMySavedAlbums(array('limit' => $limitGetMySavedAlbums, 'offset' => $offsetGetMySavedAlbums, 'market' => $country_code,));
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
                else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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

        foreach ($userMySavedAlbums->items as $item) {
            $album = $item->album;
            if ($album->name != '') {
                $savedMySavedAlbums[] = $album;
            }
        }

        $offsetGetMySavedAlbums += $limitGetMySavedAlbums;
    }
    while ($offsetGetMySavedAlbums < $userMySavedAlbums->total);

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
                    $userFollowedArtists = $api->getUserFollowedArtists(array('type' => 'artist', 'limit' => $limitGetUserFollowedArtists, 'after' => $cursorAfter,));
                }
                else {
                    $userFollowedArtists = $api->getUserFollowedArtists(array('type' => 'artist', 'limit' => $limitGetUserFollowedArtists,));
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
                else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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

    // Handle Shows
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
                $userMySavedShows = $api->getMySavedShows(array('limit' => $limitGetMySavedShows, 'offset' => $offsetGetMySavedShows, 'market' => $country_code,));
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
                else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
        }

        $offsetGetMySavedShows += $limitGetMySavedShows;
    }
    while ($offsetGetMySavedShows < $userMySavedShows->total);

    $nb_playlist_total += $userMySavedShows->total;

    $savedListPlaylist = array();
    $offsetGetUserPlaylists = 0;
    $limitGetUserPlaylists = 50;
    do {
        $retry = true;
        $nb_retry = 0;

        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userPlaylists = $api->getUserPlaylists(urlencode($userid), array('limit' => $limitGetUserPlaylists, 'offset' => $offsetGetUserPlaylists,));
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
                else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
            if ($playlist->name != '') {
                $savedListPlaylist[] = $playlist;
            }
        }
        $offsetGetUserPlaylists += $limitGetUserPlaylists;
    }
    while ($offsetGetUserPlaylists < $userPlaylists->total);

    $nb_playlist_total += $userPlaylists->total;

    // consider Your Music as a playlist for progress bar
    ++$nb_playlist_total;

    foreach ($savedListPlaylist as $playlist) {
        $tracks = $playlist->tracks;
        $owner = $playlist->owner;

        ++$nb_playlist;
        $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . escapeQuery($playlist->name), 'update_library_in_progress');

        try {
            // Loop on existing playlists in library
            $stmtGetPlaylists->bindValue(':uri', $playlist->uri);
            $stmtGetPlaylists->execute();

            $noresult = true;
            while ($playlists = $stmtGetPlaylists->fetch()) {
                $noresult = false;
                break;
            }
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return;
        }

        // Playlist does not exist, add it
        if ($noresult == true) {
            ++$nb_added_playlists;
            $playlist_artwork_path = getPlaylistArtwork($w, $playlist->uri, true, true, $use_artworks);

            if ('-' . $owner->id . '-' == '-' . $userid . '-') {
                $ownedbyuser = 1;
            }
            else {
                $ownedbyuser = 0;
            }

            $nb_track_playlist = 0;
            $duration_playlist = 0;
            $offsetGetUserPlaylistTracks = 0;
            $limitGetUserPlaylistTracks = 100;
            do {
                $retry = true;
                $nb_retry = 0;
                while ($retry) {
                    try {
                        // refresh api
                        $api = getSpotifyWebAPI($w, $api);
                        $userPlaylistTracks = $api->getPlaylistTracks($playlist->id, array('fields' => array('total', 'items(added_at)', 'items(is_local)', 'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)', 'items.track.album(album_type,images,uri,name)', 'items.track.artists(name,uri)',), 'limit' => $limitGetUserPlaylistTracks, 'offset' => $offsetGetUserPlaylistTracks, 'market' => $country_code,));
                        $retry = false;
                    }
                    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                        logMsg('Error(getPlaylistTracks): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

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
                        else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
                        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                        handleDbIssuePdoEcho($db, $w);
                        $dbartworks = null;
                        $db = null;

                        return;
                    }
                    ++$nb_track_playlist;
                }

                $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
            }
            while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

            try {
                $stmtPlaylist->bindValue(':uri', $playlist->uri);
                $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
                $stmtPlaylist->bindValue(':nb_tracks', $tracks->total);
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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                handleDbIssuePdoEcho($db, $w);
                $dbartworks = null;
                $db = null;

                return;
            }

            if (getenv('reduce_notifications') == 0) {
                displayNotificationWithArtwork($w, 'Added playlist ' . escapeQuery($playlist->name), $playlist_artwork_path, 'Refresh Library');
            }
        }
        else {

            // check if this is a self-updated playlist (spotify and 30 tracks)
            $selfUpdatedPlaylistUpdated = false;

            $owner = getPlaylistOwner($w, $playlists[0]);

            if ($owner == 'spotify' && $tracks->total == 30) {

                try {
                    $getOneTrack = 'select added_at from tracks where playlist_uri=:theplaylisturi order by added_at desc limit 1';
                    $stmtGetOneTrack = $db->prepare($getOneTrack);
                    $stmtGetOneTrack->bindValue(':theplaylisturi', $playlists[0]);
                    $stmtGetOneTrack->execute();
                    $theOneTrack = $stmtGetOneTrack->fetch();
                    date_default_timezone_set('UTC');
                    $today = date("c");
                    $last_updated = $theOneTrack[0];
                    $today_time = strtotime($today);
                    $last_updated_time = strtotime($last_updated);

                    if (($today_time - $last_updated_time) > 7 * 24 * 3600) {
                        $selfUpdatedPlaylistUpdated = true;
                    }
                }
                catch(PDOException $e) {
                    logMsg('Error(refreshLibrary - self-updated playlist): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }
            }

            // number of tracks has changed or playlist name has changed or the privacy has changed, or spotify playlist (Release Radar, Discover Weekly)
            // update the playlist
            if ($selfUpdatedPlaylistUpdated || $playlists[2] != $tracks->total || $playlists[1] != escapeQuery($playlist->name) || (($playlists[11] == '' && $playlist->public == true) || ($playlists[11] == true && $playlist->public == ''))) {
                ++$nb_updated_playlists;

                // force refresh of playlist artwork
                getPlaylistArtwork($w, $playlist->uri, true, true, $use_artworks);

                try {
                    if ($playlists[1] != escapeQuery($playlist->name)) {
                        $updatePlaylistsName = 'update playlists set name=:name where uri=:uri';
                        $stmtUpdatePlaylistsName = $db->prepare($updatePlaylistsName);

                        $stmtUpdatePlaylistsName->bindValue(':name', escapeQuery($playlist->name));
                        $stmtUpdatePlaylistsName->bindValue(':uri', $playlist->uri);
                        $stmtUpdatePlaylistsName->execute();
                    }

                    $stmtDeleteFromTracks->bindValue(':playlist_uri', $playlist->uri);
                    $stmtDeleteFromTracks->execute();
                }
                catch(PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }

                $duration_playlist = 0;
                $nb_track_playlist = 0;
                $offsetGetUserPlaylistTracks = 0;
                $limitGetUserPlaylistTracks = 100;
                $owner = $playlist->owner;
                do {
                    $retry = true;
                    $nb_retry = 0;
                    while ($retry) {
                        try {
                            // refresh api
                            $api = getSpotifyWebAPI($w, $api);
                            $userPlaylistTracks = $api->getPlaylistTracks($playlist->id, array('fields' => array('total', 'items(added_at)', 'items(is_local)', 'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)', 'items.track.album(album_type,images,uri,name)', 'items.track.artists(name,uri)',), 'limit' => $limitGetUserPlaylistTracks, 'offset' => $offsetGetUserPlaylistTracks, 'market' => $country_code,));
                            $retry = false;
                        }
                        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                            logMsg('Error(getPlaylistTracks): retry ' . $nb_retry . ' (exception ' . jTraceEx($e) . ')');

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
                            else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
                            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                            handleDbIssuePdoEcho($db, $w);
                            $dbartworks = null;
                            $db = null;

                            return;
                        }
                        ++$nb_track_playlist;
                    }

                    $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
                }
                while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

                try {
                    $stmtUpdatePlaylistsNbTracks->bindValue(':nb_tracks', $userPlaylistTracks->total);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':nb_playable_tracks', $nb_track_playlist);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':duration_playlist', beautifyTime($duration_playlist / 1000, true));
                    $stmtUpdatePlaylistsNbTracks->bindValue(':uri', $playlist->uri);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':public', $playlist->public);
                    $stmtUpdatePlaylistsNbTracks->execute();
                }
                catch(PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }
                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Updated playlist ' . escapeQuery($playlist->name), getPlaylistArtwork($w, $playlist->uri, true, false, $use_artworks), 'Refresh Library');
                }
            }
            else {
                continue;
            }
        }
    }

    try {
        // check for deleted playlists
        $getPlaylists = 'select * from playlists';
        $stmt = $db->prepare($getPlaylists);
        $stmt->execute();

        while ($playlist_in_db = $stmt->fetch()) {
            $found = false;
            foreach ($savedListPlaylist as $playlist) {
                if ($playlist->uri == $playlist_in_db[0]) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                ++$nb_removed_playlists;

                $deleteFromPlaylist = 'delete from playlists where uri=:uri';
                $stmtDelete = $db->prepare($deleteFromPlaylist);
                $stmtDelete->bindValue(':uri', $playlist_in_db[0]);
                $stmtDelete->execute();

                $deleteFromTracks = 'delete from tracks where playlist_uri=:uri';
                $stmtDelete = $db->prepare($deleteFromTracks);
                $stmtDelete->bindValue(':uri', $playlist_in_db[0]);
                $stmtDelete->execute();
                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Removed playlist ' . $playlist_in_db[1], getPlaylistArtwork($w, $playlist_in_db[0], false, false, $use_artworks), 'Refresh Library');
                }
            }
        }
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    $allMySavedAlbumsTracks = array();
    // check for update to Your Music - albums
    foreach ($savedMySavedAlbums as $album) {
        ++$nb_playlist;
        $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . escapeQuery($album->name), 'update_library_in_progress');
        try {
            // Loop on existing albums in library
            $stmtYourMusicAlbums->bindValue(':album_uri', $album->uri);
            $stmtYourMusicAlbums->execute();

            $noresult = true;
            while ($albums = $stmtYourMusicAlbums->fetch()) {
                $noresult = false;
                break;
            }
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return;
        }

        // Album does not exist, add it
        if ($noresult == true) {
            $thealbumuri = 'spotify:album:fakealbumuri';
            if (isset($album->uri)) {
                $thealbumuri = $album->uri;
            }
            ++$nb_added_albums;
            $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, true, false, false, $use_artworks);

            $offsetGetMySavedAlbumTracks = 0;
            $limitGetMySavedAlbumTracks = 50;
            do {
                $retry = true;
                $nb_retry = 0;
                while ($retry) {
                    try {
                        // refresh api
                        $api = getSpotifyWebAPI($w, $api);
                        $tmp = explode(':', $album->uri);
                        $albumTracks = $api->getAlbumTracks($tmp[2], array('limit' => $limitGetMySavedAlbumTracks, 'offset' => $offsetGetMySavedAlbumTracks, 'market' => $country_code,));

                        foreach ($albumTracks->items as $track) {
                            // add album details as it is a simplified track
                            $myalbum = new stdClass();
                            $myalbum->uri = $album->uri;
                            $myalbum->name = $album->name;
                            $myalbum->album_type = $album->album_type;
                            $myalbum->yourmusic_album = 1;
                            $track->album = $myalbum;
                            $allMySavedAlbumsTracks[] = $track;
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
                        else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
                $offsetGetMySavedAlbumTracks += $limitGetMySavedAlbumTracks;
            }
            while ($offsetGetMySavedAlbumTracks < $albumTracks->total);

            if (getenv('reduce_notifications') == 0) {
                displayNotificationWithArtwork($w, 'Added album ' . escapeQuery($album->name), $album_artwork_path, 'Refresh Library');
            }
        }
    }

    // check for update to Your Music - tracks
    $retry = true;
    $nb_retry = 0;
    while ($retry) {
        try {
            // refresh api
            $api = getSpotifyWebAPI($w, $api);

            // get only one, we just want to check total for now
            $userMySavedTracks = $api->getMySavedTracks(array('limit' => 1, 'offset' => 0,));
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
            else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                // retry any SSL error
                ++$nb_retry;
            }
            else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503) {
                // retry
                if ($nb_retry > 20) {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
                ++$nb_retry;
                sleep(15);
            }
            else {
                handleSpotifyWebAPIException($w, $e);
                $retry = false;

                return false;
            }
        }
    }

    try {
        // get current number of track in Your Music
        $getCount = 'select count(distinct uri) from tracks where yourmusic=1 and yourmusic_album=0';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_tracks = $stmt->fetch();
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $db = null;

        return;
    }

    $your_music_updated = false;
    if ($yourmusic_tracks[0] != $userMySavedTracks->total || count($allMySavedAlbumsTracks) > 0) {
        $your_music_updated = true;
        // Your Music has changed, update it
        ++$nb_playlist;
        $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . 'Your Music', 'update_library_in_progress');

        // delete tracks
        try {
            $stmtDeleteFromTracksYourMusic->bindValue(':yourmusic', 1);
            $stmtDeleteFromTracksYourMusic->execute();
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $db = null;

            return;
        }

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
                    $userMySavedTracks = $api->getMySavedTracks(array('limit' => $limitGetMySavedTracks, 'offset' => $offsetGetMySavedTracks, 'market' => $country_code,));
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
                    else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
            }

            $offsetGetMySavedTracks += $limitGetMySavedTracks;
        }
        while ($offsetGetMySavedTracks < $userMySavedTracks->total);

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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                handleDbIssuePdoEcho($db, $w);
                $dbartworks = null;
                $db = null;

                return false;
            }
        }
    }

    foreach ($savedMySavedShows as $item) {
        $show = $item->show;

        ++$nb_playlist;
        $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . escapeQuery($show->name), 'update_library_in_progress');

        try {
            // Loop on existing shows in library
            $stmtGetShows->bindValue(':uri', $show->uri);
            $stmtGetShows->execute();

            $noresult = true;
            while ($shows = $stmtGetShows->fetch()) {
                $noresult = false;
                break;
            }
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return;
        }

        $nb_episodes = getNumberOfEpisodesForShow($w, $show->uri, $country_code);

        // Show does not exist, add it
        if ($noresult == true) {
            ++$nb_added_shows;

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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                $stmtInsertShow->bindValue(':nb_episodes', $nb_episodes);
                $stmtInsertShow->execute();
            }
            catch(PDOException $e) {
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                handleDbIssuePdoEcho($db, $w);
                $dbartworks = null;
                $db = null;

                return false;
            }

            $savedMySavedEpisodes = array();
            $offsetGetMySavedEpisodes = 0;
            $limitGetMySavedEpisodes = 50;
            do {
                $retry = true;
                $nb_retry = 0;
                while ($retry) {
                    try {
                        // refresh api
                        $api = getSpotifyWebAPI($w, $api);
                        $userMySavedEpisodes = $api->getShowEpisodes($show->uri, array('limit' => $limitGetMySavedEpisodes, 'offset' => $offsetGetMySavedEpisodes, 'market' => $country_code,));
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
                        else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
                }

                $offsetGetMySavedEpisodes += $limitGetMySavedEpisodes;
            }
            while ($offsetGetMySavedEpisodes < $userMySavedEpisodes->total);

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
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return false;
                }
            }
            if (getenv('reduce_notifications') == 0) {
                displayNotificationWithArtwork($w, 'Added show ' . escapeQuery($show->name), $show_artwork_path, 'Refresh Library');
            }
        }
        else {

            // number of episodes has changed
            // update the show
            if ($shows[10] != $nb_episodes) {
                ++$nb_updated_shows;

                try {
                    $stmtDeleteFromEpisodes->bindValue(':show_uri', $show->uri);
                    $stmtDeleteFromEpisodes->execute();
                }
                catch(PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }

                // Handle Episodes
                $offsetGetMySavedEpisodes = 0;
                $limitGetMySavedEpisodes = 50;
                do {
                    $retry = true;
                    $nb_retry = 0;
                    while ($retry) {
                        try {
                            // refresh api
                            $api = getSpotifyWebAPI($w, $api);
                            $userMySavedEpisodes = $api->getShowEpisodes($show->uri, array('limit' => $limitGetMySavedEpisodes, 'offset' => $offsetGetMySavedEpisodes, 'market' => $country_code,));
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
                            else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
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
                    }

                    $offsetGetMySavedEpisodes += $limitGetMySavedEpisodes;
                }
                while ($offsetGetMySavedEpisodes < $userMySavedEpisodes->total);

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
                        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                        handleDbIssuePdoEcho($db, $w);
                        $dbartworks = null;
                        $db = null;

                        return false;
                    }
                }

                try {
                    $stmtUpdateShowsNbEpisodes->bindValue(':nb_episodes', $userMySavedEpisodes->total);
                    $stmtUpdateShowsNbEpisodes->bindValue(':uri', $show->uri);
                    $stmtUpdateShowsNbEpisodes->execute();
                }
                catch(PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }
                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Updated show ' . escapeQuery($show->name), getShowArtwork($w, $show->uri, true, false, $use_artworks), 'Refresh Library');
                }
            }
            else {
                continue;
            }
        }
    }

    $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . escapeQuery($show->name), 'update_library_in_progress');

    try {
        // check for deleted shows
        $getShows = 'select * from shows';
        $stmt = $db->prepare($getShows);
        $stmt->execute();

        while ($shows_in_db = $stmt->fetch()) {
            $found = false;
            foreach ($savedMySavedShows as $item) {
                $show = $item->show;
                if ($show->uri == $shows_in_db[0]) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                ++$nb_removed_shows;

                $deleteFromShow = 'delete from shows where uri=:uri';
                $stmtDelete = $db->prepare($deleteFromShow);
                $stmtDelete->bindValue(':uri', $shows_in_db[0]);
                $stmtDelete->execute();

                $deleteFromEpisodes = 'delete from episodes where show_uri=:uri';
                $stmtDelete = $db->prepare($deleteFromEpisodes);
                $stmtDelete->bindValue(':uri', $shows_in_db[0]);
                $stmtDelete->execute();
                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Removed show ' . $shows_in_db[1], getShowArtwork($w, $shows_in_db[0], false, false, $use_artworks), 'Refresh Library');
                }
            }
        }
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    try {
        // check for deleted albums
        $getAllSavedAlbums = 'select album_uri from tracks where yourmusic_album=1 group by album_uri';
        $stmt = $db->prepare($getAllSavedAlbums);
        $stmt->execute();

        while ($album_in_db = $stmt->fetch()) {
            $found = false;
            foreach ($savedMySavedAlbums as $album) {
                if ($album->uri == $album_in_db[0]) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                ++$nb_removed_albums;

                $deleteFromSavedAlbums = 'delete from tracks where yourmusic_album=1 and album_uri=:album_uri';
                $stmtDelete = $db->prepare($deleteFromSavedAlbums);
                $stmtDelete->bindValue(':album_uri', $album_in_db[0]);
                $stmtDelete->execute();

                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Removed album ' . $album_in_db[1], getTrackOrAlbumArtwork($w, $album_in_db[0], false, false, false, $use_artworks), 'Refresh Library');
                }
            }
        }
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    foreach ($savedMyFollowedArtists as $artist) {

        try {
            // Loop on existing artists in library
            $stmtGetFollowedArtists->bindValue(':uri', $artist->uri);
            $stmtGetFollowedArtists->execute();

            $noresult = true;
            while ($artists = $stmtGetFollowedArtists->fetch()) {
                $noresult = false;
                break;
            }
        }
        catch(PDOException $e) {
            logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return;
        }

        // Artist does not exist, add it
        if ($noresult == true) {
            ++$nb_added_followed_artists;

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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
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
                logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
                handleDbIssuePdoEcho($db, $w);
                $dbartworks = null;
                $db = null;

                return false;
            }

            if (getenv('reduce_notifications') == 0) {
                displayNotificationWithArtwork($w, 'Added followed artist ' . escapeQuery($artist->name), $artist_artwork_path, 'Refresh Library');
            }
        }
    }

    try {
        // check for unfollowed artists
        $getFollowedArtists = 'select * from followed_artists';
        $stmt = $db->prepare($getFollowedArtists);
        $stmt->execute();

        while ($followed_artist_in_db = $stmt->fetch()) {
            $found = false;
            foreach ($savedMyFollowedArtists as $artist) {
                if ($artist->uri == $followed_artist_in_db[0]) {
                    $found = true;
                    break;
                }
            }
            if ($found == false) {
                ++$nb_removed_followed_artists;

                $deleteFromFollowedArtists = 'delete from followed_artists where uri=:uri';
                $stmtDelete = $db->prepare($deleteFromFollowedArtists);
                $stmtDelete->bindValue(':uri', $followed_artist_in_db[0]);
                $stmtDelete->execute();

                if (getenv('reduce_notifications') == 0) {
                    displayNotificationWithArtwork($w, 'Unfollowed artist ' . $followed_artist_in_db[1], getArtistArtwork($w, $followed_artist_in_db[0], $followed_artist_in_db[1], false, false, false, $use_artworks), 'Refresh Library');
                }
            }
        }
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
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

        $insertCounter = 'insert into counters values (:all_tracks,:yourmusic_tracks,:all_artists,:yourmusic_artists,:all_albums,:yourmusic_albums,:playlists, :shows)';
        $stmt = $db->prepare($insertCounter);

        $stmt->bindValue(':all_tracks', $all_tracks[0]);
        $stmt->bindValue(':yourmusic_tracks', $yourmusic_tracks[0]);
        $stmt->bindValue(':all_artists', $all_artists[0]);
        $stmt->bindValue(':yourmusic_artists', $yourmusic_artists[0]);
        $stmt->bindValue(':all_albums', $all_albums[0]);
        $stmt->bindValue(':yourmusic_albums', $yourmusic_albums[0]);
        $stmt->bindValue(':playlists', $playlists_count[0]);
        $stmt->bindValue(':shows', $shows_count[0]);
        $stmt->execute();
    }
    catch(PDOException $e) {
        logMsg('Error(refreshLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return false;
    }

    $elapsed_time = time() - $words[3];
    $changedPlaylists = false;
    $changedShows = false;
    $changedFollowedArtists = false;
    $changedYourMusic = false;
    $addedMsg = '';
    $removedMsg = '';
    $updatedMsg = '';
    $addedShowMsg = '';
    $removeShowMsg = '';
    $updatedShowMsg = '';
    $yourMusicMsg = '';
    $addedFollowedArtistsMsg = '';
    $removeFollowedArtistsMsg = '';
    if ($nb_added_playlists > 0) {
        $addedMsg = $nb_added_playlists . ' added';
        $changedPlaylists = true;
    }

    if ($nb_removed_playlists > 0) {
        $removedMsg = $nb_removed_playlists . ' removed';
        $changedPlaylists = true;
    }

    if ($nb_updated_playlists > 0) {
        $updatedMsg = $nb_updated_playlists . ' updated';
        $changedPlaylists = true;
    }

    if ($your_music_updated) {
        $yourMusicMsg = ' - Your Music: updated ';

        if ($nb_added_albums > 0) {
            $yourMusicMsg .= $nb_added_albums . ' albums added';
        }

        if ($nb_removed_albums > 0) {
            $removedMsg = $nb_removed_albums . ' albums removed';
        }
        $changedYourMusic = true;
    }

    if ($nb_added_shows > 0) {
        $addedShowMsg = $nb_added_shows . ' added';
        $changedShows = true;
    }

    if ($nb_removed_shows > 0) {
        $removeShowMsg = $nb_removed_shows . ' removed';
        $changedShows = true;
    }

    if ($nb_updated_shows > 0) {
        $updatedShowMsg = $nb_updated_shows . ' updated';
        $changedShows = true;
    }

    if ($nb_added_followed_artists > 0) {
        $addedFollowedArtistsMsg = $nb_added_followed_artists . ' added';
        $changedFollowedArtists = true;
    }

    if ($nb_removed_followed_artists > 0) {
        $removeFollowedArtistsMsg = $nb_removed_followed_artists . ' removed';
        $changedFollowedArtists = true;
    }

    $message = '';
    if ($changedPlaylists) {
        $message .= 'Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg;
    }
    if ($changedShows) {
        $message .= 'Shows: ' . $addedShowMsg . ' ' . $removeShowMsg . ' ' . $updatedShowMsg;
    }
    if ($changedYourMusic) {
        $message .= $yourMusicMsg;
    }
    if ($changedFollowedArtists) {
        $message .= 'Followed artists: ' . $addedFollowedArtistsMsg . ' ' . $removeFollowedArtistsMsg;
    }
    if (!$changedPlaylists && !$changedYourMusic && !$changedShows && !$changedFollowedArtists) {
        $message = 'No change';
    }

    if (getenv('reduce_notifications') == 0) {
        displayNotificationWithArtwork($w, $message . ' - took ' . beautifyTime($elapsed_time, true), './images/update.png', 'Library refreshed');
    }

    if (file_exists($w->data() . '/library_old.db')) {
        deleteTheFile($w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');

    if ($use_artworks) {
        // Download artworks in background
        exec('php -f ./src/action.php -- "" "DOWNLOAD_ARTWORKS" "DOWNLOAD_ARTWORKS" >> "' . $w->cache() . '/action.log" 2>&1 & ');
    }

    deleteTheFile($w->data() . '/update_library_in_progress');
}
