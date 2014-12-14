<?php

// Turn off all error reporting
//error_reporting(0);

require './src/functions.php';
require_once './vendor/phprtflite/phprtflite/lib/PHPRtfLite.php';

// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$query = $argv[1];
$type = $argv[2];
$add_to_option = $argv[3];

$arg = mb_unserialize($query);
$track_uri = $arg[0];
$album_uri = $arg[1];
$artist_uri = $arg[2];
$playlist_uri = $arg[3];
$spotify_command = $arg[4];
$original_query = $arg[5];
$other_settings = $arg[6];
$other_action = $arg[7];
$alfred_playlist_uri = $arg[8];
$artist_name = $arg[9];
$track_name = $arg[10];
$album_name = $arg[11];
$track_artwork_path = $arg[12];
$artist_artwork_path = $arg[13];
$album_artwork_path = $arg[14];
$playlist_name = $arg[15];
$playlist_artwork_path = $arg[16];
$alfred_playlist_name = $arg[17];
$now_playing_notifications = $arg[18];
$is_alfred_playlist_active = $arg[19];
$country_code = $arg[20];
$userid = $arg[21];


if ($add_to_option != "") {
    if (file_exists($w->data() . '/update_library_in_progress')) {
        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
        return;
    }
}

if ($spotify_command != "" && $type == "TRACK" && $add_to_option == "") {

    $spotify_command = str_replace("\\", "", $spotify_command);
    exec("osascript -e 'tell application \"Spotify\" to $spotify_command'");
    return;
}

