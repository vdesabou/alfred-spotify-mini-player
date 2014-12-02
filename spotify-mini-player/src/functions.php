<?php

require_once './src/workflows.php';
require './vendor/autoload.php';





/**
 * playAlfredPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function playAlfredPlaylist($w)
{

    //
    // Read settings from DB
    //

    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select alfred_playlist_uri,alfred_playlist_name from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $alfred_playlist_uri = $setting[0];
        $alfred_playlist_name = $setting[1];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }


    if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
        displayNotificationWithArtwork("Error: Alfred Playlist is not set", './images/warning.png');
        return;
    }


    exec("osascript -e 'tell application \"Spotify\" to play track \"$alfred_playlist_uri\"'");

    $playlist_artwork_path = getPlaylistArtwork($w,  $alfred_playlist_uri, true, true);
    displayNotificationWithArtwork('🔈 Alfred Playlist ' . $alfred_playlist_name, $playlist_artwork_path);
}


/**
 * playCurrentArtist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function playCurrentArtist($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);

        $tmp = explode(':', $results[4]);

        $artist_uri = getArtistUriFromTrack($w, $results[4]);
        if ($artist_uri == false) {
            return;
        }
        exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
        displayNotificationWithArtwork('🔈 Artist ' . escapeQuery($results[1]), getArtistArtwork($w,  $results[1], true));
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
}

/**
 * playCurrentAlbum function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function playCurrentAlbum($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.sh 2>&1");


    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);

        $tmp = explode(':', $results[4]);

        $album_uri = getAlbumUriFromTrack($w, $results[4]);
        if ($album_uri == false) {
            return;
        }
        exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
        displayNotificationWithArtwork('🔈 Album ' . escapeQuery($results[2]), getTrackOrAlbumArtwork($w,  $results[4], true));
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
}


/**
 * addCurrentTrackTo function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackTo($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
		exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Add▹" . $results[4] . "∙" . escapeQuery($results[0]) . '▹' . "\"'");
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
}


/**
 * addCurrentTrackToAlfredPlaylistOrYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackToAlfredPlaylistOrYourMusic($w)
{
    //
    // Read settings from DB
    //

    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select is_alfred_playlist_active from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $is_alfred_playlist_active = $setting[0];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }

    if ($is_alfred_playlist_active == true) {
        addCurrentTrackToAlfredPlaylist($w);
    } else {
        addCurrentTrackToYourMusic($w);
    }
}

/**
 * addCurrentTrackToAlfredPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackToAlfredPlaylist($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);

	    //
	    // Read settings from DB
	    //

	    $dbfile = $w->data() . '/settings.db';
	    try {
	        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
	        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	        $getSettings = 'select alfred_playlist_uri,alfred_playlist_name from settings';
	        $stmt = $dbsettings->prepare($getSettings);
	        $stmt->execute();
	        $setting = $stmt->fetch();
	        $alfred_playlist_uri = $setting[0];
	        $alfred_playlist_name = $setting[1];
	    } catch (PDOException $e) {
	        handleDbIssuePdoEcho($dbsettings,$w);
	        $dbsettings = null;
	        return false;
	    }

        if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
            displayNotificationWithArtwork("Error: Alfred Playlist is not set", './images/warning.png');
            return;
        }
        $tmp = explode(':', $results[4]);
        $ret = addTracksToPlaylist($w, $tmp[2], $alfred_playlist_uri, $alfred_playlist_name, false);
        if (is_numeric($ret) && $ret > 0) {
            displayNotificationWithArtwork('' . $results[0] . ' by ' . $results[1] . ' added to Alfred Playlist ' . $alfred_playlist_name, getTrackOrAlbumArtwork($w,  $results[4], true));
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotificationWithArtwork('Error: ' . $results[0] . ' by ' . $results[1] . ' is already in Alfred Playlist ' . $alfred_playlist_name, './images/warning.png');
        }
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
}

/**
 * addCurrentTrackToYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackToYourMusic($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);

        $tmp = explode(':', $results[4]);
        $ret = addTracksToYourMusic($w, $tmp[2], false);
        if (is_numeric($ret) && $ret > 0) {
            displayNotificationWithArtwork('' . $results[0] . ' by ' . $results[1] . ' added to Your Music', getTrackOrAlbumArtwork($w,  $results[4], true));
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotificationWithArtwork('Error: ' . $results[0] . ' by ' . $results[1] . ' is already in Your Music', './images/warning.png');
        }
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
}

/**
 * getRandomTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getRandomTrack($w)
{
    //
    // Get random track from DB
    //

    $dbfile = $w->data() . "/library.db";
    try {
        $db= new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getTracks = "select uri from tracks order by random() limit 1";
        $stmt = $db->prepare($getTracks);
        $stmt->execute();
        $track = $stmt->fetch();
        $thetrackuri = $track[0];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $db = null;
        return false;
    }
    return $thetrackuri;
}

/**
 * getSpotifyWebAPI function.
 *
 * @access public
 * @param mixed $w
 * @return api, false if error
 */
function getSpotifyWebAPI($w)
{

    if (!$w->internet()) {
        displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
        return false;
    }

    //
    // Read settings from DB
    //
    $getSettings = 'select oauth_client_id,oauth_client_secret,oauth_redirect_uri,oauth_access_token,oauth_expires,oauth_refresh_token from settings';
    $dbfile = $w->data() . '/settings.db';

    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbsettings->query("PRAGMA synchronous = OFF");
        $dbsettings->query("PRAGMA journal_mode = OFF");
        $dbsettings->query("PRAGMA temp_store = MEMORY");
        $dbsettings->query("PRAGMA count_changes = OFF");
        $dbsettings->query("PRAGMA PAGE_SIZE = 4096");
        $dbsettings->query("PRAGMA default_cache_size=700000");
        $dbsettings->query("PRAGMA cache_size=700000");
        $dbsettings->query("PRAGMA compile_options");
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        return false;
    }

    try {
        $stmt = $dbsettings->prepare($getSettings);
        $settings = $stmt->execute();

    } catch (PDOException $e) {
        $dbsettings = null;
        handleDbIssuePdoEcho($dbsettings,$w);
        return false;
    }

    try {
        $setting = $stmt->fetch();
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        return false;
    }

    $oauth_client_id = $setting[0];
    $oauth_client_secret = $setting[1];
    $oauth_redirect_uri = $setting[2];
    $oauth_access_token = $setting[3];
    $oauth_expires = $setting[4];
    $oauth_refresh_token = $setting[5];

    $session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);
    $session->setRefreshToken($oauth_refresh_token);
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    // Check if refresh token necessary
    if (time() - $oauth_expires > 3100) {
        if ($session->refreshToken()) {

            $oauth_access_token = $session->getAccessToken();

            // Set new token to settings
            $updateSettings = "update settings set oauth_access_token=:oauth_access_token,oauth_expires=:oauth_expires";
            try {
                $stmt = $dbsettings->prepare($updateSettings);
                $stmt->bindValue(':oauth_access_token', $session->getAccessToken());
                $stmt->bindValue(':oauth_expires', time());
                $stmt->execute();

            } catch (PDOException $e) {
                $dbsettings = null;;
                handleDbIssuePdoEcho($dbsettings,$w);
                return false;
            }

            //displayNotification("Token was refreshed");

        } else {
            echo "Error[getSpotifyWebAPI]: token could not be refreshed";
            return false;
        }
    }
    $api->setAccessToken($oauth_access_token);

    return $api;
}


/**
 * getArtistUriFromTrack function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @return void
 */
function getArtistUriFromTrack($w, $track_uri)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    try {
        $tmp = explode(':', $track_uri);

        $track = $api->getTrack($tmp[2]);
        $artists = $track->artists;
        $artist = $artists[0];
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getArtistUriFromTrack): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    return $artist->uri;
}


/**
 * getAlbumUriFromTrack function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @return void
 */
function getAlbumUriFromTrack($w, $track_uri)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    try {
        $tmp = explode(':', $track_uri);

        $track = $api->getTrack($tmp[2]);
        $album = $track->album;
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getAlbumUriFromTrack): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    return $album->uri;
}

/**
 * clearPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @param mixed $playlist_name
 * @return void
 */
function clearPlaylist($w, $playlist_uri, $playlist_name)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    try {
        $tmp = explode(':', $playlist_uri);
        $emptytracks = array();
        $api->replacePlaylistTracks($tmp[2], $tmp[4], $emptytracks);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(clearPlaylist): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    // refresh playlist
    updatePlaylist($w, $playlist_uri, $playlist_name);

    return true;
}



/**
 * createTheUserPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_name
 * @return void
 */
