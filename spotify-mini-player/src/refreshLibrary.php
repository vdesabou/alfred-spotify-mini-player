<?php

require_once './src/workflows.php';
require_once './src/functions.php';
require './vendor/autoload.php';


/**
 * refreshLibrary function.
 *
 * @param mixed $w
 */
function refreshLibrary($w)
{
    if (!file_exists($w->data().'/library.db')) {
        displayNotificationWithArtwork($w, 'Refresh library called while library does not exist', './images/warning.png');

        return;
    }

    touch($w->data().'/update_library_in_progress');
    $w->write('InitRefreshLibrary▹'. 0 .'▹'. 0 .'▹'.time().'▹'.'starting', 'update_library_in_progress');

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

    if ($use_artworks) {
        // db for fetch artworks
        $fetch_artworks_existed = true;
        $dbfile = $w->data().'/fetch_artworks.db';
        if (!file_exists($dbfile)) {
            touch($dbfile);
            $fetch_artworks_existed = false;
        }
        // kill previous process if running
        $pid = exec("ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'");
        if ($pid != '') {
            $ret = exec("kill -9 \"$pid\"");
        }
        if (file_exists($w->data().'/download_artworks_in_progress')) {
            deleteTheFile($w->data().'/download_artworks_in_progress');
        }

        try {
            $dbartworks = new PDO("sqlite:$dbfile", '', '', array(
                    PDO::ATTR_PERSISTENT => true,
                ));
            $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
            } catch (PDOException $e) {
                logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
            handleDbIssuePdoEcho($dbartworks, $w);
            $dbartworks = null;
            $db = null;

            return false;
        }
        $artworksToDownload = false;
    }

    rename($w->data().'/library.db', $w->data().'/library_old.db');
    copy($w->data().'/library_old.db', $w->data().'/library_new.db');
    $dbfile = $w->data().'/library_new.db';

    $nb_added_playlists = 0;
    $nb_removed_playlists = 0;
    $nb_updated_playlists = 0;

    try {
        $db = new PDO("sqlite:$dbfile", '', '', array(
                PDO::ATTR_PERSISTENT => true,
            ));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec('drop table counters');
        $db->exec('create table counters (all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int)');

        $getPlaylists = 'select * from playlists where uri=:uri';
        $stmtGetPlaylists = $db->prepare($getPlaylists);

        $insertPlaylist = 'insert into playlists values (:uri,:name,:nb_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser,:nb_playable_tracks,:duration_playlist,:nb_times_played,:collaborative,:public)';
        $stmtPlaylist = $db->prepare($insertPlaylist);

        $insertTrack = 'insert into tracks values (:yourmusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration,:nb_times_played,:local_track)';
        $stmtTrack = $db->prepare($insertTrack);

        $deleteFromTracks = 'delete from tracks where playlist_uri=:playlist_uri';
        $stmtDeleteFromTracks = $db->prepare($deleteFromTracks);

        $updatePlaylistsNbTracks = 'update playlists set nb_tracks=:nb_tracks,nb_playable_tracks=:nb_playable_tracks,duration_playlist=:duration_playlist,public=:public where uri=:uri';
        $stmtUpdatePlaylistsNbTracks = $db->prepare($updatePlaylistsNbTracks);

        $deleteFromTracksYourMusic = 'delete from tracks where yourmusic=:yourmusic';
        $stmtDeleteFromTracksYourMusic = $db->prepare($deleteFromTracksYourMusic);

        // // assume low number of podcast subscriptions
        $db->exec('drop table shows');
        $db->exec('create table shows (uri text PRIMARY KEY NOT NULL, name text, description text, media_type text, show_artwork_path text, explicit boolean, added_at text, languages text, nb_times_played int, is_externally_hosted boolean)');

        $insertShow = 'insert into shows values (:uri,:name,:description,:media_type,:show_artwork_path,:explicit,:added_at,:languages,:nb_times_played,:is_externally_hosted)';
        $stmtInsertShow = $db->prepare($insertShow);
    } catch (PDOException $e) {
        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    $savedListPlaylist = array();
    $offsetGetUserPlaylists = 0;
    $limitGetUserPlaylists = 50;
    do {
        $retry = true;
        $nb_retry = 0;
        try {
            $api = getSpotifyWebAPI($w);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
            handleSpotifyWebAPIException($w, $e);

            return false;
        }

        while ($retry) {
            try {
                // refresh api
                $api = getSpotifyWebAPI($w, $api);
                $userPlaylists = $api->getUserPlaylists(urlencode($userid), array(
                        'limit' => $limitGetUserPlaylists,
                        'offset' => $offsetGetUserPlaylists,
                    ));
                $retry = false;
            } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getUserPlaylists): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()->getLastResponse();
                    $retryAfter = $lastResponse['headers']['Retry-After'];
                    sleep($retryAfter);
                } else if ($e->getCode() == 404) {
                    // skip
                    break;
                } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                } else if ($e->getCode() == 500
                    || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                } else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }
        $nb_playlist_total = $userPlaylists->total;

        foreach ($userPlaylists->items as $playlist) {
            if ($playlist->name != '') {
                $savedListPlaylist[] = $playlist;
            }
        }
        $offsetGetUserPlaylists += $limitGetUserPlaylists;
    } while ($offsetGetUserPlaylists < $userPlaylists->total);

    // consider Your Music as a playlist for progress bar
    ++$nb_playlist_total;

    foreach ($savedListPlaylist as $playlist) {
        $tracks = $playlist->tracks;
        $owner = $playlist->owner;

        ++$nb_playlist;
        $w->write('Refresh Library▹'.$nb_playlist.'▹'.$nb_playlist_total.'▹'.$words[3].'▹'.escapeQuery($playlist->name), 'update_library_in_progress');

        try {
            // Loop on existing playlists in library
            $stmtGetPlaylists->bindValue(':uri', $playlist->uri);
            $stmtGetPlaylists->execute();

            $noresult = true;
            while ($playlists = $stmtGetPlaylists->fetch()) {
                $noresult = false;
                break;
            }
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
            handleDbIssuePdoEcho($db, $w);
            $dbartworks = null;
            $db = null;

            return;
        }

        // Playlist does not exist, add it
        if ($noresult == true) {
            ++$nb_added_playlists;
            $playlist_artwork_path = getPlaylistArtwork($w, $playlist->uri, true, true, $use_artworks);

            if ('-'.$owner->id.'-' == '-'.$userid.'-') {
                $ownedbyuser = 1;
            } else {
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
                        $userPlaylistTracks = $api->getPlaylistTracks($playlist->id, array(
                                'fields' => array(
                                    'total',
                                    'items(added_at)',
                                    'items(is_local)',
                                    'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
                                    'items.track.album(album_type,images,uri,name)',
                                    'items.track.artists(name,uri)',
                                ),
                                'limit' => $limitGetUserPlaylistTracks,
                                'offset' => $offsetGetUserPlaylistTracks,
                                'market' => $country_code,
                            ));
                        $retry = false;
                    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                        logMsg('Error(getPlaylistTracks): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

                        if ($e->getCode() == 429) { // 429 is Too Many Requests
                            $lastResponse = $api->getRequest()->getLastResponse();
                            $retryAfter = $lastResponse['headers']['Retry-After'];
                            sleep($retryAfter);
                        } else if ($e->getCode() == 404) {
                            // skip
                            break;
                        } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                            // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                            // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                            // retry any SSL error
                            ++$nb_retry;
                        } else if ($e->getCode() == 500
                            || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202) {
                            // retry
                            if ($nb_retry > 2) {
                                handleSpotifyWebAPIException($w, $e);
                                $retry = false;

                                return false;
                            }
                            ++$nb_retry;
                            sleep(5);
                        } else {
                            handleSpotifyWebAPIException($w, $e);
                            $retry = false;

                            return false;
                        }
                    }
                }

                foreach ($userPlaylistTracks->items as $item) {
                    if(!isset($item->track)) {
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
                        if (isset($track->linked_from) && isset($track->linked_from->uri)) {
                            $track->uri = $track->linked_from->uri;
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
                        } else {
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
                        } else {
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
                        } else {
                            $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, false, false, false, $use_artworks);
                        }
                    } catch (PDOException $e) {
                        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
                        $stmtTrack->execute();
                    } catch (PDOException $e) {
                        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                        handleDbIssuePdoEcho($db, $w);
                        $dbartworks = null;
                        $db = null;

                        return;
                    }
                    ++$nb_track_playlist;
                }

                $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
            } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

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
            } catch (PDOException $e) {
                logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                handleDbIssuePdoEcho($db, $w);
                $dbartworks = null;
                $db = null;

                return;
            }

            displayNotificationWithArtwork($w, 'Added playlist '.escapeQuery($playlist->name), $playlist_artwork_path, 'Refresh Library');
        } else {

            // check if this is a self-updated playlist (spotify and 30 tracks)
            $selfUpdatedPlaylistUpdated = false;

            $owner = getPlaylistOwner($w,$playlists[0]);

            if($owner == 'spotify' && $tracks->total == 30) {

                try {
                    $getOneTrack = 'select added_at from tracks where playlist_uri=:theplaylisturi order by added_at desc limit 1';
                    $stmtGetOneTrack = $db->prepare($getOneTrack);
                    $stmtGetOneTrack->bindValue(':theplaylisturi', $playlists[0]);
                    $stmtGetOneTrack->execute();
                    $theOneTrack = $stmtGetOneTrack->fetch();
                    date_default_timezone_set('UTC');
                    $today = date("c");
                    $last_updated  = $theOneTrack[0];
                    $today_time = strtotime($today);
                    $last_updated_time = strtotime($last_updated);

                    if( ($today_time - $last_updated_time) > 7*24*3600) {
                        $selfUpdatedPlaylistUpdated = true;
                    }
                } catch (PDOException $e) {
                    logMsg('Error(refreshLibrary - self-updated playlist): (exception '.jTraceEx($e).')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }
            }

            // number of tracks has changed or playlist name has changed or the privacy has changed, or spotify playlist (Release Radar, Discover Weekly)
            // update the playlist
            if ($selfUpdatedPlaylistUpdated || $playlists[2] != $tracks->total || $playlists[1] != escapeQuery($playlist->name) ||
                (($playlists[11] == '' && $playlist->public == true) || ($playlists[11] == true && $playlist->public == ''))) {
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
                } catch (PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
                            $userPlaylistTracks = $api->getPlaylistTracks($playlist->id, array(
                                    'fields' => array(
                                        'total',
                                        'items(added_at)',
                                        'items(is_local)',
                                        'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
                                        'items.track.album(album_type,images,uri,name)',
                                        'items.track.artists(name,uri)',
                                    ),
                                    'limit' => $limitGetUserPlaylistTracks,
                                    'offset' => $offsetGetUserPlaylistTracks,
                                    'market' => $country_code,
                                ));
                            $retry = false;
                        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                            logMsg('Error(getPlaylistTracks): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

                            if ($e->getCode() == 429) { // 429 is Too Many Requests
                                $lastResponse = $api->getRequest()->getLastResponse();
                                $retryAfter = $lastResponse['headers']['Retry-After'];
                                sleep($retryAfter);
                            } else if ($e->getCode() == 404) {
                                // skip
                                break;
                            } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                                // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                                // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                                // retry any SSL error
                                ++$nb_retry;
                            } else if ($e->getCode() == 500
                                || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202) {
                                // retry
                                if ($nb_retry > 2) {
                                    handleSpotifyWebAPIException($w, $e);
                                    $retry = false;

                                    return false;
                                }
                                ++$nb_retry;
                                sleep(5);
                            } else {
                                handleSpotifyWebAPIException($w, $e);
                                $retry = false;

                                return false;
                            }
                        }
                    }

                    foreach ($userPlaylistTracks->items as $item) {
                        if(!isset($item->track)) {
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
                            if (isset($track->linked_from) && isset($track->linked_from->uri)) {
                                $track->uri = $track->linked_from->uri;
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
                            } else {
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
                            } else {
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
                            } else {
                                $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, false, false, false, $use_artworks);
                            }
                        } catch (PDOException $e) {
                            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
                            $stmtTrack->execute();
                        } catch (PDOException $e) {
                            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                            handleDbIssuePdoEcho($db, $w);
                            $dbartworks = null;
                            $db = null;

                            return;
                        }
                        ++$nb_track_playlist;
                    }

                    $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
                } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

                try {
                    $stmtUpdatePlaylistsNbTracks->bindValue(':nb_tracks', $userPlaylistTracks->total);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':nb_playable_tracks', $nb_track_playlist);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':duration_playlist', beautifyTime($duration_playlist / 1000, true));
                    $stmtUpdatePlaylistsNbTracks->bindValue(':uri', $playlist->uri);
                    $stmtUpdatePlaylistsNbTracks->bindValue(':public', $playlist->public);
                    $stmtUpdatePlaylistsNbTracks->execute();
                } catch (PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                    handleDbIssuePdoEcho($db, $w);
                    $dbartworks = null;
                    $db = null;

                    return;
                }
                displayNotificationWithArtwork($w, 'Updated playlist '.escapeQuery($playlist->name), getPlaylistArtwork($w, $playlist->uri, true, false, $use_artworks), 'Refresh Library');
            } else {
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
                displayNotificationWithArtwork($w, 'Removed playlist '.$playlist_in_db[1], getPlaylistArtwork($w, $playlist_in_db[0], false, false, $use_artworks), 'Refresh Library');
            }
        }
    } catch (PDOException $e) {
        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return;
    }

    // check for update to Your Music
    $retry = true;
    $nb_retry = 0;
    while ($retry) {
        try {
            // refresh api
            $api = getSpotifyWebAPI($w, $api);

            // get only one, we just want to check total for now
            $userMySavedTracks = $api->getMySavedTracks(array(
                    'limit' => 1,
                    'offset' => 0,
                ));
            $retry = false;
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            logMsg('Error(getMySavedTracks): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

            if ($e->getCode() == 429) { // 429 is Too Many Requests
            $lastResponse = $api->getRequest()->getLastResponse();
            $retryAfter = $lastResponse['headers']['Retry-After'];
            sleep($retryAfter);
            } else if ($e->getCode() == 404) {
                // skip
                break;
            } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                // retry any SSL error
                ++$nb_retry;
            } else if ($e->getCode() == 500
            || $e->getCode() == 502 || $e->getCode() == 503) {
            // retry
            if ($nb_retry > 20) {
                handleSpotifyWebAPIException($w, $e);
                $retry = false;

                return false;
            }
            ++$nb_retry;
            sleep(15);
        } else {
            handleSpotifyWebAPIException($w, $e);
            $retry = false;

            return false;
        }
        }
    }

    try {
        // get current number of track in Your Music
        $getCount = 'select count(distinct uri) from tracks where yourmusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_tracks = $stmt->fetch();
    } catch (PDOException $e) {
        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
        handleDbIssuePdoEcho($db, $w);
        $db = null;

        return;
    }

    $your_music_updated = false;
    if ($yourmusic_tracks[0] != $userMySavedTracks->total) {
        $your_music_updated = true;
        // Your Music has changed, update it
        ++$nb_playlist;
        $w->write('Refresh Library▹'.$nb_playlist.'▹'.$nb_playlist_total.'▹'.$words[3].'▹'.'Your Music', 'update_library_in_progress');

        // delete tracks
        try {
            $stmtDeleteFromTracksYourMusic->bindValue(':yourmusic', 1);
            $stmtDeleteFromTracksYourMusic->execute();
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
            handleDbIssuePdoEcho($db, $w);
            $db = null;

            return;
        }

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
                } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                    logMsg('Error(getMySavedTracks): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

                    if ($e->getCode() == 429) { // 429 is Too Many Requests
                        $lastResponse = $api->getRequest()->getLastResponse();
                        $retryAfter = $lastResponse['headers']['Retry-After'];
                        sleep($retryAfter);
                    } else if ($e->getCode() == 404) {
                        // skip
                        break;
                    } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                        // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                        // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                        // retry any SSL error
                        ++$nb_retry;
                    } else if ($e->getCode() == 500
                        || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202) {
                        // retry
                        if ($nb_retry > 2) {
                            handleSpotifyWebAPIException($w, $e);
                            $retry = false;

                            return false;
                        }
                        ++$nb_retry;
                        sleep(5);
                    } else {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                }
            }

            foreach ($userMySavedTracks->items as $item) {
                if(!isset($item->track)) {
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
                    if (isset($track->linked_from) && isset($track->linked_from->uri)) {
                        $track->uri = $track->linked_from->uri;
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
                    } else {
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
                    } else {
                        $artist_artwork_path = getArtistArtwork($w, $theartisturi, $theartistname, false, false, false, false, $use_artworks);
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
                    } else {
                        $album_artwork_path = getTrackOrAlbumArtwork($w, $thealbumuri, false, false, false, $use_artworks);
                    }
                } catch (PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                    handleDbIssuePdoEcho($dbartworks, $w);
                    $dbartworks = null;
                    $db = null;

                    return false;
                }

                try {
                    $stmtTrack->bindValue(':yourmusic', 1);
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
                    $stmtTrack->bindValue(':playlist_name', '');
                    $stmtTrack->bindValue(':playlist_uri', '');
                    $stmtTrack->bindValue(':playable', $playable);
                    $stmtTrack->bindValue(':added_at', $item->added_at);
                    $stmtTrack->bindValue(':duration', beautifyTime($track->duration_ms / 1000));
                    $stmtTrack->bindValue(':nb_times_played', 0);
                    $stmtTrack->bindValue(':local_track', $local_track);
                    $stmtTrack->execute();
                } catch (PDOException $e) {
                    logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
                    handleDbIssuePdoEcho($db, $w);
                    $db = null;

                    return;
                }
            }

            $offsetGetMySavedTracks += $limitGetMySavedTracks;
        } while ($offsetGetMySavedTracks < $userMySavedTracks->total);
    }

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
                $userMySavedShows = $api->getMySavedShows(array(
                        'limit' => $limitGetMySavedShows,
                        'offset' => $offsetGetMySavedShows,
                        'market' => $country_code,
                    ));
                $retry = false;
            } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                logMsg('Error(getMySavedShows): retry '.$nb_retry.' (exception '.jTraceEx($e).')');

                if ($e->getCode() == 429) { // 429 is Too Many Requests
                    $lastResponse = $api->getRequest()->getLastResponse();
                    $retryAfter = $lastResponse['headers']['Retry-After'];
                    sleep($retryAfter);
                } else if ($e->getCode() == 404) {
                    // skip
                    break;
                } else if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
                    // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
                    // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
                    // retry any SSL error
                    ++$nb_retry;
                } else if ($e->getCode() == 500
                    || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202) {
                    // retry
                    if ($nb_retry > 2) {
                        handleSpotifyWebAPIException($w, $e);
                        $retry = false;

                        return false;
                    }
                    ++$nb_retry;
                    sleep(5);
                } else {
                    handleSpotifyWebAPIException($w, $e);
                    $retry = false;

                    return false;
                }
            }
        }

        foreach ($userMySavedShows->items as $show) {
            $savedMySavedShows[] = $show;
        }

        $offsetGetMySavedShows += $limitGetMySavedShows;
    } while ($offsetGetMySavedShows < $userMySavedShows->total);

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
            } else {
                $show_artwork_path = getShowArtwork($w, $show->uri, false, false, false, $use_artworks);
            }
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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
            $stmtInsertShow->bindValue(':languages', 'FIXTHIS');
            $stmtInsertShow->bindValue(':added_at', $item->added_at);
            $stmtInsertShow->bindValue(':nb_times_played', 0);
            $stmtInsertShow->bindValue(':added_at', $show->is_externally_hosted);
            $stmtInsertShow->execute();
        } catch (PDOException $e) {
            logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
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

        $getCount = 'select count(distinct uri) from tracks where yourmusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_tracks = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_artists = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks where yourmusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $yourmusic_artists = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_albums = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks where yourmusic=1';
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
    } catch (PDOException $e) {
        logMsg('Error(refreshLibrary): (exception '.jTraceEx($e).')');
        handleDbIssuePdoEcho($db, $w);
        $dbartworks = null;
        $db = null;

        return false;
    }

    $elapsed_time = time() - $words[3];
    $changedPlaylists = false;
    $changedYourMusic = false;
    $addedMsg = '';
    $removedMsg = '';
    $updatedMsg = '';
    $yourMusicMsg = '';
    if ($nb_added_playlists > 0) {
        $addedMsg = $nb_added_playlists.' added';
        $changedPlaylists = true;
    }

    if ($nb_removed_playlists > 0) {
        $removedMsg = $nb_removed_playlists.' removed';
        $changedPlaylists = true;
    }

    if ($nb_updated_playlists > 0) {
        $updatedMsg = $nb_updated_playlists.' updated';
        $changedPlaylists = true;
    }

    if ($your_music_updated) {
        $yourMusicMsg = ' - Your Music: updated';
        $changedYourMusic = true;
    }

    if ($changedPlaylists && $changedYourMusic) {
        $message = 'Playlists: '.$addedMsg.' '.$removedMsg.' '.$updatedMsg.' '.$yourMusicMsg;
    } elseif ($changedPlaylists) {
        $message = 'Playlists: '.$addedMsg.' '.$removedMsg.' '.$updatedMsg;
    } elseif ($changedYourMusic) {
        $message = $yourMusicMsg;
    } else {
        $message = 'No change';
    }

    if(getenv('reduce_notifications') == 0) {
        displayNotificationWithArtwork($w, $message.' - took '.beautifyTime($elapsed_time, true), './images/update.png', 'Library refreshed');
    }

    if (file_exists($w->data().'/library_old.db')) {
        deleteTheFile($w->data().'/library_old.db');
    }
    rename($w->data().'/library_new.db', $w->data().'/library.db');

    if ($use_artworks) {
        // Download artworks in background
        exec('php -f ./src/action.php -- "" "DOWNLOAD_ARTWORKS" "DOWNLOAD_ARTWORKS" >> "'.$w->cache().'/action.log" 2>&1 & ');
    }

    deleteTheFile($w->data().'/update_library_in_progress');
}