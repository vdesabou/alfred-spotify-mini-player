<?php

// Turn off all error reporting
//error_reporting(0);

require './src/functions.php';


// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$query = $argv[1];
$type = $argv[2];
$add_to_option = $argv[3];


$arg = mb_unserialize($query);

//var_dump($arg);

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

if ($other_action == "update_playlist" && $playlist_uri != "" && $playlist_name != "") {
	updatePlaylist($w, $playlist_uri, $playlist_name);
	return;
}

if ($spotify_command != "" && $type == "TRACK" && $add_to_option == "") {

	$spotify_command = str_replace("\\", "", $spotify_command);
	exec("osascript -e 'tell application \"Spotify\" to $spotify_command'");

	if ($spotify_command == "playpause") {
		displayNotificationWithArtwork('🎶 Play/Pause ' . $track_name . '
 by ' . ucfirst($artist_name), getTrackOrAlbumArtwork($w, 'black', $track_uri, true));
	}
	return;
}


if ($type == "TRACK") {

	if ($track_uri != "") {
		if ($add_to_option != "") {

			//
			// Read settings from DB
			//
			$getSettings = 'select theme,is_alfred_playlist_active from settings';
			$dbfile = $w->data() . '/settings.db';
			exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

			if ($returnValue != 0) {
				displayNotification("Error: cannot read settings");
				return;
			}

			foreach ($settings as $setting):

				$setting = explode("	", $setting);

			$theme = $setting[0];
			$is_alfred_playlist_active = $setting[1];
			endforeach;

			$tmp = explode(':', $track_uri);

			if ($track_artwork_path == "") {
				$track_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track_uri, true);
			}
			if ($is_alfred_playlist_active == true) {

				if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
					displayNotification("Error: Alfred Playlist is not set");
					return;
				}

				// add track to alfred playlist
				$ret = addTracksToPlaylist($w, $tmp[2], $alfred_playlist_uri, $alfred_playlist_name, false);
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' added to ' . $alfred_playlist_name, $track_artwork_path);
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotification('Error: ' . $track_name . ' by ' . $artist_name . ' is already in ' . $alfred_playlist_name);
					}
			} else {
				// add track to your music
				$ret = addTracksToMyTracks($w, $tmp[2], false);
				if (is_numeric($ret) && $ret > 0) {
					displayNotificationWithArtwork('' . $track_name . ' by ' . $artist_name . ' added to Your Music', $track_artwork_path);
				} else if (is_numeric($ret) && $ret == 0) {
						displayNotification('Error: ' . $track_name . ' by ' . $artist_name . ' is already in Your Music');
					}
			}
		} else if ($playlist_uri != "") {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\" in context \"$playlist_uri\"'");
				displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
				return;
			} else {
			if ($other_action == "") {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
				displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
			}
		}
	}
} else if ($type == "ALBUM") {
		if ($album_uri == "") {
			// case of current song with alt
			$album_uri = getAlbumUriFromTrack($w, $track_uri);
			if ($album_uri == false) {
				displayNotification("Error: cannot get current album");
				return;
			}
			$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $album_uri, true);
		}
		exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
		displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path);
		return;
	} else if ($type == "ONLINE") {
		if ($artist_uri == "") {
			// case of current song with cmd
			$artist_uri = getArtistUriFromTrack($w, $track_uri);
			if ($artist_uri == false) {
				displayNotification("Error: cannot get current artist");
				return;
			}
		}

		exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹$artist_uri@$artist_name\"'");
		return;
	}else if ($type == "RANDOM") {
		$track_uri = getRandomTrack($w);
		if ($track_uri == false) {
			displayNotification("Error: cannot find a random track");
		}
		exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
		displayNotificationForCurrentTrack();
		return;
	}else if ($type == "CURRENT") {
		displayNotificationForCurrentTrack();
		return;
	}else if ($type == "NEXT") {
		exec("osascript -e 'tell application \"Spotify\" to next track'");
		displayNotificationForCurrentTrack();
		return;
	}else if ($type == "PREVIOUS") {
		exec("osascript -e 'tell application \"Spotify\" to previous track'");
		displayNotificationForCurrentTrack();
		return;
	}else if ($type == "PLAY") {
		exec("osascript -e 'tell application \"Spotify\" to playpause'");
		displayNotificationForCurrentTrack();
		return;
	}else if ($type == "PAUSE") {
		exec("osascript -e 'tell application \"Spotify\" to playpause'");
		return;
	}else if ($type == "UPDATE_LIBRARY") {
		updateLibrary($w);
		return;
	}else if ($type == "ADD_CURRENT_TRACK") {

		//
		// Read settings from DB
		//
		$getSettings = 'select theme,is_alfred_playlist_active from settings';
		$dbfile = $w->data() . '/settings.db';
		exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

		if ($returnValue != 0) {
			displayNotification("Error: cannot read settings");
			return;
		}

		foreach ($settings as $setting):

			$setting = explode("	", $setting);

		$theme = $setting[0];
		$is_alfred_playlist_active = $setting[1];
		endforeach;

		if ($is_alfred_playlist_active == true) {
			addCurrentTrackToAlfredPlaylist($w);
		} else {
			addCurrentTrackToMyTracks($w);
		}
		return;
	}

