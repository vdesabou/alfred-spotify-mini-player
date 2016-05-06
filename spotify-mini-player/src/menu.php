<?php

/**
 * oAuthChecks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $update_in_progress
 * @return void
 */
function oAuthChecks($w, $query, $settings, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	////
	// OAUTH checks
	// Check oauth config : Client ID and Client Secret
	if ($oauth_client_id == '' && substr_count($query, '▹') == 0) {
		if (mb_strlen($query) == 0) {
			$w->result(null, '', 'Your Application Client ID is missing', 'Get it from your Spotify Application and copy/paste it here', './images/settings.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'https://developer.spotify.com/my-applications/#!/applications' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Open Spotify Application page to get required information', "This will open the Application page with your default browser", './images/spotify.png', 'yes', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/setup/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Go to the website alfred-spotify-mini-player.com to see setup tutorial', "This will open the Application page with your default browser", './images/website.png', 'yes', null, '');

		} elseif (mb_strlen($query) != 32) {
			$w->result(null, '', 'The Application Client ID does not seem valid!', 'The length is not 32. Make sure to copy the Client ID from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
		} else {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Oauth_Client_ID▹' . rtrim(ltrim($query)) /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Application Client ID will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client ID", './images/settings.png', 'yes', null, '');
		}
		echo $w->toxml();
		exit;
	}

	if ($oauth_client_secret == '' && substr_count($query, '▹') == 0) {
		if (mb_strlen($query) == 0) {
			$w->result(null, '', 'Your Application Client Secret is missing!', 'Get it from your Spotify Application and enter it here', './images/settings.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'https://developer.spotify.com/my-applications/#!/applications' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Open Spotify Application page to get required information', "This will open the Application page with your default browser", './images/spotify.png', 'yes', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/setup/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Go to the website alfred-spotify-mini-player.com to see setup tutorial', "This will open the Application page with your default browser", './images/website.png', 'yes', null, '');
		} elseif (mb_strlen($query) != 32) {
			$w->result(null, '', 'The Application Client Secret does not seem valid!', 'The length is not 32. Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
		} elseif ($query == $oauth_client_id) {
			$w->result(null, '', 'The Application Client Secret entered is the same as Application Client ID, this is wrong!', 'Make sure to copy the Client Secret from https://developer.spotify.com/my-applications', './images/warning.png', 'no', null, '');
		} else {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Oauth_Client_SECRET▹' . rtrim(ltrim($query)) /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Application Client Secret will be set to <" . rtrim(ltrim($query)) . ">", "Type enter to validate the Application Client Secret", './images/settings.png', 'yes', null, '');
		}
		echo $w->toxml();
		exit;
	}

	if ($oauth_access_token == '' && substr_count($query, '▹') == 0) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'Oauth_Login' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Authenticate to Spotify", array(
				"This will start the authentication process",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/settings.png', 'yes', null, '');
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'Open▹' . 'http://alfred-spotify-mini-player.com/setup/' /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Go to the website alfred-spotify-mini-player.com to see setup tutorial', "This will open the Application page with your default browser", './images/website.png', 'yes', null, '');
		echo $w->toxml();
		exit;
	}
}


/**
 * mainMenu function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function mainMenu($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$quick_mode                = $settings->quick_mode;

	////////
	//
	// MAIN MENU
	//////////////
	$getCounters = 'select * from counters';
	try {
		$stmt = $db->prepare($getCounters);

		$counters = $stmt->execute();
		$counter  = $stmt->fetch();

	}


	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}


	$all_tracks        = $counter[0];
	$yourmusic_tracks  = $counter[1];
	$all_artists       = $counter[2];
	$yourmusic_artists = $counter[3];
	$all_albums        = $counter[4];
	$yourmusic_albums  = $counter[5];
	$nb_playlists      = $counter[6];

	if ($update_in_progress == true) {
		$in_progress_data                 = $w->read('update_library_in_progress');
		$update_library_in_progress_words = explode('▹', $in_progress_data);
		$elapsed_time       = time() - $update_library_in_progress_words[3];
		if (startsWith($update_library_in_progress_words[0], 'Init')) {
			$w->result(null, $w->data() . '/update_library_in_progress', 'Initialization phase since ' . beautifyTime($elapsed_time, true) . ' : ' . floatToSquares(0), 'Waiting for Spotify servers to return required data', './images/update_in_progress.png', 'no', null, '');
		} else {
			if ($update_library_in_progress_words[0] == 'Refresh Library') {
				$type = 'playlists';
			} elseif ($update_library_in_progress_words[0] == 'Artists') {
				$type = 'artists';
			} else {
				$type = 'tracks';
			}

			if ($update_library_in_progress_words[2] != 0) {
				$w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time, true) . ' : ' . floatToSquares(intval($update_library_in_progress_words[1]) / intval($update_library_in_progress_words[2])), $update_library_in_progress_words[1] . '/' . $update_library_in_progress_words[2] . ' ' . $type . ' processed so far. Currently processing <' . $update_library_in_progress_words[4] . '>', './images/update_in_progress.png', 'no', null, '');
			} else {
				$w->result(null, $w->data() . '/update_library_in_progress', $update_library_in_progress_words[0] . ' update in progress since ' . beautifyTime($elapsed_time, true) . ' : ' . floatToSquares(0), 'No ' . $type . ' processed so far', './images/update_in_progress.png', 'no', null, '');
			}
		}
	}
	$quick_mode_text = '';
	if ($quick_mode) {
		$quick_mode_text = ' ● ⚡ Quick Mode is active';
	}
	if ($all_playlists == true) {
		$w->result(null, '', 'Search for music in "Your Music" and your ' . $nb_playlists . ' playlists', 'Begin typing at least 3 characters to start search in your ' . $all_tracks . ' tracks' . $quick_mode_text, './images/search.png', 'no', null, '');
	} else {
		$w->result(null, '', 'Search for music in "Your Music" only', 'Begin typing at least 3 characters to start search in your ' . $yourmusic_tracks . ' tracks' . $quick_mode_text, './images/search_scope_yourmusic_only.png', 'no', null, '');
	}

	$w->result(null, '', 'Current Track', 'Display current track information and browse various options', './images/current_track.png', 'no', null, 'Current Track▹');

	$w->result(null, '', 'Play Queue', 'Get the current play queue. Always use the workflow to launch tracks, otherwise play queue will be empty', './images/play_queue.png', 'no', null, 'Play Queue▹');

	$w->result(null, serialize(array(
				'' /*track_uri*/ ,
				'' /* album_uri */ ,
				'' /* artist_uri */ ,
				'' /* playlist_uri */ ,
				'' /* spotify_command */ ,
				'' /* query */ ,
				'' /* other_settings*/ ,
				'lookup_current_artist' /* other_action */ ,

				'' /* artist_name */ ,
				'' /* track_name */ ,
				'' /* album_name */ ,
				'' /* track_artwork_path */ ,
				'' /* artist_artwork_path */ ,
				'' /* album_artwork_path */ ,
				'' /* playlist_name */ ,
				'' /* playlist_artwork_path */
			)), 'Lookup Current Artist online', array(
			'☁︎ Query all albums/tracks from current artist online..',
			'alt' => 'Not Available',
			'cmd' => 'Not Available',
			'shift' => 'Not Available',
			'fn' => 'Not Available',
			'ctrl' => 'Not Available'
		), './images/online_artist.png', 'yes', '');

	$w->result(null, '', 'Search online', '☁︎ You can search tracks, artists, albums and playlists online, i.e not in your library', './images/online.png', 'no', null, 'Search Online▹');

	if ($is_alfred_playlist_active == true) {
		if ($alfred_playlist_name != "") {
			$title = 'Alfred Playlist ● ' . $alfred_playlist_name;
			$w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
		} else {
			$title = 'Alfred Playlist ● not set';
			$w->result(null, '', $title, 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
		}

	}
	$w->result(null, '', 'Playlists', 'Browse by playlist' . ' (' . $nb_playlists . ' playlists)', './images/playlists.png', 'no', null, 'Playlist▹');
	$w->result(null, '', 'Your Music', 'Browse Your Music' . ' (' . $yourmusic_tracks . ' tracks ● ' . $yourmusic_albums . '  albums ● ' . $yourmusic_artists . ' artists)', './images/yourmusic.png', 'no', null, 'Your Music▹');
	if ($all_playlists == true) {
		$w->result(null, '', 'Artists', 'Browse by artist' . ' (' . $all_artists . ' artists)', './images/artists.png', 'no', null, 'Artist▹');
		$w->result(null, '', 'Albums', 'Browse by album' . ' (' . $all_albums . ' albums)', './images/albums.png', 'no', null, 'Album▹');
	} else {
		$w->result(null, '', 'Artists in "Your Music"', 'Browse by artist' . ' (' . $yourmusic_artists . ' artists)', './images/artists.png', 'no', null, 'Artist▹');
		$w->result(null, '', 'Albums in "Your Music"', 'Browse by album' . ' (' . $yourmusic_albums . ' albums)', './images/albums.png', 'no', null, 'Album▹');
	}

	//$w->result(null, '', 'Charts', 'Browse charts', './images/numbers.png', 'no', null, 'Charts▹');

	$w->result(null, '', 'Browse', 'Browse Spotify by categories, as in the Spotify player’s “Browse” tab', './images/browse.png', 'no', null, 'Browse▹');
    $w->result(null, '', 'Your Tops', 'Browse your top artists and top tracks', './images/star.png', 'no', null, 'Your Tops▹');

	if ($is_alfred_playlist_active == true) {
		$alfred_playlist_state = 'Alfred Playlist';
	} else {
		$alfred_playlist_state = 'Your Music';
	}
	if ($all_playlists == true) {
		$w->result(null, '', 'Settings', 'User=' . $userid . ', Search scope=<All>, Max results=<' . $max_results . '>, Controlling <' . $alfred_playlist_state . '>, Radio tracks=<' . $radio_number_tracks . '>', './images/settings.png', 'no', null, 'Settings▹');
	} else {
		$w->result(null, '', 'Settings', 'User=' . $userid . ', Search scope=<Your Music>, Max results=<' . $max_results . '>, Controlling <' . $alfred_playlist_state . '>, Radio tracks=<' . $radio_number_tracks . '>', './images/settings.png', 'no', null, 'Settings▹');
	}
}