function createTheUserPlaylist($w, $playlist_name)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    //
    // Read settings from DB
    //

    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $userid = $setting[0];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }

    try {
        $json = $api->createUserPlaylist($userid, array(
            'name' => $playlist_name,
            'public' => false
        ));
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(createUserPlaylist): createUserPlaylist " . $playlist_name . " (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    return $json->uri;
}


/**
 * createRadioArtistPlaylistForCurrentArtist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function createRadioArtistPlaylistForCurrentArtist($w)
{
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        createRadioArtistPlaylist($w, $results[1]);
    } else {
        displayNotification("Error: cannot get current artist");
    }
}

/**
 * createRadioArtistPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_name
 * @return void
 */
function createRadioArtistPlaylist($w, $artist_name)
{

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    //
    // Read settings from DB
    //

    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select userid,radio_number_tracks,echonest_api_key from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $userid = $setting[0];
        $radio_number_tracks = $setting[1];
        $echonest_api_key = $setting[2];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }


    $json = doWebApiRequest($w, 'http://developer.echonest.com/api/v4/playlist/static?api_key=' . $echonest_api_key . '&artist=' . urlencode($artist_name) . '&format=json&results=' . $radio_number_tracks . '&distribution=focused&type=artist-radio&bucket=id:spotify&bucket=tracks');

    $response = $json->response;

    $newplaylisttracks = array();
    foreach ($response->songs as $song) {
        foreach ($song->tracks as $track) {
            $foreign_id = $track->foreign_id;
            $tmp = explode(':', $foreign_id);
            $newplaylisttracks[] = $tmp[2];
            // only take one
            break;
        }
    }

    if (count($newplaylisttracks) > 0) {
        try {
            $json = $api->createUserPlaylist($userid, array(
                'name' => 'Artist radio for ' . escapeQuery($artist_name),
                'public' => false
            ));
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(createUserPlaylist): radio artist " . $artist_name . " (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }

        $ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
        if (is_numeric($ret) && $ret > 0) {
            refreshLibrary($w);
            return;
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotification('Error: Playlist ' . $json->name . ' cannot be added');
            return;
        } else {
			displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
            return;
        }
    } else {
        displayNotificationWithArtwork('Error: track was not found in Echo Nest','./images/warning.png');
        return false;
    }

    return true;
}


/**
 * createRadioSongPlaylistForCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function createRadioSongPlaylistForCurrentTrack($w)
{
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        createRadioSongPlaylist($w, $results[0], $results[4], $results[1]);
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png');
    }
}


/**
 * createRadioSongPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_name
 * @param mixed $track_uri
 * @param mixed $artist_name
 * @return void
 */
function createRadioSongPlaylist($w, $track_name, $track_uri, $artist_name)
{

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    //
    // Read settings from DB
    //

    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select userid,radio_number_tracks,echonest_api_key from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $userid = $setting[0];
        $radio_number_tracks = $setting[1];
        $echonest_api_key = $setting[2];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }

    $json = doWebApiRequest($w, 'http://developer.echonest.com/api/v4/playlist/static?api_key=' . $echonest_api_key . '&song_id=' . $track_uri . '&format=json&results=' . $radio_number_tracks . '&distribution=focused&type=song-radio&bucket=id:spotify&bucket=tracks');

    $response = $json->response;

    $newplaylisttracks = array();
    foreach ($response->songs as $song) {
        foreach ($song->tracks as $track) {
            $foreign_id = $track->foreign_id;
            $tmp = explode(':', $foreign_id);
            $newplaylisttracks[] = $tmp[2];
            // only take one
            break;
        }
    }

    if (count($newplaylisttracks) > 0) {
        try {
            $json = $api->createUserPlaylist($userid, array(
                'name' => 'Song radio for ' . escapeQuery($track_name) . ' by ' . escapeQuery($artist_name),
                'public' => false
            ));
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(createUserPlaylist): radio song " . escapeQuery($track_name) . " (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }

        $ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
        if (is_numeric($ret) && $ret > 0) {
            refreshLibrary($w);
            return;
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotificationWithArtwork('Error: Playlist ' . $json->name . ' cannot be added','./images/warning.png');
            return;
        } else {
			displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
            return;
        }
    } else {
        displayNotificationWithArtwork('Error: track was not found in Echo Nest','./images/warning.png');
        return false;
    }

    return true;
}


/**
 * getThePlaylistTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @return void
 */
function getThePlaylistTracks($w, $playlist_uri)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    $tracks = array();

    try {
        $tmp = explode(':', $playlist_uri);
        $offsetGetUserPlaylistTracks = 0;
        $limitGetUserPlaylistTracks = 100;
        do {
            $userPlaylistTracks = $api->getUserPlaylistTracks($tmp[2], $tmp[4], array(
                'fields' => array('total',
                				  'items.track(id)'),
                'limit' => $limitGetUserPlaylistTracks,
                'offset' => $offsetGetUserPlaylistTracks
            ));

            foreach ($userPlaylistTracks->items as $item) {
                $track = $item->track;
                $tracks[] = $track->id;
            }

            $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
        } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getThePlaylistTracks): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    return array_filter($tracks);
}

/**
 * getTheAlbumTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $album_uri
 * @return void
 */
function getTheAlbumTracks($w, $album_uri)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }

    $tracks = array();

    try {
        $tmp = explode(':', $album_uri);

        $json = $api->getAlbumTracks($tmp[2]);

        foreach ($json->items as $track) {
            $tracks[] = $track->id;
        }
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getTheAlbumTracks): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    return array_filter($tracks);
}

/**
 * getTheArtistAlbums function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $country_code
 * @return void
 */
function getTheArtistAlbums($w, $artist_uri, $country_code)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    $album_ids = array();

    try {
        $tmp = explode(':', $artist_uri);
        $offsetGetArtistAlbums = 0;
        $limitGetArtistAlbums = 50;
        do {
            $userArtistAlbums = $api->getArtistAlbums($tmp[2],array(
									            'album_type' => array('album','single','compilation'),
									            'market' => $country_code,
									            'limit' => $limitGetArtistAlbums,
									            'offset' => $offsetGetArtistAlbums
									        ));

            foreach ($userArtistAlbums->items as $album) {
                $album_ids[] = $album->id;
            }

            $offsetGetArtistAlbums += $limitGetArtistAlbums;
        } while ($offsetGetArtistAlbums < $userArtistAlbums->total);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	    $w2 = new Workflows('com.vdesabou.spotify.mini.player');
	    $w2->result(null, '', "Error: Spotify WEB API getArtistAlbums returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
	    echo $w2->toxml();
        exit;
    }


	$albums = array();

	 try {
	    // Note: max 20 Ids
	    $offset = 0;
	    do {
	        $output = array_slice($album_ids, $offset, 20);
	        $offset += 20;

	        if (count($output)) {
	            $resultGetAlbums = $api->getAlbums($output);
	            foreach ($resultGetAlbums->albums as $album) {
	                $albums[] = $album;
	            }
	        }

	    } while (count($output) > 0);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	    $w2 = new Workflows('com.vdesabou.spotify.mini.player');
	    $w2->result(null, '', "Error: Spotify WEB API getAlbums returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
	    echo $w2->toxml();
        exit;
    }


    return $albums;
}



/**
 * addTracksToYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $tracks
 * @param bool $allow_duplicate (default: true)
 * @return void
 */
function addTracksToYourMusic($w, $tracks, $allow_duplicate = true)
{

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }
    $tracks = (array)$tracks;
    $tracks_with_no_dup = array();
    $tracks_contain = array();
    if (!$allow_duplicate) {
        try {

            // Note: max 50 Ids
            $offset = 0;
            do {
                $output = array_slice($tracks, $offset, 50);
                $offset += 50;

                if (count($output)) {
                    $tracks_contain = $api->myTracksContains($output);
                    for ($i = 0; $i < count($output); $i++) {
                        if (!$tracks_contain[$i]) {
                            $tracks_with_no_dup[] = $output[$i];
                        }
                    }
                }

            } while (count($output) > 0);

            $tracks = $tracks_with_no_dup;
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(addTracksToYourMusic): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }
    }

    if (count($tracks) != 0) {

        try {
            $offset = 0;
            do {
                $output = array_slice($tracks, $offset, 50);
                $offset += 50;

                if (count($output)) {
                    $api->addMyTracks($output);

                }

            } while (count($output) > 0);

        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(addTracksToYourMusic): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }

        // refresh my tracks
        updateYourMusic($w);
    }

    return count($tracks);
}


/**
 * addTracksToPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $tracks
 * @param mixed $playlist_uri
 * @param mixed $playlist_name
 * @param bool $allow_duplicate (default: true)
 * @return void
 */
function addTracksToPlaylist($w, $tracks, $playlist_uri, $playlist_name, $allow_duplicate = true, $updatePlaylist = true)
{

    //
    // Read settings from DB
    //
    $dbfile = $w->data() . '/settings.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $userid = $setting[0];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }

    $tracks_with_no_dup = array();
    if (!$allow_duplicate) {
        try {
            $playlist_tracks = getThePlaylistTracks($w, $playlist_uri);

            foreach ((array)$tracks as $track) {
                if (!checkIfDuplicate($playlist_tracks, $track)) {
                    $tracks_with_no_dup[] = $track;
                }
            }

            $tracks = $tracks_with_no_dup;
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(addTracksToPlaylist): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }
    }

    if (count($tracks) != 0) {
        try {
            $tmp = explode(':', $playlist_uri);

            // Note: max 100 Ids
            $offset = 0;
            $i=0;
            do {
                $output = array_slice($tracks, $offset, 100);
                $offset += 100;

                if (count($output)) {
                    $api->addUserPlaylistTracks($userid, $tmp[4], $output, array(
                        'position' => 0
                    ));
                    $i++;
                }
/*
				if($i % 30 === 0) {
					sleep(60);
					echo "Info: Throttling in addTracksToPlaylist";
				}
*/
            } while (count($output) > 0);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(addTracksToPlaylist): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }

        if ($updatePlaylist) {
            // refresh library
            refreshLibrary($w);
        }
    }

    return count($tracks);
}


