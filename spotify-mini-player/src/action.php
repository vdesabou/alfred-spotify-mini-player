<?php

require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

// Report all PHP errors
error_reporting(E_ALL);

$query         = $argv[1];
$type          = $argv[2];
$add_to_option = $argv[3];

$arg                       = mb_unserialize($query);
$track_uri                 = $arg[0];
$album_uri                 = $arg[1];
$artist_uri                = $arg[2];
$playlist_uri              = $arg[3];
$spotify_command           = $arg[4];
$original_query            = $arg[5];
$other_settings            = $arg[6];
$other_action              = $arg[7];
$artist_name               = $arg[8];
$track_name                = $arg[9];
$album_name                = $arg[10];
$track_artwork_path        = $arg[11];
$artist_artwork_path       = $arg[12];
$album_artwork_path        = $arg[13];
$playlist_name             = $arg[14];
$playlist_artwork_path     = $arg[15];


//
// Read settings from JSON
//
$settings                  = getSettings($w);
$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
$now_playing_notifications = $settings->now_playing_notifications;
$alfred_playlist_uri       = $settings->alfred_playlist_uri;
$alfred_playlist_name      = $settings->alfred_playlist_name;
$country_code              = $settings->country_code;
$userid                    = $settings->userid;
$oauth_client_id           = $settings->oauth_client_id;
$oauth_client_secret       = $settings->oauth_client_secret;
$oauth_redirect_uri        = $settings->oauth_redirect_uri;
$oauth_access_token        = $settings->oauth_access_token;
$use_mopidy                = $settings->use_mopidy;
$volume_percent            = $settings->volume_percent;

if($other_action != "reset_settings") {
	if ($oauth_client_id == '' || $oauth_client_secret == '' || $oauth_access_token == '') {
		if ($other_settings != '' && (startsWith($other_settings, 'Oauth_Client') === false && startsWith($other_settings, 'Open') === false)) {
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini \"'");
			return;
		}

		if ($other_action != '' && $other_action != 'Oauth_Login' &&
			! startsWith($other_action, 'current')) {
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini \"'");
			return;
		}
	}
}

if ($userid != 'vdesabou' && ! startsWith($other_action, 'current')) {
	stathat_ez_count('AlfredSpotifyMiniPlayer', 'workflow used', 1);
}

if ($add_to_option != "") {
	if (file_exists($w->data() . '/update_library_in_progress')) {
		displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
		return;
	}
}

if ($spotify_command != "" && $type == "TRACK" && $add_to_option == "") {
	$spotify_command = str_replace("\\", "", $spotify_command);
	if(!startsWith($spotify_command,'activate')) {
		exec("osascript -e 'tell application \"Spotify\" to activate'");
		exec("osascript -e 'set uri to \"spotify:search:\" & \"$spotify_command\"' -e 'tell application \"Finder\" to open location uri'");
	} else {
		exec("osascript -e 'tell application \"Spotify\" to $spotify_command'");
	}
	return;
}

