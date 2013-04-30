<?php
require_once('workflows.php');
include_once('functions.php');

$w = new Workflows();

$query = $argv[1];
$type = $argv[2];
$alfredplaylist = $argv[3];

// query is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|max_results|other_action|alfred_playlist_uri

$results = explode('|', $query);

$track_uri=$results[0];
$album_uri=$results[1];
$artist_uri=$results[2];
$playlist_uri=$results[3];
$spotify_command=$results[4];
$original_query=$results[5];
$max_results=$results[6];
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
		}
		else
		{
			exec("osascript -e 'tell application \"Spotify\" to open location \"$track_uri\"'");
		}
	}
}
else if ($type == "ALBUM")
{
	if($alfredplaylist != "")
	{
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$album_uri:$alfred_playlist_uri\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
		//sleep 15 seconds	
		refresh_alfred_playlist();	
	}
	else
	{
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:playartistoralbum:$album_uri\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$album_uri\"'");
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
else if($max_results != "")
{
	$w->set( 'max_results', $max_results, 'settings.plist' );
	echo "Max results has been set to $max_results";
}
else if($original_query != "")
{
	exec("osascript -e 'tell application \"Alfred 2\" to search \"spot $original_query\"'");
}
else if($other_action != "")
{
	if($other_action == "cache")
	{
		download_all_artworks();
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
	else if ($other_action == "clear_alfred_playlist")
	{
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:clearalfredplaylist:" . $album_uri . ":" . $alfred_playlist_uri . ":" . uniqid() . "\"'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"$alfred_playlist_uri\"'");
		//sleep 15 seconds	
		refresh_alfred_playlist();	
	}
	else if ($other_action == "open_spotify_export_app")
	{
		exec("osascript -e 'tell application \"Spotify\" to activate'");
		exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer\"'");
	}
	else if ($other_action == "update_library_json")
	{
		update_library();
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
     		
     		create_playlists();
     		
     		if (file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
     		{	
     			exec("rm -rf " . $w->home() . "/Spotify/spotify-app-miniplayer");
     		}	
		}
	}
}

function create_playlists()
{
	$w = new Workflows();
	
	ini_set('memory_limit', '512M' );
	
	//
	// Create the library_starred_playlist.json
	//
	if(!file_exists($w->data() . "/library_starred_playlist.json"))
	{
		$array_starred_items = array();
			
		if (file_exists($w->data() . "/library.json"))
		{
			$json = file_get_contents($w->data() . "/library.json");	
			$json = json_decode($json,true);
			
			foreach ($json as $item) 
			{	
				if ( $item['data']['starred'] == true )
				{
					array_push( $array_starred_items, $item );
				}
			}
			$w->write( $array_starred_items, 'library_starred_playlist.json' );
		}
	}

	//
	// Create the playlists.json
	//
	if(!file_exists($w->data() . "/playlists-tmp.json"))
	{
		exec('mdfind -name guistate', $results);
		
		$theUser = "";
		$theGuiStateFile = "";
		foreach ($results as $guistateFile)
		{
			if (strpos($guistateFile,"Spotify/Users") !== false)
			{
				$theGuiStateFile = $guistateFile;
				
				$a = explode('/', trim($theGuiStateFile, '/'));
				$b = explode('-', $a[6]);
				$theUser = $b[0];
				break;
			}
		}

		if($theGuiStateFile != "")
		{
			$json = file_get_contents($theGuiStateFile);	
			$json = json_decode($json,true);
			$res = array();

			if($theUser != "")
			{
				array_push($res,'spotify:user:' . $theUser . ':starred');
			}
			
			foreach ($json['views'] as $view) 
			{					
				array_push( $res, $view['uri'] );
			}
			$w->write( $res, 'playlists-tmp.json' );
		}
	}

	//
	// Create one json file per playlist
	//
	if(file_exists($w->data() . "/playlists-tmp.json"))
	{
		$json = file_get_contents($w->data() . "/playlists-tmp.json");
		$json = json_decode($json,true);
		
		$playlist_array = array();
		
		foreach ($json as $key) 
		{
			//
			// Loop on Playlists
			//	
			$no_match = false;		
			$uri = $key;
			$completeUri = $uri;
			
			$results = explode(':', $uri);
			$playlist_name = $results[4];
			$get_context = stream_context_create(array('http'=>array('timeout'=>5)));
			@$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);
		
			$array_playlist_tracks = array();
			
			if(empty($get))
			{
				$no_match = true;
			}
			else
			{
				preg_match_all("'<title>(.*?)</title>'si", $get, $name);
				preg_match_all("'<li class=\"artist \b[^>]*>(.*?)</li>'si", $get, $artists);
				preg_match_all("'<li class=\"track-title \b[^>]*>(.*?)</li>'si", $get, $titles);
				preg_match_all("'<li \b[^>]* data-track=\"(.*?)\" \b[^>]*>'si", $get, $uris);
		
				if($name[1] && $artists[1] && $titles[1] && $uris[1])
				{
					$name = strstr($name[1][0], ' by', true);
					
					$n = 0;
		
					foreach($uris[1] as $uri)
					{
						$artist = $artists[1][$n];
						$title = ltrim(substr($titles[1][$n], strpos($titles[1][$n], ' ')));
						$uri = 'spotify:track:' . $uri;
						
						$item = array ($artist,$title,$uri);
						array_push( $array_playlist_tracks, $item );
		
						$n++;
					}
				}
				else
				{
					$no_match = true;
				}
			}
		
			if($no_match == false)
			{
				$playlist_array[$completeUri] = $name;
				$w->write( $array_playlist_tracks, 'playlist_' . $playlist_name . '.json' );
			}
		};
		
		$w->write( $playlist_array, 'playlists.json' );
		
		unlink($w->data() . "/playlists-tmp.json");	
	}
}

