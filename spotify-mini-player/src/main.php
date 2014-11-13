<?php

// Turn off all error reporting
//error_reporting(0);

require './spotify-mini-player/src/functions.php';

//$begin_time = computeTime();

// Load and use David Ferguson's Workflows.php class
require_once './spotify-mini-player/src/workflows.php';
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
	if(!file_exists($w->data() . '/library_old.db') ) {
	
		if (startsWith($update_library_in_progress_words[0], 'Init')) {
			if
			($elapsed_time < 300) {
				$w->result(null, $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time) . ' : ' . floatToSquares(0), 'waiting for Spotify servers to return required data', './spotify-mini-player/images/update_in_progress.png', 'no', null, '');
			}
			else {
				$w->result(null, '', 'There is a problem, the initialization phase last more than 5 minutes', 'Follow the step below:', './spotify-mini-player/images/warning.png', 'no', null, '');
				$w->result(null, '', "Kill update library", "You can kill it by using spot_mini_kill_update command", 'icon.png', 'no', null, '');
			}
		}
		else {
			if ($update_library_in_progress_words[0] == 'Playlist List') {
				$type = 'playlists';
			} else if ($update_library_in_progress_words[0] == 'Artists') {
					$type = 'artists';
				}
			else {
				$type = 'tracks';
			}
	
			$w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time) . ' : '  . floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])), $update_library_in_progress_words[1] . '/' . $update_library_in_progress_words[2] . ' ' . $type . ' processed so far (if no progress, use spot_mini_kill_update command to stop it)', './spotify-mini-player/images/update_in_progress.png', 'no', null, '');
		}
	
		echo $w->toxml();
		return;
	}
}

//
// Read settings from DB
//
$getSettings = 'select all_playlists,is_spotifious_active,is_alfred_playlist_active,is_displaymorefrom_active,is_lyrics_active,max_results, alfred_playlist_uri,alfred_playlist_name,country_code,theme,last_check_update_time,oauth_client_id,oauth_client_secret,oauth_redirect_uri,oauth_access_token,oauth_expires,oauth_refresh_token,display_name,userid from settings';
$dbsettingsfile = $w->data() . '/settings.db';

try {
	$dbsettings = new PDO("sqlite:$dbsettingsfile", "", "", array(PDO::ATTR_PERSISTENT => true));
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
	handleDbIssuePdoXml('new', $dbsettings);
	$dbsettings=null;
	return;
}

try {
	$stmt = $dbsettings->prepare($getSettings);
	$settings = $stmt->execute();

} catch (PDOException $e) {
	if (file_exists($w->data() . '/settings.db')) {
		unlink($w->data() . '/settings.db');
	}
}

