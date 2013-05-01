<?php
// Almost all this code is from Spoticious workflow (http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred/)
// Thanks citelao https://github.com/citelao/Spotify-for-Alfred
require_once('workflows.php');


function checkIfResultAlreadyThere($results,$title) {
	foreach($results as $result) {
		if($result[title]) {
			if($result[title] == $title) {
				return true;
			}
		}	
	}
	return false;
}

function getTrackArtwork($spotifyURL,$fetchIfNotPresent) {
	$hrefs = explode(':', $spotifyURL);
	$w = new Workflows();

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
				
	$currentArtwork = $w->data() . "/artwork/$hrefs[2].png";
	
	
	if (!file_exists($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getTrackArtworkURL($hrefs[1], $hrefs[2]);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				$fp = fopen ($currentArtwork, 'w+');
				$options = array(
				CURLOPT_FILE =>	$fp
				);
				
				$w->request( "$artwork", $options );
			}
		}
		else
		{
			return "images/default_album.png";
		}
	}
	else
	{
		if( filesize($currentArtwork) == 0 )
		{
			return "images/default_album.png";
		}		
	}
	
	if(is_numeric($artwork) && $artwork == 0)
	{
		return "images/default_album.png";
	}
	else
	{
		return $currentArtwork;
	}
}

function getArtistArtwork($artist,$fetchIfNotPresent) {
	$parsedArtist = urlencode($artist);
	$w = new Workflows();

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
		
	$currentArtwork = $w->data() . "/artwork/$parsedArtist.png";
	
	if (!file_exists($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getArtistArtworkURL($artist);
			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				$fp = fopen ($currentArtwork, 'w+');
				$options = array(
				CURLOPT_FILE =>	$fp	
				);		
				$w->request( "$artwork", $options );
			}
		}
		else
		{
			return "images/default_artist.png";
		}
	}
	else
	{
		if( filesize($currentArtwork) == 0 )
		{
			return "images/default_artist.png";
		}
	}
	
	if(is_numeric($artwork) && $artwork == 0)
	{
		return "images/default_artist.png";
	}
	else
	{
		return $currentArtwork;
	}
}

function getTrackArtworkURL($type, $id)
{
	$w = new Workflows();
	$html = $w->request( "http://open.spotify.com/$type/$id" );
	
	if (!empty($html)) {
	 	preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
	 	return (isset($m[1][0])) ? $m[1][0] : 0;
	}
	
	return 0;
}

function getArtistArtworkURL($artist) {
	$parsedArtist = urlencode($artist);
	$w = new Workflows();
	$html = $w->request( "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json");
	$json = json_decode($html, true);
	
	return $json[artist][image][1]['#text'];
}

function createPlaylists()
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
				$playlist_array[$completeUri] = str_replace("&apos;","'",str_replace("&amp;","&",$name));
				$w->write( $array_playlist_tracks, 'playlist_' . $playlist_name . '.json' );
			}
		};
		
		$w->write( $playlist_array, 'playlists.json' );
		
		unlink($w->data() . "/playlists-tmp.json");	
	}
}

function updateLibrary()
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


function downloadAllArtworks()
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

function refreshAlfredPlaylist()
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


	$w->write( $array_playlist_tracks, 'playlist_' . $playlist_name . '.json' );

}

function getPlaylistName($uri)
{	
	$name = "";
	
	$get_context = stream_context_create(array('http'=>array('timeout'=>5)));
	@$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);

	if(!empty($get))
	{
		preg_match_all("'<title>(.*?)</title>'si", $get, $name);

		if($name[1])
		{
			$name = strstr($name[1][0], ' by', true);
		}
	}

	return $name;
}
?>