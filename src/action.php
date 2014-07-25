<?php

// Turn off all error reporting
error_reporting(0);

require './src/functions.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\MiniPlayer;

require_once './vendor/autoload.php';


// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$query = $argv[1];
$type = $argv[2];
$alfredplaylist = $argv[3];


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

if ($other_action == "update_playlist" && $playlist_uri != "") {
	refreshPlaylist($w, $playlist_uri);
	return;
}

if ($spotify_command != "" && $type == "TRACK" && $alfredplaylist == "") {

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
		if ($alfredplaylist != "") {

			if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
				displayNotification("Error: Alfred Playlist is not set");
				return;
			}

			// add track to alfred playlist
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$track_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");


			displayNotificationWithArtwork('' . $track_name . '
added to ' . $alfred_playlist_name, $track_artwork_path);

			if (! $w->internet()) {
				displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
				return;
			}

			// update alfred playlist
			refreshPlaylist($w, $alfred_playlist_uri);
		} else if ($playlist_uri != "") {
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playtrackwithplaylistcontext:$track_uri:$playlist_uri\"'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");
				displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
				return;
			}
		else {
			if ($other_action == "") {
				exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
				displayNotificationWithArtwork('🔈 ' . $track_name . ' by ' . ucfirst($artist_name), $track_artwork_path);
			}
		}
	}
} else if ($type == "ALBUM") {
		if ($album_uri == "") {
			// case of current song with alt
			$album_uri = getAlbumUriFromName($w, 'black', $album_name, $artist_name);

			if ($album_artwork_path == "") {
				$album_artwork_path = getTrackOrAlbumArtwork($w, 'black', $album_uri, true);
			}
			if ($album_uri == "") {
				// track is not from library
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playcurrenttrackalbum:" . uniqid() . "\"'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
				displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path);
				return;
			}

			$album_artwork_path = getTrackOrAlbumArtwork($w, 'black', $album_uri, true);
		}

		if ($album_artwork_path == "") {
			$album_artwork_path = getTrackOrAlbumArtwork($w, 'black', $album_uri, true);
		}

		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$album_uri:" . uniqid() . "\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
		displayNotificationWithArtwork('🔈 Album ' . $album_name . ' by ' . ucfirst($artist_name), $album_artwork_path);
		return;
	} else if ($type == "ONLINE") {
		if ($artist_uri == "") {
			// case of current song with ctrl
			$artist_uri = getArtistUriFromName($w, 'black', $artist_name);

			if ($artist_uri == "") {
				$tcpport = getFreeTcpPort();
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:current_track_get_artist:" . $tcpport . ":" . uniqid() . "\"'");

				$server = IoServer::factory(
					new HttpServer(
						new WsServer(
							new MiniPlayer()
						)
					),
					$tcpport
				);
				// FIX THIS: server will exit when done
				// Did not find a way to set a timeout
				$server->run();
				return;
			}
		}

		exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹$artist_uri@$artist_name\"'");
		return;
	}else if ($type == "STAR") {
		starCurrentTrack($w);
		return;
	}else if ($type == "UNSTAR") {
		unstarCurrentTrack($w);
		return;
	}
else if ($type == "ALBUM_OR_PLAYLIST") {
		if ($alfredplaylist != "") {

			if ($album_name != "") {

				if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
					displayNotification("Error: Alfred Playlist is not set");
					return;
				}

				if ($album_uri == "") {
					// case of current song with shift
					$album_uri = getAlbumUriFromName($w, 'black', $album_name, $artist_name);

					if ($album_uri == "") {
						// track is not from library
						exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addcurrenttrackalbumtoalfredplaylist:$alfred_playlist_uri\"'");
						exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
						displayNotificationWithArtwork('Album ' . $album_name . '
added to ' . $alfred_playlist_name, getTrackOrAlbumArtwork($w, $theme, $track_uri, true));

						// update alfred playlist
						refreshPlaylist($w, $alfred_playlist_uri);
						return;
					}
					$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $album_uri, true);
				}
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$album_uri:$alfred_playlist_uri\"'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
				displayNotificationWithArtwork('Album ' . $album_name . '
added to ' . $alfred_playlist_name, $album_artwork_path);
				// update alfred playlist
				refreshPlaylist($w, $alfred_playlist_uri);
				return;
			} else if ($playlist_uri != "") {
					exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addplaylisttoalfredplaylist:$playlist_uri:$alfred_playlist_uri\"'");
					exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
					displayNotificationWithArtwork('Playlist ' . $playlist_name . '
added to ' . $alfred_playlist_name, $playlist_artwork_path);

					// update alfred playlist
					refreshPlaylist($w, $alfred_playlist_uri);
					return;
				}
		}
	} else if ($type == "ARTIST") {

		if ($artist_uri == "") {
			// case of current song with cmd
			$artist_uri = getArtistUriFromName($w, 'black', $artist_name);

			if ($artist_artwork_path == "") {
				$artist_artwork_path = getTrackOrAlbumArtwork($w, 'black', $track_uri, true);
			}

			if ($artist_uri == "") {
				// artist is not from library
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playcurrenttrackartist\"'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
				displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path);
				return;
			}
		}

		if ($artist_artwork_path == "") {
			$artist_artwork_path = getTrackOrAlbumArtwork($w, 'black', $track_uri, true);
		}

		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$artist_uri:" . uniqid() . "\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$artist_uri\"'");
		displayNotificationWithArtwork('🔈 Artist ' . $artist_name, $artist_artwork_path);
		return;
	}

