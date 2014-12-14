<?php

require_once './src/workflows.php';
require './vendor/autoload.php';


/**
 * displayBiography function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $artist_name
 * @param mixed $other_action
 * @return void
 */
function displayBiography($w,$artist_uri,$artist_name,$other_action) {

    $json = doWebApiRequest($w, 'http://developer.echonest.com/api/v4/artist/biographies?api_key=5EG94BIZEGFEY9AL9&id=' . $artist_uri);
    $response = $json->response;
    PHPRtfLite::registerAutoloader();

    foreach ($response->biographies as $biography) {

        if ($biography->site == "wikipedia") {
            $wikipedia = $biography->text;
        }
        if ($biography->site == "last.fm") {
            $lastfm = $biography->text;
        }
        $default = 'Source: ' . $biography->site . '\n' . $biography->text;
    }

    if ($wikipedia) {
        $text = $wikipedia;
        $artist = $artist_name . ' (Source: Wikipedia)';
    } elseif ($lastfm) {
        $text = $lastfm;
        $artist = $artist_name . ' (Source: Last.FM)';
    } else {
        $text = $default;
        $artist = $artist_name . ' (Source: ' . $biography->site . ')';
    }
    if ($text == "") {
        $text = "No biography found";
        $artist = $artist_name;
    }
    $output = strip_tags($text);

    $file = $w->cache() . '/biography.rtf';

    $rtf = new PHPRtfLite();

    $section = $rtf->addSection();
    // centered text
    $fontTitle = new PHPRtfLite_Font(28, 'Arial', '#000000', '#FFFFFF');
    $parFormatTitle = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_CENTER);
    $section->writeText($artist, $fontTitle, $parFormatTitle);

    $parFormat = new PHPRtfLite_ParFormat();
    $parFormat->setSpaceAfter(4);
    $font = new PHPRtfLite_Font(14, 'Arial', '#000000', '#FFFFFF');
    // write text
    $section->writeText($output, $font, $parFormat);

    $rtf->save($file);
    if ($other_action == 'display_biography') {
        exec("qlmanage -p \"$file\";osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Artist▹" . $artist_uri . "∙" . $artist_name . "▹\"'");
    } else {
        exec("qlmanage -p \"$file\";osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . $artist_name . "\"'");
    }
}


/**
 * searchWebApi function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $country_code
 * @param mixed $query
 * @param mixed $type
 * @return void
 */
function searchWebApi($w,$country_code,$query, $type, $limit = 50) {
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    $results = array();

    try {
        $offsetSearch = 0;
        if($limit != 50) {
	       $limitSearch = $limit;
        } else {
	       $limitSearch = 50;
        }
        do {
            $searchResults = $api->search(	$query,
            								$type,
            								array(
									            'market' => $country_code,
									            'limit' => $limitSearch,
									            'offset' => $offsetSearch
									        ));

			if($type == 'artist') {
				foreach ($searchResults->artists->items as $item) {
				    $results[] = $item;
				}
			} else if($type == 'track') {
				foreach ($searchResults->tracks->items as $item) {
				    $results[] = $item;
				}
			} else if($type == 'album') {
				foreach ($searchResults->albums->items as $item) {
				    $results[] = $item;
				}
			} else if($type == 'playlist') {
				foreach ($searchResults->playlists->items as $item) {
				    $results[] = $item;
				}
			}

            $offsetSearch += $limitSearch;
        } while ($offsetSearch < $searchResults->total);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(search): (exception " . print_r($e) . ")\n";
        handleSpotifyWebAPIException($w);
        return false;
    }
    return $results;
}


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
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }

	$is_alfred_playlist_active = $setting[2];
	$alfred_playlist_uri = $setting[6];
	$alfred_playlist_name = $setting[7];


    if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
        displayNotificationWithArtwork("Error: Alfred Playlist is not set", './images/warning.png');
        return;
    }


    exec("osascript -e 'tell application \"Spotify\" to play track \"$alfred_playlist_uri\"'");

    $playlist_artwork_path = getPlaylistArtwork($w,  $alfred_playlist_uri, true, true);
    displayNotificationWithArtwork('🔈 Alfred Playlist ' . $alfred_playlist_name, $playlist_artwork_path);
}

/**
 * lookupCurrentArtist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function lookupCurrentArtist($w)
{
    // get info on current song
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        $tmp = explode(':', $results[4]);
        if($tmp[1] == 'local') {
        	$artist_uri = getArtistUriFromSearch($w, $results[1]);
        } else {
	        $artist_uri = getArtistUriFromTrack($w, $results[4]);
        }

        if ($artist_uri == false) {
            displayNotificationWithArtwork("Error: cannot get current artist",'./images/warning.png', 'Error!');
            return;
        }
        exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($results[1]) . "\"'");
    } else {
        displayNotificationWithArtwork("Error: No track is playing", './images/warning.png');
    }
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        $tmp = explode(':', $results[4]);
        if($tmp[1] == 'local') {
        	$artist_uri = getArtistUriFromSearch($w, $results[1]);
        } else {
	        $artist_uri = getArtistUriFromTrack($w, $results[4]);
        }
        if ($artist_uri == false) {
            displayNotificationWithArtwork("Error: cannot get current artist",'./images/warning.png', 'Error!');
            return;
        }
        exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
        displayNotificationWithArtwork('🔈 Artist ' . escapeQuery($results[1]), getArtistArtwork($w,  $results[1], true));
    } else {
        displayNotificationWithArtwork("Error: No artist is playing", './images/warning.png');
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        $tmp = explode(':', $results[4]);
        $album_uri = getAlbumUriFromTrack($w, $results[4]);
        if ($album_uri == false) {
	        displayNotificationWithArtwork("Error: cannot get current album",'./images/warning.png', 'Error!');
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        $tmp = explode(':', $results[4]);
        if($tmp[1] == 'local') {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$country_code = $setting[8];

			// local track, look it up online

			$query = 'track:' . strtolower($results[0]) . ' artist:' . strtolower($results[1]);
        	$searchResults = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($searchResults) > 0) {
				// only one track returned
				$track=$searchResults[0];
				$artists = $track->artists;
				$artist = $artists[0];
            	echo "Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name\n";
            	$results[4] = $track->uri;
        	} else {
            	echo "Could not find track: $results[4] / $results[0] / $results[1] \n";
                displayNotificationWithArtwork('Error: local track ' . escapeQuery($results[0]) . ' has not online match','./images/warning.png', 'Error!');
                return;
        	}
        }
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
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }

	$is_alfred_playlist_active = $setting[2];

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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);

		//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }

		$is_alfred_playlist_active = $setting[2];
		$alfred_playlist_uri = $setting[6];
		$alfred_playlist_name = $setting[7];
		$country_code = $setting[8];


        if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
            displayNotificationWithArtwork("Error: Alfred Playlist is not set", './images/warning.png');
            return;
        }

        $tmp = explode(':', $results[4]);
        if($tmp[1] == 'local') {
			// local track, look it up online

			$query = 'track:' . strtolower($results[0]) . ' artist:' . strtolower($results[1]);
        	$searchResults = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($searchResults) > 0) {
				// only one track returned
				$track=$searchResults[0];
				$artists = $track->artists;
				$artist = $artists[0];
            	echo "Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name\n";
            	$results[4] = $track->uri;
        	} else {
            	echo "Could not find track: $results[4] / $results[0] / $results[1] \n";
                displayNotificationWithArtwork('Error: local track ' . escapeQuery($results[0]) . ' has not online match','./images/warning.png', 'Error!');
                return;
        	}
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        $tmp = explode(':', $results[4]);
        if($tmp[1] == 'local') {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$country_code = $setting[8];
			// local track, look it up online

			$query = 'track:' . strtolower($results[0]) . ' artist:' . strtolower($results[1]);
        	$searchResults = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($searchResults) > 0) {
				// only one track returned
				$track=$searchResults[0];
				$artists = $track->artists;
				$artist = $artists[0];
            	echo "Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name\n";
            	$results[4] = $track->uri;
        	} else {
            	echo "Could not find track: $results[4] / $results[0] / $results[1] \n";
                displayNotificationWithArtwork('Error: local track ' . escapeQuery($results[0]) . ' has not online match','./images/warning.png', 'Error!');
                return;
        	}
        }
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
	// check for library DB
	$dbfile = "";
	if (file_exists($w->data() . '/update_library_in_progress')) {
		if (file_exists($w->data() . '/library_old.db')) {
			$dbfile = $w->data() . '/library_old.db';
		}
	} else {
		$dbfile = $w->data() . "/library.db";
	}
	if ($dbfile == "") {
	    return false;
	}

    //
    // Get random track from DB
    //
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


	// in case of library update, we don't want to use settings.db
	// in order to avoid clash
	$update_in_progress = false;
	if (file_exists($w->data() . '/update_library_in_progress')) {
		$update_in_progress = true;
	 	//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }
		$oauth_client_id = $setting[11];
		$oauth_client_secret = $setting[12];
		$oauth_redirect_uri = $setting[13];
		$oauth_access_token = $setting[14];
		$oauth_expires = $setting[15];
		$oauth_refresh_token = $setting[16];
	} else {

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
	}
	
    $session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);
    $session->setRefreshToken($oauth_refresh_token);
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    // Check if refresh token necessary
    if (time() - $oauth_expires > 3100) {
        if ($session->refreshToken()) {

            $oauth_access_token = $session->getAccessToken();

			if($update_in_progress == false) {
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
			}
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    try {
        $tmp = explode(':', $track_uri);

        if($tmp[1] == 'local') {
			// local track, look it up online
			// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
			// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

			$query = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
        	$results = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($results) > 0) {
				// only one track returned
				$track=$results[0];
				$artists = $track->artists;
				$artist = $artists[0];
				return $artist->uri;

        	} else {
            	echo "Could not find artist from uri: $track_uri\n";
                return false;
        	}
        }
        $track = $api->getTrack($tmp[2]);
        $artists = $track->artists;
        $artist = $artists[0];

        return $artist->uri;
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getArtistUriFromTrack): (exception " . print_r($e) . ")";
        handleSpotifyWebAPIException($w);
    }
    return false;
}
/**
 * getArtistUriFromSearch function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_name
 * @param mixed $country_code
 * @return void
 */
