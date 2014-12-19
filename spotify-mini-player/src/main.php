<?php

// Turn off all error reporting
//error_reporting(0);

require './src/functions.php';

//$begin_time = computeTime();

// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$query = escapeQuery($argv[1]);
// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
$query = iconv('UTF-8-MAC', 'UTF-8', $query);

//
// check for library update in progress
$update_in_progress = false;
if (file_exists($w->data() . '/update_library_in_progress')) {
    $in_progress_data = $w->read('update_library_in_progress');
    $update_library_in_progress_words = explode('▹', $in_progress_data);

    $elapsed_time = time() - $update_library_in_progress_words[3];
    $update_in_progress = true;
    if (!file_exists($w->data() . '/library_old.db')) {

        if (startsWith($update_library_in_progress_words[0], 'Init')) {
            if
            ($elapsed_time < 300
            ) {
                $w->result(null, $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(0), 'Waiting for Spotify servers to return required data', './images/update_in_progress.png', 'no', null, '');
            } else {
                $w->result(null, '', 'There is a problem, the initialization phase last more than 5 minutes', 'Choose kill update library below', './images/warning.png', 'no', null, '');
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'kill_update' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Kill update library', 'This will stop the library update', './images/kill.png', 'yes', '');
            }
        } else {
            if ($update_library_in_progress_words[0] == 'Refresh Library') {
                $type = 'playlists';
            } else if ($update_library_in_progress_words[0] == 'Artists') {
                $type = 'artists';
            } else {
                $type = 'tracks';
            }

            if ($update_library_in_progress_words[2] != 0) {
                $w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])), $update_library_in_progress_words[1] . '/' . $update_library_in_progress_words[2] . ' ' . $type . ' processed so far. Currently processing <' . $update_library_in_progress_words[4] . '>', './images/update_in_progress.png', 'no', null, '');
            } else {
                $w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(0), 'No ' . $type . ' processed so far', './images/update_in_progress.png', 'no', null, '');
            }
        }

        echo $w->toxml();
        return;
    }
}