else if ($type == "ALBUM_OR_PLAYLIST") {
		if ($add_to_option != "") {

			if ($album_name != "") {

				//
				// Read settings from DB
				//
				$getSettings = 'select theme,is_alfred_playlist_active from settings';
				$dbfile = $w->data() . '/settings.db';
				exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

				if ($returnValue != 0) {
					displayNotification("Error: cannot read settings");
					return;
				}

				foreach ($settings as $setting):

					$setting = explode("	", $setting);

				$theme = $setting[0];
				$is_alfred_playlist_active = $setting[1];
				endforeach;

				if ($album_uri == "") {
					// case of current song with shift
					$album_uri = getAlbumUriFromTrack($w, $track_uri);
					if ($album_uri == false) {
						displayNotification("Error: cannot get current album");
						return;
					}
					$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $album_uri, true);
				}

				if ($is_alfred_playlist_active == true) {

					if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
						displayNotification("Error: Alfred Playlist is not set");
						return;
					}

					// add album to alfred playlist
					$ret = addTracksToPlaylist($w, getTheAlbumTracks($w, $album_uri), $alfred_playlist_uri, $alfred_playlist_name, false);
					if (is_numeric($ret) && $ret > 0) {
						displayNotificationWithArtwork('Album ' . $album_name . ' added to ' . $alfred_playlist_name, $album_artwork_path);
					} else if (is_numeric($ret) && $ret == 0) {
							displayNotification('Error: Album ' . $album_name . ' is already in ' . $alfred_playlist_name);
						}
				} else {
					// add album to your music
					$ret = addTracksToMyTracks($w, getTheAlbumTracks($w, $album_uri), false);
					if (is_numeric($ret) && $ret > 0) {
						displayNotificationWithArtwork('Album ' . $album_name . ' added to Your Music', $album_artwork_path);
					} else if (is_numeric($ret) && $ret == 0) {
							displayNotification('Error: Album ' . $album_name . ' is already in Your Music');
						}
				}

				return;
			} else if ($playlist_uri != "") {

					//
					// Read settings from DB
					//
					$getSettings = 'select theme,is_alfred_playlist_active from settings';
					$dbfile = $w->data() . '/settings.db';
					exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

					if ($returnValue != 0) {
						displayNotification("Error: cannot read settings");
						return;
					}

					foreach ($settings as $setting):

						$setting = explode("	", $setting);

					$theme = $setting[0];
					$is_alfred_playlist_active = $setting[1];
					endforeach;

					$playlist_artwork_path = getPlaylistArtwork($w, $theme, $playlist_uri, true, true);

					if ($is_alfred_playlist_active == true) {
						// add playlist to alfred playlist
						$ret = addTracksToPlaylist($w, getThePlaylistTracks($w, $playlist_uri), $alfred_playlist_uri, $alfred_playlist_name, false);
						if (is_numeric($ret) && $ret > 0) {
							displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to ' . $alfred_playlist_name, $playlist_artwork_path);
						} else if (is_numeric($ret) && $ret == 0) {
								displayNotification('Error: Playlist ' . $playlist_name . ' is already in ' . $alfred_playlist_name);
							}
					} else {
						// add playlist to your music
						$ret = addTracksToMyTracks($w, getThePlaylistTracks($w, $playlist_uri), false);
						if (is_numeric($ret) && $ret > 0) {
							displayNotificationWithArtwork('Playlist ' . $playlist_name . ' added to Your Music', $playlist_artwork_path);
						} else if (is_numeric($ret) && $ret == 0) {
								displayNotification('Error: Playlist ' . $playlist_name . ' is already in Your Music');
							}
					}

					return;
				}
		}
	} else if ($type == "ARTIST") {

		if ($artist_uri == "") {
			// case of current song with cmd
			$artist_uri = getArtistUriFromTrack($w, $track_uri);
			if ($artist_uri == false) {
				displayNotification("Error: cannot get current artist");
				return;
			}
			$artist_artwork_path = getArtistArtwork($w, 'black', $artist_uri, true);
		}

		exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
		displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path);
		return;
	}

