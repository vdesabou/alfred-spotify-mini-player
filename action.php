<?php
require_once('workflows.php');
include_once('functions.php');

$w = new Workflows();

$query = $argv[1];
$type = $argv[2];
$alfredplaylist = $argv[3];

// query is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|other_settings|other_action|alfred_playlist_uri

$results = explode('|', $query);

$track_uri=$results[0];
$album_uri=$results[1];
$artist_uri=$results[2];
$playlist_uri=$results[3];
$spotify_command=$results[4];
$original_query=$results[5];
$other_settings=$results[6];
$other_action=$results[7];
$alfred_playlist_uri=$results[8];


if($type == "TRACK")
{
	if($track_uri != "")
	{
		if($alfredplaylist != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$track_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			sleep(15);
			refreshAlfredPlaylist();
			return;
		}
		else
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
		}
	}
}
else if ($type == "ALBUM")
{
	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$album_uri\"'");
	exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
}
else if ($type == "ALBUM_OR_PLAYLIST")
{
	if($alfredplaylist != "")
	{
		if($album_uri != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$album_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			sleep(15);
			refreshAlfredPlaylist();
			return;
		}
		else if($playlist_uri != "")
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addplaylisttoalfredplaylist:$playlist_uri:$alfred_playlist_uri\"'");
			exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
			sleep(15);
			refreshAlfredPlaylist();
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
	if(is_numeric($other_settings))
	{
		$w->set( 'max_results', $other_settings, 'settings.plist' );
		echo "Max results has been set to $other_settings";
	}
	else
	{
		$w->set( 'alfred_playlist_uri', $other_settings, 'settings.plist' );
		echo "Alfred Playlist URI has been set to $other_settings";
	}
}
else if($original_query != "")
{
	exec("osascript -e 'tell application \"Alfred 2\" to search \"spot $original_query\"'");
}
else if($other_action != "")
{
	if($other_action == "cache")
	{
		downloadAllArtworks();
	}
	else if($other_action == "clear")
	{
		clear();
	}
	else if ($other_action == "disable_all_playlist")
	{
		$w->set( 'all_playlists', 'false', 'settings.plist' );
		echo "Search scope set to starred playlist";
	}
	else if ($other_action == "enable_all_playlist")
	{
		$w->set( 'all_playlists', 'true', 'settings.plist' );
		echo "Search scope set to all playlists";
	}
	else if ($other_action == "enable_spotifiuous")
	{
		$w->set( 'is_spotifious_active', 'true', 'settings.plist' );
		echo "Spotifious is now enabled";
	}
	else if ($other_action == "disable_spotifiuous")
	{
		$w->set( 'is_spotifious_active', 'false', 'settings.plist' );
		echo "Spotifious is now disabled";
	}
	else if ($other_action == "enable_artworks")
	{
		$w->set( 'is_artworks_active', 'true', 'settings.plist' );
		echo "Artworks are now enabled";
	}
	else if ($other_action == "disable_artworks")
	{
		$w->set( 'is_artworks_active', 'false', 'settings.plist' );
		echo "Artworks are now disabled";
	}
	else if ($other_action == "enable_displaymorefrom")
	{
		$w->set( 'is_displaymorefrom_active', 'true', 'settings.plist' );
		echo "\"More from this artist/album\" is now enabled";
	}
	else if ($other_action == "disable_displaymorefrom")
	{
		$w->set( 'is_displaymorefrom_active', 'false', 'settings.plist' );
		echo "\"More from this artist/album\" is now disabled";
	}
	else if ($other_action == "enable_alfred_playlist")
	{
		$w->set( 'is_alfred_playlist_active', 'true', 'settings.plist' );
		echo "Alfred Playlist is now enabled";
	}
	else if ($other_action == "disable_alfred_playlist")
	{
		$w->set( 'is_alfred_playlist_active', 'false', 'settings.plist' );
		echo "Alfred Playlist is now disabled";
	}
	else if ($other_action == "refresh_alfred_playlist")
	{
		refreshAlfredPlaylist();
		echo "Alfred Playlist has been refreshed";
	}
	else if ($other_action == "clear_alfred_playlist")
	{
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:clearalfredplaylist:$alfred_playlist_uri:" . uniqid() . "\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
		sleep(15);	
		refreshAlfredPlaylist();	
	}
	else if ($other_action == "open_spotify_export_app")
	{
		exec("osascript -e 'tell application \"Spotify\" to activate'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer\"'");
	}
	else if ($other_action == "update_library_json")
	{
		updateLibrary();
		if (file_exists($w->data() . "/library.json"))
		{
			if (file_exists($w->data() . "/library_starred_playlist.json"))
			{			
				unlink($w->data() . "/library_starred_playlist.json");
			}
			
			foreach(glob($w->data() . "/playlist*.json") as $file)
			{
				unlink($file);
     		}
     		
     		createPlaylists();
     		
     		if (file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
     		{	
     			exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
     		}
			exec("mkdir -p ~/Spotify");
			exec("cp -R '".$w->path()."/spotify-app-miniplayer' ~/Spotify");
		}
	}
}

?>