function update_library()
{
	$w = new Workflows();
	
	$created = false;
	if (file_exists($w->data() . "/library.json"))
	{
		$created = true;
	}
	
	putenv('LANG=fr_FR.UTF-8');
	
	ini_set('memory_limit', '512M' );
	
	//try to decode it 
	$json = json_decode(exec('pbpaste'));
	if (json_last_error() === JSON_ERROR_NONE) 
	{ 
		$fp = fopen ($w->data() . "/library.json", 'w+');
		fwrite($fp, str_replace("&apos;","'",str_replace("&amp;","&",exec('pbpaste'))));
		fclose($fp);
		
		if($created == true)
		{
			echo "Library has been updated";
		}
		else
		{
			echo "Library has been created";
		}
	} 
	else 
	{ 
	    //it's not JSON. Log error
	    echo "ERROR: JSON data is not valid!";
	    if(file_exists($w->data() . "/library.json"))
	    {
	    	unlink($w->data() . "/library.json");
	    }
	}	
}
function clear()
{
	$w = new Workflows();
	
	$folder   = $w->data() . "/artwork";
	$bytes    = 0;
	$total    = 0;
	if ($handle = opendir($folder)) {
	
		while (false !== ($file = readdir($handle))) {
			if (stristr($file, '.png')) {
				unlink($folder . '/' . $file);
			}
		}
	
		closedir($handle);
	}
	echo "All Artworks were cleared";
}


function download_all_artworks()
{
	$w = new Workflows();
	
	$ret = $w->get( 'all_playlists', 'settings.plist' );
	if ($ret == 'true')
	{
		$all_playlists = true;
	}
	else
	{
		$all_playlists = false;
	}
	
	# increase memory_limit
	ini_set('memory_limit', '512M' );
		
	if (file_exists($w->data() . "/library.json"))
	{
	
		$json = file_get_contents($w->data() . "/library.json");	
		$json = json_decode($json,true);
		
		foreach ($json as $item) 
		{	
			if ( ($all_playlists == false && $item['data']['starred'] == true) ||
				$all_playlists == true )
			{
				getTrackArtwork($item['data']['uri'],true);
				getArtistArtwork($item['data']['album']['artist']['name'],true);
				getTrackArtwork($item['data']['album']['uri'],true);
			}
		};
	}
	
	//		
	// playlists
	//
	
	// retrieve playlist uri from playlist name
	if(file_exists($w->data() . "/playlists.json"))
	{
		$json = file_get_contents($w->data() . "/playlists.json");
		$json = json_decode($json,true);
		
		$playlist_file = "nonexistant";
		foreach ($json as $key => $val) 
		{
			$res = explode(':', $key);
			$playlist_name = $res[4];
			$playlist_file = 'playlist_' . $playlist_name . '.json';
			
			if(file_exists($w->data() . "/" . $playlist_file))
			{
				$json_playlist = file_get_contents($w->data() . "/" . $playlist_file);
				$json_playlist = json_decode($json_playlist,true);	
					
				foreach ($json_playlist as $item) 
				{	
					getTrackArtwork($item[2],true);
				}						
			}	
		}
	}			
	
	
	if($all_playlists == true)
	{
		echo "All Artworks for all playlists were cached";
	}
	else
	{
		echo "All Artworks for ★ playlist were cached";
	}

}
function refresh_alfred_playlist()
{
	$w = new Workflows();
	
	ini_set('memory_limit', '512M' );
	
	//
	// Get alfred_playlist_uri from config
	//
	$ret = $w->get( 'alfred_playlist_uri', 'settings.plist' );
	
	$no_match = false;		
	$uri = $ret;
	$completeUri = $uri;
	
	$results = explode(':', $uri);
	$playlist_name = $results[4];
	$get_context = stream_context_create(array('http'=>array('timeout'=>5)));
	@$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);

	$array_playlist_tracks = array();
	
	if(empty($get))
	{
		$no_match = true;
	}
	else
	{
		preg_match_all("'<title>(.*?)</title>'si", $get, $name);
		preg_match_all("'<li class=\"artist \b[^>]*>(.*?)</li>'si", $get, $artists);
		preg_match_all("'<li class=\"track-title \b[^>]*>(.*?)</li>'si", $get, $titles);
		preg_match_all("'<li \b[^>]* data-track=\"(.*?)\" \b[^>]*>'si", $get, $uris);

		if($name[1] && $artists[1] && $titles[1] && $uris[1])
		{
			$name = strstr($name[1][0], ' by', true);
			
			$n = 0;

			foreach($uris[1] as $uri)
			{
				$artist = $artists[1][$n];
				$title = ltrim(substr($titles[1][$n], strpos($titles[1][$n], ' ')));
				$uri = 'spotify:track:' . $uri;
				
				$item = array ($artist,$title,$uri);
				array_push( $array_playlist_tracks, $item );

				$n++;
			}
		}
		else
		{
			$no_match = true;
		}
	}

	if($no_match == false)
	{
		$playlist_array[$completeUri] = $name;
		$w->write( $array_playlist_tracks, 'playlist_' . $playlist_name . '.json' );
	}
}
?>
