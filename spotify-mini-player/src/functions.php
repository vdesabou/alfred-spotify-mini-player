<?php

require_once './src/workflows.php';
require './vendor/autoload.php';

/**
 * getSpotifyWebAPI function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getSpotifyWebAPI($w, $old_api = null) {
	if (!$w->internet()) {
		throw new SpotifyWebAPI\SpotifyWebAPIException("No internet connection", 100);
	}

	//
	// Read settings from JSON
	//
	$settings            = getSettings($w);
	$oauth_client_id     = $settings->oauth_client_id;
	$oauth_client_secret = $settings->oauth_client_secret;
	$oauth_redirect_uri  = $settings->oauth_redirect_uri;
	$oauth_access_token  = $settings->oauth_access_token;
	$oauth_expires       = $settings->oauth_expires;
	$oauth_refresh_token = $settings->oauth_refresh_token;

	if ($old_api == null) {
		// create a new api object
		$session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);
		$api = new SpotifyWebAPI\SpotifyWebAPI();
	}

	// Check if refresh token necessary
	// if token validity < 20 minutes
	if (time() - $oauth_expires > 2400) {
		if ($old_api != null) {
			// when refresh needed:
			// create a new api object (even if api not null)
			$session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);
			$api = new SpotifyWebAPI\SpotifyWebAPI();
		}
		if ($session->refreshAccessToken($oauth_refresh_token) == true) {
			$oauth_access_token = $session->getAccessToken();
			// Set new token to settings
			$ret                = updateSetting($w, 'oauth_access_token', $oauth_access_token);
			if ($ret == false) {
				throw new SpotifyWebAPI\SpotifyWebAPIException("Cannot set oauth_access_token", 100);
			}

			$ret = updateSetting($w, 'oauth_expires', time());
			if ($ret == false) {
				throw new SpotifyWebAPI\SpotifyWebAPIException("Cannot set oauth_expires", 100);
			}
			$api->setAccessToken($oauth_access_token);
		} else {
			throw new SpotifyWebAPI\SpotifyWebAPIException("Token could not be refreshed", 100);
		}
	} else {
		// no need to refresh, the old api is
		// stil valid
		if ($old_api != null) {
			$api = $old_api;
		} else {
			// set the access token for the new api
			$api->setAccessToken($oauth_access_token);
		}
	}
	return $api;
}


/**
 * invokeMopidyMethod function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $method
 * @param mixed $params
 * @param bool $displayError (default: true)
 * @return void
 */
function invokeMopidyMethod($w, $method, $params, $displayError = true) {
	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$mopidy_server             = $settings->mopidy_server;
	$mopidy_port               = $settings->mopidy_port;

	exec("curl -s -X POST -H Content-Type:application/json -d '{
  \"method\": \"" . $method . "\",
  \"jsonrpc\": \"2.0\",
  \"params\": " . json_encode($params, JSON_HEX_APOS) . ",
  \"id\": 1
}' http://" . $mopidy_server . ":" . $mopidy_port . "/mopidy/rpc", $retArr, $retVal);

	if ($retVal != 0) {
		if ($displayError) {
			displayNotificationWithArtwork($w,'Mopidy Exception: returned error ' . $retVal, './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug Mopidy Exception: returned error " . $retVal . "\"'");

		}
		return false;
	}

	if (isset($retArr[0])) {
		$result = json_decode($retArr[0]);
		if (isset($result->result)) {
			return $result->result;
		}
		if (isset($result->error)) {
			logMsg("ERROR: invokeMopidyMethod() method: " . $method . ' params: ' . json_encode($params, JSON_HEX_APOS) . ' exception:'. print_r($result));

			if ($displayError) {
				displayNotificationWithArtwork($w,'Mopidy Exception: ' . htmlspecialchars($result->error->message), './images/warning.png', 'Error!');
				exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug Mopidy Exception: " . htmlspecialchars($result->error->message) . "\"'");
			}
			return false;
		}
	} else {
		logMsg("ERROR: empty response from Mopidy method: " . $method . ' params: ' . json_encode($params, JSON_HEX_APOS));
		displayNotificationWithArtwork($w,"ERROR: empty response from Mopidy method: " . $method . ' params: ' . json_encode($params, JSON_HEX_APOS), './images/warning.png');
	}
}




/**
 * createDebugFile function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function createDebugFile($w) {

	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$use_mopidy                = $settings->use_mopidy;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;

	exec("mkdir -p /tmp/spot_mini_debug");
	date_default_timezone_set('UTC');
	$date = date('Y-m-d H:i:s', time());

	$output = "Generated: " . $date . "\n";
	$output = $output . "----------------------------------------------\n";

	//
	// check for library update in progress
	if (file_exists($w->data() . "/update_library_in_progress")) {
	    $output = $output . "Library update in progress: " . "the file" . $w->data() . "/update_library_in_progress is present\n";
	}


	// settings.json
	//
    copy($w->data() . "/settings.json", "/tmp/spot_mini_debug/settings.json");
    // Remove oAuth values from file that will be uploaded
	updateSetting($w, 'oauth_client_secret', 'xxx', "/tmp/spot_mini_debug/settings.json");
	updateSetting($w, 'oauth_access_token', 'xxx', "/tmp/spot_mini_debug/settings.json");
	updateSetting($w, 'display_name', 'xxx', "/tmp/spot_mini_debug/settings.json");
	$output = $output . "* display_name: " . $display_name . "\n\n";
	$output = $output . "* oauth_client_secret: " . $oauth_client_secret . "\n\n";
	$output = $output . "* oauth_access_token: " . $oauth_access_token . "\n\n";
	$output = $output . "* oauth_refresh_token: " . $oauth_refresh_token . "\n\n";


	$output = $output . "****\n";

	copyDirectory($w->cache(), "/tmp/spot_mini_debug/cache");

	if (!file_exists($w->data() . "/fetch_artworks.db")) {
	    $output = $output . "The file " . $w->data() . "/fetch_artworks.db is not present\n";
	} else {
	    copy($w->data() . "/fetch_artworks.db", "/tmp/spot_mini_debug/fetch_artworks.db");
	}

	if (!file_exists($w->data() . "/library.db")) {
	    $output = $output . "The file " . $w->data() . "/library.db is not present\n";
	} else {
	    copy($w->data() . "/library.db", "/tmp/spot_mini_debug/library.db");
	}

	if (!file_exists($w->data() . "/library_new.db")) {
	    $output = $output . "The file " . $w->data() . "/library_new.db is not present\n";
	} else {
	    copy($w->data() . "/library_new.db", "/tmp/spot_mini_debug/library_new.db");
	}

	if (!file_exists($w->data() . "/library_old.db")) {
	    $output = $output . "The file " . $w->data() . "/library_old.db is not present\n";
	} else {
	    copy($w->data() . "/library_old.db", "/tmp/spot_mini_debug/library_old.db");
	}


	if (!file_exists($w->data() . "/history.json")) {
	    $output = $output . "The file " . $w->data() . "/history.json is not present\n";
	} else {
	    copy($w->data() . "/history.json", "/tmp/spot_mini_debug/history.json");
	}

	if (!file_exists($w->data() . "/playqueue.json")) {
	    $output = $output . "The file " . $w->data() . "/playqueue.json is not present\n";
	} else {
	    copy($w->data() . "/playqueue.json", "/tmp/spot_mini_debug/playqueue.json");
	}

	if (!file_exists(exec('pwd') . "/packal/package.xml")) {
	    $output = $output . "The file " . exec('pwd') . "/packal/package.xml is not present\n";
	} else {
	    copy(exec('pwd') . "/packal/package.xml", "/tmp/spot_mini_debug/package.xml");
	}

	$output = $output . exec("uname -a");
	$output = $output . "\n";
	$output = $output . exec("sw_vers -productVersion");
	$output = $output . "\n";
	$output = $output . exec("sysctl hw.memsize");
	$output = $output . "\n";
	if(! $use_mopidy) {
		$output = $output . exec("osascript -e 'tell application \"Spotify\" to version'");
	} else {
		$output = $output . "Mopidy version is " . invokeMopidyMethod($w, "core.get_version", array(), false);
	}
	$output = $output . "\n";

	exec("cd /tmp;tar cfz spot_mini_debug.tgz spot_mini_debug");

	$output = $output . "****\n";

	$output = $output . exec("curl --upload-file /tmp/spot_mini_debug.tgz https://transfer.sh/spot_mini_debug_$userid.tgz");

	exec("cd /tmp;rm -rf spot_mini_debug.tgz spot_mini_debug");

	$output = $output . "\n----------------------------------------------\nCan you describe the problem in a few lines:\n";

    exec("echo \"" . $output . "\" | pbcopy");

	exec("open \"mailto:alfred.spotify.mini.player@gmail.com?subject=Alfred Spotify Mini Player debug file&body=$output\"");
}
/**
 * getCurrentTrackInfoWithMopidy function.
 *
 * @access public
 * @param mixed $w
 * @param bool $displayError (default: true)
 * @return void
 */
function getCurrentTrackInfoWithMopidy($w, $displayError = true) {
	$tl_track = invokeMopidyMethod($w, "core.playback.get_current_track", array(), $displayError);
	if ($tl_track == false) {
		return "mopidy_stopped";
	}
	$state = invokeMopidyMethod($w, "core.playback.get_state", array(), $displayError);

	$track_name = '';
	$artist_name = '';
	$album_name = '';
	$track_uri = '';
	$length = 0;

	if (isset($tl_track->name)) {
		$track_name = $tl_track->name;
	}

	if ( isset($tl_track->artists) &&
		isset($tl_track->artists[0]) &&
		isset($tl_track->artists[0])) {
		$artist_name = $tl_track->artists[0]->name;
	}

	if (isset($tl_track->album) && isset($tl_track->album->name)) {
		$album_name = $tl_track->album->name;
	}

	if (isset($tl_track->uri)) {
		$track_uri = $tl_track->uri;
	}

	if (isset($tl_track->length)) {
		$length = $tl_track->length;
	}

	return "" . $track_name. "▹" . $artist_name . "▹" . $album_name . "▹" . $state . "▹" . $track_uri . "▹" . $length . "▹" . "0";
}


/**
 * playUriWithMopidyWithoutClearing function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $uri
 * @return void
 */
function playUriWithMopidyWithoutClearing($w, $uri) {
	$tl_tracks = invokeMopidyMethod($w, "core.tracklist.add", array('uris' => array($uri), 'at_position' => 0));
	if (isset($tl_tracks[0])) {
		invokeMopidyMethod($w, "core.playback.play", array('tl_track' => $tl_tracks[0]));
	} else {
		displayNotificationWithArtwork($w,"Cannot play track with uri " . $uri, './images/warning.png', 'Error!');
	}
}


/**
 * playUriWithMopidy function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $uri
 * @return void
 */
function playUriWithMopidy($w, $uri) {
	invokeMopidyMethod($w, "core.tracklist.clear", array());
	playUriWithMopidyWithoutClearing($w, $uri);
}


/**
 * playTrackInContextWithMopidy function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @param mixed $context_uri
 * @return void
 */
function playTrackInContextWithMopidy($w, $track_uri, $context_uri) {
	invokeMopidyMethod($w, "core.tracklist.clear", array());
	invokeMopidyMethod($w, "core.tracklist.add", array('uri' => $context_uri, 'at_position' => 0));
	$tl_tracks = invokeMopidyMethod($w, "core.tracklist.get_tl_tracks", array());

	// loop to find track_uri
	$i=0;
	foreach ($tl_tracks as $tl_track) {
		if ($tl_track->track->uri == $track_uri) {
			// found the track move it to position 0
			invokeMopidyMethod($w, "core.tracklist.move", array('start' => $i, 'end' => $i, 'to_position' => 0));
		}
		$i++;
	}

	$tl_tracks = invokeMopidyMethod($w, "core.tracklist.get_tl_tracks", array());
	invokeMopidyMethod($w, "core.playback.play", array('tl_track' => $tl_tracks[0]));
}


/**
 * setThePlaylistPrivacy function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @param mixed $playlist_name
 * @param boolean $public
 * @return void
 */
function setThePlaylistPrivacy($w, $playlist_uri, $playlist_name, $public) {
	try {
		$tmp                         = explode(':', $playlist_uri);
		$api    = getSpotifyWebAPI($w);
		$ret = $api->updateUserPlaylist(urlencode($tmp[2]), $tmp[4], array( 'name' =>  escapeQuery($playlist_name),
				'public' => $public));
		if ($ret == true) {
			// refresh library
			refreshLibrary($w);
		}
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(updateUserPlaylist): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
		return false;
	}
}


/**
 * followThePlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @return void
 */
function followThePlaylist($w, $playlist_uri) {
	//
	// Read settings from JSON
	//

	$settings            = getSettings($w);
	$is_public_playlists = $settings->is_public_playlists;

	$public = false;
	if ($is_public_playlists) {
		$public = true;
	}
	try {
		$tmp                         = explode(':', $playlist_uri);
		$api    = getSpotifyWebAPI($w);
		$ret = $api->followPlaylist(urlencode($tmp[2]), $tmp[4], array('public' => $public));
		if ($ret == true) {
			// refresh library
			refreshLibrary($w);
		}
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(followThePlaylist): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
		return false;
	}
}


/**
 * unfollowThePlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @return void
 */
function unfollowThePlaylist($w, $playlist_uri) {
	try {
		$tmp                         = explode(':', $playlist_uri);
		$api    = getSpotifyWebAPI($w);
		$ret = $api->unfollowPlaylist($tmp[2], $tmp[4]);
		if ($ret == true) {
			// refresh library
			refreshLibrary($w);
		}
	}

	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(unfollowPlaylist): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
		return false;
	}
}


/**
 * addPlaylistToPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @param mixed $playlist_name
 * @return void
 */
function addPlaylistToPlayQueue($w, $playlist_uri, $playlist_name) {
	if (!$w->internet()) {
		return false;
	}

	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$use_mopidy                = $settings->use_mopidy;

	if (! $use_mopidy) {
		$tracks = getThePlaylistFullTracks($w, $playlist_uri);
		if ($tracks == false) {
			displayNotificationWithArtwork($w,"Cannot get tracks for playlist " . $playlist_name, './images/warning.png', 'Error!');
			return false;
		}
	} else {
		$tracks = array();
	}
	$playqueue = array(
		"type" => "playlist",
		"uri" => $playlist_uri,
		"name" => escapeQuery($playlist_name),
		"current_track_index" => 0,
		"tracks" => $tracks,
	);
	$w->write($playqueue, 'playqueue.json');
}


/**
 * addAlbumToPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $album_uri
 * @param mixed $album_name
 * @return void
 */
function addAlbumToPlayQueue($w, $album_uri, $album_name) {
	if (!$w->internet()) {
		return false;
	}

	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$use_mopidy                = $settings->use_mopidy;

	if (! $use_mopidy) {
		$tracks = getTheAlbumFullTracks($w, $album_uri);
		if ($tracks == false) {
			displayNotificationWithArtwork($w,"Cannot get tracks for album " . $album_name, './images/warning.png', 'Error!');
			return false;
		}
	} else {
		$tracks = array();
	}

	$playqueue = array(
		"type" => "album",
		"uri" => $album_uri,
		"name" => escapeQuery($album_name),
		"current_track_index" => 0,
		"tracks" => $tracks,
	);
	$w->write($playqueue, 'playqueue.json');
}


/**
 * addArtistToPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $artist_name
 * @param mixed $country_code
 * @return void
 */
function addArtistToPlayQueue($w, $artist_uri, $artist_name, $country_code) {
	if (!$w->internet()) {
		return false;
	}
	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$use_mopidy                = $settings->use_mopidy;
	$country_code 			   = $settings->country_code;

	if (! $use_mopidy) {
		$tracks = getTheArtistFullTracks($w, $artist_uri, $country_code);
		if ($tracks == false) {
			displayNotificationWithArtwork($w,"Cannot get tracks for artist " . $artist_name, './images/warning.png', 'Error!');
			return false;
		}
	} else {
		$tracks = array();
	}

	$playqueue = array(
		"type" => "artist",
		"uri" => $artist_uri,
		"name" => escapeQuery($artist_name),
		"current_track_index" => 0,
		"tracks" => $tracks,
	);
	$w->write($playqueue, 'playqueue.json');
}


/**
 * addTrackToPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @param mixed $track_name
 * @param mixed $artist_name
 * @param mixed $album_name
 * @param mixed $duration
 * @param mixed $country_code
 * @return void
 */
function addTrackToPlayQueue($w, $track_uri, $track_name, $artist_name, $album_name, $duration, $country_code) {
	if (!$w->internet()) {
		return false;
	}
	//
	// Read settings from JSON
	//
	$settings                  = getSettings($w);
	$use_mopidy                = $settings->use_mopidy;

	$track = new stdClass();
	if (! $use_mopidy) {
		$tracks = array();
		$track = getTheFullTrack($w, $track_uri, $country_code);
		if ($track == false) {
			$track = new stdClass();
			$track->uri = $track_uri;
			$track->name = $track_name;
			$artists = array();
			$artist = new stdClass();
			$artist->name = $artist_name;
			$artists[0] = $artist;
			$track->artists = $artists;
			$album = new stdClass();
			$album->name = $album_name;
			$track->album = $album;
			if (is_numeric($duration)) {
				$track->duration_ms = $duration*1000;
			} else {
				$track->duration = $duration;
			}
		}
	} else {
		$tracks = array();
	}

	$playqueue = $w->read('playqueue.json');
	if ($playqueue == false) {
		$tracks[] = $track;
		$newplayqueue = array(
			"type" => "track",
			"uri" => $track_uri,
			"name" => escapeQuery($track_name),
			"current_track_index" => 0,
			"tracks" => $tracks,
		);
	} else {
		// replace current track by new track
		$playqueue->tracks[$playqueue->current_track_index] = $track;
		if (! $use_mopidy) {
			$tracks = $playqueue->tracks;
		}
		if ($playqueue->type != '') {
			$newplayqueue = array(
				"type" => $playqueue->type,
				"uri" => $playqueue->uri,
				"name" => $playqueue->name,
				"current_track_index" => $playqueue->current_track_index,
				"tracks" => $tracks,
			);
		} else {
			$newplayqueue = array(
				"type" => "track",
				"uri" => $track_uri,
				"name" => escapeQuery($track_name),
				"current_track_index" => $playqueue->current_track_index,
				"tracks" => $tracks,
			);
		}
	}
	$w->write($newplayqueue, 'playqueue.json');
}