/**
 * computeTime function.
 *
 * @access public
 * @return void
 */
function computeTime()
{
    list($msec, $sec) = explode(' ', microtime());
    return (float)$sec + (float)$msec;
}


/**
 * getFreeTcpPort function.
 *
 * @access public
 * @return void
 */
function getFreeTcpPort()
{
    //avoid warnings like this PHP Warning:  fsockopen(): unable to connect to localhost (Connection refused)
    error_reporting(~E_ALL);

    $from = 10000;
    $to = 20000;

    //TCP ports
    $host = 'localhost';

    for ($port = $from; $port <= $to; $port++) {
        $fp = fsockopen($host, $port);
        if (!$fp) {
            //port is free
            return $port;
        } else {
            // port open, close it
            fclose($fp);
        }
    }

    return 17693;
}


/**
 * getPlaylistsForTrack function.
 *
 * @access public
 * @param mixed $db
 * @param mixed $track_uri
 * @return void
 */
function getPlaylistsForTrack($db, $track_uri)
{

    $playlistsfortrack = "";
    $getPlaylistsForTrack = "select distinct playlist_name from tracks where uri=:uri";
    try {
        $stmt = $db->prepare($getPlaylistsForTrack);
        $stmt->bindValue(':uri', '' . $track_uri . '');
        $stmt->execute();

        $noresult = true;
        while ($playlist = $stmt->fetch()) {
            if ($noresult == true) {
                if ($playlist[0] == "") {
                    $playlistsfortrack = $playlistsfortrack . " ● ♫ : " . 'Your Music';
                } else {
                    $playlistsfortrack = $playlistsfortrack . " ● ♫ : " . $playlist[0];
                }
            } else {
                if ($playlist[0] == "") {
                    $playlistsfortrack = $playlistsfortrack . " ○ " . 'Your Music';
                } else {
                    $playlistsfortrack = $playlistsfortrack . " ○ " . $playlist[0];
                }

            }
            $noresult = false;
        }


    } catch (PDOException $e) {
        handleDbIssuePdoXml($db);
        return $playlistsfortrack;
    }
    return $playlistsfortrack;
}


/**
 * escapeQuery function.
 *
 * @access public
 * @param mixed $text
 * @return void
 */
function escapeQuery($text)
{
    $text = str_replace("'", "’", $text);
    $text = str_replace('"', "’", $text);
    $text = str_replace("&apos;", "’", $text);
    $text = str_replace("`", "’", $text);
    $text = str_replace("&amp;", "and", $text);
    $text = str_replace("&", "and", $text);
    $text = str_replace("\\", " ", $text);
    $text = str_replace("$", "\\$", $text);
    return $text;
}


/**
 * checkIfResultAlreadyThere function.
 *
 * @access public
 * @param mixed $results
 * @param mixed $title
 * @return void
 */
function checkIfResultAlreadyThere($results, $title)
{
    foreach ($results as $result) {
        if ($result['title']) {
            if ($result['title'] == $title) {
                return true;
            }
        }
    }
    return false;
}


/**
 * checkIfDuplicate function.
 *
 * @access public
 * @param mixed $track_ids
 * @param mixed $id
 * @return void
 */
function checkIfDuplicate($track_ids, $id)
{
    foreach ($track_ids as $track_id) {
        if ($track_id == $id) {
            return true;
        }
    }
    return false;
}


/**
 * displayNotification function.
 *
 * @access public
 * @param mixed $output
 * @return void
 */
function displayNotification($output)
{
    exec('./terminal-notifier.app/Contents/MacOS/terminal-notifier -title "Spotify Mini Player" -sender com.spotify.miniplayer -message "' . $output . '"');
}


/**
 * displayNotificationWithArtwork function.
 *
 * @access public
 * @param mixed $output
 * @param mixed $artwork
 * @return void
 */
function displayNotificationWithArtwork($output, $artwork)
{
    if ($artwork != "" && file_exists($artwork)) {
        copy($artwork, "/tmp/tmp");
    }

    exec("./terminal-notifier.app/Contents/MacOS/terminal-notifier -title 'Spotify Mini Player' -sender 'com.spotify.miniplayer' -contentImage '/tmp/tmp' -message '" . $output . "'");
}

/**
 * displayNotificationForCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function displayNotificationForCurrentTrack($w)
{
    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        displayNotificationWithArtwork('🔈 ' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]), getTrackOrAlbumArtwork($w,  $results[4], true));
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png');
    }
}

/**
 * displayLyricsForCurrentTrack function.
 *
 * @access public
 * @return void
 */
function displayLyricsForCurrentTrack()
{
    $w = new Workflows('com.vdesabou.spotify.mini.player');

    $command_output = exec("./src/track_info.sh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        displayLyrics($w, $results[1], $results[0]);
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png');
    }
}

/**
 * displayLyrics function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @param mixed $title
 * @return void
 */
function displayLyrics($w, $artist, $title)
{
    if (!$w->internet()) {
        displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
        return;
    }
    $output = getLyrics($w, $artist, $title);

    if ($output != false) {
        PHPRtfLite::registerAutoloader();

        $file = $w->cache() . '/lyrics.rtf';

        $rtf = new PHPRtfLite();

        $section = $rtf->addSection();
        // centered text
        $fontTitle = new PHPRtfLite_Font(28, 'Arial', '#000000', '#FFFFFF');
        $parFormatTitle = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_CENTER);
        $section->writeText($title . ' by ' . $artist, $fontTitle, $parFormatTitle);

        $parFormat = new PHPRtfLite_ParFormat();
        $parFormat->setSpaceAfter(4);
        $font = new PHPRtfLite_Font(14, 'Arial', '#000000', '#FFFFFF');
        // write text
        $section->writeText($output, $font, $parFormat);

        $rtf->save($file);
        exec("qlmanage -p \"$file\"");
    }
    return;
}

/**
 * getTrackOrAlbumArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $spotifyURL
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getTrackOrAlbumArtwork($w, $spotifyURL, $fetchIfNotPresent)
{

    $hrefs = explode(':', $spotifyURL);

    $isAlbum = false;
    if ($hrefs[1] == "album") {
        $isAlbum = true;
    }

    if (!file_exists($w->data() . "/artwork")):
        exec("mkdir '" . $w->data() . "/artwork'");
    endif;

    $currentArtwork = $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "/" . "$hrefs[2].png";
    $artwork = "";

    if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
        if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
            $artwork = getArtworkURL($w, $hrefs[1], $hrefs[2]);

            // if return 0, it is a 404 error, no need to fetch
            if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
                endif;
                $fp = fopen($currentArtwork, 'w+');
                $options = array(
                    CURLOPT_FILE => $fp
                );

                $w->request("$artwork", $options);
            } else {
	            if ($isAlbum) {
	                return "./images/albums.png";
	            } else {
	                return "./images/tracks.png";
	            }
            }
        } else {
            if ($isAlbum) {
                return "./images/albums.png";
            } else {
                return "./images/tracks.png";
            }
        }
    } else {
        if (filesize($currentArtwork) == 0) {
            if ($isAlbum) {
                return "./images/albums.png";
            } else {
                return "./images/tracks.png";
            }
        }
    }

    if (is_numeric($artwork) && $artwork == 0) {
        if ($isAlbum) {
            return "./images/albums.png";
        } else {
            return "./images/tracks.png";
        }
    } else {
        return $currentArtwork;
    }
}


/**
 * getPlaylistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlistURI
 * @param mixed $fetchIfNotPresent
 * @param bool $forceFetch (default: false)
 * @return void
 */
function getPlaylistArtwork($w, $playlistURI, $fetchIfNotPresent, $forceFetch = false)
{

    $hrefs = explode(':', $playlistURI);

    if (!file_exists($w->data() . "/artwork")):
        exec("mkdir '" . $w->data() . "/artwork'");
    endif;

    if (count($hrefs) == 5) {

        $filename = "" . $hrefs[2] . "_" . $hrefs[4];
        $url = "http://open.spotify.com/user/" . $hrefs[2] . "/playlist/" . $hrefs[4];
    } else {
        //starred playlist
        $filename = "" . $hrefs[2] . "_" . $hrefs[3];
        $url = "http://open.spotify.com/user/" . $hrefs[2] . "/" . $hrefs[3];
    }


    $currentArtwork = $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "/" . "$filename.png";

    if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
        if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
            $artwork = getPlaylistArtworkURL($w, $url);

            // if return 0, it is a 404 error, no need to fetch
            if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $filename . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "'");
                endif;
                $fp = fopen($currentArtwork, 'w+');
                $options = array(
                    CURLOPT_FILE => $fp
                );

                $w->request("$artwork", $options);
            } else {
            	return "./images/playlists.png";
        	}
        } else {
            return "./images/playlists.png";
        }
    } else {
        if (filesize($currentArtwork) == 0) {
            return "./images/playlists.png";
        }
    }

    if (is_numeric($artwork) && $artwork == 0) {
        return "./images/playlists.png";
    } else {
        return $currentArtwork;
    }
}