/**
 * mainSearch function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function mainSearch($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$quick_mode                = $settings->quick_mode;
	$use_mopidy                = $settings->use_mopidy;

	//
	// Search in Playlists
	//
	$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where name like :query";

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
		$public_status = '';
		if (startswith($playlist[1], 'Artist radio for')) {
			$added = '📻 ';
		}
		if ($playlist[9]) {
			$public_status = 'collaborative';
		} else {
			if ($playlist[10]) {
				$public_status = 'public';
			} else {
				$public_status = 'private';
			}
		}

		if ($quick_mode) {
			if ($playlist[10]) {
				$public_status_contrary = 'private';
			} else {
				$public_status_contrary = 'public';
			}
			$subtitle = "⚡️Launch Playlist";
			$subtitle = $subtitle . " ,⇧ ▹ add playlist to ...,  ⌥ ▹ change playlist privacy to " . $public_status_contrary;
			$added = ' ';
			if ($userid == $playlist[4] && $public_status != 'collaborative') {
				$cmdMsg = 'Change playlist privacy to ' . $public_status_contrary;
			} else {
				$cmdMsg = 'Not Available';
			}
			if (startswith($playlist[1], 'Artist radio for')) {
				$added = '📻 ';
			}
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						$playlist[0] /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'set_playlist_privacy_to_' . $public_status_contrary /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist[1] /* playlist_name */ ,
						$playlist[5] /* playlist_artwork_path */
					)), "🎵" . $added . ucfirst($playlist[1]) . " by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], array(
					$subtitle,
					'alt' => 'Not Available',
					'cmd' => $cmdMsg,
					'shift' => 'Add playlist ' . ucfirst($playlist[1]) . ' to ...',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), $playlist[5], 'yes', null, '');
		} else {
			$w->result(null, '', "🎵" . $added . ucfirst($playlist[1]), 'Browse ' . $public_status . " playlist by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
		}
	}

	//
	// Search artists
	//
	if ($all_playlists == false) {
		$getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where yourmusic=1 and artist_uri!='' and artist_name like :artist_name limit " . $max_results;
	} else {
		$getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where artist_uri!='' and artist_name like :artist_name limit " . $max_results;
	}

	try {
		$stmt = $db->prepare($getTracks);
		$stmt->bindValue(':artist_name', '%' . $query . '%');
		$tracks = $stmt->execute();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	while ($track = $stmt->fetch()) {
		if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
			if ($quick_mode) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							$track[1] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'playartist' /* other_action */ ,
							$track[0] /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							$track[0] /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "👤 " . $track[0], '⚡️Play artist', $track[2], 'yes', null, '');
			} else {
				$w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[2], 'no', null, "Artist▹" . $track[1] . '∙' . $track[0] . "▹");
			}
		}
	}

	//
	// Search everything
	//
	if ($all_playlists == false) {
		$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name like :query or album_name like :query or track_name like :query)" . "  order by added_at desc limit " . $max_results;
	} else {
		$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where (artist_name like :query or album_name like :query or track_name like :query)" . "  order by added_at desc limit " . $max_results;
	}

	try {
		$stmt = $db->prepare($getTracks);
		$stmt->bindValue(':query', '%' . $query . '%');
		$tracks = $stmt->execute();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	$noresult = true;
	$quick_mode_text = '';
	if ($quick_mode) {
		$quick_mode_text = '⚡️';
	}
	while ($track = $stmt->fetch()) {
		// if ($noresult) {
		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		// }
		$noresult = false;
		$subtitle = $track[6];
		$added = '';
		if ($track[18] == true) {
			if ($use_mopidy) {
				// skip local tracks if using Mopidy
				continue;
			}
			$added = '📌 ';
		}
		if (checkIfResultAlreadyThere($w->results(), $added . ucfirst($track[7]) . " ● " . $track[5]) == false) {
			if ($track[14] == true) {
				$w->result(null, serialize(array(
							$track[2] /*track_uri*/ ,
							$track[3] /* album_uri */ ,
							$track[4] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'' /* other_action */ ,
							$track[7] /* artist_name */ ,
							$track[5] /* track_name */ ,
							$track[6] /* album_name */ ,
							$track[9] /* track_artwork_path */ ,
							$track[10] /* artist_artwork_path */ ,
							$track[11] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), $added . ucfirst($track[7]) . " ● " . $track[5], array(
						$quick_mode_text . $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ...',
						'shift' => 'Add album ' . $track[6] . ' to ...',
						'ctrl' => 'Search artist ' . $track[7] . ' online'
					), $track[9], 'yes', array(
						'copy' => ucfirst($track[7]) . " ● " . $track[5],
						'largetype' => ucfirst($track[7]) . " ● " . $track[5]
					), '');
			} else {
				$w->result(null, '', '🚫 ' . ucfirst($track[7]) . " ● " . $track[5], $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
			}
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
	}

	//
	// Search albums
	//
	if ($all_playlists == false) {
		$getTracks = "select album_name,album_uri,album_artwork_path,uri from tracks where yourmusic=1 and album_name like :album_name group by album_name order by max(added_at) desc limit " . $max_results;
	} else {
		$getTracks = "select album_name,album_uri,album_artwork_path,uri from tracks where album_name like :album_name group by album_name order by max(added_at) desc limit " . $max_results;
	}

	try {
		$stmt = $db->prepare($getTracks);
		$stmt->bindValue(':album_name', '%' . $query . '%');
		$tracks = $stmt->execute();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	while ($track = $stmt->fetch()) {
		if (checkIfResultAlreadyThere($w->results(), "💿 " . ucfirst($track[0])) == false) {
			if ($track[1] == '') {
				// can happen for local tracks
				$track[1] = $track[3];
			}
			if ($quick_mode) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							$track[1] /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'playalbum' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							$track[0] /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							$track[2] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "💿 " . ucfirst($track[0]), '⚡️Play album', $track[2], 'yes', null, '');
			} else {
				$w->result(null, '', "💿 " . ucfirst($track[0]), "Browse this album", $track[2], 'no', null, "Album▹" . $track[1] . '∙' . $track[0] . "▹");
			}
		}
	}
	if (! $use_mopidy) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					$query /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'' /* other_action */ ,

					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Search for " . $query . " in Spotify", array(
				'This will start a new search in Spotify',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/spotify.png', 'yes', null, '');
	}

	$w->result(null, null, "Search for " . $query . " online", array(
			'This will search online, i.e not in your library',
			'alt' => 'Not Available',
			'cmd' => 'Not Available',
			'shift' => 'Not Available',
			'fn' => 'Not Available',
			'ctrl' => 'Not Available'
		), './images/online.png', 'no', null, 'Search Online▹' . $query);
}


/**
 * searchCategoriesFastAccess function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function searchCategoriesFastAccess($w, $query, $settings, $db, $update_in_progress) {
	$alfred_playlist_name = $settings->alfred_playlist_name;
	$now_playing_notifications = $settings->now_playing_notifications;

	//
	// Search categories for fast access
	//
	if (strpos(strtolower('playlists'), strtolower($query)) !== false) {
		$w->result(null, '', 'Playlists', 'Browse by playlist', './images/playlists.png', 'no', null, 'Playlist▹');
	}
	if (strpos(strtolower('albums'), strtolower($query)) !== false) {
		$w->result(null, '', 'Albums', 'Browse by album', './images/albums.png', 'no', null, 'Album▹');
	}
	if (strpos(strtolower('charts'), strtolower($query)) !== false) {
		$w->result(null, '', 'Charts', 'Browse charts', './images/numbers.png', 'no', null, 'Charts▹');
	}
	if (strpos(strtolower('browse'), strtolower($query)) !== false) {
		$w->result(null, '', 'Browse', 'Browse Spotify by categories, as in the Spotify player’s “Browse” tab', './images/browse.png', 'no', null, 'Browse▹');
	}
	if (strpos(strtolower('your top'), strtolower($query)) !== false) {
		$w->result(null, '', 'Your Tops', 'Browse your top artists and top tracks', './images/star.png', 'no', null, 'Your Tops▹');
	}
	if (strpos(strtolower('lookup current artist online'), strtolower($query)) !== false) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'lookup_current_artist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Lookup Current Artist online', array(
				'☁︎ Query all albums/tracks from current artist online..',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/online_artist.png', 'yes', '');
	}
	if (strpos(strtolower('search online'), strtolower($query)) !== false) {
		$w->result(null, '', 'Search online', '☁︎ You can search tracks, artists, albums and playlists online, i.e not in your library', './images/online.png', 'no', null, 'Search Online▹');
	}
	if (strpos(strtolower('new releases'), strtolower($query)) !== false) {
		$w->result(null, '', 'New Releases', 'Browse new album releases', './images/new_releases.png', 'no', null, 'New Releases▹');
	}
	if (strpos(strtolower('artists'), strtolower($query)) !== false) {
		$w->result(null, '', 'Artists', 'Browse by artist', './images/artists.png', 'no', null, 'Artist▹');
	}
	if (strpos(strtolower('play queue'), strtolower($query)) !== false) {
		if ($now_playing_notifications == true) {
			$w->result(null, '', 'Play Queue', 'Get the current play queue. Always use the workflow to launch tracks, otherwise play queue will be empty', './images/play_queue.png', 'no', null, 'Play Queue▹');
		}
	}
	if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
		$w->result(null, '', 'Alfred Playlist (currently set to <' . $alfred_playlist_name . '>)', 'Choose one of your playlists and add tracks, album, playlist to it directly from the workflow', './images/alfred_playlist.png', 'no', null, 'Alfred Playlist▹');
	}
	if (strpos(strtolower('settings'), strtolower($query)) !== false) {
		$w->result(null, '', 'Settings', 'Go to settings', './images/settings.png', 'no', null, 'Settings▹');
	}
	if (strpos(strtolower('featured playlist'), strtolower($query)) !== false) {
		$w->result(null, '', 'Featured Playlist', 'Browse the current featured playlists', './images/star.png', 'no', null, 'Featured Playlist▹');
	}
	if (strpos(strtolower('your music'), strtolower($query)) !== false) {
		$w->result(null, '', 'Your Music', 'Browse Your Music', './images/tracks.png', 'no', null, 'Your Music▹');
	}
	if (strpos(strtolower('current track'), strtolower($query)) !== false) {
		$w->result(null, '', 'Current Track', 'Display current track information and browse various options', './images/tracks.png', 'no', null, 'Current Track▹');
	}
}


/**
 * searchCommandsFastAccess function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function searchCommandsFastAccess($w, $query, $settings, $db, $update_in_progress) {
	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$use_mopidy                = $settings->use_mopidy;
	$mopidy_server             = $settings->mopidy_server;
	$mopidy_port               = $settings->mopidy_port;

	if (mb_strlen($query) < 2) {
		////////
		//
		// Fast Access to commands
		//////////////
		$w->result('SpotifyMiniPlayer_' . 'next', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'next' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'previous', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'previous' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'lookup_current_artist', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'lookup_current_artist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Lookup Current Artist online', array(
				'☁︎ Query all albums/tracks from current artist online..',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/online_artist.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'lyrics', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'lyrics' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Get Lyrics for current track', array(
				'Get current track lyrics',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/lyrics.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'biography', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'biography' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Display biography', array(
				"This will display the artist biography, twitter and official website",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/biography.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'play', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'play' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'play_current_artist', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'play_current_artist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');
		$w->result('SpotifyMiniPlayer_' . 'play_current_album', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'play_current_album' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');

		$w->result('SpotifyMiniPlayer_' . 'pause', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'pause' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'playpause', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'playpause' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'current', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'current' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'random', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'random' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Random Track', 'Play random track', './images/random.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'random_album', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'random_album' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Random Album', 'Play random album', './images/random_album.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'shuffle', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'shuffle' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');

		if ($update_in_progress == false) {
			$w->result('SpotifyMiniPlayer_' . 'refresh_library', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'refresh_library' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Refresh your library", array(
					'Do this when your library has changed (outside the scope of this workflow)',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/update.png', 'yes', null, '');
		}

		if ($update_in_progress == false) {
			$w->result('SpotifyMiniPlayer_' . 'current_artist_radio', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'current_artist_radio' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Create artist radio playlist for current artist', 'Create artist radio playlist', './images/radio_artist.png', 'yes', '');

			$w->result('SpotifyMiniPlayer_' . 'current_track_radio', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'current_track_radio' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Create song radio playlist for current track', 'Create song radio playlist', './images/radio_song.png', 'yes', '');

			if ($is_alfred_playlist_active == true) {
				$w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'add_current_track' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
			} else {
				$w->result('SpotifyMiniPlayer_' . 'add_current_track', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'add_current_track' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
			}
			$w->result('SpotifyMiniPlayer_' . 'add_current_track_to', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'add_current_track_to' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');

			$w->result('SpotifyMiniPlayer_' . 'remove_current_track_from', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'remove_current_track_from' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Remove current track from...', 'Current track will be removed from Your Music or a playlist of your choice', './images/remove_from.png', 'yes', '');
		}

		$w->result('SpotifyMiniPlayer_' . 'mute', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'mute' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Mute/Unmute Spotify Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'volume_down', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'volume_down' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Volume Down', 'Decrease Spotify Volume', './images/volume_down.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'volume_up', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'volume_up' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Volume Up', 'Increase Spotify Volume', './images/volume_up.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'volmax', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'volmax' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Set Spotify Volume to Maximum', 'Set the Spotify volume to maximum', './images/volmax.png', 'yes', '');

		$w->result('SpotifyMiniPlayer_' . 'volmid', serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'volmid' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Set Spotify Volume to 50%', 'Set the Spotify volume to 50%', './images/volmid.png', 'yes', '');

		if ($use_mopidy == true) {
			$w->result('SpotifyMiniPlayer_' . 'disable_mopidy', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'disable_mopidy' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Disable Mopidy", array(
					"You will use Spotify Desktop app with AppleScript instead",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/disable_mopidy.png', 'yes', null, '');
		} else {
			$w->result('SpotifyMiniPlayer_' . 'enable_mopidy', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'enable_mopidy' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Enable Mopidy", array(
					"You will use Mopidy",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/enable_mopidy.png', 'yes', null, '');
		}
	} else {
		//
		// Search commands for fast access
		//
		if (strpos(strtolower('next'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'next' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Next Track', 'Play the next track in Spotify', './images/next.png', 'yes', '');
		}
		if (strpos(strtolower('previous'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'previous' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');
		}
		if (strpos(strtolower('previous'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'previous' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Previous Track', 'Play the previous track in Spotify', './images/previous.png', 'yes', '');
		}
		if (strpos(strtolower('lyrics'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'lyrics' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Get Lyrics for current track', array(
					'Get current track lyrics',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/lyrics.png', 'yes', '');
		}

		if (strpos(strtolower('biography'), strtolower($query)) !== false) {
			$w->result('SpotifyMiniPlayer_' . 'biography', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'biography' /* other_action */ ,

						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Display biography', array(
					"This will display the artist biography, twitter and official website",
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/biography.png', 'yes', '');
		}

		if (strpos(strtolower('query'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'lookup_current_artist' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Lookup Current Artist online', array(
					'☁︎ Query all albums/tracks from current artist online..',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/online_artist.png', 'yes', '');
		}
		if (strpos(strtolower('play'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'play' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Play', 'Play the current Spotify track', './images/play.png', 'yes', '');

			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'playpause' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');

			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'play_current_artist' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Play current artist', 'Play the current artist', './images/artists.png', 'yes', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'play_current_album' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Play current album', 'Play the current album', './images/albums.png', 'yes', null, '');
		}
		if (strpos(strtolower('pause'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'pause' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Pause', 'Pause the current Spotify track', './images/pause.png', 'yes', '');

			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'playpause' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Play / Pause', 'Play or Pause the current Spotify track', './images/playpause.png', 'yes', '');
		}


		if (strpos(strtolower('current'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'current' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Get Current Track info', 'Get current track information', './images/info.png', 'yes', '');
		}
		if (strpos(strtolower('random'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'random' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Random Track', 'Play random track', './images/random.png', 'yes', '');

			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'random_album' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Random Album', 'Play random album', './images/random_album.png', 'yes', '');
		}
		if (strpos(strtolower('shuffle'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'shuffle' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Shuffle', 'Activate/Deactivate shuffling in Spotify', './images/shuffle.png', 'yes', '');
		}
		if (strpos(strtolower('refresh'), strtolower($query)) !== false) {
			if ($update_in_progress == false) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'refresh_library' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Refresh your library", array(
						'Do this when your library has changed (outside the scope of this workflow)',
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'
					), './images/update.png', 'yes', null, '');
			}
		}
		if (strpos(strtolower('update'), strtolower($query)) !== false) {
			if ($update_in_progress == false) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'refresh_library' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Refresh your library", array(
						'Do this when your library has changed (outside the scope of this workflow)',
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'
					), './images/update.png', 'yes', null, '');
			}
		}
		if ($update_in_progress == false) {
			if (strpos(strtolower('add'), strtolower($query)) !== false) {
				if ($is_alfred_playlist_active == true) {
					$w->result(null, serialize(array(
								'' /*track_uri*/ ,
								'' /* album_uri */ ,
								'' /* artist_uri */ ,
								'' /* playlist_uri */ ,
								'' /* spotify_command */ ,
								'' /* query */ ,
								'' /* other_settings*/ ,
								'add_current_track' /* other_action */ ,

								'' /* artist_name */ ,
								'' /* track_name */ ,
								'' /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								'' /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), 'Add current track to Alfred Playlist', 'Current track will be added to Alfred Playlist', './images/add_to_ap_yourmusic.png', 'yes', '');
				} else {
					$w->result(null, serialize(array(
								'' /*track_uri*/ ,
								'' /* album_uri */ ,
								'' /* artist_uri */ ,
								'' /* playlist_uri */ ,
								'' /* spotify_command */ ,
								'' /* query */ ,
								'' /* other_settings*/ ,
								'add_current_track' /* other_action */ ,

								'' /* artist_name */ ,
								'' /* track_name */ ,
								'' /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								'' /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), 'Add current track to Your Music', 'Current track will be added to Your Music', './images/add_to_ap_yourmusic.png', 'yes', '');
				}
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'add_current_track_to' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Add current track to...', 'Current track will be added to Your Music or a playlist of your choice', './images/add_to.png', 'yes', '');
			}
			if (strpos(strtolower('remove'), strtolower($query)) !== false) {
				$w->result('SpotifyMiniPlayer_' . 'remove_current_track_from', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'remove_current_track_from' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Remove current track from...', 'Current track will be removed from Your Music or a playlist of your choice', './images/remove_from.png', 'yes', '');
			}
			if (strpos(strtolower('radio'), strtolower($query)) !== false) {
				$w->result('SpotifyMiniPlayer_' . 'current_artist_radio', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'current_artist_radio' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Create artist radio playlist for current artist', 'Create artist radio playlist', './images/radio_artist.png', 'yes', '');

				$w->result('SpotifyMiniPlayer_' . 'current_track_radio', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'current_track_radio' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'Create song radio playlist for current track', 'Create song radio playlist', './images/radio_song.png', 'yes', '');
			}
		}
		if (strpos(strtolower('mute'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'mute' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Mute/Unmute Spotify Volume', 'Mute/Unmute Volume', './images/mute.png', 'yes', '');
		}
		if (strpos(strtolower('volume_down'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'volume_down' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Volume Down', 'Decrease Spotify Volume', './images/volume_down.png', 'yes', '');
		}
		if (strpos(strtolower('volume_up'), strtolower($query)) !== false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'volume_up' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Volume Up', 'Increase Spotify Volume', './images/volume_up.png', 'yes', '');
		}

		if (strpos(strtolower('volmax'), strtolower($query)) !== false) {
			$w->result('SpotifyMiniPlayer_' . 'volmax', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'volmax' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Set Spotify Volume to Maximum', 'Set the Spotify volume to maximum', './images/volmax.png', 'yes', '');
		}

		if (strpos(strtolower('volmid'), strtolower($query)) !== false) {
			$w->result('SpotifyMiniPlayer_' . 'volmid', serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'volmid' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Set Spotify Volume to 50%', 'Set the Spotify volume to 50%', './images/volmid.png', 'yes', '');
		}

		if (strpos(strtolower('mopidy'), strtolower($query)) !== false) {
			if ($use_mopidy == true) {
				$w->result('SpotifyMiniPlayer_' . 'disable_mopidy', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'disable_mopidy' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Disable Mopidy", array(
						"You will use Spotify Desktop app with AppleScript instead",
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'
					), './images/disable_mopidy.png', 'yes', null, '');
			} else {
				$w->result('SpotifyMiniPlayer_' . 'enable_mopidy', serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'enable_mopidy' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Enable Mopidy", array(
						"You will use Mopidy",
						'alt' => 'Not Available',
						'cmd' => 'Not Available',
						'shift' => 'Not Available',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'
					), './images/enable_mopidy.png', 'yes', null, '');
			}
		}
	}
}


/**
 * firstDelimiterPlaylists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterPlaylists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Search playlists
	//
	$theplaylist = $words[1];
	try {
		if (mb_strlen($theplaylist) < 2) {
			$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists";
			$stmt         = $db->prepare($getPlaylists);
		} else {
			$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where (name like :query or author like :query)";
			$stmt         = $db->prepare($getPlaylists);
			$stmt->bindValue(':query', '%' . $theplaylist . '%');
		}

		$playlists = $stmt->execute();
	}


	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	$noresult = true;
	if ($query == "Playlist▹Artist radio") {
		while ($playlist = $stmt->fetch()) {
			$noresult = false;
			if ($playlist[9]) {
				$public_status = 'collaborative';
			} else {
				if ($playlist[10]) {
					$public_status = 'public';
				} else {
					$public_status = 'private';
				}
			}
			if (startswith($playlist[1], 'Artist radio for')) {
				$w->result(null, '', "🎵 " . ucfirst($playlist[1]), $public_status . " playlist by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
			}
		}
	} elseif ($query == "Playlist▹Song radio") {
		while ($playlist = $stmt->fetch()) {
			$noresult = false;
			if ($playlist[9]) {
				$public_status = 'collaborative';
			} else {
				if ($playlist[10]) {
					$public_status = 'public';
				} else {
					$public_status = 'private';
				}
			}
			if (startswith($playlist[1], 'Song radio for')) {
				$w->result(null, '', "🎵 " . ucfirst($playlist[1]), $public_status . " playlist by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
			}
		}
	} else {
		$savedPlaylists           = array();
		$nb_artist_radio_playlist = 0;
		$nb_song_radio_playlist   = 0;
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

		if (mb_strlen($theplaylist) < 2) {
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
			$added    = ' ';
			if ($playlist[9]) {
				$public_status = 'collaborative';
			} else {
				if ($playlist[10]) {
					$public_status = 'public';
				} else {
					$public_status = 'private';
				}
			}
			$w->result(null, '', "🎵" . $added . ucfirst($playlist[1]), $public_status . " playlist by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
	}
}


/**
 * firstDelimiterAlfredPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterAlfredPlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Alfred Playlist
	//
	$playlist = $words[1];

	$r = explode(':', $alfred_playlist_uri);

	$w->result(null, '', "Browse your Alfred playlist (" . $alfred_playlist_name . " by " . $r[2] . ")", "You can change the playlist by selecting Change your Alfred playlist below", getPlaylistArtwork($w, $alfred_playlist_uri, false), 'no', null, 'Playlist▹' . $alfred_playlist_uri . '▹');

	if ($update_in_progress == false) {
		$w->result(null, '', "Change your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');

		if (strtolower($r[3]) != strtolower('Starred')) {
			$w->result(null, '', "Clear your Alfred Playlist", "This will remove all the tracks in your current Alfred Playlist", './images/uncheck.png', 'no', null, 'Alfred Playlist▹Confirm Clear Alfred Playlist▹');
		}
	}
}


/**
 * firstDelimiterArtists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterArtists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Search artists
	//
	$artist = $words[1];

	try {
		if (mb_strlen($artist) < 2) {
			if ($all_playlists == false) {
				$getTracks = "select artist_name,artist_artwork_path,artist_uri,uri from tracks where yourmusic=1 group by artist_name" . " limit " . $max_results;
			} else {
				$getTracks = "select artist_name,artist_artwork_path,artist_uri,uri from tracks  group by artist_name" . " limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
		} else {
			if ($all_playlists == false) {
				$getTracks = "select artist_name,artist_artwork_path,artist_uri,uri from tracks where yourmusic=1 and artist_name like :query limit " . $max_results;
			} else {
				$getTracks = "select artist_name,artist_artwork_path,artist_uri,uri from tracks where artist_name like :query limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $artist . '%');
		}

		$tracks = $stmt->execute();

	}


	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	// display all artists
	$noresult = true;
	while ($track = $stmt->fetch()) {
		$noresult         = false;
		$nb_artist_tracks = getNumberOfTracksForArtist($db, $track[0]);
		if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)') == false) {
			$uri = $track[2];
			// in case of local track, pass track uri instead
			if($uri == '') {
				$uri = $track[3];
			}

			$w->result(null, '', "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)', "Browse this artist", $track[1], 'no', null, "Artist▹" . $uri . '∙' . $track[0] . "▹");
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
		if (! $use_mopidy) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'artist:' . $artist /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'' /* other_action */ ,

						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Search for artist " . $artist . " in Spotify", array(
					'This will start a new search in Spotify',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/spotify.png', 'yes', null, '');
		}
	}
}


/**
 * firstDelimiterAlbums function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterAlbums($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	// New Releases menu
	$w->result(null, '', 'New Releases', 'Browse new album releases', './images/new_releases.png', 'no', null, 'New Releases▹');

	//
	// Search albums
	//
	$album = $words[1];
	try {
		if (mb_strlen($album) < 2) {
			if ($all_playlists == false) {
				$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic=1" . "  group by album_name order by max(added_at) desc limit " . $max_results;
			} else {
				$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks group by album_name order by max(added_at) desc limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
		} else {
			if ($all_playlists == false) {
				$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic=1 and album_name like :query group by album_name order by max(added_at) desc limit " . $max_results;
			} else {
				$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where album_name like :query group by album_name order by max(added_at) desc limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $album . '%');
		}

		$tracks = $stmt->execute();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	// display all albums
	$noresult = true;
	while ($track = $stmt->fetch()) {
		$noresult        = false;
		$nb_album_tracks = getNumberOfTracksForAlbum($db, $track[3]);
		if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)') == false) {
			$w->result(null, '', ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)', $track[4] . ' by ' . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . "▹");
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
		if (! $use_mopidy) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'album:' . $album /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Search for album " . $album . " in Spotify", array(
					'This will start a new search in Spotify',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/spotify.png', 'yes', null, '');
		}
	}
}


/**
 * firstDelimiterFeaturedPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterFeaturedPlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$country_code = $settings->country_code;

	$w->result(null, '', getCountryName($country_code), 'Browse the current featured playlists in ' . getCountryName($country_code), './images/star.png', 'no', null, 'Featured Playlist▹' . $country_code . '▹');

	if ($country_code != 'US') {
		$w->result(null, '', getCountryName('US'), 'Browse the current featured playlists in ' . getCountryName('US'), './images/star.png', 'no', null, 'Featured Playlist▹US▹');
	}

	if ($country_code != 'GB') {
		$w->result(null, '', getCountryName('GB'), 'Browse the current featured playlists in ' . getCountryName('GB'), './images/star.png', 'no', null, 'Featured Playlist▹GB▹');
	}

	$w->result(null, '', 'Choose Another country', 'Browse the current featured playlists in another country of your choice', './images/star.png', 'no', null, 'Featured Playlist▹Choose a Country▹');
}


/**
 * firstDelimiterSearchOnline function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterSearchOnline($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Search online
	//
	$the_query = $words[1] . "*";

	if (mb_strlen($the_query) < 2) {

		if ($kind == "Search Online") {

			$w->result(null, 'help', "Search for playlists, artists, albums or tracks online, i.e not in your library", "Begin typing at least 3 characters to start search online. This is using slow Spotify API, be patient.", './images/info.png', 'no', null, '');

			$w->result(null, null, "Search for playlists only", array(
					'This will search for playlists online, i.e not in your library',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/playlists.png', 'no', null, 'Search Playlists Online▹');

			$w->result(null, null, "Search for tracks only", array(
					'This will search for tracks online, i.e not in your library',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/tracks.png', 'no', null, 'Search Tracks Online▹');

			$w->result(null, null, "Search for artists only", array(
					'This will search for artists online, i.e not in your library',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/artists.png', 'no', null, 'Search Artists Online▹');

			$w->result(null, null, "Search for albums only", array(
					'This will search for albums online, i.e not in your library',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/albums.png', 'no', null, 'Search Albums Online▹');
		} elseif ($kind == "Search Playlists Online") {
			$w->result(null, 'help', "Search playlists online, i.e not in your library", "Begin typing at least 3 characters to start search online. This is using slow Spotify API, be patient.", './images/info.png', 'no', null, '');
		} elseif ($kind == "Search Artists Online") {
			$w->result(null, 'help', "Search artists online, i.e not in your library", "Begin typing at least 3 characters to start search online. This is using slow Spotify API, be patient.", './images/info.png', 'no', null, '');
		} elseif ($kind == "Search Tracks Online") {
			$w->result(null, 'help', "Search tracks online, i.e not in your library", "Begin typing at least 3 characters to start search online. This is using slow Spotify API, be patient.", './images/info.png', 'no', null, '');
		} elseif ($kind == "Search Albums Online") {
			$w->result(null, 'help', "Search albums online, i.e not in your library", "Begin typing at least 3 characters to start search online. This is using slow Spotify API, be patient.", './images/info.png', 'no', null, '');
		}
	} else {

		$search_playlists = false;
		$search_artists   = false;
		$search_albums    = false;
		$search_tracks    = false;

		if ($kind == "Search Online") {
			$search_playlists       = true;
			$search_artists         = true;
			$search_albums          = true;
			$search_tracks          = true;
			$search_playlists_limit = 8;
			$search_artists_limit   = 5;
			$search_albums_limit    = 5;
			$search_tracks_limit    = 20;
		} elseif ($kind == "Search Playlists Online") {
			$search_playlists       = true;
			$search_playlists_limit = ($max_results <= 50) ? $max_results : 50;
		} elseif ($kind == "Search Artists Online") {
			$search_artists       = true;
			$search_artists_limit = ($max_results <= 50) ? $max_results : 50;
		} elseif ($kind == "Search Albums Online") {
			$search_albums       = true;
			$search_albums_limit = ($max_results <= 50) ? $max_results : 50;
		} elseif ($kind == "Search Tracks Online") {
			$search_tracks       = true;
			$search_tracks_limit = ($max_results <= 50) ? $max_results : 50;
		}

		$noresult = true;

		if ($search_artists == true) {
			// Search Artists
			//

			// call to web api, if it fails,
			// it displays an error in main window
			$query   = 'artist:' . strtolower($the_query);
			$results = searchWebApi($w, $country_code, $query, 'artist', $search_artists_limit, false);

			foreach ($results as $artist) {
				if (checkIfResultAlreadyThere($w->results(), "👤 " . escapeQuery(ucfirst($artist->name))) == false) {
					$noresult = false;
					$w->result(null, '', "👤 " . escapeQuery(ucfirst($artist->name)), "Browse this artist", getArtistArtwork($w, $artist->uri, $artist->name, false), 'no', null, "Online▹" . $artist->uri . '@' . escapeQuery($artist->name) . '▹');
				}
			}
		}

		if ($search_albums == true) {
			// Search Albums
			//
			// call to web api, if it fails,
			// it displays an error in main window
			$query   = 'album:' . strtolower($the_query);
			$results = searchWebApi($w, $country_code, $query, 'album', $search_albums_limit, false);

			try {
				$api = getSpotifyWebAPI($w);
			}
			catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
				$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
				echo $w->toxml();
				return;
			}

			foreach ($results as $album) {
				if (checkIfResultAlreadyThere($w->results(), escapeQuery(ucfirst($album->name))) == false) {
					$noresult = false;

					try {
						$full_album = $api->getAlbum($album->id);
					}
					catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
						$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
						echo $w->toxml();
						return;
					}
					$w->result(null, '', escapeQuery(ucfirst($album->name)) . ' (' . $full_album->tracks->total . ' tracks)', $album->album_type . ' by ' . escapeQuery($full_album->artists[0]->name), getTrackOrAlbumArtwork($w, $album->uri, false), 'no', null, 'Online▹' . $full_album->artists[0]->uri . '@' . escapeQuery($full_album->artists[0]->name) . '@' . $album->uri . '@' . escapeQuery($album->name) . '▹');
				}
			}
		}

		if ($search_playlists == true) {
			// Search Playlists
			//

			// call to web api, if it fails,
			// it displays an error in main window
			$query   = 'playlist:' . strtolower($the_query);
			$results = searchWebApi($w, $country_code, $query, 'playlist', $search_playlists_limit, false);

			foreach ($results as $playlist) {
				$noresult = false;
				$w->result(null, '', "🎵" . escapeQuery($playlist->name), "by " . $playlist->owner->id . " ● " . $playlist->tracks->total . " tracks", getPlaylistArtwork($w, $playlist->uri, false), 'no', null, "Online Playlist▹" . $playlist->uri . '∙' . escapeQuery($playlist->name) . '▹');

			}
		}

		if ($search_tracks == true) {
			// Search Tracks
			//
			// call to web api, if it fails,
			// it displays an error in main window
			$query   = 'track:' . strtolower($the_query);
			$results = searchWebApi($w, $country_code, $query, 'track', $search_tracks_limit, false);
			$first = true;
			foreach ($results as $track) {
				// if ($first == true) {
				//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
				//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
				// }
				// $first         = false;
				$noresult      = false;
				$track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false);

				$artists = $track->artists;
				$artist  = $artists[0];
				$album   = $track->album;

				$w->result(null, serialize(array(
							$track->uri /*track_uri*/ ,
							$album->uri /* album_uri */ ,
							$artist->uri /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'play_track_in_album_context' /* other_action */ ,
							escapeQuery($artist->name) /* artist_name */ ,
							escapeQuery($track->name) /* track_name */ ,
							escapeQuery($album->name) /* album_name */ ,
							$track_artwork /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), escapeQuery(ucfirst($artist->name)) . " ● " . escapeQuery($track->name), array(
						beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album->name),
						'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify',
						'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify',
						'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
						'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...',
						'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online'
					), $track_artwork, 'yes', null, '');
			}
		}

		if ($noresult) {
			$w->result(null, 'help', "There is no result for this search", "", './images/warning.png', 'no', null, '');
		}
	}
}


/**
 * firstDelimiterCharts function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterCharts($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$country_code = $settings->country_code;

	$w->result(null, '', getCountryName($country_code), 'Browse the current charts in ' . getCountryName($country_code), './images/numbers.png', 'no', null, 'Charts▹' . $country_code . '▹');

	if ($country_code != 'US') {
		$w->result(null, '', getCountryName('US'), 'Browse the current charts in ' . getCountryName('US'), './images/numbers.png', 'no', null, 'Charts▹US▹');
	}

	if ($country_code != 'GB') {
		$w->result(null, '', getCountryName('GB'), 'Browse the current charts in ' . getCountryName('GB'), './images/numbers.png', 'no', null, 'Charts▹GB▹');
	}

	$w->result(null, '', 'Choose Another country', 'Browse the current charts in another country of your choice', './images/numbers.png', 'no', null, 'Charts▹Choose a Country▹');
}


/**
 * firstDelimiterNewReleases function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterNewReleases($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$country_code = $settings->country_code;

	$w->result(null, '', getCountryName($country_code), 'Browse the new album releases in ' . getCountryName($country_code), './images/new_releases.png', 'no', null, 'New Releases▹' . $country_code . '▹');

	if ($country_code != 'US') {
		$w->result(null, '', getCountryName('US'), 'Browse the new album releases in ' . getCountryName('US'), './images/new_releases.png', 'no', null, 'New Releases▹US▹');
	}

	if ($country_code != 'GB') {
		$w->result(null, '', getCountryName('GB'), 'Browse the new album releases in ' . getCountryName('GB'), './images/new_releases.png', 'no', null, 'New Releases▹GB▹');
	}

	$w->result(null, '', 'Choose Another country', 'Browse the new album releases in another country of your choice', './images/new_releases.png', 'no', null, 'New Releases▹Choose a Country▹');
}


/**
 * firstDelimiterCurrentTrack function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterCurrentTrack($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$is_public_playlists       = $settings->is_public_playlists;
	$use_mopidy                = $settings->use_mopidy;
	$is_display_rating         = $settings->is_display_rating;

	if ($use_mopidy) {
		$retArr = array(getCurrentTrackInfoWithMopidy($w));
	} else {
		// get info on current song
		exec("./src/track_info.ksh 2>&1", $retArr, $retVal);
		if ($retVal != 0) {
			$w->result(null, 'help', "AppleScript execution failed!", "Message: " . htmlspecialchars($retArr[0]), './images/warning.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/blog/issue-with-latest-spotify-update/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Maybe you have an issue with a Broken Spotify version?', "Go to the article to get more information", './images/website.png', 'yes', null, '');
			return;
		}
	}

	if (isset($retArr[0]) && substr_count($retArr[0], '▹') > 0) {
		$results = explode('▹', $retArr[0]);
		if ($results[1] == '' || $results[2] == '') {
			$w->result(null, 'help', "Current track is not valid: Artist or Album name is missing", "Fill missing information in Spotify and retry again", './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}

		$href = explode(':', $results[4] );
		$added = '';
		if ($href[1] == 'local') {
			$added = '📌 ';
		}
		$subtitle             = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		$subtitle             = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		if ($results[3] == "playing") {
			$w->result(null, serialize(array(
						$results[4] /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'pause' /* other_action */ ,
						escapeQuery($results[1]) /* artist_name */ ,
						escapeQuery($results[0]) /* track_name */ ,
						escapeQuery($results[2]) /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), $added . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]) . " ● " . floatToStars(($results[6] / 100) ? $is_display_rating : 0) . ' ' . beautifyTime($results[5]/ 1000), array(
					$subtitle,
					'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify',
					'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify',
					'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ...',
					'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ...',
					'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online'
				), ($results[3] == "playing") ? './images/pause.png' : './images/play.png', 'yes', null, '');

		} else {
			$w->result(null, serialize(array(
						$results[4] /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'play' /* other_action */ ,
						escapeQuery($results[1]) /* artist_name */ ,
						escapeQuery($results[0]) /* track_name */ ,
						escapeQuery($results[2]) /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), $added . escapeQuery($results[0]) . " ● " . escapeQuery($results[1]) . " ● " . escapeQuery($results[2]) . " ● " . floatToStars($results[6] / 100) . ' (' . beautifyTime($results[5]) . ')', array(
					$subtitle,
					'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify',
					'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify',
					'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ...',
					'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ...',
					'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online'
				), ($results[3] == "playing") ? './images/pause.png' : './images/play.png', 'yes', null, '');
		}


		$getTracks = "select artist_name,artist_uri from tracks where artist_name=:artist_name limit " . 1;

		try {
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':artist_name', escapeQuery($results[1]));
			$tracks = $stmt->execute();

		}
		catch (PDOException $e) {
			handleDbIssuePdoXml($db);
			return;
		}

		// check if artist is in library
		$noresult = true;
		while ($track = $stmt->fetch()) {
			if ($track[1] != '') {
				$artist_uri = $track[1];
				$noresult   = false;
			}
		}
		if ($noresult == false) {
			$w->result(null, '', "👤 " . ucfirst(escapeQuery($results[1])), "Browse this artist", getArtistArtwork($w, $artist_uri, $results[1], false), 'no', null, "Artist▹" . $artist_uri . '∙' . escapeQuery($results[1]) . "▹");
		} else {
			// artist is not in library
			$w->result(null, '', "👤 " . ucfirst(escapeQuery($results[1])), "Browse this artist", getArtistArtwork($w, '' /* empty artist_uri */, $results[1], false), 'no', null, "Artist▹" . $results[4] . '∙' . escapeQuery($results[1]) . "▹");
		}

		// use track uri here
		$album_artwork_path = getTrackOrAlbumArtwork($w, $results[4], false);
		$w->result(null, serialize(array(
					$results[4] /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'playalbum' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					escapeQuery($results[2]) /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					$album_artwork_path /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "💿 " . escapeQuery($results[2]), 'Play album', $album_artwork_path, 'yes', null, '');

		// use track uri here
		$w->result(null, '', "💿 " . ucfirst(escapeQuery($results[2])), '☁︎ Query all tracks from this album online..', './images/online_album.png', 'no', null, "Online▹" . $results[4] . '@' . escapeQuery($results[1]) . '@' . $results[4] . '@' . escapeQuery($results[2]) . '▹');


		$w->result(null, '', "Get Lyrics for track " . escapeQuery($results[0]), "This will fetch lyrics online", './images/lyrics.png', 'no', null, "Lyrics▹" . $results[4] . "∙" . escapeQuery($results[1]) . '∙' . escapeQuery($results[0]));

		if ($update_in_progress == false) {
			$w->result(null, '', 'Add track ' . escapeQuery($results[0]) . ' to...', 'This will add current track to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $results[4] . '∙' . escapeQuery($results[0]) . '▹');

			$w->result(null, '', 'Remove track ' . escapeQuery($results[0]) . ' from...', 'This will remove current track from Your Music or a playlist you will choose in next step', './images/remove.png', 'no', null, 'Remove▹' . $results[4] . '∙' . escapeQuery($results[0]) . '▹');

			$privacy_status = 'private';
			if ($is_public_playlists) {
				$privacy_status = 'public';
			}
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'current_track_radio' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Create a Song Radio Playlist based on " . escapeQuery($results[0]), array(
					'This will create a ' . $privacy_status . ' song radio playlist with ' . $radio_number_tracks . ' tracks for the current track',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/radio_song.png', 'yes', null, '');
		}

		if ($all_playlists == true) {
			$getTracks = "select playlist_uri from tracks where uri=:uri limit " . $max_results;
			try {
				$stmtgetTracks = $db->prepare($getTracks);
				$stmtgetTracks->bindValue(':uri', $results[4]);
				$stmtgetTracks->execute();
			}
			catch (PDOException $e) {
				handleDbIssuePdoXml($db);
				return;
			}

			while ($track = $stmtgetTracks->fetch()) {

				if ($track[0] == '') {
					// The track is in Your Music
					$w->result(null, '', 'In "Your Music"', "The track is in Your Music", './images/yourmusic.png', 'no', null, "Your Music▹Tracks▹" . escapeQuery($results[0]));
				} else {
					$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where uri=:uri";

					try {
						$stmtGetPlaylists = $db->prepare($getPlaylists);
						$stmtGetPlaylists->bindValue(':uri', $track[0]);
						$playlists = $stmtGetPlaylists->execute();
					}
					catch (PDOException $e) {
						handleDbIssuePdoXml($db);
						return;
					}

					while ($playlist = $stmtGetPlaylists->fetch()) {
						$added = ' ';
						if (startswith($playlist[1], 'Artist radio for')) {
							$added = '📻 ';
						}
						if (checkIfResultAlreadyThere($w->results(), "🎵" . $added . "In playlist " . ucfirst($playlist[1])) == false) {
							if ($playlist[9]) {
								$public_status = 'collaborative';
							} else {
								if ($playlist[10]) {
									$public_status = 'public';
								} else {
									$public_status = 'private';
								}
							}
							$w->result(null, '', "🎵" . $added . "In playlist " . ucfirst($playlist[1]), $public_status . " playlist by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], $playlist[5], 'no', null, "Playlist▹" . $playlist[0] . "▹");
						}
					}
				}
			}
		}
	} else {
		$w->result(null, 'help', "There is no track currently playing", "Launch a track and come back here", './images/warning.png', 'no', null, '');
	}

}


/**
 * firstDelimiterYourMusic function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterYourMusic($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$thequery = $words[1];

	if (mb_strlen($thequery) < 2) {
		$getCounters = 'select * from counters';
		try {
			$stmt = $db->prepare($getCounters);

			$counters = $stmt->execute();
			$counter  = $stmt->fetch();

		}
		catch (PDOException $e) {
			handleDbIssuePdoXml($db);
			return;
		}

		$all_tracks        = $counter[0];
		$yourmusic_tracks  = $counter[1];
		$all_artists       = $counter[2];
		$yourmusic_artists = $counter[3];
		$all_albums        = $counter[4];
		$yourmusic_albums  = $counter[5];
		$nb_playlists      = $counter[6];

		$w->result(null, '', 'Tracks', 'Browse your ' . $yourmusic_tracks . ' tracks in Your Music', './images/tracks.png', 'no', null, 'Your Music▹Tracks▹');
		$w->result(null, '', 'Albums', 'Browse your ' . $yourmusic_albums . ' albums in Your Music', './images/albums.png', 'no', null, 'Your Music▹Albums▹');
		$w->result(null, '', 'Artists', 'Browse your ' . $yourmusic_artists . ' artists in Your Music', './images/artists.png', 'no', null, 'Your Music▹Artists▹');

	} else {
		//
		// Search artists
		//
		$getTracks = "select artist_name,artist_uri,artist_artwork_path from tracks where yourmusic=1 and artist_name like :artist_name limit " . $max_results;

		try {
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':artist_name', '%' . $thequery . '%');

			$tracks = $stmt->execute();

		}
		catch (PDOException $e) {
			handleDbIssuePdoXml($db);
			return;
		}
		$noresult = true;
		while ($track = $stmt->fetch()) {

			if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0])) == false) {
				$noresult = false;
				$w->result(null, '', "👤 " . ucfirst($track[0]), "Browse this artist", $track[2], 'no', null, "Artist▹" . $track[1] . '∙' . $track[0] . "▹");
			}
		}

		//
		// Search everything
		//
		$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name like :query or album_name like :query or track_name like :query)" . " limit " . $max_results;

		try {
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $thequery . '%');

			$tracks = $stmt->execute();

		}
		catch (PDOException $e) {
			handleDbIssuePdoXml($db);
			return;
		}

		while ($track = $stmt->fetch()) {
			// if ($noresult == true) {
			//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
			//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
			//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
			// }
			$noresult = false;
			$subtitle = $track[6];

			if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " ● " . $track[5]) == false) {

				$w->result(null, serialize(array(
							$track[2] /*track_uri*/ ,
							$track[3] /* album_uri */ ,
							$track[4] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'' /* other_action */ ,
							$track[7] /* artist_name */ ,
							$track[5] /* track_name */ ,
							$track[6] /* album_name */ ,
							$track[9] /* track_artwork_path */ ,
							$track[10] /* artist_artwork_path */ ,
							$track[11] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), ucfirst($track[7]) . " ● " . $track[5], $arrayresult = array(
						$track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ...',
						'shift' => 'Add album ' . $track[6] . ' to ...',
						'ctrl' => 'Search artist ' . $track[7] . ' online'
					), $track[9], 'yes', array(
						'copy' => ucfirst($track[7]) . " ● " . $track[5],
						'largetype' => ucfirst($track[7]) . " ● " . $track[5]
					), '');

			}
		}

		if ($noresult) {
			$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
		}
	}
}