if ($type == "TRACK" && $other_settings == "") {
    if ($track_uri != "") {
        if ($add_to_option != "") {
            $tmp = explode(':', $track_uri);
	        if($tmp[1] == 'local') {
				// local track, look it up online
				$query = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
            	$results = searchWebApi($w,$country_code,$query, 'track', 1);

            	if(count($results) > 0) {
					// only one track returned
					$track=$results[0];
					$artists = $track->artists;
					$artist = $artists[0];
                	echo "Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name\n";
                	$track_uri = $track->uri;
                	$tmp = explode(':', $track_uri);

            	} else {
	            	echo "Could not find track: $track_uri / $track_name / $artist_name \n";
                    displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match','./images/warning.png', 'Error!');
                    return;
            	}
	        }
            if ($track_artwork_path == "") {
                $track_artwork_path = getTrackOrAlbumArtwork($w,  $track_uri, true);
            }
            if ($is_alfred_playlist_active == true) {

                if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
                    displayNotificationWithArtwork("Alfred Playlist is not set", './images/warning.png');
                    return;
                }

                // add track to alfred playlist

                $ret = addTracksToPlaylist($w, $tmp[2], $alfred_playlist_uri, $alfred_playlist_name, false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' added to ' . $alfred_playlist_name . ' Alfred Playlist', $track_artwork_path, 'Add Track to Alfred Playlist');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' is already in ' . $alfred_playlist_name . ' Alfred Playlist','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            } else {
                // add track to your music
                $ret = addTracksToYourMusic($w, $tmp[2], false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' added to Your Music', $track_artwork_path, 'Add Track to Your Music');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' is already in Your Music','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            }
        } else if ($playlist_uri != "") {
            // start now playing if needed
            if ($now_playing_notifications == "") {
				//
				// Get Settings from a duplicate DB to avoid clash
				// with main.php
				//
			    $setting = getSettingsFromDuplicateDb($w);
			    if($setting == false) {
				   return false;
			    }
				$now_playing_notifications = $setting[4];

            }
			if ($now_playing_notifications == 1) {
				exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
			}
            exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\" in context \"$playlist_uri\"'");
          
            if($now_playing_notifications == 0) {
            	displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
            }
            stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
            return;
        } else {
            if ($other_action == "") {

	            // start now playing if needed
	            if ($now_playing_notifications == "") {
					//
					// Get Settings from a duplicate DB to avoid clash
					// with main.php
					//
				    $setting = getSettingsFromDuplicateDb($w);
				    if($setting == false) {
					   return false;
				    }
					$now_playing_notifications = $setting[4];

	            }
				if ($now_playing_notifications == 1) {
					exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
				}
                exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
                if($now_playing_notifications == 0) {
                	displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
                }
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
                return;
            }
        }
    }
} else if ($type == "ALBUM") {
    if ($album_uri == "") {
        // case of current song with alt
        $album_uri = getAlbumUriFromTrack($w, $track_uri);
        if ($album_uri == false) {
            displayNotificationWithArtwork("Cannot get current album",'./images/warning.png', 'Error!');
            return;
        }
        $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, true);
    }
    // start now playing if needed
    if ($now_playing_notifications == "") {
		//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }
		$now_playing_notifications = $setting[4];

    }
	if ($now_playing_notifications == 1) {
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
	}
    exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
    displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path);
    stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
    return;
} else if ($type == "ONLINE") {
    if ($artist_uri == "") {
        // case of current song with cmd
        $artist_uri = getArtistUriFromTrack($w, $track_uri);
        if ($artist_uri == false) {
            displayNotificationWithArtwork("Cannot get current artist",'./images/warning.png', 'Error!');
            return;
        }
    }

    exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹$artist_uri@$artist_name\"'");
    stathat_ez_count('AlfredSpotifyMiniPlayer', 'lookup online', 1);
    return;
} else if ($type == "ALBUM_OR_PLAYLIST") {
    if ($add_to_option != "") {

        if ($album_name != "") {
            if ($album_uri == "") {
                // case of current song with shift
                $album_uri = getAlbumUriFromTrack($w, $track_uri);
                if ($album_uri == false) {
                    displayNotificationWithArtwork("Cannot get current album",'./images/warning.png', 'Error!');
                    return;
                }
                $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, true);
            }

            if ($is_alfred_playlist_active == true) {

                if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
                    displayNotificationWithArtwork("Alfred Playlist is not set", './images/warning.png');
                    return;
                }

                // add album to alfred playlist
                $ret = addTracksToPlaylist($w, getTheAlbumTracks($w, $album_uri), $alfred_playlist_uri, $alfred_playlist_name, false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('Album ' . $album_name . ' added to ' . $alfred_playlist_name . ' Alfred Playlist', $album_artwork_path, 'Add Album to Alfred Playlist');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('Album ' . $album_name . ' is already in ' . $alfred_playlist_name . ' Alfred Playlist','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            } else {
                // add album to your music
                $ret = addTracksToYourMusic($w, getTheAlbumTracks($w, $album_uri), false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('Album ' . $album_name . ' added to Your Music', $album_artwork_path, 'Add Album to Your Music');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('Album ' . $album_name . ' is already in Your Music','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            }

            return;
        } else if ($playlist_uri != "") {
            $playlist_artwork_path = getPlaylistArtwork($w,  $playlist_uri, true, true);

            if ($is_alfred_playlist_active == true) {
                if ($playlist_uri == $alfred_playlist_uri) {
                    displayNotificationWithArtwork("Cannot add Alfred Playlist " . $alfred_playlist_name . " to itself!",'./images/warning.png', 'Error!');
                    return;
                }
                // add playlist to alfred playlist
                $ret = addTracksToPlaylist($w, getThePlaylistTracks($w, $playlist_uri), $alfred_playlist_uri, $alfred_playlist_name, false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to ' . $alfred_playlist_name . ' Alfred Playlist', $playlist_artwork_path, 'Add Playlist to Alfred Playlist');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in ' . $alfred_playlist_name . ' Alfred Playlist','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            } else {
                // add playlist to your music
                $ret = addTracksToYourMusic($w, getThePlaylistTracks($w, $playlist_uri), false);
                stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
                if (is_numeric($ret) && $ret > 0) {
                    displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to Your Music', $playlist_artwork_path, 'Add Playlist to Your Music');
                    return;
                } else if (is_numeric($ret) && $ret == 0) {
                    displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in Your Music','./images/warning.png', 'Error!');
                    return;
                } else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
            }

            return;
        }
    }
} else if ($type == "DOWNLOAD_ARTWORKS") {
	if(downloadArtworks($w) == false) {
		displayNotificationWithArtwork("Error when downloading artworks",'./images/warning.png', 'Error!');
		return;
	}
    return;
} else if ($type == "ARTIST") {

    if ($artist_uri == "") {
        // case of current song with cmd
        $artist_uri = getArtistUriFromTrack($w, $track_uri);
        if ($artist_uri == false) {
            displayNotificationWithArtwork("Cannot get current artist",'./images/warning.png', 'Error!');
            return;
        }
        $artist_artwork_path = getArtistArtwork($w,  $artist_name, true);
    }

    // start now playing if needed
    if ($now_playing_notifications == "") {
		//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }
		$now_playing_notifications = $setting[4];

    }
	if ($now_playing_notifications == 1) {
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
	}
    exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
    displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path, 'Play Artist');
    stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
    return;
}

if ($playlist_uri != "" && $other_settings == "") {
    // start now playing if needed
    if ($now_playing_notifications == "") {
		//
		// Get Settings from a duplicate DB to avoid clash
		// with main.php
		//
	    $setting = getSettingsFromDuplicateDb($w);
	    if($setting == false) {
		   return false;
	    }
		$now_playing_notifications = $setting[4];

    }
	if ($now_playing_notifications == 1) {
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
	}
    exec("osascript -e 'tell application \"Spotify\" to play track \"$playlist_uri\"'");
    displayNotificationWithArtwork('🔈 Playlist ' . $playlist_name, $playlist_artwork_path, 'Launch Playlist');
    stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
    return;
} else if ($other_settings != "") {
    $setting = explode('▹', $other_settings);
    if ($setting[0] == "MAX_RESULTS") {
        $setSettings = "update settings set max_results=" . $setting[1];
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Max results set to $setting[1]",'./images/settings.png', 'Settings');
        return;
    } else if ($setting[0] == "RADIO_TRACKS") {
        $setSettings = "update settings set radio_number_tracks=" . $setting[1];
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Radio track number set to $setting[1]",'./images/settings.png', 'Settings');
        return;
    } else if ($setting[0] == "Oauth_Client_ID") {
        $setSettings = 'update settings set oauth_client_id=\"' . $setting[1] . '\"';
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Client ID set to $setting[1]",'./images/settings.png', 'Settings');
        return;
    } else if ($setting[0] == "Oauth_Client_SECRET") {
        $setSettings = 'update settings set oauth_client_secret=\"' . $setting[1] . '\"';
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Client Secret set to $setting[1]",'./images/settings.png', 'Settings');
        return;
    } else if ($setting[0] == "ALFRED_PLAYLIST") {
        $setSettings = 'update settings set alfred_playlist_uri=\"' . $setting[1] . '\"' . ',alfred_playlist_name=\"' . $setting[2] . '\"';
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");

        displayNotificationWithArtwork('Alfred Playlist set to ' . $setting[2], getPlaylistArtwork($w,  $setting[1], true), 'Settings');
        return;

    } else if ($setting[0] == "ADD_TO_PLAYLIST") {

	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }

		//if playlist_uri is notset, then create it
		if($setting[1] == 'notset') {

			$new_playlist_uri = createTheUserPlaylist($w, $setting[2]);

			if($new_playlist_uri != false) {
				$setting[1] = $new_playlist_uri;
			} else {
				return;
			}
		}

        // add track to playlist
        if ($track_uri != '') {
            $track_artwork_path = getTrackOrAlbumArtwork($w,  $track_uri, true);
            $tmp = explode(':', $track_uri);
	        if($tmp[1] == 'local') {
				// local track, look it up online

				$query = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
            	$results = searchWebApi($w,$country_code,$query, 'track', 1);

            	if(count($results) > 0) {
					// only one track returned
					$track=$results[0];
					$artists = $track->artists;
					$artist = $artists[0];
                	echo "Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name\n";
                	$track_uri = $track->uri;
                	$tmp = explode(':', $track_uri);

            	} else {
	            	echo "Could not find track: $track_uri / $track_name / $artist_name \n";
                    displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match','./images/warning.png', 'Error!');
                    return;
            	}
	        }
            $ret = addTracksToPlaylist($w, $tmp[2], $setting[1], $setting[2], false);
            stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
            if (is_numeric($ret) && $ret > 0) {
                displayNotificationWithArtwork('' . $track_name . ' added to ' . $setting[2] . ' playlist', $track_artwork_path, 'Add Track to Playlist');
                return;
            } else if (is_numeric($ret) && $ret == 0) {
                displayNotificationWithArtwork('' . $track_name . ' is already in ' . $setting[2] . ' playlist','./images/warning.png', 'Error!');
                return;
            } else {
				displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
                return;
            }
        } // add playlist to playlist
        elseif ($playlist_uri != '') {
            $playlist_artwork_path = getPlaylistArtwork($w,  $playlist_uri, true, true);
            $ret = addTracksToPlaylist($w, getThePlaylistTracks($w, $playlist_uri), $setting[1], $setting[2], false);
            stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
            if (is_numeric($ret) && $ret > 0) {
                displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to ' . $setting[2] . ' playlist', $playlist_artwork_path, 'Add Playlist to Playlist');
                return;
            } else if (is_numeric($ret) && $ret == 0) {
                displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in ' . $setting[2] . ' playlist','./images/warning.png', 'Error!');
                return;
            } else {
				displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
                return;
            }
        } // add album to playlist
        elseif ($album_uri != '') {
            $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, true);
            $ret = addTracksToPlaylist($w, getTheAlbumTracks($w, $album_uri), $setting[1], $setting[2], false);
            stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
            if (is_numeric($ret) && $ret > 0) {
                displayNotificationWithArtwork('Album ' . $album_name . ' added to ' . $setting[2] . ' playlist', $album_artwork_path, 'Add Album to Playlist');
                return;
            } else if (is_numeric($ret) && $ret == 0) {
                displayNotificationWithArtwork('Album ' . $album_name . ' is already in ' . $setting[2] . ' playlist','./images/warning.png', 'Error!');
                return;
            } else {
				displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
                return;
            }
        }
    } else if ($setting[0] == "ADD_TO_YOUR_MUSIC") {
		    if (file_exists($w->data() . '/update_library_in_progress')) {
		        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
		        return;
		    }
			// add track to your music
			if($track_uri != '') {
				$track_artwork_path = getTrackOrAlbumArtwork($w,  $track_uri, true);
				$tmp = explode(':', $track_uri);
		        if($tmp[1] == 'local') {
					// local track, look it up online

					$query = 'track:' . strtolower($track_name) . ' artist:' . strtolower($artist_name);
	            	$results = searchWebApi($w,$country_code,$query, 'track', 1);

	            	if(count($results) > 0) {
						// only one track returned
						$track=$results[0];
						$artists = $track->artists;
						$artist = $artists[0];
	                	echo "Unknown track $track_uri / $track_name / $artist_name replaced by track: $track->uri / $track->name / $artist->name\n";
	                	$track_uri = $track->uri;
	                	$tmp = explode(':', $track_uri);

	            	} else {
		            	echo "Could not find track: $track_uri / $track_name / $artist_name \n";
	                    displayNotificationWithArtwork('Local track ' . $track_name . ' has not online match','./images/warning.png', 'Error!');
	                    return;
	            	}
		        }
				$ret = addTracksToYourMusic($w, $tmp[2], false);
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('' . $track_name . ' added to Your Music', $track_artwork_path, 'Add Track to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
					displayNotificationWithArtwork('' . $track_name . ' is already in Your Music','./images/warning.png', 'Error!');
					return;
				} else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
            	}
			} // add playlist to your music
			elseif ($playlist_uri != '') {
				$playlist_artwork_path = getPlaylistArtwork($w,  $playlist_uri, true, true);
				$ret = addTracksToYourMusic($w, getThePlaylistTracks($w, $playlist_uri), false);
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to Your Music', $playlist_artwork_path, 'Add Playlist to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
					displayNotificationWithArtwork('Playlist ' . $playlist_name . ' is already in Your Music','./images/warning.png', 'Error!');
					return;
				} else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
			} // add album to your music
			elseif ($album_uri != '') {
				$album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, true);
				$ret = addTracksToYourMusic($w, getTheAlbumTracks($w, $album_uri), false);
				stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('Album ' . $album_name . ' added to Your Music', $album_artwork_path, 'Add Album to Your Music');
					return;
				} else if (is_numeric($ret) && $ret == 0) {
					displayNotificationWithArtwork('Album ' . $album_name . ' is already in Your Music','./images/warning.png', 'Error!');
					return;
				} else {
					displayNotificationWithArtwork('Exception occurred. Use debug command to get tgz file and then open an issue','./images/warning.png', 'Error!');
	                return;
	            }
			}
	} else if ($setting[0] == "Open_Url") {
        exec("open \"$setting[1]\"");
        return;
    } else if ($setting[0] == "CLEAR_ALFRED_PLAYLIST") {
        if ($setting[1] == "" || $setting[2] == "") {
            displayNotificationWithArtwork("Alfred Playlist is not set", './images/warning.png');
            return;
        }

        if (clearPlaylist($w, $setting[1], $setting[2])) {
            displayNotificationWithArtwork('Alfred Playlist ' . $setting[2] . ' was cleared', getPlaylistArtwork($w,  $setting[1], true), 'Clear Alfred Playlist');
        }
        return;
    }
} else if ($other_action != "") {
    if ($other_action == "disable_all_playlist") {
        $setSettings = "update settings set all_playlists=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Search scope set to Your Music only", './images/search_scope_yourmusic_only.png', 'Settings');
        return;
    } else if ($other_action == "enable_all_playlist") {
        $setSettings = "update settings set all_playlists=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Search scope set to your complete library", './images/search.png', 'Settings');
        return;
    } else if ($other_action == "enable_spotifiuous") {
        $setSettings = "update settings set is_spotifious_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Spotifious is now enabled", './images/enable_spotifious.png', 'Settings');
        return;
    } else if ($other_action == "disable_spotifiuous") {
        $setSettings = "update settings set is_spotifious_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Spotifious is now disabled", './images/disable_spotifious.png', 'Settings');
        return;
    } else if ($other_action == "enable_now_playing_notifications") {
        $setSettings = "update settings set is_lyrics_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
        displayNotificationWithArtwork("Now Playing notifications are now enabled", './images/enable_now_playing.png', 'Settings');
        return;
    } else if ($other_action == "search_in_spotifious") {
    	exec("osascript -e 'tell application \"Alfred 2\" to search \"spotifious $original_query\"'");
        return;
    } else if ($other_action == "disable_now_playing_notifications") {
        $setSettings = "update settings set is_lyrics_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");

		// stop process
		exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a stop >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
        displayNotificationWithArtwork("Now Playing notifications are now disabled", './images/disable_now_playing.png', 'Settings');
        return;
    } else if ($other_action == "enable_alfred_playlist") {
        $setSettings = "update settings set is_alfred_playlist_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Controlling Alfred Playlist", './images/alfred_playlist.png', 'Settings');
        return;
    } else if ($other_action == "disable_alfred_playlist") {
        $setSettings = "update settings set is_alfred_playlist_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotificationWithArtwork("Controlling Your Music", './images/yourmusic.png', 'Settings');
        return;
    } else if ($other_action == "play_track_in_album_context") {
        // start now playing if needed
        if ($now_playing_notifications == "") {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$now_playing_notifications = $setting[4];

        }
		if ($now_playing_notifications == 1) {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		}
        exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\" in context \"$album_uri\"'");
        $album_artwork_path = getTrackOrAlbumArtwork($w,  $album_uri, true);
        if($now_playing_notifications == 0) {
        	displayNotificationWithArtwork('🔈 ' . $track_name . ' in album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path, 'Play Track from Album');
        }
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
        return;
    } else if ($other_action == "play") {
        exec("osascript -e 'tell application \"Spotify\" to play'");
        if($now_playing_notifications == 0) {
			displayNotificationForCurrentTrack($w);
		}
	    return;
    } else if ($other_action == "pause") {
        exec("osascript -e 'tell application \"Spotify\" to pause'");
        return;
    } else if ($other_action == "kill_update") {
        killUpdate($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'kill update', 1);
        return;
    } else if ($other_action == "lookup_current_artist") {
		lookupCurrentArtist($w);
		return;
    } else if ($other_action == "lyrics") {
        displayLyricsForCurrentTrack();
        return;
    } else if ($other_action == "current_track_radio") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
        createRadioSongPlaylistForCurrentTrack($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
        return;
    } else if ($other_action == "current_artist_radio") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
	    createRadioArtistPlaylistForCurrentArtist($w);
	    stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
	    return;
    } else if ($other_action == "play_current_artist") {
        playCurrentArtist($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
        return;
    } else if ($other_action == "play_current_album") {
        playCurrentAlbum($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
        return;
    } else if ($other_action == "Oauth_Login") {
        $cache_log = $w->cache() . '/spotify_mini_player_web_server.log';
        exec("php -S localhost:15298 > \"$cache_log\" 2>&1 &");
        sleep(2);
        exec("open http://localhost:15298");
        return;
    } else if ($other_action == "check_for_update") {
        if (!$w->internet()) {
            displayNotificationWithArtwork("No internet connection", './images/warning.png');
            return;
        }

        $dbfile = $w->data() . '/settings.db';

        try {
            $dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
            $dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            handleDbIssuePdoEcho($dbsettings,$w);
            $dbsettings = null;
            return;
        }
        $check_results = checkForUpdate($w, 0, $dbsettings);
        if ($check_results != null && is_array($check_results)) {
            displayNotificationWithArtwork('New version ' . $check_results[0] . ' is available in Downloads directory ', './images/check_update.png', 'Update Available!');
        } else if ($check_results == null) {
            displayNotificationWithArtwork('No update available', './images/check_update.png', 'No Update');
        }
        return;
    } else if ($other_action == "current") {
	    if($now_playing_notifications == 0) {
			displayNotificationForCurrentTrack($w);
	    }
        return;
    } else if ($other_action == "force_current") {
		displayNotificationForCurrentTrack($w);
        return;
    } else if ($other_action == "add_current_track_to") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
	    addCurrentTrackTo($w);
        return;
    } else if ($other_action == "previous") {
        exec("osascript -e 'tell application \"Spotify\" to previous track'");
	    if($now_playing_notifications == 0) {
			displayNotificationForCurrentTrack($w);
	    }
        return;
    } else if ($other_action == "next") {
        exec("osascript -e 'tell application \"Spotify\" to next track'");
	    if($now_playing_notifications == 0) {
			displayNotificationForCurrentTrack($w);
	    }
        return;
    } else if ($other_action == "add_current_track") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
        addCurrentTrackToAlfredPlaylistOrYourMusic($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'add', 1);
        return;
    } else if ($other_action == "random") {
        $track_uri = getRandomTrack($w);
        if ($track_uri == false) {
            displayNotificationWithArtwork("Cannot find a random track",'./images/warning.png', 'Error!');
            return;
        }
        // start now playing if needed
        if ($now_playing_notifications == "") {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$now_playing_notifications = $setting[4];

        }
		if ($now_playing_notifications == 1) {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		}
        exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
	    if($now_playing_notifications == 0) {
			displayNotificationForCurrentTrack($w);
	    }
        return;
    } else if (startsWith($other_action, 'display_biography')) {
	    displayBiography($w, $artist_uri, $artist_name, $other_action);
	    stathat_ez_count('AlfredSpotifyMiniPlayer', 'display biography', 1);
        return;
    } else if ($other_action == "lookup_artist") {

        if (!$w->internet()) {
            displayNotificationWithArtwork("No internet connection", './images/warning.png', 'Error!');
            return;
        }
        if ($artist_uri == "") {
            $artist_uri = getArtistUriFromTrack($w, $track_uri);
        }
        exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($artist_name) . "\"'");
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'lookup online', 1);
        return;
    } else if ($other_action == "playartist") {
        // start now playing if needed
        if ($now_playing_notifications == "") {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$now_playing_notifications = $setting[4];

        }
		if ($now_playing_notifications == 1) {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		}
        exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
        displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path, 'Play Artist');
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
        return;
    } else if ($other_action == "playalbum") {
        if ($album_uri == "") {
            $album_uri = getAlbumUriFromTrack($w, $track_uri);
            if ($album_uri == false) {
                displayNotificationWithArtwork("Cannot get album",'./images/warning.png', 'Error!');
                return;
            }
        }
        // start now playing if needed
        if ($now_playing_notifications == "") {
			//
			// Get Settings from a duplicate DB to avoid clash
			// with main.php
			//
		    $setting = getSettingsFromDuplicateDb($w);
		    if($setting == false) {
			   return false;
		    }
			$now_playing_notifications = $setting[4];

        }
		if ($now_playing_notifications == 1) {
			exec("./src/spotify_mini_player_notifications.ksh -d \"" . $w->data() . "\" -a start >> \"" . $w->cache() . "/action.log\" 2>&1 & ");
		}
        exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
        displayNotificationWithArtwork('🔈 Album ' . $album_name, $album_artwork_path, 'Play Album');
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
        return;
    } else if ($other_action == "volume_up") {
    	exec("osascript -e 'set volume output volume (output volume of (get volume settings) + 6)'");
		return;
	} else if ($other_action == "volume_down") {
    	exec("osascript -e 'set volume output volume (output volume of (get volume settings) - 6)'");
		return;
	} else if ($other_action == "mute") {
    	exec("osascript -e 'if output muted of (get volume settings) is equal to true then
				set volume without output muted
			else
				set volume with output muted
			end if'");
		return;
	} else if ($other_action == "shuffle") {
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

		displayNotificationWithArtwork($command_output,'./images/shuffle.png', 'Shuffle');
		return;
	} else if ($other_action == "radio_artist") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
        createRadioArtistPlaylist($w, $artist_name);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'radio', 1);
        return;
    } else if ($other_action == "play_alfred_playlist") {
    	playAlfredPlaylist($w);
    	stathat_ez_count('AlfredSpotifyMiniPlayer', 'play', 1);
		return;
	} else if ($other_action == "update_library") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
        updateLibrary($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'update library', 1);
        return;
    } else if ($other_action == "refresh_library") {
	    if (file_exists($w->data() . '/update_library_in_progress')) {
	        displayNotificationWithArtwork("Cannot modify library while update is in progress",'./images/warning.png', 'Error!');
	        return;
	    }
        refreshLibrary($w);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'update library', 1);
        return;
    }
}
?>