/**
 * getArtistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getArtistArtwork($w, $artist, $fetchIfNotPresent)
{
    $parsedArtist = urlencode(escapeQuery($artist));

    if (!file_exists($w->data() . "/artwork")):
        exec("mkdir '" . $w->data() . "/artwork'");
    endif;

    $currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";
    $artwork = "";

    if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
        if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
            $artwork = getArtistArtworkURL($w, $artist);

            // if return 0, it is a 404 error, no need to fetch
            if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
                endif;
                $fp = fopen($currentArtwork, 'w+');
                $options = array(
                    CURLOPT_FILE => $fp
                );
                $w->request("$artwork", $options);
            } else {
	            return "./images/artists.png";
            }
        } else {
            return "./images/artists.png";
        }
    } else {
        if (filesize($currentArtwork) == 0) {
            return "./images/artists.png";
        }
    }

    if (is_numeric($artwork) && $artwork == 0) {
        return "./images/artists.png";
    } else {
        return $currentArtwork;
    }
}


/**
 * getArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $type
 * @param mixed $id
 * @return void
 */
function getArtworkURL($w, $type, $id)
{
    $options = array(
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_TIMEOUT => 5
    );

    $html = $w->request("http://open.spotify.com/$type/$id", $options);

    if (!empty($html)) {
        preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
        return (isset($m[1][0])) ? $m[1][0] : 0;
    }

    return 0;
}


/**
 * getPlaylistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $url
 * @return void
 */
function getPlaylistArtworkURL($w, $url)
{
    $options = array(
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_TIMEOUT => 5
    );
    $html = $w->request($url, $options);

    if (!empty($html)) {
        preg_match_all('/.*?twitter:image.*?content="(.*?)">.*?/is', $html, $m);
        return (isset($m[1][0])) ? $m[1][0] : 0;
    }

    return 0;
}


/**
 * getArtistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @return void
 */
function getArtistArtworkURL($w, $artist)
{
    $options = array(
        CURLOPT_TIMEOUT => 5
    );

    $parsedArtist = urlencode($artist);
    $html = $w->request("http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json", $options);
    $json = json_decode($html, true);
    // make more resilient to empty json responses
    if (!is_array($json) || empty($json['artist']['image'][1]['#text'])) {
        return '';
    }
    return $json[artist][image][1]['#text'];
}