/**
 * firstDelimiterLyrics function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterLyrics($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '∙') == 2) {
		//
		// Search Lyrics
		//
		$tmp         = $words[1];
		$words       = explode('∙', $tmp);
		$track_uri   = $words[0];
		$artist_name = $words[1];
		$track_name  = $words[2];

		list($lyrics_url, $lyrics) = getLyrics($w, $artist_name, $track_name);
		if ($userid != 'vdesabou') {
			stathat_ez_count('AlfredSpotifyMiniPlayer', 'lyrics', 1);
		}
		if ($lyrics_url != false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . $lyrics_url /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'See lyrics for ' . $track_name . ' by ' . $artist_name . ' online', "This will open your default browser", './images/lyrics.png', 'yes', null, '');

			$track_artwork = getTrackOrAlbumArtwork($w, $track_uri, false);

			$wrapped          = wordwrap($lyrics, 70, "\n", false);
			$lyrics_sentances = explode("\n", $wrapped);

			for ($i = 0; $i < count($lyrics_sentances); $i++) {
				$w->result(null, '', $lyrics_sentances[$i], '', $track_artwork, 'no', null, '');
			}
		} else {
			$w->result(null, 'help', "No lyrics found!", "", './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * firstDelimiterSettings function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterSettings($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists              = $settings->all_playlists;
	$is_alfred_playlist_active  = $settings->is_alfred_playlist_active;
	$radio_number_tracks        = $settings->radio_number_tracks;
	$now_playing_notifications  = $settings->now_playing_notifications;
	$max_results                = $settings->max_results;
	$alfred_playlist_uri        = $settings->alfred_playlist_uri;
	$alfred_playlist_name       = $settings->alfred_playlist_name;
	$country_code               = $settings->country_code;
	$last_check_update_time     = $settings->last_check_update_time;
	$oauth_client_id            = $settings->oauth_client_id;
	$oauth_client_secret        = $settings->oauth_client_secret;
	$oauth_redirect_uri         = $settings->oauth_redirect_uri;
	$oauth_access_token         = $settings->oauth_access_token;
	$oauth_expires              = $settings->oauth_expires;
	$oauth_refresh_token        = $settings->oauth_refresh_token;
	$display_name               = $settings->display_name;
	$userid                     = $settings->userid;
	$echonest_api_key           = $settings->echonest_api_key;
	$is_public_playlists        = $settings->is_public_playlists;
	$quick_mode                 = $settings->quick_mode;
	$use_mopidy                 = $settings->use_mopidy;
	$mopidy_server              = $settings->mopidy_server;
	$mopidy_port                = $settings->mopidy_port;
	$is_display_rating          = $settings->is_display_rating;
	$volume_percent             = $settings->volume_percent;
	$is_autoplay_playlist       = $settings->is_autoplay_playlist;
	$use_growl                  = $settings->use_growl;

	if ($update_in_progress == false) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'refresh_library' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Refresh your library", array(
				'Do this when your library has changed (outside the scope of this workflow)',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/update.png', 'yes', null, '');
	}

	if ($is_alfred_playlist_active == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_alfred_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Control Your Music", array(
				"You will control Your Music (if disabled, you control Alfred Playlist)",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/yourmusic.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_alfred_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Control Alfred Playlist", array(
				"You will control the Alfred Playlist (if disabled, you control Your Music)",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/alfred_playlist.png', 'yes', null, '');
	}

	if ($all_playlists == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_all_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Set Search Scope to Your Music only', array(
				'Select to search only in "Your Music"',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/search_scope_yourmusic_only.png', 'yes', null, '');

	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_all_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Unset Search Scope to Your Music only', array(
				'Select to search in your complete library ("Your Music" and all Playlists)',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/search.png', 'yes', null, '');
	}
	$w->result(null, '', "Configure Max Number of Results (currently " . $max_results . ")", "Number of results displayed (it does not apply to the list of your playlists)", './images/results_numbers.png', 'no', null, 'Settings▹MaxResults▹');
	$w->result(null, '', "Configure Number of Radio tracks (currently " . $radio_number_tracks . ")", "Number of tracks when creating a Radio Playlist.", './images/radio_numbers.png', 'no', null, 'Settings▹RadioTracks▹');
	$w->result(null, '', "Configure Volume Percent (currently " . $volume_percent . "%)", "The percentage of volume which is increased or decreased.", './images/volume_up.png', 'no', null, 'Settings▹VolumePercentage▹');


	if ($now_playing_notifications == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_now_playing_notifications' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Now Playing notifications", array(
				"Do not display notifications for current playing track",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_now_playing.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_now_playing_notifications' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Now Playing notifications", array(
				"Display notifications for current playing track",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_now_playing.png', 'yes', null, '');
	}

	if ($quick_mode == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_quick_mode' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Quick Mode", array(
				"Do not launch directly tracks/album/artists/playlists in main search",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_quick_mode.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_quick_mode' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Quick Mode", array(
				"Launch directly tracks/album/artists/playlists in main search",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_quick_mode.png', 'yes', null, '');
	}

	if ($is_display_rating == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_display_rating' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Track Rating", array(
				"Do not display track rating with stars in Current Track menu and notifications",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_display_rating.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_display_rating' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Track Rating", array(
				"Display track rating with stars in Current Track menu and notifications",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_display_rating.png', 'yes', null, '');
	}

	if ($is_autoplay_playlist == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_autoplay' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Playlist Autoplay", array(
				"Do not autoplay playlists (radios and complete collection) when they are created",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_autoplay.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_autoplay' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Playlist Autoplay", array(
				"Autoplay playlists (radios and complete collection) when they are created",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_autoplay.png', 'yes', null, '');
	}

	if ($use_growl == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_use_growl' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Growl", array(
				"Use Notification Center instead of Growl",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_use_growl.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_use_growl' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Growl", array(
				"Use Growl instead of Notification Center",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_use_growl.png', 'yes', null, '');
	}

	if ($update_in_progress == false) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'update_library' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Re-Create your library from scratch', array(
				'Do this when refresh library is not working as you would expect',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/recreate.png', 'yes', null, '');
	}

	if ($is_public_playlists == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_public_playlists' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Automatically make new playlists private", array(
				"If disabled, the workflow will mark new playlists (created or followed) as private",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_public_playlists.png', 'yes', null, '');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_public_playlists' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Automatically make new playlists public", array(
				"If enabled, the workflow will mark new playlists (created or followed) as public",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_public_playlists.png', 'yes', null, '');
	}

	if ($use_mopidy == true) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'disable_mopidy' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Disable Mopidy", array(
				"You will use Spotify Desktop app with AppleScript instead",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/disable_mopidy.png', 'yes', null, '');
		$w->result(null, '', "Configure Mopidy server (currently " . $mopidy_server . ")", "Server name/ip where Mopidy server is running", './images/mopidy_server.png', 'no', null, 'Settings▹MopidyServer▹');
		$w->result(null, '', "Configure Mopidy port (currently " . $mopidy_port . ")", "TCP port where Mopidy server is running", './images/mopidy_port.png', 'no', null, 'Settings▹MopidyPort▹');
	} else {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'enable_mopidy' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Enable Mopidy", array(
				"You will use Mopidy",
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/enable_mopidy.png', 'yes', null, '');
	}

	$w->result(null, '', 'Check for workflow update', 'Last checked: ' . beautifyTime(time() - $last_check_update_time, true) . ' ago (note this is automatically done otherwise once per day)', './images/check_update.png', 'no', null, 'Check for update...' . '▹');

	$w->result(null, serialize(array(
				'' /*track_uri*/ ,
				'' /* album_uri */ ,
				'' /* artist_uri */ ,
				'' /* playlist_uri */ ,
				'' /* spotify_command */ ,
				'' /* query */ ,
				'Open▹' . 'http://alfred-spotify-mini-player.com' /* other_settings*/ ,
				'' /* other_action */ ,
				'' /* artist_name */ ,
				'' /* track_name */ ,
				'' /* album_name */ ,
				'' /* track_artwork_path */ ,
				'' /* artist_artwork_path */ ,
				'' /* album_artwork_path */ ,
				'' /* playlist_name */ ,
				'' /* playlist_artwork_path */
			)), 'Go to the website alfred-spotify-mini-player.com', "Find out all information on the workflow on the website", './images/website.png', 'yes', null, '');
}


