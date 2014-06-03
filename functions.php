<?php

// Require the bundler.
require_once('alfred.bundler.php');

function installSpotifyAppIfNeeded($w)
{
	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer')) {
	    exec('mkdir -p ~/Spotify');
	    symlink($w->path() . '/spotify-app-miniplayer', $w->home() . '/Spotify/spotify-app-miniplayer');
	}
	
	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer/manifest.json')) {
		exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
		symlink($w->path() . '/spotify-app-miniplayer', $w->home() . '/Spotify/spotify-app-miniplayer');
	}
	
	if (!file_exists($w->home() . '/Spotify/spotify-app-miniplayer/manifest.json'))
	{
		return false;
	}
	return true;
}

function getFreeTcpPort()
{
	//avoid warnings like this PHP Warning:  fsockopen(): unable to connect to localhost (Connection refused) 
	error_reporting(~E_ALL);
	
	$from = 10000;
	$to = 20000;
	 
	//TCP ports
	$host = 'localhost';
	 
	for($port = $from; $port <= $to ; $port++)
	{
	  $fp = fsockopen($host , $port);
		if (!$fp)
		{
			//port is free
			return $port;
		}
		else 
		{
			// port open, close it
			fclose($fp);
		}
	}
	
	return 17693;
}

function escapeQuery($text)
{
    $text = str_replace("'", "’", $text);
    $text = str_replace('"', "’", $text);
    $text = str_replace("&apos;", "’", $text);
    $text = str_replace("`", "’", $text);
    $text = str_replace("&amp;", "&", $text);
    $text = str_replace("\\", " ", $text);
    $text = str_replace("$", "\\$", $text);
    return $text;
}

function checkIfResultAlreadyThere($results, $title)
{
    foreach ($results as $result) {
        if ($result[title]) {
            if ($result[title] == $title) {
                return true;
            }
        }
    }
    return false;
}

function displayNotification($output)
{
	// Load and use Terminal Notifier, a "helper" utility
	$tn = __load('Terminal-Notifier-Version-1.6' , '1.6.0' , 'utility', './bundler/Terminal-Notifier-Version-1.6.json' );
	exec( "$tn -title 'Spotify Mini Player' -sender 'com.spotify.miniplayer' -message '" . $output . "'" );
}

function displayNotificationWithArtwork($output,$artwork)
{

	copy($artwork,"/tmp/tmp");
	// Load and use Terminal Notifier, a "helper" utility
	$tn = __load('Terminal-Notifier-Version-1.6' , '1.6.0' , 'utility', './bundler/Terminal-Notifier-Version-1.6.json' );
	exec( "$tn -title 'Spotify Mini Player' -sender 'com.spotify.miniplayer' -contentImage '/tmp/tmp' -message '" . $output . "'" );
}

function getTrackOrAlbumArtwork($w,$theme, $spotifyURL, $fetchIfNotPresent)
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