/**
 * updateLibrary function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function updateLibrary($w)
{

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return false;
    }

    touch($w->data() . "/update_library_in_progress");
    $w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');


    //
    // Read settings from a copy of DB
    //

    copy($w->data() . '/settings.db', $w->data() . '/settings_tmp.db');
    $dbfile = $w->data() . '/settings_tmp.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select country_code,userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $country_code = $setting[0];
        $userid = $setting[1];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        unlink($w->data() . "/update_library_in_progress");
        return false;
    }


    $words = explode('▹', $in_progress_data);

    //
    // move legacy artwork files in hash directories if needed
    //
    if (file_exists($w->data() . "/artwork")) {
        $folder = $w->data() . "/artwork";
        if ($handle = opendir($folder)) {

            while (false !== ($file = readdir($handle))) {
                if (stristr($file, '.png')) {
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $file) . "'");
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
    if (file_exists($w->data() . '/library_new.db')) {
        unlink($w->data() . "/library_new.db");
    }
    $dbfile = $w->data() . '/library_new.db';
    touch($dbfile);

    try {
        $db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return false;
    }

    // get the total number of tracks
    $nb_tracktotal = 0;
    $savedListPlaylist = array();
    try {
        $offsetGetUserPlaylists = 0;
        $limitGetUserPlaylists = 50;
        do {
            $userPlaylists = $api->getUserPlaylists($userid, array(
                'limit' => $limitGetUserPlaylists,
                'offset' => $offsetGetUserPlaylists
            ));

            foreach ($userPlaylists->items as $playlist) {
                $tracks = $playlist->tracks;
                $nb_tracktotal += $tracks->total;
                $savedListPlaylist[] = $playlist;
            }

            $offsetGetUserPlaylists += $limitGetUserPlaylists;

        } while ($offsetGetUserPlaylists < $userPlaylists->total);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getUserPlaylists): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    $savedMySavedTracks = array();
    try {
        $offsetGetMySavedTracks = 0;
        $limitGetMySavedTracks = 50;
        do {
            $userMySavedTracks = $api->getMySavedTracks(array(
                'limit' => $limitGetMySavedTracks,
                'offset' => $offsetGetMySavedTracks
            ));

            foreach ($userMySavedTracks->items as $track) {
                $savedMySavedTracks[] = $track;
                $nb_tracktotal += 1;
            }

            $offsetGetMySavedTracks += $limitGetMySavedTracks;

        } while ($offsetGetMySavedTracks < $userMySavedTracks->total);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getMySavedTracks): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
        return false;
    }

    $db->exec("create table tracks (mymusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration_ms int)");
    $db->exec("CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)");
    $db->exec("CREATE INDEX IndexArtistName ON tracks (artist_name)");
    $db->exec("CREATE INDEX IndexAlbumName ON tracks (album_name)");
    $db->exec("create table counters (all_tracks int, mymusic_tracks int, all_artists int, mymusic_artists int, all_albums int, mymusic_albums int, playlists int)");
    $db->exec("create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean)");

    // Handle playlists
    $w->write('Create Library▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');

    $nb_track = 0;
    $insertPlaylist = "insert into playlists values (:uri,:name,:count_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser)";
    $stmtPlaylist = $db->prepare($insertPlaylist);

    $insertTrack = "insert into tracks values (:mymusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration_ms)";
    $stmtTrack = $db->prepare($insertTrack);

    foreach ($savedListPlaylist as $playlist) {
        $tracks = $playlist->tracks;
        $owner = $playlist->owner;

        $playlist_artwork_path = getPlaylistArtwork($w,  $playlist->uri, true, true);

        if ("-" . $owner->id . "-" == "-" . $userid . "-") {
            $ownedbyuser = 1;
        } else {
            $ownedbyuser = 0;
        }

        $stmtPlaylist->bindValue(':uri', $playlist->uri);
        $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
        $playlist_tracks = $playlist->tracks;
        $stmtPlaylist->bindValue(':count_tracks', $playlist_tracks->total);
        $stmtPlaylist->bindValue(':owner', $owner->id);
        $stmtPlaylist->bindValue(':username', $owner->id);
        $stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
        $stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
        $stmtPlaylist->execute();

        try {
            $offsetGetUserPlaylistTracks = 0;
            $limitGetUserPlaylistTracks = 100;
            do {
                $userPlaylistTracks = $api->getUserPlaylistTracks($owner->id, $playlist->id, array(
                    'fields' => array('total',
                        'items(added_at)',
                        'items.track(available_markets,duration_ms,uri,popularity,name)',
                        'items.track.album(album_type,images,uri,name)',
                        'items.track.artists(name,uri)'
                    ),
                    'limit' => $limitGetUserPlaylistTracks,
                    'offset' => $offsetGetUserPlaylistTracks
                ));

                foreach ($userPlaylistTracks->items as $item) {
                    $track = $item->track;

                    if($track->uri == 'spotify:track:null') {
	                    // skip
	                    $nb_track++;
	                    continue;
                    }

                    if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                        $playable = 1;
                    } else {
                        $playable = 0;
                    }

                    $artists = $track->artists;
                    $artist = $artists[0];
                    $album = $track->album;

                    //
                    // Download artworks
                    $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
                    $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
                    $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);

                    $stmtTrack->bindValue(':mymusic', 0);
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
                    $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
                    $stmtTrack->execute();

                    $nb_track++;
                    if ($nb_track % 10 === 0) {
                        $w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
                    }
                }

                $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

            } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getUserPlaylistTracks): playlist id " . $playlist->id . " (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }
    }

    // Handle Your Music
    foreach ($savedMySavedTracks as $track) {
        $track = $track->track;

        if($track->uri == 'spotify:track:null') {
            // skip
            $nb_track++;
            continue;
        }

        if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
            $playable = 1;
        } else {
            $playable = 0;
        }
        $artists = $track->artists;
        $artist = $artists[0];
        $album = $track->album;

        //
        // Download artworks
        $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
        $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
        $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);

        $stmtTrack->bindValue(':mymusic', 1);
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
        $stmtTrack->bindValue(':added_at', $track->added_at);
        $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
        $stmtTrack->execute();

        $nb_track++;
        if ($nb_track % 10 === 0) {
            $w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
        }
    }


    // update counters
    try {
        $getCount = 'select count(distinct uri) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_tracks = $stmt->fetch();

        $getCount = 'select count(distinct uri) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_tracks = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_artists = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_artists = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_albums = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_albums = $stmt->fetch();

        $getCount = 'select count(*) from playlists';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $playlists_count = $stmt->fetch();

        $insertCounter = "insert into counters values (:all_tracks,:mymusic_tracks,:all_artists,:mymusic_artists,:all_albums,:mymusic_albums,:playlists)";
        $stmt = $db->prepare($insertCounter);

        $stmt->bindValue(':all_tracks', $all_tracks[0]);
        $stmt->bindValue(':mymusic_tracks', $mymusic_tracks[0]);
        $stmt->bindValue(':all_artists', $all_artists[0]);
        $stmt->bindValue(':mymusic_artists', $mymusic_artists[0]);
        $stmt->bindValue(':all_albums', $all_albums[0]);
        $stmt->bindValue(':mymusic_albums', $mymusic_albums[0]);
        $stmt->bindValue(':playlists', $playlists_count[0]);
        $stmt->execute();

    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return false;
    }

    $elapsed_time = time() - $words[3];
    displayNotificationWithArtwork("Library (re-)created (" . $nb_track . " tracks) - took " . beautifyTime($elapsed_time),'./images/recreate.png');

    unlink($w->data() . "/update_library_in_progress");
    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    unlink($w->data() . '/settings_tmp.db');

    // remove legacy spotify app if needed
    if (file_exists($w->data() . "/library.db")) {
        if (file_exists(exec('printf $HOME') . "/Spotify/spotify-app-miniplayer")) {
            exec("rm -rf " . exec('printf $HOME') . "/Spotify/spotify-app-miniplayer");
        }
    }
}


/**
 * updatePlaylist function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updatePlaylist($w, $playlist_uri, $playlist_name)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }

    touch($w->data() . "/update_library_in_progress");
    $w->write('InitPlaylist▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');

    //
    // Read settings from a copy of DB
    //

    copy($w->data() . '/settings.db', $w->data() . '/settings_tmp.db');
    $dbfile = $w->data() . '/settings_tmp.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select country_code,userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $country_code = $setting[0];
        $userid = $setting[1];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        unlink($w->data() . "/update_library_in_progress");
        return false;
    }


    $words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');


    if (file_exists($w->data() . '/library.db')) {
        rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    }
    copy($w->data() . '/library_old.db', $w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';

    try {
        $db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        $db->exec("drop table counters");
        $db->exec("create table counters (all_tracks int, mymusic_tracks int, all_artists int, mymusic_artists int, all_albums int, mymusic_albums int, playlists int)");

        $nb_track = 0;
        $w->write('Update Playlist▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');

        $deleteFromTracks = "delete from tracks where playlist_uri=:playlist_uri";
        $stmt = $db->prepare($deleteFromTracks);
        $stmt->bindValue(':playlist_uri', $playlist_uri);
        $stmt->execute();

        $updatePlaylists = "update playlists set nb_tracks=:nb_tracks,playlist_artwork_path=:playlist_artwork_path where uri=:uri";
        $stmtUpdatePlaylists = $db->prepare($updatePlaylists);

        $insertTrack = "insert into tracks values (:mymusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration_ms)";
        $stmtTrack = $db->prepare($insertTrack);

        $tmp = explode(':', $playlist_uri);

        $nb_tracktotal = 0;
        $savedPlaylistTracks = array();
        try {
            $offsetGetUserPlaylistTracks = 0;
            $limitGetUserPlaylistTracks = 100;
            do {
                $userPlaylistTracks = $api->getUserPlaylistTracks($tmp[2], $tmp[4], array(
                    'fields' => array('total',
                        'items(added_at)',
                        'items.track(available_markets,duration_ms,uri,popularity,name)',
                        'items.track.album(album_type,images,uri,name)',
                        'items.track.artists(name,uri)'
                    ),
                    'limit' => $limitGetUserPlaylistTracks,
                    'offset' => $offsetGetUserPlaylistTracks
                ));

                foreach ($userPlaylistTracks->items as $item) {
                    $savedPlaylistTracks[] = $item;

                }
                $nb_tracktotal = $userPlaylistTracks->total;

                $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

            } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getUserPlaylistTracks): playlist id " . $tmp[4] . " (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return;
        }

        $stmtUpdatePlaylists->bindValue(':nb_tracks', $nb_tracktotal);
        $playlist_artwork_path = getPlaylistArtwork($w,  $playlist_uri, true, true);
        $stmtUpdatePlaylists->bindValue(':playlist_artwork_path', $playlist_artwork_path);
        $stmtUpdatePlaylists->bindValue(':uri', $playlist_uri);
        $stmtUpdatePlaylists->execute();

        foreach ($savedPlaylistTracks as $item) {
            $track = $item->track;

            if($track->uri == 'spotify:track:null') {
                // skip
                $nb_track++;
                continue;
            }

            if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                $playable = 1;
            } else {
                $playable = 0;
            }
            $artists = $track->artists;
            $artist = $artists[0];
            $album = $track->album;

            //
            // Download artworks
            $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
            $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
            $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);


            $stmtTrack->bindValue(':mymusic', 0);
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
            $stmtTrack->bindValue(':playlist_name', escapeQuery($playlist_name));
            $stmtTrack->bindValue(':playlist_uri', $playlist_uri);
            $stmtTrack->bindValue(':playable', $playable);
            $stmtTrack->bindValue(':added_at', $item->added_at);
            $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
            $stmtTrack->execute();

            $nb_track++;
            if ($nb_track % 10 === 0) {
                $w->write('Update Playlist▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
            }
        }


        $getCount = 'select count(distinct uri) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_tracks = $stmt->fetch();

        $getCount = 'select count(distinct uri) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_tracks = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_artists = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_artists = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_albums = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_albums = $stmt->fetch();

        $getCount = 'select count(*) from playlists';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $playlists_count = $stmt->fetch();

        $insertCounter = "insert into counters values (:all_tracks,:mymusic_tracks,:all_artists,:mymusic_artists,:all_albums,:mymusic_albums,:playlists)";
        $stmt = $db->prepare($insertCounter);

        $stmt->bindValue(':all_tracks', $all_tracks[0]);
        $stmt->bindValue(':mymusic_tracks', $mymusic_tracks[0]);
        $stmt->bindValue(':all_artists', $all_artists[0]);
        $stmt->bindValue(':mymusic_artists', $mymusic_artists[0]);
        $stmt->bindValue(':all_albums', $all_albums[0]);
        $stmt->bindValue(':mymusic_albums', $mymusic_albums[0]);
        $stmt->bindValue(':playlists', $playlists_count[0]);
        $stmt->execute();

        $elapsed_time = time() - $words[3];

        //displayNotificationWithArtwork("\nPlaylist " . $playlist_name . " updated (" . $nb_track . " tracks) - took " . beautifyTime($elapsed_time), $playlist_artwork_path);

        unlink($w->data() . "/update_library_in_progress");
        unlink($w->data() . '/library_old.db');
        rename($w->data() . '/library_new.db', $w->data() . '/library.db');
        unlink($w->data() . '/settings_tmp.db');
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        unlink($w->data() . "/update_library_in_progress");
        return;
    }

}


/**
 * refreshLibrary function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function refreshLibrary($w)
{
    // Note that a user's collaborative playlists are not currently retrievable.
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }
    touch($w->data() . "/update_library_in_progress");
    $w->write('InitRefreshLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');

    //
    // Read settings from a copy of DB
    //

    copy($w->data() . '/settings.db', $w->data() . '/settings_tmp.db');
    $dbfile = $w->data() . '/settings_tmp.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select country_code,userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $country_code = $setting[0];
        $userid = $setting[1];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        unlink($w->data() . "/update_library_in_progress");
        return false;
    }


    $words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    $nb_playlist = 0;
    if (file_exists($w->data() . '/library.db')) {
        rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    }
    copy($w->data() . '/library_old.db', $w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';

	$nb_added_playlists = 0;
	$nb_removed_playlists = 0;
	$nb_updated_playlists = 0;

    try {
        $db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$db->exec("drop table counters");
		$db->exec("create table counters (all_tracks int, mymusic_tracks int, all_artists int, mymusic_artists int, all_albums int, mymusic_albums int, playlists int)");

        $getPlaylists = "select * from playlists where uri=:uri";
        $stmtGetPlaylists = $db->prepare($getPlaylists);

        $insertPlaylist = "insert into playlists values (:uri,:name,:count_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser)";
        $stmtPlaylist = $db->prepare($insertPlaylist);

        $insertTrack = "insert into tracks values (:mymusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration_ms)";
        $stmtTrack = $db->prepare($insertTrack);

        $deleteFromTracks = "delete from tracks where playlist_uri=:playlist_uri";
        $stmtDeleteFromTracks = $db->prepare($deleteFromTracks);

        $updatePlaylistsNbTracks = "update playlists set nb_tracks=:nb_tracks where uri=:uri";
        $stmtUpdatePlaylistsNbTracks = $db->prepare($updatePlaylistsNbTracks);

        $deleteFromTracksYourMusic = "delete from tracks where mymusic=:mymusic";
        $stmtDeleteFromTracksYourMusic = $db->prepare($deleteFromTracksYourMusic);

		$savedListPlaylist = array();
        try {
            $offsetGetUserPlaylists = 0;
            $limitGetUserPlaylists = 50;
            do {
                $userPlaylists = $api->getUserPlaylists($userid, array(
                    'limit' => $limitGetUserPlaylists,
                    'offset' => $offsetGetUserPlaylists
                ));

                $nb_playlist_total = $userPlaylists->total;

                foreach ($userPlaylists->items as $playlist) {
                    $savedListPlaylist[] = $playlist;
                    $nb_tracktotal += $tracks->total;
                }

                $offsetGetUserPlaylists += $limitGetUserPlaylists;

            } while ($offsetGetUserPlaylists < $userPlaylists->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getUserPlaylists): (exception " . print_r($e) . ")";
            unlink($w->data() . "/update_library_in_progress");
            unlink($w->data() . "/library_new.db");
            unlink($w->data() . '/settings_tmp.db');
        }

		// consider Your Music as a playlist for progress bar
		$nb_playlist_total++;

		foreach ($savedListPlaylist as $playlist) {

            $tracks = $playlist->tracks;
            $owner = $playlist->owner;

            $nb_playlist++;
            $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');

            // Loop on existing playlists in library
            $stmtGetPlaylists->bindValue(':uri', $playlist->uri);
            $stmtGetPlaylists->execute();

            $noresult = true;
            while ($playlists = $stmtGetPlaylists->fetch()) {
                $noresult = false;
                break;
            }

			// Playlist does not exist, add it
            if ($noresult == true) {
	            $nb_added_playlists++;

                $playlist_artwork_path = getPlaylistArtwork($w,  $playlist->uri, true, true);

                if ("-" . $owner->id . "-" == "-" . $userid . "-") {
                    $ownedbyuser = 1;
                } else {
                    $ownedbyuser = 0;
                }

                $stmtPlaylist->bindValue(':uri', $playlist->uri);
                $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
                $stmtPlaylist->bindValue(':count_tracks', $tracks->total);
                $stmtPlaylist->bindValue(':owner', $owner->id);
                $stmtPlaylist->bindValue(':username', $owner->id);
                $stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
                $stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
                $stmtPlaylist->execute();

                try {
                    $offsetGetUserPlaylistTracks = 0;
                    $limitGetUserPlaylistTracks = 100;
                    do {
                        $userPlaylistTracks = $api->getUserPlaylistTracks($owner->id, $playlist->id, array(
                            'fields' => array(
                                'total',
                                'items(added_at)',
                                'items.track(available_markets,duration_ms,uri,popularity,name)',
                                'items.track.album(album_type,images,uri,name)',
                                'items.track.artists(name,uri)'
                            ),
                            'limit' => $limitGetUserPlaylistTracks,
                            'offset' => $offsetGetUserPlaylistTracks
                        ));

                        foreach ($userPlaylistTracks->items as $item) {
                            $track = $item->track;
		                    if($track->uri == 'spotify:track:null') {
			                    // skip
			                    $nb_track++;
			                    continue;
		                    }
                            if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                                $playable = 1;
                            } else {
                                $playable = 0;
                            }
                            $artists = $track->artists;
                            $artist = $artists[0];
                            $album = $track->album;

                            //
                            // Download artworks
                            $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
                            $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
                            $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);

                            $stmtTrack->bindValue(':mymusic', 0);
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
                            $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
                            $stmtTrack->execute();
                        }

                        $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

                    } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
                } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                    echo "Error(getUserPlaylistTracks): playlist id " . $playlist->id . " (exception " . print_r($e) . ")";
                    handleSpotifyWebAPIException($w);
                    return;
                }

                displayNotificationWithArtwork('Added playlist ' . $playlist->name, $playlist_artwork_path);
            } else {
                // number of tracks has changed or playlist name has changed
                // update the playlist
                if ($playlists[2] != $tracks->total || $playlists[1] != escapeQuery($playlist->name)) {
	                $nb_updated_playlists++;

	                if($playlists[1] != escapeQuery($playlist->name)) {
	                    $updatePlaylistsName = "update playlists set name=:name where uri=:uri";
						$stmtUpdatePlaylistsName = $db->prepare($updatePlaylistsName);

                        $stmtUpdatePlaylistsName->bindValue(':name', escapeQuery($playlist->name));
                        $stmtUpdatePlaylistsName->bindValue(':uri', $playlist->uri);
                        $stmtUpdatePlaylistsName->execute();
	                }

                    $stmtDeleteFromTracks->bindValue(':playlist_uri', $playlist->uri);
                    $stmtDeleteFromTracks->execute();

                    $tmp = explode(':', $playlist->uri);

                    try {
                        $offsetGetUserPlaylistTracks = 0;
                        $limitGetUserPlaylistTracks = 100;
                        do {
                            $userPlaylistTracks = $api->getUserPlaylistTracks($tmp[2], $tmp[4], array(
                                'fields' => array(
                                    'total',
                                    'items(added_at)',
                                    'items.track(available_markets,duration_ms,uri,popularity,name)',
                                    'items.track.album(album_type,images,uri,name)',
                                    'items.track.artists(name,uri)'
                                ),
                                'limit' => $limitGetUserPlaylistTracks,
                                'offset' => $offsetGetUserPlaylistTracks
                            ));

                            $stmtUpdatePlaylistsNbTracks->bindValue(':nb_tracks', $userPlaylistTracks->total);
                            $stmtUpdatePlaylistsNbTracks->bindValue(':uri', $playlist->uri);
                            $stmtUpdatePlaylistsNbTracks->execute();

                            foreach ($userPlaylistTracks->items as $item) {
                                $track = $item->track;
			                    if($track->uri == 'spotify:track:null') {
				                    // skip
				                    $nb_track++;
				                    continue;
			                    }
                                if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                                    $playable = 1;
                                } else {
                                    $playable = 0;
                                }
                                $artists = $track->artists;
                                $artist = $artists[0];
                                $album = $track->album;

                                //
                                // Download artworks
                                $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
                                $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
                                $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);


                                $stmtTrack->bindValue(':mymusic', 0);
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
                                $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
                                $stmtTrack->execute();
                            }

                            $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

                        } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
                    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                        echo "Error(getUserPlaylistTracks): playlist id " . $tmp[4] . " (exception " . print_r($e) . ")";
                        handleSpotifyWebAPIException($w);
                        return;
                    }
                    displayNotificationWithArtwork('Updated playlist ' . $playlist->name, getPlaylistArtwork($w,  $playlist->uri, true));
                } else {
                    continue;
                }
            }
        }

        // check for deleted playlists
        $getPlaylists = "select * from playlists";
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
	            $nb_removed_playlists++;

                $deleteFromPlaylist = "delete from playlists where uri=:uri";
                $stmtDelete = $db->prepare($deleteFromPlaylist);
                $stmtDelete->bindValue(':uri', $playlist_in_db[0]);
                $stmtDelete->execute();

                $deleteFromTracks = "delete from tracks where playlist_uri=:uri";
                $stmtDelete = $db->prepare($deleteFromTracks);
                $stmtDelete->bindValue(':uri', $playlist_in_db[0]);
                $stmtDelete->execute();
                displayNotificationWithArtwork('Removed playlist ' . $playlist_in_db[1], getPlaylistArtwork($w,  $playlist_in_db[0], false));
            }
        }

		// check for update to Your Music
        $savedMySavedTracks = array();
        try {
            $offsetGetMySavedTracks = 0;
            $limitGetMySavedTracks = 50;
            do {
                $userMySavedTracks = $api->getMySavedTracks(array(
                    'limit' => $limitGetMySavedTracks,
                    'offset' => $offsetGetMySavedTracks
                ));

				foreach ($userMySavedTracks->items as $item) {
					$savedMySavedTracks[] = $item;
				}

                $offsetGetMySavedTracks += $limitGetMySavedTracks;
            } while ($offsetGetMySavedTracks < $userMySavedTracks->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getMySavedTracks): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }
		// get current number of track in Your Music
	    $getCount = 'select count(distinct uri) from tracks where mymusic=1';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $mymusic_tracks = $stmt->fetch();
	    $your_music_updated=false;
		if($mymusic_tracks[0] != count($savedMySavedTracks)) {

			$your_music_updated=true;
			// Your Music has changed, update it
            $nb_playlist++;
            $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');

			// delete tracks
        	$stmtDeleteFromTracksYourMusic->bindValue(':mymusic', 1);
			$stmtDeleteFromTracksYourMusic->execute();

            foreach ($savedMySavedTracks as $item) {
                $track = $item->track;
                if($track->uri == 'spotify:track:null') {
                    // skip
                    $nb_track++;
                    continue;
                }
                if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                    $playable = 1;
                } else {
                    $playable = 0;
                }
                $artists = $track->artists;
                $artist = $artists[0];
                $album = $track->album;

                //
                // Download artworks
                $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
                $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
                $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);


                $stmtTrack->bindValue(':mymusic', 1);
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
                $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
                $stmtTrack->execute();
            }
        }

	    $getCount = 'select count(distinct uri) from tracks';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $all_tracks = $stmt->fetch();

	    $getCount = 'select count(distinct uri) from tracks where mymusic=1';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $mymusic_tracks = $stmt->fetch();

	    $getCount = 'select count(distinct artist_name) from tracks';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $all_artists = $stmt->fetch();

	    $getCount = 'select count(distinct artist_name) from tracks where mymusic=1';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $mymusic_artists = $stmt->fetch();

	    $getCount = 'select count(distinct album_name) from tracks';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $all_albums = $stmt->fetch();

	    $getCount = 'select count(distinct album_name) from tracks where mymusic=1';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $mymusic_albums = $stmt->fetch();

	    $getCount = 'select count(*) from playlists';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $playlists_count = $stmt->fetch();

	    $insertCounter = "insert into counters values (:all_tracks,:mymusic_tracks,:all_artists,:mymusic_artists,:all_albums,:mymusic_albums,:playlists)";
	    $stmt = $db->prepare($insertCounter);

	    $stmt->bindValue(':all_tracks', $all_tracks[0]);
	    $stmt->bindValue(':mymusic_tracks', $mymusic_tracks[0]);
	    $stmt->bindValue(':all_artists', $all_artists[0]);
	    $stmt->bindValue(':mymusic_artists', $mymusic_artists[0]);
	    $stmt->bindValue(':all_albums', $all_albums[0]);
	    $stmt->bindValue(':mymusic_albums', $mymusic_albums[0]);
	    $stmt->bindValue(':playlists', $playlists_count[0]);
	    $stmt->execute();

        $elapsed_time = time() - $words[3];
        $changedPlaylists = false;
        $changedYourMusic = false;
        if($nb_added_playlists>0) {
	        $addedMsg = $nb_added_playlists . ' added';
	        $changedPlaylists = true;
        }

        if($nb_removed_playlists>0) {
	        $removedMsg = $nb_removed_playlists . ' removed';
	        $changedPlaylists = true;
        }

        if($nb_updated_playlists>0) {
	        $updatedMsg = $nb_updated_playlists . ' updated';
	        $changedPlaylists = true;
        }

        if($your_music_updated) {
	       $yourMusicMsg = ' - Your Music: updated';
	       $changedYourMusic = true;
        }

        if($changedPlaylists && $changedYourMusic) {
	        $message = ' (Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg . ' ' . $yourMusicMsg . ')';
        } elseif($changedPlaylists) {
	        $message = ' (Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg . ')';
        } elseif($changedYourMusic) {
	        $message = ' (' . $yourMusicMsg . ')'; ;
        } else {
	        $message = ' (no change)';
        }

        displayNotificationWithArtwork("Library refreshed" . $message . " - took " . beautifyTime($elapsed_time),'./images/update.png');

        unlink($w->data() . "/update_library_in_progress");
        unlink($w->data() . '/library_old.db');
        rename($w->data() . '/library_new.db', $w->data() . '/library.db');
        unlink($w->data() . '/settings_tmp.db');
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return;
    }
}

/**
 * updateYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function updateYourMusic($w)
{

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
        return;
    }

    touch($w->data() . "/update_library_in_progress");
    $w->write('InitMyMusic▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');

    //
    // Read settings from a copy of DB
    //

    copy($w->data() . '/settings.db', $w->data() . '/settings_tmp.db');
    $dbfile = $w->data() . '/settings_tmp.db';
    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select country_code,userid from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
        $country_code = $setting[0];
        $userid = $setting[1];
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }


    $words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    $nb_playlist = 0;
    if (file_exists($w->data() . '/library.db')) {
        rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    }
    copy($w->data() . '/library_old.db', $w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';

    try {
        $db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $db->exec("drop table counters");
        $db->exec("create table counters (all_tracks int, mymusic_tracks int, all_artists int, mymusic_artists int, all_albums int, mymusic_albums int, playlists int)");

        $insertTrack = "insert into tracks values (:mymusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration_ms)";
        $stmtTrack = $db->prepare($insertTrack);

        $deleteFromTracks = "delete from tracks where mymusic=:mymusic";
        $stmtDeleteFromTracks = $db->prepare($deleteFromTracks);
        $stmtDeleteFromTracks->bindValue(':mymusic', 1);
        $stmtDeleteFromTracks->execute();


        $nb_track = 0;
        try {
            $offsetGetMySavedTracks = 0;
            $limitGetMySavedTracks = 50;
            do {
                $userMySavedTracks = $api->getMySavedTracks(array(
                    'limit' => $limitGetMySavedTracks,
                    'offset' => $offsetGetMySavedTracks
                ));

                foreach ($userMySavedTracks->items as $item) {
                    $track = $item->track;
                    if($track->uri == 'spotify:track:null') {
	                    // skip
	                    $nb_track++;
	                    continue;
                    }
                    if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
                        $playable = 1;
                    } else {
                        $playable = 0;
                    }
                    $artists = $track->artists;
                    $artist = $artists[0];
                    $album = $track->album;

                    //
                    // Download artworks
                    $track_artwork_path = getTrackOrAlbumArtwork($w,  $track->uri, true);
                    $artist_artwork_path = getArtistArtwork($w,  $artist->name, true);
                    $album_artwork_path = getTrackOrAlbumArtwork($w,  $album->uri, true);


                    $stmtTrack->bindValue(':mymusic', 1);
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
                    $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
                    $stmtTrack->execute();


                    $nb_track++;
                    if ($nb_track == 0) {
                        $w->write('Update Your Music▹0▹' . $userMySavedTracks->total . '▹' . $words[3], 'update_library_in_progress');
                    } else {
                        if ($nb_track % 10 === 0) {
                            $w->write('Update Your Music▹' . $nb_track . '▹' . $userMySavedTracks->total . '▹' . $words[3], 'update_library_in_progress');
                        }
                    }

                }

                $offsetGetMySavedTracks += $limitGetMySavedTracks;

            } while ($offsetGetMySavedTracks < $userMySavedTracks->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getMySavedTracks): (exception " . print_r($e) . ")";
            handleSpotifyWebAPIException($w);
            return false;
        }

        $getCount = 'select count(distinct uri) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_tracks = $stmt->fetch();

        $getCount = 'select count(distinct uri) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_tracks = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_artists = $stmt->fetch();

        $getCount = 'select count(distinct artist_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_artists = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $all_albums = $stmt->fetch();

        $getCount = 'select count(distinct album_name) from tracks where mymusic=1';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $mymusic_albums = $stmt->fetch();

        $getCount = 'select count(*) from playlists';
        $stmt = $db->prepare($getCount);
        $stmt->execute();
        $playlists_count = $stmt->fetch();

        $insertCounter = "insert into counters values (:all_tracks,:mymusic_tracks,:all_artists,:mymusic_artists,:all_albums,:mymusic_albums,:playlists)";
        $stmt = $db->prepare($insertCounter);

        $stmt->bindValue(':all_tracks', $all_tracks[0]);
        $stmt->bindValue(':mymusic_tracks', $mymusic_tracks[0]);
        $stmt->bindValue(':all_artists', $all_artists[0]);
        $stmt->bindValue(':mymusic_artists', $mymusic_artists[0]);
        $stmt->bindValue(':all_albums', $all_albums[0]);
        $stmt->bindValue(':mymusic_albums', $mymusic_albums[0]);
        $stmt->bindValue(':playlists', $playlists_count[0]);
        $stmt->execute();

        $elapsed_time = time() - $words[3];
        //displayNotification("Your Music updated - took " . beautifyTime($elapsed_time));

        unlink($w->data() . "/update_library_in_progress");
        unlink($w->data() . '/library_old.db');
        rename($w->data() . '/library_new.db', $w->data() . '/library.db');
        unlink($w->data() . '/settings_tmp.db');
    } catch (PDOException $e) {
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return;
    }
}


/**
 * handleDbIssuePdoXml function.
 *
 * @access public
 * @param mixed $dbhandle
 * @return void
 */