function getArtistUriFromSearch($w, $artist_name, $country_code = '')
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

	if($artist_name == '') {
		return false;
	}
	if($country_code == '') {
		//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }
		$country_code = $setting[8];

	}
	$searchResults = searchWebApi($w,$country_code,$artist_name, 'artist', 1);

	if(count($searchResults) > 0) {
		// only one artist returned
		$artist=$searchResults[0];
	} else {
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    try {
        $tmp = explode(':', $track_uri);

        if($tmp[1] == 'local') {
			// local track, look it up online
			// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
			// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

			$query = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
        	$results = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($results) > 0) {
				// only one track returned
				$track=$results[0];
				$album = $track->album;
				return $album->uri;

        	} else {
            	echo "Could not find album from uri: $track_uri\n";
                return false;
        	}
        }

        $track = $api->getTrack($tmp[2]);
        $album = $track->album;
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(getAlbumUriFromTrack): (exception " . print_r($e) . ")\n";
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    try {
        $tmp = explode(':', $playlist_uri);
        $emptytracks = array();
        $api->replacePlaylistTracks($tmp[2], $tmp[4], $emptytracks);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(clearPlaylist): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")\n";
        handleSpotifyWebAPIException($w);
        return false;
    }

    // refresh library
    refreshLibrary($w);

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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }

	$userid = $setting[18];

    try {
        $json = $api->createUserPlaylist($userid, array(
            'name' => $playlist_name,
            'public' => false
        ));
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        echo "Error(createUserPlaylist): createUserPlaylist " . $playlist_name . " (exception " . print_r($e) . ")\n";
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        createRadioArtistPlaylist($w, $results[1]);
    } else {
        displayNotificationWithArtwork("Error: cannot get current artist",'./images/warning.png', 'Error!');
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }

	$radio_number_tracks = $setting[3];
	$userid = $setting[18];
	$echonest_api_key = $setting[19];



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
            echo "Error(createUserPlaylist): radio artist " . $artist_name . " (exception " . print_r($e) . ")\n";
            handleSpotifyWebAPIException($w);
            return false;
        }

        $ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
        if (is_numeric($ret) && $ret > 0) {
            refreshLibrary($w);
            return;
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotificationWithArtwork('Error: Playlist ' . $json->name . ' cannot be added','./images/warning.png', 'Error!');
            return;
        } else {
			displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
            return;
        }
    } else {
        displayNotificationWithArtwork('Error: track was not found in Echo Nest','./images/warning.png', 'Error!');
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        createRadioSongPlaylist($w, $results[0], $results[4], $results[1]);
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png', 'Error!');
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }

	$radio_number_tracks = $setting[3];
	$country_code = $setting[8];
	$userid = $setting[18];
	$echonest_api_key = $setting[19];

    $tmp = explode(':', $track_uri);
    if($tmp[1] == 'local') {
		// local track, look it up online
		// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
		// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

		$query = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
    	$results = searchWebApi($w,$country_code,$query, 'track', 1);

    	if(count($results) > 0) {
			// only one track returned
			$track=$results[0];
			$track_uri = $track->uri;
    	} else {
        	echo "Could not find track from uri: $track_uri\n";
            return false;
    	}
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
            echo "Error(createUserPlaylist): radio song " . escapeQuery($track_name) . " (exception " . print_r($e) . ")\n";
            handleSpotifyWebAPIException($w);
            return false;
        }

        $ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
        if (is_numeric($ret) && $ret > 0) {
            refreshLibrary($w);
            return;
        } else if (is_numeric($ret) && $ret == 0) {
            displayNotificationWithArtwork('Error: Playlist ' . $json->name . ' cannot be added','./images/warning.png', 'Error!');
            return;
        } else {
			displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
            return;
        }
    } else {
        displayNotificationWithArtwork('Error: track was not found in Echo Nest','./images/warning.png', 'Error!');
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
        echo "Error(getThePlaylistTracks): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")\n";
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
        echo "Error(getTheAlbumTracks): (exception " . print_r($e) . ")\n";
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
 * getTheNewReleases function.
 * 
 * @access public
 * @param mixed $w
 * @param mixed $country_code
 * @param mixed $max_results
 * @return void
 */