/**
 * updateCurrentTrackIndexFromPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function updateCurrentTrackIndexFromPlayQueue($w) {
	$playqueue = $w->read('playqueue.json');
	if ($playqueue == false) {
		displayNotificationWithArtwork($w,"No play queue yet", './images/warning.png', 'Error!');
	}
	exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
	if ($retVal != 0) {
		displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
		return;
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$found = false;
		$i = 0;
		$current_track_name = cleanupTrackName($results[0]);
		if (isset($playqueue->tracks)) {
			if (count($playqueue->tracks) > 0) {
				foreach ($playqueue->tracks as $track) {
					$track_name = cleanupTrackName($track->name);
					if (escapeQuery($track_name) == escapeQuery($current_track_name) &&
						escapeQuery($track->artists[0]->name) == escapeQuery($results[1])) {
						$found = true;
						break;
					}
					$i++;
				}
			}
		}

		if ($found == false) {
			// empty queue
			$newplayqueue = array(
				"type" => '',
				"uri" => '',
				"name" => '',
				"current_track_index" => 0,
				"tracks" => array(),
			);
			// displayNotificationWithArtwork($w,"Play Queue has been reset!", './images/warning.png', 'Error!');
		} else {
			$newplayqueue = array(
				"type" => $playqueue->type,
				"uri" => $playqueue->uri,
				"name" => $playqueue->name,
				"current_track_index" => $i,
				"tracks" => $playqueue->tracks,
			);
		}
		$w->write($newplayqueue, 'playqueue.json');
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png');
	}
}


/**
 * getBiography function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $artist_name
 * @return void
 */
function getBiography($w, $artist_uri, $artist_name) {
	//
	// Read settings from JSON
	//
	$settings         = getSettings($w);
	$echonest_api_key = $settings->echonest_api_key;

	$json     = doJsonRequest($w, 'http://developer.echonest.com/api/v4/artist/biographies?api_key=' . $echonest_api_key . '&id=' . $artist_uri);
	$response = $json->response;
	foreach ($response->biographies as $biography) {
		if ($biography->site == "wikipedia") {
			$wikipedia = $biography->text;
			$wikipedia_url = $biography->url;
		}
		if ($biography->site == "last.fm") {
			$lastfm = $biography->text;
			$lastfm_url = $biography->url;
		}
		$default = 'Source: ' . $biography->site . '\n' . $biography->text;
		$default_url = $biography->url;
	}

	if ($lastfm) {
		$text   = $lastfm;
		$source = 'Last FM';
		$url = $lastfm_url;
	} elseif ($wikipedia) {
		$text   = $wikipedia;
		$source = 'Wikipedia';
		$url = $wikipedia_url;
	} else {
		$text   = $default;
		$source = $biography->site;
		$url = $default_url;
	}
	if ($text == "") {
		return array(false, '', '', '');
	}
	$output = strip_tags($text);

	// Get URLs of artist, if available
	$json     = doJsonRequest($w, 'http://developer.echonest.com/api/v4/artist/urls?api_key=' . $echonest_api_key . '&id=' . $artist_uri);

	$twitter_url = '';
	if (isset($json->response->urls->twitter_url)) {
		$twitter_url = $json->response->urls->twitter_url;
	}

	$official_url = '';
	if (isset($json->response->urls->official_url)) {
		$official_url = $json->response->urls->official_url;
	}

	return array($url, $source, $output, $twitter_url, $official_url);
}


/**
 * searchWebApi function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $country_code
 * @param mixed $query
 * @param mixed $type
 * @param int $limit (default: 50)
 * @param bool $actionMode (default: true)
 * @return void
 */