function handleDbIssuePdoXml($dbhandle)
{
    $w = new Workflows('com.vdesabou.spotify.mini.player');
    $w->result(uniqid(), '', 'Database Error: ' . $dbhandle->errorInfo()[0] . ' ' . $dbhandle->errorInfo()[1] . ' ' . $dbhandle->errorInfo()[2], '', './images/warning.png', 'no', null, '');
    $w->result(uniqid(), '', 'There is a problem with the library, try to re-create it.', 'Select Re-Create Library library below', './images/warning.png', 'no', null, '');
    $w->result(uniqid(), serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'update_library' /* other_action */, '' /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Re-Create Library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
    echo $w->toxml();
}


/**
 * handleSpotifyWebAPIException function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function handleSpotifyWebAPIException($w) {
	// set back old library
    if (file_exists($w->data() . "/library_new.db")) {
	    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    }

    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }

    if (file_exists($w->data() . '/settings_tmp.db')) {
        unlink($w->data() . '/settings_tmp.db');
    }

	displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png');
}
/**
 * handleDbIssuePdoEcho function.
 *
 * @access public
 * @param mixed $dbhandle
 * @param mixed $w
 * @return void
 */
function handleDbIssuePdoEcho($dbhandle,$w)
{
    echo 'Database Error: ' . $dbhandle->errorInfo()[0] . ' ' . $dbhandle->errorInfo()[1] . ' ' . $dbhandle->errorInfo()[2];
    if (file_exists($w->data() . '/update_library_in_progress')) {
        unlink($w->data() . '/update_library_in_progress');
    }

	// set back old library
    if (file_exists($w->data() . "/library_new.db")) {
	    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    }

    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }

    if (file_exists($w->data() . '/settings_tmp.db')) {
        unlink($w->data() . '/settings_tmp.db');
    }

    displayNotificationWithArtwork("Error: DB error " . $dbhandle->errorInfo()[2], './images/warning.png');
}