function getPlaylistArtwork($w, $playlistURI, $username, $fetchIfNotPresent)
{

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
        $filename = "" . $username . "_" . $hrefs[4];
        $url = "http://open.spotify.com/user/" . $username . "/playlist/" . $hrefs[4];
    } else {
        //starred playlist
        $filename = "" . $username . "_" . $hrefs[3];
        $url = "http://open.spotify.com/user/" . $username . "/" . $hrefs[3];
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

function getArtistArtwork($w, $artist, $fetchIfNotPresent)
{
    $parsedArtist = urlencode($artist);

    if (!file_exists($w->data() . "/artwork")):
        exec("mkdir '" . $w->data() . "/artwork'");
    endif;

    $currentArtwork = $w->data() . "/artwork/" . hash('md5', $parsedArtist . ".png") . "/" . "$parsedArtist.png";


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

function getTrackArtworkURL($w, $type, $id)
{
    $html = $w->request("http://open.spotify.com/$type/$id");

    if (!empty($html)) {
        preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
        return (isset($m[1][0])) ? $m[1][0] : 0;
    }

    return 0;
}

function getPlaylistArtworkURL($w, $url)
{
    $html = $w->request($url);

    if (!empty($html)) {
        preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
        return (isset($m[1][0])) ? $m[1][0] : 0;
    }

    return 0;
}

function getArtistArtworkURL($w, $artist)
{
    $parsedArtist = urlencode($artist);
    $html = $w->request("http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json");
    $json = json_decode($html, true);
    // make more resilient to empty json responses
    if (!is_array($json) || empty($json['artist']['image'][1]['#text'])) {
        return '';
    }
    return $json[artist][image][1]['#text'];
}

function updateLibrary($jsonData)
{
    $w = new Workflows();

    $in_progress_data = $w->read('update_library_in_progress');

    $words = explode('⇾', $in_progress_data);
    	
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
        if (file_exists($w->data() . "/library.db")) {
            unlink($w->data() . "/library.db");
        }
        touch($w->data() . "/library.db");

        $nb_tracktotal = 0;
        
        // get playlists
        $playlists = $json['playlists'];
        
        foreach ($playlists as $playlist) {
            $nb_tracktotal += count($playlist['tracks']);
        }
        
        // get artists 
        $artists = $json['artists'];
        $w->write('Related Artists⇾0⇾' . count($artists) . '⇾' . $words[3], 'update_library_in_progress');

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table tracks (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, availability text)"';
        exec($sql);
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
        exec($sql);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table user (uri text, username text, name text, image text)"';
        exec($sql);
        
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table playlists (uri text, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean)"';
        exec($sql);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table artists (artist_name text, artist_uri text, artist_artwork_path text, artist_biography text, artist_popularity int, artist_years_from text, artist_years_to text, related_artist_name text, related_artist_uri text, related_artist_artwork_path text, PRIMARY KEY (artist_name, related_artist_name))"';
        exec($sql);
        
        

		// Handle user
        $user = $json['user'];
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into user values (\"' . $user['uri'] . '\",\"' . escapeQuery($user['username']) . '\",\"' . escapeQuery($user['name']) . '\",\"' . $user['image'] . '\"' . ')"';
        exec($sql);

		// Handle country
        $country = $json['country'];
        $setSettings = 'update settings set country_code=\"' . $country . '\"';
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        
		// Handle related artists
		$nb_artists = 0;
		foreach ($artists as $artist) {

			$artist_artwork_path = getArtistArtwork($w, $artist['artist_name'], true);
			
			$relateds = $artist['related'];			
			foreach ($relateds as $related) {

				$related_artist_artwork_path = getArtistArtwork($w, $related['name'], true);
				
				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert or ignore into artists values (\"' . escapeQuery($artist['artist_name']) . '\",\"' . $artist['artist_uri'] . '\",\"' . $related_artist_artwork_path . '\",\"' . escapeQuery($artist['biography']) . '\",' . $artist['popularity']  . ',\"' . $artist['years']['from'] . '\",\"' . $artist['years']['to'] . '\",\"' . escapeQuery($related['name']) . '\",\"' . $related['uri'] . '\",\"' . $related_artist_artwork_path . '\")"';
				exec($sql);
			}
			$nb_artists++;
            if ($nb_artists % 10 === 0) {
                $w->write('Related Artists⇾' . $nb_artists . '⇾' . count($artists) . '⇾' . $words[3], 'update_library_in_progress');
            }				
		}
		
		
		// Handle playlists
		$w->write('Library⇾0⇾' . $nb_tracktotal . '⇾' . $words[3], 'update_library_in_progress');
			
		$nb_track = 0;
			
        foreach ($playlists as $playlist) {
            $playlist_artwork_path = getPlaylistArtwork($w, $playlist['uri'], $playlist['username'], true);

            if ($playlist['ownedbyuser'] == true) {
                $ownedbyuser = 1;
            } else {
                $ownedbyuser = 0;
            }
                
            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\",' . $ownedbyuser . ')"';
            exec($sql);

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
                $track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
                $artist_artwork_path = getArtistArtwork($w, $track['artist_name'], true);
                $album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

                $album_year = 1995;

                $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';

                exec($sql);
				
                $nb_track++;
                if ($nb_track % 10 === 0) {
                    $w->write('Library⇾' . $nb_track . '⇾' . $nb_tracktotal . '⇾' . $words[3], 'update_library_in_progress');
                }
            }
        }// end playlists
        
        $getCount = "select count(*) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

        $getCount = "select count(*) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

        $getCount = "select count(distinct artist_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

        $getCount = "select count(distinct artist_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

        $getCount = "select count(distinct album_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

        $getCount = "select count(distinct album_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
        exec($sql);

        $getCount = "select count(*) from playlists";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

        // update counters for playlists
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
        exec($sql);
        
        $elapsed_time = time() - $words[3];
        displayNotification("Library has been created (" . $all_tracks[0] . " tracks) - it took " . beautifyTime($elapsed_time));

        unlink($w->data() . "/update_library_in_progress");

        if (file_exists($w->data() . "/library.db")) {
            if (file_exists($w->home() . "/Spotify/spotify-app-miniplayer")) {
                exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
            }
        }

    } else {
        //it's not JSON. Log error
        displayNotification("ERROR: JSON data is not valid!");
    }
}

function updatePlaylist($jsonData)
{
    $w = new Workflows();
	
    $in_progress_data = $w->read('update_library_in_progress');

    $words = explode('⇾', $in_progress_data);
	
    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    //try to decode it
    $json = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $nb_tracktotal = 0;
        foreach ($json as $playlist) {

            $nb_tracktotal += count($playlist['tracks']);

        }
        $w->write('Playlist⇾0⇾' . $nb_tracktotal . '⇾' . $words[3], 'update_library_in_progress');

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "drop table counters"';
        exec($sql);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
        exec($sql);

        $nb_track = 0;

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from tracks where playlist_uri=\"' . $playlist['uri'] . '\""';
        exec($sql);

        foreach ($json as $playlist) {
            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "update playlists set nb_tracks=' . count($playlist['tracks']) . ' where uri=\"' . $playlist['uri'] . '\""';
            exec($sql);


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
                $track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
                $artist_artwork_path = getArtistArtwork($w, $track['artist_name'], true);
                $album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

                $album_year = 1995;

                $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';
                exec($sql);

                $nb_track++;
                if ($nb_track % 10 === 0) {
                    $w->write('Playlist⇾' . $nb_track . '⇾' . $nb_tracktotal . '⇾' . $words[3], 'update_library_in_progress');
                }
            }
        }

        $getCount = "select count(*) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

        $getCount = "select count(*) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

        $getCount = "select count(distinct artist_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

        $getCount = "select count(distinct artist_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

        $getCount = "select count(distinct album_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

        $getCount = "select count(distinct album_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
        exec($sql);

        $getCount = "select count(*) from playlists";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

        // update counters for playlists
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
        exec($sql);
		
		$elapsed_time = time() - $words[3];
		displayNotification("\nPlaylist has been updated (" . $nb_track . " tracks) - it took " . beautifyTime($elapsed_time));
        
        unlink($w->data() . "/update_library_in_progress");
    } else {
        //it's not JSON. Log error
        displayNotification("ERROR: JSON data is not valid!");
    }
}