/**
 * firstDelimiterCheckForUpdate function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterCheckForUpdate($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$check_results = checkForUpdate($w, 0);
	if ($check_results != null && is_array($check_results)) {
		$w->result(null, '', 'New version ' . $check_results[0] . ' is available !', $check_results[2], './images/info.png', 'no', null, '');
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'Open▹' . $check_results[1] /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Click to open and install the new version', "This will open the new version of the Spotify Mini Player workflow", './images/alfred-workflow-icon.png', 'yes', null, '');


	} elseif ($check_results == null) {
		$w->result(null, '', 'No update available', 'You are good to go!', './images/info.png', 'no', null, '');
	} else {
		$w->result(null, '', 'Error happened : ' . $check_results, 'The check for workflow update could not be done', './images/warning.png', 'no', null, '');
		if ($check_results == "This release has not been downloaded from Packal") {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://www.packal.org/workflow/spotify-mini-player' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Download workflow from Packal', "This will open the Spotify Mini Player Packal page with your default browser", './images/packal.png', 'yes', null, '');
		}

	}
	echo $w->toxml();
	return;
}


/**
 * firstDelimiterPlayQueue function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterPlayQueue($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$use_mopidy                = $settings->use_mopidy;

	if ($use_mopidy) {
		$playqueue = $w->read('playqueue.json');
		if ($playqueue == false) {
			$w->result(null, 'help', "There is no track in the play queue", "Make sure to always use the workflow to launch tracks, playlists, etc..Internet connectivity is also required", './images/warning.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Learn more about Play Queue', "Find out all information about Play Queue on alfred-spotify-mini-player.com", './images/website.png', 'yes', null, '');
			echo $w->toxml();
			return;
		}
		$tl_tracks = invokeMopidyMethod($w, "core.tracklist.get_tl_tracks", array());
		$current_tl_track = invokeMopidyMethod($w, "core.playback.get_current_tl_track", array());

		$isShuffleEnabled = invokeMopidyMethod($w, "core.tracklist.get_random", array());
		if ($isShuffleEnabled) {
			$w->result(null, 'help', "Shuffle is enabled", "The order of tracks presented below is not relevant", './images/warning.png', 'no', null, '');
		}
		$noresult = true;
		$firstTime = true;
		$nb_tracks           = 0;
		$track_name = '';
		$album_name = '';
		$playlist_name = '';
		$current_track_found = false;
		$current_track_index = 0;
		foreach ($tl_tracks as $tl_track) {
			$current_track_index++;
			if ($current_track_found == false &&
				$tl_track->tlid == $current_tl_track->tlid) {
				$current_track_found = true;
			}
			if ($current_track_found == false &&
				$tl_track->tlid != $current_tl_track->tlid) {
				continue;
			}
			if ($firstTime == true) {
				$added = '🔈 ';
				if ($playqueue->type == 'playlist') {
					$playlist_name = $playqueue->name;
				} elseif ($playqueue->type == 'album') {
					$album_name = $playqueue->name;
				} elseif ($playqueue->type == 'track') {
					$track_name = $playqueue->name;
				}
				$w->result(null, 'help', "Playing from: " . ucfirst($playqueue->type) . ' ' . $playqueue->name, 'Track ' . $current_track_index . ' on '. count($tl_tracks) . ' tracks queued', './images/play_queue.png', 'no', null, '');
				// $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				// $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
				// $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
			}
			$firstTime = false;
			$max_tracks_displayed = 150;
			if ($nb_tracks >= $max_tracks_displayed) {
				$w->result(null, 'help', "[...] " . (count($tl_tracks) - $max_tracks_displayed) . " additional tracks are in the queue", "A maximum of " . $max_tracks_displayed . " tracks is displayed." , './images/info.png', 'no', null, '');
				break;
			}
			$track_name = '';
			if (isset($tl_track->track->name)) {
				$track_name = $tl_track->track->name;
			}
			$artist_name = '';
			if (isset($tl_track->track->artists[0]->name)) {
				$artist_name = $tl_track->track->artists[0]->name;
			}
			$album_name = '';
			if (isset($tl_track->track->album->name)) {
				$album_name = $tl_track->track->album->name;
			}
			$duration = 'na';
			if (isset($tl_track->track->length)) {
				$duration = beautifyTime($tl_track->track->length / 1000);
			}
			$track_artwork = getTrackOrAlbumArtwork($w, $tl_track->track->uri, false);

			if (strpos($track_name,'[unplayable]') !== false) {
			    $track_name = str_replace('[unplayable]', '', $track_name);
			    $w->result(null, '', '🚫 ' . escapeQuery(ucfirst($artist_name)) . " ● " . escapeQuery($track_name), $duration . " ● " . $album_name, $track_artwork, 'no', null, '');
			} else {
				$w->result(null, serialize(array(
							$tl_track->track->uri /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'play_track_from_play_queue' /* other_action */ ,
							escapeQuery($artist_name) /* artist_name */ ,
							escapeQuery($track_name) /* track_name */ ,
							escapeQuery($album_name) /* album_name */ ,
							$track_artwork /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							$playlist_name /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), $added . escapeQuery($artist_name) . " ● " . escapeQuery($track_name), array(
						$duration . " ● " . escapeQuery($album_name),
						'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
						'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
						'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
						'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...',
						'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online'
					), $track_artwork, 'yes', null, '');
			}
			$noresult      = false;
			$added = '';
			$nb_tracks += 1;
		}

		if ($noresult) {
			$w->result(null, 'help', "There is no track in the play queue from Mopidy", "Make sure to always use the workflow to launch tracks, playlists, etc..Internet connectivity is also required", './images/warning.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Learn more about Play Queue', "Find out all information about Play Queue on alfred-spotify-mini-player.com", './images/website.png', 'yes', null, '');
			echo $w->toxml();
		}
	} else {
		$playqueue = $w->read('playqueue.json');
		if ($playqueue == false) {
			$w->result(null, 'help', "There is no track in the play queue", "Make sure to always use the workflow to launch tracks, playlists, etc..Internet connectivity is also required", './images/warning.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Learn more about Play Queue', "Find out all information about Play Queue on alfred-spotify-mini-player.com", './images/website.png', 'yes', null, '');
			echo $w->toxml();
			return;
		}
		$command_output = exec("osascript -e '
        tell application \"Spotify\"
        if shuffling enabled is true then
            if shuffling is true then
                return \"enabled\"
            else
                return \"disabled\"
            end if
        else
            return \"disabled\"
        end if
        end tell'");
		if ($command_output == "enabled") {
			$w->result(null, 'help', "Shuffle is enabled", "The order of tracks presented below is not relevant", './images/warning.png', 'no', null, '');
		}
		$noresult = true;
		$nb_tracks           = 0;
		$track_name = '';
		$album_name = '';
		$playlist_name = '';
		for ($i = $playqueue->current_track_index; $i < count($playqueue->tracks);$i++) {
			$track = $playqueue->tracks[$i];
			if ($noresult == true) {
				$added = '🔈 ';
				if ($playqueue->type == 'playlist') {
					$playlist_name = $playqueue->name;
				} elseif ($playqueue->type == 'album') {
					$album_name = $playqueue->name;
				} elseif ($playqueue->type == 'track') {
					$track_name = $playqueue->name;
				}
				$w->result(null, 'help', "Playing from: " . ucfirst($playqueue->type) . ' ' . $playqueue->name, 'Track ' . ($playqueue->current_track_index + 1) . ' on '. count($playqueue->tracks) . ' tracks queued', './images/play_queue.png', 'no', null, '');
				// $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				// $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
				// $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
			}
			$max_tracks_displayed = 150;
			if ($nb_tracks >= $max_tracks_displayed) {
				$w->result(null, 'help', "[...] " . (count($playqueue->tracks) - $max_tracks_displayed) . " additional tracks are in the queue", "A maximum of " . $max_tracks_displayed . " tracks is displayed." , './images/info.png', 'no', null, '');
				break;
			}
			$track_name = '';
			if (isset($track->name)) {
				$track_name = $track->name;
			}
			$artist_name = '';
			if (isset($track->artists[0]->name)) {
				$artist_name = $track->artists[0]->name;
			}
			$album_name = '';
			if (isset($track->album->name)) {
				$album_name = $track->album->name;
			}
			$duration = 'na';
			if (isset($track->duration_ms)) {
				$duration = beautifyTime($track->duration_ms / 1000);
			}
			if (isset($track->duration)) {
				$duration = $track->duration;
			}
			$track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false);
			$w->result(null, serialize(array(
						$track->uri /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'play_track_from_play_queue' /* other_action */ ,
						escapeQuery($artist_name) /* artist_name */ ,
						escapeQuery($track_name) /* track_name */ ,
						escapeQuery($album_name) /* album_name */ ,
						$track_artwork /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist_name /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), $added . escapeQuery($artist_name) . " ● " . escapeQuery($track_name), array(
					$duration . " ● " . escapeQuery($album_name),
					'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
					'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
					'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
					'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...',
					'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online'
				), $track_artwork, 'yes', null, '');
			$noresult      = false;
			$added = '';
			$nb_tracks += 1;
		}

		if ($noresult) {
			$w->result(null, 'help', "There is no track in the play queue", "Make sure to always use the workflow to launch tracks, playlists, etc..Internet connectivity is also required", './images/warning.png', 'no', null, '');
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Learn more about Play Queue', "Find out all information about Play Queue on alfred-spotify-mini-player.com", './images/website.png', 'yes', null, '');
			echo $w->toxml();
		}
	}
}