/**
 * floatToSquares function.
 *
 * @access public
 * @param mixed $decimal
 * @return void
 */
function floatToSquares($decimal)
{
    $squares = ($decimal < 1) ? floor($decimal * 10) : 10;
    return str_repeat("◼︎", $squares) . str_repeat("◻︎", 10 - $squares);
}


/**
 * Mulit-byte Unserialize
 *
 * UTF-8 will screw up a serialized string
 *
 * @access private
 * @param string
 * @return string
 */
// thanks to http://stackoverflow.com/questions/2853454/php-unserialize-fails-with-non-encoded-characters
function mb_unserialize($string)
{
    $string = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $string);
    return unserialize($string);
}


/*

This function was mostly taken from SpotCommander.

SpotCommander is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

SpotCommander is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with SpotCommander.  If not, see <http://www.gnu.org/licenses/>.

Copyright 2013 Ole Jon Bjørkum

 * getLyrics function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist
 * @param mixed $title
 * @return lyrics
 */
function getLyrics($w, $artist, $title)
{
    $query_artist = $artist;
    $query_title = $title;

    if (stristr($query_artist, 'feat.')) {
        $query_artist = stristr($query_artist, 'feat.', true);
    } elseif (stristr($query_artist, 'featuring')) {
        $query_artist = stristr($query_artist, 'featuring', true);
    } elseif (stristr($query_title, ' con ')) {
        $query_title = stristr($query_title, ' con ', true);
    } elseif (stristr($query_artist, ' & ')) {
        $query_artist = stristr($query_artist, ' & ', true);
    }

    $query_artist = str_replace('&', 'and', $query_artist);
    $query_artist = str_replace('$', 's', $query_artist);
    $query_artist = strip_string(trim($query_artist));
    $query_artist = str_replace(' - ', '-', $query_artist);
    $query_artist = str_replace(' ', '-', $query_artist);

    $query_title = str_ireplace(array('acoustic version', 'new album version', 'original album version', 'album version', 'bonus track', 'clean version', 'club mix', 'demo version', 'extended mix', 'extended outro', 'extended version', 'extended', 'explicit version', 'explicit', '(live)', '- live', 'live version', 'lp mix', '(original)', 'original edit', 'original mix edit', 'original version', '(radio)', 'radio edit', 'radio mix', 'remastered version', 're-mastered version', 'remastered digital version', 're-mastered digital version', 'remastered', 'remaster', 'remixed version', 'remix', 'single version', 'studio version', 'version acustica', 'versión acústica', 'vocal edit'), '', $query_title);

    if (stristr($query_title, 'feat.')) {
        $query_title = stristr($query_title, 'feat.', true);
    } elseif (stristr($query_title, 'featuring')) {
        $query_title = stristr($query_title, 'featuring', true);
    } elseif (stristr($query_title, ' con ')) {
        $query_title = stristr($query_title, ' con ', true);
    } elseif (stristr($query_title, '(includes')) {
        $query_title = stristr($query_title, '(includes', true);
    } elseif (stristr($query_title, '(live at')) {
        $query_title = stristr($query_title, '(live at', true);
    } elseif (stristr($query_title, 'revised')) {
        $query_title = stristr($query_title, 'revised', true);
    } elseif (stristr($query_title, '(19')) {
        $query_title = stristr($query_title, '(19', true);
    } elseif (stristr($query_title, '(20')) {
        $query_title = stristr($query_title, '(20', true);
    } elseif (stristr($query_title, '- 19')) {
        $query_title = stristr($query_title, '- 19', true);
    } elseif (stristr($query_title, '- 20')) {
        $query_title = stristr($query_title, '- 20', true);
    }

    $query_title = str_replace('&', 'and', $query_title);
    $query_title = str_replace('$', 's', $query_title);
    $query_title = strip_string(trim($query_title));
    $query_title = str_replace(' - ', '-', $query_title);
    $query_title = str_replace(' ', '-', $query_title);
    $query_title = rtrim($query_title, '-');

    $uri = strtolower('http://www.lyrics.com/' . $query_title . '-lyrics-' . $query_artist . '.html');

    $error = false;
    $no_match = false;

    $file = $w->request($uri);

    preg_match('/<div id="lyric_space">(.*?)<\/div>/s', $file, $lyrics);

    $lyrics = (empty($lyrics[1])) ? '' : $lyrics[1];

    if (empty($file)) {
        $error = true;
    } elseif (empty($lyrics) || stristr($lyrics, 'we do not have the lyric for this song') || stristr($lyrics, 'lyrics are currently unavailable') || stristr($lyrics, 'your name will be printed as part of the credit')) {
        $no_match = true;
    } else {
        if (strstr($lyrics, 'Ã') && strstr($lyrics, '©')) $lyrics = utf8_decode($lyrics);

        $lyrics = trim(str_replace('<br />', '<br>', $lyrics));

        if (strstr($lyrics, '<br>---')) $lyrics = strstr($lyrics, '<br>---', true);
    }

    if ($error) {
        displayNotificationWithArtwork("Timeout or failure. Try again",'./images/warning.png');
    } elseif ($no_match) {
        displayNotificationWithArtwork("Sorry there is no match for this track",'./images/warning.png');
    } else {
        $lyrics = strip_tags($lyrics);

        //$lyrics = (strlen($lyrics) > 1303) ? substr($lyrics,0,1300).'...' : $lyrics;

        if ($lyrics == "") {
            displayNotificationWithArtwork("Sorry there is no match for this track",'./images/warning.png');
            return false;
        } else {
            return "$lyrics";
        }
    }
}