function getTheNewReleases($w, $country_code, $max_results = 50)
{
    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    $album_ids = array();

    try {
        $tmp = explode(':', $artist_uri);
        $offsetGetNewReleases = 0;
        $limitGetNewReleases = 50;
        do {
            $newReleasesAlbums = $api->getNewReleases(array(
									            'country' => $country_code,
									            'limit' => $limitGetNewReleases,
									            'offset' => $offsetGetNewReleases
									        ));

            foreach ($newReleasesAlbums->albums->items as $album) {
                $album_ids[] = $album->id;
            }

            $offsetGetNewReleases += $limitGetNewReleases;
        } while ($offsetGetNewReleases < $max_results);
    } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	    $w2 = new Workflows('com.vdesabou.spotify.mini.player');
	    $w2->result(null, '', "Error: Spotify WEB API getNewReleases returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
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
	    $w2->result(null, '', "Error: Spotify WEB API getAlbums from getNewReleases returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
            echo "Error(addTracksToYourMusic): (exception " . print_r($e) . ")\n";
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
            echo "Error(addTracksToYourMusic): (exception " . print_r($e) . ")\n";
            handleSpotifyWebAPIException($w);
            return false;
        }

        // refresh library
        refreshLibrary($w);
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
 * @param bool $refreshLibrary (default: true)
 * @return void
 */
function addTracksToPlaylist($w, $tracks, $playlist_uri, $playlist_name, $allow_duplicate = true, $refreshLibrary = true)
{
	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }
	$userid = $setting[18];

    $api = getSpotifyWebAPI($w);
    if ($api == false) {
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
            echo "Error(addTracksToPlaylist): (exception " . print_r($e) . ")\n";
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
            echo "Error(addTracksToPlaylist): (exception " . print_r($e) . ")\n";
            handleSpotifyWebAPIException($w);
            return false;
        }

        if ($refreshLibrary) {
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
            if (strtolower($result['title']) == strtolower($title)) {
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
 * displayNotificationWithArtwork function.
 *
 * @access public
 * @param mixed $output
 * @param mixed $artwork
 * @return void
 */
function displayNotificationWithArtwork($subtitle, $artwork, $title = 'Spotify Mini Player')
{
    if ($artwork != "" && file_exists($artwork)) {
        copy($artwork, "/tmp/tmp");
    }

    exec("./terminal-notifier.app/Contents/MacOS/terminal-notifier -title '" . $title . "' -sender 'com.spotify.miniplayer' -contentImage '/tmp/tmp' -message '" . $subtitle . "'");
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
    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        displayNotificationWithArtwork('🔈 ' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' in album ' . escapeQuery($results[2]), getTrackOrAlbumArtwork($w,  $results[4], true), 'Now Playing ' . floatToStars($results[6]/100) .' (' . beautifyTime($results[5]) . ')');
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png', 'Error!');
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

    $command_output = exec("./src/track_info.ksh 2>&1");

    if (substr_count($command_output, '▹') > 0) {
        $results = explode('▹', $command_output);
        displayLyrics($w, $results[1], $results[0]);
    } else {
        displayNotificationWithArtwork("Error: cannot get current track",'./images/warning.png', 'Error!');
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
 * downloadArtworks function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function downloadArtworks($w) {

    if (!$w->internet()) {
        displayNotificationWithArtwork("Error: Download Artworks,
No internet connection", './images/warning.png');
        return;
    }

	touch($w->data() . "/download_artworks_in_progress");
	$w->write('Download Artworks▹' . 0 . '▹' . 0 . '▹' . time(), 'download_artworks_in_progress');
	$in_progress_data = $w->read('download_artworks_in_progress');
	$words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    //
    // Get list of artworks to download from DB
    //

    $dbfile = $w->data() . '/fetch_artworks.db';
    try {
        $dbartworks = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $nb_artworks_total=0;
        $nb_artworks=0;

        $getCount = 'select count(artist_name) from artists where already_fetched=0';
        $stmt = $dbartworks->prepare($getCount);
        $stmt->execute();
        $count = $stmt->fetch();
        $nb_artworks_total += intval($count[0]);

        $getCount = 'select count(track_uri) from tracks where already_fetched=0';
        $stmt = $dbartworks->prepare($getCount);
        $stmt->execute();
        $count = $stmt->fetch();
        $nb_artworks_total += intval($count[0]);

        $getCount = 'select count(album_uri) from albums where already_fetched=0';
        $stmt = $dbartworks->prepare($getCount);
        $stmt->execute();
        $count = $stmt->fetch();
        $nb_artworks_total += intval($count[0]);

        displayNotificationWithArtwork("Start downloading " . $nb_artworks_total . " artworks",'./images/artworks.png');

		// artists
        $getArtists = "select artist_name from artists where already_fetched=0";
        $stmtGetArtists = $dbartworks->prepare($getArtists);

		$updateArtist = "update artists set already_fetched=1 where artist_name=:artist_name";
        $stmtUpdateArtist = $dbartworks->prepare($updateArtist);

		// tracks
        $getTracks = "select track_uri from tracks where already_fetched=0";
        $stmtGetTracks = $dbartworks->prepare($getTracks);

		$updateTrack = "update tracks set already_fetched=1 where track_uri=:track_uri";
        $stmtUpdateTrack = $dbartworks->prepare($updateTrack);

		// albums
        $getAlbums = "select album_uri from albums where already_fetched=0";
        $stmtGetAlbums = $dbartworks->prepare($getAlbums);

		$updateAlbum = "update albums set already_fetched=1 where album_uri=:album_uri";
        $stmtUpdateAlbum = $dbartworks->prepare($updateAlbum);

		////
		// Artists
		//
		$artists = $stmtGetArtists->execute();

        while ($artist = $stmtGetArtists->fetch()) {

			$ret = getArtistArtwork($w, $artist[0], true, false, true);
	        if($ret == false) {
		        echo "WARN: $artist[0] artwork not found, using default \n";
	        } else if(!is_string($ret)) {
		        //echo "INFO: $artist[0] artwork was fetched \n";
	        } else if (is_string($ret)) {
		        //echo "INFO: $artist[0] artwork was already there $ret \n";
	        }

            $stmtUpdateArtist->bindValue(':artist_name', $artist[0]);
            $stmtUpdateArtist->execute();

	        $nb_artworks++;
	        if ($nb_artworks % 10 === 0) {
	            $w->write('Download Artworks▹' . $nb_artworks . '▹' . $nb_artworks_total . '▹' . $words[3], 'download_artworks_in_progress');
	        }
        }

		////
		// Tracks
		//
		$tracks = $stmtGetTracks->execute();

        while ($track = $stmtGetTracks->fetch()) {

			$ret = getTrackOrAlbumArtwork($w, $track[0], true, false, true);
	        if($ret == false) {
		        echo "WARN: $track[0] artwork not found, using default \n";
	        } else if(!is_string($ret)) {
		        //echo "INFO: $track[0] artwork was fetched \n";
	        } else if (is_string($ret)) {
		        //echo "INFO: $artist[0] artwork was already there $ret \n";
	        }

            $stmtUpdateTrack->bindValue(':track_uri', $track[0]);
            $stmtUpdateTrack->execute();

	        $nb_artworks++;
	        if ($nb_artworks % 10 === 0) {
	            $w->write('Download Artworks▹' . $nb_artworks . '▹' . $nb_artworks_total . '▹' . $words[3], 'download_artworks_in_progress');
	        }
        }

		////
		// Albums
		//
		$albums = $stmtGetAlbums->execute();

        while ($album = $stmtGetAlbums->fetch()) {

			$ret = getTrackOrAlbumArtwork($w, $album[0], true, false, true);
	        if($ret == false) {
		        echo "WARN: $album[0] artwork not found, using default \n";
	        } else if(!is_string($ret)) {
		        //echo "INFO: $album[0] artwork was fetched \n";
	        } else if (is_string($ret)) {
		        //echo "INFO: $artist[0] artwork was already there $ret \n";
	        }

            $stmtUpdateAlbum->bindValue(':album_uri', $album[0]);
            $stmtUpdateAlbum->execute();

	        $nb_artworks++;
	        if ($nb_artworks % 5 === 0) {
	            $w->write('Download Artworks▹' . $nb_artworks . '▹' . $nb_artworks_total . '▹' . $words[3], 'download_artworks_in_progress');
	        }
        }

    } catch (PDOException $e) {
        handleDbIssuePdoEcho($dbartworks,$w);
        $dbartworks = null;
        return false;
    }

	unlink($w->data() . "/download_artworks_in_progress");
    exec("echo \"END `date` \" >> \"" . $w->cache() . "/action.log\"");

    $elapsed_time = time() - $words[3];
    displayNotificationWithArtwork("All artworks have been downloaded (" . $nb_artworks_total . " artworks) - took " . beautifyTime($elapsed_time),'./images/artworks.png');
	stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', $nb_artworks_total);
	return true;
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
function getTrackOrAlbumArtwork($w, $spotifyURL, $fetchIfNotPresent, $fetchLater = false, $isLaterFetch = false)
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

	//
    if($fetchLater == true) {
		if (!is_file($currentArtwork)) {
			return array (false, $currentArtwork);
		} else {
			return array (true, $currentArtwork);
		}
        // always return currentArtwork
        return $currentArtwork;
    }

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

                if($isLaterFetch == true) {
	             	return true;
	            }
            } else {
	            if($isLaterFetch == true) {
	                if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
	                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
	                endif;

		            if ($isAlbum) {
		                copy('./images/albums.png', $currentArtwork);
		            } else {
		                copy('./images/tracks.png', $currentArtwork);
		            }
		            return false;
	            } else {
		            if ($isAlbum) {
		                return "./images/albums.png";
		            } else {
		                return "./images/tracks.png";
		            }
	            }
            }
        } else {
            if($isLaterFetch == true) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
                endif;

	            if ($isAlbum) {
	                copy('./images/albums.png', $currentArtwork);
	            } else {
	                copy('./images/tracks.png', $currentArtwork);
	            }
	            return false;
            } else {
	            if ($isAlbum) {
	                return "./images/albums.png";
	            } else {
	                return "./images/tracks.png";
	            }
            }
        }
    } else {
        if (filesize($currentArtwork) == 0) {
            if($isLaterFetch == true) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
                endif;

	            if ($isAlbum) {
	                copy('./images/albums.png', $currentArtwork);
	            } else {
	                copy('./images/tracks.png', $currentArtwork);
	            }
	            return false;
            } else {
	            if ($isAlbum) {
	                return "./images/albums.png";
	            } else {
	                return "./images/tracks.png";
	            }
            }
        }
    }

    if (is_numeric($artwork) && $artwork == 0) {
        if($isLaterFetch == true) {
            if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
                exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
            endif;

            if ($isAlbum) {
                copy('./images/albums.png', $currentArtwork);
            } else {
                copy('./images/tracks.png', $currentArtwork);
            }
            return false;
        } else {
            if ($isAlbum) {
                return "./images/albums.png";
            } else {
                return "./images/tracks.png";
            }
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
function getArtistArtwork($w, $artist, $fetchIfNotPresent = false, $fetchLater = false, $isLaterFetch = false)
{
    $parsedArtist = urlencode(escapeQuery($artist));

    if (!file_exists($w->data() . "/artwork")):
        exec("mkdir '" . $w->data() . "/artwork'");
    endif;

    $currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";
    $artwork = "";

	//
    if($fetchLater == true) {
		if (!is_file($currentArtwork)) {
			return array (false, $currentArtwork);
		} else {
			return array (true, $currentArtwork);
		}
        // always return currentArtwork
        return $currentArtwork;
    }

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

                if($isLaterFetch == true) {
	             	return true;
	            }
            } else {
	            if($isLaterFetch == true) {
	                if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
	                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
	                endif;
		            copy('./images/artists.png', $currentArtwork);
		            return false;
	            } else {
		            return "./images/artists.png";
	            }
            }
        } else {
            if($isLaterFetch == true) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
                endif;
	            copy('./images/artists.png', $currentArtwork);
	            return false;
            } else {
	            return "./images/artists.png";
            }
        }
    } else {
        if (filesize($currentArtwork) == 0) {
            if($isLaterFetch == true) {
                if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
                    exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
                endif;
	            copy('./images/artists.png', $currentArtwork);
	            return false;
            } else {
	            return "./images/artists.png";
            }
        }
    }

    if (is_numeric($artwork) && $artwork == 0) {
        if($isLaterFetch == true) {
            if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
                exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
            endif;
            copy('./images/artists.png', $currentArtwork);
            return false;
        } else {
            return "./images/artists.png";
        }
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
 * getSettingsFromDuplicateDb function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getSettingsFromDuplicateDb($w) {

    //
    // Read settings from a copy of DB
    //
    if (file_exists($w->data() . '/settings_tmp.db')) {
        $dbfile = $w->data() . '/settings_tmp.db';
    } else {

	    if(file_exists($w->data() . '/settings.db')) {
		    copy($w->data() . '/settings.db', $w->data() . '/settings_tmp.db');
		    $dbfile = $w->data() . '/settings_tmp.db';
	    } else {
		    return false;
	    }

    }

    try {
        $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $getSettings = 'select * from settings';
        $stmt = $dbsettings->prepare($getSettings);
        $stmt->execute();
        $setting = $stmt->fetch();
    } catch (PDOException $e) {
	    echo "Error(getSettingsFromDuplicateDb): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($dbsettings,$w);
        $dbsettings = null;
        return false;
    }
	return $setting;
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return false;
    }

    touch($w->data() . "/update_library_in_progress");
    $w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');
    $in_progress_data = $w->read('update_library_in_progress');

	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }
	$country_code = $setting[8];
	$userid = $setting[18];

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
		//exec("echo \".dump\" | sqlite3 \"" . $w->data() . "/library.db\" | sqlite3 \"" . $w->data() . "/library_old.db\"");
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
	    $db->query("PRAGMA synchronous = OFF");
	    $db->query("PRAGMA journal_mode = OFF");
	    $db->query("PRAGMA temp_store = MEMORY");
	    $db->query("PRAGMA count_changes = OFF");
	    $db->query("PRAGMA PAGE_SIZE = 4096");
	    $db->query("PRAGMA default_cache_size=700000");
	    $db->query("PRAGMA cache_size=700000");
	    $db->query("PRAGMA compile_options");
	    $db->myDataBaseNameProperty = $dbfile;
    } catch (PDOException $e) {
	    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return false;
    }

	// db for fetch artworks
	$fetch_artworks_existed = true;
    $dbfile = $w->data() . '/fetch_artworks.db';
    if (!file_exists($dbfile)) {
    	touch($dbfile);
    	$fetch_artworks_existed = false;
    }
    // kill previous process if running
	exec("pid=$(ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'); if [ \"$pid\" != \"\" ]; then kill -9 \"$pid\";echo \"KILLED `date` PIDs: $pid\"; fi >> \"" . $w->cache() . "/action.log\"");
	if (file_exists($w->data() . '/download_artworks_in_progress')) {
		unlink($w->data() . "/download_artworks_in_progress");
	}

    try {
        $dbartworks = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
	    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($dbartworks,$w);
        $dbartworks = null;
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
        echo "Error(getUserPlaylists): (exception " . print_r($e) . ")\n";
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
        echo "Error(getMySavedTracks): (exception " . print_r($e) . ")\n";
        handleSpotifyWebAPIException($w);
        return false;
    }

    // Handle playlists
    $w->write('Create Library▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');

    $nb_track = 0;

	try {
	    $db->exec("create table tracks (mymusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration_ms int)");
	    $db->exec("CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)");
	    $db->exec("CREATE INDEX IndexArtistName ON tracks (artist_name)");
	    $db->exec("CREATE INDEX IndexAlbumName ON tracks (album_name)");
	    $db->exec("create table counters (all_tracks int, mymusic_tracks int, all_artists int, mymusic_artists int, all_albums int, mymusic_albums int, playlists int)");
	    $db->exec("create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean)");

	    $insertPlaylist = "insert into playlists values (:uri,:name,:count_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser)";
	    $stmtPlaylist = $db->prepare($insertPlaylist);

	    $insertTrack = "insert into tracks values (:mymusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration_ms)";
	    $stmtTrack = $db->prepare($insertTrack);
    } catch (PDOException $e) {
	    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return false;
    }

	// DB artowrks
	if($fetch_artworks_existed == false) {
		try {
			$dbartworks->exec("create table artists (artist_name text PRIMARY KEY NOT NULL, already_fetched boolean)");
			$dbartworks->exec("create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
			$dbartworks->exec("create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");


	    } catch (PDOException $e) {
		    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($dbartworks,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return false;
	    }
	}

	try {
		// artworks
	    $insertArtistArtwork= "insert or ignore into artists values (:artist_name,:already_fetched)";
	    $stmtArtistArtwork = $dbartworks->prepare($insertArtistArtwork);

	    $insertTrackArtwork= "insert or ignore into tracks values (:track_uri,:already_fetched)";
	    $stmtTrackArtwork = $dbartworks->prepare($insertTrackArtwork);

	    $insertAlbumArtwork= "insert or ignore into albums values (:album_uri,:already_fetched)";
	    $stmtAlbumArtwork = $dbartworks->prepare($insertAlbumArtwork);

	} catch (PDOException $e) {
		echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($dbartworks,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return false;
	}
	$artworksToDownload = false;

    foreach ($savedListPlaylist as $playlist) {
        $tracks = $playlist->tracks;
        $owner = $playlist->owner;

        $playlist_artwork_path = getPlaylistArtwork($w,  $playlist->uri, true, true);

        if ("-" . $owner->id . "-" == "-" . $userid . "-") {
            $ownedbyuser = 1;
        } else {
            $ownedbyuser = 0;
        }

		try {
	        $stmtPlaylist->bindValue(':uri', $playlist->uri);
	        $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
	        $playlist_tracks = $playlist->tracks;
	        $stmtPlaylist->bindValue(':count_tracks', $playlist_tracks->total);
	        $stmtPlaylist->bindValue(':owner', $owner->id);
	        $stmtPlaylist->bindValue(':username', $owner->id);
	        $stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
	        $stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
	        $stmtPlaylist->execute();
	    } catch (PDOException $e) {
		    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($db,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return false;
	    }


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
                    $artists = $track->artists;
                    $artist = $artists[0];
                    $album = $track->album;

                    if($track->uri == 'spotify:track:null') {

						// unknown track, look it up online
						$query = 'track:' . strtolower($track->name) . ' artist:' . strtolower($artist->name);
	                	$results = searchWebApi($w,$country_code,$query, 'track', 1);

	                	if(count($results) > 0) {
							// only one track returned
							$track=$results[0];
							$artists = $track->artists;
							$artist = $artists[0];
		                	echo "INFO: Unknown track $track->uri / $track->name / $artist->name replaced by track: $track->uri / $track->name / $artist->name\n";

	                	} else {
		                    // skip
		                    echo "WARN: Skip Unknown track: $track->uri / $track->name / $artist->name / $album->name / $playlist->name / $playlist->uri \n";
		                    $nb_track++;
		                    continue;
	                	}
                    }

                    if (count($track->available_markets) == 0) {
                        $playable = 1;

                    } else if (in_array($country_code, $track->available_markets) !== false) {
	                    $playable = 1;
	                }
	                else {
                        $playable = 0;
                    }

					try {
	                    //
	                    // Download artworks in Fetch later mode
	                    list ($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w,  $track->uri, true, true);
	                    if($already_present == false) {
		                    $artworksToDownload = true;
					        $stmtTrackArtwork->bindValue(':track_uri', $track->uri);
					        $stmtTrackArtwork->bindValue(':already_fetched', 0);
					        $stmtTrackArtwork->execute();
	                    }

	                    list ($already_present, $artist_artwork_path) = getArtistArtwork($w,  $artist->name, true, true);
	                    if($already_present == false) {
		                    $artworksToDownload = true;
					        $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
					        $stmtArtistArtwork->bindValue(':already_fetched', 0);
					        $stmtArtistArtwork->execute();
	                    }

	                    list ($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w,  $album->uri, true, true);
	                    if($already_present == false) {
		                    $artworksToDownload = true;
					        $stmtAlbumArtwork->bindValue(':album_uri', $album->uri);
					        $stmtAlbumArtwork->bindValue(':already_fetched', 0);
					        $stmtAlbumArtwork->execute();
	                    }
				    } catch (PDOException $e) {
					    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
				        handleDbIssuePdoEcho($dbartworks,$w);
				        $dbartworks = null;
				        $dbsettings = null;
				        $db = null;
				        return false;
				    }

					try {
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
				    } catch (PDOException $e) {
					    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
				        handleDbIssuePdoEcho($db,$w);
				        $dbartworks = null;
				        $dbsettings = null;
				        $db = null;
				        return false;
				    }
                    $nb_track++;
                    if ($nb_track % 10 === 0) {
                        $w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
                    }
                }

                $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

            } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
        } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
            echo "Error(getUserPlaylistTracks): playlist id " . $playlist->id . " (exception " . print_r($e) . ")\n";
            handleSpotifyWebAPIException($w);
            return false;
        }
    }

    // Handle Your Music
    foreach ($savedMySavedTracks as $track) {
        $track = $track->track;

        $artists = $track->artists;
        $artist = $artists[0];
        $album = $track->album;

        if($track->uri == 'spotify:track:null') {

			// unknown track, look it up online
			$query = 'track:' . strtolower($track->name) . ' artist:' . strtolower($artist->name);
        	$results = searchWebApi($w,$country_code,$query, 'track', 1);

        	if(count($results) > 0) {
				// only one track returned
				$track=$results[0];
				$artists = $track->artists;
				$artist = $artists[0];
            	echo "INFO: Unknown track $track->uri / $track->name / $artist->name replaced by track: $track->uri / $track->name / $artist->name\n";

        	} else {
                // skip
		        echo "WARN: Skip Unknown track: $track->uri / $track->name / $artist->name / $album->name / $playlist->name / $playlist->uri \n";
                $nb_track++;
                continue;
        	}
        }

        if (count($track->available_markets) == 0) {
            $playable = 1;

        } else if (in_array($country_code, $track->available_markets) !== false) {
            $playable = 1;
        }
        else {
            $playable = 0;
        }

		try {
            //
            // Download artworks in Fetch later mode
            list ($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w,  $track->uri, true, true);
            if($already_present == false) {
                $artworksToDownload = true;
		        $stmtTrackArtwork->bindValue(':track_uri', $track->uri);
		        $stmtTrackArtwork->bindValue(':already_fetched', 0);
		        $stmtTrackArtwork->execute();
            }

            list ($already_present, $artist_artwork_path) = getArtistArtwork($w,  $artist->name, true, true);
            if($already_present == false) {
                $artworksToDownload = true;
		        $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
		        $stmtArtistArtwork->bindValue(':already_fetched', 0);
		        $stmtArtistArtwork->execute();
            }

            list ($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w,  $album->uri, true, true);
            if($already_present == false) {
                $artworksToDownload = true;
		        $stmtAlbumArtwork->bindValue(':album_uri', $album->uri);
		        $stmtAlbumArtwork->bindValue(':already_fetched', 0);
		        $stmtAlbumArtwork->execute();
            }
	    } catch (PDOException $e) {
		    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($dbartworks,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return false;
	    }

		try {
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
            $stmtTrack->bindValue(':playlist_name', escapeQuery($playlist->name));
            $stmtTrack->bindValue(':playlist_uri', $playlist->uri);
            $stmtTrack->bindValue(':playable', $playable);
            $stmtTrack->bindValue(':added_at', $item->added_at);
            $stmtTrack->bindValue(':duration_ms', $track->duration_ms);
            $stmtTrack->execute();
	    } catch (PDOException $e) {
		    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($db,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return false;
	    }

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
	    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $dbartworks = null;
        $db = null;
        return false;
    }

    $elapsed_time = time() - $words[3];
    displayNotificationWithArtwork("took " . beautifyTime($elapsed_time),'./images/recreate.png', "Library (re-)created (" . $nb_track . " tracks)");


    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    unlink($w->data() . '/settings_tmp.db');

    // remove legacy spotify app if needed
    if (file_exists(exec('printf $HOME') . "/Spotify/spotify-app-miniplayer")) {
        exec("rm -rf " . exec('printf $HOME') . "/Spotify/spotify-app-miniplayer");
    }

	// Download artworks in background
	if($artworksToDownload ==  true) {
		exec("echo \"===== DOWNLOAD_ARTWORKS ========\" >> \"" . $w->cache() . "/action.log\"");

		exec("echo \"START `date` \" >> \"" . $w->cache() . "/action.log\"");

	    exec("php -f ./src/action.php -- \"\" \"DOWNLOAD_ARTWORKS\" \"DOWNLOAD_ARTWORKS\" >> \"" .  $w->cache() . "/action.log\" 2>&1 & ");
	}

    unlink($w->data() . "/update_library_in_progress");
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
        displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
        return;
    }
    touch($w->data() . "/update_library_in_progress");
    $w->write('InitRefreshLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

    $in_progress_data = $w->read('update_library_in_progress');

	//
	// Get Settings from a duplicate DB to avoid clash
	// with main.php
	//
    $setting = getSettingsFromDuplicateDb($w);
    if($setting == false) {
	   return false;
    }
	$country_code = $setting[8];
	$userid = $setting[18];

    $words = explode('▹', $in_progress_data);

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    $nb_playlist = 0;

	// db for fetch artworks
	$fetch_artworks_existed = true;
    $dbfile = $w->data() . '/fetch_artworks.db';
    if (!file_exists($dbfile)) {
    	touch($dbfile);
    	$fetch_artworks_existed = false;
    }
    // kill previous process if running
	exec("pid=$(ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'); if [ \"$pid\" != \"\" ]; then kill -9 \"$pid\";echo \"KILLED `date` PIDs: $pid\"; fi >> \"" . $w->cache() . "/action.log\"");
	if (file_exists($w->data() . '/download_artworks_in_progress')) {
		unlink($w->data() . "/download_artworks_in_progress");
	}

    try {
        $dbartworks = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
        $dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
	    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($dbartworks,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return false;
    }

	// DB artowrks
	if($fetch_artworks_existed == false) {
		try {
			$dbartworks->exec("create table artists (artist_name text PRIMARY KEY NOT NULL, already_fetched boolean)");
			$dbartworks->exec("create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
			$dbartworks->exec("create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");


	    } catch (PDOException $e) {
		    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($dbartworks,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return false;
	    }
	}

	try {
		// artworks
	    $insertArtistArtwork= "insert or ignore into artists values (:artist_name,:already_fetched)";
	    $stmtArtistArtwork = $dbartworks->prepare($insertArtistArtwork);

	    $insertTrackArtwork= "insert or ignore into tracks values (:track_uri,:already_fetched)";
	    $stmtTrackArtwork = $dbartworks->prepare($insertTrackArtwork);

	    $insertAlbumArtwork= "insert or ignore into albums values (:album_uri,:already_fetched)";
	    $stmtAlbumArtwork = $dbartworks->prepare($insertAlbumArtwork);

	} catch (PDOException $e) {
		echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($dbartworks,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return false;
	}
	$artworksToDownload = false;

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
    } catch (PDOException $e) {
	    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return;
    }


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
        echo "Error(getUserPlaylists): (exception " . print_r($e) . ")\n";
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
		    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($db,$w);
	        $dbartworks = null;
	        $dbsettings = null;
	        $db = null;
	        return;
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

			try {
	            $stmtPlaylist->bindValue(':uri', $playlist->uri);
	            $stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
	            $stmtPlaylist->bindValue(':count_tracks', $tracks->total);
	            $stmtPlaylist->bindValue(':owner', $owner->id);
	            $stmtPlaylist->bindValue(':username', $owner->id);
	            $stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
	            $stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
	            $stmtPlaylist->execute();
		    } catch (PDOException $e) {
			    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
		        handleDbIssuePdoEcho($db,$w);
		        $dbartworks = null;
		        $dbsettings = null;
		        $db = null;
		        return;
		    }


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
	                    $artists = $track->artists;
	                    $artist = $artists[0];
	                    $album = $track->album;

	                    if($track->uri == 'spotify:track:null') {

							// unknown track, look it up online
							$query = 'track:' . strtolower($track->name) . ' artist:' . strtolower($artist->name);
		                	$results = searchWebApi($w,$country_code,$query, 'track', 1);

		                	if(count($results) > 0) {
								// only one track returned
								$track=$results[0];
								$artists = $track->artists;
								$artist = $artists[0];
			                	echo "INFO: Unknown track $track->uri / $track->name / $artist->name replaced by track: $track->uri / $track->name / $artist->name\n";

		                	} else {
			                    // skip
								echo "WARN: Skip Unknown track: $track->uri / $track->name / $artist->name / $album->name / $playlist->name / $playlist->uri \n";
			                    $nb_track++;
			                    continue;
		                	}
	                    }

	                    if (count($track->available_markets) == 0) {
	                        $playable = 1;

	                    } else if (in_array($country_code, $track->available_markets) !== false) {
		                    $playable = 1;
		                }
		                else {
	                        $playable = 0;
	                    }

						try {
		                    //
		                    // Download artworks in Fetch later mode
		                    list ($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w,  $track->uri, true, true);
		                    if($already_present == false) {
			                    $artworksToDownload = true;
						        $stmtTrackArtwork->bindValue(':track_uri', $track->uri);
						        $stmtTrackArtwork->bindValue(':already_fetched', 0);
						        $stmtTrackArtwork->execute();
		                    }

		                    list ($already_present, $artist_artwork_path) = getArtistArtwork($w,  $artist->name, true, true);
		                    if($already_present == false) {
			                    $artworksToDownload = true;
						        $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
						        $stmtArtistArtwork->bindValue(':already_fetched', 0);
						        $stmtArtistArtwork->execute();
		                    }

		                    list ($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w,  $album->uri, true, true);
		                    if($already_present == false) {
			                    $artworksToDownload = true;
						        $stmtAlbumArtwork->bindValue(':album_uri', $album->uri);
						        $stmtAlbumArtwork->bindValue(':already_fetched', 0);
						        $stmtAlbumArtwork->execute();
		                    }
					    } catch (PDOException $e) {
						    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
					        handleDbIssuePdoEcho($dbartworks,$w);
					        $dbartworks = null;
					        $dbsettings = null;
					        $db = null;
					        return false;
					    }

						try {
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
					    } catch (PDOException $e) {
						    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
					        handleDbIssuePdoEcho($db,$w);
					        $dbartworks = null;
					        $dbsettings = null;
					        $db = null;
					        return;
					    }
                    }

                    $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

                } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
            } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                echo "Error(getUserPlaylistTracks): playlist id " . $playlist->id . " (exception " . print_r($e) . ")\n";
                handleSpotifyWebAPIException($w);
                return;
            }

            displayNotificationWithArtwork('Added playlist ' . escapeQuery($playlist->name), $playlist_artwork_path);
        } else {
            // number of tracks has changed or playlist name has changed
            // update the playlist
            if ($playlists[2] != $tracks->total || $playlists[1] != escapeQuery($playlist->name)) {
                $nb_updated_playlists++;

				// force refresh of playlist artwork
				getPlaylistArtwork($w,  $playlist->uri, true, true);

				try {
	                if($playlists[1] != escapeQuery($playlist->name)) {
	                    $updatePlaylistsName = "update playlists set name=:name where uri=:uri";
						$stmtUpdatePlaylistsName = $db->prepare($updatePlaylistsName);

	                    $stmtUpdatePlaylistsName->bindValue(':name', escapeQuery($playlist->name));
	                    $stmtUpdatePlaylistsName->bindValue(':uri', $playlist->uri);
	                    $stmtUpdatePlaylistsName->execute();
	                }

	                $stmtDeleteFromTracks->bindValue(':playlist_uri', $playlist->uri);
	                $stmtDeleteFromTracks->execute();
			    } catch (PDOException $e) {
				    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
			        handleDbIssuePdoEcho($db,$w);
			        $dbartworks = null;
			        $dbsettings = null;
			        $db = null;
			        return;
			    }

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

						try {
	                        $stmtUpdatePlaylistsNbTracks->bindValue(':nb_tracks', $userPlaylistTracks->total);
	                        $stmtUpdatePlaylistsNbTracks->bindValue(':uri', $playlist->uri);
	                        $stmtUpdatePlaylistsNbTracks->execute();
					    } catch (PDOException $e) {
						    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
					        handleDbIssuePdoEcho($db,$w);
					        $dbartworks = null;
					        $dbsettings = null;
					        $db = null;
					        return;
					    }

                        foreach ($userPlaylistTracks->items as $item) {
                            $track = $item->track;
		                    $artists = $track->artists;
		                    $artist = $artists[0];
		                    $album = $track->album;

		                    if($track->uri == 'spotify:track:null') {

								// unknown track, look it up online
								$query = 'track:' . strtolower($track->name) . ' artist:' . strtolower($artist->name);
			                	$results = searchWebApi($w,$country_code,$query, 'track', 1);

			                	if(count($results) > 0) {
									// only one track returned
									$track=$results[0];
									$artists = $track->artists;
									$artist = $artists[0];
				                	echo "INFO: Unknown track $track->uri / $track->name / $artist->name replaced by track: $track->uri / $track->name / $artist->name\n";

			                	} else {
				                    // skip
									echo "WARN: Skip Unknown track: $track->uri / $track->name / $artist->name / $album->name / $playlist->name / $playlist->uri \n";
				                    $nb_track++;
				                    continue;
			                	}
		                    }

		                    if (count($track->available_markets) == 0) {
		                        $playable = 1;

		                    } else if (in_array($country_code, $track->available_markets) !== false) {
			                    $playable = 1;
			                }
			                else {
		                        $playable = 0;
		                    }

							try {
			                    //
			                    // Download artworks in Fetch later mode
			                    list ($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w,  $track->uri, true, true);
			                    if($already_present == false) {
				                    $artworksToDownload = true;
							        $stmtTrackArtwork->bindValue(':track_uri', $track->uri);
							        $stmtTrackArtwork->bindValue(':already_fetched', 0);
							        $stmtTrackArtwork->execute();
			                    }

			                    list ($already_present, $artist_artwork_path) = getArtistArtwork($w,  $artist->name, true, true);
			                    if($already_present == false) {
				                    $artworksToDownload = true;
							        $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
							        $stmtArtistArtwork->bindValue(':already_fetched', 0);
							        $stmtArtistArtwork->execute();
			                    }

			                    list ($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w,  $album->uri, true, true);
			                    if($already_present == false) {
				                    $artworksToDownload = true;
							        $stmtAlbumArtwork->bindValue(':album_uri', $album->uri);
							        $stmtAlbumArtwork->bindValue(':already_fetched', 0);
							        $stmtAlbumArtwork->execute();
			                    }
						    } catch (PDOException $e) {
							    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
						        handleDbIssuePdoEcho($dbartworks,$w);
						        $dbartworks = null;
						        $dbsettings = null;
						        $db = null;
						        return false;
						    }

							try {

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
						    } catch (PDOException $e) {
							    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
						        handleDbIssuePdoEcho($db,$w);
						        $dbartworks = null;
						        $dbsettings = null;
						        $db = null;
						        return;
						    }
                        }

                        $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;

                    } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
                } catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
                    echo "Error(getUserPlaylistTracks): playlist id " . $tmp[4] . " (exception " . print_r($e) . ")\n";
                    handleSpotifyWebAPIException($w);
                    return;
                }
                displayNotificationWithArtwork('Updated playlist ' . escapeQuery($playlist->name), getPlaylistArtwork($w,  $playlist->uri, true));
            } else {
                continue;
            }
        }
    }

	try {
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
    } catch (PDOException $e) {
	    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbartworks = null;
        $dbsettings = null;
        $db = null;
        return;
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
        echo "Error(getMySavedTracks): (exception " . print_r($e) . ")\n";
        handleSpotifyWebAPIException($w);
        return false;
    }
    try {
		// get current number of track in Your Music
	    $getCount = 'select count(distinct uri) from tracks where mymusic=1';
	    $stmt = $db->prepare($getCount);
	    $stmt->execute();
	    $mymusic_tracks = $stmt->fetch();
    } catch (PDOException $e) {
	    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $db = null;
        return;
    }

    $your_music_updated=false;
	if($mymusic_tracks[0] != count($savedMySavedTracks)) {

		$your_music_updated=true;
		// Your Music has changed, update it
        $nb_playlist++;
        $w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');

		// delete tracks
		try {
			$stmtDeleteFromTracksYourMusic->bindValue(':mymusic', 1);
			$stmtDeleteFromTracksYourMusic->execute();
	    } catch (PDOException $e) {
		    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
	        handleDbIssuePdoEcho($db,$w);
	        $dbsettings = null;
	        $db = null;
	        return;
	    }


        foreach ($savedMySavedTracks as $item) {
            $track = $item->track;
            $artists = $track->artists;
            $artist = $artists[0];
            $album = $track->album;

            if($track->uri == 'spotify:track:null') {

				// unknown track, look it up online
				$query = 'track:' . strtolower($track->name) . ' artist:' . strtolower($artist->name);
            	$results = searchWebApi($w,$country_code,$query, 'track', 1);

            	if(count($results) > 0) {
					// only one track returned
					$track=$results[0];
					$artists = $track->artists;
					$artist = $artists[0];
                	echo "INFO: Unknown track $track->uri / $track->name / $artist->name replaced by track: $track->uri / $track->name / $artist->name\n";

            	} else {
                    // skip
	                echo "WARN: Skip Unknown track: $track->uri / $track->name / $artist->name / $album->name / $playlist->name / $playlist->uri \n";
                    $nb_track++;
                    continue;
            	}
            }

            if (count($track->available_markets) == 0) {
                $playable = 1;

            } else if (in_array($country_code, $track->available_markets) !== false) {
                $playable = 1;
            }
            else {
                $playable = 0;
            }

			try {
                //
                // Download artworks in Fetch later mode
                list ($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w,  $track->uri, true, true);
                if($already_present == false) {
                    $artworksToDownload = true;
			        $stmtTrackArtwork->bindValue(':track_uri', $track->uri);
			        $stmtTrackArtwork->bindValue(':already_fetched', 0);
			        $stmtTrackArtwork->execute();
                }

                list ($already_present, $artist_artwork_path) = getArtistArtwork($w,  $artist->name, true, true);
                if($already_present == false) {
                    $artworksToDownload = true;
			        $stmtArtistArtwork->bindValue(':artist_name', $artist->name);
			        $stmtArtistArtwork->bindValue(':already_fetched', 0);
			        $stmtArtistArtwork->execute();
                }

                list ($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w,  $album->uri, true, true);
                if($already_present == false) {
                    $artworksToDownload = true;
			        $stmtAlbumArtwork->bindValue(':album_uri', $album->uri);
			        $stmtAlbumArtwork->bindValue(':already_fetched', 0);
			        $stmtAlbumArtwork->execute();
                }
		    } catch (PDOException $e) {
			    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
		        handleDbIssuePdoEcho($dbartworks,$w);
		        $dbartworks = null;
		        $dbsettings = null;
		        $db = null;
		        return false;
		    }

			try {
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
		     } catch (PDOException $e) {
			    echo "Error(refreshLibrary): (exception " . print_r($e) . ")\n";
		        handleDbIssuePdoEcho($db,$w);
		        $dbsettings = null;
		        $db = null;
		        return;
		    }

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
	    echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
        handleDbIssuePdoEcho($db,$w);
        $dbsettings = null;
        $dbartworks = null;
        $db = null;
        return false;
    }

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
        $message = 'Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg . ' ' . $yourMusicMsg;
    } elseif($changedPlaylists) {
        $message = 'Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg;
    } elseif($changedYourMusic) {
        $message = $yourMusicMsg;
    } else {
        $message = 'No change';
    }

    displayNotificationWithArtwork($message . " - took " . beautifyTime($elapsed_time),'./images/update.png','Library refreshed');


    if (file_exists($w->data() . '/library_old.db')) {
        unlink($w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');
    unlink($w->data() . '/settings_tmp.db');

	// Download artworks in background
	if($artworksToDownload ==  true) {
		exec("echo \"===== DOWNLOAD_ARTWORKS ========\" >> \"" . $w->cache() . "/action.log\"");

		exec("echo \"START `date` \" >> \"" . $w->cache() . "/action.log\"");

	    exec("php -f ./src/action.php -- \"\" \"DOWNLOAD_ARTWORKS\" \"DOWNLOAD_ARTWORKS\" >> \"" .  $w->cache() . "/action.log\" 2>&1 & ");
	}

    unlink($w->data() . "/update_library_in_progress");
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

	displayNotificationWithArtwork('Error: Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
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
    echo 'Database Error: ' . $dbhandle->errorInfo()[0] . ' ' . $dbhandle->errorInfo()[1] . ' ' . $dbhandle->errorInfo()[2] . '\n';
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
    exit;
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
 * floatToStars function.
 *
 * @access public
 * @param mixed $decimal
 * @return void
 */
function floatToStars($decimal)
{
    $squares = ($decimal < 1) ? floor($decimal * 5) : 5;
    return str_repeat("★", $squares) . str_repeat("☆", 5 - $squares);
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
        displayNotificationWithArtwork("Timeout or failure. Try again",'./images/warning.png', 'Error!');
    } elseif ($no_match) {
        displayNotificationWithArtwork("Sorry there is no match for this track",'./images/warning.png', 'Error!');
    } else {
        $lyrics = strip_tags($lyrics);

        //$lyrics = (strlen($lyrics) > 1303) ? substr($lyrics,0,1300).'...' : $lyrics;

        if ($lyrics == "") {
            displayNotificationWithArtwork("Sorry there is no match for this track",'./images/warning.png', 'Error!');
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
            displayNotificationWithArtwork("Error: this release has not been downloaded from Packal",'./images/warning.png', 'Error!');
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
 * getCountryName function.
 *
 * @access public
 * @param mixed $cc
 * @return void
 */
function getCountryName($cc) {

	// from http://stackoverflow.com/questions/14599400/how-to-get-iso-3166-1-compatible-country-code

	$country_names = json_decode(file_get_contents("./src/country_names.json")
, true);

    return $country_names[$cc];
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


/**
 * searchCommandsFastAccess function.
 * 
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $setting
 * @return void
 */
function searchCommandsFastAccess($w,$query,$setting)
{
	$all_playlists = $setting[0];
	$is_spotifious_active = $setting[1];
	$is_alfred_playlist_active = $setting[2];
	$radio_number_tracks = $setting[3];
	$now_playing_notifications = $setting[4]; // instead of is_lyrics_active
	$max_results = $setting[5];
	$alfred_playlist_uri = $setting[6];
	$alfred_playlist_name = $setting[7];
	$country_code = $setting[8];
	// Theme is deprecated
	//$theme = $setting[9];
	$last_check_update_time = $setting[10];
	$oauth_client_id = $setting[11];
	$oauth_client_secret = $setting[12];
	$oauth_redirect_uri = $setting[13];
	$oauth_access_token = $setting[14];
	$oauth_expires = $setting[15];
	$oauth_refresh_token = $setting[16];
	$display_name = $setting[17];
	$userid = $setting[18];
	$echonest_api_key = $setting[19];
	
	if (mb_strlen($query) < 3) {
	    ////////
	    //
	    //	Fast Access to commands
	    //////////////			
        $w->result('SpotifyMiniPlayer_' . 'next', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'next' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'previous', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'previous' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');


        $w->result('SpotifyMiniPlayer_' . 'lookup_current_artist', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lookup_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Lookup Current Artist online', array(
            '☁︎ Query all albums/tracks from current artist online..',
            'alt' => 'Not Available',
            'cmd' => 'Not Available',
            'shift' => 'Not Available',
            'fn' => 'Not Available',
            'ctrl' => 'Not Available'), './images/online_artist.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'play', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'play_current_artist', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');
        $w->result('SpotifyMiniPlayer_' . 'play_current_album', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_current_album' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');

        $w->result('SpotifyMiniPlayer_' . 'pause', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'pause' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'current', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'force_current' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'random', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'random' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Random Track', 'Play random track', './images/random.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'shuffle', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'shuffle' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');

        if ($update_in_progress == false) {
            $w->result('SpotifyMiniPlayer_' . 'refresh_library', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'refresh_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Refresh your library", "Do this when your library has changed (outside the scope of this workflow)", './images/update.png', 'yes', null, '');
        }

        if ($update_in_progress == false) {
            if ($is_alfred_playlist_active == true) {
                $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
            } else {
                $w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
            }
			$w->result('SpotifyMiniPlayer_' . 'add_current_track_to', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track_to' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');
		}

        $w->result('SpotifyMiniPlayer_' . 'mute', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'mute' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Mute/Unmute System Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');

        $w->result('SpotifyMiniPlayer_' . 'volume_down', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'volume_down' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Volume Down', 'Decrease System Volume', './images/volume_down.png', 'yes', '');

       $w->result('SpotifyMiniPlayer_' . 'volume_up', serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'volume_up' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Volume Up', 'Increase System Volume', './images/volume_up.png', 'yes', '');		
	} else {
	    //
	    // Search commands for fast access
	    //
	    if (strpos(strtolower('next'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'next' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');
	    } else if (strpos(strtolower('previous'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'previous' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');
	    } else if (strpos(strtolower('lookup'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lookup_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Lookup Current Artist online', array(
	            '☁︎ Query all albums/tracks from current artist online..',
	            'alt' => 'Not Available',
	            'cmd' => 'Not Available',
	            'shift' => 'Not Available',
	            'fn' => 'Not Available',
	            'ctrl' => 'Not Available'), './images/online_artist.png', 'yes', '');
	    } else if (strpos(strtolower('query'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'lookup_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Lookup Current Artist online', array(
	            '☁︎ Query all albums/tracks from current artist online..',
	            'alt' => 'Not Available',
	            'cmd' => 'Not Available',
	            'shift' => 'Not Available',
	            'fn' => 'Not Available',
	            'ctrl' => 'Not Available'), './images/online_artist.png', 'yes', '');
	    } else if (strpos(strtolower('play'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');
	
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_current_artist' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'play_current_album' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');
	    } else if (strpos(strtolower('pause'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'pause' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');
	    } else if (strpos(strtolower('current'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'force_current' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');
	    } else if (strpos(strtolower('random'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'random' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Random Track', 'Play random track', './images/random.png', 'yes', '');
	    } else if (strpos(strtolower('shuffle'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'shuffle' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');
	    } else if (strpos(strtolower('refresh'), strtolower($query)) !== false) {
	        if ($update_in_progress == false) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'refresh_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Refresh your library", "Do this when your library has changed (outside the scope of this workflow)", './images/update.png', 'yes', null, '');
	        }
	    } else if (strpos(strtolower('update'), strtolower($query)) !== false) {
	        if ($update_in_progress == false) {
	            $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'refresh_library' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), "Refresh your library", "Do this when your library has changed (outside the scope of this workflow)", './images/update.png', 'yes', null, '');
	        }
	    } else if (strpos(strtolower('add'), strtolower($query)) !== false) {
	        if ($update_in_progress == false) {
	            if ($is_alfred_playlist_active == true) {
	                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
	            } else {
	                $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
	            }
				$w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'add_current_track_to' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');
			}
	    } else if (strpos(strtolower('mute'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'mute' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Mute/Unmute System Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');
	    } else if (strpos(strtolower('volume_down'), strtolower($query)) !== false) {
	        $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'volume_down' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Volume Down', 'Decrease System Volume', './images/volume_down.png', 'yes', '');
	    } else if (strpos(strtolower('volume_up'), strtolower($query)) !== false) {
	       $w->result(null, serialize(array('' /*track_uri*/, '' /* album_uri */, '' /* artist_uri */, '' /* playlist_uri */, '' /* spotify_command */, '' /* query */, '' /* other_settings*/, 'volume_up' /* other_action */, $alfred_playlist_uri /* alfred_playlist_uri */, ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */, $now_playing_notifications /* now_playing_notifications */, $is_alfred_playlist_active /* is_alfred_playlist_active */, $country_code /* country_code*/, $userid /* userid*/)), 'Volume Up', 'Increase System Volume', './images/volume_up.png', 'yes', '');
	    }
    } 
    return $w;	
}

///////////////
//
// StatHat integration
//


/**
 * do_post_request function.
 * 
 * @access public
 * @param mixed $url
 * @param mixed $data
 * @param mixed $optional_headers (default: null)
 * @return void
 */
function do_post_request($url, $data, $optional_headers = null)
{
        $params = array('http' => array(
                'method' => 'POST',
                'content' => $data
        ));
        if ($optional_headers !== null) {
                $params['http']['header'] = $optional_headers;
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
                throw new Exception("Problem with $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
                throw new Exception("Problem reading data from $url, $php_errormsg");
        }
        return $response;
}

/**
 * do_async_post_request function.
 * 
 * @access public
 * @param mixed $url
 * @param mixed $params
 * @return void
 */
function do_async_post_request($url, $params)
{
        foreach ($params as $key => &$val) {
                if (is_array($val)) $val = implode(',', $val);
                $post_params[] = $key.'='.urlencode($val);
        }
        $post_string = implode('&', $post_params);

        $parts=parse_url($url);

        $fp = fsockopen($parts['host'],
                isset($parts['port'])?$parts['port']:80,
                $errno, $errstr, 30);

        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);
}

/**
 * stathat_count function.
 * 
 * @access public
 * @param mixed $stat_key
 * @param mixed $user_key
 * @param mixed $count
 * @return void
 */
function stathat_count($stat_key, $user_key, $count)
{
        return do_async_post_request("http://api.stathat.com/c", array('key' => $stat_key, 'ukey' => $user_key, 'count' => $count));
}

/**
 * stathat_value function.
 * 
 * @access public
 * @param mixed $stat_key
 * @param mixed $user_key
 * @param mixed $value
 * @return void
 */
function stathat_value($stat_key, $user_key, $value)
{
        do_async_post_request("http://api.stathat.com/v", array('key' => $stat_key, 'ukey' => $user_key, 'value' => $value));
}

/**
 * stathat_ez_count function.
 * 
 * @access public
 * @param mixed $email
 * @param mixed $stat_name
 * @param mixed $count
 * @return void
 */
function stathat_ez_count($email, $stat_name, $count)
{
        do_async_post_request("http://api.stathat.com/ez", array('email' => $email, 'stat' => $stat_name, 'count' => $count));
}

/**
 * stathat_ez_value function.
 * 
 * @access public
 * @param mixed $email
 * @param mixed $stat_name
 * @param mixed $value
 * @return void
 */
function stathat_ez_value($email, $stat_name, $value)
{
        do_async_post_request("http://api.stathat.com/ez", array('email' => $email, 'stat' => $stat_name, 'value' => $value));
}

/**
 * stathat_count_sync function.
 * 
 * @access public
 * @param mixed $stat_key
 * @param mixed $user_key
 * @param mixed $count
 * @return void
 */
function stathat_count_sync($stat_key, $user_key, $count)
{
         return do_post_request("http://api.stathat.com/c", "key=$stat_key&ukey=$user_key&count=$count");
}

/**
 * stathat_value_sync function.
 * 
 * @access public
 * @param mixed $stat_key
 * @param mixed $user_key
 * @param mixed $value
 * @return void
 */
function stathat_value_sync($stat_key, $user_key, $value)
{
        return do_post_request("http://api.stathat.com/v", "key=$stat_key&ukey=$user_key&value=$value");
}

/**
 * stathat_ez_count_sync function.
 * 
 * @access public
 * @param mixed $email
 * @param mixed $stat_name
 * @param mixed $count
 * @return void
 */
function stathat_ez_count_sync($email, $stat_name, $count)
{
        return do_post_request("http://api.stathat.com/ez", "email=$email&stat=$stat_name&count=$count");
}

/**
 * stathat_ez_value_sync function.
 * 
 * @access public
 * @param mixed $email
 * @param mixed $stat_name
 * @param mixed $value
 * @return void
 */
function stathat_ez_value_sync($email, $stat_name, $value)
{
        return do_post_request("http://api.stathat.com/ez", "email=$email&stat=$stat_name&value=$value");
}


?>