/**
 * firstDelimiterBrowse function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$country_code = $settings->country_code;

	$w->result(null, '', getCountryName($country_code), 'Browse the Spotify categories in ' . getCountryName($country_code), './images/browse.png', 'no', null, 'Browse▹' . $country_code . '▹');

	if ($country_code != 'US') {
		$w->result(null, '', getCountryName('US'), 'Browse the Spotify categories in ' . getCountryName('US'), './images/browse.png', 'no', null, 'Browse▹US▹');
	}

	if ($country_code != 'GB') {
		$w->result(null, '', getCountryName('GB'), 'Browse the Spotify categories in ' . getCountryName('GB'), './images/browse.png', 'no', null, 'Browse▹GB▹');
	}

	$w->result(null, '', 'Choose Another country', 'Browse the Spotify categories in another country of your choice', './images/browse.png', 'no', null, 'Browse▹Choose a Country▹');
}

/**
 * firstDelimiterYourTops function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function firstDelimiterYourTops($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$w->result(null, '', 'Get your top artists (last 4 weeks)', 'Get your top artists for last 4 weeks', './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹short_term');

	$w->result(null, '', 'Get your top artists (last 6 months)', 'Get your top artists for last 6 months', './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹medium_term');

	$w->result(null, '', 'Get your top artists (all time)', 'Get your top artists for all time', './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹long_term');

	$w->result(null, '', 'Get your top tracks (last 4 weeks)', 'Get your top tracks for last 4 weeks', './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹short_term');

	$w->result(null, '', 'Get your top tracks (last 6 months)', 'Get your top tracks for last 6 months', './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹medium_term');

	$w->result(null, '', 'Get your top tracks (all time)', 'Get your top tracks for all time', './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹long_term');
}

/**
 * secondDelimiterArtists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterArtists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$is_public_playlists       = $settings->is_public_playlists;
	$use_mopidy                = $settings->use_mopidy;

	//
	// display tracks for selected artists
	//
	$tmp         = explode('∙', $words[1]);
	$artist_uri  = $tmp[0];
	$artist_name = $tmp[1];
	$track       = $words[2];

	$href = explode(':', $artist_uri);
	if ($href[1] == 'track') {
		$track_uri  = $artist_uri;
		$artist_uri = getArtistUriFromTrack($w, $track_uri);
		if ($artist_uri == false) {
			$w->result(null, 'help', "The artist cannot be retrieved from track uri", 'URI was ' . $tmp[0], './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
	if ($href[1] == 'local') {
		$artist_uri = getArtistUriFromSearch($w, $href[2], $country_code);
		if ($artist_uri == false) {
			$w->result(null, 'help', "The artist cannot be retrieved from local track uri", 'URI was ' . $tmp[0], './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
	if (mb_strlen($track) < 2) {
		$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false);
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					$artist_uri /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'playartist' /* other_action */ ,
					$artist_name /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					$artist_artwork_path /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "👤 " . $artist_name, 'Play artist', $artist_artwork_path, 'yes', null, '');
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					$artist_uri /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'lookup_artist' /* other_action */ ,
					$artist_name /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "👤 " . $artist_name, '☁︎ Query all albums/tracks from this artist online..', './images/online_artist.png', 'yes', null, '');


		$w->result(null, '', "Display biography", "This will display the artist biography, twitter and official website", './images/biography.png', 'no', null, "Biography▹" . $artist_uri . '∙' . escapeQuery($artist_name) . '▹');

		$w->result(null, '', 'Follow/Unfollow Artist', 'Display options to follow/unfollow the artist', './images/follow.png', 'no', null, "Follow/Unfollow▹" . $artist_uri . "@" . $artist_name . '▹');

		$w->result(null, '', 'Related Artists', 'Browse related artists', './images/related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name . '▹');

		if ($update_in_progress == false) {
			$privacy_status = 'private';
			if ($is_public_playlists) {
				$privacy_status = 'public';
			}
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						$artist_uri /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'radio_artist' /* other_action */ ,
						$artist_name /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Create a Radio Playlist for ' . $artist_name, 'This will create a ' . $privacy_status . ' radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
		}

		if ($update_in_progress == false) {
			$privacy_status = 'private';
			if ($is_public_playlists) {
				$privacy_status = 'public';
			}
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						$artist_uri /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'complete_collection_artist' /* other_action */ ,
						$artist_name /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Create a Complete Collection Playlist for ' . $artist_name, 'This will create a ' . $privacy_status . ' playlist for the artist with all the albums and singles', './images/complete_collection.png', 'yes', null, '');
		}

		if ($all_playlists == false || count($tmp) == 3) {
			$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and artist_uri=:artist_uri limit " . $max_results;
		} else {
			$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where artist_uri=:artist_uri limit " . $max_results;
		}
		$stmt = $db->prepare($getTracks);
		$stmt->bindValue(':artist_uri', $artist_uri);
	} else {
		if ($all_playlists == false || count($tmp) == 3) {
			$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_uri=:artist_uri and track_name like :track)" . " limit " . $max_results;
		} else {
			$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where artist_uri=:artist_uri and track_name like :track limit " . $max_results;
		}
		$stmt = $db->prepare($getTracks);
		$stmt->bindValue(':artist_uri', $artist_uri);
		$stmt->bindValue(':track', '%' . $track . '%');
	}
	$tracks = $stmt->execute();
	$noresult = true;
	while ($track = $stmt->fetch()) {
		// if ($noresult) {
		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		// }
		$noresult = false;
		$subtitle = $track[6];

		$added = '';
		if ($track[18] == true) {
			if ($use_mopidy) {
				// skip local tracks if using Mopidy
				continue;
			}
			$added = '📌 ';
		}
		if (checkIfResultAlreadyThere($w->results(), $added . ucfirst($track[7]) . " ● " . $track[5]) == false) {
			if ($track[14] == true) {
				$w->result(null, serialize(array(
							$track[2] /*track_uri*/ ,
							$track[3] /* album_uri */ ,
							$track[4] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'' /* other_action */ ,
							$track[7] /* artist_name */ ,
							$track[5] /* track_name */ ,
							$track[6] /* album_name */ ,
							$track[9] /* track_artwork_path */ ,
							$track[10] /* artist_artwork_path */ ,
							$track[11] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), $added . ucfirst($track[7]) . " ● " . $track[5], array(
						$track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ...',
						'shift' => 'Add album ' . $track[6] . ' to ...',
						'ctrl' => 'Search artist ' . $track[7] . ' online'
					), $track[9], 'yes', null, '');
			} else {
				$w->result(null, '', '🚫 ' . ucfirst($track[7]) . " ● " . $track[5], $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
			}
		}
	}

	if ($noresult) {
		if (mb_strlen($track) < 2) {
			$w->result(null, 'help', "There is no track in your library for the artist " . escapeQuery($artist_name), "Choose one of the options above", './images/info.png', 'no', null, '');
		} else {
			$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
		}
	}

	if (! $use_mopidy) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'artist:' . $artist_name /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'' /* other_action */ ,

					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Search for artist " . $artist_name . " in Spotify", array(
				'This will start a new search in Spotify',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/spotify.png', 'yes', null, '');
	}
}


/**
 * secondDelimiterAlbums function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterAlbums($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$use_mopidy                = $settings->use_mopidy;

	//
	// display tracks for selected album
	//
	$tmp        = explode('∙', $words[1]);
	$album_uri  = $tmp[0];
	$album_name = $tmp[1];
	$track      = $words[2];

	$href = explode(':', $album_uri);
	if ($href[1] == 'track' || $href[1] == 'local') {
		$track_uri = $album_uri;
		$album_uri = getAlbumUriFromTrack($w, $track_uri);
		if ($album_uri == false) {
			$w->result(null, 'help', "The album cannot be retrieved from track uri", 'URI was ' . $tmp[0], './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}


	try {
		if (mb_strlen($track) < 2) {
			if ($all_playlists == false || count($tmp) == 3) {
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and album_uri=:album_uri limit " . $max_results;
			} else {
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where album_uri=:album_uri limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':album_uri', $album_uri);
		} else {
			if ($all_playlists == false || count($tmp) == 3) {
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (album_uri=:album_uri and track_name like :track limit " . $max_results;
			} else {
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where album_uri=:album_uri and track_name like :track limit " . $max_results;
			}
			$stmt = $db->prepare($getTracks);
			$stmt->bindValue(':album_uri', $album_uri);
			$stmt->bindValue(':track', '%' . $track . '%');
		}

		$tracks = $stmt->execute();

	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false);
	$w->result(null, serialize(array(
				'' /*track_uri*/ ,
				$album_uri /* album_uri */ ,
				'' /* artist_uri */ ,
				'' /* playlist_uri */ ,
				'' /* spotify_command */ ,
				'' /* query */ ,
				'' /* other_settings*/ ,
				'playalbum' /* other_action */ ,

				'' /* artist_name */ ,
				'' /* track_name */ ,
				$album_name /* album_name */ ,
				'' /* track_artwork_path */ ,
				'' /* artist_artwork_path */ ,
				$album_artwork_path /* album_artwork_path */ ,
				'' /* playlist_name */ ,
				'' /* playlist_artwork_path */
			)), "💿 " . $album_name, 'Play album', $album_artwork_path, 'yes', null, '');

	try {
		$getArtist     = "select artist_uri,artist_name from tracks where album_uri=:album_uri limit 1";
		$stmtGetArtist = $db->prepare($getArtist);
		$stmtGetArtist->bindValue(':album_uri', $album_uri);
		$tracks_artist = $stmtGetArtist->execute();
		$onetrack      = $stmtGetArtist->fetch();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	$w->result(null, '', "💿 " . ucfirst($album_name), '☁︎ Query all tracks from this album online..', './images/online_album.png', 'no', null, "Online▹" . $onetrack[0] . '@' . $onetrack[1] . '@' . $album_uri . '@' . $album_name . '▹');

	if ($update_in_progress == false) {
		$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add the album to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
	}
	$noresult = true;
	while ($track = $stmt->fetch()) {
		// if ($noresult == true) {
		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		// }
		$noresult = false;
		$subtitle = $track[6];

		$added = '';
		if ($track[18] == true) {
			if ($use_mopidy) {
				// skip local tracks if using Mopidy
				continue;
			}
			$added = '📌 ';
		}
		if (checkIfResultAlreadyThere($w->results(), $added . ucfirst($track[7]) . " ● " . $track[5]) == false) {
			if ($track[14] == true) {
				$w->result(null, serialize(array(
							$track[2] /*track_uri*/ ,
							$track[3] /* album_uri */ ,
							$track[4] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'play_track_in_album_context' /* other_action */ ,
							$track[7] /* artist_name */ ,
							$track[5] /* track_name */ ,
							$track[6] /* album_name */ ,
							$track[9] /* track_artwork_path */ ,
							$track[10] /* artist_artwork_path */ ,
							$track[11] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), $added . ucfirst($track[7]) . " ● " . $track[5], array(
						$track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ...',
						'shift' => 'Add album ' . $track[6] . ' to ...',
						'ctrl' => 'Search artist ' . $track[7] . ' online'
					), $track[9], 'yes', null, '');
			} else {
				$w->result(null, '', '🚫 ' . ucfirst($track[7]) . " ● " . $track[5], $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
			}
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
	}

	if (! $use_mopidy) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'album:' . $album_name /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Search for album " . $album_name . " in Spotify", array(
				'This will start a new search in Spotify',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/spotify.png', 'yes', null, '');
	}
}


/**
 * secondDelimiterPlaylists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterPlaylists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$use_mopidy                = $settings->use_mopidy;

	//
	// display tracks for selected playlist
	//
	$theplaylisturi = $words[1];
	$thetrack       = $words[2];
	$getPlaylists   = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where uri=:uri";

	try {
		$stmt = $db->prepare($getPlaylists);
		$stmt->bindValue(':uri', $theplaylisturi);

		$playlists        = $stmt->execute();
		$noresultplaylist = true;
		while ($playlist = $stmt->fetch()) {
			$noresultplaylist = false;
			if (mb_strlen($thetrack) < 2) {
				if ($playlist[9]) {
					$public_status = 'collaborative';
				} else {
					if ($playlist[10]) {
						$public_status = 'public';
					} else {
						$public_status = 'private';
					}
				}
				if ($playlist[10]) {
					$public_status_contrary = 'private';
				} else {
					$public_status_contrary = 'public';
				}
				$subtitle = "Launch Playlist";
				$subtitle = $subtitle . " ,⇧ ▹ add playlist to ..., ⌘ ▹ change playlist privacy to " . $public_status_contrary;
				$added = ' ';
				if ($userid == $playlist[4] && $public_status != 'collaborative') {
					$cmdMsg = 'Change playlist privacy to ' . $public_status_contrary;
				} else {
					$cmdMsg = 'Not Available';
				}
				if (startswith($playlist[1], 'Artist radio for')) {
					$added = '📻 ';
				}
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							$playlist[0] /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'set_playlist_privacy_to_' . $public_status_contrary /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							$playlist[1] /* playlist_name */ ,
							$playlist[5] /* playlist_artwork_path */
						)), "🎵" . $added . ucfirst($playlist[1]) . " by " . $playlist[3] . " ● " . $playlist[7] . " tracks ● " . $playlist[8], array(
						$subtitle,
						'alt' => 'Not Available',
						'cmd' => $cmdMsg,
						'shift' => 'Add playlist ' . ucfirst($playlist[1]) . ' to ...',
						'fn' => 'Not Available',
						'ctrl' => 'Not Available'
					), $playlist[5], 'yes', null, '');
				if (! $use_mopidy) {
					$w->result(null, serialize(array(
								'' /*track_uri*/ ,
								'' /* album_uri */ ,
								'' /* artist_uri */ ,
								'' /* playlist_uri */ ,
								'activate (open location "' . $playlist[0] . '")' /* spotify_command */ ,
								'' /* query */ ,
								'' /* other_settings*/ ,
								'' /* other_action */ ,

								'' /* artist_name */ ,
								'' /* track_name */ ,
								'' /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								'' /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), "Open playlist " . escapeQuery($playlist[1]) . " in Spotify", "This will open the playlist in Spotify", './images/spotify.png', 'yes', null, '');
				}

				if ($update_in_progress == false) {
					$w->result(null, '', 'Add playlist ' . escapeQuery($playlist[1]) . ' to...', 'This will add the playlist to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $playlist[0] . '∙' . escapeQuery($playlist[1]) . '▹');
				}

				if ($update_in_progress == false) {
					$w->result(null, '', 'Remove playlist ' . escapeQuery($playlist[1]), 'A confirmation will be asked in next step', './images/uncheck.png', 'no', null, 'Confirm Remove Playlist▹' . $playlist[0] . '∙' . escapeQuery($playlist[1]) . '▹');
				}
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where playlist_uri=:theplaylisturi order by added_at desc limit " . $max_results;
				$stmt      = $db->prepare($getTracks);
				$stmt->bindValue(':theplaylisturi', $theplaylisturi);
			} else {
				$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where playlist_uri=:theplaylisturi and (artist_name like :track or album_name like :track or track_name like :track)" . " order by added_at desc limit " . $max_results;
				$stmt      = $db->prepare($getTracks);
				$stmt->bindValue(':theplaylisturi', $theplaylisturi);
				$stmt->bindValue(':track', '%' . $thetrack . '%');
			}

			$tracks = $stmt->execute();
			$noresult = true;
			while ($track = $stmt->fetch()) {
				// if ($noresult) {
				//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
				//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
				// }
				$noresult = false;
				$subtitle = $track[6];
				$added = '';
				if ($track[18] == true) {
					if ($use_mopidy) {
						// skip local tracks if using Mopidy
						continue;
					}
					$added = '📌 ';
				}
				if (checkIfResultAlreadyThere($w->results(), $added . ucfirst($track[7]) . " ● " . $track[5]) == false) {
					if ($track[14] == true) {
						$w->result(null, serialize(array(
									$track[2] /*track_uri*/ ,
									$track[3] /* album_uri */ ,
									$track[4] /* artist_uri */ ,
									$theplaylisturi /* playlist_uri */ ,
									'' /* spotify_command */ ,
									'' /* query */ ,
									'' /* other_settings*/ ,
									'' /* other_action */ ,
									$track[7] /* artist_name */ ,
									$track[5] /* track_name */ ,
									$track[6] /* album_name */ ,
									$track[9] /* track_artwork_path */ ,
									$track[10] /* artist_artwork_path */ ,
									$track[11] /* album_artwork_path */ ,
									$playlist[1] /* playlist_name */ ,
									'' /* playlist_artwork_path */
								)), $added . ucfirst($track[7]) . " ● " . $track[5], array(
								$track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
								'alt' => 'Play album ' . $track[6] . ' in Spotify',
								'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
								'fn' => 'Add track ' . $track[5] . ' to ...',
								'shift' => 'Add album ' . $track[6] . ' to ...',
								'ctrl' => 'Search artist ' . $track[7] . ' online'
							), $track[9], 'yes', null, '');
					} else {
						$w->result(null, '', '🚫 ' . ucfirst($track[7]) . " ● " . $track[5], $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
					}
				}
			}

			if ($noresult) {
				$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');

			}
		}

		// can happen only with Alfred Playlist deleted
		if ($noresultplaylist) {
			$w->result(null, 'help', "It seems your Alfred Playlist was deleted", "Choose option below to change it", './images/warning.png', 'no', null, '');
			$w->result(null, '', "Change your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
		}
	}


	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}
}