//
// Create settings.db with default values if needed
//
if (!file_exists($w->data() . '/settings.db')) {
	touch($w->data() . '/settings.db');
	try {
		$dbsettings = new PDO("sqlite:$dbsettingsfile", "", "", null);
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$dbsettings->exec("create table settings (all_playlists boolean, is_spotifious_active boolean, is_alfred_playlist_active boolean, is_displaymorefrom_active boolean, is_lyrics_active boolean, max_results int, alfred_playlist_uri text, alfred_playlist_name text, country_code text, theme text, last_check_update_time int, oauth_client_id text,oauth_client_secret text,oauth_redirect_uri text,oauth_access_token text,oauth_expires int,oauth_refresh_token text,display_name text,userid text)");
		$dbsettings->exec("insert into settings values (1,0,1,1,1,50,\"\",\"\",\"\",\"new\",0,\"\",\"\",\"http://localhost:15298/callback.php\",\"\",0,\"\",\"\",\"\")");

		$dbsettings->query("PRAGMA synchronous = OFF");
		$dbsettings->query("PRAGMA journal_mode = OFF");
		$dbsettings->query("PRAGMA temp_store = MEMORY");
		$dbsettings->query("PRAGMA count_changes = OFF");
		$dbsettings->query("PRAGMA PAGE_SIZE = 4096");
		$dbsettings->query("PRAGMA default_cache_size=700000");
		$dbsettings->query("PRAGMA cache_size=700000");
		$dbsettings->query("PRAGMA compile_options");

		$stmt = $dbsettings->prepare($getSettings);

		$w->result(null, '', 'Settings have been reset to default values', 'Please invoke again the workflow now to enjoy the Spotify Mini Player', './spotify-mini-player/images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;

	} catch (PDOException $e) {
		handleDbIssuePdoXml('new', $dbsettings);
		return;
	}
}

try {
	$setting = $stmt->fetch();
}
catch (PDOException $e) {
	handleDbIssuePdoXml('new', $dbsettings);
	return;
}
$all_playlists = $setting[0];
$is_spotifious_active = $setting[1];
$is_alfred_playlist_active = $setting[2];
$is_displaymorefrom_active = $setting[3];
$is_lyrics_active = $setting[4];
$max_results = $setting[5];
$alfred_playlist_uri = $setting[6];
$alfred_playlist_name = $setting[7];
$country_code = $setting[8];
$theme = $setting[9];
$last_check_update_time = $setting[10];
$oauth_client_id = $setting[11];
$oauth_client_secret = $setting[12];
$oauth_redirect_uri = $setting[13];
$oauth_access_token = $setting[14];
$oauth_expires = $setting[15];
$oauth_refresh_token = $setting[16];
$display_name = $setting[17];
$userid = $setting[18];

////
// OAUTH checks
// Check oauth config : Client ID and Client Secret
if ($oauth_client_id == '' && substr_count($query, '▹') == 0) {
	if (mb_strlen($query) == 0) {
		$w->result(null, '', 'Your Application Client ID is missing', 'Get it from your Spotify Application and copy/paste it here', './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, '');
	} else if(mb_strlen($query) != 32) {
		$w->result(null, '', 'The Application Client ID does not seem valid!', 'The length is not 32. Make sure to copy the Client ID from https://developer.spotify.com/my-applications', './spotify-mini-player/images/warning.png', 'no', null, '');
	} else {
		$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'Oauth_Client_ID▹' . rtrim(ltrim($query)) /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Application Client ID will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client ID", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
	}
	echo $w->toxml();
	return;
}

if ($oauth_client_secret == '' && substr_count($query, '▹') == 0) {
	if (mb_strlen($query) == 0) {
		$w->result(null, '', 'Your Application Client Secret is missing!', 'Get it from your Spotify Application and enter it here', './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, '');
	} else if(mb_strlen($query) != 32) {
		$w->result(null, '', 'The Application Client Secret does not seem valid!', 'The length is not 32. Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './spotify-mini-player/images/warning.png', 'no', null, '');
	} else if($query == $oauth_client_id) {
		$w->result(null, '', 'The Application Client Secret entered is the same as Application Client ID, this is wrong!', 'Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './spotify-mini-player/images/warning.png', 'no', null, '');
	} else {
		$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'Oauth_Client_SECRET▹' . rtrim(ltrim($query)) /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Application Client Secret will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client Secret", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
	}
	echo $w->toxml();
	return;
}

if ($oauth_access_token == '' && substr_count($query, '▹') == 0) {
	$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'Oauth_Login' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Authenticate to Spotify", array(
			"This will start the authentication process",
			'alt' => 'Not Available',
			'cmd' => 'Not Available',
			'shift' => 'Not Available',
			'fn' => 'Not Available',
			'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
	echo $w->toxml();
	return;
}


// check for library DB
$dbfile = "";

if ($update_in_progress == false && 
	file_exists($w->data() . '/library.db')) {
	$dbfile = $w->data() . '/library.db';
} else if (file_exists($w->data() . '/library_old.db')) {
	// update in progress use the old library
	if($update_in_progress == true) {
		$dbfile = $w->data() . '/library_old.db';
	} else {
		unlink($w->data() . '/library_old.db');
	}
}
if ($dbfile == "") {
	$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_library' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Create library', "when done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
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
	handleDbIssuePdoXml($theme, $db);
	return;
}
	
$check_results = checkForUpdate($w, $last_check_update_time, $dbsettings);
if
($check_results != null && is_array($check_results)) {
	$w->result(null, '', 'New version ' . $check_results[0] . ' is available', $check_results[2], './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
	$w->result(null, $check_results[1], 'Please install the new version in Downloads directory', $check_results[1], 'fileicon:'.$check_results[1], 'no', '', '', 'file' );

	echo $w->toxml();
	return;
}

// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding('UTF-8');
if (mb_strlen($query) < 3 ||
	((substr_count($query, '▹') == 1) && (strpos('Settings▹', $query) !== false))
) {
	if (substr_count($query, '▹') == 0) {
		$getCounters = 'select * from counters';
		try {
			$stmt = $db->prepare($getCounters);

			$counters = $stmt->execute();
			$counter = $stmt->fetch();

		} catch (PDOException $e) {
			handleDbIssuePdoXml($theme, $db);
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
				$w->result(null, $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time) . ' : ' . floatToSquares(0), 'waiting for Spotify servers to return required data', './spotify-mini-player/images/update_in_progress.png', 'no', null, '');
			} else {
				if ($update_library_in_progress_words[0] == 'Playlist List') {
					$type = 'playlists';
				} else if ($update_library_in_progress_words[0] == 'Artists') {
						$type = 'artists';
					}
				else {
					$type = 'tracks';
				}
				$w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time) . ' : '  . floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])), $update_library_in_progress_words[1] . '/' . $update_library_in_progress_words[2] . ' ' . $type . ' processed so far', './spotify-mini-player/images/update_in_progress.png', 'no', null, '');	
			}
		}
		if ($all_playlists == true) {
			$w->result(null, '', 'Search for music in "Your Music" and your ' . $nb_playlists . ' playlists', 'Begin typing at least 3 characters to start search' . ' (' . $all_tracks . ' tracks)', './spotify-mini-player/images/' . $theme . '/' . 'allplaylists.png', 'no', null, '');
		} else {
			$w->result(null, '', 'Search for music in "Your Music" only', 'Begin typing at least 3 characters to start search' . ' (' . $mymusic_tracks . ' tracks)', './spotify-mini-player/images/' . $theme . '/' . 'tracks.png', 'no', null, '');
		}
		if ($is_displaymorefrom_active == true) {
			// get info on current song
			$command_output = exec("./spotify-mini-player/src/track_info.sh 2>&1");

			if (substr_count($command_output, '▹') > 0) {
				$results = explode('▹', $command_output);
				$currentArtistArtwork = getArtistArtwork($w, $theme, $results[1], false);
				$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
				if ($is_alfred_playlist_active == true) {
					$arrayresult = array(
						$subtitle,
						'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify',
						'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify',
						'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ' . $alfred_playlist_name,
						'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ' . $alfred_playlist_name,
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

				if($results[3] == "playing") {
					$w->result(null, serialize(array($results[4] /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'pause' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), "🔈 " . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]), $arrayresult, ($results[3] == "playing") ? './spotify-mini-player/images/' . $theme . '/' . 'pause.png' : './spotify-mini-player/images/' . $theme . '/' . 'play.png', 'yes', null, '');

				} else {
					$w->result(null, serialize(array($results[4] /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'play' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), "🔈 " . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]), $arrayresult, ($results[3] == "playing") ? './spotify-mini-player/images/' . $theme . '/' . 'pause.png' : './spotify-mini-player/images/' . $theme . '/' . 'play.png', 'yes', null, '');
				}


				$getTracks = "select artist_name,artist_uri from tracks where playable=1 and artist_name=:artist_name limit " . 1;

				try {
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':artist_name', escapeQuery($results[1]));
					$tracks = $stmt->execute();

				} catch (PDOException $e) {
					handleDbIssuePdoXml($theme, $db);
					return;
				}

				// check if artist is in library
				$noresult=true;
				while ($track = $stmt->fetch()) {
					$artist_uri = $track[1];
					$noresult=false;
				}

				if
				($noresult == false) {
					$w->result(null, '', "🔈👤 " . ucfirst(escapeQuery($results[1])), "Browse this artist", $currentArtistArtwork, 'no', null, "Artist▹" . $artist_uri . '∙' . escapeQuery($results[1]) . "▹");
				}

				if
				($is_lyrics_active == true) {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'GET_LYRICS▹' . escapeQuery($results[1]) . '▹' . escapeQuery($results[0]) /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , '' /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "🔈🎤 Get Lyrics for track " . escapeQuery($results[0]),
						array(
							'This will fetch lyrics on lyrics.com',
							'alt' => 'Not Available',
							'cmd' => 'Not Available',
							'shift' => 'Not Available',
							'fn' => 'Not Available',
							'ctrl' => 'Not Available')
						, getTrackOrAlbumArtwork($w, $theme, $results[4], false), 'yes', null, '');
				}

				if ($all_playlists == true) {
					$getTracks = "select playlist_uri from tracks where playable=1 and uri=:uri limit " . $max_results;

					try {
						$stmt = $db->prepare($getTracks);
						$stmt->bindValue(':uri', $results[4]);
						$stmt->execute();

					} catch (PDOException $e) {
						handleDbIssuePdoXml($theme, $db);
						return;
					}

					while ($track = $stmt->fetch()) {

						$getPlaylists = "select * from playlists where uri=:uri";

						try {
							$stmt = $db->prepare($getPlaylists);
							$stmt->bindValue(':uri', $track[0]);

							$playlists = $stmt->execute();

						} catch (PDOException $e) {
							handleDbIssuePdoXml($theme, $db);
							return;
						}

						while ($playlist = $stmt->fetch()) {

							if (checkIfResultAlreadyThere($w->results(), "🔈🎵 " . "In playlist " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)") == false) {
								$w->result(null, '', "🔈🎵 " . "In playlist " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
							}
						}
					}
				}
			}
		}

		if ($is_alfred_playlist_active == true) {
			if
			($alfred_playlist_name != "") {
				$title = '♫ Alfred Playlist ● ' . $alfred_playlist_name;
				$w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './spotify-mini-player/images/' . $theme . '/' . 'alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
			}
			else {
				$title = '♫ Alfred Playlist ● not set';
				$w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './spotify-mini-player/images/' . $theme . '/' . 'alfred_playlist.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
			}

		}
		$w->result(null, '', 'Playlists', 'Browse by playlist' . ' (' . $nb_playlists . ' playlists)', './spotify-mini-player/images/' . $theme . '/' . 'playlists.png', 'no', null, 'Playlist▹');
		$w->result(null, '', 'Your Music', 'Browse Your Music' . ' (' . $mymusic_tracks . ' tracks ● ' .  $mymusic_albums . '  albums ● ' . $mymusic_artists . ' artists)', './spotify-mini-player/images/' . $theme . '/' . 'tracks.png', 'no', null, 'YourMusic▹');
		if ($all_playlists == true) {
			$w->result(null, '', 'Artists', 'Browse by artist' . ' (' . $all_artists . ' artists)', './spotify-mini-player/images/' . $theme . '/' . 'artists.png', 'no', null, 'Artist▹');
			$w->result(null, '', 'Albums', 'Browse by album' . ' (' . $all_albums . ' albums)', './spotify-mini-player/images/' . $theme . '/' . 'albums.png', 'no', null, 'Album▹');
		} else {
			$w->result(null, '', 'Artists in "Your Music"', 'Browse by artist' . ' (' . $mymusic_artists . ' artists)', './spotify-mini-player/images/' . $theme . '/' . 'artists.png', 'no', null, 'Artist▹');
			$w->result(null, '', 'Albums in "Your Music"', 'Browse by album' . ' (' . $mymusic_albums . ' albums)', './spotify-mini-player/images/' . $theme . '/' . 'albums.png', 'no', null, 'Album▹');
		}

		$w->result(null, '', 'Featured Playlists', 'Browse the current featured playlists', './spotify-mini-player/images/' . $theme . '/' . 'star.png', 'no', null, 'FeaturedPlaylist▹');

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
		if ($all_playlists == true) {
			$w->result(null, '', 'Settings', 'Search scope=<all>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Controlling <' . $alfred_playlist_state . '>', './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, 'Settings▹');
		} else {
			$w->result(null, '', 'Settings', 'Search scope=<only ★>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Controlling <' . $alfred_playlist_state . '>', './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, 'Settings▹');
		}
	}
	//
	// Settings
	//
	elseif (substr_count($query, '▹') == 1) {
		if ($all_playlists == true) {
			// argument is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|query|other_settings|other_action|alfred_playlist_uri|artist_name
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'disable_all_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Change Search Scope', array(
					'Select to change to "Your Music" only',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'search.png', 'yes', null, '');

		} else {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'enable_all_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Change Search Scope', array(
					'Select to change to "Your Music" and all Playlists',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'search.png', 'yes', null, '');
		}

		if ($update_in_progress == false) {
		$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_library' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Re-Create Library', "When done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
		}
		$w->result(null, '', "Configure Max Number of Results", "Number of results displayed. (it doesn't apply to your playlist list)", './spotify-mini-player/images/' . $theme . '/' . 'numbers.png', 'no', null, 'Settings▹MaxResults▹');
		$w->result(null, '', "Configure the Theme", "Current available colors for icons: green or black, or new design", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, 'Settings▹Theme▹');

		if ($is_spotifious_active == true) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'disable_spotifiuous' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Disable Spotifious", array(
					"Do not display Spotifious in default results",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'uncheck.png', 'yes', null, '');
		} else {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'enable_spotifiuous' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Enable Spotifious", array(
					"Display Spotifious in default results",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'check.png', 'yes', null, '');
		}
		if ($is_alfred_playlist_active == true) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'disable_alfred_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Enable Your Music", array(
					"You will control Your Music (if disabled, you control Alfred Playlist)",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
		} else {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'enable_alfred_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Enable Alfred Playlist", array(
					"You will control the Alfred Playlist (if disabled, you control Your Music)",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
		}
		if ($is_lyrics_active == true) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'disable_lyrics' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Disable Get Lyrics", array(
					"Do not display Get Lyrics",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'uncheck.png', 'yes', null, '');
		} else {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'enable_lyrics' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Enable Get Lyrics", array(
					"Display Get Lyrics",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'check.png', 'yes', null, '');
		}
		if ($is_displaymorefrom_active == true) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'disable_displaymorefrom' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Disable \"Now Playing\"", array(
					"Disable display of various options based on current track",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'uncheck.png', 'yes', null, '');
		} else {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'enable_displaymorefrom' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Enable \"Now Playing\"", array(
					"Enable display of various options based on current track",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'check.png', 'yes', null, '');
		}

		$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'check_for_update' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Check for workflow update', array(
				"Note this is automatically done otherwise once per day",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'), './spotify-mini-player/images/' . $theme . '/' . 'check_update.png', 'yes', null, '');
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
			$w->result(null, '', 'Playlists', 'Browse by playlist', './spotify-mini-player/images/' . $theme . '/' . 'playlists.png', 'no', null, 'Playlist▹');
		} else if (strpos(strtolower('albums'), strtolower($query)) !== false) {
				$w->result(null, '', 'Albums', 'Browse by album', './spotify-mini-player/images/' . $theme . '/' . 'albums.png', 'no', null, 'Album▹');
			} else if (strpos(strtolower('artists'), strtolower($query)) !== false) {
				$w->result(null, '', 'Artists', 'Browse by artist', './spotify-mini-player/images/' . $theme . '/' . 'artists.png', 'no', null, 'Artist▹');
			} else if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
				$w->result(null, '', 'Alfred Playlist (currently set to <' . $alfred_playlist_name . '>)' , 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './spotify-mini-player/images/' . $theme . '/' . 'alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
			} else if (strpos(strtolower('settings'), strtolower($query)) !== false) {
				$w->result(null, '', 'Settings', 'Go to settings', './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, 'Settings▹');
			} else if (strpos(strtolower('featured'), strtolower($query)) !== false) {
				$w->result(null, '', 'Featured Playlist', 'Browse the current featured playlists', './spotify-mini-player/images/' . $theme . '/' . 'star.png', 'no', null, 'FeaturedPlaylist▹');
			} else if (strpos(strtolower('yourmusic'), strtolower($query)) !== false) {
				$w->result(null, '', 'Your Music', 'Browse Your Music', './spotify-mini-player/images/' . $theme . '/' . 'tracks.png', 'no', null, 'YourMusic▹');
			}


		//
		// Search commands for fast access
		//
		if (strpos(strtolower('next'), strtolower($query)) !== false) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'next' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Next Track', 'Play the next track in Spotify', 'icon.png', 'yes', '');
		} else if (strpos(strtolower('previous'), strtolower($query)) !== false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'previous' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Previous Track', 'Play the previous track in Spotify', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('play'), strtolower($query)) !== false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'play' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Play', 'Play the current Spotify track', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('pause'), strtolower($query)) !== false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'pause' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Pause', 'Pause the current Spotify track', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('current'), strtolower($query)) !== false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'current' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Get Current Track info', 'Get current track information', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('random'), strtolower($query)) !== false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'random' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Random Track', 'Play random track', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('add'), strtolower($query)) !== false) {
				if ($is_alfred_playlist_active == true) {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'add_current_track' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', 'icon.png', 'yes', '');
				} else {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'add_current_track' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Add current track to Your Music', 'Current track will be added to Your Music', 'icon.png', 'yes', '');
				}
			} else if (strpos(strtolower('mute'), strtolower($query)) !== false) {
				$osascript_command = 'if sound volume is less than or equal to 0 then
										set sound volume to 100
									else
										set sound volume to 0
									end if';
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , $osascript_command /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Mute Spotify Volume', 'Mute Spotify', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('volmid'), strtolower($query)) !== false) {
				$osascript_command = 'set sound volume to 50';
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , $osascript_command /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Set Spotify Volume to 50%', 'Set the Spotify Volume to 50%', 'icon.png', 'yes', '');
			} else if (strpos(strtolower('volmax'), strtolower($query)) !== false) {
				$osascript_command = 'set sound volume to 100';
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , $osascript_command /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Set Spotify Volume to Maximum', 'Set the Spotify Volume to Maximum', 'icon.png', 'yes', '');
			}

		//
		// Search in Playlists
		//
		$getPlaylists = "select * from playlists where name like :query";

		try {
			$stmt = $db->prepare($getPlaylists);
			$stmt->bindValue(':query', '%' . $query . '%');
			$playlists = $stmt->execute();

		} catch (PDOException $e) {
			handleDbIssuePdoXml($theme, $db);
			return;
		}

		while ($playlist = $stmt->fetch()) {

			$w->result(null, '', "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3] . " (" . $playlist[4] . ")", $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
		}

		//
		// Search artists
		//
		if ($all_playlists == false) {
			$getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where playable=1 and mymusic=1 and artist_name like :artist_name limit " . $max_results;
		} else {
			$getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where playable=1 and artist_name like :artist_name limit " . $max_results;
		}

		try {
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':artist_name', '%' . $query . '%');

			$tracks = $stmt->execute();

		} catch (PDOException $e) {
			handleDbIssuePdoXml($theme, $db);
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
			$getTracks = "select * from tracks where playable=1 and mymusic=1 and (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;
		} else {
			$getTracks = "select * from tracks where playable=1 and (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;
		}

		try {
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $query . '%');

			$tracks = $stmt->execute();

		} catch (PDOException $e) {
			handleDbIssuePdoXml($theme, $db);
			return;
		}

		$noresult=true;
		while ($track = $stmt->fetch()) {

			if
			($noresult==true) {
				$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
				$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
			}
			$noresult=false;
			$subtitle = $track[6];

			if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

				$playlistsfortrack = getPlaylistsForTrack($db, $theme, $track[2]);

				if ($is_alfred_playlist_active == true) {
					$arrayresult = array(
						beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name,
						'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name,
						'ctrl' => 'Search artist ' . $track[7] . ' online')
					;
				} else {
					$arrayresult = array(
						beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to Your Music',
						'shift' => 'Add album ' . $track[6] . ' to Your Music',
						'ctrl' => 'Search artist ' . $track[7] . ' online')
					;
				}

				$w->result(null, serialize(array($track[2] /*track_uri*/ , $track[3] /* album_uri */ , $track[4] /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', array('copy' => ucfirst($track[7]) . " ● " . $track[5], 'largetype' => ucfirst($track[7]) . " ● " . $track[5]), '');

			}
		}

		if
		($noresult) {
			$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
		}

		$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $query . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $query . " in Spotify", array(
				'This will start a new search in Spotify',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');

		if ($is_spotifious_active == true) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , $query /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $query . " with Spotifious", array(
					'Spotifious workflow must be installed and script filter set with <spotifious>',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), './spotify-mini-player/images/spotifious.png', 'yes', null, '');
		}
	} ////////////
	//
	// FIRST DELIMITER: Artist▹, Album▹, Playlist▹, Alfred Playlist▹, Settings▹, FeaturedPlaylist▹, YourMusic▹ or Online▹artist uri
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
					$getPlaylists = "select * from playlists";
					$stmt = $db->prepare($getPlaylists);

					if ($update_in_progress == false) {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_playlist_list' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update your playlists (new, modified or deleted)", "when done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
				}
				}
				else {
					$getPlaylists = "select * from playlists where (name like :query or author like :query)";
					$stmt = $db->prepare($getPlaylists);
					$stmt->bindValue(':query', '%' . $theplaylist . '%');
				}

				$playlists = $stmt->execute();
			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			$noresult=true;
			while ($playlist = $stmt->fetch()) {

				$noresult=false;

				$w->result(null, '', "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");

			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}

		} // search by Playlist end
		elseif ($kind == "Alfred Playlist") {
			//
			// Alfred Playlist
			//
			$playlist = $words[1];

			$r = explode(':', $alfred_playlist_uri);

			$w->result(null, '', "Browse your Alfred playlist (" . $alfred_playlist_name . " by " . $r[2] . ")" , "You can change the playlist by selecting Change your Alfred playlist below", getPlaylistArtwork($w, $theme , $alfred_playlist_uri, false), 'no', null, 'Playlist▹' . $alfred_playlist_uri . '▹');

			$w->result(null, '', "Change your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');

			if
			(strtolower($r[3]) != strtolower('Starred')) {
				$w->result(null, '', "Clear your Alfred Playlist", "This will remove all the tracks in your current Alfred Playlist", './spotify-mini-player/images/' . $theme . '/' . 'uncheck.png', 'no', null, 'Alfred Playlist▹Confirm Clear Alfred Playlist▹');
			}
			if ($update_in_progress == false) {
			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , $alfred_playlist_uri /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $alfred_playlist_name /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update your Alfred Playlist", "when done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
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
						$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 and mymusic=1 group by artist_name" . " limit " . $max_results;
					} else {
						$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 group by artist_name" . " limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
				}
				else {
					if ($all_playlists == false) {
						$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 and mymusic=1 and artist_name like :query limit " . $max_results;
					} else {
						$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 and artist_name like :query limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':query', '%' . $artist . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			// display all artists
			$noresult=true;
			while ($track = $stmt->fetch()) {

				$noresult=false;

				if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
					$w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[1], 'no', null, "Artist▹" . $track[2] . '∙'. $track[0] . "▹");
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}

		} // search by Artist end
		elseif ($kind == "Album") {
			//
			// Search albums
			//
			$album = $words[1];
			try {
				if (mb_strlen($album) < 3) {
					if ($all_playlists == false) {
						$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 and mymusic=1 group by album_name" . " limit " . $max_results;
					} else {
						$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 group by album_name" . " limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
				}
				else {
					if ($all_playlists == false) {
						$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 and mymusic=1 and album_name like :query limit " . $max_results;
					} else {
						$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 and album_name like :query limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':query', '%' . $album . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			// display all albums
			$noresult=true;
			while ($track = $stmt->fetch()) {

				$noresult=false;

				if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0])) == false) {
					$w->result(null, '', ucfirst($track[0]), "by " . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . "▹");
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}
		} // search by Album end
		elseif ($kind == "FeaturedPlaylist") {

			if($country_code == 'FR') {
				$country_flag = '🇫🇷';
				$country_name = 'France';
			} elseif($country_code == 'IT') {
				$country_flag = '🇮🇹';
				$country_name = 'Italy';
			} else {
				$country_flag = $country_code;
				$country_name = $country_code;
			}
			$w->result(null, '', $country_flag, 'Browse the current featured playlists in ' .  $country_name, './spotify-mini-player/images/' . $theme . '/' . 'star.png', 'no', null, 'FeaturedPlaylist▹'.$country_code.'▹');

			if ($country_code != 'US') {
				$w->result(null, '', '🇺🇸', 'Browse the current featured playlists in US', './spotify-mini-player/images/' . $theme . '/' . 'star.png', 'no', null, 'FeaturedPlaylist▹US▹');
			}

			if ($country_code != 'GB') {
				$w->result(null, '', '🇬🇧', 'Browse the current featured playlists in UK', './spotify-mini-player/images/' . $theme . '/' . 'star.png', 'no', null, 'FeaturedPlaylist▹GB▹');
			}

		} // Featured Playlist end
		elseif ($kind == "YourMusic") {
			if ($update_in_progress == false) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_your_music' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update Your Music ", "when done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
			}
			$w->result(null, '', 'Tracks' , 'Browse tracks in Your Music', './spotify-mini-player/images/' . $theme . '/' . 'tracks.png', 'no', null, 'YourMusic▹Tracks▹');
			$w->result(null, '', 'Albums', 'Browse albums in Your Music', './spotify-mini-player/images/' . $theme . '/' . 'albums.png', 'no', null, 'YourMusic▹Albums▹');
			$w->result(null, '', 'Artists', 'Browse artists in Your Music', './spotify-mini-player/images/' . $theme . '/' . 'artists.png', 'no', null, 'YourMusic▹Artists▹');

		} // Featured YourMusic end
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

				$artist_artwork_path = getArtistArtwork($w, $theme, $artist_name, false);
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'playartist' /* other_action */ , '' /* alfred_playlist_uri */ , $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, $artist_artwork_path /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "👤 " . $artist_name, '▶️ Play artist', $artist_artwork_path, 'yes', null, '');

				$w->result('display-biography', serialize(array('' /*track_uri*/ , '' /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'display_biography_online' /* other_action */ , '' /* alfred_playlist_uri */ , $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Display biography', 'This will display the artist biography', './spotify-mini-player/images/' . $theme . '/' . 'biography.png', 'yes', null, '');

				$w->result(null, '', 'Related Artists', 'Browse related artists', './spotify-mini-player/images/' . $theme . '/' . 'related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name);

				$json = doWebApiRequest($w, "https://api.spotify.com/v1/artists/" . trim($tmp_uri[2]) . "/albums");

				$album_id_list="";
				$first=true;
				foreach ($json->items as $album) {

					if (count($album->available_markets) == 0 || in_array($country_code, $album->available_markets) !== false) {

						if
						($first==true) {
							$album_id_list = $album_id_list . $album->id;
							$first=false;
						} else {
							$album_id_list = $album_id_list . "," . $album->id;
						}
					}
				}

				$json2 = doWebApiRequest($w, "https://api.spotify.com/v1/albums?ids=" . $album_id_list);
				foreach ($json2->albums as $album) {

					if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name)) == false) {

						$genre = (count($album->genres) > 0) ? ' ● Genre: ' . implode('|', $album->genres) : '';
						$w->result(null, '', ucfirst($album->name), $album->album_type . " by " . $artist_name . ' ● Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w, $theme, $album->uri, false), 'no', null, "Online▹" . $artist_uri . "@" . $artist_name . "@" . $album->uri . "@" . $album->name);
					}
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

				$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $album_uri, false);
				$w->result(null, serialize(array('' /*track_uri*/ , $album_uri /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'playalbum' /* other_action */ , '' /* alfred_playlist_uri */ , '' /* artist_name */, '' /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "💿 " . escapeQuery($album_name), '▶️ Play album', $album_artwork_path, 'yes', null, '');

				$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
				$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
				foreach ($json->items as $track) {

					if (count($track->available_markets) == 0 || in_array($country_code, $track->available_markets) !== false) {
						$track_artwork = getTrackOrAlbumArtwork($w, $theme, $track->uri, false);
						if ($is_alfred_playlist_active == true) {
							$arrayresult = array(
								beautifyTime($track->duration_ms/1000) . " ● " . $album_name,
								'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
								'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
								'fn' => 'Add track ' . escapeQuery($track->name) . ' to ' . $alfred_playlist_name,
								'shift' => 'Add album ' . escapeQuery($album_name) . ' to ' . $alfred_playlist_name,
								'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online');
						} else {
							$arrayresult = array(
								beautifyTime($track->duration_ms/1000) . " ● " . escapeQuery($album_name),
								'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
								'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
								'fn' => 'Add track ' . escapeQuery($track->name) . ' to Your Music',
								'shift' => 'Add album ' . escapeQuery(album_name) . ' to Your Music',
								'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online');
						}
						$w->result(null, serialize(array($track->uri /*track_uri*/ , $album_uri /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $artist_name  /* artist_name */, $track->name /* track_name */, $album_name /* album_name */, $track_artwork /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), escapeQuery(ucfirst($artist_name)) . " ● " . escapeQuery($track->name), $arrayresult, $track_artwork, 'yes', null, '');
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

					$w->result(null, '', "👤 " . ucfirst($related->name), '☁︎ Query all albums/tracks from this artist online..', getArtistArtwork($w, $theme, $related->name, false), 'no', null, "Online▹" . $related->uri . "@" . $related->name);
				}

			}
		}
	} ////////////
	//
	// SECOND DELIMITER: Artist▹the_artist▹tracks , Album▹the_album▹tracks,
	//  Playlist▹the_playlist▹tracks,Settings▹Theme▹color or Settings▹MaxResults▹max_numbers,
	//  Alfred Playlist▹Set Alfred Playlist▹alfred_playlist,
	//  Alfred Playlist▹Clear Alfred Playlist▹yes or YourMusic▹Tracks▹
	//  YourMusic▹Albums▹ or YourMusic▹Artists▹
	//
	////////////
	elseif (substr_count($query, '▹') == 2) {
		//
		// Get all songs for selected artist
		//

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

			$getArtists = "select artist_uri,artist_artwork_path from tracks where artist_uri=:artist_uri";

			try {
				$stmt = $db->prepare($getArtists);
				$stmt->bindValue(':artist_uri', $artist_uri);

				$artists = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			$theartist = $stmt->fetch();

			try {
				if (mb_strlen($track) < 3) {

					if
					($theartist != false) {
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'playartist' /* other_action */ , '' /* alfred_playlist_uri */ , $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, $theartist[1] /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "👤 " . $artist_name, '▶️ Play artist', $theartist[1], 'yes', null, '');
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'morefromthisartist' /* other_action */ , '' /* alfred_playlist_uri */ , $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "👤 " . $artist_name, '☁︎ Query all albums/tracks from this artist online..', $theartist[1], 'yes', null, '');


				        $w->result('display-biography', serialize(array('' /*track_uri*/ , '' /* album_uri */ , $artist_uri /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'display_biography' /* other_action */ , '' /* alfred_playlist_uri */ , $artist_name  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), 'Display biography', 'This will display the artist biography', './spotify-mini-player/images/' . $theme . '/' . 'biography.png', 'yes', null, '');


						$w->result(null, '', 'Related Artists', 'Browse related artists', './spotify-mini-player/images/' . $theme . '/' . 'related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name);
					}

					if ($all_playlists == false) {
						$getTracks = "select * from tracks where playable=1 and mymusic=1 and artist_uri=:artist_uri limit " . $max_results;
					} else {
						$getTracks = "select * from tracks where playable=1 and artist_uri=:artist_uri limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':artist_uri', $artist_uri);

				}
				else {
					if ($all_playlists == false) {
						$getTracks = "select * from tracks where playable=1 and mymusic=1 and (artist_uri=:artist_uri and track_name like :track)" . " limit " . $max_results;
					} else {
						$getTracks = "select * from tracks where playable=1 and artist_uri=:artist_uri and track_name like :track limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':artist_uri', $artist_uri);
					$stmt->bindValue(':track', '%' . $track . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			$noresult=true;
			while ($track = $stmt->fetch()) {

				if
				($noresult==true) {
					$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
					$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
					$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
				}
				$noresult=false;
				$subtitle = $track[6];

				if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

					$getPlaylistsForTrack = "select playlist_name from tracks where uri=:uri";
					try {
						$stmt2 = $db->prepare($getPlaylistsForTrack);
						$stmt2->bindValue(':uri', '' . $track[2] . '');

						$stmt2->execute();

						$playlistsfortrack = "";

						$noresult2=true;
						while ($playlist = $stmt2->fetch()) {
							if
							($noresult2==true) {
								$playlistsfortrack = $playlistsfortrack . " ● In playlists: " . $playlist[0];
							} else {
								$playlistsfortrack =  $playlistsfortrack . " ○ " . $playlist[0];
							}
							$noresult2=false;
						}


					} catch (PDOException $e) {
						handleDbIssuePdoXml($theme, $db);
						return;
					}

					if ($is_alfred_playlist_active == true) {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name,
							'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name,
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					} else {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to Your Music',
							'shift' => 'Add album ' . $track[6] . ' to Your Music',
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					}
					$w->result(null, serialize(array($track[2] /*track_uri*/ , $track[3] /* album_uri */ , $track[4] /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}

			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $artist_name . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $artist_name . " in Spotify", array(
					'This will start a new search in Spotify',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');

			if
			($theartist != false) {
				if ($is_spotifious_active == true) {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , $theartist[4] . " ▹ " . $artist_name . " ►" /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $artist_name . " with Spotifious", array(
							'Spotifious workflow must be installed and script filter set with <spotifious>',
							'alt' => 'Not Available',
							'cmd' => 'Not Available',
							'shift' => 'Not Available',
							'fn' => 'Not Available',
							'ctrl' => 'Not Available'), './spotify-mini-player/images/spotifious.png', 'yes', null, '');
				}
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
					if ($all_playlists == false) {
						$getTracks = "select * from tracks where playable=1 and mymusic=1 and album_uri=:album_uri limit " . $max_results;
					} else {
						$getTracks = "select * from tracks where playable=1 and album_uri=:album_uri limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':album_uri', $album_uri);
				}
				else {
					if ($all_playlists == false) {
						$getTracks = "select * from tracks where playable=1 and mymusic=1 and (album_uri=:album_uri and track_name like :track limit " . $max_results;
					} else {
						$getTracks = "select * from tracks where playable=1 and album_uri=:album_uri and track_name like :track limit " . $max_results;
					}
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':album_uri', $album_uri);
					$stmt->bindValue(':track', '%' . $track . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $album_uri, false);
			$w->result(null, serialize(array('' /*track_uri*/ , $album_uri /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'playalbum' /* other_action */ , '' /* alfred_playlist_uri */ , '' /* artist_name */, '' /* track_name */, $album_name /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, $album_artwork_path /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "💿 " . $album_name, '▶️ Play album', $album_artwork_path, 'yes', null, '');

			$noresult=true;
			while ($track = $stmt->fetch()) {

				if
				($noresult==true) {
					$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
					$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
					$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
				}
				$noresult=false;
				$subtitle = $track[6];

				if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

					$getPlaylistsForTrack = "select playlist_name from tracks where uri=:uri";
					try {
						$stmt2 = $db->prepare($getPlaylistsForTrack);
						$stmt2->bindValue(':uri', '' . $track[2] . '');

						$stmt2->execute();

						$playlistsfortrack = "";

						$noresult2=true;
						while ($playlist = $stmt2->fetch()) {
							if
							($noresult2==true) {
								$playlistsfortrack = $playlistsfortrack . " ● In playlists: " . $playlist[0];
							} else {
								$playlistsfortrack =  $playlistsfortrack . " ○ " . $playlist[0];
							}
							$noresult2=false;
						}


					} catch (PDOException $e) {
						handleDbIssuePdoXml($theme, $db);
						return;
					}

					if ($is_alfred_playlist_active == true) {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name,
							'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name,
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					} else {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to Your Music',
							'shift' => 'Add album ' . $track[6] . ' to Your Music',
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					}
					$w->result(null, serialize(array($track[2] /*track_uri*/ , $track[3] /* album_uri */ , $track[4] /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');

				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $album_name . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $album_name . " in Spotify", array(
						'This will start a new search in Spotify',
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');
			}
			else {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $album_name . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $album_name . " in Spotify", array(
						'This will start a new search in Spotify',
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');

				if ($is_spotifious_active == true) {
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , $album_uri . " ▹ " . $album_name . " ►"/* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $album_name . " with Spotifious", array(
							'Spotifious workflow must be installed and script filter set with <spotifious>',
							'alt' => 'Not Available',
							'cmd' => 'Not Available',
							'shift' => 'Not Available',
							'fn' => 'Not Available',
							'ctrl' => 'Not Available'), './spotify-mini-player/images/spotifious.png', 'yes', null, '');
				}
			}

		} // end of tracks by album
		elseif ($kind == "Playlist") {
			//
			// display tracks for selected playlist
			//
			$theplaylisturi = $words[1];
			$thetrack = $words[2];
			$getPlaylists = "select * from playlists where uri=:uri";

			try {
				$stmt = $db->prepare($getPlaylists);
				$stmt->bindValue(':uri', $theplaylisturi);

				$playlists = $stmt->execute();

				while ($playlist = $stmt->fetch()) {
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
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , $playlist[0] /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist[1] /* playlist_name */, $playlist[5] /* playlist_artwork_path */, $alfred_playlist_name /* alfred_playlist_name */)), "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks), by " . $playlist[3], $arrayresult, $playlist[5], 'yes', null, '');
						if ($update_in_progress == false) {
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , $playlist[0] /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_playlist' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist[1] /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update playlist " . ucfirst($playlist[1]) . " by " . $playlist[3], "when done you'll receive a notification. you can check progress by invoking the workflow again", './spotify-mini-player/images/' . $theme . '/' . 'update.png', 'yes', null, '');
						}

						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "' . $playlist[0] . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Open playlist " . $playlist[1] . " in Spotify", "This will open the playlist in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', null, '');

						$getTracks = "select * from tracks where playable=1 and playlist_uri=:theplaylisturi limit " . $max_results;
						$stmt = $db->prepare($getTracks);
						$stmt->bindValue(':theplaylisturi', $theplaylisturi);
					}
					else {
						$getTracks = "select * from tracks where playable=1 and playlist_uri=:theplaylisturi and (artist_name like :track or album_name like :track or track_name like :track)" . " limit " . $max_results;
						$stmt = $db->prepare($getTracks);
						$stmt->bindValue(':theplaylisturi', $theplaylisturi);
						$stmt->bindValue(':track', '%' . $thetrack . '%');
					}

					$tracks = $stmt->execute();

					$noresult=true;
					while ($track = $stmt->fetch()) {

						if
						($noresult==true) {
							$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
							$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
							$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
						}
						$noresult=false;
						$subtitle = $track[6];

						if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

							$getPlaylistsForTrack = "select playlist_name from tracks where uri=:uri";
							try {
								$stmt2 = $db->prepare($getPlaylistsForTrack);
								$stmt2->bindValue(':uri', '' . $track[2] . '');

								$stmt2->execute();

								$playlistsfortrack = "";

								$noresult2=true;
								while ($playlist2 = $stmt2->fetch()) {
									if
									($noresult2==true) {
										$playlistsfortrack = $playlistsfortrack . " ● In playlists: " . $playlist2[0];
									} else {
										$playlistsfortrack =  $playlistsfortrack . " ○ " . $playlist2[0];
									}
									$noresult2=false;
								}


							} catch (PDOException $e) {
								handleDbIssuePdoXml($theme, $db);
								return;
							}
							if ($is_alfred_playlist_active == true) {
								$arrayresult = array(
									beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
									'alt' => 'Play album ' . $track[6] . ' in Spotify',
									'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
									'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name,
									'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name,
									'ctrl' => 'Search artist ' . $track[7] . ' online');
							} else {
								$arrayresult = array(
									beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
									'alt' => 'Play album ' . $track[6] . ' in Spotify',
									'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
									'fn' => 'Add track ' . $track[5] . ' to Your Music',
									'shift' => 'Add album ' . $track[6] . ' to Your Music',
									'ctrl' => 'Search artist ' . $track[7] . ' online');
							}
							$w->result(null, serialize(array($track[2] /*track_uri*/ , $track[3] /* album_uri */ , $track[4] /* artist_uri */ , $theplaylisturi /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');

						}
					}

					if
					($noresult) {
						$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');

					}

					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $playlist[1] . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $playlist[1] . " in Spotify", array(
							'This will start a new search in Spotify',
							'alt' => 'Not Available',
							'cmd' => 'Not Available',
							'shift' => 'Not Available',
							'fn' => 'Not Available',
							'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');

					if ($is_spotifious_active == true) {
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , $playlist[1] /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $playlist[1] . " with Spotifious", array(
								'Spotifious workflow must be installed and script filter set with <spotifious>',
								'alt' => 'Not Available',
								'cmd' => 'Not Available',
								'shift' => 'Not Available',
								'fn' => 'Not Available',
								'ctrl' => 'Not Available'), './spotify-mini-player/images/spotifious.png', 'yes', null, '');
					}

				}
			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}
		} // end of tracks by Playlist
		elseif ($kind == "YourMusic" && $words[1] == "Tracks") {
			//
			// display tracks for Your Music
			//
			$thetrack = $words[2];

			if (mb_strlen($thetrack) < 3) {
				$getTracks = "select * from tracks where playable=1 and mymusic=:mymusic limit " . $max_results;
				$stmt = $db->prepare($getTracks);
				$stmt->bindValue(':mymusic', 1);
			}
			else {
				$getTracks = "select * from tracks where playable=1 and mymusic=:mymusic and (artist_name like :track or album_name like :track or track_name like :track)" . " limit " . $max_results;
				$stmt = $db->prepare($getTracks);
				$stmt->bindValue(':mymusic', 1);
				$stmt->bindValue(':track', '%' . $thetrack . '%');
			}

			$tracks = $stmt->execute();

			$noresult=true;
			while ($track = $stmt->fetch()) {

				if
				($noresult==true) {
					$subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
					$subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
					$w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
				}
				$noresult=false;
				$subtitle = $track[6];

				if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

					$getPlaylistsForTrack = "select playlist_name from tracks where uri=:uri";
					try {
						$stmt2 = $db->prepare($getPlaylistsForTrack);
						$stmt2->bindValue(':uri', '' . $track[2] . '');

						$stmt2->execute();

						$playlistsfortrack = "";

						$noresult2=true;
						while ($playlist2 = $stmt2->fetch()) {
							if
							($noresult2==true) {
								$playlistsfortrack = $playlistsfortrack . " ● In playlists: " . $playlist2[0];
							} else {
								$playlistsfortrack =  $playlistsfortrack . " ○ " . $playlist2[0];
							}
							$noresult2=false;
						}
					} catch (PDOException $e) {
						handleDbIssuePdoXml($theme, $db);
						return;
					}
					if ($is_alfred_playlist_active == true) {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to ' . $alfred_playlist_name,
							'shift' => 'Add album ' . $track[6] . ' to ' . $alfred_playlist_name,
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					} else {
						$arrayresult = array(
							beautifyTime($track[16]/1000) . " ● " . $subtitle .  $playlistsfortrack,
							'alt' => 'Play album ' . $track[6] . ' in Spotify',
							'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
							'fn' => 'Add track ' . $track[5] . ' to Your Music',
							'shift' => 'Add album ' . $track[6] . ' to Your Music',
							'ctrl' => 'Search artist ' . $track[7] . ' online');
					}
					$w->result(null, serialize(array($track[2] /*track_uri*/ , $track[3] /* album_uri */ , $track[4] /* artist_uri */ , $theplaylisturi /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , $track[7]  /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, $alfred_playlist_name /* $alfred_playlist_name */)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult, $track[9], 'yes', null, '');
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');

			}

			$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , 'activate (open location "spotify:search:' . $playlist[1] . '")' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $playlist[1] . " in Spotify", array(
					'This will start a new search in Spotify',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'), 'fileicon:/Applications/Spotify.app', 'yes', null, '');

			if ($is_spotifious_active == true) {
				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , $playlist[1] /* query */ , '' /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Search for " . $playlist[1] . " with Spotifious", array(
						'Spotifious workflow must be installed and script filter set with <spotifious>',
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'), './spotify-mini-player/images/spotifious.png', 'yes', null, '');
			}
		} // end of YourMusic▹Tracks▹
		elseif ($kind == "YourMusic" && $words[1] == "Albums") {

			//
			// Search albums
			//
			$album = $words[2];
			try {
				if (mb_strlen($album) < 3) {
					$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 and mymusic=1 group by album_name" . " limit " . $max_results;
					$stmt = $db->prepare($getTracks);
				}
				else {
					$getTracks = "select album_name,album_artwork_path,artist_name,album_uri from tracks where playable=1 and mymusic=1 and album_name like :query limit " . $max_results;
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':query', '%' . $album . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			// display all albums
			$noresult=true;
			while ($track = $stmt->fetch()) {

				$noresult=false;

				if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0])) == false) {
					$w->result(null, '', ucfirst($track[0]), "by " . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . "▹");
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}
		} // end of YourMusic▹Albums▹
		elseif ($kind == "YourMusic" && $words[1] == "Artists") {
			//
			// Search artists
			//
			$artist = $words[2];

			try {
				if (mb_strlen($artist) < 3) {
					$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 and mymusic=1 group by artist_name" . " limit " . $max_results;
					$stmt = $db->prepare($getTracks);
				}
				else {
					$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where playable=1 and mymusic=1 and artist_name like :query limit " . $max_results;
					$stmt = $db->prepare($getTracks);
					$stmt->bindValue(':query', '%' . $artist . '%');
				}

				$tracks = $stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdoXml($theme, $db);
				return;
			}

			// display all artists
			$noresult=true;
			while ($track = $stmt->fetch()) {

				$noresult=false;

				if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
					$w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[1], 'no', null, "Artist▹" . $track[2] . '∙'. $track[0] . "▹");
				}
			}

			if
			($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './spotify-mini-player/images/warning.png', 'no', null, '');
			}
		} // end of YourMusic▹Albums▹
		elseif ($kind == "Settings") {
			$setting_kind = $words[1];
			$the_query = $words[2];

			if ($setting_kind == "MaxResults") {
				if (mb_strlen($the_query) == 0) {
					$w->result(null, '', "Enter the Max Results number (must be greater than 0):", "Recommendation is between 10 to 100", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, '');
				} else {
					// max results has been set
					if (is_numeric($the_query) == true && $the_query > 0) {
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'MAX_RESULTS▹' . $the_query /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Max Results will be set to <" . $the_query . ">", "Type enter to validate the Max Results", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'yes', null, '');
					} else {
						$w->result(null, '', "The Max Results value entered is not valid", "Please fix it", './spotify-mini-player/images/warning.png', 'no', null, '');

					}
				}
			}
			else if ($setting_kind == "Theme") {

					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'set_theme_to_black' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Set theme to Black", "will set icons to black color", './spotify-mini-player/images/' . 'black' . '/' . 'settings.png', 'yes', null, '');

					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'set_theme_to_green' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Set theme to Green", "will set icons to green color", './spotify-mini-player/images/' . 'green' . '/' . 'settings.png', 'yes', null, '');

					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'set_theme_to_new' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Set theme to New Design", "will set icons to new design", './spotify-mini-player/images/' . 'new' . '/' . 'settings.png', 'yes', null, '');

				}
		} // end of Settings
		elseif ($kind == "FeaturedPlaylist") {
			$country = $words[1];
			$the_query = $words[2];

			$api = getSpotifyWebAPI($w);
			if ($api == false) {
				$w->result(null, 'help', "Internal issue (getSpotifyWebAPI)", "", './spotify-mini-player/images/warning.png', 'no', null, '');
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
				$w->result(null, '', $featuredPlaylists->message , '' . $playlists->total . ' playlists available', './spotify-mini-player/images/' . $theme . '/' . 'info.png', 'no', null, '');
				$items = $playlists->items;
				foreach ($items as $playlist) {
					$tracks = $playlist->tracks;
					$owner = $playlist->owner;

					$playlist_artwork_path = getPlaylistArtwork($w, $theme , $playlist->uri, false);
					$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , $playlist->uri /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , '' /* other_action */ , $alfred_playlist_uri /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, $playlist->name /* playlist_name */, $playlist_artwork_path /* playlist_artwork_path */, $alfred_playlist_name /* alfred_playlist_name */)), ucfirst($playlist->name) . " (" . $tracks->total . " tracks)", $arrayresult, $playlist_artwork_path, $playlist->uri, 'yes', null, '');
				}

			}
			catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
				$w->result(null, 'help', "Exception occurred", "" . $e, './spotify-mini-player/images/warning.png', 'no', null, '');
				echo $w->toxml();
				return;
			}

		}
		elseif ($kind == "Alfred Playlist") {
			$setting_kind = $words[1];
			$theplaylist = $words[2];

			if ($setting_kind == "Set Alfred Playlist") {
				$w->result(null, '', "Set your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './spotify-mini-player/images/' . $theme . '/' . 'settings.png', 'no', null, '');

				try {
					if (mb_strlen($theplaylist) < 3) {
						$getPlaylists = "select * from playlists where ownedbyuser=1";
						$stmt = $db->prepare($getPlaylists);
					}
					else {
						$getPlaylists = "select * from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist)";
						$stmt = $db->prepare($getPlaylists);
						$stmt->bindValue(':playlist', '%' . $theplaylist . '%');
					}

					$playlists = $stmt->execute();

				} catch (PDOException $e) {
					handleDbIssuePdoXml($theme, $db);
					return;
				}

				while ($playlist = $stmt->fetch()) {

					// Prevent toplist to be chosen as Alfred Playlist
					if (strpos($playlist[0], 'toplist') === false) {
						$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'ALFRED_PLAYLIST▹' .  $playlist[0] . '▹' . $playlist[1] /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "Select the playlist to set it as your Alfred Playlist", $playlist[5], 'yes', null, '');
					}
				}
			} elseif ($setting_kind == "Confirm Clear Alfred Playlist") {

				$w->result(null, '', "Are you sure?", "This will remove all the tracks in your current Alfred Playlist.", './spotify-mini-player/images/warning.png', 'no', null, '');

				$w->result(null, '', "No, cancel", "Return to Alfred Playlist", './spotify-mini-player/images/' . $theme . '/' . 'uncheck.png', 'no', null, 'Alfred Playlist▹');

				$w->result(null, serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , 'CLEAR_ALFRED_PLAYLIST▹' .  $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/ , '' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Yes, go ahead", "This is not undoable", './spotify-mini-player/images/' . $theme . '/' . 'check.png', 'yes', null, '');

			}
		}
		// end of Settings
	}
}

echo $w->toxml();

//$end_time = computeTime();
//$total_temp = ($end_time-$begin_time);
//echo "$total_temp\n";

?>