function updatePlaylistList($jsonData)
{
    $w = new Workflows();

    $in_progress_data = $w->read('update_library_in_progress');

    $words = explode('⇾', $in_progress_data);
	
    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    //try to decode it
    $json = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $nb_playlist_total = count($json);

        $w->write('Playlist List⇾0⇾' . $nb_playlist_total . '⇾' . $words[3], 'update_library_in_progress');

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "drop table counters"';
        exec($sql);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
        exec($sql);

        foreach ($json as $playlist) {
            $playlists = array();
            $getPlaylists = "select * from playlists where name='" . escapeQuery($playlist['name']) . "'" . " and username='" . $playlist['username'] . "'";
            $dbfile = $w->data() . "/library.db";
            exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

            $nb_playlist++;
            if ($nb_playlist % 4 === 0) {
                $w->write('Playlist List⇾' . $nb_playlist . '⇾' . $nb_playlist_total . '⇾' . $words[3], 'update_library_in_progress');
            }

            if ($returnValue != 0) {
                displayNotification("ERROR: when processing playlist" . escapeQuery($playlist['name']) . " with uri " . $playlist['uri'] . "\n");
                continue;
            }

            // Add the new playlist
            if (count($playlists) == 0) {
                displayNotification("Added playlist " . $playlist['name'] . "\n");
                $playlist_artwork_path = getPlaylistArtwork($w, $playlist['uri'], $playlist['username'], true);

	            if ($playlist['ownedbyuser'] == true) {
	                $ownedbyuser = 1;
	            } else {
	                $ownedbyuser = 0;
	            }
            
                $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\",' . $ownedbyuser . ')"';
                exec($sql);

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
                    $track_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['uri'], true);
                    $artist_artwork_path = getArtistArtwork($w, $track['artist_name'], true);
                    $album_artwork_path = getTrackOrAlbumArtwork($w,$theme, $track['album_uri'], true);

                    $album_year = 1995;

                    $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';

                    exec($sql);
                    
                }
            } else {
                continue;
            }
        }

        // check for deleted playlists
        $playlists = array();

        $getPlaylists = "select * from playlists";

        $dbfile = $w->data() . "/library.db";

        exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

        if ($returnValue != 0) {
            displayNotification("ERROR: when checking deleted playlist");
        } else {
            foreach ($playlists as $pl):
                $pl = explode("	", $pl);

                $found = 0;
                foreach ($json as $playlist) {
                    if (escapeQuery($playlist['name']) == escapeQuery($pl[1]) && $playlist['username'] == $pl[4]) {
                        $found = 1;
                        break;
                    }
                }
                if ($found != 1) {
                    displayNotification("Playlist " . escapeQuery($pl[1]) . " was removed" . "\n");
                    $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from playlists where uri=\"' . $pl[0] . '\""';
                    exec($sql);
                    $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from tracks where playlist_uri=\"' . $pl[0] . '\""';
                    exec($sql);
                }
            endforeach;
        }

        $getCount = "select count(*) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_tracks);

        $getCount = "select count(*) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_tracks);

        $getCount = "select count(distinct artist_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_artists);

        $getCount = "select count(distinct artist_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_artists);

        $getCount = "select count(distinct album_name) from tracks";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $all_albums);

        $getCount = "select count(distinct album_name) from tracks where starred=1";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $starred_albums);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values (' . $all_tracks[0] . ',' . $starred_tracks[0] . ',' . $all_artists[0] . ',' . $starred_artists[0] . ',' . $all_albums[0] . ',' . $starred_albums[0] . ',' . '\"\"' . ')"';
        exec($sql);

        $getCount = "select count(*) from playlists";
        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists_count);

        // update counters for playlists
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists=' . $playlists_count[0] . '"';
        exec($sql);

		$elapsed_time = time() - $words[3];
        displayNotification("Playlist list has been updated - it took " . beautifyTime($elapsed_time));

        unlink($w->data() . "/update_library_in_progress");
    } else {
        //it's not JSON. Log error
        displayNotification("ERROR: JSON data is not valid!");
    }
}