/**
 * secondDelimiterOnline function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterOnline($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '@') == 1) {
		//
		// Search Artist Online
		//
		$tmp        = $words[1];
		$words      = explode('@', $tmp);
		$artist_uri = $words[0];
		$tmp_uri    = explode(':', $artist_uri);

		$artist_name = $words[1];

		$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false);
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					$artist_uri /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'playartist' /* other_action */ ,
					$artist_name /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					$artist_artwork_path /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "👤 " . escapeQuery($artist_name), 'Play artist', $artist_artwork_path, 'yes', null, '');

		$w->result(null, '', "Display biography", "This will display the artist biography, twitter and official website", './images/biography.png', 'no', null, "Biography▹" . $artist_uri . '∙' . escapeQuery($artist_name) . '▹');

		$w->result(null, '', 'Follow/Unfollow Artist', 'Display options to follow/unfollow the artist', './images/follow.png', 'no', null, "Follow/Unfollow▹" . $artist_uri . "@" . $artist_name . '▹');

		$w->result(null, '', 'Related Artists', 'Browse related artists', './images/related.png', 'no', null, "OnlineRelated▹" . $artist_uri . "@" . $artist_name . '▹');

		if ($update_in_progress == false) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						$artist_uri /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'radio_artist' /* other_action */ ,
						$artist_name /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'Create a Radio Playlist for ' . $artist_name, 'This will create a radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
		}

		// call to web api, if it fails,
		// it displays an error in main window
		$albums = getTheArtistAlbums($w, $artist_uri, $country_code);

		$w->result(null, 'help', "Select an album below to browse it", 'singles and compilations are also displayed', './images/info.png', 'no', null, '');

		$noresult = true;
		foreach ($albums as $album) {
			if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name) . ' (' . count($album->tracks->items) . ' tracks)') == false) {
				$noresult = false;
				$genre    = (count($album->genres) > 0) ? ' ● Genre: ' . implode('|', $album->genres) : '';
				$tracks   = $album->tracks;
				$w->result(null, '', ucfirst($album->name) . ' (' . count($album->tracks->items) . ' tracks)', $album->album_type . " by " . $artist_name . ' ● Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w, $album->uri, false), 'no', null, "Online▹" . $artist_uri . "@" . $artist_name . "@" . $album->uri . "@" . $album->name . '▹');
			}
		}

		if ($noresult) {
			$w->result(null, 'help', "There is no album for this artist", "", './images/warning.png', 'no', null, '');
		}

	} elseif (substr_count($query, '@') == 3) {
		//
		// Search Album Online
		//
		$tmp         = $words[1];
		$words       = explode('@', $tmp);
		$artist_uri  = $words[0];
		$artist_name = $words[1];
		$album_uri   = $words[2];
		$album_name  = $words[3];

		$href = explode(':', $album_uri);
		if ($href[1] == 'track' || $href[1] == 'local') {
			$track_uri = $album_uri;
			$album_uri = getAlbumUriFromTrack($w, $track_uri);
			if ($album_uri == false) {
				$w->result(null, 'help', "The album cannot be retrieved from track uri", 'URI was ' . $track_uri, './images/warning.png', 'no', null, '');
				echo $w->toxml();
				return;
			}
		}
		$href = explode(':', $artist_uri);
		if ($href[1] == 'track') {
			$track_uri  = $artist_uri;
			$artist_uri = getArtistUriFromTrack($w, $track_uri);
			if ($artist_uri == false) {
				$w->result(null, 'help', "The artist cannot be retrieved from track uri", 'URI was ' . $track_uri, './images/warning.png', 'no', null, '');
				echo $w->toxml();
				return;
			}
		}
		$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false);
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					$album_uri /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'playalbum' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					$album_name /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					$album_artwork_path /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "💿 " . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');

		if ($update_in_progress == false) {
			$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add the album to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
		}

		// call to web api, if it fails,
		// it displays an error in main window
		$tracks = getTheAlbumFullTracks($w, $album_uri);
		$noresult = true;
		foreach ($tracks as $track) {
			// if ($noresult == true) {
			//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
			//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
			//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
			// }
			$track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false);
			if (isset($track->is_playable) && $track->is_playable) {
				$noresult      = false;
				$w->result(null, serialize(array(
							$track->uri /*track_uri*/ ,
							$album_uri /* album_uri */ ,
							$artist_uri /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'play_track_in_album_context' /* other_action */ ,
							$artist_name /* artist_name */ ,
							$track->name /* track_name */ ,
							$album_name /* album_name */ ,
							$track_artwork /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), escapeQuery(ucfirst($artist_name)) . " ● " . escapeQuery($track->name), array(
						beautifyTime($track->duration_ms / 1000) . " ● " . $album_name,
						'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
						'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify',
						'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
						'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...',
						'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online'
					), $track_artwork, 'yes', null, '');
			} else {
				$w->result(null, '', '🚫 ' . escapeQuery(ucfirst($artist_name)) . " ● " . escapeQuery($track->name), beautifyTime($track->duration_ms / 1000) . " ● " . $album_name, $track_artwork, 'no', null, '');
			}
		}
	}
}


/**
 * secondDelimiterOnlineRelated function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterOnlineRelated($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '@') == 1) {
		//
		// Search Related Artist Online
		//
		$tmp         = $words[1];
		$words       = explode('@', $tmp);
		$artist_uri  = $words[0];
		$artist_name = $words[1];

		// call to web api, if it fails,
		// it displays an error in main window
		$relateds = getTheArtistRelatedArtists($w, trim($artist_uri));

		foreach ($relateds as $related) {
			$w->result(null, '', "👤 " . ucfirst($related->name), '☁︎ Query all albums/tracks from this artist online..', getArtistArtwork($w, $related->uri, $related->name, false), 'no', null, "Online▹" . $related->uri . "@" . $related->name . '▹');
		}
	}
}


/**
 * secondDelimiterOnlinePlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterOnlinePlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$is_public_playlists       = $settings->is_public_playlists;
	$use_mopidy                = $settings->use_mopidy;

	//
	// display tracks for selected online playlist
	//
	$tmp            = explode('∙', $words[1]);
	$theplaylisturi = $tmp[0];
	$url            = explode(':', $theplaylisturi);
	$owner_id       = $url[2];
	$playlist_id    = $url[4];

	$theplaylistname     = $tmp[1];
	$thetrack            = $words[2];
	$savedPlaylistTracks = array();
	$duration_playlist   = 0;
	$nb_tracks           = 0;
	try {
		$api                         = getSpotifyWebAPI($w);
		$offsetGetUserPlaylistTracks = 0;
		$limitGetUserPlaylistTracks  = 100;
		do {
			// refresh api
			$api                = getSpotifyWebAPI($w, false, $api);
			$userPlaylistTracks = $api->getUserPlaylistTracks($owner_id, $playlist_id, array(
					'fields' => array(
						'total',
						'items(added_at)',
						'items(is_local)',
						'items.track(is_playable,duration_ms,uri,popularity,name)',
						'items.track.album(album_type,images,uri,name)',
						'items.track.artists(name,uri)'
					),
					'limit' => $limitGetUserPlaylistTracks,
					'offset' => $offsetGetUserPlaylistTracks,
					'market' => $country_code
				));

			foreach ($userPlaylistTracks->items as $item) {
				$track = $item->track;
				$savedPlaylistTracks[] = $item;
				$nb_tracks += 1;
				$duration_playlist += $track->duration_ms;
			}
			$offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
		} while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}

	$subtitle = "Launch Playlist";
	if ($is_alfred_playlist_active == true) {
		$subtitle = "$subtitle ,⇧ ▹ add playlist to ...";
	}
	$playlist_artwork_path = getPlaylistArtwork($w, $theplaylisturi, false);
	$w->result(null, serialize(array(
				'' /*track_uri*/ ,
				'' /* album_uri */ ,
				'' /* artist_uri */ ,
				$theplaylisturi /* playlist_uri */ ,
				'' /* spotify_command */ ,
				'' /* query */ ,
				'' /* other_settings*/ ,
				'' /* other_action */ ,

				'' /* artist_name */ ,
				'' /* track_name */ ,
				'' /* album_name */ ,
				'' /* track_artwork_path */ ,
				'' /* artist_artwork_path */ ,
				'' /* album_artwork_path */ ,
				$theplaylistname /* playlist_name */ ,
				$playlist_artwork_path /* playlist_artwork_path */ ,
				$alfred_playlist_name
				/* alfred_playlist_name */
			)), "🎵" . ucfirst($theplaylistname) . " by " . $owner_id . " ● " . $nb_tracks . " tracks ● " . beautifyTime($duration_playlist / 1000, true), array(
			$subtitle,
			'alt' => 'Not Available',
			'cmd' => 'Not Available',
			'shift' => 'Add playlist ' . ucfirst($theplaylistname) . ' to your Alfred Playlist',
			'fn' => 'Not Available',
			'ctrl' => 'Not Available'
		), $playlist_artwork_path, 'yes', null, '');

	if (! $use_mopidy) {
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'activate (open location "' . $theplaylisturi . '")' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'' /* other_action */ ,

					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Open playlist " . $theplaylistname . " in Spotify", "This will open the playlist in Spotify", './images/spotify.png', 'yes', null, '');
	}
	if ($update_in_progress == false) {
		$added = 'privately';
		$privacy_status = 'private';
		if ($is_public_playlists) {
			$added = 'publicly';
			$privacy_status = 'public';
		}
		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					$theplaylisturi /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'follow_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					$theplaylistname /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Follow ' . $added . ' playlist ' . $theplaylistname , "This will add the playlist (marked as " . $privacy_status . ") to your library", './images/follow.png', 'yes', null, '');
	}

	$noresult   = true;
	$nb_results = 0;
	foreach ($savedPlaylistTracks as $item) {
		if ($nb_results > $max_results) {
			break;
		}
		$track = $item->track;
		// if ($noresult) {
		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		// }
		$noresult = false;
		$artists  = $track->artists;
		$artist   = $artists[0];
		$album    = $track->album;

		$track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false);
		if (isset($track->is_playable) && $track->is_playable) {
			$w->result(null, serialize(array(
						$track->uri /*track_uri*/ ,
						$album->uri /* album_uri */ ,
						$artist->uri /* artist_uri */ ,
						$theplaylisturi /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'' /* other_action */ ,
						escapeQuery($artist->name) /* artist_name */ ,
						escapeQuery($track->name) /* track_name */ ,
						escapeQuery($album->name) /* album_name */ ,
						$track_artwork_path /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), ucfirst(escapeQuery($artist->name)) . " ● " . escapeQuery($track->name), array(
					beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album->name),
					'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify',
					'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify',
					'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
					'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...',
					'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online'
				), $track_artwork_path, 'yes', null, '');
			$nb_results++;
		} else {
			$added = '';
			if (isset($item->is_local) && $item->is_local) {
				$added = '📌 ';
			} else {
				$added = '🚫 ';
			}
			$w->result(null, '', $added . ucfirst(escapeQuery($artist->name)) . " ● " . escapeQuery($track->name), beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album->name), $track_artwork_path, 'no', null, '');
			$nb_results++;
		}
	}
}


/**
 * secondDelimiterYourMusicTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterYourMusicTracks($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$use_mopidy                = $settings->use_mopidy;

	//
	// display tracks for Your Music
	//
	$thetrack = $words[2];

	if (mb_strlen($thetrack) < 2) {
		$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 order by added_at desc limit " . $max_results;
		$stmt      = $db->prepare($getTracks);
	} else {
		$getTracks = "select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name like :track or album_name like :track or track_name like :track)" . " order by added_at desc limit " . $max_results;
		$stmt      = $db->prepare($getTracks);
		$stmt->bindValue(':track', '%' . $thetrack . '%');
	}

	$tracks = $stmt->execute();

	$noresult = true;
	while ($track = $stmt->fetch()) {
		// if ($noresult) {
		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
		// }
		$noresult = false;
		$subtitle = $track[6];

		$added = '';
		if ($track[18] == true) {
			if ($use_mopidy) {
				// skip local tracks if using Mopidy
				continue;
			}
			$added = '📌 ';
		}
		if (checkIfResultAlreadyThere($w->results(), $added . ucfirst($track[7]) . " ● " . $track[5]) == false) {
			if ($track[14] == true) {
				$w->result(null, serialize(array(
							$track[2] /*track_uri*/ ,
							$track[3] /* album_uri */ ,
							$track[4] /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'' /* other_action */ ,
							$track[7] /* artist_name */ ,
							$track[5] /* track_name */ ,
							$track[6] /* album_name */ ,
							$track[9] /* track_artwork_path */ ,
							$track[10] /* artist_artwork_path */ ,
							$track[11] /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), $added . ucfirst($track[7]) . " ● " . $track[5], array(
						$track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]),
						'alt' => 'Play album ' . $track[6] . ' in Spotify',
						'cmd' => 'Play artist ' . $track[7] . ' in Spotify',
						'fn' => 'Add track ' . $track[5] . ' to ...',
						'shift' => 'Add album ' . $track[6] . ' to ...',
						'ctrl' => 'Search artist ' . $track[7] . ' online'
					), $track[9], 'yes', null, '');
			} else {
				$w->result(null, '', '🚫 ' . ucfirst($track[7]) . " ● " . $track[5], $track[16] . " ● " . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
			}
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');

	}

	if (mb_strlen($thetrack) > 0) {
		if (! $use_mopidy) {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						$thetrack  /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Search for " . $thetrack . " in Spotify", array(
					'This will start a new search in Spotify',
					'alt' => 'Not Available',
					'cmd' => 'Not Available',
					'shift' => 'Not Available',
					'fn' => 'Not Available',
					'ctrl' => 'Not Available'
				), './images/spotify.png', 'yes', null, '');
		}

		$w->result(null, null, "Search for " . $thetrack . " online", array(
				'This will search online, i.e not in your library',
				'alt' => 'Not Available',
				'cmd' => 'Not Available',
				'shift' => 'Not Available',
				'fn' => 'Not Available',
				'ctrl' => 'Not Available'
			), './images/online.png', 'no', null, 'Search Online▹' . $thetrack);
	}
}


/**
 * secondDelimiterYourMusicAlbums function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterYourMusicAlbums($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Search albums
	//
	$album = $words[2];
	try {
		if (mb_strlen($album) < 2) {
			$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic=1" . " group by album_name order by max(added_at) desc limit " . $max_results;
			$stmt      = $db->prepare($getTracks);
		} else {
			$getTracks = "select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic=1 and album_name like :query group by album_name order by max(added_at) desc limit " . $max_results;
			$stmt      = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $album . '%');
		}

		$tracks = $stmt->execute();

	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	// display all albums
	$noresult = true;
	while ($track = $stmt->fetch()) {
		$noresult        = false;
		$nb_album_tracks = getNumberOfTracksForAlbum($db, $track[3], true);
		if (checkIfResultAlreadyThere($w->results(), ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)') == false) {
			$w->result(null, '', ucfirst($track[0]) . ' (' . $nb_album_tracks . ' tracks)', $track[4] . ' by ' . $track[2], $track[1], 'no', null, "Album▹" . $track[3] . '∙' . $track[0] . '∙' . ' ★ ' . "▹");
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
	}
}

/**
 * secondDelimiterYourTopArtists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterYourTopArtists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];
	$time_range  = $words[2];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;


	try {
		$api               = getSpotifyWebAPI($w);
		$topArtists        = $api->getMyTop('artists',array(
				'time_range' => $time_range,
				'limit' => ($max_results <= 50) ? $max_results : 50,
			));

		$items = $topArtists->items;
		$noresult = true;
		foreach ($items as $artist) {
            $noresult         = false;
            $w->result(null, '', "👤 " . ucfirst($artist->name), "Browse this artist", getArtistArtwork($w, $artist->uri, $artist->name, false), 'no', null, "Artist▹" . $artist->uri . '∙' . $artist->name . '∙' . "▹");


		}

    	if ($noresult) {
    		$w->result(null, 'help', "There is no result for your top artists", "", './images/warning.png', 'no', null, '');
    	}

	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}

}

/**
 * secondDelimiterYourTopTracks function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterYourTopTracks($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];
	$time_range  = $words[2];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;


	try {
		$api               = getSpotifyWebAPI($w);
		$topTracks        = $api->getMyTop('tracks',array(
				'time_range' => $time_range,
				'limit' => ($max_results <= 50) ? $max_results : 50,
			));

		$noresult = true;

        $items = $topTracks->items;
    	foreach ($items as $track) {
    		// if ($noresult) {
    		//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
    		//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
    		//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
    		// }
    		$noresult = false;
    		$artists  = $track->artists;
    		$artist   = $artists[0];
    		$album    = $track->album;

    		$track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false);
    		if (isset($track->is_playable) && $track->is_playable) {
    			$w->result(null, serialize(array(
    						$track->uri /*track_uri*/ ,
    						$album->uri /* album_uri */ ,
    						$artist->uri /* artist_uri */ ,
    						$theplaylisturi /* playlist_uri */ ,
    						'' /* spotify_command */ ,
    						'' /* query */ ,
    						'' /* other_settings*/ ,
    						'' /* other_action */ ,
    						escapeQuery($artist->name) /* artist_name */ ,
    						escapeQuery($track->name) /* track_name */ ,
    						escapeQuery($album->name) /* album_name */ ,
    						$track_artwork_path /* track_artwork_path */ ,
    						'' /* artist_artwork_path */ ,
    						'' /* album_artwork_path */ ,
    						'' /* playlist_name */ ,
    						'' /* playlist_artwork_path */
    					)), ucfirst(escapeQuery($artist->name)) . " ● " . escapeQuery($track->name), array(
    					beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album->name),
    					'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify',
    					'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify',
    					'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
    					'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...',
    					'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online'
    				), $track_artwork_path, 'yes', null, '');
    			$nb_results++;
    		} else {
    			$added = '';
    			if (isset($item->is_local) && $item->is_local) {
    				$added = '📌 ';
    			} else {
    				$added = '🚫 ';
    			}
    			$w->result(null, '', $added . ucfirst(escapeQuery($artist->name)) . " ● " . escapeQuery($track->name), beautifyTime($track->duration_ms / 1000) . " ● " . escapeQuery($album->name), $track_artwork_path, 'no', null, '');
    			$nb_results++;
    		}
    	}

    	if ($noresult) {
    		$w->result(null, 'help', "There is no result for your top tracks", "", './images/warning.png', 'no', null, '');
    	}

	}
	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}

}