if ($playlist_uri != "") {
	exec("osascript -e 'tell application \"Spotify\" to play track \"$playlist_uri\"'");
	displayNotificationWithArtwork('🔈 Playlist ' . $playlist_name, $playlist_artwork_path);
}else if ($other_settings != "") {
		$setting = explode('▹', $other_settings);
		if ($setting[0] == "MAX_RESULTS") {
			$setSettings = "update settings set max_results=" . $setting[1];
			$dbfile = $w->data() . "/settings.db";
			exec("sqlite3 \"$dbfile\" \"$setSettings\"");
			displayNotification("Max results set to $setting[1]");
		}
		else if ($setting[0] == "Oauth_Client_ID") {
				$setSettings = 'update settings set oauth_client_id=\"' . $setting[1] . '\"';
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotification("Client ID set to $setting[1]");
			}
		else if ($setting[0] == "Oauth_Client_SECRET") {
				$setSettings = 'update settings set oauth_client_secret=\"' . $setting[1] . '\"';
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotification("Client Secret set to $setting[1]");
			}
		else if ($setting[0] == "ALFRED_PLAYLIST") {
				$setSettings = 'update settings set alfred_playlist_uri=\"' . $setting[1] . '\"' . ',alfred_playlist_name=\"' . $setting[2] . '\"';
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");

				displayNotificationWithArtwork('Alfred Playlist set to ' . $setting[2], getPlaylistArtwork($w, 'black', $setting[1], true));
				return;

			} else if ($setting[0] == "CLEAR_ALFRED_PLAYLIST") {

				if ($setting[1] == "" || $setting[2] == "") {
					displayNotification("Error: Alfred Playlist is not set");
					return;
				}

				if (clearPlaylist($w, $setting[1], $setting[2])) {
					displayNotificationWithArtwork('Alfred Playlist ' . $setting[2] . ' was cleared' , getPlaylistArtwork($w, 'black', $setting[1], true));
				}
				return;
			} else if ($setting[0] == "GET_LYRICS") {
				if (! $w->internet()) {
					displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
					return;
				}
				getLyrics($w, $setting[1], $setting[2]);
			}
	} else if ($original_query != "") {
		exec("osascript -e 'tell application \"Alfred 2\" to search \"spotifious $original_query\"'");
	} else if ($other_action != "") {

		//
		// Read settings from DB
		//
		$getSettings = 'select theme,is_alfred_playlist_active from settings';
		$dbfile = $w->data() . '/settings.db';
		exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

		if ($returnValue != 0) {
			displayNotification("Error: cannot read settings");
			return;
		}

		foreach ($settings as $setting):

			$setting = explode("	", $setting);

		$theme = $setting[0];
		$is_alfred_playlist_active = $setting[1];
		endforeach;

		if ($other_action == "disable_all_playlist") {
			$setSettings = "update settings set all_playlists=0";
			$dbfile = $w->data() . "/settings.db";
			exec("sqlite3 \"$dbfile\" \"$setSettings\"");
			displayNotificationWithArtwork("Search scope set to your music", './images/' . $theme . '/' . 'search.png');
		} else if ($other_action == "enable_all_playlist") {
				$setSettings = "update settings set all_playlists=1";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Search scope set to all playlists", './images/' . $theme . '/' . 'search.png');
			} else if ($other_action == "enable_spotifiuous") {
				$setSettings = "update settings set is_spotifious_active=1";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Spotifious is now enabled", './images/' . $theme . '/' . 'check.png');
			} else if ($other_action == "disable_spotifiuous") {
				$setSettings = "update settings set is_spotifious_active=0";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Spotifious is now disabled", './images/' . $theme . '/' . 'uncheck.png');
			} else if ($other_action == "set_theme_to_black") {
				$setSettings = "update settings set theme='black'";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Theme set to black", './images/' . 'black' . '/' . 'check.png');
			} else if ($other_action == "set_theme_to_green") {
				$setSettings = "update settings set theme='green'";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Theme set to green", './images/' . 'green' . '/' . 'check.png');
			} else if ($other_action == "set_theme_to_new") {
				$setSettings = "update settings set theme='new'";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Theme set to new", './images/' . 'new' . '/' . 'check.png');
			} else if ($other_action == "enable_displaymorefrom") {
				$setSettings = "update settings set is_displaymorefrom_active=1";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Now Playing is now enabled", './images/' . $theme . '/' . 'check.png');
			} else if ($other_action == "disable_displaymorefrom") {
				$setSettings = "update settings set is_displaymorefrom_active=0";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Now Playing is now disabled", './images/' . $theme . '/' . 'uncheck.png');
			} else if ($other_action == "enable_lyrics") {
				$setSettings = "update settings set is_lyrics_active=1";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Get Lyrics is now enabled", './images/' . $theme . '/' . 'check.png');
			} else if ($other_action == "disable_lyrics") {
				$setSettings = "update settings set is_lyrics_active=0";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Get Lyrics is now disabled", './images/' . $theme . '/' . 'uncheck.png');
			} else if ($other_action == "enable_alfred_playlist") {
				$setSettings = "update settings set is_alfred_playlist_active=1";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Alfred Playlist is now enabled", './images/' . $theme . '/' . 'check.png');
			} else if ($other_action == "disable_alfred_playlist") {
				$setSettings = "update settings set is_alfred_playlist_active=0";
				$dbfile = $w->data() . "/settings.db";
				exec("sqlite3 \"$dbfile\" \"$setSettings\"");
				displayNotificationWithArtwork("Alfred Playlist is now disabled", './images/' . $theme . '/' . 'uncheck.png');
			}else if ($other_action == "Oauth_Login") {
				exec("php -S localhost:15298 > /tmp/spotify_mini_player_web_server.log 2>&1 &");
				sleep(2);
				exec("open http://localhost:15298");
			} else if ($other_action == "check_for_update") {
				if (! $w->internet()) {
					displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
					return;
				}

				$dbfile = $w->data() . '/settings.db';

				try {
					$dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
					$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				} catch (PDOException $e) {
					handleDbIssuePdoEcho($dbsettings);
					$dbsettings=null;
					return;
				}
				$check_results = checkForUpdate($w, 0, $dbsettings);
				if ($check_results != null && is_array($check_results)) {
					displayNotificationWithArtwork('New version ' . $check_results[0] . ' is available in Downloads directory ', './images/' . $theme . '/' . 'check_update.png');
				}
				else if ($check_results == null) {
						displayNotificationWithArtwork('No update available', './images/' . $theme . '/' . 'check_update.png');
				}

			} else if ($other_action == "current") {
				displayNotificationForCurrentTrack();
				return;
			} else if ($other_action == "add_current_track") {
				if ($is_alfred_playlist_active == true) {
					addCurrentTrackToAlfredPlaylist($w);
				} else {
					addCurrentTrackToMyTracks($w);
				}
				return;
			} else if ($other_action == "previous") {
				exec("osascript -e 'tell application \"Spotify\" to previous track'");
				displayNotificationForCurrentTrack();
				return;
			} else if ($other_action == "next") {
				exec("osascript -e 'tell application \"Spotify\" to next track'");
				displayNotificationForCurrentTrack();
				return;
			} else if ($other_action == "random") {
				$track_uri = getRandomTrack($w);
				if ($track_uri == false) {
					displayNotification("Error: cannot find a random track");
				}
				exec("osascript -e 'tell application \"Spotify\" to play track \"$track_uri\"'");
				displayNotificationForCurrentTrack();
				return;
			}
		else if ($other_action == "display_biography") {
				$getBiography = "select artist_biography from artists where artist_name='" . $artist_name . "'";

				$dbfile = $w->data() . "/library.db";
				exec("sqlite3 -separator '	' \"$dbfile\" \"$getBiography\" 2>&1", $biographs, $returnValue);

				if ($returnValue != 0) {
					displayNotification("There is a problem with the library, try to update it");
					return;
				}

				if (count($biographs) == 0) {
					displayNotificationWithArtwork("No biography found", './images/' . $theme . '/' . 'biography.png');
					return;
				}

				foreach ($biographs as $biography):
					$biography = explode("	", $biography);

				if ($biography[0] != "") {
					$output=strip_tags($biography[0]);
					echo "🎓 $artist_name\n---------------------------\n$output";
					return;
				}
				else {
					displayNotificationWithArtwork("No biography found", './images/' . $theme . '/' . 'biography.png');
					return;
				}
				endforeach;
				return;
			}
		else if ($other_action == "morefromthisartist") {

				if (! $w->internet()) {
					displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
					return;
				}
				exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹" . $artist_uri . "@" . escapeQuery($artist_name) . "\"'");
			}
		else if ($other_action == "playartist") {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$artist_uri\"'");
				displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path);
				return;
			}
		else if ($other_action == "playalbum") {
				exec("osascript -e 'tell application \"Spotify\" to play track \"$album_uri\"'");
				displayNotificationWithArtwork('🔈 Album ' . $album_name, $album_artwork_path);
				return;
			}
		else if ($other_action == "update_library") {
				updateLibrary($w);
				return;
			} else if ($other_action == "update_playlist_list") {
				updatePlaylistList($w);
				return;
			}
	}
?>
