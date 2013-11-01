<?php

// Turn off all error reporting
error_reporting(0);

require_once('workflows.php');
include_once('functions.php');

$w = new Workflows();

$query = $argv[1];
$type = $argv[2];
$alfredplaylist = $argv[3];

// query is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|other_settings|other_action|alfred_playlist_uri|artist_name

$results = explode('|', $query);

if(count($results)!=10)
{
	echo "ERROR: bad csv format in action.php, please report to workflow author";
	return;
}

$track_uri=$results[0];
$album_uri=$results[1];
$artist_uri=$results[2];
$playlist_uri=$results[3];
$spotify_command=$results[4];
$original_query=$results[5];
$other_settings=$results[6];
$other_action=$results[7];
$alfred_playlist_uri=$results[8];
$artist_name=$results[9];


if($type == "TRACK")
{
	if($track_uri != "")
	{
		if($alfredplaylist != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$track_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			return;
		}
		else
		{
			if($other_action == "")
				exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
		}
	}
}
else if ($type == "ALBUM")
{
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$album_uri\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
}
else if ($type == "ONLINE")
{
	exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online→$artist_uri@$artist_name\"'");
}
else if ($type == "ALBUM_OR_PLAYLIST")
{
	if($alfredplaylist != "")
	{
		if($album_uri != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$album_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			return;
		}
		else if($playlist_uri != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addplaylisttoalfredplaylist:$playlist_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			return;			
		}
	}
}
else if ($type == "ARTIST")
{
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$artist_uri\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$artist_uri\"'");
}

if($playlist_uri != "")
{
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:startplaylist:$playlist_uri\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$playlist_uri\"'");
}
else if($spotify_command != "")
{
	exec("osascript -e 'tell application \"Spotify\" to $spotify_command'");
}
else if($other_settings != "")
{
	$setting = explode('→', $other_settings);
	if($setting[0] == "MAX_RESULTS")
	{
		$setSettings = "update settings set max_results=".$setting[1];
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "Max results has been set to $setting[1]";
	}
	else if($setting[0] == "ALFRED_PLAYLIST")
	{
		$setSettings = 'update settings set alfred_playlist_uri=\"'.$setting[1].'\"';
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "Alfred Playlist URI has been set to $setting[1]";
	}
	else if($setting[0] == "COUNTRY")
	{
		$setSettings = 'update settings set country_code=\"'.$setting[1].'\"';
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "Country Code has been set to $setting[1]";
	}
}
else if($original_query != "")
{
	exec("osascript -e 'tell application \"Alfred 2\" to search \"spot $original_query\"'");
}
else if($other_action != "")
{
	if ($other_action == "disable_all_playlist")
	{
		$setSettings = "update settings set all_playlists=0";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");	
		echo "Search scope set to starred playlist";
	}
	else if ($other_action == "enable_all_playlist")
	{
		$setSettings = "update settings set all_playlists=1";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");	
		echo "Search scope set to all playlists";
	}
	else if ($other_action == "enable_spotifiuous")
	{
		$setSettings = "update settings set is_spotifious_active=1";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");	
		echo "Spotifious is now enabled";
	}
	else if ($other_action == "disable_spotifiuous")
	{
		$setSettings = "update settings set is_spotifious_active=0";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");	
		echo "Spotifious is now disabled";
	}
	else if ($other_action == "enable_displaymorefrom")
	{
		$setSettings = "update settings set is_displaymorefrom_active=1";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "\"More from this artist/album\" is now enabled";
	}
	else if ($other_action == "disable_displaymorefrom")
	{
		$setSettings = "update settings set is_displaymorefrom_active=0";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "\"More from this artist/album\" is now disabled";
	}
	else if ($other_action == "enable_alfred_playlist")
	{
		$setSettings = "update settings set is_alfred_playlist_active=1";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "Alfred Playlist is now enabled";
	}
	else if ($other_action == "disable_alfred_playlist")
	{
		$setSettings = "update settings set is_alfred_playlist_active=0";
		$dbfile = $w->data() . "/settings.db";
		exec("sqlite3 \"$dbfile\" \"$setSettings\"");
		echo "Alfred Playlist is now disabled";
	}
	else if ($other_action == "refresh_alfred_playlist")
	{
		refreshAlfredPlaylist();
		echo "Alfred Playlist has been refreshed";
	}
	else if ($other_action == "play_top_list")
	{
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:toplist:" . uniqid() . "\"'");
	}
	else if ($other_action == "delete_update_library_in_progress")
	{
		unlink($w->data() . "/update_library_in_progress");
	}
	else if ($other_action == "open_spotify_export_app")
	{
		exec("osascript -e 'tell application \"Spotify\" to activate'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer\"'");
	}
	else if ($other_action == "morefromthisartist")
	{
		$t = explode(':', $track_uri);
		$completeurl = getArtistURLFromTrack($w,$t[2]);
		$a = explode('/', $completeurl);
		if($a[4] != "")
		{
			exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online→spotify:artist:$a[4]@" . escapeQuery($artist_name) . "\"'");
		}
		else
		{
			echo "Error: Could no retrieve the artist";
		}
	}
	else if ($other_action == "update_library_json")
	{
		updateLibrary();
		if (file_exists($w->data() . "/library.db"))
		{			
			foreach(glob($w->data() . "/playlist*.json") as $file)
			{
				unlink($file);
     		}
     		
     		if (file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
     		{	
     			exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
     		}
		}
	}
}

?>
