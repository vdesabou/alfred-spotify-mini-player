<?php

// Turn off all error reporting
error_reporting(0);

require('functions.php');
require_once('workflows.php');

$query = escapeQuery($argv[1]);
# thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
$query = iconv('UTF-8-MAC', 'UTF-8', $query);

$w = new Workflows();


//
// check for library update in progress
if (file_exists($w->data() . '/update_library_in_progress')) {
    if (file_exists($w->data() . '/library.db')) {
        $in_progress_data = $w->read('update_library_in_progress');

        if (substr_count($in_progress_data, '→') == 2) {
            $words = explode('→', $in_progress_data);

            if ($words[0] == 'Playlist List') {
                $type = 'playlists';
            } else {
                $type = 'tracks';
            }
            $w->result('', $w->data() . '/update_library_in_progress', $words[0] . ' update in progress: ' . floatToSquares(intval($words[1]) / intval($words[2])), $words[1] . '/' . $words[2] . ' ' . $type . ' processed so far (if no progress, use spot_mini_kill_update command to stop it)', './images/update.png', 'no', '');
        } else {
            $w->result('', $w->data() . '/update_library_in_progress', 'Update in progress: ' . floatToSquares(0), '0 tracks processed so far (if no progress, use spot_mini_kill_update command to stop it)', './images/update.png', 'no', '');
        }
    } else {
        $w->result('', $w->data() . '/update_library_in_progress', 'Library update seems broken', 'You can kill it by using spot_mini_kill_update command', './images/warning.png', 'no', '');
    }


    echo $w->toxml();
    return;
}

//
// Install spotify-app-miniplayer app if needed
// very first time use
//
if(!installSpotifyAppIfNeeded($w))
{
	$w->result('', '', 'Unable to install properly Spotify Mini Player App in ~/Spotify/spotify-app-miniplayer', 'Report to the author (use spot_mini_debug command to generate a tgz file)', './images/warning.png', 'no', '');
    echo $w->toxml();
    return;
}


//
// Read settings from DB
//
$getSettings = 'select * from settings';
$dbfile = $w->data() . '/settings.db';
exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

if ($returnValue != 0) {
    if (file_exists($w->data() . '/settings.db')) {
        unlink($w->data() . '/settings.db');
    }
}


//
// Create settings.db with default values if needed
//
if (!file_exists($w->data() . '/settings.db')) {
    touch($w->data() . '/settings.db');

    $sql = 'sqlite3 "' . $w->data() . '/settings.db" ' . ' "create table settings (all_playlists boolean, is_spotifious_active boolean, is_alfred_playlist_active boolean, is_displaymorefrom_active boolean, max_results int, alfred_playlist_uri text, country_code text)"';
    exec($sql);

    $sql = 'sqlite3 "' . $w->data() . '/settings.db" ' . '"insert into settings values (0,1,1,1,50,\"\",\"\")"';
    exec($sql);
}


foreach ($settings as $setting):

    $setting = explode("	", $setting);

    $all_playlists = $setting[0];
    $is_spotifious_active = $setting[1];
    $is_alfred_playlist_active = $setting[2];
    $is_displaymorefrom_active = $setting[3];
    $max_results = $setting[4];
    $alfred_playlist_uri = $setting[5];
    $country_code = $setting[6];
endforeach;


// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding('UTF-8');
if (mb_strlen($query) < 3 ||
    ((substr_count($query, '→') == 1) && (strpos('Settings→', $query) !== false))
) {
    if (substr_count($query, '→') == 0) {
        // check for correct configuration
        if (file_exists($w->data() . '/library.db')) {
            $getCounters = 'select * from counters';
            $dbfile = $w->data() . '/library.db';
            exec("sqlite3 -separator '	' \"$dbfile\" \"$getCounters\" 2>&1", $counters, $returnValue);

            if ($returnValue != 0) {
                $w->result('', '', 'There is a problem with the library, try to update it.', 'Select Update library below', './images/warning.png', 'no', '');

                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                echo $w->toxml();
                return;
            }

            foreach ($counters as $counter):

                $counter = explode("	", $counter);

                $all_tracks = $counter[0];
                $starred_tracks = $counter[1];
                $all_artists = $counter[2];
                $starred_artists = $counter[3];
                $all_albums = $counter[4];
                $starred_albums = $counter[5];
                $nb_playlists = $counter[6];
            endforeach;

            if ($all_playlists == true) {
                $w->result('', '', 'Search for music in all your playlists', 'Begin typing at least 3 characters to start search' . ' (' . $all_tracks . ' tracks)', './images/allplaylists.png', 'no', '');
            } else {
                $w->result('', '', 'Search for music in your ★ playlist', 'Begin typing at least 3 characters to start search' . ' (' . $starred_tracks . ' tracks)', './images/star.png', 'no', '');
            }

            if ($is_displaymorefrom_active == true) {
                // get info on current song
                $command_output = exec("./track_info.sh 2>&1");

                if (substr_count($command_output, '→') > 0) {
                    $results = explode('→', $command_output);
                    $currentArtistArtwork = getArtistArtwork($w, $results[1], false);
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'playpause' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "🔈 " . $results[0], $results[2] . ' by ' . $results[1], ($results[3] == "playing") ? './images/pause.png' : './images/play.png', 'yes', '');
                    
                    $w->result('', serialize(array($results[4] /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'morefromthisartist' /* other_action */ ,'' /* alfred_playlist_uri */ ,$results[1]  /* artist_name */)), "🔈👤 " . $results[1], 'Query all albums/tracks from this artist online..', $currentArtistArtwork, 'yes', '');
                    
                    
                    
	                $getTracks = "select * from tracks where playable=1 and uri='" . $results[4] . "'" . " limit " . $max_results;

	
	                $dbfile = $w->data() . "/library.db";
	                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);
	
	                if ($returnValue != 0) {
	                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');
                 
	                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
	
	                    echo $w->toxml();
	                    return;
	                }
	
			        foreach ($tracks as $track):
			            $track = explode("	", $track);
						            
				        $getPlaylists = "select * from playlists where uri='" . $track[13] . "'";
				
				        $dbfile = $w->data() . "/library.db";
				        exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);
				
				        if ($returnValue != 0) {
				            $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');
				            
				            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
				
				            echo $w->toxml();
				            return;
				        }
				
				        foreach ($playlists as $playlist):
				            $playlist = explode("	", $playlist);
							
							if (checkIfResultAlreadyThere($w->results(), "🔈🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)") == false) {
				            	$w->result("spotify_mini-spotify-inplaylist-$playlist[1]", '', "🔈🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3] . " (" . $playlist[4] . ")", $playlist[5], 'no', "Playlist→" . $playlist[0] . "→");
				            }
				        endforeach;
			        endforeach;

                    

                }
            }
            if ($is_alfred_playlist_active == true) {
                $w->result('', '', 'Alfred Playlist', 'Control your Alfred Playlist', './images/alfred_playlist.png', 'no', 'Alfred Playlist→');
            }
            $w->result('', '', 'Playlists', 'Browse by playlist' . ' (' . $nb_playlists . ' playlists)', './images/playlists.png', 'no', 'Playlist→');
            if ($all_playlists == true) {
                $w->result('', '', 'Artists', 'Browse by artist' . ' (' . $all_artists . ' artists)', './images/artists.png', 'no', 'Artist→');
                $w->result('', '', 'Albums', 'Browse by album' . ' (' . $all_albums . ' albums)', './images/albums.png', 'no', 'Album→');
            } else {
                $w->result('', '', 'Artists', 'Browse by artist' . ' (' . $starred_artists . ' artists)', './images/artists.png', 'no', 'Artist→');
                $w->result('', '', 'Albums', 'Browse by album' . ' (' . $starred_albums . ' albums)', './images/albums.png', 'no', 'Album→');
            }
        } else {
			if (!file_exists($w->data() . '/library.db')) {
                $w->result('', '', 'Workflow is not configured', '1/ Select Open Spotify Mini Player App below and make sure it works 2/ Then select Install library below', './images/warning.png', 'no', '');
				
				$w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'open_spotify_export_app' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "1/ Open Spotify Mini Player App <spotify:app:miniplayer>", "If it doesn't work, restart Spotify multiple times and make sure you have a developer account", './images/app_miniplayer.png', 'yes', '');
				
				
            }

			$w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), '2/ Install library', "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
            echo $w->toxml();
            return;
        }

        if ($is_spotifious_active == true) {
            $spotifious_state = 'enabled';
        } else {
            $spotifious_state = 'disabled';
        }
        if ($is_alfred_playlist_active == true) {
            $alfred_playlist_state = 'enabled';
        } else {
            $alfred_playlist_state = 'disabled';
        }
        if ($all_playlists == true) {
            $w->result('', '', 'Settings', 'Search scope=<all>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Alfred Playlist is <' . $alfred_playlist_state . '>', './images/settings.png', 'no', 'Settings→');
        } else {
            $w->result('', '', 'Settings', 'Search scope=<only ★>, Max results=<' . $max_results . '>, Spotifious is <' . $spotifious_state . '>, Alfred Playlist is <' . $alfred_playlist_state . '>', './images/settings.png', 'no', 'Settings→');
        }

    } //
    // Settings
    //
    elseif (substr_count($query, '→') == 1) {
        if ($all_playlists == true) {
            // argument is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|query|other_settings|other_action|alfred_playlist_uri|artist_name
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'disable_all_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), 'Change Search Scope', 'Select to change to ★ playlist only', './images/search.png', 'yes', '');
            
        } else {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'enable_all_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), 'Change Search Scope', 'Select to change to ALL playlists', './images/search.png', 'yes', '');
        }

        $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), 'Update Library', "When done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
        $w->result('', '', "Configure Max Number of Results", "Number of results displayed. (it doesn't apply to your playlist list)", './images/numbers.png', 'no', 'Settings→MaxResults→');
        $w->result('', '', "Configure your Country Code", "This is needed to get available results when finding all albums/tracks from an artist", './images/country.png', 'no', 'Settings→Country→');

        if ($is_spotifious_active == true) {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'disable_spotifiuous' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Disable Spotifious", "Do not display Spotifious in default results", './images/uncheck.png', 'yes', '');
        } else {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'enable_spotifiuous' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Enable Spotifious", "Display Spotifious in default results", './images/check.png', 'yes', '');
        }
        if ($is_alfred_playlist_active == true) {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'disable_alfred_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Disable Alfred Playlist", "Do not display Alfred Playlist", './images/uncheck.png', 'yes', '');
        } else {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'enable_alfred_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Enable Alfred Playlist", "Display Alfred Playlist", './images/check.png', 'yes', '');
        }
        if ($is_displaymorefrom_active == true) {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'disable_displaymorefrom' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Disable \"Query all albums/tracks from this artist\"", "Disable the option which displays all albums and tracks from current artist", './images/uncheck.png', 'yes', '');
        } else {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'enable_displaymorefrom' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Enable \"Query all albums/tracks from this artist\"", "Enable the option which  displays all albums and tracks from current artist", './images/check.png', 'yes', '');
        }

        $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'open_spotify_export_app' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Open Spotify Mini Player App <spotify:app:miniplayer>", "Go to the Spotify Mini Player App in Spotify.", './images/app_miniplayer.png', 'yes', '');
    }
} else {
    ////////////
    //
    // NO DELIMITER
    //
    ////////////
    if (substr_count($query, '→') == 0) {
        //
        // Search categories for fast access
        //
        if (strpos(strtolower('playlist'), strtolower($query)) !== false) {
            $w->result('', '', 'Playlists', 'Browse by playlist', './images/playlists.png', 'no', 'Playlist→');
        } else if (strpos(strtolower('album'), strtolower($query)) !== false) {
            $w->result('', '', 'Albums', 'Browse by album', './images/albums.png', 'no', 'Album→');
        } else if (strpos(strtolower('artist'), strtolower($query)) !== false) {
            $w->result('', '', 'Artists', 'Browse by artist', './images/artists.png', 'no', 'Artist→');
        } else if (strpos(strtolower('alfred'), strtolower($query)) !== false) {
            $w->result('', '', 'Alfred Playlist', 'Control your Alfred Playlist', './images/alfred_playlist.png', 'no', 'Alfred Playlist→');
        } else if (strpos(strtolower('setting'), strtolower($query)) !== false) {
            $w->result('', '', 'Settings', 'Go to settings', './images/settings.png', 'no', 'Settings→');
        }


        //
        // Search in Playlists
        //

        $getPlaylists = "select * from playlists where name like '%" . $query . "%'";

        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

        if ($returnValue != 0) {
            $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

           
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

            echo $w->toxml();
            return;
        }

        foreach ($playlists as $playlist):
            $playlist = explode("	", $playlist);

            $w->result("spotify_mini-spotify-playlist-$playlist[1]", '', "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3] . " (" . $playlist[4] . ")", $playlist[5], 'no', "Playlist→" . $playlist[0] . "→");
        endforeach;


        //
        // Search artists
        //
        if ($all_playlists == false) {
            $getTracks = "select * from tracks where playable=1 and starred=1 and artist_name like '%" . $query . "%'" . " limit " . $max_results;
        } else {
            $getTracks = "select * from tracks where playable=1 and artist_name like '%" . $query . "%'" . " limit " . $max_results;
        }


        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

        if ($returnValue != 0) {
            $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

            echo $w->toxml();
            return;
        }

        foreach ($tracks as $track):
            $track = explode("	", $track);

            if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[7])) == false) {
                $w->result("spotify_mini-spotify-artist-" . $track[7], '', "👤 " . ucfirst($track[7]), "Get tracks from this artist", $track[10], 'no', "Artist→" . $track[7] . "→");
            }
        endforeach;


        //
        // Search everything
        //
        if ($all_playlists == false) {
            $getTracks = "select * from tracks where playable=1 and starred=1 and (artist_name like '%" . $query . "%' or album_name like '%" . $query . "%' or track_name like '%" . $query . "%')" . " limit " . $max_results;
        } else {
            $getTracks = "select * from tracks where playable=1 and (artist_name like '%" . $query . "%' or album_name like '%" . $query . "%' or track_name like '%" . $query . "%')" . " limit " . $max_results;
        }


        $dbfile = $w->data() . "/library.db";
        exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

        if ($returnValue != 0) {
            $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

            echo $w->toxml();
            return;
        }

        if (count($tracks) > 0) {
            $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
            if ($is_alfred_playlist_active == true) {
                $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
            }
            $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
        }
        foreach ($tracks as $track):
            $track = explode("	", $track);

            $subtitle = ($track[0] == true) ? "★ " : "";
            $subtitle = $subtitle . $track[6];

            if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                $w->result("spotify_mini-spotify-track" . $track[2], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                
            }
        endforeach;

        $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $query . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $query . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
        
        if ($is_spotifious_active == true) {
            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$query /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $query . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
        }
    } ////////////
    //
    // FIRST DELIMITER: Artist→, Album→, Playlist→, Alfred Playlist→, Settings→ or Online→artist uri
    //
    ////////////
    elseif (substr_count($query, '→') == 1) {
        $words = explode('→', $query);

        $kind = $words[0];

        if ($kind == "Playlist") {
            //
            // Search playlists
            //
            $theplaylist = $words[1];

            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_playlist_list' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update Playlist List (use it when you have added or removed a playlist)", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
            

            if (mb_strlen($theplaylist) < 3) {
                //
                // Display all playlists
                //
                $getPlaylists = "select * from playlists";

                $dbfile = $w->data() . "/library.db";

                exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                foreach ($playlists as $playlist):
                    $playlist = explode("	", $playlist);

                    $w->result("spotify_mini-spotify-playlist-$playlist[1]", '', "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3] . " (" . $playlist[4] . ")", $playlist[5], 'no', "Playlist→" . $playlist[0] . "→");
                endforeach;
            } else {
                $getPlaylists = "select * from playlists where ( name like '%" . $theplaylist . "%' or author like '%" . $theplaylist . "%')";

                $dbfile = $w->data() . "/library.db";

                exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                foreach ($playlists as $playlist):
                    $playlist = explode("	", $playlist);

                    $w->result("spotify_mini-spotify-playlist-$playlist[1]", '', "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks)", "by " . $playlist[3] . " (" . $playlist[4] . ")", $playlist[5], 'no', "Playlist→" . $playlist[0] . "→");
                endforeach;
            }
        } // search by Playlist end
        elseif ($kind == "Alfred Playlist") {
            //
            // Alfred Playlist
            //
            $playlist = $words[1];

            if ($alfred_playlist_uri == "") {
                $w->result("spotify_mini-spotify-alfredplaylist-set", '', "Set your Alfred playlist URI", "define the URI of your Alfred playlist", './images/alfred_playlist.png', 'no', 'Alfred Playlist→Set Alfred Playlist URI→');
            } else {
                $r = explode(':', $alfred_playlist_uri);

                $w->result("spotify_mini-spotify-alfredplaylist-browse", '', "Browse your Alfred playlist", "browse your alfred playlist", getPlaylistArtwork($w, $alfred_playlist_uri, $r[2], false), 'no', 'Playlist→' . $alfred_playlist_uri . '→');

                $w->result("spotify_mini-spotify-alfredplaylist-refresh", serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,$alfred_playlist_uri /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update your Alfred Playlist", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
                
                $w->result("spotify_mini-spotify-alfredplaylist-set", '', "Update your Alfred playlist URI", "define the URI of your Alfred playlist", './images/settings.png', 'no', 'Alfred Playlist→Set Alfred Playlist URI→');

            }
        } //  Alfred Playlist end
        elseif ($kind == "Artist") {
            //
            // Search artists
            //
            $artist = $words[1];


            if (mb_strlen($artist) < 3) {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 group by artist_name" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 group by artist_name" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                // display all artists
                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[7])) == false) {
                        $w->result("spotify_mini-spotify-artist-" . $track[7], '', "👤 " . ucfirst($track[7]), "Get tracks from this artist", $track[10], 'no', "Artist→" . $track[7] . "→");
                    }
                endforeach;
            } else {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and artist_name like '%" . $artist . "%'" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and artist_name like '%" . $artist . "%'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    if (checkIfResultAlreadyThere($w->results(), "👤 " . ucfirst($track[7])) == false) {
                        $w->result("spotify_mini-spotify-artist-" . $track[7], '', "👤 " . ucfirst($track[7]), "Get tracks from this artist", $track[10], 'no', "Artist→" . $track[7] . "→");
                    }
                endforeach;

                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $artist . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $artist . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$artist /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $artist . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
            }
        } // search by Artist end
        elseif ($kind == "Album") {
            //
            // Search albums
            //
            $album = $words[1];

            if (mb_strlen($album) < 3) {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 group by album_name" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 group by album_name" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                // display all albums
                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[6])) == false) {
                        $w->result("spotify_mini-spotify-album" . $track[6], '', ucfirst($track[6]), "by " . $track[7], $track[11], 'no', "Album→" . $track[6] . "→");
                    }
                endforeach;
            } else {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and album_name like '%" . $album . "%'" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and album_name like '%" . $album . "%'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[6])) == false) {
                        $w->result("spotify_mini-spotify-album" . $track[6], '', ucfirst($track[6]), "by " . $track[7], $track[11], 'no', "Album→" . $track[6] . "→");
                    }
                endforeach;


                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $album . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $album . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$album /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $album . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
            }
        } // search by Album end
        elseif ($kind == "Online") {
            if (substr_count($query, '@') == 1) {
                //
                // Search Artist Online
                //
                $tmp = $words[1];
                $words = explode('@', $tmp);
                $artist_uri = $words[0];
                $artist_name = $words[1];

                if ($country_code == "") {
                    $w->result('', '', "Country code is not configured", "Configure it now", './images/warning.png', 'no', '');
                    $w->result('', '', "Configure your Country Code", "This is needed to get available results when doing online lookups", './images/country.png', 'no', 'Settings→Country→');

                    echo $w->toxml();
                    return;
                }

                $json = $w->request("http://ws.spotify.com/lookup/1/.json?uri=" . trim($artist_uri) . "&extras=albumdetail");

                if (empty($json)) {
                    $w->result('', '', "Error: Spotify Metadata API returned empty result", "http://ws.spotify.com/lookup/1/.json?uri=" . $artist_uri . "&extras=albumdetail", './images/warning.png', 'no', '');
                    echo $w->toxml();
                    return;
                }

                $json = json_decode($json);
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $w->result('', '', "There was an error when retrieving online information", "Maximum stack depth exceeded", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $w->result('', '', "There was an error when retrieving online information", "Unexpected control character found", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $w->result('', '', "There was an error when retrieving online information", "Syntax error, malformed JSON", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_NONE:
                        foreach ($json->artist->albums as $key => $value) {
                            $album = array();
                            $album = $value->album;

                            // only display albums from the artist
                            if (strpos($album->{"artist-id"}, $artist_uri) !== false) {
                                $availability = array();
                                $availability = $album->availability;

                                if (strpos($availability->territories, $country_code) !== false) {
                                    if (checkIfResultAlreadyThere($w->results(), ucfirst($album->name)) == false) {
                                        $w->result("spotify_mini-spotify-online-album" . $album->name, '', ucfirst($album->name), "by " . $album->artist . " (" . $album->released . ")", getTrackOrAlbumArtwork($w, $album->href, false), 'no', "Online→" . $artist_uri . "@" . $album->artist . "@" . $album->href . "@" . $album->name);
                                    }
                                }
                            }
                        }
                        break;
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

                $json = $w->request("http://ws.spotify.com/lookup/1/.json?uri=$album_uri&extras=trackdetail");

                if (empty($json)) {
                    $w->result('', '', "Error: Spotify Metadata API returned empty result", "http://ws.spotify.com/lookup/1/.json?uri=" . $album_uri . "&extras=trackdetail", './images/warning.png', 'no', '');
                    echo $w->toxml();
                    return;
                }

                $json = json_decode($json);
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $w->result('', '', "There was an error when retrieving online information", "Maximum stack depth exceeded", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $w->result('', '', "There was an error when retrieving online information", "Unexpected control character found", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $w->result('', '', "There was an error when retrieving online information", "Syntax error, malformed JSON", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_NONE:
                        $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                        if ($is_alfred_playlist_active == true) {
                            $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                        }
                        $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                        foreach ($json->album->tracks as $key => $value) {
                            $w->result("spotify_mini-spotify-online-track-" . $value->name, serialize(array($value->href /*track_uri*/ ,$album_uri /* album_uri */ ,$artist_uri /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$artist_name  /* artist_name */)), ucfirst($artist_name) . " - " . $value->name, $album_name . " (" . $json->album->released . ")", getTrackOrAlbumArtwork($w, $value->href, false), 'yes', '');


                        }
                        break;
                }
            }

        } // Online mode end
    } ////////////
    //
    // SECOND DELIMITER: Artist→the_artist→tracks , Album→the_album→tracks, Playlist→the_playlist→tracks,Settings→Country→country or Settings→MaxResults→max_numbers, Alfred Playlist→Set Alfred Playlist URI→alfred_playlist_uri
    //
    ////////////
    elseif (substr_count($query, '→') == 2) {
        //
        // Get all songs for selected artist
        //

        $words = explode('→', $query);

        $kind = $words[0];
        if ($kind == "Artist") {
            //
            // display tracks for selected artists
            //
            $artist = $words[1];
            $track = $words[2];

            if (mb_strlen($track) < 3) {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and artist_name='" . $artist . "'" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and artist_name='" . $artist . "'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

                    echo $w->toxml();
                    return;
                }

                if (count($tracks) > 0) {
                    $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    if ($is_alfred_playlist_active == true) {
                        $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    }
                    $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    $subtitle = ($track[0] == true) ? "★ " : "";
                    $subtitle = $subtitle . $track[6];

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                        $w->result("spotify_mini-spotify-track-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                    }
                    if ($artist_uri == "")
                        $artist_uri = $track[4];
                endforeach;

                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $artist . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $artist . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$artist_uri . " ► " . $artist . " ►" /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $artist . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
            } else {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and (artist_name='" . $artist . "' and track_name like '%" . $track . "%')" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and artist_name='" . $artist . "' and track_name like '%" . $track . "%'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                    echo $w->toxml();
                    return;
                }

                if (count($tracks) > 0) {
                    $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    if ($is_alfred_playlist_active == true) {
                        $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    }
                    $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    $subtitle = ($track[0] == true) ? "★ " : "";
                    $subtitle = $subtitle . $track[6];

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                        $w->result("spotify_mini-spotify-track-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                    }
                endforeach;

                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $track . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $track . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$track /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $track . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
                
            }
        } // end of tracks by artist
        elseif ($kind == "Album") {
            //
            // display tracks for selected album
            //
            $album = $words[1];
            $track = $words[2];

            if (mb_strlen($track) < 3) {
                $album_uri = "";

                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and album_name='" . $album . "'" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and album_name='" . $album . "'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                    echo $w->toxml();
                    return;
                }

                if (count($tracks) > 0) {
                    $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    if ($is_alfred_playlist_active == true) {
                        $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    }
                    $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    $subtitle = ($track[0] == true) ? "★ " : "";
                    $subtitle = $subtitle . $track[6];

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                        $w->result("spotify_mini-spotify-track-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                    }
                    if ($album_uri == "")
                        $album_uri = $track[3];
                endforeach;

                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $album . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $album . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$album_uri . " ► " . $album . " ►"/* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $album . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
                
            } else {
                if ($all_playlists == false) {
                    $getTracks = "select * from tracks where playable=1 and starred=1 and (album_name='" . $album . "' and track_name like '%" . $track . "%')" . " limit " . $max_results;
                } else {
                    $getTracks = "select * from tracks where playable=1 and album_name='" . $album . "' and track_name like '%" . $track . "%'" . " limit " . $max_results;
                }


                $dbfile = $w->data() . "/library.db";
                exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                if ($returnValue != 0) {
                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

		            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                    echo $w->toxml();
                    return;
                }

                if (count($tracks) > 0) {
                    $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                    if ($is_alfred_playlist_active == true) {
                        $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                    }
                    $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                }

                foreach ($tracks as $track):
                    $track = explode("	", $track);

                    $subtitle = ($track[0] == true) ? "★ " : "";
                    $subtitle = $subtitle . $track[6];

                    if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                        $w->result("spotify_mini-spotify-track-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                    }
                endforeach;


                $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $track . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $track . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                if ($is_spotifious_active == true) {
                    $w->result('',  serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$track /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $track . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                }
                
               
            }
        } // end of tracks by album
        elseif ($kind == "Playlist") {
            //
            // display tracks for selected playlist
            //
            $theplaylisturi = $words[1];
            $thetrack = $words[2];

            $getPlaylists = "select * from playlists where uri='" . $theplaylisturi . "'";
            $dbfile = $w->data() . "/library.db";
            exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);

            if ($returnValue != 0) {
                $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

	            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                echo $w->toxml();
                return;
            }

            if (count($playlists) > 0) {
                $playlist = $playlists[0];
                $playlist = explode("	", $playlist);
                if (mb_strlen($thetrack) < 3) {
                    //
                    // display all tracks from playlist
                    //
                    $getTracks = "select * from tracks where playable=1 and playlist_uri='" . $theplaylisturi . "' limit " . $max_results;

                    $dbfile = $w->data() . "/library.db";
                    exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                    if ($returnValue != 0) {
                        $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

			            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                        echo $w->toxml();
                        return;
                    }
                    if (count($tracks) > 0) {
                        $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                        if ($is_alfred_playlist_active == true) {
                            $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                        }
                        $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                    }

                    $subtitle = "Launch Playlist";
                    if ($is_alfred_playlist_active == true &&
                        $playlist[1] != "Alfred Playlist"
                    ) {
                        $subtitle = "$subtitle ,⇧ → add playlist to ♫";
                    }
                    $w->result("spotify_mini-spotify-playlist-$playlist[1]", serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,$playlist[0] /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,''  /* artist_name */)), "🎵 " . ucfirst($playlist[1]) . " (" . $playlist[2] . " tracks), by " . $playlist[3] . " (" . $playlist[4] . ")", $subtitle, $playlist[5], 'yes', '');
                    
                    

                    $w->result("spotify_mini-spotify-update-$playlist[1]", serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,$playlist[0] /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_playlist' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update playlist " . ucfirst($playlist[1]) . " by " . $playlist[3], "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');
                    
                    foreach ($tracks as $track):
                        $track = explode("	", $track);

                        if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                            $subtitle = ($track[0] == true) ? "★ " : "";
                            $subtitle = $subtitle . $track[6];
                            $w->result("spotify_mini-spotify-playlist-track-" . $playlist[1] . "-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                        }
                    endforeach;
                } else {
                    $getTracks = "select * from tracks where playable=1 and playlist_uri='" . $theplaylisturi . "' and (artist_name like '%" . $thetrack . "%' or album_name like '%" . $thetrack . "%' or track_name like '%" . $thetrack . "%')" . " limit " . $max_results;


                    $dbfile = $w->data() . "/library.db";
                    exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\" 2>&1", $tracks, $returnValue);

                    if ($returnValue != 0) {
                        $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

			            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');


                        echo $w->toxml();
                        return;
                    }
                    if (count($tracks) > 0) {
                        $subtitle = "  ⌥ (play album) ⌘ (play artist) ctrl (lookup online)";
                        if ($is_alfred_playlist_active == true) {
                            $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                        }
                        $w->result('help', 'help', "Select a track to play it", $subtitle, './images/info.png', 'no', '');
                    }


                    foreach ($tracks as $track):
                        $track = explode("	", $track);

                        $subtitle = $track[6] . "  ⌥ (play album) ⌘ (play artist)";
                        if ($is_alfred_playlist_active == true) {
                            $subtitle = "$subtitle fn (add track to ♫) ⇧ (add album to ♫)";
                        }

                        if (checkIfResultAlreadyThere($w->results(), ucfirst($track[7]) . " - " . $track[5]) == false) {
                            $subtitle = ($track[0] == true) ? "★ " : "";
                            $subtitle = $subtitle . $track[6];
                            $w->result("spotify_mini-spotify-playlist-track-" . $playlist[1] . "-" . $track[5], serialize(array($track[2] /*track_uri*/ ,$track[3] /* album_uri */ ,$track[4] /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,$alfred_playlist_uri /* alfred_playlist_uri */ ,$track[7]  /* artist_name */)), ucfirst($track[7]) . " - " . $track[5], $subtitle, $track[9], 'yes', '');
                        }
                    endforeach;

                    $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,"activate (open location \"spotify:search:" . $thetrack . "\")" /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $thetrack . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '');
                    if ($is_spotifious_active == true) {
                        $w->result('',  serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,$thetrack /* query */ ,'' /* other_settings*/ , '' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Search for " . $thetrack . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '');
                    }
                }
            }
        } // end of tracks by Playlist
        elseif ($kind == "Settings") {
            $setting_kind = $words[1];
            $the_query = $words[2];

            if ($setting_kind == "MaxResults") {
                if (mb_strlen($the_query) == 0) {
                    $w->result('', '', "Enter the Max Results number (must be greater than 0):", "Recommendation is between 50 to 100", './images/settings.png', 'no', '');
                } else {
                    // max results has been set
                    if (is_numeric($the_query) == true && $the_query > 0) {
                        $w->result('', "||||||" . "MAX_RESULTS→" . $the_query . "|||", "Max Results will be set to <" . $the_query . ">", "Type enter to validate the Max Results", './images/settings.png', 'yes', '');
                    } else {
                        $w->result('', '', "The Max Results value entered is not valid", "Please fix it", './images/warning.png', 'no', '');

                    }
                }
            } else if ($setting_kind == "Country") {

                $json = $w->request("https://raw.github.com/johannesl/Internationalization/master/countrycodes.json");

                if (empty($json)) {
                    $w->result('', '', "Error: retrieving country code list", "url is https://raw.github.com/johannesl/Internationalization/master/countrycodes.json", './images/warning.png', 'no', '');
                    echo $w->toxml();
                    return;
                }

                $json = json_decode($json);
                switch (json_last_error()) {
                    case JSON_ERROR_DEPTH:
                        $w->result('', '', "There was an error when retrieving online information", "Maximum stack depth exceeded", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $w->result('', '', "There was an error when retrieving online information", "Unexpected control character found", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_SYNTAX:
                        $w->result('', '', "There was an error when retrieving online information", "Syntax error, malformed JSON", './images/warning.png', 'no', '');
                        break;
                    case JSON_ERROR_NONE:
                        if (mb_strlen($the_query) == 0) {
                            $w->result('', '', "Select your country:", "This is needed to get accurate results from online spotify lookups ", './images/country.png', 'no', '');
                            foreach ($json as $key => $value) {
                                $w->result('', "||||||" . "COUNTRY→" . $value . "|||", ucfirst($key), $value, './images/country.png', 'yes', '');
                            }
                        } else {
                            foreach ($json as $key => $value) {
                                if (strpos(strtolower($key), strtolower($the_query)) !== false) {
                                    $w->result('', "||||||" . "COUNTRY→" . $value . "|||", ucfirst($key), $value, './images/country.png', 'yes', '');
                                }
                            }
                        }
                        break;
                }
            }
        } // end of Settings
        elseif ($kind == "Alfred Playlist") {
            $alfred_playlist_uri = $words[2];

            if (mb_strlen($alfred_playlist_uri) == 0) {
                $w->result('', '', "Enter the Alfred Spotify URI:", "Create the playlist in Spotify(shall be named <Alfred Playlist>, right click on it and select copy spotify URI", './images/settings.png', 'no', '');
            } else {
                // alfred_playlist_uri has been set
                               
                
                if (substr_count($alfred_playlist_uri, ':') == 4) {
                
					// get name of user by searching for spotify:user:@:starred playlist
					
					$getPlaylists = "select * from playlists where uri='" . "spotify:user:@:starred" . "'";
					
	                $dbfile = $w->data() . "/library.db";
	
	                exec("sqlite3 -separator '	' \"$dbfile\" \"$getPlaylists\" 2>&1", $playlists, $returnValue);
	
	                if ($returnValue != 0) {
	                    $w->result('', '', "There is a problem with the library, try to update it.", "Select Update library below", './images/warning.png', 'no', '');

			            $w->result('', serialize(array('' /*track_uri*/ ,'' /* album_uri */ ,'' /* artist_uri */ ,'' /* playlist_uri */ ,'' /* spotify_command */ ,'' /* query */ ,'' /* other_settings*/ , 'update_library' /* other_action */ ,'' /* alfred_playlist_uri */ ,''  /* artist_name */)), "Update library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', '');

	
	                    echo $w->toxml();
	                    return;
	                }
	                
			        foreach ($playlists as $playlist):
			            $playlist = explode("	", $playlist);
						
						$user_name = $playlist[4];
			        endforeach;
                
                    list($playlistName,$wrong_user,$real_user) = validateAlfredPlaylist($alfred_playlist_uri,$user_name);
                    if ($playlistName == "Alfred Playlist" &&
                    	$wrong_user == false) {
                        // internally, the user is replaced by @
                        $words = explode(':', $alfred_playlist_uri);


                        $w->result('', "||||||" . "ALFRED_PLAYLIST→" . $words[0] . ":" . $words[1] . ":@:" . $words[3] . ":" . $words[4] . "|||", "Alfred Playlist URI will be set to <" . $alfred_playlist_uri . ">", "Type enter to validate", './images/settings.png', 'yes', '');
                    } else {
                    	if($playlistName == "")
                    	{
                        	$w->result('', '', 'The playlist is not valid', 'if you have just created it, allow some time to the playlist to be synchronized to spotify servers' , './images/warning.png', 'no', '');
                        }
                        else if($playlistName != "Alfred Playlist")
                    	{
                        	$w->result('', '', 'The playlist entered <' . $playlistName . '>is not valid', 'shall be <Alfred Playlist>', './images/warning.png', 'no', '');
                        }
                        else if($wrong_user)
                        {
	                        $w->result('', '', 'The playlist entered does not belong to you', 'it shall be created by ' . $user_name . ' but it has been created' . $real_user, './images/warning.png', 'no', '');
                        }
                    }
                    
                } else {
                    $w->result('', '', "The playlist URI format entered is not valid", "format is spotify:user:myuser:playlist:20SZYrktr658JNa429t1vV", './images/warning.png', 'no', '');

                }
            }
        }
        // end of Settings
    }
}

echo $w->toxml();

?>