if ($playlist_uri != "") {
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:startplaylist:$playlist_uri:" . uniqid() . "\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");
	displayNotificationWithArtwork('🔈 Playlist ' . $playlist_name, $playlist_artwork_path);
}else if ($other_settings != "") {
		$setting = explode('▹', $other_settings);
		if ($setting[0] == "MAX_RESULTS") {
			$setSettings = "update settings set max_results=" . $setting[1];
			$dbfile = $w->data() . "/settings.db";
			exec("sqlite3 \"$dbfile\" \"$setSettings\"");
			displayNotification("Max results set to $setting[1]");
		} else if ($setting[0] == "ALFRED_PLAYLIST") {
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
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:clearplaylist:$setting[1]:" . uniqid() . "\"'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"$setting[1]\"'");

				displayNotificationWithArtwork('Alfred Playlist ' . $setting[2] . ' was cleared' , getPlaylistArtwork($w, 'black', $setting[1], true));

				// update alfred playlist
				refreshPlaylist($w, $setting[1]);
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
		$getSettings = 'select theme from settings';
		$dbfile = $w->data() . '/settings.db';
		exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

		if ($returnValue != 0) {
			displayNotification("Error: cannot read settings");
			return;
		}


		foreach ($settings as $setting):

			$setting = explode("	", $setting);

		$theme = $setting[0];
		endforeach;

		if ($other_action == "disable_all_playlist") {
			$setSettings = "update settings set all_playlists=0";
			$dbfile = $w->data() . "/settings.db";
			exec("sqlite3 \"$dbfile\" \"$setSettings\"");
			displayNotificationWithArtwork("Search scope set to starred playlist", './images/' . $theme . '/' . 'search.png');
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
			} else if ($other_action == "open_spotify_export_app") {
				exec("osascript -e 'tell application \"Spotify\" to activate'");
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer\"'");
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
					handleDbIssuePdo('new', $dbsettings);
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

			} else if ($other_action == "star") {
				starCurrentTrack($w);
				return;
			} else if ($other_action == "unstar") {
				unstarCurrentTrack($w);
				return;
			} else if ($other_action == "random") {
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:random:" . uniqid() . "\"'");
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
		else if ($other_action == "update_library") {
				if (! $w->internet()) {
					displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
					return;
				}
				touch($w->data() . "/update_library_in_progress");
				$w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

				$tcpport = getFreeTcpPort();
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:update_library:" . $tcpport . ":" . uniqid() . "\"'");

				$server = IoServer::factory(
					new HttpServer(
						new WsServer(
							new MiniPlayer()
						)
					),
					$tcpport
				);
				// FIX THIS: server will exit when done
				// Did not find a way to set a timeout
				$server->run();
			} else if ($other_action == "update_playlist_list") {
				if (! $w->internet()) {
					displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
					return;
				}
				touch($w->data() . "/update_library_in_progress");
				$w->write('InitPlaylistList▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

				$tcpport = getFreeTcpPort();
				exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:update_playlist_list:" . $tcpport . ":" . uniqid() . "\"'");

				$server = IoServer::factory(
					new HttpServer(
						new WsServer(
							new MiniPlayer()
						)
					),
					$tcpport
				);
				// FIX THIS: server will exit when done
				// Did not find a way to set a timeout
				$server->run();
			}
	}


/**
 * starCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function starCurrentTrack($w) {
	$tcpport = getFreeTcpPort();
	$getUser = 'select username from user';
	$dbfile = $w->data() . '/library.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getUser\" 2>&1", $users, $returnValue);

	if ($returnValue != 0) {
		displayNotification('An error happened with user database');
		return;
	}

	foreach ($users as $user):
		$user = explode("	", $user);
	$username = $user[0];
	endforeach;

	touch($w->data() . "/update_library_in_progress");
	$w->write('InitPlaylist▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:star:" . $tcpport . ":" . uniqid() . "\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:user:$username:starred\"'");



	$server = IoServer::factory(
		new HttpServer(
			new WsServer(
				new MiniPlayer()
			)
		),
		$tcpport
	);
	// FIX THIS: server will exit when done
	// Did not find a way to set a timeout
	$server->run();
	return;
}

/**
 * unstarCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function unstarCurrentTrack($w) {
	$tcpport = getFreeTcpPort();
	$getUser = 'select username from user';
	$dbfile = $w->data() . '/library.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getUser\" 2>&1", $users, $returnValue);

	if ($returnValue != 0) {
		displayNotification('An error happened with user database');
		return;
	}

	foreach ($users as $user):
		$user = explode("	", $user);
	$username = $user[0];
	endforeach;

	touch($w->data() . "/update_library_in_progress");
	$w->write('InitPlaylist▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:unstar:" . $tcpport . ":" . uniqid() . "\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:user:$username:starred\"'");



	$server = IoServer::factory(
		new HttpServer(
			new WsServer(
				new MiniPlayer()
			)
		),
		$tcpport
	);
	// FIX THIS: server will exit when done
	// Did not find a way to set a timeout
	$server->run();
	return;
}

/**
 * refreshPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @string $playlist_uri
 * @return void
 */
function refreshPlaylist($w, $playlist_uri) {
	// update alfred playlist
	touch($w->data() . "/update_library_in_progress");
	$w->write('InitPlaylist▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

	$tcpport = getFreeTcpPort();
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:update_playlist:" . $playlist_uri . ":" . $tcpport . ":" . uniqid() . "\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");

	$server = IoServer::factory(
		new HttpServer(
			new WsServer(
				new MiniPlayer()
			)
		),
		$tcpport
	);
	// FIX THIS: server will exit when done
	// Did not find a way to set a timeout
	$server->run();
	return;

}

?>