if ($type == "TRACK" && $other_settings == "" &&
	(startsWith($other_action, 'set_playlist_privacy_to_') || $other_action == "play_track_from_play_queue" || $other_action == ""
		||  ($other_action == "play_track_in_album_context" && $add_to_option != "")
		||  ($other_action == "play" && $add_to_option != "")
		||  ($other_action == "playpause" && $add_to_option != "")
		||  ($other_action == "pause" && $add_to_option != "")  )) {
	if ($track_uri != "") {
		if ($add_to_option != "") {
			$tmp = explode(':', $track_uri);
			if ($tmp[1] == 'local') {
				// local track, look it up online
				$query   = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
				$results = searchWebApi($w, $country_code, $query, 'track', 1);

				if (count($results) > 0) {
					// only one track returned
					$track   = $results[0];
					$artists = $track->artists;
					$artist  = $artists[0];
					logMsg("Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name");
					$track_uri = $track->uri;
					$tmp       = explode(':', $track_uri);

				} else {
					logMsg("Could not find track: $track_uri / $track_name / $artist_name");
					displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match', './images/warning.png', 'Error!');
					return;
				}
			}
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Add▹" . $track_uri . "∙" . escapeQuery($track_name) . '▹' . "\"'");
			return;
		} else if ($playlist_uri != "") {
				// start now playing if needed
				$mopidy_arg = "";
				if ($use_mopidy) {
					$mopidy_arg = "MOPIDY";
				}
				exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
				if (! $use_mopidy) {
					exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
					if ($retVal != 0) {
						displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
						exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
						return;
					}
				}
				if ($use_mopidy) {
					playTrackInContextWithMopidy($w, $track_uri, $playlist_uri);
				} else {
					exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\" in context \"$playlist_uri\"'");
				}

				if ($now_playing_notifications == false) {
					displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
				}
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
				}
				addPlaylistToPlayQueue($w, $playlist_uri, $playlist_name);
				return;
			} else {
			if ($other_action == "" || $other_action == "play_track_from_play_queue") {
				// start now playing if needed
				$mopidy_arg = "";
				if ($use_mopidy) {
					$mopidy_arg = "MOPIDY";
				}
				exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
				if (! $use_mopidy) {
					exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
					if ($retVal != 0) {
						displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
						exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
						return;
					}
				}
				if ($use_mopidy) {
					playUriWithMopidyWithoutClearing($w, $track_uri);
				} else {
					exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
				}
				if ($now_playing_notifications == false) {
					displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
				}
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
				}
				if ($other_action == "") {
					if ($use_mopidy) {
						$retArr = array(getCurrentTrackInfoWithMopidy($w));
					} else {
						// get info on current song
						exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
						if ($retVal != 0) {
							displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
							exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
							return;
						}
					}
					if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
						$results = explode('▹', $retArr[0]);
						addTrackToPlayQueue($w, $track_uri, escapeQuery($results[0]), escapeQuery($results[1]), escapeQuery($results[2]), $results[5], $country_code);
					}
				}
				return;
			}
		}
	} elseif ($playlist_uri != "") {
		$mopidy_arg = "";
		if ($use_mopidy) {
			$mopidy_arg = "MOPIDY";
		}
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		if (! $use_mopidy) {
			exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
			if ($retVal != 0) {
				displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
				exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
				return;
			}
		}
		if ($use_mopidy) {
			playUriWithMopidy($w, $playlist_uri);
		} else {
			exec("osascript -e 'tell application \"Spotify\" to play track \"$playlist_uri\"'");
		}

		if ($playlist_artwork_path == '') {
			$playlist_artwork_path = getPlaylistArtwork($w, $playlist_uri, true, false);
		}
		displayNotificationWithArtwork('🔈 Playlist ' . $playlist_name, $playlist_artwork_path, 'Launch Playlist');
		if ($userid != 'vdesabou') {
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
		}
		addPlaylistToPlayQueue($w, $playlist_uri, $playlist_name);
		return;
	}
} else if ($type == "ALBUM") {
		if ($album_uri == "") {
			if($track_uri == "") {
				displayNotificationWithArtwork("Cannot get current album", './images/warning.png', 'Error!');
				return;
			}
			// case of current song with alt
			$album_uri = getAlbumUriFromTrack($w, $track_uri);
			if ($album_uri == false) {
				displayNotificationWithArtwork("Cannot get current album", './images/warning.png', 'Error!');
				return;
			}
			$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
		}
		$mopidy_arg = "";
		if ($use_mopidy) {
			$mopidy_arg = "MOPIDY";
		}
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		if (! $use_mopidy) {
			exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
			if ($retVal != 0) {
				displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
				exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
				return;
			}
		}
		if ($use_mopidy) {
			playUriWithMopidy($w, $album_uri);
		} else {
			exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
		}
		displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path, 'Play Album');
		if ($userid != 'vdesabou') {
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
		}
		addAlbumToPlayQueue($w, $album_uri, $album_name);
		return;
	} else if ($type == "ONLINE") {
		if ($artist_uri == "") {
			// case of current song with cmd
			$artist_uri = getArtistUriFromTrack($w, $track_uri);
			if ($artist_uri == false) {
				displayNotificationWithArtwork("Cannot get current artist", './images/warning.png', 'Error!');
				return;
			}
		}
		exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($artist_name) . '▹' . "\"'");
		if ($userid != 'vdesabou') {
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'lookup online', 1);
		}
		return;
	} else if ($type == "ALBUM_OR_PLAYLIST") {
		if ($add_to_option != "") {
			if ($album_name != "") {
				if ($album_uri == "") {
					if($track_uri == "") {
						displayNotificationWithArtwork("Cannot get current album", './images/warning.png', 'Error!');
						return;
					}
					// case of current song with shift
					$album_uri = getAlbumUriFromTrack($w, $track_uri);
					if ($album_uri == false) {
						displayNotificationWithArtwork("Cannot get current album", './images/warning.png', 'Error!');
						return;
					}
					$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
				}
				exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Add▹" . $album_uri . "∙" . escapeQuery($album_name) . '▹' . "\"'");
				return;
			} else if ($playlist_uri != "") {
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Add▹" . $playlist_uri . "∙" . escapeQuery($playlist_name) . '▹' . "\"'");
					return;
				}
		}
	} else if ($type == "DOWNLOAD_ARTWORKS") {
		if (downloadArtworks($w) == false) {
			displayNotificationWithArtwork("Error when downloading artworks", './images/warning.png', 'Error!');
			return;
		}
		return;
	} else if ($type == "ARTIST_OR_PLAYLIST_PRIVACY") {
		if ($artist_name != "") {
			if ($artist_uri == "") {
				// case of current song with cmd
				$artist_uri = getArtistUriFromTrack($w, $track_uri);
				if ($artist_uri == false) {
					displayNotificationWithArtwork("Cannot get current artist", './images/warning.png', 'Error!');
					return;
				}
				$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, true);
			}
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playUriWithMopidy($w, $artist_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
			}

			displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path, 'Play Artist');
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addArtistToPlayQueue($w, $artist_uri, $artist_name, $country_code);
			return;
		} elseif ($playlist_uri != "") {
			// case cmd on playlist: change privacy
			// in other_action, the privacy is set
			$tmp = explode(':', $playlist_uri);
			if ($userid != $tmp[2]) {
				displayNotificationWithArtwork("You cannot update a playlist you don’t own", './images/warning.png', 'Error!');
				return;
			}
			if ($other_action == 'set_playlist_privacy_to_public') {
				$public = true;
				$msgPublic = 'public';
			} elseif ($other_action == 'set_playlist_privacy_to_private') {
				$public = false;
				$msgPublic = 'private';
			} else {
				displayNotificationWithArtwork("Error when changing playlist privacy", './images/warning.png', 'Error!');
				return;
			}
			setThePlaylistPrivacy($w, $playlist_uri, $playlist_name, $public);
			displayNotificationWithArtwork("Playlist is now " . $msgPublic, './images/disable_public_playlists.png', 'Change playlist privacy');
			return;
		}
	} else if ($other_settings != "") {
		$setting = explode('▹', $other_settings);
		if ($setting[0] == "MAX_RESULTS") {
			$ret = updateSetting($w, 'max_results', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork('Max results set to ' . $setting[1], './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "RADIO_TRACKS") {
			$ret = updateSetting($w, 'radio_number_tracks', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork('Radio track number set to ' . $setting[1], './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		}  else if ($setting[0] == "VOLUME_PERCENT") {
			$ret = updateSetting($w, 'volume_percent', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork('Volume Percentage set to ' . $setting[1], './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "MOPIDY_SERVER") {
			$ret = updateSetting($w, 'mopidy_server', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork('Mopidy server set to ' . $setting[1], './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "MOPIDY_PORT") {
			$ret = updateSetting($w, 'mopidy_port', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork('Mopidy TCP port set to ' . $setting[1], './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "Oauth_Client_ID") {
			$ret = updateSetting($w, 'oauth_client_id', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork("Client ID set to $setting[1]", './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "Oauth_Client_SECRET") {
			$ret = updateSetting($w, 'oauth_client_secret', $setting[1]);
			if ($ret == true) {
				displayNotificationWithArtwork("Client Secret set to $setting[1]", './images/settings.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($setting[0] == "ALFRED_PLAYLIST") {
			$ret = updateSetting($w, 'alfred_playlist_uri', $setting[1]);
			if ($ret == true) {
				$ret = updateSetting($w, 'alfred_playlist_name', $setting[2]);
				if ($ret == true) {
					displayNotificationWithArtwork('Alfred Playlist set to ' . $setting[2], getPlaylistArtwork($w, $setting[1], true), 'Settings');
				} else {
					displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
				}
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;

		} else if ($setting[0] == "ADD_TO_PLAYLIST") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			// if playlist_uri is notset, then create it
			if ($setting[1] == 'notset') {
				$new_playlist_uri = createTheUserPlaylist($w, $setting[2]);
				if ($new_playlist_uri != false) {
					$setting[1] = $new_playlist_uri;
				} else {
					return;
				}
			}
			// add track to playlist
			if ($track_uri != '') {
				$track_artwork_path = getTrackOrAlbumArtwork($w, $track_uri, true);
				$tmp                = explode(':', $track_uri);
				if ($tmp[1] == 'local') {
					// local track, look it up online
					$query   = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
					$results = searchWebApi($w, $country_code, $query, 'track', 1);

					if (count($results) > 0) {
						// only one track returned
						$track   = $results[0];
						$artists = $track->artists;
						$artist  = $artists[0];
						logMsg("Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name");
						$track_uri = $track->uri;
						$tmp       = explode(':', $track_uri);

					} else {
						logMsg("Could not find track: $track_uri / $track_name / $artist_name");
						displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match', './images/warning.png', 'Error!');
						return;
					}
				}
				$ret = addTracksToPlaylist($w, $tmp[2], $setting[1], $setting[2], false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('' . $track_name . ' added to ' . $setting[2] . ' playlist', $track_artwork_path, 'Add Track to Playlist');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('' . $track_name . ' is already in ' . $setting[2] . ' playlist', './images/warning.png', 'Error!');
						return;
					}
			} // add playlist to playlist
			elseif ($playlist_uri != '') {
				$playlist_artwork_path = getPlaylistArtwork($w, $playlist_uri, true, true);
				$ret                   = addTracksToPlaylist($w, getThePlaylistTracks($w, $playlist_uri), $setting[1], $setting[2], false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to ' . $setting[2] . ' playlist', $playlist_artwork_path, 'Add Playlist to Playlist');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in ' . $setting[2] . ' playlist', './images/warning.png', 'Error!');
						return;
					}
			} // add album to playlist
			elseif ($album_uri != '') {
				$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
				$ret                = addTracksToPlaylist($w, getTheAlbumTracks($w, $album_uri), $setting[1], $setting[2], false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Album ' . $album_name . ' added to ' . $setting[2] . ' playlist', $album_artwork_path, 'Add Album to Playlist');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('Album ' . $album_name . ' is already in ' . $setting[2] . ' playlist', './images/warning.png', 'Error!');
						return;
					}
			}
		} else if ($setting[0] == "REMOVE_FROM_PLAYLIST") {

			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			// remove track from playlist
			if ($track_uri != '') {
				$track_artwork_path = getTrackOrAlbumArtwork($w, $track_uri, true);
				$tmp                = explode(':', $track_uri);
				if ($tmp[1] == 'local') {
					displayNotificationWithArtwork('Cannot remove local track ' . $track_name, './images/warning.png', 'Error!');
					return;
				}
				$ret                = removeTrackFromPlaylist($w, $tmp[2], $setting[1], $setting[2]);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if ($ret == true) {
					displayNotificationWithArtwork('' . $track_name . ' removed from ' . $setting[2] . ' playlist', $track_artwork_path, 'Remove Track from Playlist');
					return;
				}
			}
		} else if ($setting[0] == "ADD_TO_YOUR_MUSIC") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			// add track to your music
			if ($track_uri != '') {
				$track_artwork_path = getTrackOrAlbumArtwork($w, $track_uri, true);
				$tmp                = explode(':', $track_uri);
				if ($tmp[1] == 'local') {
					// local track, look it up online

					$query   = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
					$results = searchWebApi($w, $country_code, $query, 'track', 1);

					if (count($results) > 0) {
						// only one track returned
						$track   = $results[0];
						$artists = $track->artists;
						$artist  = $artists[0];
						logMsg("Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name");
						$track_uri = $track->uri;
						$tmp       = explode(':', $track_uri);

					} else {
						logMsg("Could not find track: $track_uri / $track_name / $artist_name");
						displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match', './images/warning.png', 'Error!');
						return;
					}
				}
				$ret = addTracksToYourMusic($w, $tmp[2], false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('' . $track_name . ' added to Your Music', $track_artwork_path, 'Add Track to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('' . $track_name . ' is already in Your Music', './images/warning.png', 'Error!');
						return;
					}
			} // add playlist to your music
			elseif ($playlist_uri != '') {
				$playlist_artwork_path = getPlaylistArtwork($w, $playlist_uri, true, true);
				$ret                   = addTracksToYourMusic($w, getThePlaylistTracks($w, $playlist_uri), false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to Your Music', $playlist_artwork_path, 'Add Playlist to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in Your Music', './images/warning.png', 'Error!');
						return;
					}
			} // add album to your music
			elseif ($album_uri != '') {
				$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
				$ret                = addTracksToYourMusic($w, getTheAlbumTracks($w, $album_uri), false);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Album ' . $album_name . ' added to Your Music', $album_artwork_path, 'Add Album to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotificationWithArtwork('Album ' . $album_name . ' is already in Your Music', './images/warning.png', 'Error!');
						return;
					}
			}
		} else if ($setting[0] == "REMOVE_FROM_YOUR_MUSIC") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			// remove track from your music
			if ($track_uri != '') {
				$track_artwork_path = getTrackOrAlbumArtwork($w, $track_uri, true);
				$tmp                = explode(':', $track_uri);
				$ret                = removeTrackFromYourMusic($w, $tmp[2]);
				if ($userid != 'vdesabou') {
					stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
				}
				if ($ret == true) {
					displayNotificationWithArtwork('' . $track_name . ' removed from Your Music', $track_artwork_path, 'Remove Track from Your Music');
					return;
				}
			}
		} else if ($setting[0] == "Open") {
			exec("open \"$setting[1]\"");
			return;
		} else if ($setting[0] == "Reveal") {
			exec("open -R \"$setting[1]\"");
			return;
		} else if ($setting[0] == "CLEAR_ALFRED_PLAYLIST") {
			if ($setting[1] == "" || $setting[2] == "") {
				displayNotificationWithArtwork("Alfred Playlist is not set", './images/warning.png', 'Error!');
				return;
			}
			if (clearPlaylist($w, $setting[1], $setting[2])) {
				displayNotificationWithArtwork('Alfred Playlist ' . $setting[2] . ' was cleared', getPlaylistArtwork($w, $setting[1], true), 'Clear Alfred Playlist');
			}
			return;
		}
	} else if ($other_action != "") {
		if ($other_action == "disable_all_playlist") {
			$ret = updateSetting($w, 'all_playlists', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Search scope set to Your Music only", './images/search_scope_yourmusic_only.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_all_playlist") {
			$ret = updateSetting($w, 'all_playlists', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Search scope set to your complete library", './images/search.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_now_playing_notifications") {
			$ret = updateSetting($w, 'now_playing_notifications', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Now Playing notifications are now enabled", './images/enable_now_playing.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_now_playing_notifications") {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a stop >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			$ret = updateSetting($w, 'now_playing_notifications', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Now Playing notifications are now disabled", './images/disable_now_playing.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_quick_mode") {
			$ret = updateSetting($w, 'quick_mode', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Quick Mode is now enabled", './images/enable_quick_mode.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_quick_mode") {
			$ret = updateSetting($w, 'quick_mode', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Quick Mode is now disabled", './images/disable_quick_mode.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_display_rating") {
			$ret = updateSetting($w, 'is_display_rating', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Track Rating is now enabled", './images/enable_display_rating.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_display_rating") {
			$ret = updateSetting($w, 'is_display_rating', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Track Rating is now disabled", './images/disable_display_rating.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_autoplay") {
			$ret = updateSetting($w, 'is_autoplay_playlist', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Playlist Autoplay is now enabled", './images/enable_autoplay.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_autoplay") {
			$ret = updateSetting($w, 'is_autoplay_playlist', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Playlist Autoplay is now disabled", './images/disable_autoplay.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_mopidy") {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a stop >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			$ret = updateSetting($w, 'use_mopidy', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Mopidy is now enabled", './images/enable_mopidy.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_mopidy") {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a stop >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.playback.pause", array());
			}
			$ret = updateSetting($w, 'use_mopidy', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Mopidy is now disabled", './images/disable_mopidy.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_alfred_playlist") {
			$ret = updateSetting($w, 'is_alfred_playlist_active', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("Controlling Alfred Playlist", './images/alfred_playlist.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_alfred_playlist") {
			$ret = updateSetting($w, 'is_alfred_playlist_active', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("Controlling Your Music", './images/yourmusic.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "enable_public_playlists") {
			$ret = updateSetting($w, 'is_public_playlists', 1);
			if ($ret == true) {
				displayNotificationWithArtwork("New playlists will be now public", './images/enable_public_playlists.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "disable_public_playlists") {
			$ret = updateSetting($w, 'is_public_playlists', 0);
			if ($ret == true) {
				displayNotificationWithArtwork("New playlists will be now private", './images/disable_public_playlists.png', 'Settings');
			} else {
				displayNotificationWithArtwork("Error while updating settings", './images/settings.png', 'Error!');
			}
			return;
		} else if ($other_action == "play_track_in_album_context") {
			// start now playing if needed
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playTrackInContextWithMopidy($w, $track_uri, $album_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\" in context \"$album_uri\"'");
			}
			$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
			if ($now_playing_notifications == false) {
				displayNotificationWithArtwork('🔈 ' . $track_name . ' in album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path, 'Play Track from Album');
			}
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addAlbumToPlayQueue($w, $album_uri, $album_name);
			return;
		} else if ($other_action == "play") {
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.playback.resume", array());
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play'");
			}
			if ($now_playing_notifications == false) {
				displayNotificationForCurrentTrack($w);
			}
			return;
		} else if ($other_action == "pause") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.playback.pause", array());
			} else {
				exec("osascript -e 'tell application \"Spotify\" to pause'");
			}
			return;
		} else if ($other_action == "playpause") {
			if ($use_mopidy) {
				$state = invokeMopidyMethod($w, "core.playback.get_state", array());
				if($state == 'playing') {
					invokeMopidyMethod($w, "core.playback.pause", array());
				} else {
					invokeMopidyMethod($w, "core.playback.resume", array());
				}
			} else {
				exec("osascript -e 'tell application \"Spotify\" to playpause'");
			}
			return;
		} else if ($other_action == "kill_update") {
			killUpdate($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'kill update', 1);
			}
			return;
		} else if ($other_action == "lookup_current_artist") {
			lookupCurrentArtist($w);
			return;
		} else if ($other_action == "unfollow_playlist") {
			unfollowThePlaylist($w, $playlist_uri);
			return;
		} else if ($other_action == "follow_playlist") {
			followThePlaylist($w, $playlist_uri);
			return;
		} else if ($other_action == "lyrics") {
			displayLyricsForCurrentTrack($w);
			return;
		} else if ($other_action == "current_track_radio") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			createRadioSongPlaylistForCurrentTrack($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
			}
			return;
		} else if ($other_action == "current_artist_radio") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			createRadioArtistPlaylistForCurrentArtist($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
			}
			return;
		} else if ($other_action == "play_current_artist") {
			playCurrentArtist($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			return;
		} else if ($other_action == "play_current_album") {
			playCurrentAlbum($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			return;
		} else if ($other_action == "Oauth_Login") {
			// check PHP version
			$version = explode('.', phpversion());
			if ($version[1] < 4) {
				displayNotificationWithArtwork("PHP 5.4.0 or later is required for authentication", './images/warning.png', 'Error!');
				exec("open http://alfred-spotify-mini-player.com/known-issues/#php_requirement");
				return;
			}
			exec("kill -9 $(ps -efx | grep \"php\" | egrep \"php -S localhost:15298\" | grep -v grep | awk '{print $2}')");
			sleep(1);
			$cache_log = $w->cache() . '/spotify_mini_player_web_server.log';
			exec("php -S localhost:15298 > \"$cache_log\" 2>&1 &");
			sleep(2);
			exec("open http://localhost:15298");
			return;
		} else if ($other_action == "current") {
			if ($now_playing_notifications == true ||
				($now_playing_notifications == false && $type == "")) {
				displayNotificationForCurrentTrack($w);
			}
			if (! $use_mopidy) {
				if ($type != "playing") {
					updateCurrentTrackIndexFromPlayQueue($w);
				}
			}
			return;
		} else if ($other_action == "current_mopidy") {
			$ret = getCurrentTrackInfoWithMopidy($w, false);
			echo "$ret";
			return;
		} else if ($other_action == "add_current_track_to") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			addCurrentTrackTo($w);
			return;
		} else if ($other_action == "remove_current_track_from") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			removeCurrentTrackFrom($w);
			return;
		} else if ($other_action == "download_update") {
			$check_results = checkForUpdate($w, 0, true);
			if ($check_results != null && is_array($check_results)) {
				exec("open \"$check_results[1]\"");
			    displayNotificationWithArtwork("Please install the new version with Alfred", './images/check_update.png', 'Update available');
			    return;
			}
			return;
		}  else if ($other_action == "previous") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.playback.previous", array());
			} else {
				exec("osascript -e 'tell application \"Spotify\" to previous track'");
			}
			if ($now_playing_notifications == true) {
				displayNotificationForCurrentTrack($w);
			}
			return;
		} else if ($other_action == "next") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.playback.next", array());
			} else {
				exec("osascript -e 'tell application \"Spotify\" to next track'");
			}
			if ($now_playing_notifications == false) {
				displayNotificationForCurrentTrack($w);
			}
			return;
		} else if ($other_action == "add_current_track") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			addCurrentTrackToAlfredPlaylistOrYourMusic($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'add_or_remove', 1);
			}
			return;
		} else if ($other_action == "random") {
			list($track_uri, $track_name, $artist_name, $album_name, $duration) = getRandomTrack($w);
			// start now playing if needed
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playUriWithMopidyWithoutClearing($w, $track_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
			}

			if ($now_playing_notifications == false) {
				displayNotificationForCurrentTrack($w);
			}
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addTrackToPlayQueue($w, $track_uri, $track_name, $artist_name, $album_name, $duration, $country_code);
			return;
		} else if ($other_action == "random_album") {
			list($album_uri, $album_name, $theartistname) = getRandomAlbum($w);
			// start now playing if needed
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playUriWithMopidy($w, $album_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
			}
			displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . $theartistname, getTrackOrAlbumArtwork($w, $album_uri, true), 'Play Random Album');
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addAlbumToPlayQueue($w, $album_uri, $album_name);
			return;
		} else if ($other_action == "reset_settings") {
			deleteTheFile($w->data() . '/settings.json');
			return;
		} else if ($other_action == "biography") {
			displayCurrentArtistBiography($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'display biography', 1);
			}
			return;
		} else if ($other_action == "go_back") {
			$history = $w->read('history.json');

			if ($history == false) {
				displayNotificationWithArtwork("No history yet", './images/warning.png', 'Error!');
			}
			$query = array_pop($history);
			// pop twice
			$query = array_pop($history);
			$w->write($history, 'history.json');
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini $query\"'");
			return;
		} else if ($other_action == "lookup_artist") {

			if (!$w->internet()) {
				displayNotificationWithArtwork("No internet connection", './images/warning.png', 'Error!');
				return;
			}
			if ($artist_uri == "") {
				$artist_uri = getArtistUriFromTrack($w, $track_uri);
			}
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($artist_name) . '▹' . "\"'");
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'lookup online', 1);
			}
			return;
		} else if ($other_action == "playartist") {
			$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, true);
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playUriWithMopidy($w, $artist_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
			}
			displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path, 'Play Artist');
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addArtistToPlayQueue($w, $artist_uri, $artist_name, $country_code);
			return;
		} else if ($other_action == "playalbum") {
			if ($album_uri == "") {
				if($track_uri == "") {
					displayNotificationWithArtwork("Cannot get current album", './images/warning.png', 'Error!');
					return;
				}
				$album_uri = getAlbumUriFromTrack($w, $track_uri);
				if ($album_uri == false) {
					displayNotificationWithArtwork("Cannot get album", './images/warning.png', 'Error!');
					return;
				}
			}
			$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, true);
			$mopidy_arg = "";
			if ($use_mopidy) {
				$mopidy_arg = "MOPIDY";
			}
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start -m \"" . $mopidy_arg . "\"  >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			if (! $use_mopidy) {
				exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
				if ($retVal != 0) {
					displayNotificationWithArtwork('AppleScript Exception: ' . htmlspecialchars($retArr[0]) . ' use spot_mini_debug command', './images/warning.png', 'Error!');
					exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini_debug AppleScript Exception: " . htmlspecialchars($retArr[0]) . "\"'");
					return;
				}
			}
			if ($use_mopidy) {
				playUriWithMopidy($w, $album_uri);
			} else {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
			}
			displayNotificationWithArtwork('🔈 Album ' . $album_name, $album_artwork_path, 'Play Album');
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			addAlbumToPlayQueue($w, $album_uri, $album_name);
			return;
		} else if ($other_action == "volume_up") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => invokeMopidyMethod($w, "core.mixer.get_volume", array()) + $volume_percent));
			} else {
				$command_output = exec("osascript -e 'tell application \"Spotify\"
                if it is running then
					if (sound volume + $volume_percent) > 100 then
						set theVolume to 100
						set sound volume to theVolume
						return \"Spotify volume is at maximum level.\"
					else
						set theVolume to (sound volume + $volume_percent)
						set sound volume to theVolume
						return \"Spotify volume has been increased to \" & theVolume & \"%.\"
					end if
                end if
            end tell'");
            	displayNotificationWithArtwork($command_output, './images/volume_up.png', 'Volume Up');
			}

			return;
		} else if ($other_action == "volume_down") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => invokeMopidyMethod($w, "core.mixer.get_volume", array()) - $volume_percent ));
			} else {
				$command_output = exec("osascript -e 'tell application \"Spotify\"
                if it is running then
					if (sound volume - $volume_percent) < 0 then
						set theVolume to 0
						set sound volume to theVolume
						return \"Spotify volume is at minimum level.\"
					else
						set theVolume to (sound volume - $volume_percent)
						set sound volume to theVolume
						return \"Spotify volume has been decreased to \" & theVolume & \"%.\"
					end if
                    set sound volume to theVolume
                end if
            end tell'");
            	displayNotificationWithArtwork($command_output, './images/volume_down.png', 'Volume Down');
			}

			return;
		} else if ($other_action == "volmax") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => 100));
			} else {
				exec("osascript -e 'tell application \"Spotify\"
                if it is running then
                    set sound volume to 100
                end if
            end tell'");
			}
			displayNotificationWithArtwork("Spotify volume has been set to maximum", './images/volmax.png', 'Volume Max');
			return;
		} else if ($other_action == "volmid") {
			if ($use_mopidy) {
				invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => 50));
			} else {
				exec("osascript -e 'tell application \"Spotify\"
                if it is running then
                    set sound volume to 50
                end if
            end tell'");
			}
			displayNotificationWithArtwork("Spotify volume has been set to 50%", './images/volmid.png', 'Volume 50%');
			return;
		} else if ($other_action == "mute") {
			if ($use_mopidy) {
				$volume = invokeMopidyMethod($w, "core.mixer.get_volume", array());
				if ($volume <= 0) {
					invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => 100));
					$command_output = "Spotify volume is unmuted.";
				} else {
					invokeMopidyMethod($w, "core.mixer.set_volume", array('volume' => 0));
					$command_output = "Spotify volume is muted.";
				}
			} else {
				$command_output = exec("osascript -e 'tell application \"Spotify\"
                if sound volume is less than or equal to 0 then
                    set sound volume to 100
                    return \"Spotify volume is unmuted.\"
                else
                    set sound volume to 0
                    return \"Spotify volume is muted.\"
                end if
            end tell'");
			}
			displayNotificationWithArtwork($command_output, './images/mute.png', 'Mute');
			return;
		} else if ($other_action == "shuffle") {
			if ($use_mopidy) {
				$isShuffleEnabled = invokeMopidyMethod($w, "core.tracklist.get_random", array());
				if ($isShuffleEnabled) {
					invokeMopidyMethod($w, "core.tracklist.set_random", array('value' => false));
					$command_output = "Shuffle is now disabled.";
				} else {
					invokeMopidyMethod($w, "core.tracklist.set_random", array('value' => true));
					$command_output = "Shuffle is now enabled.";
				}
			} else {
				$command_output = exec("osascript -e '
    	tell application \"Spotify\"
    	if shuffling enabled is true then
    		if shuffling is true then
				set shuffling to false
				return \"Shuffle is now disabled.\"
			else
				set shuffling to true
				return \"Shuffle is now enabled.\"
			end if
		else
			return \"Shuffle is not currently enabled.\"
		end if
		end tell'");
			}
			displayNotificationWithArtwork($command_output, './images/shuffle.png', 'Shuffle');
			return;
		} else if ($other_action == "spot_mini_debug") {
			createDebugFile($w);
			return;
		} else if ($other_action == "radio_artist") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			createRadioArtistPlaylist($w, $artist_name);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
			}
			return;
		}  else if ($other_action == "complete_collection_artist") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			createCompleteCollectionArtistPlaylist($w, $artist_name, $artist_uri);
			return;
		} else if ($other_action == "play_alfred_playlist") {
			playAlfredPlaylist($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
			}
			return;
		} else if ($other_action == "update_library") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			updateLibrary($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'update library', 1);
			}
			return;
		} else if ($other_action == "refresh_library") {
			if (file_exists($w->data() . '/update_library_in_progress')) {
				displayNotificationWithArtwork("Cannot modify library while update is in progress", './images/warning.png', 'Error!');
				return;
			}
			refreshLibrary($w);
			if ($userid != 'vdesabou') {
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'update library', 1);
			}
			return;
		}
	}
?>
