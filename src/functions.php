<?php

require_once './src/workflows.php';

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
 * installSpotifyAppIfNeeded function.
 *
 * @access public
 * @param mixed $w
 * @return void
 */
function installSpotifyAppIfNeeded($w) {
	if (!file_exists($w->home())) {
		displayNotification("Error: Home Directory <" . $w->home() . "> does not exist");
		return false;
	}

	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer')) {
		exec('mkdir -p ~/Spotify');
		symlink(exec('pwd') . '/spotify-app-miniplayer', $w->home() . '/Spotify/spotify-app-miniplayer');
	}

	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer/manifest.json')) {
		exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
		symlink(exec('pwd') . '/spotify-app-miniplayer', $w->home() . '/Spotify/spotify-app-miniplayer');
	}

	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer/manifest.json')) {
		return false;
	}
	return true;
}

/**
 * getFreeTcpPort function.
 *
 * @access public
 * @return void
 */
function getFreeTcpPort() {
	//avoid warnings like this PHP Warning:  fsockopen(): unable to connect to localhost (Connection refused)
	error_reporting(~E_ALL);

	$from = 10000;
	$to = 20000;

	//TCP ports
	$host = 'localhost';

	for ($port = $from; $port <= $to ; $port++) {
		$fp = fsockopen($host , $port);
		if (!$fp) {
			//port is free
			return $port;
		}
		else {
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
 * @param mixed $theme
 * @param mixed $track_uri
 * @return void
 */
function getPlaylistsForTrack($db, $theme, $track_uri) {

	$playlistsfortrack = "";
	$getPlaylistsForTrack = "select playlist_name from tracks where uri=:uri";
	try {
		$stmt = $db->prepare($getPlaylistsForTrack);
		$stmt->bindValue(':uri', '' . $track_uri . '');

		$stmt->execute();

		$noresult=true;
		while ($playlist = $stmt->fetch()) {
			if ($noresult==true) {
				$playlistsfortrack = $playlistsfortrack . " ● In playlists: " . $playlist[0];
			} else {
				$playlistsfortrack =  $playlistsfortrack . " ○ " . $playlist[0];
			}
			$noresult=false;
		}


	} catch (PDOException $e) {
		handleDbIssuePdo($theme, $db);
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
function escapeQuery($text) {
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
function checkIfResultAlreadyThere($results, $title) {
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
 * displayNotification function.
 *
 * @access public
 * @param mixed $output
 * @return void
 */
function displayNotification($output) {
	exec('./terminal-notifier.app/Contents/MacOS/terminal-notifier -title "Spotify Mini Player" -sender com.spotify.miniplayer -message "' .  $output . '"');
}

/**
 * displayNotificationWithArtwork function.
 *
 * @access public
 * @param mixed $output
 * @param mixed $artwork
 * @return void
 */
function displayNotificationWithArtwork($output, $artwork) {
	if ($artwork != "") {
		copy($artwork, "/tmp/tmp");
	}

	exec("./terminal-notifier.app/Contents/MacOS/terminal-notifier -title 'Spotify Mini Player' -sender 'com.spotify.miniplayer' -contentImage '/tmp/tmp' -message '" .  $output . "'");
}

/**
 * displayNotificationForStarredTrack function.
 *
 * @access public
 * @param mixed $track_name
 * @param mixed $track_uri
 * @return void
 */
function displayNotificationForStarredTrack($track_name, $track_uri) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');
	displayNotificationWithArtwork('⭐️ ' . $track_name . ' has been starred', getTrackOrAlbumArtwork($w, 'black', $track_uri, true));
}

/**
 * displayNotificationForUnstarredTrack function.
 *
 * @access public
 * @param mixed $track_name
 * @param mixed $track_uri
 * @return void
 */
function displayNotificationForUnstarredTrack($track_name, $track_uri) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');
	displayNotificationWithArtwork('❌ ' . $track_name . ' has been unstarred', getTrackOrAlbumArtwork($w, 'black', $track_uri, true));
}

/**
 * getTrackOrAlbumArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $spotifyURL
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getTrackOrAlbumArtwork($w, $theme, $spotifyURL, $fetchIfNotPresent) {

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

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
			$artwork = getTrackArtworkURL($w, $hrefs[1], $hrefs[2]);

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
			}
		} else {
			if ($isAlbum) {
				return "images/" . $theme . "/albums.png";
			} else {
				return "images/" . $theme . "/tracks.png";
			}
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			if ($isAlbum) {
				return "images/" . $theme . "/albums.png";
			} else {
				return "images/" . $theme . "/tracks.png";
			}
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		if ($isAlbum) {
			return "images/" . $theme . "/albums.png";
		} else {
			return "images/" . $theme . "/tracks.png";
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
 * @param mixed $theme
 * @param mixed $playlistURI
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getPlaylistArtwork($w, $theme, $playlistURI, $fetchIfNotPresent) {

	$hrefs = explode(':', $playlistURI);

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	// examples of playlists URI
	// spotify:user:@:playlist:20SZdrktr658JNa42Lt1vV
	// spotify:user:@cf86d5f3b8f0b11bc0e70d7fa3661dc8:playlist:3vxotOnOGDlZXyzJPLFnm2

	// need to translate to http://open.spotify.com/user/xxxxusernamexxx/playlist/6orFdd91Cb0fwB2kyUFCKX

	// spotify:user:@:starred
	// spotify:user:117875373:starred

	// need to translate to http://open.spotify.com/user/xxxxusernamexxx/starred

	if (count($hrefs) == 5) {

		$filename = "" . $hrefs[2] . "_" . $hrefs[4];
		$url = "http://open.spotify.com/user/" . $hrefs[2] . "/playlist/" . $hrefs[4];
	} else {
		//starred playlist
		$filename = "" . $hrefs[2] . "_" . $hrefs[3];
		$url = "http://open.spotify.com/user/" . $hrefs[2] . "/" . $hrefs[3];
	}

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $filename . ".png") . "/" . "$filename.png";

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
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
			}
		} else {

			return "images/" . $theme . "/playlists.png";
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			return "images/" . $theme . "/playlists.png";
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		return "images/" . $theme . "/playlists.png";
	} else {
		return $currentArtwork;
	}
}

/**
 * getArtistArtwork function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $artist
 * @param mixed $fetchIfNotPresent
 * @return void
 */
function getArtistArtwork($w, $theme, $artist, $fetchIfNotPresent) {
	$parsedArtist = urlencode($artist);

	if (!file_exists($w->data() . "/artwork")):
		exec("mkdir '" . $w->data() . "/artwork'");
	endif;

	$currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";
	$artwork = "";

	if (!is_file($currentArtwork)) {
		if ($fetchIfNotPresent == true) {
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
			}
		} else {
			return "images/" . $theme . "/artists.png";
		}
	} else {
		if (filesize($currentArtwork) == 0) {
			return "images/" . $theme . "/artists.png";
		}
	}

	if (is_numeric($artwork) && $artwork == 0) {
		return "images/" . $theme . "/artists.png";
	} else {
		return $currentArtwork;
	}
}

/**
 * getTrackArtworkURL function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $type
 * @param mixed $id
 * @return void
 */
function getTrackArtworkURL($w, $type, $id) {
	$html = $w->request("http://open.spotify.com/$type/$id");

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
function getPlaylistArtworkURL($w, $url) {
	$html = $w->request($url);

	if (!empty($html)) {
		preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
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
function getArtistArtworkURL($w, $artist) {
	$parsedArtist = urlencode($artist);
	$html = $w->request("http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json");
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
 * @param mixed $jsonData
 * @return void
 */
function updateLibrary($jsonData) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//	
	$dbfile = $w->data() . '/settings.db';
	try {
		$dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$getSettings = 'select theme from settings';
		$stmt = $dbsettings->prepare($getSettings);
		$setting = $stmt->fetch();
		$theme = $setting[0];
	} catch (PDOException $e) {
		handleDbIssuePdo('new', $dbsettings);
		$dbsettings=null;
		unlink($w->data() . "/update_library_in_progress");
		return;
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

	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$dbfile = $w->data() . '/library.db';
		if (file_exists($dbfile)) {
			unlink($dbfile);
		}
		touch($dbfile);
	
		try {
			$db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $db);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}
	
		$nb_tracktotal = 0;

		// get playlists
		$playlists = $json['playlists'];

		foreach ($playlists as $playlist) {
			$nb_tracktotal += count($playlist['tracks']);
		}

		// get artists
		$artists = $json['artists'];
		$w->write('Related Artists▹0▹' . count($artists) . '▹' . $words[3], 'update_library_in_progress');

		$db->exec("create table tracks (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, availability text)");
		$db->exec("CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)");
		$db->exec("CREATE INDEX IndexArtistName ON tracks (artist_name)");
		$db->exec("CREATE INDEX IndexAlbumName ON tracks (album_name)");
		$db->exec("create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)");
		$db->exec("create table user (uri text, username text, name text, image text)");
		$db->exec("create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean)");
		$db->exec("create table artists (artist_name text, artist_uri text, artist_artwork_path text, artist_biography text, artist_popularity int, artist_years_from text, artist_years_to text, related_artist_name text, related_artist_uri text, related_artist_artwork_path text, PRIMARY KEY (artist_name, related_artist_name))");
		$db->exec("CREATE INDEX indexArtistNameForArtists ON artists (artist_name)");

		// Handle user
		$user = $json['user'];
		$insertUser = "insert into user values (:uri,:username,:name,:image)";
		try {
			$stmt = $db->prepare($insertUser);
			$stmt->bindValue(':uri', $user['uri']);
			$stmt->bindValue(':username', escapeQuery($user['username']));
			$stmt->bindValue(':name', escapeQuery($user['name']));
			$stmt->bindValue(':image', $user['image']);
			$stmt->execute();

		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $db);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}
			
		// Handle country
		$country = $json['country'];		
		$updateCountry = "update settings set country_code=:country_code";
		try {
			$stmt = $dbsettings->prepare($updateCountry);
			$stmt->bindValue(':country_code', $country);
			$stmt->execute();

		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $dbsettings);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}

		// Handle related artists
		$nb_artists = 0;
		try {
			$insertArtist = "insert or ignore into artists values (:artist_name,:artist_uri,:artist_artwork_path,:biography,:popularity,:from,:to,:related_name,:related_uri,:related_artist_artwork_path)";
			$stmt = $db->prepare($insertArtist);
			
			foreach ($artists as $artist) {
	
				$artist_artwork_path = getArtistArtwork($w, $theme, $artist['artist_name'], true);
	
				if (isset($artist['related'])) {
					$relateds = $artist['related'];
					foreach ($relateds as $related) {
						$related_artist_artwork_path = getArtistArtwork($w, $theme, $related['name'], true);						
						$stmt->bindValue(':artist_name', escapeQuery($artist['artist_name']));
						$stmt->bindValue(':artist_uri', $artist['artist_uri']);
						$stmt->bindValue(':artist_artwork_path', $artist_artwork_path);
						$stmt->bindValue(':biography', escapeQuery($artist['biography']));
						$stmt->bindValue(':popularity', $artist['popularity']);
						$stmt->bindValue(':from', $artist['years']['from']);
						$stmt->bindValue(':to', $artist['years']['to']);
						$stmt->bindValue(':related_name', escapeQuery($related['name']));
						$stmt->bindValue(':related_uri', $related['uri']);
						$stmt->bindValue(':related_artist_artwork_path', $related_artist_artwork_path);
						$stmt->execute();
					}
				} else {					
					$stmt->bindValue(':artist_name', escapeQuery($artist['artist_name']));
					$stmt->bindValue(':artist_uri', $artist['artist_uri']);
					$stmt->bindValue(':artist_artwork_path', $artist_artwork_path);
					$stmt->bindValue(':biography', escapeQuery($artist['biography']));
					$stmt->bindValue(':popularity', $artist['popularity']);
					$stmt->bindValue(':from', $artist['years']['from']);
					$stmt->bindValue(':to', $artist['years']['to']);
					$stmt->bindValue(':related_name', "");
					$stmt->bindValue(':related_uri', "");
					$stmt->bindValue(':related_artist_artwork_path', "");
					$stmt->execute();
				}
				$nb_artists++;
				if ($nb_artists % 10 === 0) {
					$w->write('Related Artists▹' . $nb_artists . '▹' . count($artists) . '▹' . $words[3], 'update_library_in_progress');
				}
			}

		
			// Handle playlists
			$w->write('Library▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
	
			$nb_track = 0;
			$insertPlaylist = "insert into playlists values (:uri,:name,:count_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser)";
			$stmtPlaylist = $db->prepare($insertPlaylist);	

			$insertTrack = "insert into tracks values (:starred,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_year,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:availability)";
			$stmtTrack = $db->prepare($insertTrack);
						
			foreach ($playlists as $playlist) {
				$playlist_artwork_path = getPlaylistArtwork($w, 'black', $playlist['uri'], true);
	
				if ($playlist['ownedbyuser'] == true) {
					$ownedbyuser = 1;
				} else {
					$ownedbyuser = 0;
				}

				$stmtPlaylist->bindValue(':uri', $playlist['uri']);
				$stmtPlaylist->bindValue(':name', escapeQuery($playlist['name']));
				$stmtPlaylist->bindValue(':count_tracks', count($playlist['tracks']));
				$stmtPlaylist->bindValue(':owner', $playlist['owner']);
				$stmtPlaylist->bindValue(':username', $playlist['username']);
				$stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
				$stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
				$stmtPlaylist->execute();
						
				foreach ($playlist['tracks'] as $track) {
	
					if ($track['starred'] == true) {
						$starred = 1;
					} else {
						$starred = 0;
					}
	
					if ($track['playable'] == true) {
						$playable = 1;
					} else {
						$playable = 0;
					}
	
					//
					// Download artworks
					$track_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['uri'], true);
					$artist_artwork_path = getArtistArtwork($w, $theme, $track['artist_name'], true);
					$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['album_uri'], true);
					$album_year = 1995;					
					
					$stmtTrack->bindValue(':starred', $starred);	
					$stmtTrack->bindValue(':popularity', $track['popularity']);
					$stmtTrack->bindValue(':uri',$track['uri']);
					$stmtTrack->bindValue(':album_uri',$track['album_uri']);
					$stmtTrack->bindValue(':artist_uri',$track['artist_uri']);
					$stmtTrack->bindValue(':track_name',escapeQuery($track['name']));
					$stmtTrack->bindValue(':album_name',escapeQuery($track['album_name']));
					$stmtTrack->bindValue(':artist_name',escapeQuery($track['artist_name']));
					$stmtTrack->bindValue(':album_year',$album_year);
					$stmtTrack->bindValue(':track_artwork_path',$track_artwork_path);
					$stmtTrack->bindValue(':artist_artwork_path',$artist_artwork_path);
					$stmtTrack->bindValue(':album_artwork_path',$album_artwork_path);
					$stmtTrack->bindValue(':playlist_name',escapeQuery($track['playlist_name']));
					$stmtTrack->bindValue(':playlist_uri',$track['playlist_uri']);
					$stmtTrack->bindValue(':playable',$playable);
					$stmtTrack->bindValue(':availability',$track['availability']);
					$stmtTrack->execute();	
					
					$nb_track++;
					if ($nb_track % 10 === 0) {
						$w->write('Library▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
					}
				}
			}// end playlists
	
			$getCount = 'select count(distinct uri) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_tracks = $stmt->fetch();

			$getCount = 'select count(distinct uri) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_tracks = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_albums = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_albums = $stmt->fetch();

			$getCount = 'select count(*) from playlists';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$playlists_count = $stmt->fetch();
			
			$insertCounter = "insert into counters values (:all_tracks,:starred_tracks,:all_artists,:starred_artists,:all_albums,:starred_albums,:playlists)";
			$stmt = $db->prepare($insertCounter);
			
			$stmt->bindValue(':all_tracks', $all_tracks[0]);
			$stmt->bindValue(':starred_tracks', $starred_tracks[0]);
			$stmt->bindValue(':all_artists', $all_artists[0]);
			$stmt->bindValue(':starred_artists', $starred_artists[0]);
			$stmt->bindValue(':all_albums', $all_albums[0]);
			$stmt->bindValue(':starred_albums', $starred_albums[0]);
			$stmt->bindValue(':playlists', $playlists_count[0]);
			$stmt->execute();
			
		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $db);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}
		
		$elapsed_time = time() - $words[3];
		displayNotification("Library has been created (" . $all_tracks[0] . " tracks) - it took " . beautifyTime($elapsed_time));

		unlink($w->data() . "/update_library_in_progress");

		if (file_exists($w->data() . "/library.db")) {
			if (file_exists(exec('printf $HOME') . "/Spotify/spotify-app-miniplayer")) {
				exec("rm -rf " . exec('printf $HOME') . "/Spotify/spotify-app-miniplayer");
			}
		}

	} else {
		unlink($w->data() . "/update_library_in_progress");
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
}

/**
 * updatePlaylist function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updatePlaylist($jsonData) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$dbfile = $w->data() . '/settings.db';
	try {
		$dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$getSettings = 'select theme from settings';
		$stmt = $dbsettings->prepare($getSettings);
		$setting = $stmt->fetch();
		$theme = $setting[0];
	} catch (PDOException $e) {
		handleDbIssuePdo('new', $dbsettings);
		$dbsettings=null;
		unlink($w->data() . "/update_library_in_progress");
		return;
	}

	$words = explode('▹', $in_progress_data);

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$dbfile = $w->data() . '/library.db';
		
		try {
			$db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		
			$nb_tracktotal = 0;
			foreach ($json as $playlist) {
				$nb_tracktotal += count($playlist['tracks']);
			}
			$w->write('Playlist▹0▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
	
			$db->exec("drop table counters");
			$db->exec("create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)");
	
			$nb_track = 0;

			$deleteFromTracks="delete from tracks where playlist_uri=:playlist_uri";
			$stmt = $db->prepare($deleteFromTracks);
			$stmt->bindValue(':playlist_uri', $playlist['uri']);
			$stmt->execute();

			$updatePlaylistsNbTracks="update playlists set nb_tracks=:nb_tracks where uri=:uri";
			$stmt = $db->prepare($updatePlaylistsNbTracks);
			
			$insertTrack = "insert into tracks values (:starred,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_year,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:availability)";
			$stmtTrack = $db->prepare($insertTrack);
						
						
			foreach ($json as $playlist) {
				$stmt->bindValue(':nb_tracks', count($playlist['tracks']));
				$stmt->bindValue(':uri', $playlist['uri']);
				$stmt->execute();
					
				foreach ($playlist['tracks'] as $track) {
	
					if ($track['starred'] == true) {
						$starred = 1;
					} else {
						$starred = 0;
					}
	
					if ($track['playable'] == true) {
						$playable = 1;
					} else {
						$playable = 0;
					}
	
					//
					// Download artworks
					$track_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['uri'], true);
					$artist_artwork_path = getArtistArtwork($w, $theme, $track['artist_name'], true);
					$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['album_uri'], true);
	
					$album_year = 1995;
	
					$stmtTrack->bindValue(':starred', $starred);	
					$stmtTrack->bindValue(':popularity', $track['popularity']);
					$stmtTrack->bindValue(':uri',$track['uri']);
					$stmtTrack->bindValue(':album_uri',$track['album_uri']);
					$stmtTrack->bindValue(':artist_uri',$track['artist_uri']);
					$stmtTrack->bindValue(':track_name',escapeQuery($track['name']));
					$stmtTrack->bindValue(':album_name',escapeQuery($track['album_name']));
					$stmtTrack->bindValue(':artist_name',escapeQuery($track['artist_name']));
					$stmtTrack->bindValue(':album_year',$album_year);
					$stmtTrack->bindValue(':track_artwork_path',$track_artwork_path);
					$stmtTrack->bindValue(':artist_artwork_path',$artist_artwork_path);
					$stmtTrack->bindValue(':album_artwork_path',$album_artwork_path);
					$stmtTrack->bindValue(':playlist_name',escapeQuery($track['playlist_name']));
					$stmtTrack->bindValue(':playlist_uri',$track['playlist_uri']);
					$stmtTrack->bindValue(':playable',$playable);
					$stmtTrack->bindValue(':availability',$track['availability']);
					$stmtTrack->execute();	
	
					$nb_track++;
					if ($nb_track % 10 === 0) {
						$w->write('Playlist▹' . $nb_track . '▹' . $nb_tracktotal . '▹' . $words[3], 'update_library_in_progress');
					}
				}
			}
	
			$getCount = 'select count(distinct uri) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_tracks = $stmt->fetch();

			$getCount = 'select count(distinct uri) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_tracks = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_albums = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_albums = $stmt->fetch();

			$getCount = 'select count(*) from playlists';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$playlists_count = $stmt->fetch();
			
			$insertCounter = "insert into counters values (:all_tracks,:starred_tracks,:all_artists,:starred_artists,:all_albums,:starred_albums,:playlists)";
			$stmt = $db->prepare($insertCounter);
			
			$stmt->bindValue(':all_tracks', $all_tracks[0]);
			$stmt->bindValue(':starred_tracks', $starred_tracks[0]);
			$stmt->bindValue(':all_artists', $all_artists[0]);
			$stmt->bindValue(':starred_artists', $starred_artists[0]);
			$stmt->bindValue(':all_albums', $all_albums[0]);
			$stmt->bindValue(':starred_albums', $starred_albums[0]);
			$stmt->bindValue(':playlists', $playlists_count[0]);
			$stmt->execute();
	
			$elapsed_time = time() - $words[3];
	
			displayNotificationWithArtwork("\nPlaylist " . $playlist['name'] . " has been updated (" . $nb_track . " tracks) - it took " . beautifyTime($elapsed_time), getPlaylistArtwork($w, 'black', $playlist['uri'], true));
			
			unlink($w->data() . "/update_library_in_progress");

		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $db);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}
		
		
	} else {
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
}

/**
 * removeUpdateLibraryInProgressFile function.
 *
 * @access public
 * @return void
 */
function removeUpdateLibraryInProgressFile() {
	$w = new Workflows('com.vdesabou.spotify.mini.player');
	unlink($w->data() . "/update_library_in_progress");
}

/**
 * updatePlaylistList function.
 *
 * @access public
 * @param mixed $jsonData
 * @return void
 */
function updatePlaylistList($jsonData) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');

	$in_progress_data = $w->read('update_library_in_progress');

	//
	// Read settings from DB
	//
	$dbfile = $w->data() . '/settings.db';
	try {
		$dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
		$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$getSettings = 'select theme from settings';
		$stmt = $dbsettings->prepare($getSettings);
		$setting = $stmt->fetch();
		$theme = $setting[0];
	} catch (PDOException $e) {
		handleDbIssuePdo('new', $dbsettings);
		$dbsettings=null;
		unlink($w->data() . "/update_library_in_progress");
		return;
	}

	$words = explode('▹', $in_progress_data);

	putenv('LANG=fr_FR.UTF-8');

	ini_set('memory_limit', '512M');

	//try to decode it
	$json = json_decode($jsonData, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		$nb_playlist_total = count($json);

		$w->write('Playlist List▹0▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');

		$dbfile = $w->data() . '/library.db';
		
		try {
			$db = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			$db->exec("drop table counters");
			$db->exec("create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)");
	
			$getPlaylists = "select * from playlists where name=:name and username=:username";
			$stmt = $db->prepare($getPlaylists);
			
			$insertPlaylist = "insert into playlists values (:uri,:name,:count_tracks,:owner,:username,:playlist_artwork_path,:ownedbyuser)";
			$stmtPlaylist = $db->prepare($insertPlaylist);

			$insertTrack = "insert into tracks values (:starred,:popularity,:uri,:album_uri,:artist_uri,:track_name,:album_name,:artist_name,:album_year,:track_artwork_path,:artist_artwork_path,:album_artwork_path,:playlist_name,:playlist_uri,:playable,:availability)";
			$stmtTrack = $db->prepare($insertTrack);
						
			foreach ($json as $playlist) {
				
				$stmt->bindValue(':name', escapeQuery($playlist['name']));
				$stmt->bindValue(':username', $playlist['username']);
				$stmt->execute();
				
				$noresult=true;
				while ($playlists = $stmt->fetch()) {
					$noresult=false;
					break;
				}
									
				$nb_playlist++;
				if ($nb_playlist % 4 === 0) {
					$w->write('Playlist List▹' . $nb_playlist . '▹' . $nb_playlist_total . '▹' . $words[3], 'update_library_in_progress');
				}

				// Add the new playlist
				if ($noresult == true) {
					displayNotification("Added playlist " . $playlist['name'] . "\n");
					$playlist_artwork_path = getPlaylistArtwork($w, 'black', $playlist['uri'], true);
	
					if ($playlist['ownedbyuser'] == true) {
						$ownedbyuser = 1;
					} else {
						$ownedbyuser = 0;
					}

					$stmtPlaylist->bindValue(':uri', $playlist['uri']);
					$stmtPlaylist->bindValue(':name', escapeQuery($playlist['name']));
					$stmtPlaylist->bindValue(':count_tracks', count($playlist['tracks']));
					$stmtPlaylist->bindValue(':owner', $playlist['owner']);
					$stmtPlaylist->bindValue(':username', $playlist['username']);
					$stmtPlaylist->bindValue(':playlist_artwork_path', $playlist_artwork_path);
					$stmtPlaylist->bindValue(':ownedbyuser', $ownedbyuser);
					$stmtPlaylist->execute();
				
					foreach ($playlist['tracks'] as $track) {
	
						if ($track['starred'] == true) {
							$starred = 1;
						} else {
							$starred = 0;
						}
	
						if ($track['playable'] == true) {
							$playable = 1;
						} else {
							$playable = 0;
						}
	
						//
						// Download artworks
						$track_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['uri'], true);
						$artist_artwork_path = getArtistArtwork($w, $theme, $track['artist_name'], true);
						$album_artwork_path = getTrackOrAlbumArtwork($w, $theme, $track['album_uri'], true);
	
						$album_year = 1995;
	
						$stmtTrack->bindValue(':starred', $starred);	
						$stmtTrack->bindValue(':popularity', $track['popularity']);
						$stmtTrack->bindValue(':uri',$track['uri']);
						$stmtTrack->bindValue(':album_uri',$track['album_uri']);
						$stmtTrack->bindValue(':artist_uri',$track['artist_uri']);
						$stmtTrack->bindValue(':track_name',escapeQuery($track['name']));
						$stmtTrack->bindValue(':album_name',escapeQuery($track['album_name']));
						$stmtTrack->bindValue(':artist_name',escapeQuery($track['artist_name']));
						$stmtTrack->bindValue(':album_year',$album_year);
						$stmtTrack->bindValue(':track_artwork_path',$track_artwork_path);
						$stmtTrack->bindValue(':artist_artwork_path',$artist_artwork_path);
						$stmtTrack->bindValue(':album_artwork_path',$album_artwork_path);
						$stmtTrack->bindValue(':playlist_name',escapeQuery($track['playlist_name']));
						$stmtTrack->bindValue(':playlist_uri',$track['playlist_uri']);
						$stmtTrack->bindValue(':playable',$playable);
						$stmtTrack->bindValue(':availability',$track['availability']);
						$stmtTrack->execute();
	
					}
				} else {
					continue;
				}
			}
	
			// check for deleted playlists	
			$getPlaylists = "select * from playlists";
			$stmt = $db->prepare($getPlaylists);
			$stmt->execute();

			while ($pl = $stmt->fetch()) {
				$found = 0;
				foreach ($json as $playlist) {
					if (escapeQuery($playlist['name']) == escapeQuery($pl[1]) && $playlist['username'] == $pl[4]) {
						$found = 1;
						break;
					}
				}
				if ($found != 1) {
					$deleteFromPlaylist="delete from playlists where uri=:uri";
					$stmtDelete = $db->prepare($deleteFromPlaylist);
					$stmtDelete->bindValue(':uri', $pl[0]);
					$stmtDelete->execute();

					$deleteFromTracks="delete from tracks where playlist_uri=:uri";
					$stmtDelete = $db->prepare($deleteFromTracks);
					$stmtDelete->bindValue(':uri', $pl[0]);
					$stmtDelete->execute();
					displayNotification("Playlist " . escapeQuery($pl[1]) . " was removed" . "\n");
				}			
			}
	
			$getCount = 'select count(distinct uri) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_tracks = $stmt->fetch();

			$getCount = 'select count(distinct uri) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_tracks = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct artist_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_artists = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$all_albums = $stmt->fetch();
			
			$getCount = 'select count(distinct album_name) from tracks where starred=1';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$starred_albums = $stmt->fetch();

			$getCount = 'select count(*) from playlists';
			$stmt = $db->prepare($getCount);
			$stmt->execute();
			$playlists_count = $stmt->fetch();
			
			$insertCounter = "insert into counters values (:all_tracks,:starred_tracks,:all_artists,:starred_artists,:all_albums,:starred_albums,:playlists)";
			$stmt = $db->prepare($insertCounter);
			
			$stmt->bindValue(':all_tracks', $all_tracks[0]);
			$stmt->bindValue(':starred_tracks', $starred_tracks[0]);
			$stmt->bindValue(':all_artists', $all_artists[0]);
			$stmt->bindValue(':starred_artists', $starred_artists[0]);
			$stmt->bindValue(':all_albums', $all_albums[0]);
			$stmt->bindValue(':starred_albums', $starred_albums[0]);
			$stmt->bindValue(':playlists', $playlists_count[0]);
			$stmt->execute();
	
			$elapsed_time = time() - $words[3];
			displayNotification("Playlist list has been updated - it took " . beautifyTime($elapsed_time));
	
			unlink($w->data() . "/update_library_in_progress");
		
		} catch (PDOException $e) {
			handleDbIssuePdo($theme, $db);
			$dbsettings=null;
			$db=null;
			unlink($w->data() . "/update_library_in_progress");
			return;
		}
	} else {
		//it's not JSON. Log error
		displayNotification("ERROR: JSON data is not valid!");
	}
}

/**
 * handleDbIssue function.
 *
 * @access public
 * @param mixed $theme
 * @return void
 */
function handleDbIssue($theme) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');
	$w->result(uniqid(), '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', null, '');

	$w->result(uniqid(), serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_library' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/' . $theme . '/' . 'update.png', 'yes', null, '');

	echo $w->toxml();
}

/**
 * handleDbIssuePdo function.
 *
 * @access public
 * @param mixed $theme
 * @param mixed $dbhandle
 * @return void
 */
function handleDbIssuePdo($theme, $dbhandle) {
	$w = new Workflows('com.vdesabou.spotify.mini.player');
	$w->result(uniqid(), '', 'Database Error: ' . $dbhandle->errorInfo()[0] . ' ' . $dbhandle->errorInfo()[1] . ' ' . $dbhandle->errorInfo()[2], '', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', null, '');
	$w->result(uniqid(), serialize(array('' /*track_uri*/ , '' /* album_uri */ , '' /* artist_uri */ , '' /* playlist_uri */ , '' /* spotify_command */ , '' /* query */ , '' /* other_settings*/ , 'update_library' /* other_action */ , '' /* alfred_playlist_uri */ , ''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/' . $theme . '/' . 'update.png', 'yes', null, '');
	echo $w->toxml();
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
 * getArtistUriFromName function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $artist
 * @return void
 */
function getArtistUriFromName($w, $theme, $artist) {
	$getArtists = "select artist_uri,artist_artwork_path,artist_biography from artists where artist_name='" . $artist . "'";

	$dbfile = $w->data() . "/library.db";
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getArtists\" 2>&1", $artists, $returnValue);

	if ($returnValue != 0) {
		handleDbIssue($theme);
		return "";
	}

	if (count($artists) > 0) {

		$theartist = explode("	", $artists[0]);
		return $theartist[0];
	}
	return "";
}

/**
 * getAlbumUriFromName function.
 *
 * @access public
 * @param mixed $w
 * @param mixed $theme
 * @param mixed $album
 * @param mixed $artist
 * @return void
 */
function getAlbumUriFromName($w, $theme, $album, $artist) {
	$getTracks = "select album_uri from tracks where album_name='" . $album . "' and artist_name='" . $artist . "'";

	$dbfile = $w->data() . "/library.db";
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

	if ($returnValue != 0) {
		handleDbIssue($theme);
		return "";
	}

	if (count($tracks) > 0) {

		$thealbum = explode("	", $tracks[0]);
		return $thealbum[0];
	}
	return "";
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
function mb_unserialize($string) {
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
 * @return void
 */
function getLyrics($w, $artist, $title) {
	$query_artist = $artist;
	$query_title = $title;

	if (stristr($query_artist, 'feat.')) {
		$query_artist = stristr($query_artist, 'feat.', true);
	}
	elseif (stristr($query_artist, 'featuring')) {
		$query_artist = stristr($query_artist, 'featuring', true);
	}
	elseif (stristr($query_title, ' con ')) {
		$query_title = stristr($query_title, ' con ', true);
	}
	elseif (stristr($query_artist, ' & ')) {
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
	}
	elseif (stristr($query_title, 'featuring')) {
		$query_title = stristr($query_title, 'featuring', true);
	}
	elseif (stristr($query_title, ' con ')) {
		$query_title = stristr($query_title, ' con ', true);
	}
	elseif (stristr($query_title, '(includes')) {
		$query_title = stristr($query_title, '(includes', true);
	}
	elseif (stristr($query_title, '(live at')) {
		$query_title = stristr($query_title, '(live at', true);
	}
	elseif (stristr($query_title, 'revised')) {
		$query_title = stristr($query_title, 'revised', true);
	}
	elseif (stristr($query_title, '(19')) {
		$query_title = stristr($query_title, '(19', true);
	}
	elseif (stristr($query_title, '(20')) {
		$query_title = stristr($query_title, '(20', true);
	}
	elseif (stristr($query_title, '- 19')) {
		$query_title = stristr($query_title, '- 19', true);
	}
	elseif (stristr($query_title, '- 20')) {
		$query_title = stristr($query_title, '- 20', true);
	}

	$query_title = str_replace('&', 'and', $query_title);
	$query_title = str_replace('$', 's', $query_title);
	$query_title = strip_string(trim($query_title));
	$query_title = str_replace(' - ', '-', $query_title);
	$query_title = str_replace(' ', '-', $query_title);
	$query_title = rtrim($query_title, '-');

	$uri = strtolower('http://www.lyrics.com/' . $query_title .'-lyrics-' . $query_artist . '.html');

	$error = false;
	$no_match = false;

	$file = $w->request($uri);

	preg_match('/<div id="lyric_space">(.*?)<\/div>/s', $file, $lyrics);

	$lyrics = (empty($lyrics[1])) ? '' : $lyrics[1];

	if (empty($file)) {
		$error = true;
	}
	elseif (empty($lyrics) || stristr($lyrics, 'we do not have the lyric for this song') || stristr($lyrics, 'lyrics are currently unavailable') || stristr($lyrics, 'your name will be printed as part of the credit')) {
		$no_match = true;
	}
	else {
		if (strstr($lyrics, 'Ã') && strstr($lyrics, '©')) $lyrics = utf8_decode($lyrics);

		$lyrics = trim(str_replace('<br />', '<br>', $lyrics));

		if (strstr($lyrics, '<br>---')) $lyrics = strstr($lyrics, '<br>---', true);
	}

	if ($error) {
		displayNotification("Timeout or failure. Try again");
	}
	elseif ($no_match) {
		displayNotification("Sorry there is no match for this track");
	}
	else {
		$lyrics = strip_tags($lyrics);

		//$lyrics = (strlen($lyrics) > 1303) ? substr($lyrics,0,1300).'...' : $lyrics;

		if ($lyrics=="") {
			displayNotification("Sorry there is no match for this track");
		}
		else {
			echo "🎤 $title by $artist\n---------------------------\n$lyrics";
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
 * @return void
 */
function checkForUpdate($w, $last_check_update_time, $dbsettings) {

	if (time()-$last_check_update_time > 86400) {
		// update last_check_update_time
		$setSettings = "update settings set last_check_update_time=" . time();
		$dbsettings->exec($setSettings);

		if (! $w->internet()) {
			displayNotificationWithArtwork("Check for update error:
No internet connection", './images/warning.png');
			return;
		}

		// get local information
		if (!file_exists('./packal/package.xml')) {
			displayNotification("Error: this release has not been downloaded from Packal");
			return 1;
		}
		$xml = $w->read('./packal/package.xml');
		$workflow= new SimpleXMLElement($xml);
		$local_version = $workflow->version;
		$remote_json = "https://raw.githubusercontent.com/vdesabou/alfred-spotify-mini-player/master/remote.json";

		// get remote information
		$jsonDataRemote = $w->request($remote_json);

		if (empty($jsonDataRemote)) {
			displayNotification("Check for update error:
the export.json " . $remote_json . " file cannot be found");
			return 1;
		}

		$json = json_decode($jsonDataRemote, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			$download_url = $json['download_url'];
			$remote_version = $json['version'];
			$description = $json['description'];

			if ($local_version < $remote_version) {

				$workflow_file_name = exec('printf $HOME') . '/Downloads/spotify-app-miniplayer-' . $remote_version . '.alfredworkflow';
				$fp = fopen($workflow_file_name , 'w+');
				$options = array(
					CURLOPT_FILE => $fp
				);
				$w->request("$download_url", $options);

				return array($remote_version, $workflow_file_name, $description);
			}

		}
		else {
			displayNotification("Check for update error:
remote.json error");
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
function doWebApiRequest($w,$url) {

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
 * beautifyTime function.
 *
 * @access public
 * @param mixed $seconds
 * @return void
 */
function beautifyTime($seconds) {
	$m = floor($seconds / 60);
	$s = $seconds % 60;
	$s = ($s < 10) ? "0$s" : "$s";
	return  $m . "m" . $s . "s";
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

?>