/**
 * secondDelimiterYourMusicArtists function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterYourMusicArtists($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	//
	// Search artists
	//
	$artist = $words[2];

	try {
		if (mb_strlen($artist) < 2) {
			$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where yourmusic=1 group by artist_name" . " limit " . $max_results;
			$stmt      = $db->prepare($getTracks);
		} else {
			$getTracks = "select artist_name,artist_artwork_path,artist_uri from tracks where yourmusic=1 and artist_name like :query limit " . $max_results;
			$stmt      = $db->prepare($getTracks);
			$stmt->bindValue(':query', '%' . $artist . '%');
		}

		$tracks = $stmt->execute();

	}


	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	// display all artists
	$noresult = true;
	while ($track = $stmt->fetch()) {
		$noresult         = false;
		$nb_artist_tracks = getNumberOfTracksForArtist($db, $track[0], true);
		if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)') == false) {
			$uri = $track[2];
			// in case of local track, pass track uri instead
			if($uri == '') {
				$uri = $track[3];
			}

			$w->result(null, '', "👤 " . ucfirst($track[0]) . ' (' . $nb_artist_tracks . ' tracks)', "Browse this artist", $track[1], 'no', null, "Artist▹" . $uri . '∙' . $track[0] . '∙' . ' ★ ' . "▹");
		}
	}

	if ($noresult) {
		$w->result(null, 'help', "There is no result for your search", "", './images/warning.png', 'no', null, '');
	}
}


/**
 * secondDelimiterSettings function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterSettings($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$setting_kind = $words[1];
	$the_query    = $words[2];

	if ($setting_kind == "MaxResults") {
		if (mb_strlen($the_query) == 0) {
			$w->result(null, '', "Enter the Max Results number (must be greater than 0):", "Recommendation is between 10 to 100", './images/settings.png', 'no', null, '');
		} else {
			// max results has been set
			if (is_numeric($the_query) == true && $the_query > 0) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'MAX_RESULTS▹' . $the_query /* other_settings*/ ,
							'' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Max Results will be set to <" . $the_query . ">", "Type enter to validate the Max Results", './images/settings.png', 'yes', null, '');
			} else {
				$w->result(null, '', "The Max Results value entered is not valid", "Please fix it", './images/warning.png', 'no', null, '');

			}
		}
	} elseif ($setting_kind == "RadioTracks") {
		if (mb_strlen($the_query) == 0) {
			$w->result(null, '', "Enter the number of tracks to get when creating a radio Playlist:", "Must be between 1 and 100", './images/settings.png', 'no', null, '');
		} else {
			// number radio tracks has been set
			if (is_numeric($the_query) == true && $the_query > 0 && $the_query <= 100) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'RADIO_TRACKS▹' . $the_query /* other_settings*/ ,
							'' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */ ,
							'' /* $alfred_playlist_name */
						)), "Number of Radio Tracks will be set to <" . $the_query . ">", "Type enter to validate the Radio Tracks number", './images/settings.png', 'yes', null, '');
			} else {
				$w->result(null, '', "The number of tracks value entered is not valid", "Please fix it, it must be a number between 1 and 100", './images/warning.png', 'no', null, '');

			}
		}
	}  elseif ($setting_kind == "VolumePercentage") {
		if (mb_strlen($the_query) == 0) {
			$w->result(null, '', "Enter the percentage of volume:", "Must be between 1 and 50", './images/settings.png', 'no', null, '');
		} else {
			// volume percent
			if (is_numeric($the_query) == true && $the_query > 0 && $the_query <= 50) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'VOLUME_PERCENT▹' . $the_query /* other_settings*/ ,
							'' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */ ,
							'' /* $alfred_playlist_name */
						)), "Volume Percentage will be set to <" . $the_query . ">", "Type enter to validate the Volume Percentage number", './images/settings.png', 'yes', null, '');
			} else {
				$w->result(null, '', "The number of volume percentage entered is not valid", "Please fix it, it must be a number between 1 and 50", './images/warning.png', 'no', null, '');

			}
		}
	} elseif ($setting_kind == "MopidyServer") {
		if (mb_strlen($the_query) == 0) {
			$w->result(null, '', "Enter the server name or IP where Mopidy server is running:", "Example: 192.168.0.5 or myserver.mydomain.mydomainextension", './images/settings.png', 'no', null, '');
		} else {
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'MOPIDY_SERVER▹' . $the_query /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */ ,
						'' /* $alfred_playlist_name */
					)), "Mopidy server will be set to <" . $the_query . ">", "Type enter to validate the Mopidy server name or IP", './images/settings.png', 'yes', null, '');
		}
	} elseif ($setting_kind == "MopidyPort") {
		if (mb_strlen($the_query) == 0) {
			$w->result(null, '', "Enter the TCP port number where Mopidy server is running:", "Must be a numeric value", './images/settings.png', 'no', null, '');
		} else {
			// tcp port has been set
			if (is_numeric($the_query) == true) {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'MOPIDY_PORT▹' . $the_query /* other_settings*/ ,
							'' /* other_action */ ,
							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */ ,
							'' /* $alfred_playlist_name */
						)), "Mopidy TCP port will be set to <" . $the_query . ">", "Type enter to validate the Mopidy TCP port number", './images/settings.png', 'yes', null, '');
			} else {
				$w->result(null, '', "The TCP port value entered is not valid", "Please fix it, it must be a numeric value", './images/warning.png', 'no', null, '');

			}
		}
	}
}


/**
 * secondDelimiterFeaturedPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterFeaturedPlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$country = $words[1];

	if ($country == 'Choose a Country') {
		// list taken from http://charts.spotify.com/docs
		$spotify_country_codes = array(
			"ar",
			"at",
			"au",
			"be",
			"bg",
			"ch",
			"cl",
			"co",
			"cr",
			"cz",
			"de",
			"dk",
			"ec",
			"ee",
			"es",
			"fi",
			"fr",
			"gb",
			"gr",
			"gt",
			"hk",
			"hu",
			"ie",
			"is",
			"it",
			"li",
			"lt",
			"lu",
			"lv",
			"mx",
			"my",
			"nl",
			"no",
			"nz",
			"pe",
			"pl",
			"pt",
			"se",
			"sg",
			"sk",
			"sv",
			"tr",
			"tw",
			"us",
			"uy"
		);
		foreach ($spotify_country_codes as $spotify_country_code) {
			if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {
				$w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the current featured playlists in ' . getCountryName(strtoupper($spotify_country_code)), './images/star.png', 'no', null, 'Featured Playlist▹' . strtoupper($spotify_country_code) . '▹');
			}
		}
	} else {
		try {
			$api               = getSpotifyWebAPI($w);
			$featuredPlaylists = $api->getFeaturedPlaylists(array(
					'country' => $country,
					'limit' => ($max_results <= 50) ? $max_results : 50,
					'offset' => 0,
				));

			$subtitle  = "Launch Playlist";
			$playlists = $featuredPlaylists->playlists;
			$w->result(null, '', $featuredPlaylists->message, '' . $playlists->total . ' playlists available', './images/info.png', 'no', null, '');
			$items = $playlists->items;
			foreach ($items as $playlist) {
				$w->result(null, '', "🎵" . escapeQuery($playlist->name), "by " . $playlist->owner->id . " ● " . $playlist->tracks->total . " tracks", getPlaylistArtwork($w, $playlist->uri, false), 'no', null, "Online Playlist▹" . $playlist->uri . '∙' . escapeQuery($playlist->name) . "▹");
			}

		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * secondDelimiterCharts function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterCharts($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$country = $words[1];

	if ($country == 'Choose a Country') {
		// list taken from http://charts.spotify.com/docs
		$spotify_country_codes = array(
			"ar",
			"at",
			"au",
			"be",
			"bg",
			"ch",
			"cl",
			"co",
			"cr",
			"cz",
			"de",
			"dk",
			"ec",
			"ee",
			"es",
			"fi",
			"fr",
			"gb",
			"gr",
			"gt",
			"hk",
			"hu",
			"ie",
			"is",
			"it",
			"li",
			"lt",
			"lu",
			"lv",
			"mx",
			"my",
			"nl",
			"no",
			"nz",
			"pe",
			"pl",
			"pt",
			"se",
			"sg",
			"sk",
			"sv",
			"tr",
			"tw",
			"us",
			"uy"
		);
		foreach ($spotify_country_codes as $spotify_country_code) {
			if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {
				$w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the current charts in ' . getCountryName(strtoupper($spotify_country_code)), './images/numbers.png', 'no', null, 'Charts▹' . strtoupper($spotify_country_code) . '▹');
			}
		}
	} else {
		$json = doJsonRequest($w, "http://charts.spotify.com/api/tracks/most_streamed/" . trim($country) . "/weekly/latest", false);

		$nb_results = 0;
		$noresult   = true;
		foreach ($json->tracks as $track) {
			if ($nb_results > $max_results) {
				break;
			}
			// if ($noresult) {
			//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
			//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
			//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
			// }
			// $noresult  = false;
			// format is https://play.spotify.com/track/3WBLQj2qtrKYFDcC5aisLD
			$href      = explode('/', $track->track_url);
			$track_uri = 'spotify:track:' . $href[4];

			$href      = explode('/', $track->album_url);
			$album_uri = 'spotify:album:' . $href[4];

			$href          = explode('/', $track->artist_url);
			$artist_uri    = 'spotify:artist:' . $href[4];
			$track_artwork = getTrackOrAlbumArtwork($w, $track_uri, false);
			$w->result(null, serialize(array(
						$track_uri /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						$artist_uri /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'' /* other_action */ ,
						escapeQuery($track->artist_name) /* artist_name */ ,
						escapeQuery($track->track_name) /* track_name */ ,
						escapeQuery($track->album_name) /* album_name */ ,
						$track_artwork /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), ucfirst(escapeQuery($track->track_name)) . " ● " . escapeQuery($track->artist_name), array(
					escapeQuery($track->album_name) . " ● " . $track->num_streams . ' streams',
					'alt' => 'Play album ' . escapeQuery($track->album_name) . ' in Spotify',
					'cmd' => 'Play artist ' . escapeQuery($track->artist_name) . ' in Spotify',
					'fn' => 'Add track ' . escapeQuery($track->track_name) . ' to ...',
					'shift' => 'Add album ' . escapeQuery($track->album_name) . ' to ...',
					'ctrl' => 'Search artist ' . escapeQuery($track->artist_name) . ' online'
				), $track_artwork, 'yes', null, '');
			$nb_results++;
		}
	}
}


/**
 * secondDelimiterNewReleases function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterNewReleases($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$country = $words[1];

	if ($country == 'Choose a Country') {
		// list taken from http://charts.spotify.com/docs
		$spotify_country_codes = array(
			"ar",
			"at",
			"au",
			"be",
			"bg",
			"ch",
			"cl",
			"co",
			"cr",
			"cz",
			"de",
			"dk",
			"ec",
			"ee",
			"es",
			"fi",
			"fr",
			"gb",
			"gr",
			"gt",
			"hk",
			"hu",
			"ie",
			"is",
			"it",
			"li",
			"lt",
			"lu",
			"lv",
			"mx",
			"my",
			"nl",
			"no",
			"nz",
			"pe",
			"pl",
			"pt",
			"se",
			"sg",
			"sk",
			"sv",
			"tr",
			"tw",
			"us",
			"uy"
		);
		foreach ($spotify_country_codes as $spotify_country_code) {
			if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {
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

			$noresult = true;
			foreach ($albums as $album) {
				if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name) . ' (' . count($album->tracks->items) . ' tracks)') == false) {
					$noresult = false;
					$genre    = (count($album->genres) > 0) ? ' ● Genre: ' . implode('|', $album->genres) : '';
					$tracks   = $album->tracks;
					$w->result(null, '', ucfirst($album->name) . ' (' . count($album->tracks->items) . ' tracks)', $album->album_type . " by " . $album->artists[0]->name . ' ● Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w, $album->uri, false), 'no', null, "New Releases▹" . $country . '▹' . $album->uri . "@" . $album->name);
				}
			}

			if ($noresult) {
				$w->result(null, 'help', "There is no album for this artist", "", './images/warning.png', 'no', null, '');
			}

		} elseif (substr_count($query, '@') == 1) {
			//
			// Search Album Online
			//
			$tmp        = $words[2];
			$words      = explode('@', $tmp);
			$album_uri  = $words[0];
			$album_name = $words[1];

			$album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false);
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'' /* other_settings*/ ,
						'playalbum' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						$album_name /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						$album_artwork_path /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "💿 " . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');

			if ($update_in_progress == false) {
				$w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', 'This will add the album to Your Music or a playlist you will choose in next step', './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
			}


			// call to web api, if it fails,
			// it displays an error in main window
			$tracks = getTheAlbumFullTracks($w, $album_uri);

			$noresult = true;
			foreach ($tracks as $track) {
				// if ($noresult == true) {
				//     $subtitle = "⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
				//     $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
				//     $w->result(null, 'help', "Select a track below to play it (or choose alternative described below)", $subtitle, './images/info.png', 'no', null, '');
				// }
				// $noresult           = false;
				$track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false);
				$w->result(null, serialize(array(
							$track->uri /*track_uri*/ ,
							$album_uri /* album_uri */ ,
							$track->artists[0]->uri /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'' /* other_settings*/ ,
							'play_track_in_album_context' /* other_action */ ,
							$track->artists[0]->name /* artist_name */ ,
							$track->name /* track_name */ ,
							$album_name /* album_name */ ,
							$track_artwork_path /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), escapeQuery(ucfirst($track->artists[0]->name)) . " ● " . escapeQuery($track->name), array(
						beautifyTime($track->duration_ms / 1000) . " ● " . $album_name,
						'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify',
						'cmd' => 'Play artist ' . escapeQuery($track->artists[0]->name) . ' in Spotify',
						'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...',
						'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...',
						'ctrl' => 'Search artist ' . escapeQuery($track->artists[0]->name) . ' online'
					), $track_artwork_path, 'yes', null, '');
			}
		}
	}
}


/**
 * secondDelimiterAdd function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterAdd($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$is_public_playlists        = $settings->is_public_playlists;

	if ($update_in_progress == true) {
		$w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', 'Please retry when update is finished', './images/warning.png', 'no', null, '');

		echo $w->toxml();
		return;
	}

	$tmp = explode('∙', $words[1]);
	$uri = $tmp[0];

	$track_name = '';
	$track_uri = '';
	$album_name = '';
	$album_uri = '';
	$playlist_name = '';
	$playlist_uri = '';

	$href = explode(':', $uri);
	$message = '';
	$type    = '';
	if ($href[1] == 'track') {
		$type       = 'track';
		$track_name = $tmp[1];
		$track_uri  = $uri;
		$message    = "track " . $track_name;
	} elseif ($href[1] == 'album') {
		$type       = 'album';
		$album_name = $tmp[1];
		$album_uri  = $uri;
		$message    = "album  " . $album_name;
	} elseif ($href[1] == 'user') {
		$type          = 'playlist';
		$playlist_name = $tmp[1];
		$playlist_uri  = $uri;
		$message       = "playlist " . $playlist_name;
	} elseif ($href[1] == 'local') {
		$w->result(null, '', 'Cannot add local track to playlist using the Web API', 'This is a limitation of Spotify Web API', './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}
	$theplaylist = $words[2];

	try {
		if (mb_strlen($theplaylist) < 2) {
			$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1";
			$stmt         = $db->prepare($getPlaylists);

			$w->result(null, '', 'Add ' . $type . ' ' . $tmp[1] . ' to Your Music or one of your playlists below..', "Select Your Music or one of your playlists below to add the " . $message, './images/add.png', 'no', null, '');

			$privacy_status = 'private';
			if ($is_public_playlists) {
				$privacy_status = 'public';
			}
			$w->result(null, '', "Create a new playlist ", "Create a new " . $privacy_status . " playlist and add the " . $message, './images/create_playlist.png', 'no', null, $query . 'Enter Playlist Name▹');

			// put Alfred Playlist at beginning
			if ($is_alfred_playlist_active == true) {
				if ($alfred_playlist_uri != '' && $alfred_playlist_name != '') {
					$w->result(null, serialize(array(
								$track_uri /*track_uri*/ ,
								$album_uri /* album_uri */ ,
								'' /* artist_uri */ ,
								$playlist_uri /* playlist_uri */ ,
								'' /* spotify_command */ ,
								'' /* query */ ,
								'ADD_TO_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/ ,
								'' /* other_action */ ,

								'' /* artist_name */ ,
								$track_name /* track_name */ ,
								$album_name /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								$playlist_name /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), "🎵 Alfred Playlist " . " ● " . ucfirst($alfred_playlist_name), "Select the playlist to add the " . $message, './images/alfred_playlist.png', 'yes', null, '');

				}
			}

			$w->result(null, serialize(array(
						$track_uri /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						'' /* artist_uri */ ,
						$playlist_uri /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'ADD_TO_YOUR_MUSIC▹' /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						$track_name /* track_name */ ,
						$album_name /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist_name /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Your Music", "Select to add the " . $message . " to Your Music", './images/yourmusic.png', 'yes', null, '');
		} else {
			$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist)";
			$stmt         = $db->prepare($getPlaylists);
			$stmt->bindValue(':playlist', '%' . $theplaylist . '%');
		}

		$playlists = $stmt->execute();
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	while ($playlist = $stmt->fetch()) {
		if (($playlist[0] != $alfred_playlist_uri && (mb_strlen($theplaylist) < 2)) || (mb_strlen($theplaylist) >= 3)) {
			$added = ' ';
			if (startswith($playlist[1], 'Artist radio for')) {
				$added = '📻 ';
			}
			$w->result(null, serialize(array(
						$track_uri /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						'' /* artist_uri */ ,
						$playlist_uri /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'ADD_TO_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						$track_name /* track_name */ ,
						$album_name /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist_name /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "🎵" . $added . ucfirst($playlist[1]), $playlist[7] . " tracks ● " . $playlist[8] . " ● Select the playlist to add the " . $message, $playlist[5], 'yes', null, '');
		}
	}
}


/**
 * secondDelimiterRemove function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterRemove($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if ($update_in_progress == true) {
		$w->result(null, '', 'Cannot remove tracks while update is in progress', 'Please retry when update is finished', './images/warning.png', 'no', null, '');

		echo $w->toxml();
		return;
	}

	$tmp         = explode('∙', $words[1]);
	$uri         = $tmp[0];
	$href = explode(':', $uri);
	// it is necessarly a track:
	$type        = 'track';
	$track_name  = $tmp[1];
	$track_uri   = $uri;
	$message     = "track " . $track_name;
	$theplaylist = $words[2];

	if ($href[1] == 'local') {
		$w->result(null, '', 'Cannot remove local tracks from playlists using the Web API', 'This is a limitation of Spotify Web API', './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}

	$noresult             = true;
	$getPlaylistsForTrack = "select distinct playlist_uri from tracks where uri=:uri";
	try {
		$stmt = $db->prepare($getPlaylistsForTrack);
		$stmt->bindValue(':uri', '' . $track_uri . '');
		$stmt->execute();

		while ($playlistsForTrack = $stmt->fetch()) {
			if ($playlistsForTrack[0] == "") {
				if ($noresult == true) {
					$w->result(null, '', 'Remove ' . $type . ' ' . $tmp[1] . ' from Your Music or one of your playlists below..', "Select Your Music or one of your playlists below to remove the " . $message, './images/add.png', 'no', null, '');
				}
				// Your Music
				$w->result(null, serialize(array(
							$track_uri /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'REMOVE_FROM_YOUR_MUSIC▹' /* other_settings*/ ,
							'' /* other_action */ ,
							'' /* artist_name */ ,
							$track_name /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), "Your Music", "Select to remove the " . $message . " from Your Music", './images/yourmusic.png', 'yes', null, '');
				$noresult = false;
			} else {
				if (mb_strlen($theplaylist) < 2) {
					$getPlaylists     = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and uri=:playlist_uri";
					$stmtGetPlaylists = $db->prepare($getPlaylists);
					$stmtGetPlaylists->bindValue(':playlist_uri', $playlistsForTrack[0]);
				} else {
					$getPlaylists     = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist) and uri=:playlist_uri";
					$stmtGetPlaylists = $db->prepare($getPlaylists);
					$stmtGetPlaylists->bindValue(':playlist_uri', $playlistsForTrack[0]);
					$stmtGetPlaylists->bindValue(':playlist', '%' . $theplaylist . '%');
				}

				$playlists = $stmtGetPlaylists->execute();

				while ($playlist = $stmtGetPlaylists->fetch()) {
					if ($noresult == true) {
						$w->result(null, '', 'Remove ' . $type . ' ' . $tmp[1] . ' from Your Music or one of your playlists below..', "Select Your Music or one of your playlists below to remove the " . $message, './images/add.png', 'no', null, '');
					}
					$added = ' ';
					if (startswith($playlist[1], 'Artist radio for')) {
						$added = '📻 ';
					}
					$w->result(null, serialize(array(
								$track_uri /*track_uri*/ ,
								'' /* album_uri */ ,
								'' /* artist_uri */ ,
								'' /* playlist_uri */ ,
								'' /* spotify_command */ ,
								'' /* query */ ,
								'REMOVE_FROM_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/ ,
								'' /* other_action */ ,
								'' /* artist_name */ ,
								$track_name /* track_name */ ,
								'' /* album_name */ ,
								'' /* track_artwork_path */ ,
								'' /* artist_artwork_path */ ,
								'' /* album_artwork_path */ ,
								'' /* playlist_name */ ,
								'' /* playlist_artwork_path */
							)), "🎵" . $added . ucfirst($playlist[1]), $playlist[7] . " tracks ● " . $playlist[8] . " ● Select the playlist to remove the " . $message, $playlist[5], 'yes', null, '');
					$noresult = false;
				}

			}
		}
	}
	catch (PDOException $e) {
		handleDbIssuePdoXml($db);
		return;
	}

	if ($noresult) {
		$w->result(null, 'help', "The current track is not in Your Music or one of your playlists", "", './images/warning.png', 'no', null, '');
	}
}


