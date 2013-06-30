<?php
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

function getTrackArtwork($w,$is_artworks_active,$spotifyURL,$fetchIfNotPresent) {

	if($is_artworks_active == false)
		return "images/default_track.png";
		
	$hrefs = explode(':', $spotifyURL);

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
				
	$currentArtwork = $w->data() . "/artwork/$hrefs[2].png";
	 	
	if (!is_file($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getTrackArtworkURL($w,$hrefs[1], $hrefs[2]);

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
			return "images/default_track.png";
		}
	}
	else
	{
		if( filesize($currentArtwork) == 0 )
		{
			return "images/default_track.png";
		}		
	}
	
	if(is_numeric($artwork) && $artwork == 0)
	{
		return "images/default_track.png";
	}
	else
	{
		return $currentArtwork;
	}
}

function getArtistArtwork($w,$is_artworks_active,$artist,$fetchIfNotPresent) {
	
	if($is_artworks_active == false)
		return "images/default_artist.png";
		
	$parsedArtist = urlencode($artist);

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
		
	$currentArtwork = $w->data() . "/artwork/$parsedArtist.png";
	
	return $currentArtwork;
	
	if (!is_file($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getArtistArtworkURL($w,$artist);
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

function getTrackArtworkURL($w,$type, $id)
{
	$w = new Workflows();
	$html = $w->request( "http://open.spotify.com/$type/$id" );
	
	if (!empty($html)) {
	 	preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
	 	return (isset($m[1][0])) ? $m[1][0] : 0;
	}
	
	return 0;
}

function getArtistArtworkURL($w,$artist) {
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
			
			if($results[4])
			{
				$playlist_name = $results[4];
				
			}elseif ($results[3] == "starred")
			{
				$playlist_name = "starred";
			}
			else
			{
				continue;
			}

			$get_context = stream_context_create(array('http'=>array('timeout'=>15)));
			@$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);
		
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table \"playlist_' . $playlist_name . '\" (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text)"';
			exec($sql);
			
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
						$uri = 'spotify:track:' . $uri;
						
						$artist = $artists[1][$n];
						$title = ltrim(substr($titles[1][$n], strpos($titles[1][$n], ' ')));						

						$getTrack = "select * from tracks where uri='".$uri."'";				
						$dbfile = $w->data() . "/library.db";
						$tracks = array();
						exec("sqlite3 -separator '	' \"$dbfile\" \"$getTrack\"", $tracks);
						
						foreach($tracks as $track):
							$track = explode("	",$track);

							$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into \"playlist_' . $playlist_name . '\" values ('. $track[0] .','.$track[1] .',\"'.$track[2] .'\",\"'.$track[3] .'\",\"'.$track[4] .'\",\"'.$track[5] .'\",\"'.$track[6] .'\",\"'.$track[7] .'\",'.$track[8] .')"';

							exec($sql);
						endforeach;
			
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
			}
		};
		
		$w->write( $playlist_array, 'playlists.json' );
		
		unlink($w->data() . "/playlists-tmp.json");	
	}
}

function updateLibrary()
{
	$w = new Workflows();
	
	putenv('LANG=fr_FR.UTF-8');
	
	ini_set('memory_limit', '512M' );
	
	//try to decode it 
	$json = json_decode(exec('pbpaste'),true);
	if (json_last_error() === JSON_ERROR_NONE) 
	{
		touch($w->data() . "/library.db");
		
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table tracks (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text)"';
		exec($sql);
		$count = 0;
		foreach ($json as $item) 
		{				
			if( $item['data']['starred'] == true )
			{
				$starred = 1;
			}
			else
			{
				$starred = 0;				
			}
			
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values ('. $starred .','.$item['data']['popularity'].',\"'.$item['data']['uri'].'\",\"'.$item['data']['album']['uri'].'\",\"'.$item['data']['album']['artist']['uri'].'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['name']))).'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['album']['name']))).'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['album']['artist']['name']))).'\"'.','.$item['data']['album']['year'].')"';
			exec($sql);
			$count++;	
		}
		
		echo "Library has been created (" . $count . " tracks)";
	} 
	else 
	{ 
	    //it's not JSON. Log error
	    echo "ERROR: JSON data is not valid!";
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
		
	if (file_exists($w->data() . "/library.db"))
	{
	
		if($all_playlists == false)
		{
			$getTracks = "select * from tracks where starred=1";
		}
		else
		{
			$getTracks = "select * from tracks";
		}
		
		
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\"", $tracks);
	
		foreach($tracks as $track):
			if($currentResultNumber > $max_results)
				break;
			$track = explode("	",$track);

			getTrackArtwork(true,$track[2],true);
			getArtistArtwork(true,$track[7],true);
			getTrackArtwork(true,$track[3],true);

		endforeach;
	}
	
	//		
	// playlists
	//
	
	// retrieve playlist uri from playlist name
	if(file_exists($w->data() . "/playlists.json"))
	{
		$json = file_get_contents($w->data() . "/playlists.json");
		$json = json_decode($json,true);
		
		foreach ($json as $key => $val) 
		{
			$results = explode(':', $key);

			if($results[4])
			{
				$playlist_name = $results[4];
				
			}elseif ($results[3] == "starred")
			{
				$playlist_name = "starred";
			}
			else
			{
				continue;
			}
			
			$getTracks = "select * from \"playlist_" . $playlist_name . "\"";
			
			$dbfile = $w->data() . "/library.db";
			exec("sqlite3 -separator '	' \"$dbfile\" \"$getTracks\"", $tracks);
			
			foreach($tracks as $track):
				$track = explode("	",$track);
				
				getTrackArtwork(true,$track[2],true);
			endforeach;
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