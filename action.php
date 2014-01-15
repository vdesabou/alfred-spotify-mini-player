<?php

// Turn off all error reporting
error_reporting(0);

require_once('workflows.php');
require('functions.php');

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\MiniPlayer;

require_once('./vendor/autoload.php');


$w = new Workflows();

$query = $argv[1];
$type = $argv[2];
$alfredplaylist = $argv[3];


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

if ($other_action == "update_playlist" && $playlist_uri != "") {
    touch($w->data() . "/update_library_in_progress");
    $w->write('InitPlaylist⇾' . 0 . '⇾' . 0 . '⇾' . time(), 'update_library_in_progress');

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


if ($type == "TRACK") {
    if ($track_uri != "") {
        if ($alfredplaylist != "") {
            exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$track_uri:$alfred_playlist_uri\"'");
            exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
            return;
        } else if ($playlist_uri != "") {
            exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playtrackwithplaylistcontext:$track_uri:$playlist_uri\"'");
            exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");
            return;
        }  
        else {
            if ($other_action == "")
                exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
        }
    }
} else if ($type == "ALBUM") {
    exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$album_uri:" . uniqid() . "\"'");
    exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
} else if ($type == "ONLINE") {
    exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online⇾$artist_uri@$artist_name\"'");
} else if ($type == "ALBUM_OR_PLAYLIST") {
    if ($alfredplaylist != "") {
        if ($album_uri != "") {
            exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$album_uri:$alfred_playlist_uri\"'");
            exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
            return;
        } else if ($playlist_uri != "") {
            exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addplaylisttoalfredplaylist:$playlist_uri:$alfred_playlist_uri\"'");
            exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
            return;
        }
    }
} else if ($type == "ARTIST") {
    exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$artist_uri:" . uniqid() . "\"'");
    exec("osascript -e 'tell application \"Spotify\" to open location \"$artist_uri\"'");
}

if ($playlist_uri != "") {
    exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:startplaylist:$playlist_uri:" . uniqid() . "\"'");
    exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");
} else if ($spotify_command != "") {

	$spotify_command = str_replace("\\", "", $spotify_command);
    exec("osascript -e 'tell application \"Spotify\" to $spotify_command'");
} else if ($other_settings != "") {
    $setting = explode('⇾', $other_settings);
    if ($setting[0] == "MAX_RESULTS") {
        $setSettings = "update settings set max_results=" . $setting[1];
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotification("Max results set to $setting[1]");
    } else if ($setting[0] == "ALFRED_PLAYLIST") {
        $setSettings = 'update settings set alfred_playlist_uri=\"' . $setting[1] . '\"' . ',alfred_playlist_name=\"' . $setting[2] . '\"';
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotification("Alfred Playlist set to $setting[2]");
    } else if ($setting[0] == "CLEAR_ALFRED_PLAYLIST") {
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:clearplaylist:$setting[1]:" . uniqid() . "\"'");
	    exec("osascript -e 'tell application \"Spotify\" to open location \"$setting[1]\"'");
	    displayNotification("Alfred Playlist $setting[2] was cleared");
    } else if ($setting[0] == "GET_LYRICS") {
		getLyrics($w,$setting[1],$setting[2]);
    }
} else if ($original_query != "") {
    exec("osascript -e 'tell application \"Alfred 2\" to search \"spot $original_query\"'");
} else if ($other_action != "") {
    if ($other_action == "disable_all_playlist") {
        $setSettings = "update settings set all_playlists=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Search scope set to starred playlist");
    } else if ($other_action == "enable_all_playlist") {
        $setSettings = "update settings set all_playlists=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
        displayNotification("Search scope set to all playlists");
    } else if ($other_action == "enable_spotifiuous") {
        $setSettings = "update settings set is_spotifious_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Spotifious is now enabled");
    } else if ($other_action == "disable_spotifiuous") {
        $setSettings = "update settings set is_spotifious_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Spotifious is now disabled");
    } else if ($other_action == "set_theme_to_black") {
        $setSettings = "update settings set theme='black'";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Theme set to black");
    } else if ($other_action == "set_theme_to_green") {
        $setSettings = "update settings set theme='green'";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Theme set to green");
    }
    else if ($other_action == "enable_displaymorefrom") {
        $setSettings = "update settings set is_displaymorefrom_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Now Playing is now enabled");
    } else if ($other_action == "disable_displaymorefrom") {
        $setSettings = "update settings set is_displaymorefrom_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Now Playing is now disabled");
    } else if ($other_action == "enable_alfred_playlist") {
        $setSettings = "update settings set is_alfred_playlist_active=1";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Alfred Playlist is now enabled");
    } else if ($other_action == "disable_alfred_playlist") {
        $setSettings = "update settings set is_alfred_playlist_active=0";
        $dbfile = $w->data() . "/settings.db";
        exec("sqlite3 \"$dbfile\" \"$setSettings\"");
	    displayNotification("Alfred Playlist is now disabled");
    } else if ($other_action == "open_spotify_export_app") {
        exec("osascript -e 'tell application \"Spotify\" to activate'");
        exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer\"'");
    } else if ($other_action == "check_for_update") {
		$check_results = checkForUpdate($w,0);
		if($check_results != null && is_array($check_results)) {
			displayNotification('New version ' . $check_results[0] . ' is available in Downloads directory ');
		}
		else {
			displayNotification('No update available');
		}
		
    } else if ($other_action == "star") {
        exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:star:" . uniqid() . "\"'");
        
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
        exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:user:$username:starred\"'");
        displayNotification("⭐️ Track has been starred");
    } else if ($other_action == "random") {
        exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:random:" . uniqid() . "\"'");
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
            displayNotification("No biography found");
            return;
        }
    	
        foreach ($biographs as $biography):
            $biography = explode("	", $biography);

            if($biography[0] != "")
            {
            	$output=strip_tags($biography[0]);
            	echo "🎓 $artist_name\n---------------------------\n$output";
	            return;
            }
            else
            {
	            displayNotification("No biography found");
	            return;
            }
        endforeach;
	    
    } 
	else if ($other_action == "morefromthisartist") {
        exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online⇾" . $artist_uri . "@" . escapeQuery($artist_name) . "\"'");
    }
     else if ($other_action == "update_library") {
        touch($w->data() . "/update_library_in_progress");
        $w->write('InitLibrary⇾' . 0 . '⇾' . 0 . '⇾' . time(), 'update_library_in_progress');

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
        touch($w->data() . "/update_library_in_progress");
        $w->write('InitPlaylistList⇾' . 0 . '⇾' . 0 . '⇾' . time(), 'update_library_in_progress');
		
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

?>