/**
 * secondDelimiterAlfredPlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterAlfredPlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$setting_kind = $words[1];
	$theplaylist  = $words[2];

	if ($setting_kind == "Set Alfred Playlist") {
		$w->result(null, '', "Set your Alfred playlist", "Select one of your playlists below as your Alfred playlist", './images/settings.png', 'no', null, '');

		try {
			if (mb_strlen($theplaylist) < 2) {
				$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1";
				$stmt         = $db->prepare($getPlaylists);
			} else {
				$getPlaylists = "select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name like :playlist or author like :playlist)";
				$stmt         = $db->prepare($getPlaylists);
				$stmt->bindValue(':playlist', '%' . $theplaylist . '%');
			}

			$playlists = $stmt->execute();

		}
		catch (PDOException $e) {
			handleDbIssuePdoXml($db);
			return;
		}

		while ($playlist = $stmt->fetch()) {

			$added = ' ';
			if (startswith($playlist[1], 'Artist radio for')) {
				$added = '📻 ';
			}
			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'ALFRED_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "🎵" . $added . ucfirst($playlist[1]), $playlist[7] . " tracks ● " . $playlist[8] . " ● Select the playlist to set it as your Alfred Playlist", $playlist[5], 'yes', null, '');

		}
	} elseif ($setting_kind == "Confirm Clear Alfred Playlist") {

		$w->result(null, '', "Are you sure?", "This will remove all the tracks in your current Alfred Playlist.", './images/warning.png', 'no', null, '');

		$w->result(null, '', "No, cancel", "Return to Alfred Playlist", './images/uncheck.png', 'no', null, 'Alfred Playlist▹');

		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'CLEAR_ALFRED_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Yes, go ahead", "This is undoable", './images/check.png', 'yes', null, '');

	}
}


/**
 * secondDelimiterFollowUnfollow function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterFollowUnfollow($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '@') == 1) {
		//
		// Follow / Unfollow artist Option menu
		//
		$tmp        = $words[1];
		$words      = explode('@', $tmp);
		$artist_uri = $words[0];
		$tmp_uri    = explode(':', $artist_uri);

		$artist_name = $words[1];

		try {
			$api              = getSpotifyWebAPI($w);
			$isArtistFollowed = $api->currentUserFollows('artist', $tmp_uri[2]);

			$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false);
			if (!$isArtistFollowed[0]) {
				$w->result(null, '', 'Follow artist ' . $artist_name, 'You are not currently following the artist', $artist_artwork_path, 'no', null, "Follow▹" . $artist_uri . "@" . $artist_name . '▹');
			} else {
				$w->result(null, '', 'Unfollow artist ' . $artist_name, 'You are currently following the artist', $artist_artwork_path, 'no', null, "Unfollow▹" . $artist_uri . "@" . $artist_name . '▹');
			}

		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * secondDelimiterFollowOrUnfollow function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterFollowOrUnfollow($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '@') == 1) {
		//
		// Follow / Unfollow actions
		//
		$tmp        = $words[1];
		$words      = explode('@', $tmp);
		$artist_uri = $words[0];
		$tmp_uri    = explode(':', $artist_uri);

		$artist_name = $words[1];

		if ($kind == "Follow") {
			$follow = true;
		} else {
			$follow = false;
		}
		try {
			$api = getSpotifyWebAPI($w);
			if ($follow) {
				$ret = $api->followArtistsOrUsers('artist', $tmp_uri[2]);
			} else {
				$ret = $api->unfollowArtistsOrUsers('artist', $tmp_uri[2]);
			}

			if ($ret) {
				if ($follow) {
					displayNotificationWithArtwork($w,'You are now following the artist ' . $artist_name, './images/follow.png', 'Follow');
					exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Artist▹" . $artist_uri . "∙" . escapeQuery($artist_name) . '▹' . "\"'");
				} else {
					displayNotificationWithArtwork($w,'You are no more following the artist ' . $artist_name, './images/follow.png', 'Unfollow');
					exec("osascript -e 'tell application \"Alfred 3\" to search \"spot_mini Artist▹" . $artist_uri . "∙" . escapeQuery($artist_name) . '▹' . "\"'");
				}
			} else {
				$w->result(null, '', 'Error!', 'An error happened, try again or report to the author', './images/warning.png', 'no', null, '');
			}
		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * secondDelimiterDisplayBiography function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterDisplayBiography($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '∙') == 1) {
		//
		// Search Biography
		//
		$tmp         = $words[1];
		$words       = explode('∙', $tmp);
		$artist_uri   = $words[0];
		$artist_name = $words[1];

		list($biography_url, $source , $biography, $twitter_url, $official_url) = getBiography($w, $artist_uri, $artist_name);

		if ($biography_url != false) {

			if ($source == 'Last FM') {
				$image = './images/lastfm.png';
			} elseif ($source == 'Wikipedia') {
				$image = './images/wikipedia.png';
			} else {
				$image = './images/biography.png';
			}

			if ($twitter_url != '') {
				$twitter_account = end((explode('/', rtrim($twitter_url, '/'))));
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'Open▹' . $twitter_url /* other_settings*/ ,
							'' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'See twitter account @' . $twitter_account, "This will open your default browser with the twitter of the artist", './images/twitter.png', 'yes', null, '');
			}

			if ($official_url != '') {
				$w->result(null, serialize(array(
							'' /*track_uri*/ ,
							'' /* album_uri */ ,
							'' /* artist_uri */ ,
							'' /* playlist_uri */ ,
							'' /* spotify_command */ ,
							'' /* query */ ,
							'Open▹' . $official_url /* other_settings*/ ,
							'' /* other_action */ ,

							'' /* artist_name */ ,
							'' /* track_name */ ,
							'' /* album_name */ ,
							'' /* track_artwork_path */ ,
							'' /* artist_artwork_path */ ,
							'' /* album_artwork_path */ ,
							'' /* playlist_name */ ,
							'' /* playlist_artwork_path */
						)), 'See official website for the artist (' . $official_url . ')' , "This will open your default browser with the official website of the artist", './images/artists.png', 'yes', null, '');
			}


			$w->result(null, serialize(array(
						'' /*track_uri*/ ,
						'' /* album_uri */ ,
						'' /* artist_uri */ ,
						'' /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'Open▹' . $biography_url /* other_settings*/ ,
						'' /* other_action */ ,
						'' /* artist_name */ ,
						'' /* track_name */ ,
						'' /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						'' /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), 'See biography for ' . $artist_name . ' on ' . $source, "This will open your default browser", $image, 'yes', null, '');

			$wrapped = wordwrap($biography, 70, "\n", false);
			$biography_sentances = explode("\n", $wrapped);
			$artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false);
			for ($i = 0; $i < count($biography_sentances); $i++) {
				$w->result(null, '', $biography_sentances[$i], '', $artist_artwork_path, 'no', null, '');
			}
		} else {
			$w->result(null, 'help', "No biography found!", "", './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * secondDelimiterDisplayConfirmRemovePlaylist function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterDisplayConfirmRemovePlaylist($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	if (substr_count($query, '∙') == 1) {
		$tmp         = $words[1];
		$words       = explode('∙', $tmp);
		$playlist_uri   = $words[0];
		$playlist_name = $words[1];
		$w->result(null, '', "Are you sure?", "This will remove the playlist from your library.", './images/warning.png', 'no', null, '');

		$w->result(null, '', "No, cancel", "Return to the playlist menu", './images/uncheck.png', 'no', null, 'Playlist▹' . $playlist_uri . '▹');

		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					$playlist_uri /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'' /* other_settings*/ ,
					'unfollow_playlist' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					$playlist_name /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Yes, go ahead", "You can always recover a removed playlist by choosing option below", './images/check.png', 'yes', null, '');

		$w->result(null, serialize(array(
					'' /*track_uri*/ ,
					'' /* album_uri */ ,
					'' /* artist_uri */ ,
					'' /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'Open▹' . 'https://www.spotify.com/us/account/recover-playlists/' /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					'' /* track_name */ ,
					'' /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					'' /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), 'Open Spotify web page to recover any of your playlists', "This will open the Spotify page with your default browser", './images/spotify.png', 'yes', null, '');
	}
}


/**
 * secondDelimiterBrowse function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function secondDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$country = $words[1];

	if ($country == 'Choose a Country') {
		// list taken from http://charts.spotify.com/docs
		$spotify_country_codes = array(
			"ar",
			"at",
			"au",
			"be",
			"bg",
			"ch",
			"cl",
			"co",
			"cr",
			"cz",
			"de",
			"dk",
			"ec",
			"ee",
			"es",
			"fi",
			"fr",
			"gb",
			"gr",
			"gt",
			"hk",
			"hu",
			"ie",
			"is",
			"it",
			"li",
			"lt",
			"lu",
			"lv",
			"mx",
			"my",
			"nl",
			"no",
			"nz",
			"pe",
			"pl",
			"pt",
			"se",
			"sg",
			"sk",
			"sv",
			"tr",
			"tw",
			"us",
			"uy"
		);
		foreach ($spotify_country_codes as $spotify_country_code) {
			if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {
				$w->result(null, '', getCountryName(strtoupper($spotify_country_code)), 'Browse the Spotify categories in ' . getCountryName(strtoupper($spotify_country_code)), './images/browse.png', 'no', null, 'Browse▹' . strtoupper($spotify_country_code) . '▹');
			}
		}
	} else {
		try {
			$api    = getSpotifyWebAPI($w);
			$offsetListCategories = 0;
			$limitListCategories  = 50;
			do {
				// refresh api
				$api                = getSpotifyWebAPI($w, $api);
				$listCategories = $api->getCategoriesList(array(
						'country' => $country,
						'limit' => $limitListCategories,
						'locale' => '',
						'offset' => $offsetListCategories
					));
				$offsetListCategories += $limitListCategories;
			} while ($offsetListCategories < $listCategories->categories->total);

			foreach ($listCategories->categories->items as $category) {
				$w->result(null, '', escapeQuery($category->name), "Browse this category", getCategoryArtwork($w, $category->id, $category->icons[0]->url, true, false), 'no', null, "Browse▹" . $country . "▹" . $category->id . "▹");
			}

		}
		catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
			$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
			echo $w->toxml();
			return;
		}
	}
}


/**
 * thirdDelimiterAdd function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function thirdDelimiterAdd($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;
	$is_public_playlists        = $settings->is_public_playlists;

	$tmp = explode('∙', $words[1]);
	$uri = $tmp[0];

	$track_name = '';
	$track_uri = '';
	$album_name = '';
	$album_uri = '';
	$playlist_name = '';
	$playlist_uri = '';

	$message = '';
	$type    = '';

	$href = explode(':', $uri);
	if ($href[1] == 'track') {
		$type       = 'track';
		$track_name = $tmp[1];
		$track_uri  = $uri;
		$message    = "track " . $track_name;
	} elseif ($href[1] == 'album') {
		$type       = 'album';
		$album_name = $tmp[1];
		$album_uri  = $uri;
		$message    = "album  " . $album_name;
	} elseif ($href[1] == 'user') {
		$type          = 'playlist';
		$playlist_name = $tmp[1];
		$playlist_uri  = $uri;
		$message       = "playlist " . $playlist_name;
	}

	$the_query = $words[3];

	if ($update_in_progress == true) {
		$w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', 'Please retry when update is finished', './images/warning.png', 'no', null, '');

		echo $w->toxml();
		return;
	}

	if (mb_strlen($the_query) == 0) {
		$privacy_status = 'private';
		if ($is_public_playlists) {
			$privacy_status = 'public';
		}
		$w->result(null, '', "Enter the name of the new playlist: ", "This will create a new " . $privacy_status . " playlist with the name entered", './images/create_playlist.png', 'no', null, '');

		$w->result(null, 'help', "Or choose an alternative below", "Some playlists names are proposed below", './images/info.png', 'no', null, '');

		if ($album_name != "") {
			$w->result(null, serialize(array(
						$track_uri /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						'' /* artist_uri */ ,
						$playlist_uri /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . $album_name /* other_settings*/ ,
						'' /* other_action */ ,

						'' /* artist_name */ ,
						$track_name /* track_name */ ,
						$album_name /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist_name /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Create a playlist named '" . $album_name . "'", 'This will create a playlist ' . $album_name . ' with content of the album', './images/add.png', 'yes', null, '');
		}

		if ($playlist_name != "") {
			$w->result(null, serialize(array(
						$track_uri /*track_uri*/ ,
						$album_uri /* album_uri */ ,
						'' /* artist_uri */ ,
						$playlist_uri /* playlist_uri */ ,
						'' /* spotify_command */ ,
						'' /* query */ ,
						'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . $playlist_name /* other_settings*/ ,
						'' /* other_action */ ,

						'' /* artist_name */ ,
						$track_name /* track_name */ ,
						$album_name /* album_name */ ,
						'' /* track_artwork_path */ ,
						'' /* artist_artwork_path */ ,
						'' /* album_artwork_path */ ,
						$playlist_name /* playlist_name */ ,
						'' /* playlist_artwork_path */
					)), "Create a copy of playlist named '" . $playlist_name . "'", 'This will copy the existing playlist ' . $playlist_name . ' to a new one', './images/add.png', 'yes', null, '');
		}
	} else {
		// playlist name has been set
		$w->result(null, serialize(array(
					$track_uri /*track_uri*/ ,
					$album_uri /* album_uri */ ,
					'' /* artist_uri */ ,
					$playlist_uri /* playlist_uri */ ,
					'' /* spotify_command */ ,
					'' /* query */ ,
					'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . ltrim(rtrim($the_query)) /* other_settings*/ ,
					'' /* other_action */ ,
					'' /* artist_name */ ,
					$track_name /* track_name */ ,
					$album_name /* album_name */ ,
					'' /* track_artwork_path */ ,
					'' /* artist_artwork_path */ ,
					'' /* album_artwork_path */ ,
					$playlist_name /* playlist_name */ ,
					'' /* playlist_artwork_path */
				)), "Create playlist " . ltrim(rtrim($the_query)), "This will create the playlist and add the " . $message, './images/add.png', 'yes', null, '');
	}
}


/**
 * thirdDelimiterBrowse function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 * @return void
 */
function thirdDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
	$words = explode('▹', $query);
	$kind  = $words[0];

	$all_playlists             = $settings->all_playlists;
	$is_alfred_playlist_active = $settings->is_alfred_playlist_active;
	$radio_number_tracks       = $settings->radio_number_tracks;
	$now_playing_notifications = $settings->now_playing_notifications;
	$max_results               = $settings->max_results;
	$alfred_playlist_uri       = $settings->alfred_playlist_uri;
	$alfred_playlist_name      = $settings->alfred_playlist_name;
	$country_code              = $settings->country_code;
	$last_check_update_time    = $settings->last_check_update_time;
	$oauth_client_id           = $settings->oauth_client_id;
	$oauth_client_secret       = $settings->oauth_client_secret;
	$oauth_redirect_uri        = $settings->oauth_redirect_uri;
	$oauth_access_token        = $settings->oauth_access_token;
	$oauth_expires             = $settings->oauth_expires;
	$oauth_refresh_token       = $settings->oauth_refresh_token;
	$display_name              = $settings->display_name;
	$userid                    = $settings->userid;
	$echonest_api_key          = $settings->echonest_api_key;

	$country = $words[1];
	$category = $words[2];

	try {
		$offsetCategoryPlaylists = 0;
		$limitCategoryPlaylists  = 50;
		$api    = getSpotifyWebAPI($w);
		do {
			// refresh api
			$api                = getSpotifyWebAPI($w, $api);
			$listPlaylists = $api->getCategoryPlaylists($category, array(
					'country' => $country,
					'limit' => $limitCategoryPlaylists,
					'offset' => $offsetCategoryPlaylists
				));

			$subtitle  = "Launch Playlist";
			$playlists = $listPlaylists->playlists;
			$items = $playlists->items;
			foreach ($items as $playlist) {
				$w->result(null, '', "🎵" . escapeQuery($playlist->name), "by " . $playlist->owner->id . " ● " . $playlist->tracks->total . " tracks", getPlaylistArtwork($w, $playlist->uri, false), 'no', null, "Online Playlist▹" . $playlist->uri . '∙' . escapeQuery($playlist->name) . "▹");
			}

			$offsetCategoryPlaylists += $limitCategoryPlaylists;
		} while ($offsetCategoryPlaylists < $listPlaylists->playlists->total);
	}


	catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
		$w->result(null, 'help', "Exception occurred", "" . $e->getMessage(), './images/warning.png', 'no', null, '');
		echo $w->toxml();
		return;
	}
}