function handleDbIssue($theme) {
	$w = new Workflows();
    $w->result(uniqid(), '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', null, '');

    $w->result(uniqid(), serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */, '' /* track_name */, '' /* album_name */, '' /* track_artwork_path */, '' /* artist_artwork_path */, '' /* album_artwork_path */, '' /* playlist_name */, '' /* playlist_artwork_path */, '' /* $alfred_playlist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/' . $theme . '/' . 'update.png', 'yes', null, '');

    echo $w->toxml();
}

function floatToSquares($decimal)
{
    $squares = ($decimal < 1) ? floor($decimal * 10) : 10;
    return str_repeat("◼︎", $squares) . str_repeat("◻︎", 10 - $squares);
}

function getArtistUriFromName($w,$theme,$artist) {
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

*/
function getLyrics($w,$artist,$title) {
	$query_artist = $artist;
	$query_title = $title;
	
	if(stristr($query_artist, 'feat.'))
	{
	        $query_artist = stristr($query_artist, 'feat.', true);
	}
	elseif(stristr($query_artist, 'featuring'))
	{
	        $query_artist = stristr($query_artist, 'featuring', true);
	}
	elseif(stristr($query_title, ' con '))
	{
	        $query_title = stristr($query_title, ' con ', true);
	}
	elseif(stristr($query_artist, ' & '))
	{
	        $query_artist = stristr($query_artist, ' & ', true);
	}
	
	$query_artist = str_replace('&', 'and', $query_artist);
	$query_artist = str_replace('$', 's', $query_artist);
	$query_artist = strip_string(trim($query_artist));
	$query_artist = str_replace(' - ', '-', $query_artist);
	$query_artist = str_replace(' ', '-', $query_artist);
	
	$query_title = str_ireplace(array('acoustic version', 'new album version', 'original album version', 'album version', 'bonus track', 'clean version', 'club mix', 'demo version', 'extended mix', 'extended outro', 'extended version', 'extended', 'explicit version', 'explicit', '(live)', '- live', 'live version', 'lp mix', '(original)', 'original edit', 'original mix edit', 'original version', '(radio)', 'radio edit', 'radio mix', 'remastered version', 're-mastered version', 'remastered digital version', 're-mastered digital version', 'remastered', 'remaster', 'remixed version', 'remix', 'single version', 'studio version', 'version acustica', 'versión acústica', 'vocal edit'), '', $query_title);
	
	if(stristr($query_title, 'feat.'))
	{
	        $query_title = stristr($query_title, 'feat.', true);
	}
	elseif(stristr($query_title, 'featuring'))
	{
	        $query_title = stristr($query_title, 'featuring', true);
	}
	elseif(stristr($query_title, ' con '))
	{
	        $query_title = stristr($query_title, ' con ', true);
	}
	elseif(stristr($query_title, '(includes'))
	{
	        $query_title = stristr($query_title, '(includes', true);
	}
	elseif(stristr($query_title, '(live at'))
	{
	        $query_title = stristr($query_title, '(live at', true);
	}
	elseif(stristr($query_title, 'revised'))
	{
	        $query_title = stristr($query_title, 'revised', true);
	}
	elseif(stristr($query_title, '(19'))
	{
	        $query_title = stristr($query_title, '(19', true);
	}
	elseif(stristr($query_title, '(20'))
	{
	        $query_title = stristr($query_title, '(20', true);
	}
	elseif(stristr($query_title, '- 19'))
	{
	        $query_title = stristr($query_title, '- 19', true);
	}
	elseif(stristr($query_title, '- 20'))
	{
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

    if(empty($file))
    {
            $error = true;
    }
    elseif(empty($lyrics) || stristr($lyrics, 'we do not have the lyric for this song') || stristr($lyrics, 'lyrics are currently unavailable') || stristr($lyrics, 'your name will be printed as part of the credit'))
    {
            $no_match = true;
    }
    else
    {
            if(strstr($lyrics, 'Ã') && strstr($lyrics, '©')) $lyrics = utf8_decode($lyrics);

            $lyrics = trim(str_replace('<br />', '<br>', $lyrics));

            if(strstr($lyrics, '<br>---')) $lyrics = strstr($lyrics, '<br>---', true);
    }	
    
	if($error)
	{
	        displayNotification("Timeout or failure. Try again");
	}
	elseif($no_match)
	{
	        displayNotification("Sorry there is no match for this track");
	}
	else
	{
			$lyrics = strip_tags($lyrics);
			
			//$lyrics = (strlen($lyrics) > 1303) ? substr($lyrics,0,1300).'...' : $lyrics;
			
			if($lyrics=="")
			{
				displayNotification("Sorry there is no match for this track");
			}
			else
			{
				echo "🎤 $title by $artist\n---------------------------\n$lyrics";
			}
	}
}

function strip_string($string)
{
        return preg_replace('/[^a-zA-Z0-9-\s]/', '', $string);
}

function checkForUpdate($w,$last_check_update_time) {
	
	if(time()-$last_check_update_time > 86400)
	{
		// update last_check_update_time	
	    $setSettings = "update settings set last_check_update_time=" . time();
	    $dbfile = $w->data() . "/settings.db";
	    exec("sqlite3 \"$dbfile\" \"$setSettings\"");

		// get local information		
		if (!file_exists('./packal/package.xml')) {
            displayNotification("Error: the package.xml file cannot be found");
            return;
		}
		$xml = $w->read('./packal/package.xml');
		$workflow= new SimpleXMLElement($xml);
    	$local_version = $workflow->version;
    	$remote_json = "https://raw.githubusercontent.com/vdesabou/alfred-spotify-mini-player/master/remote.json"; 

		// get remote information
        $jsonDataRemote = $w->request($remote_json);

        if (empty($jsonDataRemote)) {
            displayNotification("Error: the export.json " . $remote_json . " file cannot be found");
            return;
        }

        $json = json_decode($jsonDataRemote,true);
        if (json_last_error() === JSON_ERROR_NONE) {
        	$download_url = $json['download_url'];
        	$remote_version = $json['version'];
        	$description = $json['description'];
        	
        	if($local_version < $remote_version) {
	        	displayNotification("An update is available");
				
				$workflow_file_name = $w->home() . '/Downloads/spotify-app-miniplayer-' . $remote_version . '.alfredworkflow';
                $fp = fopen($workflow_file_name , 'w+');
                $options = array(
                    CURLOPT_FILE => $fp
                );
                $w->request("$download_url", $options);
                
                return array($remote_version,$workflow_file_name,$description);	        	
        	}
        	
        }
        else {
			displayNotification("Error: check for update failed: remote.json error");	        
        }	

	}
}

/* 
	Thanks to Spotifious code 
	
	https://github.com/citelao/Spotify-for-Alfred
*/
function beautifyTime($seconds) {
	$m = floor($seconds / 60);
	$s = $seconds % 60;
	$s = ($s < 10) ? "0$s" : "$s";
	return  $m . "m" . $s . "s";
}

function startswith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}

?>