function searchWebApi($w, $country_code, $query, $type, $limit = 50, $actionMode = true) {
	$results = array();

	try {
		if ($limit != 50) {
			$limitSearch = $limit;
		} else {
			$limitSearch = 50;
		}
		$api           = getSpotifyWebAPI($w);
		$searchResults = $api->search($query, $type, array(
				'market' => $country_code,
				'limit' => $limitSearch
			));

		if ($type == 'artist') {
			foreach ($searchResults->artists->items as $item) {
				$results[] = $item;
			}
		} elseif ($type == 'track') {
			foreach ($searchResults->tracks->items as $item) {
				$results[] = $item;
			}
		} elseif ($type == 'album') {
			foreach ($searchResults->albums->items as $item) {
				$results[] = $item;
			}
		} elseif ($type == 'playlist') {
			foreach ($searchResults->playlists->items as $item) {
				$results[] = $item;
			}
		}
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		if ($actionMode == true) {
			logMsg("Error(search): (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);
		} else {
			$w2 = new Workflows('com.vdesabou.spotify.mini.player');
			$w2->result(null, '', "Error: Spotify WEB API returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
			echo $w2->toxml();
		}

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
function playAlfredPlaylist($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$use_mopidy                = $settings->use_mopidy;

	if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
		displayNotificationWithArtwork($w,"Alfred Playlist is not set", './images/warning.png');

		return;
	}
	if ($use_mopidy) {
		playUriWithMopidy($w, $alfred_playlist_uri);
	} else {
		exec("osascript -e 'tell application \"Spotify\" to play track \"$alfred_playlist_uri\"'");
		addPlaylistToPlayQueue($w, $alfred_playlist_uri, $alfred_playlist_name);
	}

	$playlist_artwork_path = getPlaylistArtwork($w, $alfred_playlist_uri, true, true);
	displayNotificationWithArtwork($w,'🔈 Alfred Playlist ' . $alfred_playlist_name, $playlist_artwork_path, 'Play Alfred Playlist');
}


/**
 * lookupCurrentArtist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function lookupCurrentArtist($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp     = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			$artist_uri = getArtistUriFromSearch($w, $results[1]);
		} else {
			$artist_uri = getArtistUriFromTrack($w, $results[4]);
		}

		if ($artist_uri == false) {
			displayNotificationWithArtwork($w,"Cannot get current artist", './images/warning.png', 'Error!');

			return;
		}
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($results[1]) . '▹' . "\"'");
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png');
	}
}


/**
 * displayCurrentArtistBiography function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function displayCurrentArtistBiography($w) {
	if (!$w->internet()) {
		displayNotificationWithArtwork($w,"No internet connection", './images/warning.png');
		return;
	}

	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp     = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			$artist_uri = getArtistUriFromSearch($w, $results[1]);
		} else {
			$artist_uri = getArtistUriFromTrack($w, $results[4]);
		}
		if ($artist_uri == false) {
			displayNotificationWithArtwork($w,"Cannot get current artist", './images/warning.png', 'Error!');

			return;
		}
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Biography▹" . $artist_uri . "∙" . escapeQuery($results[1]) . '▹' . "\"'");
	} else {
		displayNotificationWithArtwork($w,"No artist is playing", './images/warning.png');
	}
}


/**
 * playCurrentArtist function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function playCurrentArtist($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;
	$country_code 			   = $settings->country_code;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp     = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			$artist_uri = getArtistUriFromSearch($w, $results[1]);
		} else {
			$artist_uri = getArtistUriFromTrack($w, $results[4]);
		}
		if ($artist_uri == false) {
			displayNotificationWithArtwork($w,"Cannot get current artist", './images/warning.png', 'Error!');

			return;
		}
		if ($use_mopidy) {
			playUriWithMopidy($w, $artist_uri);
		} else {
			exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
			addArtistToPlayQueue($w, $artist_uri, escapeQuery($results[1]), $country_code);
		}
		displayNotificationWithArtwork($w,'🔈 Artist ' . escapeQuery($results[1]), getArtistArtwork($w, $artist_uri, $results[1], true), 'Play Current Artist');
	} else {
		displayNotificationWithArtwork($w,"No artist is playing", './images/warning.png');
	}
}


/**
 * playCurrentAlbum function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function playCurrentAlbum($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp       = explode(':', $results[4]);
		$album_uri = getAlbumUriFromTrack($w, $results[4]);
		if ($album_uri == false) {
			displayNotificationWithArtwork($w,"Cannot get current album", './images/warning.png', 'Error!');

			return;
		}
		exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
		displayNotificationWithArtwork($w,'🔈 Album ' . escapeQuery($results[2]), getTrackOrAlbumArtwork($w, $results[4], true), 'Play Current Album');
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png');
	}
}


/**
 * addCurrentTrackTo function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackTo($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp     = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			//
			// Read settings from JSON
			//
			$settings = getSettings($w);
			$country_code = $settings->country_code;
			// local track, look it up online

			$query         = 'track:' . strtolower(escapeQuery($results[0])) . ' artist:' . strtolower(escapeQuery($results[1]));
			$searchResults = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($searchResults) > 0) {
				// only one track returned
				$track   = $searchResults[0];
				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;
				logMsg("Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name / $album->uri");
				$results[4] = $track->uri;
			} else {
				logMsg("Could not find track: $results[4] / $results[0] / $results[1] ");
				displayNotificationWithArtwork($w,'Local track ' . escapeQuery($results[0]) . ' has not online match', './images/warning.png', 'Error!');

				return;
			}
		}
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Add▹" . $results[4] . "∙" . escapeQuery($results[0]) . '▹' . "\"'");
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png');
	}
}


/**
 * removeCurrentTrackFrom function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function removeCurrentTrackFrom($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Remove▹" . $results[4] . "∙" . escapeQuery($results[0]) . '▹' . "\"'");
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png');
	}
}


/**
 * addCurrentTrackToAlfredPlaylistOrYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackToAlfredPlaylistOrYourMusic($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;

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
function addCurrentTrackToAlfredPlaylist($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		//
		// Read settings from JSON
		//

		$settings = getSettings($w);

		$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
		$alfred_playlist_uri       = $settings->alfred_playlist_uri;
		$alfred_playlist_name      = $settings->alfred_playlist_name;
		$country_code              = $settings->country_code;

		if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
			displayNotificationWithArtwork($w,"Alfred Playlist is not set", './images/warning.png');

			return;
		}

		$tmp = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			// local track, look it up online

			$query         = 'track:' . strtolower(escapeQuery($results[0])) . ' artist:' . strtolower(escapeQuery($results[1]));
			$searchResults = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($searchResults) > 0) {
				// only one track returned
				$track   = $searchResults[0];
				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;
				logMsg("Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name / $album->uri");
				$results[4] = $track->uri;
			} else {
				logMsg("Could not find track: $results[4] / $results[0] / $results[1] ");
				displayNotificationWithArtwork($w,'Local track ' . escapeQuery($results[0]) . ' has not online match', './images/warning.png', 'Error!');

				return;
			}
		}

		$tmp = explode(':', $results[4]);
		$ret = addTracksToPlaylist($w, $tmp[2], $alfred_playlist_uri, $alfred_playlist_name, false);
		if (is_numeric($ret) && $ret > 0) {
			displayNotificationWithArtwork($w,'' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' added to Alfred Playlist ' . $alfred_playlist_name, getTrackOrAlbumArtwork($w, $results[4], true), 'Add Current Track to Alfred Playlist');
		} elseif (is_numeric($ret) && $ret == 0) {
			displayNotificationWithArtwork($w,'' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' is already in Alfred Playlist ' . $alfred_playlist_name, './images/warning.png', 'Error!');
		}
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png', 'Error!');
	}
}


/**
 * addCurrentTrackToYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function addCurrentTrackToYourMusic($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		$tmp     = explode(':', $results[4]);
		if (isset($tmp[1]) && $tmp[1] == 'local') {
			//
			// Read settings from JSON
			//

			$settings     = getSettings($w);
			$country_code = $settings->country_code;
			// local track, look it up online

			$query         = 'track:' . strtolower(escapeQuery($results[0])) . ' artist:' . strtolower(escapeQuery($results[1]));
			$searchResults = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($searchResults) > 0) {
				// only one track returned
				$track   = $searchResults[0];
				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;
				logMsg("Unknown track $results[4] / $results[0] / $results[1] replaced by track: $track->uri / $track->name / $artist->name / $album->uri");
				$results[4] = $track->uri;
			} else {
				logMsg("Could not find track: $results[4] / $results[0] / $results[1] ");
				displayNotificationWithArtwork($w,'Local track ' . escapeQuery($results[0]) . ' has not online match', './images/warning.png', 'Error!');

				return;
			}
		}
		$tmp = explode(':', $results[4]);
		$ret = addTracksToYourMusic($w, $tmp[2], false);
		if (is_numeric($ret) && $ret > 0) {
			displayNotificationWithArtwork($w,'' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' added to Your Music', getTrackOrAlbumArtwork($w, $results[4], true), 'Add Current Track to Your Music');
		} elseif (is_numeric($ret) && $ret == 0) {
			displayNotificationWithArtwork($w,'' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' is already in Your Music', './images/warning.png');
		}
	} else {
		displayNotificationWithArtwork($w,"No track is playing", './images/warning.png', 'Error!');
	}
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
function addTracksToYourMusic($w, $tracks, $allow_duplicate = true) {
	$tracks             = (array) $tracks;
	$tracks_with_no_dup = array();
	$tracks_contain     = array();
	if (!$allow_duplicate) {
		try {
			$api    = getSpotifyWebAPI($w);
			// Note: max 50 Ids
			$offset = 0;
			do {
				$output = array_slice($tracks, $offset, 50);
				$offset += 50;

				if (count($output)) {
					// refresh api
					$api            = getSpotifyWebAPI($w, $api);
					$tracks_contain = $api->myTracksContains($output);
					for ($i = 0; $i < count($output); $i++) {
						if (!$tracks_contain[$i]) {
							$tracks_with_no_dup[] = $output[$i];
						}
					}
				}
			} while (count($output) > 0);

			$tracks = $tracks_with_no_dup;
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(addTracksToYourMusic): (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);
			return false;
		}
	}

	if (count($tracks) != 0) {
		try {
			$api    = getSpotifyWebAPI($w);
			$offset = 0;
			do {
				$output = array_slice($tracks, $offset, 50);
				$offset += 50;

				if (count($output)) {
					// refresh api
					$api = getSpotifyWebAPI($w, $api);
					$api->addMyTracks($output);
				}
			} while (count($output) > 0);
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(addTracksToYourMusic): (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);

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
function addTracksToPlaylist($w, $tracks, $playlist_uri, $playlist_name, $allow_duplicate = true, $refreshLibrary = true) {
	//
	// Read settings from JSON
	//
	$settings = getSettings($w);
	$userid   = $settings->userid;

	$tracks_with_no_dup = array();
	if (!$allow_duplicate) {
		$playlist_tracks = getThePlaylistTracks($w, $playlist_uri);
		foreach ((array) $tracks as $track) {
			if (!checkIfDuplicate($playlist_tracks, $track)) {
				$tracks_with_no_dup[] = $track;
			}
		}
		$tracks = $tracks_with_no_dup;
	}

	if (count($tracks) != 0) {
		try {
			$api = getSpotifyWebAPI($w);
			$tmp = explode(':', $playlist_uri);

			// Note: max 100 Ids
			$offset = 0;
			$i      = 0;
			do {
				$output = array_slice($tracks, $offset, 100);
				$offset += 100;

				if (count($output)) {
					// refresh api
					$api = getSpotifyWebAPI($w, $api);
					$api->addUserPlaylistTracks(urlencode($userid), $tmp[4], $output, array(
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
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(addTracksToPlaylist): (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);
			return false;
		}

		if ($refreshLibrary) {
			refreshLibrary($w);
		}
	}

	return count($tracks);
}


/**
 * removeTrackFromPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @param mixed $playlist_uri
 * @param mixed $playlist_name
 * @param bool $refreshLibrary (default: true)
 * @return void
 */
function removeTrackFromPlaylist($w, $track_uri, $playlist_uri, $playlist_name, $refreshLibrary = true) {
	//
	// Read settings from JSON
	//
	$settings = getSettings($w);
	$userid   = $settings->userid;

	try {
		$api = getSpotifyWebAPI($w);
		$tmp = explode(':', $playlist_uri);
		$api->deleteUserPlaylistTracks(urlencode($userid), $tmp[4], array(
				array(
					'id' => $track_uri
				)
			));
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(removeTrackFromPlaylist): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
		return false;
	}


	if ($refreshLibrary) {
		refreshLibrary($w);
	}

	return true;
}


/**
 * removeTrackFromYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @param bool $refreshLibrary (default: true)
 * @return void
 */
function removeTrackFromYourMusic($w, $track_uri, $refreshLibrary = true) {
	//
	// Read settings from JSON
	//
	$settings = getSettings($w);
	$userid   = $settings->userid;

	try {
		$api = getSpotifyWebAPI($w);
		$api->deleteMyTracks($track_uri);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(removeTrackFromYourMusic): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
		return false;
	}


	if ($refreshLibrary) {
		refreshLibrary($w);
	}

	return true;
}


/**
 * getRandomTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getRandomTrack($w) {
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
		$db = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$getTracks = "select uri,track_name,artist_name,album_name,duration from tracks order by random() limit 1";
		$stmt      = $db->prepare($getTracks);
		$stmt->execute();
		$track       = $stmt->fetch();
		$thetrackuri = $track[0];
		$thetrackname = $track[1];
		$theartistname = $track[2];
		$thealbumname = $track[3];
		$theduration = $track[4];
	}
	catch (PDOException $e) {
		handleDbIssuePdoEcho($db, $w);
	}

	return array($thetrackuri, $thetrackname, $theartistname, $thealbumname, $theduration);
}


/**
 * getRandomAlbum function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getRandomAlbum($w) {
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
	// Get random album from DB
	//
	try {
		$db = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$getTracks = "select album_uri,album_name,artist_name from tracks order by random() limit 1";
		$stmt      = $db->prepare($getTracks);
		$stmt->execute();
		$track       = $stmt->fetch();
		$thealbumuri = $track[0];
		$thealbumname = $track[1];
		$theartistname = $track[2];
	}
	catch (PDOException $e) {
		handleDbIssuePdoEcho($db, $w);
	}

	return array($thealbumuri, $thealbumname, $theartistname);
}


/**
 * getArtistUriFromTrack function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @return void
 */
function getArtistUriFromTrack($w, $track_uri) {
	try {
		$tmp = explode(':', $track_uri);

		if (isset($tmp[1]) && $tmp[1] == 'local') {
			// local track, look it up online
			// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
			// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

			$query   = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
			$results = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($results) > 0) {
				// only one track returned
				$track   = $results[0];
				$artists = $track->artists;
				$artist  = $artists[0];

				return $artist->uri;
			} else {
				logMsg("Could not find artist from uri: $track_uri");

				return false;
			}
		}
		$api     = getSpotifyWebAPI($w);
		$track   = $api->getTrack($tmp[2]);
		$artists = $track->artists;
		$artist  = $artists[0];

		return $artist->uri;
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		echo "Error(getArtistUriFromTrack): (exception " . print_r($e) . ")";
		handleSpotifyWebAPIException($w, $e);
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
function getArtistUriFromSearch($w, $artist_name, $country_code = '') {
	if ($artist_name == '') {
		return false;
	}
	if ($country_code == '') {
		//
		// Read settings from JSON
		//

		$settings = getSettings($w);

		$country_code = $settings->country_code;
	}
	$searchResults = searchWebApi($w, $country_code, $artist_name, 'artist', 1);

	if (count($searchResults) > 0) {
		// only one artist returned
		$artist = $searchResults[0];
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
function getAlbumUriFromTrack($w, $track_uri) {
	try {
		$tmp = explode(':', $track_uri);

		if (isset($tmp[1]) && $tmp[1] == 'local') {
			//
			// Read settings from JSON
			//
			$settings            = getSettings($w);
			$country_code        = $settings->country_code;
			// local track, look it up online
			// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
			// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

			$query   = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
			$results = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($results) > 0) {
				// only one track returned
				$track = $results[0];
				$album = $track->album;

				return $album->uri;
			} else {
				logMsg("Could not find album from uri: $track_uri");

				return false;
			}
		}
		$api   = getSpotifyWebAPI($w);
		$track = $api->getTrack($tmp[2]);
		$album = $track->album;
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getAlbumUriFromTrack): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

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
function clearPlaylist($w, $playlist_uri, $playlist_name) {
	try {
		$tmp         = explode(':', $playlist_uri);
		$emptytracks = array();
		$api         = getSpotifyWebAPI($w);
		$api->replacePlaylistTracks(urlencode($tmp[2]), $tmp[4], $emptytracks);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(clearPlaylist): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

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
function createTheUserPlaylist($w, $playlist_name) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);
	$userid   = $settings->userid;
	$is_public_playlists        = $settings->is_public_playlists;

	$public = false;
	if ($is_public_playlists) {
		$public = true;
	}
	try {
		$api  = getSpotifyWebAPI($w);
		$json = $api->createUserPlaylist(urlencode($userid), array(
				'name' => $playlist_name,
				'public' => $public
			));
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(createUserPlaylist): createUserPlaylist " . $playlist_name . " (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

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
function createRadioArtistPlaylistForCurrentArtist($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		createRadioArtistPlaylist($w, $results[1]);
	} else {
		displayNotificationWithArtwork($w,"Cannot get current artist", './images/warning.png', 'Error!');
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
function createRadioArtistPlaylist($w, $artist_name) {
	//
	// Read settings from JSON
	//

	$settings             = getSettings($w);
	$radio_number_tracks  = $settings->radio_number_tracks;
	$userid               = $settings->userid;
	$echonest_api_key     = $settings->echonest_api_key;
	$is_public_playlists  = $settings->is_public_playlists;
	$is_autoplay_playlist = $settings->is_autoplay_playlist;

	$public = false;
	if ($is_public_playlists) {
		$public = true;
	}

	$json = doJsonRequest($w, 'http://developer.echonest.com/api/v4/playlist/static?api_key=' . $echonest_api_key . '&artist=' . urlencode($artist_name) . '&format=json&results=' . $radio_number_tracks . '&distribution=focused&type=artist-radio&bucket=id:spotify&bucket=tracks');

	$response = $json->response;

	$newplaylisttracks = array();
	foreach ($response->songs as $song) {
		foreach ($song->tracks as $track) {
			$foreign_id          = $track->foreign_id;
			$tmp                 = explode(':', $foreign_id);
			$newplaylisttracks[] = $tmp[2];
			// only take one
			break;
		}
	}

	if (count($newplaylisttracks) > 0) {
		try {
			$api  = getSpotifyWebAPI($w);
			$json = $api->createUserPlaylist($userid, array(
					'name' => 'Artist radio for ' . escapeQuery($artist_name),
					'public' => $public
				));
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(createUserPlaylist): radio artist " . $artist_name . " (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);

			return false;
		}

		$ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
		if (is_numeric($ret) && $ret > 0) {
			if($is_autoplay_playlist) {
				sleep(2);
				exec("osascript -e 'tell application \"Spotify\" to play track \"$json->uri\"'");
				$playlist_artwork_path = getPlaylistArtwork($w, $json->uri, true, false);
				displayNotificationWithArtwork($w,'🔈 Playlist ' . $json->name, $playlist_artwork_path, 'Launch Artist Radio Playlist');
			}
			refreshLibrary($w);

			return;
		} elseif (is_numeric($ret) && $ret == 0) {
			displayNotificationWithArtwork($w,'Playlist ' . $json->name . ' cannot be added', './images/warning.png', 'Error!');

			return;
		}
	} else {
		displayNotificationWithArtwork($w,'Artist was not found in Echo Nest', './images/warning.png', 'Error!');

		return false;
	}

	return true;
}

/**
 * createCompleteCollectionArtistPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_name
 * @return void
 */
function createCompleteCollectionArtistPlaylist($w, $artist_name, $artist_uri) {
	//
	// Read settings from JSON
	//
	$settings             = getSettings($w);
	$userid               = $settings->userid;
	$country_code         = $settings->country_code;
	$is_public_playlists  = $settings->is_public_playlists;
	$is_autoplay_playlist = $settings->is_autoplay_playlist;

	$public = false;
	if ($is_public_playlists) {
		$public = true;
	}

	$newplaylisttracks = array();
	// call to web api, if it fails,
	// it displays an error in main window
	$albums = getTheArtistAlbums($w, $artist_uri, $country_code, true, false);

	foreach ($albums as $album) {
		// call to web api, if it fails,
		// it displays an error in main window
		$tracks = getTheAlbumFullTracks($w, $album->uri, true);
		foreach ($tracks as $track) {
			$tmp                 = explode(':', $track->uri);
			$newplaylisttracks[] = $tmp[2];
		}
	}

	if (count($newplaylisttracks) > 0) {
		try {
			$api  = getSpotifyWebAPI($w);
			$json = $api->createUserPlaylist($userid, array(
					'name' => 'CC for ' . escapeQuery($artist_name),
					'public' => $public
				));
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(createCompleteCollectionArtistPlaylist): Complete Collection " . $artist_name . " (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);

			return false;
		}

		$ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
		if (is_numeric($ret) && $ret > 0) {
			if($is_autoplay_playlist) {
				sleep(2);
				exec("osascript -e 'tell application \"Spotify\" to play track \"$json->uri\"'");
				$playlist_artwork_path = getPlaylistArtwork($w, $json->uri, true, false);
				displayNotificationWithArtwork($w,'🔈 Playlist ' . $json->name, $playlist_artwork_path, 'Launch Complete Collection Playlist');
			}
			refreshLibrary($w);

			return;
		} elseif (is_numeric($ret) && $ret == 0) {
			displayNotificationWithArtwork($w,'Playlist ' . $json->name . ' cannot be added', './images/warning.png', 'Error!');

			return;
		}
	} else {
		displayNotificationWithArtwork($w,'No track was found for artist ' . $artist_name, './images/warning.png', 'Error!');

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
function createRadioSongPlaylistForCurrentTrack($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		createRadioSongPlaylist($w, $results[0], $results[4], $results[1]);
	} else {
		displayNotificationWithArtwork($w,"There is not track currently playing", './images/warning.png', 'Error!');
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
function createRadioSongPlaylist($w, $track_name, $track_uri, $artist_name) {
	//
	// Read settings from JSON
	//

	$settings             = getSettings($w);
	$radio_number_tracks  = $settings->radio_number_tracks;
	$userid               = $settings->userid;
	$echonest_api_key     = $settings->echonest_api_key;
	$country_code         = $settings->country_code;
	$is_public_playlists  = $settings->is_public_playlists;
	$is_autoplay_playlist = $settings->is_autoplay_playlist;

	$public = false;
	if ($is_public_playlists) {
		$public = true;
	}

	$tmp = explode(':', $track_uri);
	if (isset($tmp[1]) && $tmp[1] == 'local') {
		// local track, look it up online
		// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
		// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

		$query   = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
		$results = searchWebApi($w, $country_code, $query, 'track', 1);

		if (count($results) > 0) {
			// only one track returned
			$track     = $results[0];
			$track_uri = $track->uri;
		} else {
			logMsg("Could not find track from uri: $track_uri");

			return false;
		}
	}

	$json = doJsonRequest($w, 'http://developer.echonest.com/api/v4/playlist/static?api_key=' . $echonest_api_key . '&song_id=' . $track_uri . '&format=json&results=' . $radio_number_tracks . '&distribution=focused&type=song-radio&bucket=id:spotify&bucket=tracks');

	$response = $json->response;

	$newplaylisttracks = array();
	foreach ($response->songs as $song) {
		foreach ($song->tracks as $track) {
			$foreign_id          = $track->foreign_id;
			$tmp                 = explode(':', $foreign_id);
			$newplaylisttracks[] = $tmp[2];
			// only take one
			break;
		}
	}

	if (count($newplaylisttracks) > 0) {
		try {
			$api  = getSpotifyWebAPI($w);
			$json = $api->createUserPlaylist($userid, array(
					'name' => 'Song radio for ' . escapeQuery($track_name) . ' by ' . escapeQuery($artist_name),
					'public' => $public
				));
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(createUserPlaylist): radio song " . escapeQuery($track_name) . " (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);

			return false;
		}

		$ret = addTracksToPlaylist($w, $newplaylisttracks, $json->uri, $json->name, false, false);
		if (is_numeric($ret) && $ret > 0) {
			if($is_autoplay_playlist) {
				sleep(2);
				exec("osascript -e 'tell application \"Spotify\" to play track \"$json->uri\"'");
				$playlist_artwork_path = getPlaylistArtwork($w, $json->uri, true, false);
				displayNotificationWithArtwork($w,'🔈 Playlist ' . $json->name, $playlist_artwork_path, 'Launch Radio Playlist');
			}
			refreshLibrary($w);

			return;
		} elseif (is_numeric($ret) && $ret == 0) {
			displayNotificationWithArtwork($w,'Playlist ' . $json->name . ' cannot be added', './images/warning.png', 'Error!');

			return;
		}
	} else {
		displayNotificationWithArtwork($w,'Track was not found in Echo Nest', './images/warning.png', 'Error!');

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
function getThePlaylistTracks($w, $playlist_uri) {
	$tracks = array();
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$country_code = $settings->country_code;
	try {
		$api                         = getSpotifyWebAPI($w);
		$tmp                         = explode(':', $playlist_uri);
		$offsetGetUserPlaylistTracks = 0;
		$limitGetUserPlaylistTracks  = 100;
		do {
			// refresh api
			$api                = getSpotifyWebAPI($w, $api);
			$userPlaylistTracks = $api->getUserPlaylistTracks($tmp[2], $tmp[4], array(
					'fields' => array(
						'total',
						'items.track(id,is_playable,linked_from)',
						'items(is_local)'
					),
					'limit' => $limitGetUserPlaylistTracks,
					'offset' => $offsetGetUserPlaylistTracks,
					'market' => $country_code
				));

			foreach ($userPlaylistTracks->items as $item) {
				$track    = $item->track;
				if (isset($track->is_playable) && $track->is_playable) {
					if(isset($track->linked_from) && isset($track->linked_from->id)) {
						$track->id = $track->linked_from->id;
					}
					$tracks[] = $track->id;
				}
				if (isset($item->is_local) && $item->is_local) {
					$tracks[] = $track->id;
				}
			}

			$offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
		} while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getThePlaylistTracks): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);
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
function getTheAlbumTracks($w, $album_uri) {
	$tracks = array();
	try {
		$api                  = getSpotifyWebAPI($w);
		$tmp                  = explode(':', $album_uri);
		$offsetGetAlbumTracks = 0;
		$limitGetAlbumTracks  = 50;
		do {
			// refresh api
			$api         = getSpotifyWebAPI($w, $api);
			$albumTracks = $api->getAlbumTracks($tmp[2], array(
					'limit' => $limitGetAlbumTracks,
					'offset' => $offsetGetAlbumTracks
				));

			foreach ($albumTracks->items as $track) {
				$tracks[] = $track->id;
			}
			$offsetGetAlbumTracks += $limitGetAlbumTracks;
		} while ($offsetGetAlbumTracks < $albumTracks->total);

	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getTheAlbumTracks): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

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
 * @param bool $actionMode (default: false)
 * @param bool $all_type (default: true)
 * @return void
 */
function getTheArtistAlbums($w, $artist_uri, $country_code, $actionMode = false, $all_type = true ) {
	$album_ids = array();

	try {
		$api                   = getSpotifyWebAPI($w);
		$tmp                   = explode(':', $artist_uri);
		$offsetGetArtistAlbums = 0;
		$limitGetArtistAlbums  = 50;

		if($all_type) {
			$album_type = array(
						'album',
						'single',
						'compilation'
					);
		} else {
			$album_type = array(
						'album',
						'single'
					);
		}
		do {
			// refresh api
			$api              = getSpotifyWebAPI($w, $api);
			$userArtistAlbums = $api->getArtistAlbums($tmp[2], array(
					'album_type' => $album_type,
					'market' => $country_code,
					'limit' => $limitGetArtistAlbums,
					'offset' => $offsetGetArtistAlbums
				));

			foreach ($userArtistAlbums->items as $album) {
				$album_ids[] = $album->id;
			}

			$offsetGetArtistAlbums += $limitGetArtistAlbums;
		} while ($offsetGetArtistAlbums < $userArtistAlbums->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		if ($actionMode == false) {
			$w2 = new Workflows('com.vdesabou.spotify.mini.player');
			$w2->result(null, '', "Error: Spotify WEB API getArtistAlbums returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
			echo $w2->toxml();
			exit;

		} else {
			echo "Error(getTheArtistAlbums): (exception " . print_r($e) . ")";
			handleSpotifyWebAPIException($w, $e);
			return false;
		}



	}

	$albums = array();

	try {
		// Note: max 20 Ids
		$offset = 0;
		do {
			$output = array_slice($album_ids, $offset, 20);
			$offset += 20;

			if (count($output)) {
				// refresh api
				$api             = getSpotifyWebAPI($w, $api);
				$resultGetAlbums = $api->getAlbums($output);
				foreach ($resultGetAlbums->albums as $album) {
					$albums[] = $album;
				}
			}
		} while (count($output) > 0);
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w2 = new Workflows('com.vdesabou.spotify.mini.player');
		$w2->result(null, '', "Error: Spotify WEB API getAlbums returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
		echo $w2->toxml();
		exit;
	}

	return $albums;
}


/**
 * getTheAlbumFullTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $album_uri
 * @param bool $actionMode (default: true)
 * @return void
 */
function getTheAlbumFullTracks($w, $album_uri, $actionMode = false) {
	$tracks = array();
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);
	$country_code = $settings->country_code;

	try {
		$api                  = getSpotifyWebAPI($w);
		$tmp                  = explode(':', $album_uri);
		$offsetGetAlbumTracks = 0;
		$limitGetAlbumTracks  = 50;
		do {
			// refresh api
			$api         = getSpotifyWebAPI($w, $api);
			$albumTracks = $api->getAlbumTracks($tmp[2], array(
					'limit' => $limitGetAlbumTracks,
					'offset' => $offsetGetAlbumTracks,
					'market' => $country_code
				));

			foreach ($albumTracks->items as $track) {
				$tracks[] = $track;
			}

			$offsetGetAlbumTracks += $limitGetAlbumTracks;
		} while ($offsetGetAlbumTracks < $albumTracks->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		if ($actionMode == false) {
			$w2 = new Workflows('com.vdesabou.spotify.mini.player');
			$w2->result(null, '', "Error: Spotify WEB API getAlbumTracks returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
			echo $w2->toxml();
			exit;
		} else {
			echo "Error(getTheAlbumFullTracks): (exception " . print_r($e) . ")";
			handleSpotifyWebAPIException($w, $e);
			return false;
		}
	}

	return $tracks;
}


/**
 * getThePlaylistFullTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @return void
 */
function getThePlaylistFullTracks($w, $playlist_uri) {
	$tracks = array();
	try {
		$api                         = getSpotifyWebAPI($w);
		$tmp                         = explode(':', $playlist_uri);
		$offsetGetUserPlaylistTracks = 0;
		$limitGetUserPlaylistTracks  = 100;
		do {
			// refresh api
			$api                = getSpotifyWebAPI($w, $api);
			$userPlaylistTracks = $api->getUserPlaylistTracks($tmp[2], $tmp[4], array(
					'fields' => array(
						'total',
						'items(added_at)',
						'items(is_local)',
						'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
						'items.track.album(album_type,images,uri,name)',
						'items.track.artists(name,uri)'
					),
					'limit' => $limitGetUserPlaylistTracks,
					'offset' => $offsetGetUserPlaylistTracks
				));

			foreach ($userPlaylistTracks->items as $item) {
				$tracks[] = $item->track;
			}

			$offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
		} while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getThePlaylistFullTracks): playlist uri " . $playlist_uri . " (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

		return false;
	}

	return $tracks;
}


/**
 * getTheArtistFullTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $country_code
 * @return void
 */
function getTheArtistFullTracks($w, $artist_uri, $country_code) {
	$tracks = array();
	try {
		$api                         = getSpotifyWebAPI($w);
		$tmp                         = explode(':', $artist_uri);
		$artistTopTracks = $api->getArtistTopTracks($tmp[2], array(
					'country' => $country_code
				));

		foreach ($artistTopTracks->tracks as $track) {
			$tracks[] = $track;
		}
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getTheArtistFullTracks): artist uri " . $artist_uri . " (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

		return false;
	}

	return $tracks;
}


/**
 * getTheFullTrack function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $track_uri
 * @param mixed $country_code
 * @return void
 */
function getTheFullTrack($w, $track_uri, $country_code) {
	try {
		$tmp = explode(':', $track_uri);

		if (isset($tmp[1]) && $tmp[1] == 'local') {
			// local track, look it up online
			// spotify:local:The+D%c3%b8:On+My+Shoulders+-+Single:On+My+Shoulders:318
			// spotify:local:Damien+Rice:B-Sides:Woman+Like+a+Man+%28Live%2c+Unplugged%29:284

			$query   = 'track:' . urldecode(strtolower($tmp[4])) . ' artist:' . urldecode(strtolower($tmp[2]));
			$results = searchWebApi($w, $country_code, $query, 'track', 1);

			if (count($results) > 0) {
				// only one track returned
				$track   = $results[0];
				return $track;
			} else {
				logMsg("Could not find track from uri: $track_uri");
				return false;
			}
		}
		$api     = getSpotifyWebAPI($w);
		$track   = $api->getTrack($tmp[2]);
		return $track;
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		echo "Error(getTheFullTrack): (exception " . print_r($e) . ")";
		handleSpotifyWebAPIException($w, $e);
	}

	return false;
}


/**
 * getTheArtistRelatedArtists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @return void
 */
function getTheArtistRelatedArtists($w, $artist_uri) {
	$relateds = array();

	try {
		$api = getSpotifyWebAPI($w);
		$tmp = explode(':', $artist_uri);

		$relatedArtists = $api->getArtistRelatedArtists($tmp[2]);

		foreach ($relatedArtists->artists as $related) {
			$relateds[] = $related;
		}
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w2 = new Workflows('com.vdesabou.spotify.mini.player');
		$w2->result(null, '', "Error: Spotify WEB API getArtistRelatedArtists returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
		echo $w2->toxml();
		exit;
	}

	return $relateds;
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
function getTheNewReleases($w, $country_code, $max_results = 50) {
	$album_ids = array();

	try {
		$api                  = getSpotifyWebAPI($w);
		$offsetGetNewReleases = 0;
		$limitGetNewReleases  = 50;
		do {
			// refresh api
			$api               = getSpotifyWebAPI($w, $api);
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
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
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
				// refresh api
				$api             = getSpotifyWebAPI($w, $api);
				$resultGetAlbums = $api->getAlbums($output);
				foreach ($resultGetAlbums->albums as $album) {
					$albums[] = $album;
				}
			}
		} while (count($output) > 0);
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w2 = new Workflows('com.vdesabou.spotify.mini.player');
		$w2->result(null, '', "Error: Spotify WEB API getNewReleases from getNewReleases returned error " . $e->getMessage(), "Try again or report to author", './images/warning.png', 'no', null, '');
		echo $w2->toxml();
		exit;
	}

	return $albums;
}


/**
 * computeTime function.
 *
 * @access public
 * @return void
 */
function computeTime() {
	list($msec, $sec) = explode(' ', microtime());

	return (float) $sec + (float) $msec;
}


/**
 * truncateStr function.
 *
 * @access public
 * @param mixed $input
 * @param mixed $length
 * @return void
 */
function truncateStr($input, $length) {
	// only truncate if input is actually longer than $length
	if (strlen($input) > $length) {
		// check if there are any spaces at all and if the last one is within
		// the given length if so truncate at space else truncate at length.
		if (strrchr($input, " ") && strrchr($input, " ") < $length) {
			return substr( $input, 0, strrpos( substr( $input, 0, $length), ' ' ) )."…";
		}
		else {
			return substr( $input, 0, $length )."…";
		}
	}
	else {
		return $input;
	}
}


/**
 * getPlaylistsForTrack function.
 *
 * @access public
 * @param mixed $db
 * @param mixed $track_uri
 * @return void
 */
function getPlaylistsForTrack($db, $track_uri) {
	$playlistsfortrack    = "";
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
					$playlistsfortrack = $playlistsfortrack . " ● ♫ : " . truncateStr($playlist[0], 30);
				}
			} else {
				if ($playlist[0] == "") {
					$playlistsfortrack = $playlistsfortrack . " ○ " . 'Your Music';
				} else {
					$playlistsfortrack = $playlistsfortrack . " ○ " . truncateStr($playlist[0], 30);
				}
			}
			$noresult = false;
		}
	}


	catch (PDOException $e) {
		return "";
	}

	return $playlistsfortrack;
}


/**
 * getNumberOfTracksForAlbum function.
 *
 * @access public
 * @param mixed $db
 * @param mixed $album_uri
 * @return void
 */
function getNumberOfTracksForAlbum($db, $album_uri, $yourmusiconly = false) {
	if ($yourmusiconly == false) {
		$getNumberOfTracksForAlbum = "select count(distinct track_name) from tracks where album_uri=:album_uri";
	} else {
		$getNumberOfTracksForAlbum = "select count(distinct track_name) from tracks where yourmusic=1 and album_uri=:album_uri";
	}
	try {
		$stmt = $db->prepare($getNumberOfTracksForAlbum);
		$stmt->bindValue(':album_uri', '' . $album_uri . '');
		$stmt->execute();
		$nb = $stmt->fetch();
	}
	catch (PDOException $e) {
		return 0;
	}

	return $nb[0];
}


/**
 * getNumberOfTracksForArtist function.
 *
 * @access public
 * @param mixed $db
 * @param mixed $artist_name
 * @return void
 */
function getNumberOfTracksForArtist($db, $artist_name, $yourmusiconly = false) {
	if ($yourmusiconly == false) {
		$getNumberOfTracksForArtist = "select count(distinct track_name) from tracks where artist_name=:artist_name";
	} else {
		$getNumberOfTracksForArtist = "select count(distinct track_name) from tracks where yourmusic=1 and artist_name=:artist_name";
	}

	try {
		$stmt = $db->prepare($getNumberOfTracksForArtist);
		$stmt->bindValue(':artist_name', '' . $artist_name . '');
		$stmt->execute();
		$nb = $stmt->fetch();
	}
	catch (PDOException $e) {
		return 0;
	}

	return $nb[0];
}


/**
 * escapeQuery function.
 *
 * @access public
 * @param mixed $text
 * @return void
 */
function escapeQuery($text) {
	$text = str_replace("'", "’", $text);
	$text = str_replace('"', "", $text);
	$text = str_replace("&apos;", "’", $text);
	$text = str_replace("`", "’", $text);
	$text = str_replace("&amp;", "and", $text);
	$text = str_replace("&", "and", $text);
	$text = str_replace("\\", " ", $text);
	$text = str_replace("$", "\\$", $text);

	if(startswith($text, "’")) {
		$text = ltrim ($text, "’");
	}
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
function checkIfResultAlreadyThere($results, $title) {
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
function checkIfDuplicate($track_ids, $id) {
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
 * @param mixed $w
 * @param mixed $subtitle
 * @param mixed $artwork
 * @param string $title (default: 'Spotify Mini Player')
 * @return void
 */
function displayNotificationWithArtwork($w,$subtitle, $artwork, $title = 'Spotify Mini Player') {
	//
	// Read settings from JSON
	//
	$settings            = getSettings($w);
	$use_growl           = $settings->use_growl;

	if(!$use_growl) {
		if ($artwork != "" && file_exists($artwork)) {
			copy($artwork, "/tmp/tmp");
		}
		exec("./terminal-notifier.app/Contents/MacOS/terminal-notifier -title '" . $title . "' -sender 'com.spotify.miniplayer' -contentImage '/tmp/tmp' -message '" . $subtitle . "'");
	} else {
		exec("./src/growl_notification.ksh -t \"" . $title . "\" -s \"" . $subtitle . "\" >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
	}

}


/**
 * displayNotificationForCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function displayNotificationForCurrentTrack($w) {
	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;
	$is_display_rating         = $settings->is_display_rating;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		displayNotificationWithArtwork($w,'🔈 ' . escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]) . ' in album ' . escapeQuery($results[2]), getTrackOrAlbumArtwork($w, $results[4], true), 'Now Playing ' . floatToStars(($results[6] / 100) ? $is_display_rating : 0) . ' (' . beautifyTime($results[5] / 1000) . ')');
	}
}


/**
 * displayLyricsForCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function displayLyricsForCurrentTrack($w) {
	if (!$w->internet()) {
		displayNotificationWithArtwork($w,"No internet connection", './images/warning.png');

		return;
	}

	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			displayNotificationWithArtwork($w,'AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
			exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Lyrics▹" . $results[4] . "∙" . escapeQuery($results[1]) . "∙" . escapeQuery($results[0]) . "\"'");
	} else {
		displayNotificationWithArtwork($w,"There is not track currently playing", './images/warning.png', 'Error!');
	}
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
		displayNotificationWithArtwork($w,"Download Artworks,
	No internet connection", './images/warning.png');

		return;
	}

	touch($w->data() . "/download_artworks_in_progress");
	$w->write('Download Artworks▹' . 0 . '▹' . 0 . '▹' . time(), 'download_artworks_in_progress');
	$in_progress_data = $w->read('download_artworks_in_progress');
	$words            = explode('▹', $in_progress_data);

	//
	// Read settings from JSON
	//
	$settings            = getSettings($w);
	$userid              = $settings->userid;

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	//
	// Get list of artworks to download from DB
	//
	$nb_artworks_total = 0;
	$nb_artworks       = 0;

	$dbfile = $w->data() . '/fetch_artworks.db';
	if (file_exists($dbfile)) {
		try {
			$dbartworks = new PDO("sqlite:$dbfile", "", "", array(
					PDO::ATTR_PERSISTENT => true
				));
			$dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$getCount = 'select count(artist_name) from artists where already_fetched=0';
			$stmt     = $dbartworks->prepare($getCount);
			$stmt->execute();
			$count = $stmt->fetch();
			$nb_artworks_total += intval($count[0]);

			$getCount = 'select count(track_uri) from tracks where already_fetched=0';
			$stmt     = $dbartworks->prepare($getCount);
			$stmt->execute();
			$count = $stmt->fetch();
			$nb_artworks_total += intval($count[0]);

			$getCount = 'select count(album_uri) from albums where already_fetched=0';
			$stmt     = $dbartworks->prepare($getCount);
			$stmt->execute();
			$count = $stmt->fetch();
			$nb_artworks_total += intval($count[0]);

			if ($nb_artworks_total != 0) {
				displayNotificationWithArtwork($w,"Start downloading " . $nb_artworks_total . " artworks", './images/artworks.png', 'Artworks');

				// artists
				$getArtists     = "select artist_uri,artist_name from artists where already_fetched=0";
				$stmtGetArtists = $dbartworks->prepare($getArtists);

				$updateArtist     = "update artists set already_fetched=1 where artist_uri=:artist_uri";
				$stmtUpdateArtist = $dbartworks->prepare($updateArtist);

				// tracks
				$getTracks     = "select track_uri from tracks where already_fetched=0";
				$stmtGetTracks = $dbartworks->prepare($getTracks);

				$updateTrack     = "update tracks set already_fetched=1 where track_uri=:track_uri";
				$stmtUpdateTrack = $dbartworks->prepare($updateTrack);

				// albums
				$getAlbums     = "select album_uri from albums where already_fetched=0";
				$stmtGetAlbums = $dbartworks->prepare($getAlbums);

				$updateAlbum     = "update albums set already_fetched=1 where album_uri=:album_uri";
				$stmtUpdateAlbum = $dbartworks->prepare($updateAlbum);

				////
				// Artists
				//
				$artists = $stmtGetArtists->execute();

				while ($artist = $stmtGetArtists->fetch()) {
					$ret = getArtistArtwork($w, $artist[0], $artist[1], true, false, true);
					if ($ret == false) {
						logMsg("WARN: $artist[0] $artist[1] artwork not found, using default");
					} elseif (!is_string($ret)) {
						//logMsg("INFO: $artist[0] $artist[1] artwork was fetched ");
					} elseif (is_string($ret)) {
						//logMsg("INFO: $artist[0] $artist[1] artwork was already there $ret ");
					}

					$stmtUpdateArtist->bindValue(':artist_uri', $artist[0]);
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
					if ($ret == false) {
						logMsg("WARN: $track[0] artwork not found, using default");
					} elseif (!is_string($ret)) {
						//logMsg("INFO: $track[0] artwork was fetched ");
					} elseif (is_string($ret)) {
						//logMsg("INFO: $artist[0] artwork was already there $ret ");
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
					if ($ret == false) {
						logMsg("WARN: $album[0] artwork not found, using default ");
					} elseif (!is_string($ret)) {
						//logMsg("INFO: $album[0] artwork was fetched ");
					} elseif (is_string($ret)) {
						//logMsg("INFO: $artist[0] artwork was already there $ret ");
					}

					$stmtUpdateAlbum->bindValue(':album_uri', $album[0]);
					$stmtUpdateAlbum->execute();

					$nb_artworks++;
					if ($nb_artworks % 5 === 0) {
						$w->write('Download Artworks▹' . $nb_artworks . '▹' . $nb_artworks_total . '▹' . $words[3], 'download_artworks_in_progress');
					}
				}
			}
		}
		catch (PDOException $e) {
			handleDbIssuePdoEcho($dbartworks, $w);
			$dbartworks = null;

			return false;
		}
	}
	deleteTheFile($w->data() . "/download_artworks_in_progress");
	logMsg("End of Download Artworks");
	if ($nb_artworks_total != 0) {
		$elapsed_time = time() - $words[3];
		displayNotificationWithArtwork($w,"All artworks have been downloaded (" . $nb_artworks_total . " artworks) - took " . beautifyTime($elapsed_time, true), './images/artworks.png', 'Artworks');
		if ($userid != 'vdesabou') {
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', $nb_artworks_total);
		}
	}

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
function getTrackOrAlbumArtwork($w, $spotifyURL, $fetchIfNotPresent, $fetchLater = false, $isLaterFetch = false) {
	$hrefs = explode(':', $spotifyURL);
	$isAlbum = false;
	if ($hrefs[1] == "album") {
		$isAlbum = true;
	}

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "/" . "$hrefs[2].png";
	$artwork        = "";

	//
	if ($fetchLater == true) {
		if (!is_file($currentArtwork)) {
			return array(
				false,
				$currentArtwork
			);
		} else {
			return array(
				true,
				$currentArtwork
			);
		}
		// always return currentArtwork
		return $currentArtwork;
	}

	if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $hrefs[2] == "fakeuri") {
		if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
			$artwork = getArtworkURL($w, $hrefs[1], $hrefs[2]);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $hrefs[2] . ".png") . "'");
				endif;
				$fp      = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_TIMEOUT => 5
				);

				$w->request("$artwork", $options);

				if ($isLaterFetch == true) {
					return true;
				} else {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', 1);
				}
			} else {
				if ($isLaterFetch == true) {
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
			if ($isLaterFetch == true) {
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
			if ($isLaterFetch == true) {
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
		if ($isLaterFetch == true) {
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
 * @param mixed $playlist_uri
 * @param mixed $fetchIfNotPresent
 * @param bool $forceFetch (default: false)
 * @return void
 */
function getPlaylistArtwork($w, $playlist_uri, $fetchIfNotPresent, $forceFetch = false) {
	$tmp                         = explode(':', $playlist_uri);
	$filename = "" . $tmp[2] . "_" . $tmp[4];
	$artwork = '';

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "/" . "$filename.png";

	if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
		if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
			$artwork = getPlaylistArtworkURL($w, $playlist_uri);
			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $filename . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "'");
				endif;
				$fp      = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_TIMEOUT => 5
				);

				$w->request("$artwork", $options);
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', 1);
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
 * getCategoryArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $categoryId
 * @param mixed $categoryURI
 * @param mixed $fetchIfNotPresent
 * @param bool $forceFetch (default: false)
 * @return void
 */
function getCategoryArtwork($w, $categoryId, $categoryURI, $fetchIfNotPresent, $forceFetch = false) {
	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $categoryId . ".jpg") . "/" . "$categoryId.jpg";

	if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
		if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0) || $forceFetch) {
			if (!file_exists($w->data() . "/artwork/" . hash('md5', $categoryId . ".jpg"))):
				exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $categoryId . ".jpg") . "'");
			endif;
			$fp      = fopen($currentArtwork, 'w+');
			$options = array(
				CURLOPT_FILE => $fp,
				CURLOPT_FOLLOWLOCATION => 1,
				CURLOPT_TIMEOUT => 5
			);
			$w->request("$categoryURI", $options);
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', 1);
		} else {
			return "./images/browse.png";
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			return "./images/browse.png";
		}
	}

	return $currentArtwork;
}



/**
 * getArtistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_uri
 * @param mixed $artist_name
 * @param bool $fetchIfNotPresent (default: false)
 * @param bool $fetchLater (default: false)
 * @param bool $isLaterFetch (default: false)
 * @return void
 */
function getArtistArtwork($w, $artist_uri, $artist_name, $fetchIfNotPresent = false, $fetchLater = false, $isLaterFetch = false) {
	$parsedArtist = urlencode(escapeQuery($artist_name));

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";

    if($artist_uri == '') {
        return "./images/artists.png";
    }

	$tmp  = explode(':', $artist_uri);
	if(isset($tmp[2])) {
		$artist_uri = $tmp[2];
	}
	$artwork        = "";
	//
	if ($fetchLater == true) {
		if (!is_file($currentArtwork)) {
			return array(
				false,
				$currentArtwork
			);
		} else {
			return array(
				true,
				$currentArtwork
			);
		}
		// always return currentArtwork
		return $currentArtwork;
	}

	if (!is_file($currentArtwork) || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
		if ($fetchIfNotPresent == true || (is_file($currentArtwork) && filesize($currentArtwork) == 0)) {
			$artwork = getArtistArtworkURL($w, $artist_uri);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if (!file_exists($w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png"))):
					exec("mkdir '" . $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "'");
				endif;
				$fp      = fopen($currentArtwork, 'w+');
				$options = array(
					CURLOPT_FILE => $fp,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_TIMEOUT => 5
				);
				$w->request("$artwork", $options);
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'artworks', 1);
				if ($isLaterFetch == true) {
					return true;
				}
			} else {
				if ($isLaterFetch == true) {
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
			if ($isLaterFetch == true) {
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
			if ($isLaterFetch == true) {
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
		if ($isLaterFetch == true) {
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
function getArtworkURL($w, $type, $id) {
	$url = "";

	if(startswith($id, 'fake')) {
		return $url;
	}
	if($type == 'track') {
		try {
			$api     = getSpotifyWebAPI($w);
			$track  = $api->getTrack($id);
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			echo "Error(getArtworkURL): (exception " . print_r($e) . ")";
			return $url;
		}
		if(isset($track->album) && isset($track->album->images)) {

			// 60 px
			if(isset($track->album->images[2]) && isset($track->album->images[2]->url)) {
				return $track->album->images[2]->url;
			}

			// 300 px
			if(isset($track->album->images[1]) && isset($track->album->images[1]->url)) {
				return $track->album->images[1]->url;
			}

			// 600 px
			if(isset($track->album->images[0]) && isset($track->album->images[0]->url)) {
				return $track->album->images[0]->url;
			}
		}
	} else {
		try {
			$api     = getSpotifyWebAPI($w);
			$album  = $api->getAlbum($id);
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			echo "Error(getArtworkURL): (exception " . print_r($e) . ")";
			return $url;
		}
		if(isset($album->images)) {

			// 60 px
			if(isset($album->images[2]) && isset($album->images[2]->url)) {
				return $album->images[2]->url;
			}

			// 300 px
			if(isset($album->images[1]) && isset($album->images[1]->url)) {
				return $album->images[1]->url;
			}

			// 600 px
			if(isset($album->images[0]) && isset($album->images[0]->url)) {
				return $album->images[0]->url;
			}
		}
	}

	return $url;
}


/**
 * getPlaylistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $playlist_uri
 * @return void
 */
function getPlaylistArtworkURL($w, $playlist_uri) {
	$url = "";
	$tmp = explode(':', $playlist_uri);
	try {
		$api     = getSpotifyWebAPI($w);
		$playlist = $api->getUserPlaylist(urlencode($tmp[2]), $tmp[4], array(
				'fields' => array(
					'images'
				)
			));
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		echo "Error(getPlaylistArtworkURL): (exception " . print_r($e) . ")";
		return $url;
	}
	if(isset($playlist->images)) {

		// 60 px
		if(isset($playlist->images[2]) && isset($playlist->images[2]->url)) {
			return $playlist->images[2]->url;
		}

		// 300 px
		if(isset($playlist->images[1]) && isset($playlist->images[1]->url)) {
			return $playlist->images[1]->url;
		}

		// 600 px
		if(isset($playlist->images[0]) && isset($playlist->images[0]->url)) {
			return $playlist->images[0]->url;
		}
	}
	return $url;
}


/**
 * getArtistArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $artist_id
 * @return void
 */
function getArtistArtworkURL($w, $artist_id) {
	$url = "";
	if(startswith($artist_id, 'fake')) {
		return $url;
	}
	try {
		$api     = getSpotifyWebAPI($w);
		$artist  = $api->getArtist($artist_id);
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		echo "Error(getArtistArtworkURL): (exception " . print_r($e) . ")";
		return $url;
	}

	if(isset($artist->images)) {

		// 60 px
		if(isset($artist->images[2]) && isset($artist->images[2]->url)) {
			return $artist->images[2]->url;
		}

		// 300 px
		if(isset($artist->images[1]) && isset($artist->images[1]->url)) {
			return $artist->images[1]->url;
		}

		// 600 px
		if(isset($artist->images[0]) && isset($artist->images[0]->url)) {
			return $artist->images[0]->url;
		}
	}
	return $url;
}


/**
 * updateLibrary function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function updateLibrary($w) {
	touch($w->data() . "/update_library_in_progress");
	$w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time() . '▹' . 'starting', 'update_library_in_progress');
	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from JSON
	//
	$settings                   = getSettings($w);
	$country_code               = $settings->country_code;
	$userid                     = $settings->userid;

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
	deleteTheFile($w->data() . '/library_new.db');
	$dbfile = $w->data() . '/library_new.db';
	touch($dbfile);

	try {
		$db = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$db->query("PRAGMA synchronous = OFF");
		$db->query("PRAGMA journal_mode = OFF");
		$db->query("PRAGMA temp_store = MEMORY");
		$db->query("PRAGMA count_changes = OFF");
		$db->query("PRAGMA PAGE_SIZE = 4096");
		$db->query("PRAGMA default_cache_size=700000");
		$db->query("PRAGMA cache_size=700000");
		$db->query("PRAGMA compile_options");
	}
	catch (PDOException $e) {
		echo "Error(updateLibrary): (exception " . print_r($e) . ")\n";
		handleDbIssuePdoEcho($db, $w);
		$db = null;

		return false;
	}

	// db for fetch artworks
	// kill previous process if running
	$pid = exec("ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'");
	if ($pid != "") {
		logMsg("KILL Download daemon <$pid>");
		$ret = exec("kill -9 \"$pid\"");
	}
	$dbfile                 = $w->data() . '/fetch_artworks.db';
	if (file_exists($dbfile)) {
		deleteTheFile($dbfile);
		touch($dbfile);
	}
	if (file_exists($w->data() . '/download_artworks_in_progress')) {
		deleteTheFile($w->data() . '/download_artworks_in_progress');
	}
	try {
		$dbartworks = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($dbartworks, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}

	// DB artowrks
	try {
		$dbartworks->exec("create table artists (artist_uri text PRIMARY KEY NOT NULL, artist_name text, already_fetched boolean)");
		$dbartworks->exec("create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
		$dbartworks->exec("create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($dbartworks, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}

	// get the total number of tracks
	$nb_tracktotal     = 0;
	$nb_skipped        = 0;
	$savedListPlaylist = array();
	try {
		$api                    = getSpotifyWebAPI($w);
	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		logMsg("Error(getUserPlaylists): (exception " . print_r($e) . ")");
		handleSpotifyWebAPIException($w, $e);

		return false;
	}

	$offsetGetUserPlaylists = 0;
	$limitGetUserPlaylists  = 50;
	do {
		$retry = true;
		$nb_retry = 0;
		while ($retry) {
			try {
				// refresh api
				$api           = getSpotifyWebAPI($w, $api);
				$userPlaylists = $api->getUserPlaylists(urlencode($userid), array(
						'limit' => $limitGetUserPlaylists,
						'offset' => $offsetGetUserPlaylists
					));
				$retry = false;
			}
			catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
				logMsg("Error(getUserPlaylists): retry " . $nb_retry . " (exception " . print_r($e) . ")");

				if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
					|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
					// retry
					if ($nb_retry > 20) {
						handleSpotifyWebAPIException($w, $e);
						$retry = false;
						return false;
					}
					$nb_retry++;
					sleep(15);
				} else {
					handleSpotifyWebAPIException($w, $e);
					$retry = false;
					return false;
				}
			}
		}

		foreach ($userPlaylists->items as $playlist) {
			$tracks = $playlist->tracks;
			$nb_tracktotal += $tracks->total;
			if ($playlist->name != "") {
				$savedListPlaylist[] = $playlist;
			}
		}
		$offsetGetUserPlaylists += $limitGetUserPlaylists;
	} while ($offsetGetUserPlaylists < $userPlaylists->total);

	$savedMySavedTracks = array();
	$offsetGetMySavedTracks = 0;
	$limitGetMySavedTracks  = 50;
	do {
		$retry = true;
		$nb_retry = 0;
		while ($retry) {
			try {
				// refresh api
				$api               = getSpotifyWebAPI($w, $api);
				$userMySavedTracks = $api->getMySavedTracks(array(
						'limit' => $limitGetMySavedTracks,
						'offset' => $offsetGetMySavedTracks,
						'market' => $country_code
					));
				$retry = false;
			}
			catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
				logMsg("Error(getMySavedTracks): retry " . $nb_retry . " (exception " . print_r($e) . ")");

				if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
					|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
					// retry
					if ($nb_retry > 20) {
						handleSpotifyWebAPIException($w, $e);
						$retry = false;
						return false;
					}
					$nb_retry++;
					sleep(15);
				} else {
					handleSpotifyWebAPIException($w, $e);
					$retry = false;
					return false;
				}
			}
		}

		foreach ($userMySavedTracks->items as $track) {
			$savedMySavedTracks[] = $track;
			$nb_tracktotal += 1;
		}

		$offsetGetMySavedTracks += $limitGetMySavedTracks;
	} while ($offsetGetMySavedTracks < $userMySavedTracks->total);

	// Handle playlists
	$w->write('Create Library▹0▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . 'starting', 'update_library_in_progress');

	$nb_track = 0;

	try {
		$db->exec("create table tracks (yourmusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration text, nb_times_played int, local_track boolean)");
		$db->exec("CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)");
		$db->exec("CREATE INDEX IndexArtistName ON tracks (artist_name)");
		$db->exec("CREATE INDEX IndexAlbumName ON tracks (album_name)");
		$db->exec("create table counters (all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int)");
		$db->exec("create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean, nb_playable_tracks int, duration_playlist text, nb_times_played int, collaborative boolean, public boolean)");

		$insertPlaylist = "insert into playlists values (:uri,:name,:nb_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser,:nb_playable_tracks,:duration_playlist,:nb_times_played,:collaborative,:public)";
		$stmtPlaylist   = $db->prepare($insertPlaylist);

		$insertTrack = "insert into tracks values (:yourmusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration,:nb_times_played,:local_track)";
		$stmtTrack   = $db->prepare($insertTrack);
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}

	try {
		// artworks
		$insertArtistArtwork = "insert or ignore into artists values (:artist_uri, :artist_name,:already_fetched)";
		$stmtArtistArtwork   = $dbartworks->prepare($insertArtistArtwork);

		$insertTrackArtwork = "insert or ignore into tracks values (:track_uri,:already_fetched)";
		$stmtTrackArtwork   = $dbartworks->prepare($insertTrackArtwork);

		$insertAlbumArtwork = "insert or ignore into albums values (:album_uri,:already_fetched)";
		$stmtAlbumArtwork   = $dbartworks->prepare($insertAlbumArtwork);
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($dbartworks, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}
	$artworksToDownload = false;

	foreach ($savedListPlaylist as $playlist) {
		$duration_playlist = 0;
		$nb_track_playlist = 0;
		$tracks            = $playlist->tracks;
		$owner             = $playlist->owner;

		$playlist_artwork_path = getPlaylistArtwork($w, $playlist->uri, true, true);

		if ("-" . $owner->id . "-" == "-" . $userid . "-") {
			$ownedbyuser = 1;
		} else {
			$ownedbyuser = 0;
		}

		try {
			$api                         = getSpotifyWebAPI($w);
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(getUserPlaylistTracks): playlist id " . $playlist->id . " (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);
			return false;
		}
		$offsetGetUserPlaylistTracks = 0;
		$limitGetUserPlaylistTracks  = 100;
		do {
			$retry = true;
			$nb_retry = 0;
			while ($retry) {
				try {
					// refresh api
					$api                = getSpotifyWebAPI($w, $api);
					$userPlaylistTracks = $api->getUserPlaylistTracks(urlencode($owner->id), $playlist->id, array(
							'fields' => array(
								'total',
								'items(added_at)',
								'items(is_local)',
								'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
								'items.track.album(album_type,images,uri,name)',
								'items.track.artists(name,uri)'
							),
							'limit' => $limitGetUserPlaylistTracks,
							'offset' => $offsetGetUserPlaylistTracks,
							'market' => $country_code
						));
					$retry = false;
				}
				catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
					logMsg("Error(getUserPlaylists): retry " . $nb_retry . " (exception " . print_r($e) . ")");

					if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
						|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
						// retry
						if ($nb_retry > 20) {
							handleSpotifyWebAPIException($w, $e);
							$retry = false;
							return false;
						}
						$nb_retry++;
						sleep(15);
					} else {
						handleSpotifyWebAPIException($w, $e);
						$retry = false;
						return false;
					}
				}
			}

			foreach ($userPlaylistTracks->items as $item) {
				$track   = $item->track;
				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;

				$playable = 0;
				$local_track = 0;
				if (isset($track->is_playable) && $track->is_playable) {
					$playable = 1;
					if(isset($track->linked_from) && isset($track->linked_from->uri)) {
						$track->uri = $track->linked_from->uri;
					}
				}
				if (isset($item->is_local) && $item->is_local) {
					$playable = 1;
					$local_track = 1;
				}
				try {
					//
					// Download artworks in Fetch later mode
					$thetrackuri = 'spotify:track:faketrackuri';
					if ($local_track == 0 && isset($track->uri)) {
						$thetrackuri = $track->uri;
					}
					list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
						$stmtTrackArtwork->bindValue(':already_fetched', 0);
						$stmtTrackArtwork->execute();
					}
					$theartistname = 'fakeartist';
					if (isset($artist->name)) {
						$theartistname = $artist->name;
					}
					$theartisturi = 'spotify:artist:fakeartisturi';
					if (isset($artist->uri)) {
						$theartisturi = $artist->uri;
					}
					list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi , $theartistname, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
						$stmtArtistArtwork->bindValue(':artist_name', $theartistname);
						$stmtArtistArtwork->bindValue(':already_fetched', 0);
						$stmtArtistArtwork->execute();
					}

					$thealbumuri = 'spotify:album:fakealbumuri';
					if (isset($album->uri)) {
						$thealbumuri = $album->uri;
					}
					list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
						$stmtAlbumArtwork->bindValue(':already_fetched', 0);
						$stmtAlbumArtwork->execute();
					}
				}
				catch (PDOException $e) {
					logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($dbartworks, $w);
					$dbartworks = null;
					$db         = null;

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
				}
				catch (PDOException $e) {
					logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($db, $w);
					$dbartworks = null;
					$db         = null;

					return false;
				}
				$nb_track++;
				$nb_track_playlist++;
				if ($nb_track % 10 === 0) {
					$w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . escapeQuery($playlist->name), 'update_library_in_progress');
				}
			}

			$offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
		} while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);

		try {
			$stmtPlaylist->bindValue(':uri', $playlist->uri);
			$stmtPlaylist->bindValue(':name', escapeQuery($playlist->name));
			$stmtPlaylist->bindValue(':nb_tracks', $playlist->tracks->total);
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
		catch (PDOException $e) {
			logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($db, $w);
			$dbartworks = null;
			$db         = null;
			return false;
		}
	}

	// Handle Your Music
	foreach ($savedMySavedTracks as $track) {
		$track = $track->track;
		$artists = $track->artists;
		$artist  = $artists[0];
		$album   = $track->album;

		$playable = 0;
		$local_track = 0;
		if (isset($track->is_playable) && $track->is_playable) {
			$playable = 1;
			if(isset($track->linked_from) && isset($track->linked_from->uri)) {
				$track->uri = $track->linked_from->uri;
			}
		}
		if (isset($item->is_local) && $item->is_local) {
			$playable = 1;
			$local_track = 1;
		}
		try {
			//
			// Download artworks in Fetch later mode
			$thetrackuri = 'spotify:track:faketrackuri';
			if ($local_track == 0 && isset($track->uri)) {
				$thetrackuri = $track->uri;
			}
			list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true);
			if ($already_present == false) {
				$artworksToDownload = true;
				$stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
				$stmtTrackArtwork->bindValue(':already_fetched', 0);
				$stmtTrackArtwork->execute();
			}
			$theartistname = 'fakeartist';
			if (isset($artist->name)) {
				$theartistname = $artist->name;
			}
			$theartisturi = 'spotify:artist:fakeartisturi';
			if (isset($artist->uri)) {
				$theartisturi = $artist->uri;
			}
			list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi , $theartistname, true, true);
			if ($already_present == false) {
				$artworksToDownload = true;
				$stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
				$stmtArtistArtwork->bindValue(':artist_name', $theartistname);
				$stmtArtistArtwork->bindValue(':already_fetched', 0);
				$stmtArtistArtwork->execute();
			}

			$thealbumuri = 'spotify:album:fakealbumuri';
			if (isset($album->uri)) {
				$thealbumuri = $album->uri;
			}
			list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true);
			if ($already_present == false) {
				$artworksToDownload = true;
				$stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
				$stmtAlbumArtwork->bindValue(':already_fetched', 0);
				$stmtAlbumArtwork->execute();
			}
		}
		catch (PDOException $e) {
			logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($dbartworks, $w);
			$dbartworks = null;
			$db         = null;

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
		}
		catch (PDOException $e) {
			logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($db, $w);
			$dbartworks = null;
			$db         = null;

			return false;
		}

		$nb_track++;
		if ($nb_track % 10 === 0) {
			$w->write('Create Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3] . '▹' . 'Your Music', 'update_library_in_progress');
		}
	}

	// update counters
	try {
		$getCount = 'select count(distinct uri) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_tracks = $stmt->fetch();

		$getCount = 'select count(distinct uri) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_tracks = $stmt->fetch();

		$getCount = 'select count(distinct artist_name) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_artists = $stmt->fetch();

		$getCount = 'select count(distinct artist_name) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_artists = $stmt->fetch();

		$getCount = 'select count(distinct album_name) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_albums = $stmt->fetch();

		$getCount = 'select count(distinct album_name) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_albums = $stmt->fetch();

		$getCount = 'select count(*) from playlists';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$playlists_count = $stmt->fetch();

		$insertCounter = "insert into counters values (:all_tracks,:yourmusic_tracks,:all_artists,:yourmusic_artists,:all_albums,:yourmusic_albums,:playlists)";
		$stmt          = $db->prepare($insertCounter);

		$stmt->bindValue(':all_tracks', $all_tracks[0]);
		$stmt->bindValue(':yourmusic_tracks', $yourmusic_tracks[0]);
		$stmt->bindValue(':all_artists', $all_artists[0]);
		$stmt->bindValue(':yourmusic_artists', $yourmusic_artists[0]);
		$stmt->bindValue(':all_albums', $all_albums[0]);
		$stmt->bindValue(':yourmusic_albums', $yourmusic_albums[0]);
		$stmt->bindValue(':playlists', $playlists_count[0]);
		$stmt->execute();
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}

	$elapsed_time = time() - $words[3];
	if ($nb_skipped == 0) {
		displayNotificationWithArtwork($w," " . $nb_track . " tracks - took " . beautifyTime($elapsed_time, true), './images/recreate.png', "Library (re-)created");
	} else {
		displayNotificationWithArtwork($w," " . $nb_track . " tracks / " . $nb_skipped . " skipped - took " . beautifyTime($elapsed_time, true), './images/recreate.png', "Library (re-)created");
	}

	if (file_exists($w->data() . '/library_old.db')) {
		deleteTheFile($w->data() . '/library_old.db');
	}
	rename($w->data() . '/library_new.db', $w->data() . '/library.db');

	// remove legacy spotify app if needed
	if (file_exists(exec('printf $HOME') . "/Spotify/spotify-app-miniplayer")) {
		exec("rm -rf " . exec('printf $HOME') . "/Spotify/spotify-app-miniplayer");
	}
	// remove legacy settings.db if needed
	if (file_exists($w->data() . '/settings.db')) {
		deleteTheFile($w->data() . '/settings.db');
	}

	// Download artworks in background
	if ($artworksToDownload == true) {
		exec("php -f ./src/action.php -- \"\" \"DOWNLOAD_ARTWORKS\" \"DOWNLOAD_ARTWORKS\" >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
	}
	deleteTheFile($w->data() . '/update_library_in_progress');
}


/**
 * refreshLibrary function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function refreshLibrary($w) {
	if (!file_exists($w->data() . '/library.db')) {
		displayNotificationWithArtwork($w,"Refresh library called while library does not exist", './images/warning.png');
		return;
	}

	touch($w->data() . "/update_library_in_progress");
	$w->write('InitRefreshLibrary▹' . 0 . '▹' . 0 . '▹' . time() . '▹' . 'starting', 'update_library_in_progress');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from JSON
	//

	$settings = getSettings($w);

	$country_code               = $settings->country_code;
	$userid                     = $settings->userid;

	$words = explode('▹', $in_progress_data);

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	$nb_playlist = 0;

	// db for fetch artworks
	$fetch_artworks_existed = true;
	$dbfile                 = $w->data() . '/fetch_artworks.db';
	if (!file_exists($dbfile)) {
		touch($dbfile);
		$fetch_artworks_existed = false;
	}
	// kill previous process if running
	$pid = exec("ps -efx | grep \"php\" | egrep \"DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}'");
	if ($pid != "") {
		logMsg("KILL Download daemon <$pid>");
		$ret = exec("kill -9 \"$pid\"");
	}
	if (file_exists($w->data() . '/download_artworks_in_progress')) {
		deleteTheFile($w->data() . '/download_artworks_in_progress');
	}

	try {
		$dbartworks = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$dbartworks->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e) {
		logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($dbartworks, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}

	// DB artowrks
	if ($fetch_artworks_existed == false) {
		try {
			$dbartworks->exec("create table artists (artist_uri text PRIMARY KEY NOT NULL, artist_name text, already_fetched boolean)");
			$dbartworks->exec("create table tracks (track_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
			$dbartworks->exec("create table albums (album_uri text PRIMARY KEY NOT NULL, already_fetched boolean)");
		}
		catch (PDOException $e) {
			logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($dbartworks, $w);
			$dbartworks = null;
			$db         = null;

			return false;
		}
	}

	try {
		// artworks
		$insertArtistArtwork = "insert or ignore into artists values (:artist_uri,:artist_name,:already_fetched)";
		$stmtArtistArtwork   = $dbartworks->prepare($insertArtistArtwork);

		$insertTrackArtwork = "insert or ignore into tracks values (:track_uri,:already_fetched)";
		$stmtTrackArtwork   = $dbartworks->prepare($insertTrackArtwork);

		$insertAlbumArtwork = "insert or ignore into albums values (:album_uri,:already_fetched)";
		$stmtAlbumArtwork   = $dbartworks->prepare($insertAlbumArtwork);
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($dbartworks, $w);
		$dbartworks = null;
		$db         = null;

		return false;
	}
	$artworksToDownload = false;
	rename($w->data() . '/library.db', $w->data() . '/library_old.db');
	copy($w->data() . '/library_old.db', $w->data() . '/library_new.db');
	$dbfile = $w->data() . '/library_new.db';

	$nb_added_playlists   = 0;
	$nb_removed_playlists = 0;
	$nb_updated_playlists = 0;

	try {
		$db = new PDO("sqlite:$dbfile", "", "", array(
				PDO::ATTR_PERSISTENT => true
			));
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$db->exec("drop table counters");
		$db->exec("create table counters (all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int)");

		$getPlaylists     = "select * from playlists where uri=:uri";
		$stmtGetPlaylists = $db->prepare($getPlaylists);

		$insertPlaylist = "insert into playlists values (:uri,:name,:nb_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser,:nb_playable_tracks,:duration_playlist,:nb_times_played,:collaborative,:public)";
		$stmtPlaylist   = $db->prepare($insertPlaylist);

		$insertTrack = "insert into tracks values (:yourmusic,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_type,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:added_at,:duration,:nb_times_played,:local_track)";
		$stmtTrack   = $db->prepare($insertTrack);

		$deleteFromTracks     = "delete from tracks where playlist_uri=:playlist_uri";
		$stmtDeleteFromTracks = $db->prepare($deleteFromTracks);

		$updatePlaylistsNbTracks     = "update playlists set nb_tracks=:nb_tracks,nb_playable_tracks=:nb_playable_tracks,duration_playlist=:duration_playlist,public=:public where uri=:uri";
		$stmtUpdatePlaylistsNbTracks = $db->prepare($updatePlaylistsNbTracks);

		$deleteFromTracksYourMusic     = "delete from tracks where yourmusic=:yourmusic";
		$stmtDeleteFromTracksYourMusic = $db->prepare($deleteFromTracksYourMusic);
	}
	catch (PDOException $e) {
		logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$dbartworks = null;
		$db         = null;
		return;
	}

	$savedListPlaylist = array();
	$offsetGetUserPlaylists = 0;
	$limitGetUserPlaylists  = 50;
	do {
		$retry = true;
		$nb_retry = 0;
		try {
    		$api           = getSpotifyWebAPI($w);
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
			handleSpotifyWebAPIException($w, $e);
			return false;
		}

		while ($retry) {
			try {
				// refresh api
				$api           = getSpotifyWebAPI($w, $api);
				$userPlaylists = $api->getUserPlaylists(urlencode($userid), array(
						'limit' => $limitGetUserPlaylists,
						'offset' => $offsetGetUserPlaylists
					));
				$retry = false;
			}
			catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
				logMsg("Error(getUserPlaylists): retry " . $nb_retry . " (exception " . print_r($e) . ")");

				if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
					|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
					// retry
					if ($nb_retry > 20) {
						handleSpotifyWebAPIException($w, $e);
						$retry = false;
						return false;
					}
					$nb_retry++;
					sleep(15);
				} else {
					handleSpotifyWebAPIException($w, $e);
					$retry = false;
					return false;
				}
			}
		}
		$nb_playlist_total = $userPlaylists->total;

		foreach ($userPlaylists->items as $playlist) {
			if ($playlist->name != "") {
				$savedListPlaylist[] = $playlist;
			}
		}
		$offsetGetUserPlaylists += $limitGetUserPlaylists;
	} while ($offsetGetUserPlaylists < $userPlaylists->total);

	// consider Your Music as a playlist for progress bar
	$nb_playlist_total++;

	foreach ($savedListPlaylist as $playlist) {
		$tracks = $playlist->tracks;
		$owner  = $playlist->owner;

		$nb_playlist++;
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
		catch (PDOException $e) {
			logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($db, $w);
			$dbartworks = null;
			$db         = null;

			return;
		}

		// Playlist does not exist, add it
		if ($noresult == true) {
			$nb_added_playlists++;
			$playlist_artwork_path = getPlaylistArtwork($w, $playlist->uri, true, true);

			if ("-" . $owner->id . "-" == "-" . $userid . "-") {
				$ownedbyuser = 1;
			} else {
				$ownedbyuser = 0;
			}

			$nb_track_playlist           = 0;
			$duration_playlist           = 0;
			$offsetGetUserPlaylistTracks = 0;
			$limitGetUserPlaylistTracks  = 100;
			do {
				$retry = true;
				$nb_retry = 0;
				while ($retry) {
					try {
						// refresh api
						$api                = getSpotifyWebAPI($w, $api);
						$userPlaylistTracks = $api->getUserPlaylistTracks(urlencode($owner->id), $playlist->id, array(
								'fields' => array(
									'total',
									'items(added_at)',
									'items(is_local)',
									'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
									'items.track.album(album_type,images,uri,name)',
									'items.track.artists(name,uri)'
								),
								'limit' => $limitGetUserPlaylistTracks,
								'offset' => $offsetGetUserPlaylistTracks,
								'market' => $country_code
							));
						$retry = false;
					}
					catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
						logMsg("Error(getUserPlaylistTracks): retry " . $nb_retry . " (exception " . print_r($e) . ")");

						if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
							|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
							// retry
							if ($nb_retry > 20) {
								handleSpotifyWebAPIException($w, $e);
								$retry = false;
								return false;
							}
							$nb_retry++;
							sleep(15);
						} else {
							handleSpotifyWebAPIException($w, $e);
							$retry = false;
							return false;
						}
					}
				}

				foreach ($userPlaylistTracks->items as $item) {
					$track   = $item->track;
					$artists = $track->artists;
					$artist  = $artists[0];
					$album   = $track->album;

					$playable = 0;
					$local_track = 0;
					if (isset($track->is_playable) && $track->is_playable) {
						$playable = 1;
						if(isset($track->linked_from) && isset($track->linked_from->uri)) {
							$track->uri = $track->linked_from->uri;
						}
					}
					if (isset($item->is_local) && $item->is_local) {
						$playable = 1;
						$local_track = 1;
					}
					try {
						//
						// Download artworks in Fetch later mode
						$thetrackuri = 'spotify:track:faketrackuri';
						if ($local_track == 0 && isset($track->uri)) {
							$thetrackuri = $track->uri;
						}
						list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true);
						if ($already_present == false) {
							$artworksToDownload = true;
							$stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
							$stmtTrackArtwork->bindValue(':already_fetched', 0);
							$stmtTrackArtwork->execute();
						}
						$theartistname = 'fakeartist';
						if (isset($artist->name)) {
							$theartistname = $artist->name;
						}
						$theartisturi = 'spotify:artist:fakeartisturi';
						if (isset($artist->uri)) {
							$theartisturi = $artist->uri;
						}
						list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi , $theartistname, true, true);
						if ($already_present == false) {
							$artworksToDownload = true;
							$stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
							$stmtArtistArtwork->bindValue(':artist_name', $theartistname);
							$stmtArtistArtwork->bindValue(':already_fetched', 0);
							$stmtArtistArtwork->execute();
						}

						$thealbumuri = 'spotify:album:fakealbumuri';
						if (isset($album->uri)) {
							$thealbumuri = $album->uri;
						}
						list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true);
						if ($already_present == false) {
							$artworksToDownload = true;
							$stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
							$stmtAlbumArtwork->bindValue(':already_fetched', 0);
							$stmtAlbumArtwork->execute();
						}
					}
					catch (PDOException $e) {
						logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
						handleDbIssuePdoEcho($dbartworks, $w);
						$dbartworks = null;
						$db         = null;

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
					}
					catch (PDOException $e) {
						logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
						handleDbIssuePdoEcho($db, $w);
						$dbartworks = null;
						$db         = null;

						return;
					}
					$nb_track_playlist++;
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
			}
			catch (PDOException $e) {
				logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
				handleDbIssuePdoEcho($db, $w);
				$dbartworks = null;
				$db         = null;
				return;
			}

			displayNotificationWithArtwork($w,'Added playlist ' . escapeQuery($playlist->name), $playlist_artwork_path, 'Refresh Library');
		} else {
			// number of tracks has changed or playlist name has changed or the privacy has changed
			// update the playlist
			if ($playlists[2] != $tracks->total || $playlists[1] != escapeQuery($playlist->name) ||
				(($playlists[11] == '' && $playlist->public == true) || ($playlists[11] == true && $playlist->public == ''))) {
				$nb_updated_playlists++;

				// force refresh of playlist artwork
				getPlaylistArtwork($w, $playlist->uri, true, true);

				try {
					if ($playlists[1] != escapeQuery($playlist->name)) {
						$updatePlaylistsName     = "update playlists set name=:name where uri=:uri";
						$stmtUpdatePlaylistsName = $db->prepare($updatePlaylistsName);

						$stmtUpdatePlaylistsName->bindValue(':name', escapeQuery($playlist->name));
						$stmtUpdatePlaylistsName->bindValue(':uri', $playlist->uri);
						$stmtUpdatePlaylistsName->execute();
					}

					$stmtDeleteFromTracks->bindValue(':playlist_uri', $playlist->uri);
					$stmtDeleteFromTracks->execute();
				}
				catch (PDOException $e) {
					logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($db, $w);
					$dbartworks = null;
					$db         = null;
					return;
				}

				$duration_playlist           = 0;
				$nb_track_playlist           = 0;
				$offsetGetUserPlaylistTracks = 0;
				$limitGetUserPlaylistTracks  = 100;
				$owner  					 = $playlist->owner;
				do {
					$retry = true;
					$nb_retry = 0;
					while ($retry) {
						try {
							// refresh api
							$api                = getSpotifyWebAPI($w, $api);
							$userPlaylistTracks = $api->getUserPlaylistTracks(urlencode($owner->id), $playlist->id, array(
									'fields' => array(
										'total',
										'items(added_at)',
										'items(is_local)',
										'items.track(is_playable,duration_ms,uri,popularity,name,linked_from)',
										'items.track.album(album_type,images,uri,name)',
										'items.track.artists(name,uri)'
									),
									'limit' => $limitGetUserPlaylistTracks,
									'offset' => $offsetGetUserPlaylistTracks,
									'market' => $country_code
								));
							$retry = false;
						}
						catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
							logMsg("Error(getUserPlaylistTracks): retry " . $nb_retry . " (exception " . print_r($e) . ")");

							if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
								|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
								// retry
								if ($nb_retry > 20) {
									handleSpotifyWebAPIException($w, $e);
									$retry = false;
									return false;
								}
								$nb_retry++;
								sleep(15);
							} else {
								handleSpotifyWebAPIException($w, $e);
								$retry = false;
								return false;
							}
						}
					}

					foreach ($userPlaylistTracks->items as $item) {
						$track   = $item->track;
						$artists = $track->artists;
						$artist  = $artists[0];
						$album   = $track->album;

						$playable = 0;
						$local_track = 0;
						if (isset($track->is_playable) && $track->is_playable) {
							$playable = 1;
							if(isset($track->linked_from) && isset($track->linked_from->uri)) {
								$track->uri = $track->linked_from->uri;
							}
						}
						if (isset($item->is_local) && $item->is_local) {
							$playable = 1;
							$local_track = 1;
						}

						try {
							//
							// Download artworks in Fetch later mode
							$thetrackuri = 'spotify:track:faketrackuri';
							if ($local_track == 0 && isset($track->uri)) {
								$thetrackuri = $track->uri;
							}
							list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true);
							if ($already_present == false) {
								$artworksToDownload = true;
								$stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
								$stmtTrackArtwork->bindValue(':already_fetched', 0);
								$stmtTrackArtwork->execute();
							}
							$theartistname = 'fakeartist';
							if (isset($artist->name)) {
								$theartistname = $artist->name;
							}
							$theartisturi = 'spotify:artist:fakeartisturi';
							if (isset($artist->uri)) {
								$theartisturi = $artist->uri;
							}
							list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi , $theartistname, true, true);
							if ($already_present == false) {
								$artworksToDownload = true;
								$stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
								$stmtArtistArtwork->bindValue(':artist_name', $theartistname);
								$stmtArtistArtwork->bindValue(':already_fetched', 0);
								$stmtArtistArtwork->execute();
							}

							$thealbumuri = 'spotify:album:fakealbumuri';
							if (isset($album->uri)) {
								$thealbumuri = $album->uri;
							}
							list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true);
							if ($already_present == false) {
								$artworksToDownload = true;
								$stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
								$stmtAlbumArtwork->bindValue(':already_fetched', 0);
								$stmtAlbumArtwork->execute();
							}
						}
						catch (PDOException $e) {
							logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
							handleDbIssuePdoEcho($dbartworks, $w);
							$dbartworks = null;
							$db         = null;
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
						}
						catch (PDOException $e) {
							logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
							handleDbIssuePdoEcho($db, $w);
							$dbartworks = null;
							$db         = null;
							return;
						}
						$nb_track_playlist++;
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
				}
				catch (PDOException $e) {
					logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($db, $w);
					$dbartworks = null;
					$db         = null;
					return;
				}
				displayNotificationWithArtwork($w,'Updated playlist ' . escapeQuery($playlist->name), getPlaylistArtwork($w, $playlist->uri, true), 'Refresh Library');
			} else {
				continue;
			}
		}
	}

	try {
		// check for deleted playlists
		$getPlaylists = "select * from playlists";
		$stmt         = $db->prepare($getPlaylists);
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
				$stmtDelete         = $db->prepare($deleteFromPlaylist);
				$stmtDelete->bindValue(':uri', $playlist_in_db[0]);
				$stmtDelete->execute();

				$deleteFromTracks = "delete from tracks where playlist_uri=:uri";
				$stmtDelete       = $db->prepare($deleteFromTracks);
				$stmtDelete->bindValue(':uri', $playlist_in_db[0]);
				$stmtDelete->execute();
				displayNotificationWithArtwork($w,'Removed playlist ' . $playlist_in_db[1], getPlaylistArtwork($w, $playlist_in_db[0], false), 'Refresh Library');
			}
		}
	}
	catch (PDOException $e) {
		logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$dbartworks = null;
		$db         = null;

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
					'offset' => 0
				));
			$retry = false;
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			logMsg("Error(getMySavedTracks): retry " . $nb_retry . " (exception " . print_r($e) . ")");

			if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
				|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
				// retry
				if ($nb_retry > 20) {
					handleSpotifyWebAPIException($w, $e);
					$retry = false;
					return false;
				}
				$nb_retry++;
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
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_tracks = $stmt->fetch();
	}
	catch (PDOException $e) {
		logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$db = null;

		return;
	}

	$your_music_updated = false;
	if ($yourmusic_tracks[0] != $userMySavedTracks->total) {
		$your_music_updated = true;
		// Your Music has changed, update it
		$nb_playlist++;
		$w->write('Refresh Library▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3] . '▹' . 'Your Music', 'update_library_in_progress');

		// delete tracks
		try {
			$stmtDeleteFromTracksYourMusic->bindValue(':yourmusic', 1);
			$stmtDeleteFromTracksYourMusic->execute();
		}
		catch (PDOException $e) {
			logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
			handleDbIssuePdoEcho($db, $w);
			$db = null;

			return;
		}

		$offsetGetMySavedTracks = 0;
		$limitGetMySavedTracks  = 50;
		do {
			$retry = true;
			$nb_retry = 0;
			while ($retry) {
				try {
					// refresh api
					$api               = getSpotifyWebAPI($w, $api);
					$userMySavedTracks = $api->getMySavedTracks(array(
							'limit' => $limitGetMySavedTracks,
							'offset' => $offsetGetMySavedTracks,
							'market' => $country_code
						));
					$retry = false;
				}
				catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
					logMsg("Error(getMySavedTracks): retry " . $nb_retry . " (exception " . print_r($e) . ")");

					if ($e->getCode() == 429 || $e->getCode() == 404 || $e->getCode() == 500
						|| $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 0) {
						// retry
						if ($nb_retry > 20) {
							handleSpotifyWebAPIException($w, $e);
							$retry = false;
							return false;
						}
						$nb_retry++;
						sleep(15);
					} else {
						handleSpotifyWebAPIException($w, $e);
						$retry = false;
						return false;
					}
				}
			}

			foreach ($userMySavedTracks->items as $item) {
				$track   = $item->track;
				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;

				$playable = 0;
				$local_track = 0;
				if (isset($track->is_playable) && $track->is_playable) {
					$playable = 1;
					if(isset($track->linked_from) && isset($track->linked_from->uri)) {
						$track->uri = $track->linked_from->uri;
					}
				}
				if (isset($item->is_local) && $item->is_local) {
					$playable = 1;
					$local_track = 1;
				}

				try {
					//
					// Download artworks in Fetch later mode
					$thetrackuri = 'spotify:track:faketrackuri';
					if ($local_track == 0 && isset($track->uri)) {
						$thetrackuri = $track->uri;
					}
					list($already_present, $track_artwork_path) = getTrackOrAlbumArtwork($w, $thetrackuri, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtTrackArtwork->bindValue(':track_uri', $thetrackuri);
						$stmtTrackArtwork->bindValue(':already_fetched', 0);
						$stmtTrackArtwork->execute();
					}
					$theartistname = 'fakeartist';
					if (isset($artist->name)) {
						$theartistname = $artist->name;
					}
					$theartisturi = 'spotify:artist:fakeartisturi';
					if (isset($artist->uri)) {
						$theartisturi = $artist->uri;
					}
					list($already_present, $artist_artwork_path) = getArtistArtwork($w, $theartisturi , $theartistname, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtArtistArtwork->bindValue(':artist_uri', $artist->uri);
						$stmtArtistArtwork->bindValue(':artist_name', $theartistname);
						$stmtArtistArtwork->bindValue(':already_fetched', 0);
						$stmtArtistArtwork->execute();
					}

					$thealbumuri = 'spotify:album:fakealbumuri';
					if (isset($album->uri)) {
						$thealbumuri = $album->uri;
					}
					list($already_present, $album_artwork_path) = getTrackOrAlbumArtwork($w, $thealbumuri, true, true);
					if ($already_present == false) {
						$artworksToDownload = true;
						$stmtAlbumArtwork->bindValue(':album_uri', $thealbumuri);
						$stmtAlbumArtwork->bindValue(':already_fetched', 0);
						$stmtAlbumArtwork->execute();
					}
				}
				catch (PDOException $e) {
					logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($dbartworks, $w);
					$dbartworks = null;
					$db         = null;

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
				}
				catch (PDOException $e) {
					logMsg("Error(refreshLibrary): (exception " . print_r($e) . ")");
					handleDbIssuePdoEcho($db, $w);
					$db = null;

					return;
				}
			}

			$offsetGetMySavedTracks += $limitGetMySavedTracks;
		} while ($offsetGetMySavedTracks < $userMySavedTracks->total);
	}

	// update counters
	try {
		$getCount = 'select count(distinct uri) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_tracks = $stmt->fetch();

		$getCount = 'select count(distinct uri) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_tracks = $stmt->fetch();

		$getCount = 'select count(distinct artist_name) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_artists = $stmt->fetch();

		$getCount = 'select count(distinct artist_name) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_artists = $stmt->fetch();

		$getCount = 'select count(distinct album_name) from tracks';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$all_albums = $stmt->fetch();

		$getCount = 'select count(distinct album_name) from tracks where yourmusic=1';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$yourmusic_albums = $stmt->fetch();

		$getCount = 'select count(*) from playlists';
		$stmt     = $db->prepare($getCount);
		$stmt->execute();
		$playlists_count = $stmt->fetch();

		$insertCounter = "insert into counters values (:all_tracks,:yourmusic_tracks,:all_artists,:yourmusic_artists,:all_albums,:yourmusic_albums,:playlists)";
		$stmt          = $db->prepare($insertCounter);

		$stmt->bindValue(':all_tracks', $all_tracks[0]);
		$stmt->bindValue(':yourmusic_tracks', $yourmusic_tracks[0]);
		$stmt->bindValue(':all_artists', $all_artists[0]);
		$stmt->bindValue(':yourmusic_artists', $yourmusic_artists[0]);
		$stmt->bindValue(':all_albums', $all_albums[0]);
		$stmt->bindValue(':yourmusic_albums', $yourmusic_albums[0]);
		$stmt->bindValue(':playlists', $playlists_count[0]);
		$stmt->execute();
	}
	catch (PDOException $e) {
		logMsg("Error(updateLibrary): (exception " . print_r($e) . ")");
		handleDbIssuePdoEcho($db, $w);
		$dbartworks = null;
		$db         = null;
		return false;
	}

	$elapsed_time     = time() - $words[3];
	$changedPlaylists = false;
	$changedYourMusic = false;
	$addedMsg = '';
	$removedMsg = '';
	$updatedMsg = '';
	$yourMusicMsg = '';
	if ($nb_added_playlists > 0) {
		$addedMsg         = $nb_added_playlists . ' added';
		$changedPlaylists = true;
	}

	if ($nb_removed_playlists > 0) {
		$removedMsg       = $nb_removed_playlists . ' removed';
		$changedPlaylists = true;
	}

	if ($nb_updated_playlists > 0) {
		$updatedMsg       = $nb_updated_playlists . ' updated';
		$changedPlaylists = true;
	}

	if ($your_music_updated) {
		$yourMusicMsg     = ' - Your Music: updated';
		$changedYourMusic = true;
	}

	if ($changedPlaylists && $changedYourMusic) {
		$message = 'Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg . ' ' . $yourMusicMsg;
	} elseif ($changedPlaylists) {
		$message = 'Playlists: ' . $addedMsg . ' ' . $removedMsg . ' ' . $updatedMsg;
	} elseif ($changedYourMusic) {
		$message = $yourMusicMsg;
	} else {
		$message = 'No change';
	}

	displayNotificationWithArtwork($w,$message . " - took " . beautifyTime($elapsed_time, true), './images/update.png', 'Library refreshed');

	if (file_exists($w->data() . '/library_old.db')) {
		deleteTheFile($w->data() . '/library_old.db');
	}
	rename($w->data() . '/library_new.db', $w->data() . '/library.db');

	// Download artworks in background
	logMsg("========DOWNLOAD_ARTWORKS DURING REFRESH LIBRARY ========");
	exec("php -f ./src/action.php -- \"\" \"DOWNLOAD_ARTWORKS\" \"DOWNLOAD_ARTWORKS\" >> \"" . $w->cache() . "/action.log\" 2>&1 & ");

	deleteTheFile($w->data() . '/update_library_in_progress');
}


/**
 * handleDbIssuePdoXml function.
 *
 * @access public
 * @param mixed $dbhandle
 * @return void
 */
function handleDbIssuePdoXml($dbhandle) {
	$errorInfo = $dbhandle->errorInfo();
	$w         = new Workflows('com.vdesabou.spotify.mini.player');
	$w->result(uniqid(), '', 'Database Error: ' . $errorInfo[0] . ' ' . $errorInfo[1] . ' ' . $errorInfo[2], '', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), '', 'There is a problem with the library, try to re-create it.', 'Select Re-Create Library library below', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), serialize(array(
				'' /*track_uri*/ ,
				'' /* album_uri */ ,
				'' /* artist_uri */ ,
				'' /* playlist_uri */ ,
				'' /* spotify_command */ ,
				'' /* query */ ,
				'' /* other_settings*/ ,
				'update_library' /* other_action */ ,
				'' /* alfred_playlist_uri */ ,
				'' /* artist_name */ ,
				'' /* track_name */ ,
				'' /* album_name */ ,
				'' /* track_artwork_path */ ,
				'' /* artist_artwork_path */ ,
				'' /* album_artwork_path */ ,
				'' /* playlist_name */ ,
				'' /* playlist_artwork_path */ ,
				''
				/* $alfred_playlist_name */
			)), "Re-Create Library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
	echo $w->toxml();
}


/**
 * handleDbIssuePdoEcho function.
 *
 * @access public
 * @param mixed $dbhandle
 * @param mixed $w
 * @return void
 */
function handleDbIssuePdoEcho($dbhandle, $w) {
	$errorInfo = $dbhandle->errorInfo();
	logMsg('DB Exception: ' . $errorInfo[0] . ' ' . $errorInfo[1] . ' ' . $errorInfo[2]);

	if (file_exists($w->data() . '/update_library_in_progress')) {
		deleteTheFile($w->data() . '/update_library_in_progress');
	}

	// set back old library
	if (file_exists($w->data() . "/library_new.db")) {
		rename($w->data() . '/library_new.db', $w->data() . '/library.db');
	}

	if (file_exists($w->data() . '/library_old.db')) {
		deleteTheFile($w->data() . '/library_old.db');
	}

	displayNotificationWithArtwork($w,"DB Exception: " . $errorInfo[2], './images/warning.png');

	exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug DB Exception: " . escapeQuery($errorInfo[2]) . "\"'");

	exit;
}


/**
 * handleSpotifyWebAPIException function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $e
 * @return void
 */
function handleSpotifyWebAPIException($w, $e) {
	if (file_exists($w->data() . '/update_library_in_progress')) {
		deleteTheFile($w->data() . '/update_library_in_progress');
	}

	// remove the new library (it failed)
	if (file_exists($w->data() . "/library_new.db")) {
		deleteTheFile($w->data() . '/library_new.db');
	}

	// set back old library
	if (file_exists($w->data() . '/library_old.db')) {
		rename($w->data() . '/library_old.db', $w->data() . '/library.db');
	}

	displayNotificationWithArtwork($w,'Web API Exception: ' . $e->getCode() . ' - ' . $e->getMessage() . ' use spot_mini_debug command', './images/warning.png', 'Error!');

	exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini_debug Web API Exception: " . escapeQuery($e->getMessage()) . "\"'");

	exit;
}


/**
 * floatToSquares function.
 *
 * @access public
 * @param mixed $decimal
 * @return void
 */
function floatToSquares($decimal) {
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
function floatToStars($decimal) {
	if ($decimal == 0) {
		return '';
	}
	$squares = ($decimal < 1) ? floor($decimal * 5) : 5;

	return str_repeat("★", $squares) . str_repeat("☆", 5 - $squares);
}


/**
 * Mulit-byte Unserialize
 *
 * UTF-8 will screw up a serialized string
 * Thanks to http://stackoverflow.com/questions/2853454/php-unserialize-fails-with-non-encoded-characters
 *
 * @access private
 * @param string
 * @return string
 */

function mb_unserialize($string) {
	$string2 = preg_replace_callback(
		'!s:(\d+):"(.*?)";!s',
		function($m) {
			$len = strlen($m[2]);
			$result = "s:$len:\"{$m[2]}\";";
			return $result;

		},
		$string);
	return unserialize($string2);
}



/**
 * cleanupTrackName function.
 *
 * @access public
 * @param mixed $track_name
 * @return void
 */
function cleanupTrackName($track_name) {
	return str_ireplace(array(
			'acoustic version',
			'new album version',
			'original album version',
			'album version',
			'bonus track',
			'clean version',
			'club mix',
			'demo version',
			'extended mix',
			'extended outro',
			'extended version',
			'extended',
			'explicit version',
			'explicit',
			'(live)',
			'- live',
			'live version',
			'lp mix',
			'(original)',
			'original edit',
			'original mix edit',
			'original version',
			'(radio)',
			'radio edit',
			'remix edit',
			'radio mix',
			'remastered version',
			're-mastered version',
			'remastered digital version',
			're-mastered digital version',
			'remastered',
			'remaster',
			'remixed version',
			'remix',
			'single version',
			'studio version',
			'version acustica',
			'versión acústica',
			'vocal edit'
		), '', $track_name);
}


/**
 * cleanupArtistName function.
 *
 * @access public
 * @param mixed $artist_name
 * @return void
 */
function cleanupArtistName($artist_name) {
	$query_artist = $artist_name;
	if (stristr($query_artist, 'feat.')) {
		$query_artist = stristr($query_artist, 'feat.', true);
	} elseif (stristr($query_artist, 'featuring')) {
		$query_artist = stristr($query_artist, 'featuring', true);
	} elseif (stristr($query_artist, ' & ')) {
		$query_artist = stristr($query_artist, ' & ', true);
	}

	$query_artist = str_replace('&', 'and', $query_artist);
	$query_artist = str_replace('$', 's', $query_artist);
	$query_artist = strip_string(trim($query_artist));
	$query_artist = str_replace(' - ', '-', $query_artist);
	$query_artist = str_replace(' ', '-', $query_artist);

	return $query_artist;
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
function getLyrics($w, $artist, $title) {
	$query_artist = $artist;
	$query_title  = $title;

	$query_artist = cleanupArtistName($query_artist);
	$query_title  = cleanupTrackName($query_title);

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

	$uri = strtolower('https://www.musixmatch.com/lyrics/' . $query_artist . '/' . $query_title);
	$error    = false;
	$no_match = false;

	$options = array(
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_TIMEOUT => 10,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
	);
	$file = $w->request($uri, $options);

	preg_match('/<script>.*var __mxmState = (.*?);<\/script>/s', $file, $lyrics);
	$lyrics = (empty($lyrics[1])) ? '' : $lyrics[1];
	if (empty($file)) {
		return array(
			false,
			''
		);
	} elseif ($lyrics == '') {
		$no_match = true;
		return array(
			false,
			''
		);
	} else {
		$json = json_decode($lyrics);
		switch (json_last_error()) {
		case JSON_ERROR_DEPTH:
			return array(
				false,
				''
			);
		case JSON_ERROR_CTRL_CHAR:
			return array(
				false,
				''
			);
		case JSON_ERROR_SYNTAX:
			return array(
				false,
				''
			);
		case JSON_ERROR_NONE:

			if (isset($json->page) &&
				isset($json->page->lyrics) &&
				isset($json->page->lyrics->lyrics)) {
				if ($json->page->lyrics->lyrics->body == '') {
					return array(
						false,
						''
					);
				} else {
					return array(
						$uri,
						$json->page->lyrics->lyrics->body
					);
				}
			} else {
				return array(
					false,
					''
				);
			}
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
function strip_string($string) {
	return preg_replace('/[^a-zA-Z0-9-\s]/', '', $string);
}

/**
 * checkForUpdate function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $last_check_update_time
 * @param bool $download (default: true)
 * @return void
 */
function checkForUpdate($w, $last_check_update_time, $download = false) {
	if (time() - $last_check_update_time > 86400 || $download == true) {
		// update last_check_update_time
		$ret = updateSetting($w, 'last_check_update_time', time());
		if ($ret == false) {
			return "Error while updating settings";
		}

		if (!$w->internet()) {
			return "No internet connection !";
		}

		// get local information
		if (!file_exists('./packal/package.xml')) {
			return "This release has not been downloaded from Packal";
		}
		$xml           = $w->read('./packal/package.xml');
		$workflow      = new SimpleXMLElement($xml);
		$local_version = $workflow->version;
		$remote_json   = "https://raw.githubusercontent.com/vdesabou/alfred-spotify-mini-player/master/remote.json";

		// get remote information
		$jsonDataRemote = $w->request($remote_json);

		if (empty($jsonDataRemote)) {
			return "The export.json " . $remote_json . " file cannot be found";
		}

		$json = json_decode($jsonDataRemote, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			$download_url   = $json['download_url'];
			$remote_version = $json['version'];
			$description    = $json['description'];

			if ($local_version < $remote_version) {
				if($download == true) {
					$workflow_file_name = exec('printf $HOME') . '/Downloads/spotify-mini-player-' . $remote_version . '.alfredworkflow';
					$fp                 = fopen($workflow_file_name, 'w+');
					$options            = array(
						CURLOPT_FILE => $fp
					);
					$w->request("$download_url", $options);

					return array(
						$remote_version,
						$workflow_file_name,
						$description
					);
				} else {
					$w->result(null, serialize(array(
								'' /*track_uri*/ ,
								'' /* album_uri */ ,
								'' /* artist_uri */ ,
								'' /* playlist_uri */ ,
								'' /* spotify_command */ ,
								'' /* query */ ,
								'' /* other_settings*/ ,
								'download_update' /* other_action */ ,
								'' /* artist_name */ ,
								'' /* track_name */ ,
								'' /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								'' /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), 'An update is available, version ' . $remote_version . '. Click to download', '' . $description, './images/check_update.png', 'yes', '');
				}
			}
		} else {
			return "Cannot read remote.json";
		}
	}
}


/**
 * doJsonRequest function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $url
 * @param bool $actionMode (default: true)
 * @return void
 */
function doJsonRequest($w, $url, $actionMode = true) {
	if (!$w->internet()) {
		if ($actionMode == true) {
			displayNotificationWithArtwork($w,"No internet connection", './images/warning.png');

			exit;
		} else {
			$w->result(null, '', "Error: No internet connection", $url, './images/warning.png', 'no', null, '');
			echo $w->toxml();
			exit;
		}
	}

	$json = $w->request($url);
	if (empty($json)) {
		if ($actionMode == true) {
			displayNotificationWithArtwork($w,"Error: JSON request returned empty result", './images/warning.png');

			exit;
		} else {
			$w->result(null, '', "Error: JSON request returned empty result", $url, './images/warning.png', 'no', null, '');
			echo $w->toxml();
			exit;
		}
	}

	$json = json_decode($json);
	switch (json_last_error()) {
	case JSON_ERROR_NONE:
		return $json;
	default:
		if ($actionMode == true) {
			displayNotificationWithArtwork($w,"Error: JSON request returned error " . json_last_error() . ' (' . json_last_error_msg() . ')', './images/warning.png');

			exit;
		} else {
			$w->result(null, '', "Error: JSON request returned error " . json_last_error() . ' (' . json_last_error_msg() . ')', "Try again or report to author", './images/warning.png', 'no', null, '');
			echo $w->toxml();
			exit;
		}
	}
}


/**
 * killUpdate function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function killUpdate($w) {
	deleteTheFile($w->data() . '/update_library_in_progress');
	deleteTheFile($w->data() . '/download_artworks_in_progress');


	if (file_exists($w->data() . '/library_old.db')) {
	    rename($w->data() . '/library_old.db', $w->data() . '/library.db');
    }

	if (file_exists($w->data() . '/library_new.db')) {
		deleteTheFile($w->data() . '/library_new.db');
	}

	exec("kill -9 $(ps -efx | grep \"php\" | egrep \"update_|php -S localhost:15298|ADDTOPLAYLIST|UPDATE_|DOWNLOAD_ARTWORKS\" | grep -v grep | awk '{print $2}')");

	displayNotificationWithArtwork($w,"Update library was killed", './images/kill.png', 'Kill Update Library ');
}


/**
 * deleteTheFile function.
 *
 * @access public
 * @param mixed $filename
 * @return void
 */
function deleteTheFile($filename) {
	@chmod($filename, 0777);
	@unlink(realpath($filename));

	if (is_file($filename)) {
		logMsg("Error(deleteTheFile): file was locked (or permissions error) " . realpath($filename) . " permissions: " . decoct(fileperms(realpath($filename)) & 0777));
		displayNotificationWithArtwork($w,"Problem deleting " . $filename, './images/warning.png', 'Delete File');
	}
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
	$country_names = json_decode(file_get_contents("./src/country_names.json"), true);
	return $country_names[$cc];
}


/**
 * beautifyTime function.
 *
 * @access public
 * @param mixed $seconds
 * @param bool $withText (default: false)
 * @return void
 */
function beautifyTime($seconds, $withText = false) {
	$ret = gmdate("H●i●s", $seconds);
	$tmp = explode('●', $ret);
	if ($tmp[0] == '00' && $tmp[1] != '00') {
		$min = ltrim($tmp[1], 0);

		if ($withText == true) {
			return "$min min $tmp[2] sec";
		} else {
			return "$min:$tmp[2]";
		}
	} elseif ($tmp[1] == '00') {
		$sec = ltrim($tmp[2], 0);
		if ($sec == '') {
			$sec = 0;
		}

		if ($withText == true) {
			return "$sec sec";
		} else {
			return "0:$tmp[2]";
		}
	} else {
		$hr  = ltrim($tmp[0], 0);
		$min = ltrim($tmp[1], 0);

		return "$hr hr $min min";
	}
}


/**
 * startswith function.
 *
 * @access public
 * @param mixed $haystack
 * @param mixed $needle
 * @return void
 */
function startswith($haystack, $needle) {
	return substr($haystack, 0, strlen($needle)) === $needle;
}


/**
 * getSettings function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function getSettings($w) {
	if (file_exists($w->data() . '/settings.db') && !file_exists($w->data() . '/settings.json')) {
		// migrate settings.db to settings.json
		//
		// Read settings from DB
		//
		$getSettings    = 'select all_playlists,is_spotifious_active,is_alfred_playlist_active,radio_number_tracks,is_lyrics_active,max_results, alfred_playlist_uri,alfred_playlist_name,country_code,theme,last_check_update_time,oauth_client_id,oauth_client_secret,oauth_redirect_uri,oauth_access_token,oauth_expires,oauth_refresh_token,display_name,userid,echonest_api_key from settings';
		$dbsettingsfile = $w->data() . '/settings.db';

		try {
			$dbsettings = new PDO("sqlite:$dbsettingsfile", "", "", array(
					PDO::ATTR_PERSISTENT => true
				));
			$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbsettings->query("PRAGMA synchronous = OFF");
			$dbsettings->query("PRAGMA journal_mode = OFF");
			$dbsettings->query("PRAGMA temp_store = MEMORY");
			$dbsettings->query("PRAGMA count_changes = OFF");
			$dbsettings->query("PRAGMA PAGE_SIZE = 4096");
			$dbsettings->query("PRAGMA default_cache_size=700000");
			$dbsettings->query("PRAGMA cache_size=700000");
			$dbsettings->query("PRAGMA compile_options");

			$stmt     = $dbsettings->prepare($getSettings);
			$settings = $stmt->execute();
			$setting  = $stmt->fetch();

			$migrated = array(
				'all_playlists' => $setting[0],
				'is_alfred_playlist_active' => $setting[2],
				'radio_number_tracks' => $setting[3],
				'now_playing_notifications' => 1,
				'max_results' => $setting[5],
				'alfred_playlist_uri' => $setting[6],
				'alfred_playlist_name' => $setting[7],
				'country_code' => $setting[8],
				'last_check_update_time' => $setting[10],
				'oauth_client_id' => $setting[11],
				'oauth_client_secret' => $setting[12],
				'oauth_redirect_uri' => 'http://localhost:15298/callback.php',
				'oauth_access_token' => $setting[14],
				'oauth_expires' => $setting[15],
				'oauth_refresh_token' => $setting[16],
				'display_name' => $setting[17],
				'userid' => $setting[18],
				'echonest_api_key' => '5EG94BIZEGFEY9AL9',
				'is_public_playlists' => 0,
				'use_mopidy' => 0,
				'mopidy_server' => '127.0.0.1',
				'mopidy_port' => '6680',
				'volume_percent' => 20,
				'is_display_rating' => 1,
				'is_autoplay_playlist' => 1,
				'use_growl' => 0,
			);

			$ret = $w->write($migrated, 'settings.json');
		}
		catch (PDOException $e) {
			logMsg("Error(getSettings): (exception " . print_r($e) . ")");
		}
		deleteTheFile($w->data() . '/settings.db');
	}

	$settings = $w->read('settings.json');

	if ($settings == false) {
		$default = array(
			'all_playlists' => 1,
			'is_alfred_playlist_active' => 1,
			'radio_number_tracks' => 30,
			'now_playing_notifications' => 1,
			'max_results' => 50,
			'alfred_playlist_uri' => '',
			'alfred_playlist_name' => '',
			'country_code' => '',
			'last_check_update_time' => 0,
			'oauth_client_id' => '',
			'oauth_client_secret' => '',
			'oauth_redirect_uri' => 'http://localhost:15298/callback.php',
			'oauth_access_token' => '',
			'oauth_expires' => 0,
			'oauth_refresh_token' => '',
			'display_name' => '',
			'userid' => '',
			'echonest_api_key' => '5EG94BIZEGFEY9AL9',
			'is_public_playlists' => 0,
			'quick_mode' => 0,
			'use_mopidy' => 0,
			'mopidy_server' => '127.0.0.1',
			'mopidy_port' => '6680',
			'volume_percent' => 20,
			'is_display_rating' => 1,
			'is_autoplay_playlist' => 1,
			'use_growl' => 0,
		);

		$ret = $w->write($default, 'settings.json');
		displayNotificationWithArtwork($w,"Settings have been set to default", './images/info.png', 'Settings reset');

		$settings = $w->read('settings.json');
	}

	// add quick_mode if needed
	if (!isset($settings->quick_mode)) {
		updateSetting($w, 'quick_mode', 0);
		$settings = $w->read('settings.json');
	}

	// add usemopidy if needed
	if (!isset($settings->use_mopidy)) {
		updateSetting($w, 'use_mopidy', 0);
		$settings = $w->read('settings.json');
	}

	// add mopidy_server if needed
	if (!isset($settings->mopidy_server)) {
		updateSetting($w, 'mopidy_server', '127.0.0.1');
		$settings = $w->read('settings.json');
	}

	// add mopidy_port if needed
	if (!isset($settings->mopidy_port)) {
		updateSetting($w, 'mopidy_port', '6680');
		$settings = $w->read('settings.json');
	}

	// add volume_percent if needed
	if (!isset($settings->volume_percent)) {
		updateSetting($w, 'volume_percent', 20);
		$settings = $w->read('settings.json');
	}

	// add is_display_rating if needed
	if (!isset($settings->is_display_rating)) {
		updateSetting($w, 'is_display_rating', 1);
		$settings = $w->read('settings.json');
	}

	// add is_autoplay_playlist if needed
	if (!isset($settings->is_autoplay_playlist)) {
		updateSetting($w, 'is_autoplay_playlist', 1);
		$settings = $w->read('settings.json');
	}

	// add use_growl if needed
	if (!isset($settings->use_growl)) {
		updateSetting($w, 'use_growl', 0);
		$settings = $w->read('settings.json');
	}

	return $settings;
}


/**
 * updateSetting function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $setting_name
 * @param mixed $setting_new_value
 * @param string $settings_file (default: 'settings.json')
 * @return void
 */
function updateSetting($w, $setting_name, $setting_new_value, $settings_file = 'settings.json') {
	$settings     = $w->read($settings_file);
	$new_settings = array();
	$found = false;

	foreach ($settings as $key => $value) {
		if ($key == $setting_name) {
			$new_settings[$key] = $setting_new_value;
			$found = true;
		} else {
			$new_settings[$key] = $value;
		}
	}
	if ($found == false) {
		$new_settings[$setting_name] = $setting_new_value;
	}
	$ret = $w->write($new_settings, $settings_file);

	return $ret;
}


/**
 * logMsg function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $msg
 * @return void
 */
function logMsg($msg) {
	date_default_timezone_set('UTC');
	$date = date('Y-m-d H:i:s', time());
	file_put_contents('php://stderr', "$date" . "|{$msg}" . PHP_EOL);
}


/**
 * copyDirectory function.
 *
 * @access public
 * @param mixed $source
 * @param mixed $destination
 * @return void
 */
function copyDirectory($source, $destination) {
	if (is_dir($source)) {
		@mkdir($destination);
		$directory = dir($source);
		while (FALSE !== ($readdirectory = $directory->read())) {
			if ($readdirectory == '.' || $readdirectory == '..') {
				continue;
			}
			$PathDir = $source . '/' . $readdirectory;
			if (is_dir($PathDir)) {
				copyDirectory($PathDir, $destination . '/' . $readdirectory);
				continue;
			}
			copy($PathDir, $destination . '/' . $readdirectory);
		}

		$directory->close();
	} else {
		copy($source, $destination);
	}
}

/**
 * removeDirectory function.
 *
 * @access public
 * @param mixed $path
 * @return void
 */
function removeDirectory($path)
{
    if (is_dir($path) === true)
    {
        $files = array_diff(scandir($path), array('.', '..'));

        foreach ($files as $file)
        {
            removeDirectory(realpath($path) . '/' . $file);
        }

        return rmdir($path);
    }

    else if (is_file($path) === true)
    {
        return unlink($path);
    }

    return false;
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
function do_post_request($url, $data, $optional_headers = null) {
	$params = array(
		'http' => array(
			'method' => 'POST',
			'content' => $data
		)
	);
	if ($optional_headers !== null) {
		$params['http']['header'] = $optional_headers;
	}
	$ctx = stream_context_create($params);
	$fp  = @fopen($url, 'rb', false, $ctx);
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
function do_async_post_request($url, $params) {
	foreach ($params as $key => &$val) {
		if (is_array($val)) {
			$val = implode(',', $val);
		}
		$post_params[] = $key . '=' . urlencode($val);
	}
	$post_string = implode('&', $post_params);

	$parts = parse_url($url);

	$fp = @fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);

	if ($fp) {
		$out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
		$out .= "Host: " . $parts['host'] . "\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-Length: " . strlen($post_string) . "\r\n";
		$out .= "Connection: Close\r\n\r\n";
		if (isset($post_string)) {
			$out .= $post_string;
		}

		fwrite($fp, $out);
		fclose($fp);
	} else {
		logMsg("Error: Problem when updating stat with stathat");
	}
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
function stathat_count($stat_key, $user_key, $count) {
	return do_async_post_request("http://api.stathat.com/c", array(
			'key' => $stat_key,
			'ukey' => $user_key,
			'count' => $count
		));
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
function stathat_value($stat_key, $user_key, $value) {
	do_async_post_request("http://api.stathat.com/v", array(
			'key' => $stat_key,
			'ukey' => $user_key,
			'value' => $value
		));
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
function stathat_ez_count($email, $stat_name, $count) {
	do_async_post_request("http://api.stathat.com/ez", array(
			'email' => $email,
			'stat' => $stat_name,
			'count' => $count
		));
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
function stathat_ez_value($email, $stat_name, $value) {
	do_async_post_request("http://api.stathat.com/ez", array(
			'email' => $email,
			'stat' => $stat_name,
			'value' => $value
		));
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
function stathat_count_sync($stat_key, $user_key, $count) {
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
function stathat_value_sync($stat_key, $user_key, $value) {
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
function stathat_ez_count_sync($email, $stat_name, $count) {
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
function stathat_ez_value_sync($email, $stat_name, $value) {
	return do_post_request("http://api.stathat.com/ez", "email=$email&stat=$stat_name&value=$value");
}