//
// check for download artworks in progress
$download_artworks_in_progress = false;
if (file_exists($w->data() . '/download_artworks_in_progress')) {
    $in_progress_data = $w->read('download_artworks_in_progress');
    $download_artworks_in_progress_words = explode('▹', $in_progress_data);

    $elapsed_time = time() - $download_artworks_in_progress_words[3];
    $download_artworks_in_progress = true;


    if ($download_artworks_in_progress_words[2] != 0) {
        $w->result(null, $w->data() . '/download_artworks_in_progress', $download_artworks_in_progress_words[0] . ' in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(intval($download_artworks_in_progress_words[1]) / intval($download_artworks_in_progress_words[2])), $download_artworks_in_progress_words[1] . '/' . $download_artworks_in_progress_words[2] . ' artworks processed so far (empty artworks can be seen until full download is complete)', './images/artworks.png', 'no', null, '');
    } else {
        $w->result(null, $w->data() . '/download_artworks_in_progress', $download_artworks_in_progress_words[0] . ' in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(0), 'No artwork processed so far (empty artworks can be seen until full download is complete)', './images/artworks.png', 'no', null, '');
    }
}

//
// Read settings from JSON
//

$settings = getSettings($w);

$all_playlists = $settings->all_playlists;
$is_spotifious_active = $settings->is_spotifious_active;
$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
$radio_number_tracks = $settings->radio_number_tracks;
$now_playing_notifications = $settings->now_playing_notifications;
$max_results = $settings->max_results;
$alfred_playlist_uri = $settings->alfred_playlist_uri;
$alfred_playlist_name = $settings->alfred_playlist_name;
$country_code = $settings->country_code;
$last_check_update_time = $settings->last_check_update_time;
$oauth_client_id = $settings->oauth_client_id;
$oauth_client_secret = $settings->oauth_client_secret;
$oauth_redirect_uri = $settings->oauth_redirect_uri;
$oauth_access_token = $settings->oauth_access_token;
$oauth_expires = $settings->oauth_expires;
$oauth_refresh_token = $settings->oauth_refresh_token;
$display_name = $settings->display_name;
$userid = $settings->userid;
$echonest_api_key = $settings->echonest_api_key;


////
// OAUTH checks
// Check oauth config : Client ID and Client Secret
if ($oauth_client_id == '' && substr_count($query, '▹') == 0) {
    if (mb_strlen($query) == 0) {
        $w->result(null, '', 'Your Application Client ID is missing', 'Get it from your Spotify Application and copy/paste it here', './images/settings.png', 'no', null, '');
        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Open▹' . 'https://developer.spotify.com/my-applications/#!/applications' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Open Spotify Application page to get required information', "This will open the Application page with your default browser", './images/spotify.png', 'yes', null, '');
    } else if (mb_strlen($query) != 32) {
        $w->result(null, '', 'The Application Client ID does not seem valid!', 'The length is not 32. Make sure to copy the Client ID from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
    } else {
        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Oauth_Client_ID▹' . rtrim(ltrim($query)) /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Application Client ID will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client ID", './images/settings.png', 'yes', null, '');
    }
    echo $w->toxml();
    return;
}

if ($oauth_client_secret == '' && substr_count($query, '▹') == 0) {
    if (mb_strlen($query) == 0) {
        $w->result(null, '', 'Your Application Client Secret is missing!', 'Get it from your Spotify Application and enter it here', './images/settings.png', 'no', null, '');
        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Open▹' . 'https://developer.spotify.com/my-applications/#!/applications' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Open Spotify Application page to get required information', "This will open the Application page with your default browser", './images/spotify.png', 'yes', null, '');
    } else if (mb_strlen($query) != 32) {
        $w->result(null, '', 'The Application Client Secret does not seem valid!', 'The length is not 32. Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
    } else if ($query == $oauth_client_id) {
        $w->result(null, '', 'The Application Client Secret entered is the same as Application Client ID, this is wrong!', 'Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
    } else {
        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Oauth_Client_SECRET▹' . rtrim(ltrim($query)) /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Application Client Secret will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client Secret", './images/settings.png', 'yes', null, '');
    }
    echo $w->toxml();
    return;
}

if ($oauth_access_token == '' && substr_count($query, '▹') == 0) {
    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'Oauth_Login' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Authenticate to Spotify", array(
        "This will start the authentication process",
        'alt' => 'Not Available',
        'cmd' => 'Not Available',
        'shift' => 'Not Available',
        'fn' => 'Not Available',
        'ctrl' => 'Not Available'), './images/settings.png', 'yes', null, '');
    echo $w->toxml();
    return;
}


// check for library DB
$dbfile = "";

if ($update_in_progress == false &&
    file_exists($w->data() . '/library.db')
) {
    $dbfile = $w->data() . '/library.db';
} else if (file_exists($w->data() . '/library_old.db')) {
    // update in progress use the old library
    if ($update_in_progress == true) {
        $dbfile = $w->data() . '/library_old.db';
    } else {
        unlink($w->data() . '/library_old.db');
    }
}
if ($dbfile == "") {
    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'update_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Create library', "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
    echo $w->toxml();
    return;
}


try {
    $db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->query("PRAGMA synchronous = OFF");
    $db->query("PRAGMA journal_mode = OFF");
    $db->query("PRAGMA temp_store = MEMORY");
    $db->query("PRAGMA count_changes = OFF");
    $db->query("PRAGMA PAGE_SIZE = 4096");
    $db->query("PRAGMA default_cache_size=700000");
    $db->query("PRAGMA cache_size=700000");
    $db->query("PRAGMA compile_options");
} catch (PDOException $e) {
    handleDbIssuePdoXml($db);
    return;
}


//
// Check for workflow update
// Do not do it if update is in progress
// since it update settings
//
if($update_in_progress == false) {
	$check_results = checkForUpdate($w, $last_check_update_time);
	if($check_results != null && is_array($check_results)) {
	    $w->result(null, '', 'New version ' . $check_results[0] . ' is available', $check_results[2], './images/info.png', 'no', null, '');
	    $w->result(null, $check_results[1], 'Please install the new version in Downloads directory', $check_results[1], 'fileicon:' . $check_results[1], 'no', '', '', 'file');

	    echo $w->toxml();
	    return;
	}
}


// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding('UTF-8');

//
// Fast access to commands
//
if (startsWith($query, ' ')) {
	$w = searchCommandsFastAccess($w,ltrim($query),$settings);
	echo $w->toxml();
	return;
}


//
if (mb_strlen($query) < 3) {

    ////////
    //
    //	MAIN MENU
    //////////////
    $getCounters = 'select * from counters';
    try {
        $stmt = $db->prepare($getCounters);

        $counters = $stmt->execute();
        $counter = $stmt->fetch();

    } catch (PDOException $e) {
        handleDbIssuePdoXml($db);
        return;
    }

    $all_tracks = $counter[0];
    $mymusic_tracks = $counter[1];
    $all_artists = $counter[2];
    $mymusic_artists = $counter[3];
    $all_albums = $counter[4];
    $mymusic_albums = $counter[5];
    $nb_playlists = $counter[6];

    if ($update_in_progress == true) {
        if (startsWith($update_library_in_progress_words[0], 'Init')) {
            $w->result(null, $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(0), 'Waiting for Spotify servers to return required data', './images/update_in_progress.png', 'no', null, '');
        } else {
            if ($update_library_in_progress_words[0] == 'Refresh Library') {
                $type = 'playlists';
            } else if ($update_library_in_progress_words[0] == 'Artists') {
                $type = 'artists';
            } else {
                $type = 'tracks';
            }

            if ($update_library_in_progress_words[2] != 0) {
                $w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])), $update_library_in_progress_words[1] . '/' . $update_library_in_progress_words[2] . ' ' . $type . ' processed so far. Currently processing <' . $update_library_in_progress_words[4] . '>', './images/update_in_progress.png', 'no', null, '');
            } else {
                $w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time,true) . ' : ' . floatToSquares(0), 'No ' . $type . ' processed so far', './images/update_in_progress.png', 'no', null, '');
            }
        }
    }
    if ($all_playlists == true) {
        $w->result(null, '', 'Search for music in "Your Music" and your ' . $nb_playlists . ' playlists', 'Begin typing at least 3 characters to start search in your ' . $all_tracks . ' tracks', './images/search.png', 'no', null, '');
    } else {
        $w->result(null, '', 'Search for music in "Your Music" only', 'Begin typing at least 3 characters to start search in your ' . $mymusic_tracks . ' tracks', './images/search_scope_yourmusic_only.png', 'no', null, '');
    }

    $w->result(null, '', 'Current Track', 'Display current track information and browse various options', './images/current_track.png', 'no', null, 'Current Track▹');

	$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lookup_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Lookup Current Artist online', array(
            '☁︎ Query all albums/tracks from current artist online..',
            'alt' => 'Not Available',
            'cmd' => 'Not Available',
            'shift' => 'Not Available',
            'fn' => 'Not Available',
            'ctrl' => 'Not Available'), './images/online_artist.png', 'yes', '');


    if ($is_alfred_playlist_active == true) {
        if
        ($alfred_playlist_name != ""
        ) {
            $title = 'Alfred Playlist ● ' . $alfred_playlist_name;
            $w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
        } else {
            $title = 'Alfred Playlist ● not set';
            $w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
        }

    }
    $w->result(null, '', 'Playlists', 'Browse by playlist' . ' (' . $nb_playlists . ' playlists)', './images/playlists.png', 'no', null, 'Playlist▹');
    $w->result(null, '', 'Your Music', 'Browse Your Music' . ' (' . $mymusic_tracks . ' tracks ● ' . $mymusic_albums . '  albums ● ' . $mymusic_artists . ' artists)', './images/yourmusic.png', 'no', null, 'Your Music▹');
    if ($all_playlists == true) {
        $w->result(null, '', 'Artists', 'Browse by artist' . ' (' . $all_artists . ' artists)', './images/artists.png', 'no', null, 'Artist▹');
        $w->result(null, '', 'Albums', 'Browse by album' . ' (' . $all_albums . ' albums)', './images/albums.png', 'no', null, 'Album▹');
    } else {
        $w->result(null, '', 'Artists in "Your Music"', 'Browse by artist' . ' (' . $mymusic_artists . ' artists)', './images/artists.png', 'no', null, 'Artist▹');
        $w->result(null, '', 'Albums in "Your Music"', 'Browse by album' . ' (' . $mymusic_albums . ' albums)', './images/albums.png', 'no', null, 'Album▹');
    }

    //$w->result(null, '', 'Charts', 'Browse charts', './images/numbers.png', 'no', null, 'Charts▹');

    if ($is_spotifious_active == true) {
        $spotifious_state = 'enabled';
    } else {
        $spotifious_state = 'disabled';
    }
    if ($is_alfred_playlist_active == true) {
        $alfred_playlist_state = 'Alfred Playlist';
    } else {
        $alfred_playlist_state = 'Your Music';
    }
    // do not allow settings if update in progress
	if($update_in_progress == true) {
		$w->result(null, '', 'Settings (not available)', 'Settings cannot be changed while an update is in progress', './images/warning.png', 'no', null, '');
	} else {
        if ($all_playlists == true) {
            $w->result(null, '', 'Settings', 'Search scope=<All>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Controlling <' . $alfred_playlist_state . '>, Radio tracks=<' . $radio_number_tracks . '>', './images/settings.png', 'no', null, 'Settings▹');
        } else {
            $w->result(null, '', 'Settings', 'Search scope=<Your Music>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Controlling <' . $alfred_playlist_state . '>, Radio tracks=<' . $radio_number_tracks . '>', './images/settings.png', 'no', null, 'Settings▹');
        }
	}
} else {
    ////////////
    //
    // NO DELIMITER
    //
    ////////////
    if (substr_count($query, '▹') == 0) {
        //
        // Search categories for fast access
        //
        if (strpos(strtolower('playlists'), strtolower($query)) !== false) {
            $w->result(null, '', 'Playlists', 'Browse by playlist', './images/playlists.png', 'no', null, 'Playlist▹');
        } else if (strpos(strtolower('albums'), strtolower($query)) !== false) {
            $w->result(null, '', 'Albums', 'Browse by album', './images/albums.png', 'no', null, 'Album▹');
        } else if (strpos(strtolower('charts'), strtolower($query)) !== false) {
            $w->result(null, '', 'Charts', 'Browse charts', './images/numbers.png', 'no', null, 'Charts▹');
        } else if (strpos(strtolower('new releases'), strtolower($query)) !== false) {
            $w->result(null, '', 'New Releases', 'Browse new album releases', './images/new_releases.png', 'no', null, 'New Releases▹');
        } else if (strpos(strtolower('artists'), strtolower($query)) !== false) {
            $w->result(null, '', 'Artists', 'Browse by artist', './images/artists.png', 'no', null, 'Artist▹');
        } else if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
            $w->result(null, '', 'Alfred Playlist (currently set to <' . $alfred_playlist_name . '>)', 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
        } else if (strpos(strtolower('settings'), strtolower($query)) !== false) {
            $w->result(null, '', 'Settings', 'Go to settings', './images/settings.png', 'no', null, 'Settings▹');
        } else if (strpos(strtolower('featured'), strtolower($query)) !== false) {
            $w->result(null, '', 'Featured Playlist', 'Browse the current featured playlists', './images/star.png', 'no', null, 'Featured Playlist▹');
        } else if (strpos(strtolower('yourmusic'), strtolower($query)) !== false) {
            $w->result(null, '', 'Your Music', 'Browse Your Music', './images/tracks.png', 'no', null, 'Your Music▹');
        } else if (strpos(strtolower('current track'), strtolower($query)) !== false) {
            $w->result(null, '', 'Current Track', 'Display current track information and browse various options', './images/tracks.png', 'no', null, 'Current Track▹');
        }


		$w = searchCommandsFastAccess($w,$query,$setting);

        //
        // Search in Playlists
        //
        $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where name like :query";

        try {
            $stmt = $db->prepare($getPlaylists);
            $stmt->bindValue(':query', '%' . $query . '%');
            $playlists = $stmt->execute();

        } catch (PDOException $e) {
            handleDbIssuePdoXml($db);
            return;
        }

        while ($playlist = $stmt->fetch()) {
            $added = ' ';
            if (startswith($playlist[1], 'Artist radio for')) {
                $added = '📻 ';
            }
            $w->result(null, '', "🎵" . $added . ucfirst($playlist[1]) . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
        }

        //
        // Search artists
        //
        if ($all_playlists == false) {
            $getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where  mymusic=1 and artist_name like :artist_name limit " . $max_results;
        } else {
            $getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where  artist_name like :artist_name limit " . $max_results;
        }

        try {
            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':artist_name', '%' . $query . '%');

            $tracks = $stmt->execute();

        } catch (PDOException $e) {
            handleDbIssuePdoXml($db);
            return;
        }

        while ($track = $stmt->fetch()) {

            if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
                $w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[2], 'no', null, "Artist▹" . $track[1] . '∙' . $track[0] . "▹");
            }
        }

        //
        // Search everything
        //
        if ($all_playlists == false) {
            $getTracks = "select * from tracks where  mymusic=1 and (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;
        } else {
            $getTracks = "select * from tracks where  (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;
        }

        try {
            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':query', '%' . $query . '%');

            $tracks = $stmt->execute();

        } catch (PDOException $e) {
            handleDbIssuePdoXml($db);
            return;
        }

        $noresult = true;
        while ($track = $stmt->fetch()) {

            if
            ($noresult == true
            ) {
                $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
            }
            $noresult = false;
            $subtitle = $track[6];

            if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

                $playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

                if ($is_alfred_playlist_active == true) {
                    $arrayresult = array(
                        $track[16] . " ● " . $subtitle . $playlistsfortrack,
                        'alt' => 'Play album ' . $track[6] . ' in Spotify',
                        'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                        'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                        'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                        'ctrl' => 'Search artist ' . $track[7] . ' online');
                } else {
                    $arrayresult = array(
                        $track[16] . " ● " . $subtitle . $playlistsfortrack,
                        'alt' => 'Play album ' . $track[6] . ' in Spotify',
                        'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                        'fn' => 'Add track ' . $track[5] . ' to Your Music',
                        'shift' => 'Add album ' . $track[6] . ' to Your Music',
                        'ctrl' => 'Search artist ' . $track[7] . ' online');
                }

                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', array('copy' => ucfirst($track[7]) . " ● " . $track[5], 'largetype' => ucfirst($track[7]) . " ● " . $track[5]), '');

            }
        }

        if($noresult) {
            $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
        }

        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "spotify:search:' . $query . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $query . " in Spotify", array(
            'This will start a new search in Spotify',
            'alt' => 'Not Available',
            'cmd' => 'Not Available',
            'shift' => 'Not Available',
            'fn' => 'Not Available',
            'ctrl' => 'Not Available'), './images/spotify.png', 'yes', null, '');

        if ($is_spotifious_active == true) {
            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, $query /* query */, '' /* other_settings*/, 'search_in_spotifious' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $query . " with Spotifious", array(
                'Spotifious workflow must be installed and script filter set with <spotifious>',
                'alt' => 'Not Available',
                'cmd' => 'Not Available',
                'shift' => 'Not Available',
                'fn' => 'Not Available',
                'ctrl' => 'Not Available'), './images/spotifious.png', 'yes', null, '');
        }
    }
    ////////////
    //
    // FIRST DELIMITER
    //
    ////////////
    elseif (substr_count($query, '▹') == 1) {
        $words = explode('▹', $query);
        $kind = $words[0];

        if ($kind == "Playlist") {
            //
            // Search playlists
            //
            $theplaylist = $words[1];
            try {
                if (mb_strlen($theplaylist) < 3) {
                    $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists";
                    $stmt = $db->prepare($getPlaylists);
                } else {
                    $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where (name like :query or author like :query)";
                    $stmt = $db->prepare($getPlaylists);
                    $stmt->bindValue(':query', '%' . $theplaylist . '%');
                }

                $playlists = $stmt->execute();
            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            $noresult = true;
            if ($query == "Playlist▹Artist radio") {
                while ($playlist = $stmt->fetch()) {

                    $noresult = false;

                    if (startswith($playlist[1], 'Artist radio for')) {
                        $w->result(null, '', "🎵 " . ucfirst($playlist[1]) . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
                    }
                }
            } elseif ($query == "Playlist▹Song radio") {
                while ($playlist = $stmt->fetch()) {

                    $noresult = false;

                    if (startswith($playlist[1], 'Song radio for')) {
                        $w->result(null, '', "🎵 " . ucfirst($playlist[1]) . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
                    }
                }
            } else {
                $savedPlaylists = array();
                $nb_artist_radio_playlist = 0;
                $nb_song_radio_playlist = 0;
                while ($playlist = $stmt->fetch()) {

                    if (startswith($playlist[1], 'Artist radio for')) {
                        $nb_artist_radio_playlist++;
                        continue;
                    }

                    if (startswith($playlist[1], 'Song radio for')) {
                        $nb_song_radio_playlist++;
                        continue;
                    }

                    $savedPlaylists[] = $playlist;
                }

                if (mb_strlen($theplaylist) < 3) {
                    if ($nb_artist_radio_playlist > 0) {
                        $w->result(null, '', "Browse your artist radio playlists (" . $nb_artist_radio_playlist . " playlists)", "Display all your artist radio playlists", './images/radio_artist.png', 'no', null, "Playlist▹Artist radio");
                    }
                    if ($nb_song_radio_playlist > 0) {
                        $w->result(null, '', "Browse your song radio playlists (" . $nb_song_radio_playlist . " playlists)", "Display all your song radio playlists", './images/radio_song.png', 'no', null, "Playlist▹Song radio");
                    }
                    $w->result(null, '', 'Featured Playlists', 'Browse the current featured playlists', './images/star.png', 'no', null, 'Featured Playlist▹');
                }

                foreach ($savedPlaylists as $playlist) {
                    $noresult = false;
                    $added = ' ';
                    $w->result(null, '', "🎵" . $added . ucfirst($playlist[1]) . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
                }
            }

            if
            ($noresult
            ) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
            }

        } // search by Playlist end
        elseif ($kind == "Alfred Playlist") {
            //
            // Alfred Playlist
            //
            $playlist = $words[1];

            $r = explode(':', $alfred_playlist_uri);

            $w->result(null, '', "Browse your Alfred playlist (" . $alfred_playlist_name . " by " . $r[2] . ")", "You can change the playlist by selecting Change your Alfred playlist below", getPlaylistArtwork($w,  $alfred_playlist_uri, false), 'no', null, 'Playlist▹' . $alfred_playlist_uri . '▹');

			if($update_in_progress == false) {
	            $w->result(null, '', "Change your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');

	            if
	            (strtolower($r[3]) != strtolower('Starred')
	            ) {
	                $w->result(null, '', "Clear your Alfred Playlist", "This will remove all the tracks in your current Alfred Playlist", './images/uncheck.png', 'no', null, 'Alfred Playlist▹Confirm Clear Alfred Playlist▹');
	            }
	        }
        } //  Alfred Playlist end
        elseif ($kind == "Artist") {
            //
            // Search artists
            //
            $artist = $words[1];

            try {
                if (mb_strlen($artist) < 3) {
                    if ($all_playlists == false) {
                        $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where  mymusic=1 group by artist_name" . " limit " . $max_results;
                    } else {
                        $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks  group by artist_name" . " limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                } else {
                    if ($all_playlists == false) {
                        $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where  mymusic=1 and artist_name like :query limit " . $max_results;
                    } else {
                        $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where  artist_name like :query limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':query', '%' . $artist . '%');
                }

                $tracks = $stmt->execute();

            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            // display all artists
            $noresult = true;
            while ($track = $stmt->fetch()) {
                $noresult = false;
				$nb_artist_tracks = getNumberOfTracksForArtist($db, $track[2]);
                if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)') == false) {
                    $w->result(null, '', "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)', "Browse this artist", $track[1], 'no', null, "Artist▹" . $track[2] . '∙' . $track[0] . "▹");
                }
            }

            if
            ($noresult
            ) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
            }

        } // search by Artist end
        elseif ($kind == "Album") {

	        // New Releases menu
	        $w->result(null, '', 'New Releases', 'Browse new album releases', './images/new_releases.png', 'no', null, 'New Releases▹');

            //
            // Search albums
            //
            $album = $words[1];
            try {
                if (mb_strlen($album) < 3) {
                    if ($all_playlists == false) {
                        $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where  mymusic=1 group by album_name" . " limit " . $max_results;
                    } else {
                        $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks  group by album_name" . " limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                } else {
                    if ($all_playlists == false) {
                        $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where  mymusic=1 and album_name like :query limit " . $max_results;
                    } else {
                        $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where  album_name like :query limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':query', '%' . $album . '%');
                }

                $tracks = $stmt->execute();

            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            // display all albums
            $noresult = true;
            while ($track = $stmt->fetch()) {
                $noresult = false;
				$nb_album_tracks = getNumberOfTracksForAlbum($db, $track[3]);
                if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)') == false) {
                    $w->result(null, '', ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)', $track[4] . ' by ' . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . "▹");
                }
            }

            if
            ($noresult
            ) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
            }
        } // search by Album end
        elseif ($kind == "Featured Playlist") {

            $w->result(null, '', getCountryName($country_code), 'Browse the current featured playlists in ' . getCountryName($country_code), './images/star.png', 'no', null, 'Featured Playlist▹' . $country_code . '▹');

            if ($country_code != 'US') {
                $w->result(null, '', getCountryName('US'), 'Browse the current featured playlists in ' . getCountryName('US'), './images/star.png', 'no', null, 'Featured Playlist▹US▹');
            }

            if ($country_code != 'GB') {
                $w->result(null, '', getCountryName('GB'), 'Browse the current featured playlists in ' . getCountryName('GB'), './images/star.png', 'no', null, 'Featured Playlist▹GB▹');
            }

            $w->result(null, '', 'Choose Another country', 'Browse the current featured playlists in another country of your choice', './images/star.png', 'no', null, 'Featured Playlist▹Choose a Country▹');

        } // Featured Playlist end
        elseif ($kind == "Charts") {

            $w->result(null, '', getCountryName($country_code), 'Browse the current charts in ' . getCountryName($country_code), './images/numbers.png', 'no', null, 'Charts▹' . $country_code . '▹');

            if ($country_code != 'US') {
                $w->result(null, '', getCountryName('US'), 'Browse the current charts in ' . getCountryName('US'), './images/numbers.png', 'no', null, 'Charts▹US▹');
            }

            if ($country_code != 'GB') {
                $w->result(null, '', getCountryName('GB'), 'Browse the current charts in ' . getCountryName('GB'), './images/numbers.png', 'no', null, 'Charts▹GB▹');
            }

            $w->result(null, '', 'Choose Another country', 'Browse the current charts in another country of your choice', './images/numbers.png', 'no', null, 'Charts▹Choose a Country▹');

        } // Charts end
        elseif ($kind == "New Releases") {

            $w->result(null, '', getCountryName($country_code), 'Browse the new album releases in ' . getCountryName($country_code), './images/new_releases.png', 'no', null, 'New Releases▹' . $country_code . '▹');

            if ($country_code != 'US') {
                $w->result(null, '', getCountryName('US'), 'Browse the new album releases in ' . getCountryName('US'), './images/new_releases.png', 'no', null, 'New Releases▹US▹');
            }

            if ($country_code != 'GB') {
                $w->result(null, '', getCountryName('GB'), 'Browse the new album releases in ' . getCountryName('GB'), './images/new_releases.png', 'no', null, 'New Releases▹GB▹');
            }

            $w->result(null, '', 'Choose Another country', 'Browse the new album releases in another country of your choice', './images/new_releases.png', 'no', null, 'New Releases▹Choose a Country▹');

        } // New Releases end
        elseif ($kind == "Current Track") {
            // get info on current song
            $command_output = exec("./src/track_info.ksh 2>&1");

            if (substr_count($command_output, '▹') > 0) {
                $results = explode('▹', $command_output);

                if($results[1] == '' || $results[2] == '') {
					$w->result(null, 'help', "Current track is not valid: Artist or Album name is missing", "Fill missing information in Spotify and retry again", './images/warning.png', 'no', null, '');
					echo $w->toxml();
					return;

                }

                $currentArtistArtwork = getArtistArtwork($w,  $results[1], false);
                $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                if ($is_alfred_playlist_active == true) {
                    $arrayresult = array(
                        $subtitle,
                        'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify',
                        'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify',
                        'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                        'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                        'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online');
                } else {
                    $arrayresult = array(
                        $subtitle,
                        'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify',
                        'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify',
                        'fn' => 'Add track ' . escapeQuery($results[0]) . ' to Your Music',
                        'shift' => 'Add album ' . escapeQuery($results[2]) . ' to Your Music',
                        'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online');
                }

                if ($results[3] == "playing") {
                    $w->result(null, serialize(array($results[4] /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'pause' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), " " . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]) . " ● " . floatToStars($results[6]/100) .' (' . beautifyTime($results[5]) . ')', $arrayresult, ($results[3] == "playing") ? './images/pause.png' : './images/play.png', 'yes', null, '');

                } else {
                    $w->result(null, serialize(array($results[4] /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), " " . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]) . " ● " . floatToStars($results[6]/100) .' (' . beautifyTime($results[5]) . ')', $arrayresult, ($results[3] == "playing") ? './images/pause.png' : './images/play.png', 'yes', null, '');
                }


                $getTracks = "select artist_name,artist_uri from tracks where  artist_name=:artist_name limit " . 1;

                try {
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':artist_name', escapeQuery($results[1]));
                    $tracks = $stmt->execute();

                } catch (PDOException $e) {
                    handleDbIssuePdoXml($db);
                    return;
                }

                // check if artist is in library
                $noresult = true;
                while ($track = $stmt->fetch()) {
                    $artist_uri = $track[1];
                    $noresult = false;
                }

                if ($noresult == false) {
                    $w->result(null, '', "👤 " . ucfirst(escapeQuery($results[1])), "Browse this artist", $currentArtistArtwork, 'no', null, "Artist▹" . $artist_uri . '∙' . escapeQuery($results[1]) . "▹");
                } else {
                    // artist is not in library
                    $w->result(null, '', "👤 " . ucfirst(escapeQuery($results[1])), "Browse this artist", $currentArtistArtwork, 'no', null, "Artist▹" . $results[4] . '∙' . escapeQuery($results[1]) . "▹");
                }

                // use track uri here
                $album_artwork_path = getTrackOrAlbumArtwork($w,  $results[4], false);
                $w->result(null, serialize(array($results[4] /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playalbum' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, escapeQuery($results[2]) /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "💿 " . escapeQuery($results[2]), 'Play album', $album_artwork_path, 'yes', null, '');


				$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lyrics' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Get Lyrics for track " . escapeQuery($results[0]),
                        array(
                            'This will fetch lyrics on lyrics.com',
                            'alt' => 'Not Available',
                            'cmd' => 'Not Available',
                            'shift' => 'Not Available',
                            'fn' => 'Not Available',
                            'ctrl' => 'Not Available')
                        , './images/lyrics.png', 'yes', null, '');



				if($update_in_progress == false) {
                	$w->result(null, '', 'Add track ' . escapeQuery($results[0]) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $results[4] . '∙' . escapeQuery($results[0]) . '▹');


                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'current_track_radio' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Create a Song Radio Playlist based on " . escapeQuery($results[0]),
                    array(
                        'This will create a song radio playlist with ' . $radio_number_tracks . ' tracks for the current track',
                        'alt' => 'Not Available',
                        'cmd' => 'Not Available',
                        'shift' => 'Not Available',
                        'fn' => 'Not Available',
                        'ctrl' => 'Not Available')
                    , './images/radio_song.png', 'yes', null, '');
				}

                if ($all_playlists == true) {
                    $getTracks = "select playlist_uri from tracks where  uri=:uri limit " . $max_results;

                    try {
                        $stmtgetTracks = $db->prepare($getTracks);
                        $stmtgetTracks->bindValue(':uri', $results[4]);
                        $stmtgetTracks->execute();

                    } catch (PDOException $e) {
                        handleDbIssuePdoXml($db);
                        return;
                    }

                    while ($track = $stmtgetTracks->fetch()) {

                        $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where uri=:uri";

                        try {
                            $stmtGetPlaylists = $db->prepare($getPlaylists);
                            $stmtGetPlaylists->bindValue(':uri', $track[0]);

                            $playlists = $stmtGetPlaylists->execute();

                        } catch (PDOException $e) {
                            handleDbIssuePdoXml($db);
                            return;
                        }

                        while ($playlist = $stmtGetPlaylists->fetch()) {
                            $added = ' ';
                            if (startswith($playlist[1], 'Artist radio for')) {
                                $added = '📻 ';
                            }
                            if (checkIfResultAlreadyThere($w->results(), "🎵" . $added . "In playlist " . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8]) == false) {
                                $w->result(null, '', "🎵" . $added . "In playlist " . ucfirst($playlist[1]) . " ● by " . $playlist[3], $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
                            }
                        }
                    }
                }
            }
            else {
				$w->result(null, 'help', "There is no track currently playing", "Launch a track and come back here", './images/warning.png', 'no', null, '');
            }
        } // Current Track end
        elseif ($kind == "Your Music") {
            $thequery = $words[1];

           	if (mb_strlen($thequery) < 3) {
	            $getCounters = 'select * from counters';
	            try {
	                $stmt = $db->prepare($getCounters);

	                $counters = $stmt->execute();
	                $counter = $stmt->fetch();

	            } catch (PDOException $e) {
	                handleDbIssuePdoXml($db);
	                return;
	            }

	            $all_tracks = $counter[0];
	            $mymusic_tracks = $counter[1];
	            $all_artists = $counter[2];
	            $mymusic_artists = $counter[3];
	            $all_albums = $counter[4];
	            $mymusic_albums = $counter[5];
	            $nb_playlists = $counter[6];

	            $w->result(null, '', 'Tracks', 'Browse your ' . $mymusic_tracks . ' tracks in Your Music', './images/tracks.png', 'no', null, 'Your Music▹Tracks▹');
	            $w->result(null, '', 'Albums', 'Browse your ' . $mymusic_albums . ' albums in Your Music', './images/albums.png', 'no', null, 'Your Music▹Albums▹');
	            $w->result(null, '', 'Artists', 'Browse your ' . $mymusic_artists . ' artists in Your Music', './images/artists.png', 'no', null, 'Your Music▹Artists▹');

            } else {
		        //
		        // Search artists
		        //
		        $getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where mymusic=1 and artist_name like :artist_name limit " . $max_results;

		        try {
		            $stmt = $db->prepare($getTracks);
		            $stmt->bindValue(':artist_name', '%' . $thequery . '%');

		            $tracks = $stmt->execute();

		        } catch (PDOException $e) {
		            handleDbIssuePdoXml($db);
		            return;
		        }
				$noresult=true;
		        while ($track = $stmt->fetch()) {

		            if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
			            $noresult=false;
		                $w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[2], 'no', null, "Artist▹" . $track[1] . '∙' . $track[0] . "▹");
		            }
		        }

		        //
		        // Search everything
		        //
		        $getTracks = "select * from tracks where  mymusic=1 and (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;

		        try {
		            $stmt = $db->prepare($getTracks);
		            $stmt->bindValue(':query', '%' . $thequery . '%');

		            $tracks = $stmt->execute();

		        } catch (PDOException $e) {
		            handleDbIssuePdoXml($db);
		            return;
		        }

		        while ($track = $stmt->fetch()) {

		            if
		            ($noresult == true
		            ) {
		                $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
		                $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		            }
		            $noresult = false;
		            $subtitle = $track[6];

		            if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {
		                $playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

		                if ($is_alfred_playlist_active == true) {
		                    $arrayresult = array(
		                        $track[16] . " ● " . $subtitle . $playlistsfortrack,
		                        'alt' => 'Play album ' . $track[6] . ' in Spotify',
		                        'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
		                        'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
		                        'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
		                        'ctrl' => 'Search artist ' . $track[7] . ' online');
		                } else {
		                    $arrayresult = array(
		                        $track[16] . " ● " . $subtitle . $playlistsfortrack,
		                        'alt' => 'Play album ' . $track[6] . ' in Spotify',
		                        'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
		                        'fn' => 'Add track ' . $track[5] . ' to Your Music',
		                        'shift' => 'Add album ' . $track[6] . ' to Your Music',
		                        'ctrl' => 'Search artist ' . $track[7] . ' online');
		                }

		                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', array('copy' => ucfirst($track[7]) . " ● " . $track[5], 'largetype' => ucfirst($track[7]) . " ● " . $track[5]), '');

		            }
		        }

		        if ($noresult) {
		            $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
		        }
            }
        } // Featured Your Music end
        elseif ($kind == "Online") {
            if (substr_count($query, '@') == 1) {
                //
                // Search Artist Online
                //
                $tmp = $words[1];
                $words = explode('@', $tmp);
                $artist_uri = $words[0];
                $tmp_uri = explode(':', $artist_uri);

                $artist_name = $words[1];

                $artist_artwork_path = getArtistArtwork($w,  $artist_name, false);
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playartist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, $artist_artwork_path /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "👤 " . $artist_name, 'Play artist', $artist_artwork_path, 'yes', null, '');

                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'display_biography' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Display biography', 'This will display the artist biography', './images/biography.png', 'yes', null, '');

                $w->result(null, '', 'Related Artists', 'Browse related artists', './images/related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name);
                if ($update_in_progress == false) {
                    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'radio_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Create a Radio Playlist for ' . $artist_name, 'This will create a radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
                }

				// call to web api, if it fails,
				// it displays an error in main window
				$albums = getTheArtistAlbums($w, $artist_uri, $country_code);

				$w->result(null, 'help', "Select an album below to browse it", 'singles and compilations are also displayed', './images/info.png', 'no', null, '');

				$noresult=true;
                foreach ($albums as $album) {
                    if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name) . ' (' . count($tracks->items) . ' tracks)') == false) {
						$noresult=false;
                        $genre = (count($album->genres) > 0) ? ' ● Genre: ' . implode('|', $album->genres) : '';
                        $tracks = $album->tracks;
                        $w->result(null, '', ucfirst($album->name) . ' (' . count($tracks->items) . ' tracks)', $album->album_type . " by " . $artist_name . ' ● Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w,  $album->uri, false), 'no', null, "Online▹" . $artist_uri . "@" . $artist_name . "@" . $album->uri . "@" . $album->name);
                    }
                }

		        if ($noresult) {
		            $w->result(null, 'help', "There is no album for this artist", "", './images/warning.png', 'no', null, '');
		        }

            } elseif (substr_count($query, '@') == 3) {
                //
                // Search Album Online
                //
                $tmp = $words[1];
                $words = explode('@', $tmp);
                $artist_uri = $words[0];
                $artist_name = $words[1];
                $album_uri = $words[2];
                $album_name = $words[3];

                $tmp_uri = explode(':', $album_uri);

                $json = doWebApiRequest($w, "https://api.spotify.com/v1/albums/" . $tmp_uri[2] . "/tracks");

                $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, false);
                $w->result(null, serialize(array('' /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playalbum' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "💿 " . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');

				if($update_in_progress == false) {
               		$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
				}


                $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
                foreach ($json->items as $track) {

                    if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                        $track_artwork = getTrackOrAlbumArtwork($w,  $track->uri, false);
                        if ($is_alfred_playlist_active == true) {
                            $arrayresult = array(
                                beautifyTime($track->duration_ms / 1000) . " ● " . $album_name,
                                'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
                                'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
                                'fn' => 'Add track ' . escapeQuery($track->name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                                'shift' => 'Add album ' . escapeQuery($album_name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                                'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online');
                        } else {
                            $arrayresult = array(
                                beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album_name),
                                'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
                                'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
                                'fn' => 'Add track ' . escapeQuery($track->name) . ' to Your Music',
                                'shift' => 'Add album ' . escapeQuery(album_name) . ' to Your Music',
                                'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online');
                        }
                        $w->result(null, serialize(array($track->uri /*track_uri*/, $album_uri /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_track_in_album_context' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, $track->name /* track_name */, $album_name /* album_name */, $track_artwork /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), escapeQuery(ucfirst($artist_name)) . " ● " . escapeQuery($track->name), $arrayresult, $track_artwork, 'yes', null, '');
                    }
                }
            }

        } // Online mode end
        elseif ($kind == "OnlineRelated") {
            if (substr_count($query, '@') == 1) {
                //
                // Search Related Artist Online
                //
                $tmp = $words[1];
                $words = explode('@', $tmp);
                $artist_uri = $words[0];
                $tmp_uri = explode(':', $artist_uri);

                $artist_name = $words[1];

                $json = doWebApiRequest($w, "https://api.spotify.com/v1/artists/" . trim($tmp_uri[2]) . "/related-artists");

                foreach ($json->artists as $related) {

                    $w->result(null, '', "👤 " . ucfirst($related->name), '☁︎ Query all albums/tracks from this artist online..', getArtistArtwork($w,  $related->name, false), 'no', null, "Online▹" . $related->uri . "@" . $related->name);
                }
            }
        } // end OnlineRelated
        elseif ($kind == "Settings") {
			// do not allow settings if update in progress
			if($update_in_progress == true) {
				$w->result(null, '', 'Settings (not available)', 'Settings cannot be changed while an update is in progress', './images/warning.png', 'no', null, '');

				echo $w->toxml();
		        return;
			}

	        if ($update_in_progress == false) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'refresh_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Refresh your library", "Do this when your library has changed (outside the scope of this workflow)", './images/update.png', 'yes', null, '');
	        }

	        if ($is_alfred_playlist_active == true) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'disable_alfred_playlist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Control Your Music", array(
	                "You will control Your Music (if disabled, you control Alfred Playlist)",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/yourmusic.png', 'yes', null, '');
	        } else {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'enable_alfred_playlist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Control Alfred Playlist", array(
	                "You will control the Alfred Playlist (if disabled, you control Your Music)",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/alfred_playlist.png', 'yes', null, '');
	        }

	        if ($all_playlists == true) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'disable_all_playlist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Set Search Scope to Your Music only', array(
	                'Select to search only in "Your Music"',
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/search_scope_yourmusic_only.png', 'yes', null, '');

	        } else {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'enable_all_playlist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Unset Search Scope to Your Music only', array(
	                'Select to search in your complete library ("Your Music" and all Playlists)',
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/search.png', 'yes', null, '');
	        }
	        $w->result(null, '', "Configure Max Number of Results", "Number of results displayed. (does not apply for the list of your playlists)", './images/results_numbers.png', 'no', null, 'Settings▹MaxResults▹');
	        $w->result(null, '', "Configure Number of Radio tracks", "Number of tracks to get when creating a Radio Playlist.", './images/radio_numbers.png', 'no', null, 'Settings▹RadioTracks▹');

	        if ($is_spotifious_active == true) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'disable_spotifiuous' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Disable Spotifious", array(
	                "Do not display Spotifious in default results",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/disable_spotifious.png', 'yes', null, '');
	        } else {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'enable_spotifiuous' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Enable Spotifious", array(
	                "Display Spotifious in default results",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/enable_spotifious.png', 'yes', null, '');
	        }
	        if ($now_playing_notifications == true) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'disable_now_playing_notifications' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Disable Now Playing notifications", array(
	                "Do not display notifications for current playing track",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/disable_now_playing.png', 'yes', null, '');
	        } else {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'enable_now_playing_notifications' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Enable Now Playing notifications", array(
	                "Display notifications for current playing track",
	                'alt' => 'Not Available',
	                'cmd' => 'Not Available',
	                'shift' => 'Not Available',
	                'fn' => 'Not Available',
	                'ctrl' => 'Not Available'), './images/enable_now_playing.png', 'yes', null, '');
	        }

	        if ($update_in_progress == false) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'update_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Re-Create your library from scratch', "Do this when refresh library is not working as you would expect", './images/recreate.png', 'yes', null, '');
	        }

	        $w->result(null, '', 'Check for workflow update', 'Note this is automatically done otherwise once per day', './images/check_update.png', 'no', null, 'Check for update, please wait..' . '▹');
        } // end Settings
	    elseif ($kind == "Check for update, please wait..") {
			$check_results = checkForUpdate($w,0);
			if($check_results != null && is_array($check_results)) {
			    $w->result(null, '', 'New version ' . $check_results[0] . ' is available !', $check_results[2], './images/info.png', 'no', null, '');
				$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Open▹' . $check_results[1] /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Click to open and install the new version', "This will open the new version of the Spotify Mini Player workflow", './images/alfred-workflow-icon.png', 'yes', null, '');


			} else if ($check_results == null) {
				$w->result(null, '', 'No update available', 'You are good to go!', './images/info.png', 'no', null, '');
			} else {
				$w->result(null, '', 'Error happened : ' . $check_results, 'The check for workflow update could not be done', './images/warning.png', 'no', null, '');
				if($check_results == "This release has not been downloaded from Packal") {
					$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'Open▹' . 'http://www.packal.org/workflow/spotify-mini-player' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Download workflow from Packal', "This will open the Spotify Mini Player Packal page with your default browser", './images/packal.png', 'yes', null, '');
				}

			}
		    echo $w->toxml();
		    return;

	    } // end of Check Workflow Update
    }
    ////////////
    //
    // SECOND DELIMITER
    //
    ////////////
    elseif (substr_count($query, '▹') == 2) {

        $words = explode('▹', $query);
        $kind = $words[0];
        if ($kind == "Artist") {
            //
            // display tracks for selected artists
            //
            $tmp = explode('∙', $words[1]);
            $artist_uri = $tmp[0];
            $artist_name = $tmp[1];
            $track = $words[2];

            $href = explode(':', $artist_uri);
            if ($href[1] == 'track') {

                $track_uri = $artist_uri;
                $artist_uri = getArtistUriFromTrack($w, $track_uri);
                if($artist_uri == false) {
					$w->result(null, 'help', "The artist cannot be retrieved", "", './images/warning.png', 'no', null, '');
					echo $w->toxml();
					return;
                }
            }
            if (mb_strlen($track) < 3) {
                $artist_artwork_path = getArtistArtwork($w,  $artist_name, false);
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playartist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, $artist_artwork_path /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "👤 " . $artist_name, 'Play artist', $artist_artwork_path, 'yes', null, '');
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lookup_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "👤 " . $artist_name, '☁︎ Query all albums/tracks from this artist online..', './images/online_artist.png', 'yes', null, '');


                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'display_biography' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Display biography', 'This will display the artist biography', './images/biography.png', 'yes', null, '');


                $w->result(null, '', 'Related Artists', 'Browse related artists', './images/related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name);

				if ($update_in_progress == false) {
                	$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'radio_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Create a Radio Playlist for ' . $artist_name, 'This will create a radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
                }

                if ($all_playlists == false || count($tmp) == 3) {
                    $getTracks = "select * from tracks where mymusic=1 and artist_uri=:artist_uri limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where  artist_uri=:artist_uri limit " . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':artist_uri', $artist_uri);
            } else {
                if ($all_playlists == false || count($tmp) == 3) {
                    $getTracks = "select * from tracks where mymusic=1 and (artist_uri=:artist_uri and track_name like :track)" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where  artist_uri=:artist_uri and track_name like :track limit " . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':artist_uri', $artist_uri);
                $stmt->bindValue(':track', '%' . $track . '%');
            }

            $tracks = $stmt->execute();

            $noresult = true;
            while ($track = $stmt->fetch()) {

                if($noresult == true) {
                    $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
                }
                $noresult = false;
                $subtitle = $track[6];

                if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

					$playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

                    if ($is_alfred_playlist_active == true) {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    } else {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to Your Music',
                            'shift' => 'Add album ' . $track[6] . ' to Your Music',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    }
                    $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
                }
            }

            if ($noresult) {
                if (mb_strlen($track) < 3) {
                    $w->result(null, 'help', "There is no track in your library for the artist " . escapeQuery($artist_name), "Choose one of the options above", './images/info.png', 'no', null, '');
                } else {
                    $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
                }

            }

            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "spotify:search:' . $artist_name . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $artist_name . " in Spotify", array(
                'This will start a new search in Spotify',
                'alt' => 'Not Available',
                'cmd' => 'Not Available',
                'shift' => 'Not Available',
                'fn' => 'Not Available',
                'ctrl' => 'Not Available'), './images/spotify.png', 'yes', null, '');

            if ($is_spotifious_active == true) {
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, $theartist[4] . " ▹ " . $artist_name . " ►" /* query */, '' /* other_settings*/, 'search_in_spotifious' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $artist_name . " with Spotifious", array(
                    'Spotifious workflow must be installed and script filter set with <spotifious>',
                    'alt' => 'Not Available',
                    'cmd' => 'Not Available',
                    'shift' => 'Not Available',
                    'fn' => 'Not Available',
                    'ctrl' => 'Not Available'), './images/spotifious.png', 'yes', null, '');
            }

        } // end of tracks by artist
        elseif ($kind == "Album") {
            //
            // display tracks for selected album
            //
            $tmp = explode('∙', $words[1]);
            $album_uri = $tmp[0];
            $album_name = $tmp[1];
            $track = $words[2];

            try {
                if (mb_strlen($track) < 3) {
                    if ($all_playlists == false || count($tmp) == 3) {
                        $getTracks = "select * from tracks where  mymusic=1 and album_uri=:album_uri limit " . $max_results;
                    } else {
                        $getTracks = "select * from tracks where  album_uri=:album_uri limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':album_uri', $album_uri);
                } else {
                    if ($all_playlists == false || count($tmp) == 3) {
                        $getTracks = "select * from tracks where  mymusic=1 and (album_uri=:album_uri and track_name like :track limit " . $max_results;
                    } else {
                        $getTracks = "select * from tracks where  album_uri=:album_uri and track_name like :track limit " . $max_results;
                    }
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':album_uri', $album_uri);
                    $stmt->bindValue(':track', '%' . $track . '%');
                }

                $tracks = $stmt->execute();

            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, false);
            $w->result(null, serialize(array('' /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playalbum' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "💿 " . $album_name, 'Play album', $album_artwork_path, 'yes', null, '');

			if($update_in_progress == false) {
            	$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
            }

            $noresult = true;
            while ($track = $stmt->fetch()) {

                if
                ($noresult == true
                ) {
                    $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
                }
                $noresult = false;
                $subtitle = $track[6];

                if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

					$playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

                    if ($is_alfred_playlist_active == true) {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    } else {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to Your Music',
                            'shift' => 'Add album ' . $track[6] . ' to Your Music',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    }
                    $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_track_in_album_context' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
                }
            }

            if
            ($noresult
            ) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');

                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "spotify:search:' . $album_name . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $album_name . " in Spotify", array(
                    'This will start a new search in Spotify',
                    'alt' => 'Not Available',
                    'cmd' => 'Not Available',
                    'shift' => 'Not Available',
                    'fn' => 'Not Available',
                    'ctrl' => 'Not Available'), './images/spotify.png', 'yes', null, '');
            } else {
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "spotify:search:' . $album_name . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $album_name . " in Spotify", array(
                    'This will start a new search in Spotify',
                    'alt' => 'Not Available',
                    'cmd' => 'Not Available',
                    'shift' => 'Not Available',
                    'fn' => 'Not Available',
                    'ctrl' => 'Not Available'), './images/spotify.png', 'yes', null, '');

                if ($is_spotifious_active == true) {
                    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, $album_uri . " ▹ " . $album_name . " ►"/* query */, '' /* other_settings*/, 'search_in_spotifious' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $album_name . " with Spotifious", array(
                        'Spotifious workflow must be installed and script filter set with <spotifious>',
                        'alt' => 'Not Available',
                        'cmd' => 'Not Available',
                        'shift' => 'Not Available',
                        'fn' => 'Not Available',
                        'ctrl' => 'Not Available'), './images/spotifious.png', 'yes', null, '');
                }
            }

        } // end of tracks by album
        elseif ($kind == "Playlist") {
            //
            // display tracks for selected playlist
            //
            $theplaylisturi = $words[1];
            $thetrack = $words[2];
            $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where uri=:uri";

            try {
                $stmt = $db->prepare($getPlaylists);
                $stmt->bindValue(':uri', $theplaylisturi);

                $playlists = $stmt->execute();
				$noresultplaylist = true;
                while ($playlist = $stmt->fetch()) {

	                $noresultplaylist = false;
                    if (mb_strlen($thetrack) < 3) {

                        $subtitle = "Launch Playlist";
                        if ($is_alfred_playlist_active == true &&
                            $playlist[1] != $alfred_playlist_name
                        ) {
                            $subtitle = "$subtitle ,⇧ ▹ add playlist to ♫";
                        }

                        if ($is_alfred_playlist_active == true) {
                            $arrayresult = array(
                                $subtitle,
                                'alt' => 'Not Available',
                                'cmd' => 'Not Available',
                                'shift' => 'Add playlist ' . ucfirst($playlist[1]) . ' to your Alfred Playlist',
                                'fn' => 'Not Available',
                                'ctrl' => 'Not Available');
                        } else {
                            $arrayresult = array(
                                $subtitle,
                                'alt' => 'Not Available',
                                'cmd' => 'Not Available',
                                'shift' => 'Add playlist ' . ucfirst($playlist[1]) . ' to Your Music',
                                'fn' => 'Not Available',
                                'ctrl' => 'Not Available');
                        }
                        $added = ' ';
                        if (startswith($playlist[1], 'Artist radio for')) {
                            $added = '📻 ';
                        }
                        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, $playlist[0] /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist[1] /* playlist_name */, $playlist[5] /* playlist_artwork_path */, $alfred_playlist_name /* alfred_playlist_name */)), "🎵" . $added . ucfirst($playlist[1]) . " (" . $playlist[7] . " tracks), by " . $playlist[3], $arrayresult, $playlist[5], 'yes', null, '');

                        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "' . $playlist[0] . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Open playlist " . escapeQuery($playlist[1]) . " in Spotify", "This will open the playlist in Spotify", './images/spotify.png', 'yes', null, '');

						if($update_in_progress == false) {
                        	$w->result(null, '', 'Add playlist ' . escapeQuery($playlist[1]) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $playlist[0] . '∙' . escapeQuery($playlist[1]) . '▹');
                        }

                        $getTracks = "select * from tracks where  playlist_uri=:theplaylisturi limit " . $max_results;
                        $stmt = $db->prepare($getTracks);
                        $stmt->bindValue(':theplaylisturi', $theplaylisturi);
                    } else {
                        $getTracks = "select * from tracks where  playlist_uri=:theplaylisturi and (artist_name like :track or album_name like :track or track_name like :track)" . " limit " . $max_results;
                        $stmt = $db->prepare($getTracks);
                        $stmt->bindValue(':theplaylisturi', $theplaylisturi);
                        $stmt->bindValue(':track', '%' . $thetrack . '%');
                    }

                    $tracks = $stmt->execute();

                    $noresult = true;
                    while ($track = $stmt->fetch()) {

                        if
                        ($noresult == true
                        ) {
                            $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                            $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                            $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
                        }
                        $noresult = false;
                        $subtitle = $track[6];

                        if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {
							$playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

                            if ($is_alfred_playlist_active == true) {
                                $arrayresult = array(
                                    $track[16] . " ● " . $subtitle . $playlistsfortrack,
                                    'alt' => 'Play album ' . $track[6] . ' in Spotify',
                                    'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                                    'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                                    'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                                    'ctrl' => 'Search artist ' . $track[7] . ' online');
                            } else {
                                $arrayresult = array(
                                    $track[16] . " ● " . $subtitle . $playlistsfortrack,
                                    'alt' => 'Play album ' . $track[6] . ' in Spotify',
                                    'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                                    'fn' => 'Add track ' . $track[5] . ' to Your Music',
                                    'shift' => 'Add album ' . $track[6] . ' to Your Music',
                                    'ctrl' => 'Search artist ' . $track[7] . ' online');
                            }
                            $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, $theplaylisturi /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');

                        }
                    }

                    if
                    ($noresult
                    ) {
                        $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');

                    }
                }

				// can happen only with Alfred Playlist deleted
		        if($noresultplaylist) {
		            $w->result(null, 'help', "It seems your Alfred Playlist was deleted", "Choose option below to change it", './images/warning.png', 'no', null, '');
					$w->result(null, '', "Change your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
		        }
            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }
        } // end of tracks by Playlist
        elseif ($kind == "Your Music" && $words[1] == "Tracks") {
            //
            // display tracks for Your Music
            //
            $thetrack = $words[2];

            if (mb_strlen($thetrack) < 3) {
                $getTracks = "select * from tracks where mymusic=1 limit " . $max_results;
                $stmt = $db->prepare($getTracks);
            } else {
                $getTracks = "select * from tracks where mymusic=1 and (artist_name like :track or album_name like :track or track_name like :track)" . " limit " . $max_results;
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':track', '%' . $thetrack . '%');
            }

            $tracks = $stmt->execute();

            $noresult = true;
            while ($track = $stmt->fetch()) {

                if
                ($noresult == true
                ) {
                    $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
                }
                $noresult = false;
                $subtitle = $track[6];

                if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

                   	$playlistsfortrack = getPlaylistsForTrack($db, $track[2]);

                    if ($is_alfred_playlist_active == true) {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    } else {
                        $arrayresult = array(
                            $track[16] . " ● " . $subtitle . $playlistsfortrack,
                            'alt' => 'Play album ' . $track[6] . ' in Spotify',
                            'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
                            'fn' => 'Add track ' . $track[5] . ' to Your Music',
                            'shift' => 'Add album ' . $track[6] . ' to Your Music',
                            'ctrl' => 'Search artist ' . $track[7] . ' online');
                    }
                    $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, $theplaylisturi /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
                }
            }

            if($noresult) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');

            }

            if (mb_strlen($thetrack) > 0) {
                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, 'activate (open location "spotify:search:' . $thetrack . '")' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $thetrack . " in Spotify", array(
                    'This will start a new search in Spotify',
                    'alt' => 'Not Available',
                    'cmd' => 'Not Available',
                    'shift' => 'Not Available',
                    'fn' => 'Not Available',
                    'ctrl' => 'Not Available'), './images/spotify.png', 'yes', null, '');

                if ($is_spotifious_active == true) {
                    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, $thetrack /* query */, '' /* other_settings*/, 'search_in_spotifious' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Search for " . $thetrack . " with Spotifious", array(
                        'Spotifious workflow must be installed and script filter set with <spotifious>',
                        'alt' => 'Not Available',
                        'cmd' => 'Not Available',
                        'shift' => 'Not Available',
                        'fn' => 'Not Available',
                        'ctrl' => 'Not Available'), './images/spotifious.png', 'yes', null, '');
                }
            }
        } // end of Your Music▹Tracks▹
        elseif ($kind == "Your Music" && $words[1] == "Albums") {

            //
            // Search albums
            //
            $album = $words[2];
            try {
                if (mb_strlen($album) < 3) {
                    $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where  mymusic=1 group by album_name" . " limit " . $max_results;
                    $stmt = $db->prepare($getTracks);
                } else {
                    $getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where  mymusic=1 and album_name like :query limit " . $max_results;
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':query', '%' . $album . '%');
                }

                $tracks = $stmt->execute();

            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            // display all albums
            $noresult = true;
            while ($track = $stmt->fetch()) {
                $noresult = false;
				$nb_album_tracks = getNumberOfTracksForAlbum($db, $track[3], true);
                if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)') == false) {
                    $w->result(null, '', ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)', $track[4] . ' by ' . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . '∙' . ' ★ ' . "▹");
                }
            }

            if($noresult) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
            }
        } // end of Your Music▹Albums▹
        elseif ($kind == "Your Music" && $words[1] == "Artists") {
            //
            // Search artists
            //
            $artist = $words[2];

            try {
                if (mb_strlen($artist) < 3) {
                    $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where mymusic=1 group by artist_name" . " limit " . $max_results;
                    $stmt = $db->prepare($getTracks);
                } else {
                    $getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where mymusic=1 and artist_name like :query limit " . $max_results;
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':query', '%' . $artist . '%');
                }

                $tracks = $stmt->execute();

            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            // display all artists
            $noresult = true;
            while ($track = $stmt->fetch()) {
                $noresult = false;
				$nb_artist_tracks = getNumberOfTracksForArtist($db, $track[2], true);
                if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)') == false) {
                    $w->result(null, '', "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)', "Browse this artist", $track[1], 'no', null, "Artist▹" . $track[2] . '∙' . $track[0] . '∙' . ' ★ ' . "▹");
                }
            }

            if($noresult) {
                $w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
            }
        } // end of Your Music▹Artists▹
        elseif ($kind == "Settings") {
            $setting_kind = $words[1];
            $the_query = $words[2];

            if ($setting_kind == "MaxResults") {
                if (mb_strlen($the_query) == 0) {
                    $w->result(null, '', "Enter the Max Results number (must be greater than 0):", "Recommendation is between 10 to 100", './images/settings.png', 'no', null, '');
                } else {
                    // max results has been set
                    if (is_numeric($the_query) == true && $the_query > 0) {
                        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'MAX_RESULTS▹' . $the_query /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Max Results will be set to <" . $the_query . ">", "Type enter to validate the Max Results", './images/settings.png', 'yes', null, '');
                    } else {
                        $w->result(null, '', "The Max Results value entered is not valid", "Please fix it", './images/warning.png', 'no', null, '');

                    }
                }
            } else if ($setting_kind == "RadioTracks") {
                if (mb_strlen($the_query) == 0) {
                    $w->result(null, '', "Enter the number of tracks to get when creating a radio Playlist:", "Must be between 1 and 100", './images/settings.png', 'no', null, '');
                } else {
                    // number radio tracks has been set
                    if (is_numeric($the_query) == true && $the_query > 0 && $the_query <= 100) {
                        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'RADIO_TRACKS▹' . $the_query /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Number of Radio Tracks will be set to <" . $the_query . ">", "Type enter to validate the Radio Tracks number", './images/settings.png', 'yes', null, '');
                    } else {
                        $w->result(null, '', "The number of tracks value entered is not valid", "Please fix it, it must be a number between 1 and 100", './images/warning.png', 'no', null, '');

                    }
                }
            }
        } // end of Settings
        elseif ($kind == "Featured Playlist") {
            $country = $words[1];

            if($country == 'Choose a Country') {
	           // list taken from http://charts.spotify.com/docs
	           $spotify_country_codes = array("ar","at","au","be","bg","ch","cl","co","cr","cz","de","dk","ec","ee","es","fi","fr","gb","gr","gt","hk","hu","ie","is","it","li","lt","lu","lv","mx","my","nl","no","nz","pe","pl","pt","se","sg","sk","sv","tr","tw","us","uy");
	           	foreach ($spotify_country_codes as $spotify_country_code) {
		            if (strtoupper($spotify_country_code) != 'US' &&
		            	strtoupper($spotify_country_code) != 'GB' &&
		            	strtoupper($spotify_country_code) != strtoupper($country_code)) {
		                $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the current featured playlists in ' . getCountryName(strtoupper($spotify_country_code)), './images/star.png', 'no', null, 'Featured Playlist▹' . strtoupper($spotify_country_code) . '▹');
		            }
		        }
            } else {
	            $api = getSpotifyWebAPI($w);
	            if ($api == false) {
	                $w->result(null, 'help', "Internal issue (getSpotifyWebAPI)", "", './images/warning.png', 'no', null, '');
	                echo $w->toxml();
	                return;
	            }

	            try {
	                $featuredPlaylists = $api->getFeaturedPlaylists(array(
	                    'country' => $country,
	                    'limit' => 0,
	                    'locale' => '',
	                    'offset' => 0,
	                    'timestamp' => ''
	                ));

	                $subtitle = "Launch Playlist";
	                if ($is_alfred_playlist_active == true) {
	                    $arrayresult = array(
	                        $subtitle,
	                        'alt' => 'Not Available',
	                        'cmd' => 'Not Available',
	                        'shift' => 'Add playlist ' . ucfirst($playlist->name) . ' to your Alfred Playlist',
	                        'fn' => 'Not Available',
	                        'ctrl' => 'Not Available');
	                } else {
	                    $arrayresult = array(
	                        $subtitle,
	                        'alt' => 'Not Available',
	                        'cmd' => 'Not Available',
	                        'shift' => 'Add playlist ' . ucfirst($playlist->name) . ' to Your Music',
	                        'fn' => 'Not Available',
	                        'ctrl' => 'Not Available');
	                }
	                $playlists = $featuredPlaylists->playlists;
	                $w->result(null, '', $featuredPlaylists->message, '' . $playlists->total . ' playlists available', './images/info.png', 'no', null, '');
	                $items = $playlists->items;
	                foreach ($items as $playlist) {
	                    $tracks = $playlist->tracks;
	                    $owner = $playlist->owner;

	                    $playlist_artwork_path = getPlaylistArtwork($w,  $playlist->uri, false);
	                    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, $playlist->uri /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist->name /* playlist_name */, $playlist_artwork_path /* playlist_artwork_path */, $alfred_playlist_name /* alfred_playlist_name */)), ucfirst($playlist->name) . " (" . $tracks->total . " tracks)", $arrayresult, $playlist_artwork_path, $playlist->uri, 'yes', null, '');
	                }

	            } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	                $w->result(null, 'help', "Exception occurred", "" . $e, './images/warning.png', 'no', null, '');
	                echo $w->toxml();
	                return;
	            }
	        }
        } // end of Featured Playlist
        elseif ($kind == "Charts") {
            $country = $words[1];

            if($country == 'Choose a Country') {
	           // list taken from http://charts.spotify.com/docs
	           $spotify_country_codes = array("ar","at","au","be","bg","ch","cl","co","cr","cz","de","dk","ec","ee","es","fi","fr","gb","gr","gt","hk","hu","ie","is","it","li","lt","lu","lv","mx","my","nl","no","nz","pe","pl","pt","se","sg","sk","sv","tr","tw","us","uy");
	           	foreach ($spotify_country_codes as $spotify_country_code) {
		            if (strtoupper($spotify_country_code) != 'US' &&
		            	strtoupper($spotify_country_code) != 'GB' &&
		            	strtoupper($spotify_country_code) != strtoupper($country_code)) {
		                $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the current charts in ' . getCountryName(strtoupper($spotify_country_code)), './images/numbers.png', 'no', null, 'Charts▹' . strtoupper($spotify_country_code) . '▹');
		            }
		        }
            } else {
	            $json = doWebApiRequest($w, "http://charts.spotify.com/api/tracks/most_streamed/" . trim($country) . "/weekly/latest");

				$nb_results=0;
	            foreach ($json->tracks as $track) {
					if($nb_results > $max_results) {
						break;
					}
	                // format is https://play.spotify.com/track/3WBLQj2qtrKYFDcC5aisLD
	                $href = explode('/', $track->track_url);
	                $track_uri = 'spotify:track:' . $href[4];

	                $href = explode('/', $track->album_url);
	                $album_uri = 'spotify:album:' . $href[4];

	                $href = explode('/', $track->artist_url);
	                $artist_uri = 'spotify:artist:' . $href[4];
	                if ($is_alfred_playlist_active == true) {
	                    $arrayresult = array(
	                        escapeQuery($track->album_name) . " ● " . $track->num_streams . ' streams',
	                        'alt' => 'Play album ' . escapeQuery($track->album_name) . ' in Spotify',
	                        'cmd' => 'Play artist ' . escapeQuery($track->artist_name) . ' in Spotify',
	                        'fn' => 'Add track ' . escapeQuery($track->track_name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
	                        'shift' => 'Add album ' . escapeQuery($track->album_name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
	                        'ctrl' => 'Search artist ' . escapeQuery($track->artist_name) . ' online');
	                } else {
	                    $arrayresult = array(
	                        escapeQuery($track->album_name) . " ● " . $track->num_streams . ' streams',
	                        'alt' => 'Play album ' . escapeQuery($track->album_name) . ' in Spotify',
	                        'cmd' => 'Play artist ' . escapeQuery($track->artist_name) . ' in Spotify',
	                        'fn' => 'Add track ' . escapeQuery($track->track_name) . ' to Your Music',
	                        'shift' => 'Add album ' . escapeQuery($track->album_name) . ' to Your Music',
	                        'ctrl' => 'Search artist ' . escapeQuery($track->artist_name) . ' online');
	                }
	                $track_artwork = getTrackOrAlbumArtwork($w,  $track_uri, false);
	                $w->result(null, serialize(array($track_uri /*track_uri*/, $album_uri /* album_uri */, $artist_uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, escapeQuery($track->artist_name)  /* artist_name */, escapeQuery($track->track_name) /* track_name */, escapeQuery($track->album_name) /* album_name */, $track_artwork /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), ucfirst(escapeQuery($track->track_name)) . " ● " . escapeQuery($track->artist_name), $arrayresult, $track_artwork, 'yes', null, '');
	                $nb_results++;
	            }
            }
        } // end of Charts
        elseif ($kind == "New Releases") {
	        $country = $words[1];

            if($country == 'Choose a Country') {
	           // list taken from http://charts.spotify.com/docs
	           $spotify_country_codes = array("ar","at","au","be","bg","ch","cl","co","cr","cz","de","dk","ec","ee","es","fi","fr","gb","gr","gt","hk","hu","ie","is","it","li","lt","lu","lv","mx","my","nl","no","nz","pe","pl","pt","se","sg","sk","sv","tr","tw","us","uy");
	           	foreach ($spotify_country_codes as $spotify_country_code) {
		            if (strtoupper($spotify_country_code) != 'US' &&
		            	strtoupper($spotify_country_code) != 'GB' &&
		            	strtoupper($spotify_country_code) != strtoupper($country_code)) {
		                $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the new album releases in ' . getCountryName(strtoupper($spotify_country_code)), './images/new_releases.png', 'no', null, 'New Releases▹' . strtoupper($spotify_country_code) . '▹');
		            }
		        }
            } else {
	            if (substr_count($query, '@') == 0) {
                //
                // Get New Releases Online
                //

				// call to web api, if it fails,
				// it displays an error in main window
				$albums = getTheNewReleases($w, $country, $max_results);

				$w->result(null, 'help', "Select an album below to browse it", 'singles and compilations are also displayed', './images/info.png', 'no', null, '');

				$noresult=true;
                foreach ($albums as $album) {
                    if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name) . ' (' . count($tracks->items) . ' tracks)') == false) {
						$noresult=false;
                        $genre = (count($album->genres) > 0) ? ' ● Genre: ' . implode('|', $album->genres) : '';
                        $tracks = $album->tracks;
                        $w->result(null, '', ucfirst($album->name) . ' (' . count($tracks->items) . ' tracks)', $album->album_type . " by " . $album->artists[0]->name . ' ● Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w,  $album->uri, false), 'no', null, "New Releases▹" .$country . '▹' . $album->uri . "@" . $album->name);
                    }
                }

		        if ($noresult) {
		            $w->result(null, 'help', "There is no album for this artist", "", './images/warning.png', 'no', null, '');
		        }

            } elseif (substr_count($query, '@') == 1) {
	                //
	                // Search Album Online
	                //
	                $tmp = $words[2];
	                $words = explode('@', $tmp);
	                $album_uri = $words[0];
	                $album_name = $words[1];

	                $tmp_uri = explode(':', $album_uri);

	                $json = doWebApiRequest($w, "https://api.spotify.com/v1/albums/" . $tmp_uri[2] . "/tracks");

	                $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, false);
	                $w->result(null, serialize(array('' /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'playalbum' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, '' /* artist_name */, '' /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "💿 " . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');

					if($update_in_progress == false) {
	               		$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
					}


	                $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
	                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
	                $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
	                foreach ($json->items as $track) {

	                    if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
	                        $track_artwork = getTrackOrAlbumArtwork($w,  $track->uri, false);
	                        if ($is_alfred_playlist_active == true) {
	                            $arrayresult = array(
	                                beautifyTime($track->duration_ms / 1000) . " ● " . $album_name,
	                                'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
	                                'cmd' => 'Play artist ' . escapeQuery($track->artists[0]->name) . ' in Spotify',
	                                'fn' => 'Add track ' . escapeQuery($track->name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
	                                'shift' => 'Add album ' . escapeQuery($album_name) . ' to ' . $alfred_playlist_name . ' Alfred Playlist',
	                                'ctrl' => 'Search artist ' . escapeQuery($track->artists[0]->name) . ' online');
	                        } else {
	                            $arrayresult = array(
	                                beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album_name),
	                                'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
	                                'cmd' => 'Play artist ' . escapeQuery($track->artists[0]->name) . ' in Spotify',
	                                'fn' => 'Add track ' . escapeQuery($track->name) . ' to Your Music',
	                                'shift' => 'Add album ' . escapeQuery(album_name) . ' to Your Music',
	                                'ctrl' => 'Search artist ' . escapeQuery($track->artists[0]->name) . ' online');
	                        }
	                        $w->result(null, serialize(array($track->uri /*track_uri*/, $album_uri /* album_uri */, $track->artists[0]->uri /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_track_in_album_context' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, $track->artists[0]->name  /* artist_name */, $track->name /* track_name */, $album_name /* album_name */, $track_artwork /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), escapeQuery(ucfirst($track->artists[0]->name)) . " ● " . escapeQuery($track->name), $arrayresult, $track_artwork, 'yes', null, '');
	                    }
	                }
	            }
	        }

        } // New Releases mode end
        elseif ($kind == "Add") {

		    if($update_in_progress == true) {
				$w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', 'Please retry when update is finished', './images/warning.png', 'no', null, '');

				echo $w->toxml();
		        return;
			}

            $tmp = explode('∙', $words[1]);
            $uri = $tmp[0];

            $href = explode(':', $uri);
            if ($href[1] == 'track') {
                $type = 'track';
                $track_name = $tmp[1];
                $track_uri = $uri;
                $message = "track " . $track_name;
            } elseif ($href[1] == 'album') {
                $type = 'album';
                $album_name = $tmp[1];
                $album_uri = $uri;
                $message = "album  " . $album_name;
            } elseif ($href[1] == 'user') {
                $type = 'playlist';
                $playlist_name = $tmp[1];
                $playlist_uri = $uri;
                $message = "playlist " . $playlist_name;
            }
            $theplaylist = $words[2];


            try {
                if (mb_strlen($theplaylist) < 3) {
                    $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1";
                    $stmt = $db->prepare($getPlaylists);

                    $w->result(null, '', 'Add ' . $type . ' ' . $tmp[1] . ' to Your Music or one of your playlists below..', "Select Your Music or one of your playlists below to add the " . $message, './images/add.png', 'no', null, '');

					$w->result(null, '', "Create a new playlist ", "Create a new playlist and add the " . $message, './images/create_playlist.png', 'no', null, $query . 'Enter Playlist Name▹');

                    // put Alfred Playlist at beginning
                    if ($is_alfred_playlist_active == true) {
                        if ($alfred_playlist_uri != '' && $alfred_playlist_name != '') {
                            $w->result(null, serialize(array($track_uri /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, $playlist_uri /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'ADD_TO_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, $track_name /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist_name /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "🎵 Alfred Playlist " . " ● " . ucfirst($alfred_playlist_name), "Select the playlist to add the " . $message, './images/alfred_playlist.png', 'yes', null, '');

                        }
                    }

					$w->result(null, serialize(array($track_uri /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, $playlist_uri /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'ADD_TO_YOUR_MUSIC▹' /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, $track_name /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist_name /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Your Music", "Select to add the " . $message . " to Your Music", './images/yourmusic.png', 'yes', null, '');
                } else {
                    $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist)";
                    $stmt = $db->prepare($getPlaylists);
                    $stmt->bindValue(':playlist', '%' . $theplaylist . '%');
                }

                $playlists = $stmt->execute();
            } catch (PDOException $e) {
                handleDbIssuePdoXml($db);
                return;
            }

            while ($playlist = $stmt->fetch()) {

                if (($playlist[0] != $alfred_playlist_uri
                        && (mb_strlen($theplaylist) < 3)) ||
                    (mb_strlen($theplaylist) >= 3)
                ) {
                    $added = ' ';
                    if (startswith($playlist[1], 'Artist radio for')) {
                        $added = '📻 ';
                    }
                    $w->result(null, serialize(array($track_uri /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, $playlist_uri /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'ADD_TO_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, $track_name /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist_name /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "🎵" . $added . ucfirst($playlist[1]), $playlist[7] . " tracks ● " . $playlist[8] . " ● Select the playlist to add the " . $message, $playlist[5], 'yes', null, '');
                }
            }
        } // end Add
        elseif ($kind == "Alfred Playlist") {
            $setting_kind = $words[1];
            $theplaylist = $words[2];

            if ($setting_kind == "Set Alfred Playlist") {
                $w->result(null, '', "Set your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, '');

                try {
                    if (mb_strlen($theplaylist) < 3) {
                        $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1";
                        $stmt = $db->prepare($getPlaylists);
                    } else {
                        $getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist)";
                        $stmt = $db->prepare($getPlaylists);
                        $stmt->bindValue(':playlist', '%' . $theplaylist . '%');
                    }

                    $playlists = $stmt->execute();

                } catch (PDOException $e) {
                    handleDbIssuePdoXml($db);
                    return;
                }

                while ($playlist = $stmt->fetch()) {

                    $added = ' ';
                    if (startswith($playlist[1], 'Artist radio for')) {
                        $added = '📻 ';
                    }
                    $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'ALFRED_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "🎵" . $added . ucfirst($playlist[1]), $playlist[7] . " tracks ● " . $playlist[8] . " ● Select the playlist to set it as your Alfred Playlist", $playlist[5], 'yes', null, '');

                }
            } elseif ($setting_kind == "Confirm Clear Alfred Playlist") {

                $w->result(null, '', "Are you sure?", "This will remove all the tracks in your current Alfred Playlist.", './images/warning.png', 'no', null, '');

                $w->result(null, '', "No, cancel", "Return to Alfred Playlist", './images/uncheck.png', 'no', null, 'Alfred Playlist▹');

                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'CLEAR_ALFRED_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Yes, go ahead", "This is undoable", './images/check.png', 'yes', null, '');

            }
        }
        // end of Settings
    }
    ////////////
    //
    // THIRD DELIMITER
    //
    ////////////
    elseif (substr_count($query, '▹') == 3) {

	    if($update_in_progress == true) {
			$w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', 'Please retry when update is finished', './images/warning.png', 'no', null, '');

			echo $w->toxml();
	        return;
		}
        $words = explode('▹', $query);
        $kind = $words[0];

        $tmp = explode('∙', $words[1]);
        $uri = $tmp[0];

        $href = explode(':', $uri);
        if ($href[1] == 'track') {
            $type = 'track';
            $track_name = $tmp[1];
            $track_uri = $uri;
            $message = "track " . $track_name;
        } elseif ($href[1] == 'album') {
            $type = 'album';
            $album_name = $tmp[1];
            $album_uri = $uri;
            $message = "album  " . $album_name;
        } elseif ($href[1] == 'user') {
            $type = 'playlist';
            $playlist_name = $tmp[1];
            $playlist_uri = $uri;
            $message = "playlist " . $playlist_name;
        }

        $the_query = $words[3];

        if ($kind == "Add") {
            if (mb_strlen($the_query) == 0) {
                $w->result(null, '', "Enter the name of the new playlist: ", "This will create a new playlist with the name entered", './images/create_playlist.png', 'no', null, '');
            } else {
                // playlist name has been set
                $w->result(null, serialize(array($track_uri /*track_uri*/, $album_uri /* album_uri */, '' /* artist_uri */, $playlist_uri /* playlist_uri */, '' /* spotify_command */, '' /* query */, 'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . ltrim(rtrim($the_query)) /* other_settings*/, '' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, $track_name /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist_name /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Create playlist " . ltrim(rtrim($the_query)), "This will create the playlist and add the " . $message, './images/add.png', 'yes', null, '');

            }
	    }
    }
}

echo $w->toxml();

//$end_time = computeTime();
//$total_temp = ($end_time-$begin_time);
//echo "$total_temp\n";

?>