/**
 * strip_string function.
 *
 * @access public
 * @param mixed $string
 * @return void
 */
function strip_string($string)
{
    return preg_replace('/[^a-zA-Z0-9-\s]/', '', $string);
}


/**
 * checkForUpdate function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $last_check_update_time
 * @return void
 */
function checkForUpdate($w, $last_check_update_time, $dbsettings)
{

    if (time() - $last_check_update_time > 86400) {
        // update last_check_update_time
        $setSettings = "update settings set last_check_update_time=" . time();
        $dbsettings->exec($setSettings);

        if (!$w->internet()) {
            displayNotificationWithArtwork("Check for update error:
No internet connection", './images/warning.png');
            return;
        }

        // get local information
        if (!file_exists('./packal/package.xml')) {
            displayNotificationWithArtwork("Error: this release has not been downloaded from Packal",'./images/warning.png');
            return 1;
        }
        $xml = $w->read('./packal/package.xml');
        $workflow = new SimpleXMLElement($xml);
        $local_version = $workflow->version;
        $remote_json = "https://raw.githubusercontent.com/vdesabou/alfred-spotify-mini-player/master/remote.json";

        // get remote information
        $jsonDataRemote = $w->request($remote_json);

        if (empty($jsonDataRemote)) {
            displayNotificationWithArtwork("Check for update error:
the export.json " . $remote_json . " file cannot be found",'./images/warning.png');
            return 1;
        }

        $json = json_decode($jsonDataRemote, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $download_url = $json['download_url'];
            $remote_version = $json['version'];
            $description = $json['description'];

            if ($local_version < $remote_version) {

                $workflow_file_name = exec('printf $HOME') . '/Downloads/spotify-mini-player-' . $remote_version . '.alfredworkflow';
                $fp = fopen($workflow_file_name, 'w+');
                $options = array(
                    CURLOPT_FILE => $fp
                );
                $w->request("$download_url", $options);

                return array($remote_version, $workflow_file_name, $description);
            }

        } else {
            displayNotificationWithArtwork("Check for update error:
remote.json error",'./images/warning.png');
            return 1;
        }

    }
}


/**
 * doWebApiRequest function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $url
 * @return void
 */
function doWebApiRequest($w, $url)
{

    $json = $w->request($url);

    if (empty($json)) {
        $w->result(null, '', "Error: Spotify WEB API returned empty result", $url, './images/warning.png', 'no', null, '');
        echo $w->toxml();
        exit;
    }

    $json = json_decode($json);
    switch (json_last_error()) {
        case JSON_ERROR_DEPTH:
            $w->result(null, '', "There was an error when retrieving online information", "Maximum stack depth exceeded", './images/warning.png', 'no', null, '');
            echo $w->toxml();
            exit;
        case JSON_ERROR_CTRL_CHAR:
            $w->result(null, '', "There was an error when retrieving online information", "Unexpected control character found", './images/warning.png', 'no', null, '');
            echo $w->toxml();
            exit;
        case JSON_ERROR_SYNTAX:
            $w->result(null, '', "There was an error when retrieving online information", "Syntax error, malformed JSON", './images/warning.png', 'no', null, '');
            echo $w->toxml();
            exit;
        case JSON_ERROR_NONE:
            return $json;
    }

    $w->result(null, '', "Error: Spotify WEB API returned error " . json_last_error(), "Try again or report to author", './images/warning.png', 'no', null, '');
    echo $w->toxml();
    exit;
}


/**
 * killUpdate function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function killUpdate($w)
{
    if (file_exists($w->data() . '/update_library_in_progress')) {
        unlink($w->data() . '/update_library_in_progress');
    }

	// set back old library
    if (file_exists($w->data() . "/library_new.db")) {
	    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    }

    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }

    if (file_exists($w->data() . '/settings_tmp.db')) {
        unlink($w->data() . '/settings_tmp.db');
    }

    exec("kill -9 $(ps -efx | grep \"php\" | egrep \"update_|php -S localhost:15298|ADDTOPLAYLIST|UPDATE_\" | grep -v grep | awk '{print $2}')");

    displayNotificationWithArtwork("Update library/playlist was killed!", './images/kill.png');
}

/**
 * beautifyTime function.
 *
 * @access public
 * @param mixed $seconds
 * @return void
 */
function beautifyTime($seconds)
{
    $m = floor($seconds / 60);
    $s = $seconds % 60;
    $s = ($s < 10) ? "0$s" : "$s";
    return $m . "m" . $s . "s";
}


/**
 * startswith function.
 *
 * @access public
 * @param mixed $haystack
 * @param mixed $needle
 * @return void
 */
function startswith($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === $needle;
}


?>