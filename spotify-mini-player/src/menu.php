<?php
/**
 * oAuthChecks function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $update_in_progress
 */
function oAuthChecks($w, $query, $settings, $update_in_progress) {
    $oauth_client_id = $settings->oauth_client_id;
    $oauth_client_secret = $settings->oauth_client_secret;
    $oauth_access_token = $settings->oauth_access_token;

    ////
    // OAUTH checks
    // Check oauth config : Client ID and Client Secret
    if (($oauth_client_id == '' || $oauth_client_secret == '') && substr_count($query, '▹') == 0) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'app_setup'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Launch Spotify application setup', array('Your browser will open, follow instructions there', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'yes', null, '');
        listUsers($w);
        echo $w->tojson();
        exit;
    }

    if ($oauth_access_token == '' && substr_count($query, '▹') == 0) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'oauth_login'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Authenticate to Spotify', array('This will start the authentication process', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'yes', null, '');
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/setup/' /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Go to the website alfred-spotify-mini-player.com to see setup tutorial', 'This will open the Application page with your default browser', './images/website.png', 'yes', null, '');

        listUsers($w);

        echo $w->tojson();
        exit;
    }
}

/**
 * mainMenu function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function mainMenu($w, $query, $settings, $db, $update_in_progress) {
    $all_playlists = $settings->all_playlists;
    $is_alfred_playlist_active = $settings->is_alfred_playlist_active;
    $radio_number_tracks = $settings->radio_number_tracks;
    $max_results = $settings->max_results;
    $alfred_playlist_uri = $settings->alfred_playlist_uri;
    $alfred_playlist_name = $settings->alfred_playlist_name;
    $userid = $settings->userid;
    $use_artworks = $settings->use_artworks;
    $output_application = $settings->output_application;
    $quick_mode = $settings->quick_mode;
    $fuzzy_search = $settings->fuzzy_search;
    $podcasts_enabled = $settings->podcasts_enabled;

    ////////
    // MAIN MENU
    //////////////
    $retry = true;
    if($update_in_progress && file_exists($w->data() . '/create_library')) {
        $results = getExternalResults($w, 'counters', array('all_tracks','yourmusic_tracks','all_artists','yourmusic_artists','all_albums','yourmusic_albums','playlists','shows','episodes'), '', 'where id=0');
    } else {
        while ($retry) {
            $getCounters = 'select all_tracks,yourmusic_tracks,all_artists,yourmusic_artists,all_albums,yourmusic_albums,playlists,shows,episodes from counters where id=0';
            try {
                $stmt = $db->prepare($getCounters);
                $stmt->execute();
                $results = $stmt->fetchAll();
                $retry = false;
            }
            catch(PDOException $e) {
                $db->exec('drop table counters');
                $db->exec('create table counters (id int PRIMARY KEY, all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int, episodes int)');
                updateCounters($w, $db);
            }
        }
    }
    $counters = $results[0];
    $all_tracks = $counters[0];
    $yourmusic_tracks = $counters[1];
    $all_artists = $counters[2];
    $yourmusic_artists = $counters[3];
    $all_albums = $counters[4];
    $yourmusic_albums = $counters[5];
    $nb_playlists = $counters[6];
    $nb_shows = $counters[7];

    if ($update_in_progress == true) {
        $in_progress_data = $w->read('update_library_in_progress');
        $update_library_in_progress_words = explode('▹', $in_progress_data);
        $elapsed_time = time() - $update_library_in_progress_words[3];
        if (startsWith($update_library_in_progress_words[0], 'Init')) {
            if ($elapsed_time < 5400) {
                $w->result(null, $w->data().'/update_library_in_progress', 'Initialization phase since '.beautifyTime($elapsed_time, true).' : '.floatToSquares(0).'Currently processing '.$update_library_in_progress_words[4],array(
                    'Waiting for Spotify servers to return required data it may take time depending on your library',
                    'alt' => '',
                    'cmd' => '',
                    'shift' => '',
                    'fn' => '',
                    'ctrl' => '',
                ), './images/update_in_progress.png', 'no', null, '');
            } else {
                $w->result(null, '', 'There is a problem, the initialization phase took more than 90 minutes' ,array(
                    'Choose kill update library below and report to the author',
                    'alt' => '',
                    'cmd' => '',
                    'shift' => '',
                    'fn' => '',
                    'ctrl' => '',
                ), './images/warning.png', 'no', null, '');
                $w->result(null, serialize(array(
                    '' /*track_uri*/,
                    '' /* album_uri */,
                    '' /* artist_uri */,
                    '' /* playlist_uri */,
                    '' /* spotify_command */,
                    '' /* query */,
                    '' /* other_settings*/,
                    'kill_update' /* other_action */,
                    '' /* alfred_playlist_uri */,
                    '' /* artist_name */,
                    '' /* track_name */,
                    '' /* album_name */,
                    '' /* track_artwork_path */,
                    '' /* artist_artwork_path */,
                    '' /* album_artwork_path */,
                    '' /* playlist_name */,
                    '' /* playlist_artwork_path */,
                    '' /* $alfred_playlist_name */,
                    '' /* now_playing_notifications */,
                    '' /* is_alfred_playlist_active */,
                    '' /* country_code*/,
                    '',
                    /* userid*/
                )), 'Kill update library', 'This will stop the library update', './images/kill.png', 'yes', '');
            }
        }
        else {
            if ($update_library_in_progress_words[2] != 0) {
                $w->result(null, $w->data().'/update_library_in_progress', $update_library_in_progress_words[0].' in progress since '.beautifyTime($elapsed_time, true).' : '.floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])),array(
                    $update_library_in_progress_words[1].'/'.$update_library_in_progress_words[2].' playlists/shows processed so far. Currently processing '.$update_library_in_progress_words[4],
                    'alt' => '',
                    'cmd' => '',
                    'shift' => '',
                    'fn' => '',
                    'ctrl' => '',
                ), './images/update_in_progress.png', 'no', null, '');
            } else {
                $w->result(null, $w->data().'/update_library_in_progress', $update_library_in_progress_words[0].' in progress since '.beautifyTime($elapsed_time, true).' : '.floatToSquares(0),array(
                    'Nothing processed so far',
                    'alt' => '',
                    'cmd' => '',
                    'shift' => '',
                    'fn' => '',
                    'ctrl' => '',
                ), './images/update_in_progress.png', 'no', null, '');
            }
        }
    }
    $fuzzy_search_text = '';
    if ($fuzzy_search) {
        $fuzzy_search_text = getenv('emoji_fuzzy').' Fuzzy ';
    }
    $quick_mode_text = '';
    if ($quick_mode) {
        $quick_mode_text = ' '.getenv('emoji_separator').' '.getenv('emoji_quickmode').' Quick Mode is active';
    }
    if ($all_playlists == true) {
        $w->result(null, '', $fuzzy_search_text . 'Search for music in "Your Music" and your ' . $nb_playlists . ' playlists', array('Begin typing at least 3 characters to start search in your ' . $all_tracks . ' tracks, ' . $nb_playlists . ' playlists and ' . $nb_shows . ' shows' . $quick_mode_text, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search.png', 'no', null, '');
    }
    else {
        $w->result(null, '', $fuzzy_search_text . 'Search for music in "Your Music" only', array('Begin typing at least 3 characters to start search in your ' . $yourmusic_tracks . ' tracks, ' . $nb_playlists . ' playlists and ' . $nb_shows . ' shows' . $quick_mode_text, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search_scope_yourmusic_only.png', 'no', null, '');
    }

    if (getenv('menu_display_current_track') == 1) {
        $w->result(null, '', 'Current Track', array('Display current track information and browse various options', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/current_track.png', 'no', null, 'Current Track▹');
    }

    if ($output_application == 'CONNECT') {
        if (getenv('menu_display_spotify_connect') == 1) {
            $w->result(null, '', 'Spotify Connect', array('Display Spotify Connect devices', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'no', null, 'Spotify Connect▹');
        }
    }

    if (getenv('menu_display_play_queue') == 1) {
        $w->result(null, '', 'Play Queue', array('Get the current play queue. Always use the workflow to launch tracks otherwise play queue will be empty', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/play_queue.png', 'no', null, 'Play Queue▹');
    }

    if (getenv('menu_display_lookup_current_artist_online') == 1) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'lookup_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Lookup Current Artist online', array(getenv('emoji_online').' Query all albums/tracks from current artist online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_artist.png', 'yes', '');
    }

    if (getenv('menu_display_show_in_spotify') == 1 && $podcasts_enabled) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'show_in_spotify'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Show current track in Spotify Desktop', array('This will open Spotify Desktop with current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', '');
    }

    if (getenv('menu_display_search_online') == 1) {
        $w->result(null, '', 'Search online', array(getenv('emoji_online').' You can search playlists, artists, albums, shows, episodes or tracks online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online.png', 'no', null, 'Search Online▹');
    }

    if (getenv('menu_display_alfred_playlist') == 1) {
        if ($is_alfred_playlist_active == true) {
            if ($alfred_playlist_name != '') {

                $w->result(null, '', 'Browse your Alfred playlist (' . $alfred_playlist_name . ')', array('You can change the Alfred Playlist during next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), getPlaylistArtwork($w, $alfred_playlist_uri, false, false, $use_artworks), 'no', null, 'Playlist▹' . $alfred_playlist_uri . '▹');
            }
            else {
                $title = getenv('emoji_alfred') . 'Alfred Playlist '.getenv('emoji_separator').' not set';
                $w->result(null, '', $title, array('Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
            }
        }
    }

    if (getenv('menu_display_your_recent_tracks') == 1) {
        $w->result(null, '', 'Your Recent Tracks', array('Browse your recent tracks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/recent.png', 'no', null, 'Recent Tracks▹');
    }
    if (getenv('menu_display_browse_by_playlist') == 1) {
        $w->result(null, '', 'Playlists', array('Browse by playlist' . ' (' . $nb_playlists . ' playlists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/playlists.png', 'no', null, 'Playlist▹');
    }
    if (getenv('menu_display_browse_your_music') == 1) {
        $w->result(null, '', 'Your Music', array('Browse Your Music' . ' (' . $yourmusic_tracks . ' tracks '.getenv('emoji_separator').' ' . $yourmusic_albums . '  albums '.getenv('emoji_separator').' ' . $yourmusic_artists . ' artists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/yourmusic.png', 'no', null, 'Your Music▹');
    }
    if ($all_playlists == true) {
        if (getenv('menu_display_browse_by_artist') == 1) {
            $w->result(null, '', 'Artists', array('Browse by artist' . ' (' . $all_artists . ' artists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/artists.png', 'no', null, 'Artist▹');
        }
        if (getenv('menu_display_browse_by_album') == 1) {
            $w->result(null, '', 'Albums', array('Browse by album' . ' (' . $all_albums . ' albums)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/albums.png', 'no', null, 'Album▹');
        }
    }
    else {
        if (getenv('menu_display_browse_by_artist') == 1) {
            $w->result(null, '', 'Artists in "Your Music"', array('Browse by artist' . ' (' . $yourmusic_artists . ' artists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/artists.png', 'no', null, 'Artist▹');
        }
        if (getenv('menu_display_browse_by_album') == 1) {
            $w->result(null, '', 'Albums in "Your Music"', array('Browse by album' . ' (' . $yourmusic_albums . ' albums)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/albums.png', 'no', null, 'Album▹');
        }
    }

    if (getenv('menu_display_browse_by_show') == 1 && $podcasts_enabled) {
        $w->result(null, '', 'Shows', array('Browse by show' . ' (' . $nb_shows . ' shows)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/shows.png', 'no', null, 'Show▹');
    }

    if (getenv('menu_display_browse_categories') == 1) {
        $w->result(null, '', 'Browse', array('Browse Spotify by categories as in the Spotify player’s “Browse” tab', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹');
    }
    if (getenv('menu_display_your_tops') == 1) {
        $w->result(null, '', 'Your Tops', array('Browse your top artists and top tracks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Your Tops▹');
    }

    if ($is_alfred_playlist_active == true) {
        $alfred_playlist_state = getenv('emoji_alfred') . 'Alfred Playlist';
    }
    else {
        $alfred_playlist_state = 'Your Music';
    }
    if ($all_playlists == true) {
        $w->result(null, '', 'Settings', array('User=' . $userid . ', Search scope=<All>, Max results=<' . $max_results . '>, Controlling <' . $alfred_playlist_state . '> Radio tracks=<' . $radio_number_tracks . '>', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Settings▹');
    }
    else {
        $w->result(null, '', 'Settings', array('User=' . $userid . ', Search scope=<Your Music>, Max results=<' . $max_results . '>, Controlling <' . $alfred_playlist_state . '> Radio tracks=<' . $radio_number_tracks . '>', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Settings▹');
    }
}

/**
 * mainSearch function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function mainSearch($w, $query, $settings, $db, $update_in_progress) {
    $all_playlists = $settings->all_playlists;
    $max_results = $settings->max_results;
    $userid = $settings->userid;
    $quick_mode = $settings->quick_mode;
    $output_application = $settings->output_application;
    $search_order = $settings->search_order;
    $fuzzy_search = $settings->fuzzy_search;
    $podcasts_enabled = $settings->podcasts_enabled;

    $search_categories = explode('▹', $search_order);

    foreach ($search_categories as $search_category) {

        if ($search_category == 'playlist') {

            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $query, 'playlists', array('uri','name','nb_tracks','author','username','playlist_artwork_path','ownedbyuser','nb_playable_tracks','duration_playlist','collaborative','public'), $max_results, '2,4', '');
            } else {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public,nb_times_played from playlists where (name_deburr like :query or author like :query) order by nb_times_played desc';
                $stmt = $db->prepare($getPlaylists);
                $stmt->bindValue(':query', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    return;
                }
            }

            foreach ($results as $playlist) {
                $added = ' ';
                $public_status = '';
                if (startswith($playlist[1], 'Artist radio for')) {
                    $added = getenv('emoji_radio').' ';
                }
                if ($playlist[9]) {
                    $public_status = 'collaborative';
                }
                else {
                    if ($playlist[10]) {
                        $public_status = 'public';
                    }
                    else {
                        $public_status = 'private';
                    }
                }

                if ($quick_mode) {
                    if ($playlist[10]) {
                        $public_status_contrary = 'private';
                    }
                    else {
                        $public_status_contrary = 'public';
                    }
                    $subtitle = getenv('emoji_quickmode').'Launch Playlist';
                    $subtitle = $subtitle . ' ,⇧ ▹ add playlist to ...,  ⌥ ▹ change playlist privacy to ' . $public_status_contrary;
                    $added = ' ';
                    if ($userid == $playlist[4] && $public_status != 'collaborative') {
                        $cmdMsg = 'Change playlist privacy to ' . $public_status_contrary;
                    }
                    else {
                        $cmdMsg = '';
                    }
                    if (startswith($playlist[1], 'Artist radio for')) {
                        $added = getenv('emoji_radio').' ';
                    }
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, $playlist[0] /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'set_playlist_privacy_to_' . $public_status_contrary /* other_action */, ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, $playlist[1] /* playlist_name */, $playlist[5], /* playlist_artwork_path */
                    )), getenv('emoji_playlist') . $added . $playlist[1] . ' by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8], array($subtitle, 'alt' => '', 'cmd' => $cmdMsg, 'shift' => 'Add playlist ' . $playlist[1] . ' to ...', 'fn' => '', 'ctrl' => '',), $playlist[5], 'yes', null, '');
                }
                else {
                    $w->result(null, '', getenv('emoji_playlist') . $added . $playlist[1], array('Browse ' . $public_status . ' playlist by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $playlist[5], 'no', null, 'Playlist▹' . $playlist[0] . '▹');
                }
            }
        }

        if ($search_category == 'artist') {

            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $results = getFuzzySearchResults($w, $update_in_progress, $query, 'followed_artists', array('name','uri','artist_artwork_path'), $max_results, '1', '');
                }
                else {
                    $results = getFuzzySearchResults($w, $update_in_progress, $query, 'tracks', array('artist_name','artist_uri','artist_artwork_path'), $max_results, '1', '');
                }
            } else {
                // Search artists
                if ($all_playlists == false) {
                    $getArtists = 'select name,uri,artist_artwork_path from followed_artists where name_deburr like :query limit ' . $max_results;
                }
                else {
                    $getArtists = 'select artist_name,artist_uri,artist_artwork_path from tracks where artist_name_deburr like :query limit ' . $max_results;
                }
                $stmt = $db->prepare($getArtists);
                $stmt->bindValue(':query', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    return;
                }
            }

            foreach ($results as $track) {
                if (checkIfResultAlreadyThere($w->results(), getenv('emoji_artist').' ' . $track[0]) == false) {
                    if ($quick_mode) {
                        $w->result(null, serialize(array(''
                        /*track_uri*/, ''
                        /* album_uri */, $track[1] /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'playartist'
                        /* other_action */, $track[0] /* artist_name */, ''
                        /* track_name */, ''
                        /* album_name */, ''
                        /* track_artwork_path */, $track[0] /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), getenv('emoji_artist').' ' . $track[0], getenv('emoji_quickmode').'Play artist', $track[2], 'yes', null, '');
                    }
                    else {
                        $w->result(null, '', getenv('emoji_artist').' ' . $track[0], array('Browse this artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track[2], 'no', null, 'Artist▹' . $track[1] . '∙' . $track[0] . '▹');
                    }
                }
            }
        }

        if ($search_category == 'track') {

            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $where_clause = 'where yourmusic=1';
                }
                else {
                    $where_clause = '';
                }
                $results = getFuzzySearchResults($w, $update_in_progress, $query, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6..8', $where_clause);
            } else {
                // Search tracks
                if ($all_playlists == false) {
                    $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name_deburr like :query or album_name_deburr like :query or track_name_deburr like :query)' . '  order by added_at desc limit ' . $max_results;
                }
                else {
                    $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where (artist_name_deburr like :query or album_name_deburr like :query or track_name_deburr like :query)' . '  order by added_at desc limit ' . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':query', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    return;
                }
            }

            $quick_mode_text = '';
            if ($quick_mode) {
                $quick_mode_text = getenv('emoji_quickmode');
            }
            foreach ($results as $track) {
                $subtitle = $track[6];
                $added = '';
                if ($track[18] == true) {
                    if ($output_application == 'MOPIDY') {
                        // skip local tracks if using Mopidy
                        continue;
                    }
                    $added = getenv('emoji_local_track').' ';
                }
                if (checkIfResultAlreadyThere($w->results(), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
                    if ($track[14] == true) {
                        $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, ''
                        /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], array($quick_mode_text . $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', array('copy' => $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], 'largetype' => $track[7] . ' '.getenv('emoji_separator').' ' . $track[5],), '');
                    }
                    else {
                        $w->result(null, '', getenv('emoji_not_playable').' ' . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
                    }
                }
            }
        }

        if ($search_category == 'album') {

            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $where_clause = 'where yourmusic_album=1';
                }
                else {
                    $where_clause = '';
                }
                $results = getFuzzySearchResults($w, $update_in_progress, $query, 'tracks', array('album_name','album_uri','album_artwork_path','uri','artist_name'), $max_results, '1,5', $where_clause);
            } else {
                // Search albums
                if ($all_playlists == false) {
                    $getTracks = 'select album_name,album_uri,album_artwork_path,uri, artist_name from tracks where yourmusic=1 and album_name != "" and (album_name_deburr like :album_name or artist_name_deburr like :album_name) group by album_name order by max(added_at) desc limit ' . $max_results;
                }
                else {
                    $getTracks = 'select album_name,album_uri,album_artwork_path,uri, artist_name from tracks where album_name != "" and (album_name_deburr like :album_name or artist_name_deburr like :album_name) group by album_name order by max(added_at) desc limit ' . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':album_name', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    return;
                }
            }

            foreach ($results as $track) {
                $nb_album_tracks = getNumberOfTracksForAlbum($update_in_progress, $w, $db, $track[1]);
                if (checkIfResultAlreadyThere($w->results(), getenv('emoji_album').' ' . $track[0]. ' (' . $nb_album_tracks . ' tracks)'. ' by '.$track[4]) == false) {
                    if ($track[1] == '') {
                        // can happen for local tracks
                        $track[1] = $track[3];
                    }
                    if ($quick_mode) {
                        $w->result(null, serialize(array(''
                        /*track_uri*/, $track[1] /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'playalbum'
                        /* other_action */, ''
                        /* artist_name */, ''
                        /* track_name */, $track[0] /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, $track[2] /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), getenv('emoji_album').' ' . $track[0], getenv('emoji_quickmode').'Play album', $track[2], 'yes', null, '');
                    }
                    else {
                        $w->result(null, '', getenv('emoji_album').' ' . $track[0]. ' (' . $nb_album_tracks . ' tracks)'. ' by '.$track[4], array('Browse this album', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track[2], 'no', null, 'Album▹' . $track[1] . '∙' . $track[0] . '▹');
                    }
                }
            }
        }

        if ($search_category == 'show' && $podcasts_enabled) {

            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $query, 'shows', array('uri','name','description','media_type','show_artwork_path','explicit','added_at','languages','nb_times_played','is_externally_hosted', 'nb_episodes'), $max_results, '2', '');
            } else {
                $getShows = 'select * from shows where name_deburr like :query limit ' . $max_results;
                $stmt = $db->prepare($getShows);
                $stmt->bindValue(':query', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    exit;
                }
            }

            foreach ($results as $show) {
                if (checkIfResultAlreadyThere($w->results(), getenv('emoji_show').' ' . $show[1] . ' (' . $show[10] . ' episodes)') == false) {
                    $w->result(null, '', getenv('emoji_show').' ' . $show[1] . ' (' . $show[10] . ' episodes)', array('Browse this show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $show[4], 'no', null, 'Show▹' . $show[0] . '∙' . $show[1] . '▹');
                }
            }
        }

        if ($search_category == 'episode' && $podcasts_enabled) {
            // Search episodes
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $query, 'episodes', array('uri', 'name', 'uri', 'show_uri', 'show_name', 'description', 'episode_artwork_path', 'is_playable', 'languages', 'nb_times_played', 'is_externally_hosted', 'duration_ms', 'explicit', 'release_date', 'release_date_precision', 'audio_preview_url', 'fully_played', 'resume_position_ms'), $max_results, '2,4', '');
            } else {
                $getEpisodes = 'select uri, name, uri, show_uri, show_name, description, episode_artwork_path, is_playable, languages, nb_times_played, is_externally_hosted, duration_ms, explicit, release_date, release_date_precision, audio_preview_url, fully_played, resume_position_ms from episodes where name_deburr like :name order by release_date desc limit ' . $max_results;
                $stmt = $db->prepare($getEpisodes);
                $stmt->bindValue(':name', '%' . deburr($query) . '%');
                try {
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    exit;
                }
            }

            foreach ($results as $episodes) {
                $subtitle = $episodes[6];

                $fully_played = '';
                if ($episodes[16] == 1) {
                    // fully_played
                    $fully_played = '✔️';
                }
                if (checkIfResultAlreadyThere($w->results(), getenv('emoji_show').' ' . $fully_played . $episodes[1]) == false) {
                    if ($episodes[7] == true) {
                        $w->result(null, serialize(array($episodes[2] /*track_uri*/, $episodes[3] /* album_uri */, $episodes[4] /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'play_episode'
                        /* other_action */, $episodes[7] /* artist_name */, $episodes[5] /* track_name */, $episodes[6] /* album_name */, $episodes[9] /* track_artwork_path */, $episodes[10] /* artist_artwork_path */, $episodes[11] /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), getenv('emoji_show').' ' . $fully_played . $episodes[1], array('Progress: ' . floatToCircles(intval($episodes[17]) / intval($episodes[11])) . ' Duration ' . beautifyTime($episodes[11] / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episodes[13] . ' '.getenv('emoji_separator').' Languages: ' . $episodes[8], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $episodes[6], 'yes', null, '');
                    }
                    else {
                        $w->result(null, '', getenv('emoji_not_playable').' ' . getenv('emoji_show').' ' . $fully_played . $episodes[1], 'Progress: ' . floatToCircles(intval($episodes[17]) / intval($episodes[11])) . ' Duration ' . beautifyTime($episodes[11] / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episodes[13] . ' '.getenv('emoji_separator').' Languages: ' . $episodes[8], $episodes[6], 'no', null, '');
                    }
                }
            }
        }
    } // end foreach search_category

    if ($output_application != 'MOPIDY') {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, base64_encode($query) /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, ''
        /* other_action */,

        ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Search for ' . $query . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
    }

    $w->result(null, null, 'Search for ' . $query . ' online', array('This will search online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online.png', 'no', null, 'Search Online▹' . $query);
}

/**
 * searchCategoriesFastAccess function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function searchCategoriesFastAccess($w, $query, $settings, $db, $update_in_progress) {
    $alfred_playlist_name = $settings->alfred_playlist_name;
    $now_playing_notifications = $settings->now_playing_notifications;
    $podcasts_enabled = $settings->podcasts_enabled;

    // Search categories for fast access
    if (strpos(strtolower('playlists'), strtolower($query)) !== false) {
        $w->result(null, '', 'Playlists', array('Browse by playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/playlists.png', 'no', null, 'Playlist▹');
    }
    if (strpos(strtolower('albums'), strtolower($query)) !== false) {
        $w->result(null, '', 'Albums', array('Browse by album', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/albums.png', 'no', null, 'Album▹');
    }
    if (strpos(strtolower('browse'), strtolower($query)) !== false) {
        $w->result(null, '', 'Browse', array('Browse Spotify by categories as in the Spotify player’s “Browse” tab', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹');
    }
    if (strpos(strtolower('your top'), strtolower($query)) !== false) {
        $w->result(null, '', 'Your Tops', array('Browse your top artists and top tracks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Your Tops▹');
    }
    if (strpos(strtolower('recent'), strtolower($query)) !== false) {
        $w->result(null, '', 'Your Recent Tracks', array('Browse your recent tracks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/recent.png', 'no', null, 'Recent Tracks▹');
    }
    if (strpos(strtolower('lookup current artist online'), strtolower($query)) !== false) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'lookup_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Lookup Current Artist online', array(getenv('emoji_online').' Query all albums/tracks from current artist online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_artist.png', 'yes', '');
    }
    if (strpos(strtolower('search online'), strtolower($query)) !== false) {
        $w->result(null, '', 'Search online', array(getenv('emoji_online').' You can search playlists, artists, albums, shows, episodes or tracks online, i.e not in your librar', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online.png', 'no', null, 'Search Online▹');
    }
    if (strpos(strtolower('new releases'), strtolower($query)) !== false) {
        $w->result(null, '', 'New Releases', array('Browse new album releases', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹');
    }
    if (strpos(strtolower('artists'), strtolower($query)) !== false) {
        $w->result(null, '', 'Artists', array('Browse by artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/artists.png', 'no', null, 'Artist▹');
    }
    if (strpos(strtolower('show'), strtolower($query)) !== false || strpos(strtolower('pod'), strtolower($query)) !== false) {
        if ($podcasts_enabled) {
            $w->result(null, '', 'Shows', array('Browse by show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/shows.png', 'no', null, 'Show▹');
        }
    }
    if (strpos(strtolower('play queue'), strtolower($query)) !== false) {
        if ($now_playing_notifications == true) {
            $w->result(null, '', 'Play Queue', array('Get the current play queue. Always use the workflow to launch tracks otherwise play queue will be empty', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/play_queue.png', 'no', null, 'Play Queue▹');
        }
    }
    if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
        $w->result(null, '', getenv('emoji_alfred') . 'Alfred Playlist (currently set to <' . $alfred_playlist_name . '>)', array('Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
    }
    if (strpos(strtolower('settings'), strtolower($query)) !== false) {
        $w->result(null, '', 'Settings', array('Go to settings', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Settings▹');
    }
    if (strpos(strtolower('featured playlist'), strtolower($query)) !== false) {
        $w->result(null, '', 'Featured Playlist', array('Browse the current featured playlists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹');
    }
    if (strpos(strtolower('your music'), strtolower($query)) !== false) {
        $w->result(null, '', 'Your Music', array('Browse Your Music', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/tracks.png', 'no', null, 'Your Music▹');
    }
    if (strpos(strtolower('current track'), strtolower($query)) !== false) {
        $w->result(null, '', 'Current Track', array('Display current track information and browse various options', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/current_track.png', 'no', null, 'Current Track▹');
    }
    if (strpos(strtolower('spotify connect'), strtolower($query)) !== false) {
        $w->result(null, '', 'Spotify Connect', array('Display Spotify Connect devices', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'no', null, 'Spotify Connect▹');
    }
}

/**
 * searchCommandsFastAccess function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function searchCommandsFastAccess($w, $query, $settings, $db, $update_in_progress) {
    $is_alfred_playlist_active = $settings->is_alfred_playlist_active;
    $output_application = $settings->output_application;

    $cmd = '';
    if ($output_application == 'CONNECT') {
        $cmd = 'Activate/Deactivate repeating in Spotify for current track';
    }

    if (countCharacters($query) < 2) {
        ////////
        // Fast Access to commands
        //////////////
        $w->result('SpotifyMiniPlayer_' . 'next', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'next'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'previous', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'previous'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'lookup_current_artist', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'lookup_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Lookup Current Artist online', array(getenv('emoji_online').' Query all albums/tracks from current artist online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_artist.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'show_in_spotify', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'show_in_spotify'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Show current track in Spotify Desktop', array('This will open Spotify Desktop with current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'lyrics', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'lyrics'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Get Lyrics for current track', array('Get current track lyrics', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'play', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'play'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'play_current_artist', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'play_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'follow_current_artist', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'follow_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Follow current artist', 'Follow the current artist', './images/follow.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'unfollow_current_artist', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'unfollow_current_artist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Unfollow current artist', 'Unfollow the current artist', './images/follow.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'play_current_album', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'play_current_album'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'pause', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'pause'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'playpause', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'playpause'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'current', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'current'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'debug', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'open_debug_tools'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Open debug tools', array('This is how you can access information required for further troubleshooting', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/debug.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'random', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'random'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Random Track', 'Play random track', './images/random.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'random_album', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'random_album'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Random Album', 'Play random album', './images/random_album.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'shuffle', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'shuffle'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'repeating', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'repeating'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Repeating', array('Activate/Deactivate repeating in Spotify', 'alt' => '', 'cmd' => $cmd, 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/repeating.png', 'yes', '');

        $osx_version = exec('sw_vers -productVersion');
        if (version_compare($osx_version, '10,14', '<')) {
            $w->result('SpotifyMiniPlayer_' . 'share', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'share'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Share current track using Mac OS X Sharing ', array('This will open the Mac OS X Sharing for the current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/share.png', 'yes', null, '');
        }

        $w->result('SpotifyMiniPlayer_' . 'reset_playlist_number_times_played', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'reset_playlist_number_times_played'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Reset number of times played for playlists', 'This will reset playlists all times played counters to 0', './images/settings.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'web_search', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'web_search'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Do a web search for current track or artist on Youtube, Facebook, etc.. ', array('You will be prompted to choose the web service you want to use', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/youtube.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'play_liked_songs', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'play_liked_songs'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, '' /* playlist_name */, '', /* playlist_artwork_path */
        )), '♥️ Play your Liked Songs', 'This will play your liked songs', './images/star.png', 'yes', null, '');

        if ($update_in_progress == false) {
            $w->result('SpotifyMiniPlayer_' . 'refresh_library', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'refresh_library'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Refresh your library', array('Do this when your library has changed (outside the scope of this workflow)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/update.png', 'yes', null, '');
        }

        if ($update_in_progress == false) {
            $w->result('SpotifyMiniPlayer_' . 'current_artist_radio', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'current_artist_radio'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Create artist radio playlist for current artist', 'Create artist radio playlist', './images/radio_artist.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'current_track_radio', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'current_track_radio'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Create song radio playlist for current track', 'Create song radio playlist', './images/radio_song.png', 'yes', '');

            if ($is_alfred_playlist_active == true) {
                $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'add_current_track'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
            }
            else {
                $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'add_current_track'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
            }
            $w->result('SpotifyMiniPlayer_' . 'add_current_track_to', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'add_current_track_to'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'remove_current_track_from', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'remove_current_track_from'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Remove current track from...', 'Current track will be removed from Your Music or a playlist of your choice', './images/remove_from.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'remove_current_track_from_alfred_playlist_or_your_music', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'remove_current_track'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Remove current track from Alfred Playlist or Your Music', 'Current track will be removed from your Alfred Playlist or Your Music', './images/remove.png', 'yes', '');
        }

        $w->result('SpotifyMiniPlayer_' . 'mute', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'mute'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Mute/Unmute Spotify Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'volume_down', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'volume_down'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Volume Down', 'Decrease Spotify Volume', './images/volume_down.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'set_volume', '', 'Set the volume', array('Set the volume level from 0 to 100%', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/volume_up.png', 'no', null, 'Settings▹SetVolume▹');

        $w->result('SpotifyMiniPlayer_' . 'volume_up', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'volume_up'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Volume Up', 'Increase Spotify Volume', './images/volume_up.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'volmax', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'volmax'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Set Spotify Volume to Maximum', 'Set the Spotify volume to maximum', './images/volmax.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'volmid', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'volmid'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Set Spotify Volume to 50%', 'Set the Spotify volume to 50%', './images/volmid.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'output_audio', serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'output_audio'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Choose audio output device', 'Output audio devices', './images/speaker.png', 'yes', '');
    }
    else {

        // Search commands for fast access
        if (strpos(strtolower('share'), strtolower($query)) !== false) {
            $osx_version = exec('sw_vers -productVersion');
            if (version_compare($osx_version, '10,14', '<')) {
                $w->result('SpotifyMiniPlayer_' . 'share', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'share'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Share current track using Mac OS X Sharing ', array('This will open the Mac OS X Sharing for the current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/share.png', 'yes', null, '');
            }
        }
        if (strpos(strtolower('web search'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'web_search', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'web_search'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Do a web search for current track or artist on Youtube, Facebook, etc.. ', array('You will be prompted to choose the web service you want to use', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/youtube.png', 'yes', null, '');
        }
        if (strpos(strtolower('debug'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'debug', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'open_debug_tools'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Open debug tools', array('This is how you can access information required for further troubleshooting', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/debug.png', 'yes', null, '');
        }
        if (strpos(strtolower('output'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'output_audio', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'output_audio'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Choose audio output device', 'Output audio devices', './images/speaker.png', 'yes', '');
        }
        if (strpos(strtolower('next'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'next', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'next'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');
        }
        if (strpos(strtolower('previous'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'previous', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'previous'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');
        }
        if (strpos(strtolower('reset'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'reset_playlist_number_times_played', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'reset_playlist_number_times_played'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Reset number of times played for playlists', 'This will reset playlists all times played counters to 0', './images/settings.png', 'yes', '');
        }
        if (strpos(strtolower('lyrics'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'lyrics', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'lyrics'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Get Lyrics for current track', array('Get current track lyrics', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'yes', '');
        }

        if (strpos(strtolower('query'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'lookup_current_artist', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'lookup_current_artist'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Lookup Current Artist online', array(getenv('emoji_online').' Query all albums/tracks from current artist online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_artist.png', 'yes', '');
        }
        if (strpos(strtolower('play'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'play', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'playpause', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'playpause'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'play_current_artist', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play_current_artist'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');

            $w->result('SpotifyMiniPlayer_' . 'follow_current_artist', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'follow_current_artist'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Follow current artist', 'Follow the current artist', './images/follow.png', 'yes', null, '');

            $w->result('SpotifyMiniPlayer_' . 'unfollow_current_artist', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'unfollow_current_artist'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Unfollow current artist', 'Unfollow the current artist', './images/follow.png', 'yes', null, '');

            $w->result('SpotifyMiniPlayer_' . 'play_current_album', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play_current_album'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');
            $w->result('SpotifyMiniPlayer_' . 'play_liked_songs', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play_liked_songs'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, '' /* playlist_name */, '', /* playlist_artwork_path */
            )), '♥️ Play your Liked Songs', 'This will play your liked songs', './images/star.png', 'yes', null, '');
        }
        if (strpos(strtolower('pause'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'pause', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'pause'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'playpause', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'playpause'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');
        }

        if (strpos(strtolower('current'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'current', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'current'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');
        }
        if (strpos(strtolower('random'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'random', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'random'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Random Track', 'Play random track', './images/random.png', 'yes', '');

            $w->result('SpotifyMiniPlayer_' . 'random_album', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'random_album'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Random Album', 'Play random album', './images/random_album.png', 'yes', '');
        }
        if (strpos(strtolower('shuffle'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'shuffle', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'shuffle'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');
        }
        if (strpos(strtolower('repeating'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'repeating', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'repeating'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Repeating', array('Activate/Deactivate repeating in Spotify', 'alt' => '', 'cmd' => $cmd, 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/repeating.png', 'yes', '');
        }
        if (strpos(strtolower('refresh'), strtolower($query)) !== false) {
            if ($update_in_progress == false) {
                $w->result('SpotifyMiniPlayer_' . 'refresh_library', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'refresh_library'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Refresh your library', array('Do this when your library has changed (outside the scope of this workflow)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/update.png', 'yes', null, '');
            }
        }
        if (strpos(strtolower('like'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'play_liked_songs', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play_liked_songs'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, '' /* playlist_name */, '', /* playlist_artwork_path */
            )), '♥️ Play your Liked Songs', 'This will play your liked songs', './images/star.png', 'yes', null, '');
        }
        if (strpos(strtolower('update'), strtolower($query)) !== false) {
            if ($update_in_progress == false) {
                $w->result('SpotifyMiniPlayer_' . 'refresh_library', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'refresh_library'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Refresh your library', array('Do this when your library has changed (outside the scope of this workflow)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/update.png', 'yes', null, '');
            }
        }
        if ($update_in_progress == false) {
            if (strpos(strtolower('add'), strtolower($query)) !== false) {
                if ($is_alfred_playlist_active == true) {
                    $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'add_current_track'
                    /* other_action */,

                    ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
                }
                else {
                    $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'add_current_track'
                    /* other_action */,

                    ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
                }
                $w->result('SpotifyMiniPlayer_' . 'add_current_track_to', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'add_current_track_to'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');
            }
            if (strpos(strtolower('remove'), strtolower($query)) !== false) {
                $w->result('SpotifyMiniPlayer_' . 'remove_current_track_from', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'remove_current_track_from'
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Remove current track from...', 'Current track will be removed from Your Music or a playlist of your choice', './images/remove_from.png', 'yes', '');

                $w->result('SpotifyMiniPlayer_' . 'remove_current_track_from_alfred_playlist_or_your_music', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'remove_current_track'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Remove current track from Alfred Playlist or Your Music', 'Current track will be removed from your Alfred Playlist or Your Music', './images/remove.png', 'yes', '');

            }
            if (strpos(strtolower('radio'), strtolower($query)) !== false) {
                $w->result('SpotifyMiniPlayer_' . 'current_artist_radio', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'current_artist_radio'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Create artist radio playlist for current artist', 'Create artist radio playlist', './images/radio_artist.png', 'yes', '');

                $w->result('SpotifyMiniPlayer_' . 'current_track_radio', serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'current_track_radio'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Create song radio playlist for current track', 'Create song radio playlist', './images/radio_song.png', 'yes', '');
            }
        }
        if (strpos(strtolower('mute'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'mute', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'mute'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Mute/Unmute Spotify Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');
        }
        if (strpos(strtolower('volume_down'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'volume_down', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'volume_down'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Volume Down', 'Decrease Spotify Volume', './images/volume_down.png', 'yes', '');
        }
        if (strpos(strtolower('volume_set'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'set_volume', '', 'Set the volume', array('Set the volume level from 0 to 100%', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/volume_up.png', 'no', null, 'Settings▹SetVolume▹');
        }
        if (strpos(strtolower('volume_up'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'volume_up', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'volume_up'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Volume Up', 'Increase Spotify Volume', './images/volume_up.png', 'yes', '');
        }

        if (strpos(strtolower('volmax'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'volmax', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'volmax'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Set Spotify Volume to Maximum', 'Set the Spotify volume to maximum', './images/volmax.png', 'yes', '');
        }

        if (strpos(strtolower('volmid'), strtolower($query)) !== false) {
            $w->result('SpotifyMiniPlayer_' . 'volmid', serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'volmid'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Set Spotify Volume to 50%', 'Set the Spotify volume to 50%', './images/volmid.png', 'yes', '');
        }
    }
}

