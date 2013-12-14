<?php
require_once('workflows.php');

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

function escapeQuery($text)
{
    $text = str_replace("'", "’", $text);
    $text = str_replace('"', "’", $text);
    $text = str_replace("&apos;", "’", $text);
    $text = str_replace("`", "’", $text);
    $text = str_replace("&amp;", "&", $text);
    $text = str_replace("\\", " ", $text);
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

function getArtistURLFromTrack($w, $id)
{
    $html = $w->request("http://open.spotify.com/track/$id");

    if (!empty($html)) {
        preg_match_all('/.*?music:musician.*?content="(.*?)">.*?/is', $html, $m);
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
        foreach ($json as $playlist) {

            $nb_tracktotal += count($playlist['tracks']);

        }
        $w->write('Library⇾0⇾' . $nb_tracktotal, 'update_library_in_progress');

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table tracks (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, availability text)"';
        exec($sql);
        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
        exec($sql);


        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table playlists (uri text, name text, nb_tracks int, author text, username text, playlist_artwork_path text)"';
        exec($sql);

        $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table related (artist_name text, related_artist_name text, related_artist_uri text, related_artist_artwork_path text, PRIMARY KEY (artist_name, related_artist_name))"';
        exec($sql);
        
        $nb_track = 0;

        foreach ($json as $playlist) {
            $playlist_artwork_path = getPlaylistArtwork($w, $playlist['uri'], $playlist['username'], true);

            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\")"';
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

				// handle related artists here
				foreach ($track['related'] as $related) {
				
					$related_artist_artwork_path = getArtistArtwork($w, $related['name'], true);
				    
		            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert or ignore into related values (\"' . escapeQuery($track['artist_name']) . '\",\"' . escapeQuery($related['name']) . '\",\"' . $related['uri'] . '\",\"' . $related_artist_artwork_path . '\")"';
		            exec($sql);				
				}
				
                $nb_track++;
                if ($nb_track % 10 === 0) {
                    $w->write('Library⇾' . $nb_track . '⇾' . $nb_tracktotal, 'update_library_in_progress');
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

        echo "Library has been created (" . $all_tracks[0] . " tracks)";

        unlink($w->data() . "/update_library_in_progress");

        if (file_exists($w->data() . "/library.db")) {
            if (file_exists($w->home() . "/Spotify/spotify-app-miniplayer")) {
                exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
            }
        }

    } else {
        //it's not JSON. Log error
        echo "ERROR: JSON data is not valid!";
    }
}

function updatePlaylist($jsonData)
{
    $w = new Workflows();

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    //try to decode it
    $json = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $nb_tracktotal = 0;
        foreach ($json as $playlist) {

            $nb_tracktotal += count($playlist['tracks']);

        }
        $w->write('Playlist⇾0⇾' . $nb_tracktotal, 'update_library_in_progress');

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
                //echo "$track[name]\n";

                $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values (' . $starred . ',' . $track['popularity'] . ',\"' . $track['uri'] . '\",\"' . $track['album_uri'] . '\",\"' . $track['artist_uri'] . '\",\"' . escapeQuery($track['name']) . '\",\"' . escapeQuery($track['album_name']) . '\",\"' . escapeQuery($track['artist_name']) . '\"' . ',' . $album_year . ',\"' . $track_artwork_path . '\"' . ',\"' . $artist_artwork_path . '\"' . ',\"' . $album_artwork_path . '\"' . ',\"' . escapeQuery($track['playlist_name']) . '\"' . ',\"' . $track['playlist_uri'] . '\"' . ',' . $playable . ',\"' . $track['availability'] . '\"' . ')"';

                exec($sql);

				// handle related artists here
				foreach ($track['related'] as $related) {
				
					$related_artist_artwork_path = getArtistArtwork($w, $related['name'], true);
				    
		            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert or ignore into related values (\"' . escapeQuery($track['artist_name']) . '\",\"' . escapeQuery($related['name']) . '\",\"' . $related['uri'] . '\",\"' . $related_artist_artwork_path . '\")"';
		            exec($sql);				
				}
				
                $nb_track++;
                if ($nb_track % 10 === 0) {
                    $w->write('Playlist⇾' . $nb_track . '⇾' . $nb_tracktotal, 'update_library_in_progress');
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

        echo "\nPlaylist has been updated (" . $nb_track . " tracks)";

        unlink($w->data() . "/update_library_in_progress");
    } else {
        //it's not JSON. Log error
        echo "ERROR: JSON data is not valid!";
    }
}

function updatePlaylistList($jsonData)
{
    $w = new Workflows();

    putenv('LANG=fr_FR.UTF-8');

    ini_set('memory_limit', '512M');

    //try to decode it
    $json = json_decode($jsonData, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $nb_playlist_total = count($json);

        $w->write('Playlist List⇾0⇾' . $nb_playlist_total, 'update_library_in_progress');

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
                $w->write('Playlist List⇾' . $nb_playlist . '⇾' . $nb_playlist_total, 'update_library_in_progress');
            }

            if ($returnValue != 0) {
                echo "ERROR: when processing playlist" . escapeQuery($playlist['name']) . " with uri " . $playlist['uri'] . "\n";
                continue;
            }

            // Add the new playlist
            if (count($playlists) == 0) {
                echo "Added playlist " . $playlist['name'] . "\n";
                $playlist_artwork_path = getPlaylistArtwork($w, $playlist['uri'], $playlist['username'], true);

                $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"' . $playlist['uri'] . '\",\"' . escapeQuery($playlist['name']) . '\",' . count($playlist['tracks']) . ',\"' . $playlist['owner'] . '\",\"' . $playlist['username'] . '\",\"' . $playlist_artwork_path . '\")"';
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
                    
					// handle related artists here
					foreach ($track['related'] as $related) {
					
						$related_artist_artwork_path = getArtistArtwork($w, $related['name'], true);
					    
			            $sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert or ignore into related values (\"' . escapeQuery($track['artist_name']) . '\",\"' . escapeQuery($related['name']) . '\",\"' . $related['uri'] . '\",\"' . $related_artist_artwork_path . '\")"';
			            exec($sql);				
					}
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
            echo "ERROR: when checking deleted playlist";
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
                    echo "Playlist " . escapeQuery($pl[1]) . " was removed" . "\n";
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

        echo "Playlist list has been updated";

        unlink($w->data() . "/update_library_in_progress");
    } else {
        //it's not JSON. Log error
        echo "ERROR: JSON data is not valid!";
    }
}


function floatToSquares($decimal)
{
    $squares = ($decimal < 1) ? floor($decimal * 10) : 10;
    return str_repeat("◼︎", $squares) . str_repeat("◻︎", 10 - $squares);
}

function validateAlfredPlaylist($uri,$user_name)
{
    $name = "";
    $playlistName = "";
	$wrong_user = true;
	
    $get_context = stream_context_create(array('http' => array('timeout' => 5)));
    @$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);

    if (!empty($get)) {
        preg_match_all("'<title>(.*?)</title>'si", $get, $name);

        if ($name[1]) {
        	$result=$name[1][0];
            $playlistName = strstr($result, ' by', true);
            $username = strstr($result, ' by');
			
			
			if($username == (' by ' . $user_name))
			{
				$wrong_user = false;
			}
                        
        }
        
    }

    return array($playlistName, $wrong_user, $username);
}

?>