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

function getTrackOrAlbumArtwork($w,$spotifyURL,$fetchIfNotPresent) {

	$hrefs = explode(':', $spotifyURL);
	
	$isAlbum = false;
	if($hrefs[1] == "album")
	{
		$isAlbum = true;
	}	

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
				
	$currentArtwork = $w->data() . "/artwork/" . hash('md5',$hrefs[2] . ".png") . "/" . "$hrefs[2].png";
	 	
	if (!is_file($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getTrackArtworkURL($w,$hrefs[1], $hrefs[2]);

			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if ( !file_exists( $w->data() . "/artwork/" . hash('md5',$hrefs[2] . ".png") ) ):
					exec("mkdir '".$w->data()."/artwork/".hash('md5',$hrefs[2] . ".png")."'");
				endif;
				$fp = fopen ($currentArtwork, 'w+');
				$options = array(
				CURLOPT_FILE =>	$fp
				);
				
				$w->request( "$artwork", $options );
			}
		}
		else
		{
			if($isAlbum)
			{
				return "images/default_album.png";
			}
			else
			{
				return "images/default_track.png";
			}
		}
	}
	else
	{
		if( filesize($currentArtwork) == 0 )
		{
			if($isAlbum)
			{
				return "images/default_album.png";
			}
			else
			{
				return "images/default_track.png";
			}
		}		
	}
	
	if(is_numeric($artwork) && $artwork == 0)
	{
		if($isAlbum)
		{
			return "images/default_album.png";
		}
		else
		{
			return "images/default_track.png";
		}
	}
	else
	{
		return $currentArtwork;
	}
}

function getArtistArtwork($w,$artist,$fetchIfNotPresent) {
	
	if($is_artworks_active == false)
		return "images/default_artist.png";
		
	$parsedArtist = urlencode($artist);

	if ( !file_exists( $w->data() . "/artwork" ) ):
		exec("mkdir '".$w->data()."/artwork'");
	endif;
			
	$currentArtwork = $w->data() . "/artwork/" . hash('md5',$parsedArtist . ".png") . "/" . "$parsedArtist.png";
		
	if (!is_file($currentArtwork)) 
	{
		if($fetchIfNotPresent == true)
		{
			$artwork = getArtistArtworkURL($w,$artist);
			// if return 0, it is a 404 error, no need to fetch
			if (!empty($artwork) || (is_numeric($artwork) && $artwork != 0)) {
				if ( !file_exists( $w->data() . "/artwork/" . hash('md5',$parsedArtist . ".png") ) ):
					exec("mkdir '".$w->data()."/artwork/".hash('md5',$parsedArtist . ".png")."'");
				endif;
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
	$html = $w->request( "http://open.spotify.com/$type/$id" );
	
	if (!empty($html)) {
	 	preg_match_all('/.*?og:image.*?content="(.*?)">.*?/is', $html, $m);
	 	return (isset($m[1][0])) ? $m[1][0] : 0;
	}
	
	return 0;
}

function getArtistArtworkURL($w,$artist) {
	$parsedArtist = urlencode($artist);
	$html = $w->request( "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&api_key=49d58890a60114e8fdfc63cbcf75d6c5&artist=$parsedArtist&format=json");
	$json = json_decode($html, true);
	
	return $json[artist][image][1]['#text'];
}

function updateLibrary()
{
	$w = new Workflows();
	
	//
	// move files in hash directories if existing
	//
	$folder   = $w->data() . "/artwork";
	$bytes    = 0;
	$total    = 0;
	if ($handle = opendir($folder)) {
	
		while (false !== ($file = readdir($handle))) {
			if (stristr($file, '.png')) {
				exec("mkdir '".$w->data()."/artwork/".hash('md5',$file)."'");
				rename($folder . '/' . $file,$folder . '/' . hash('md5',$file) . '/' . $file);
			}
		}
	
		closedir($handle);
	}
	
	putenv('LANG=fr_FR.UTF-8');
	
	ini_set('memory_limit', '512M' );
	
	//try to decode it 
	$json = json_decode(exec('pbpaste'),true);
	if (json_last_error() === JSON_ERROR_NONE) 
	{
		touch($w->data() . "/library.db");
		
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table tracks (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text, track_artwork_path text, artist_artwork_path text, album_artwork_path text)"';
		exec($sql);
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table counters (all_tracks int, starred_tracks int, all_artists int, starred_artists int, all_albums int, starred_albums int, playlists int)"';
		exec($sql);

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
			
			//
			// Download artworks
			$track_artwork_path = getTrackOrAlbumArtwork($w,true,$item['data']['uri'],true);
			$artist_artwork_path = getArtistArtwork($w,true,$item['data']['album']['artist']['name'],true);
			$album_artwork_path = getTrackOrAlbumArtwork($w,true,$item['data']['album']['uri'],true);
			
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into tracks values ('. $starred .','.$item['data']['popularity'].',\"'.$item['data']['uri'].'\",\"'.$item['data']['album']['uri'].'\",\"'.$item['data']['album']['artist']['uri'].'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['name']))).'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['album']['name']))).'\",\"'.str_replace("`","\`",str_replace("&apos;","'",str_replace("&amp;","&",$item['data']['album']['artist']['name']))).'\"'.','.$item['data']['album']['year'].',\"'.$track_artwork_path.'\"'.',\"'.$artist_artwork_path.'\"'.',\"'.$album_artwork_path.'\"'.')"';
			exec($sql);
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
	
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into counters values ('. $all_tracks[0] .','. $starred_tracks[0] .','. $all_artists[0] .','. $starred_artists[0] .','. $all_albums[0] .','. $starred_albums[0] .','. '\"\"' .')"';
		exec($sql);
				
		echo "Library has been created (" . $all_tracks[0] . " tracks)";								
	} 
	else 
	{ 
	    //it's not JSON. Log error
	    echo "ERROR: JSON data is not valid!";
	}	
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
		
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table playlists (uri text, name text, nb_tracks int, author text)"';
		exec($sql);
		
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
		
			$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . ' "create table \"playlist_' . $playlist_name . '\" (starred boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_year text, track_artwork_path text, artist_artwork_path text, album_artwork_path text)"';
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

						//
						// Download artworks
						$track_artwork_path = getTrackOrAlbumArtwork($w,true,$uri,true);
						$artist_artwork_path = getArtistArtwork($w,true,$artist,true);
						
						$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into \"playlist_' . $playlist_name . '\" values (0,0,\"'. $uri .'\",\"\",\"\",\"'.$title .'\",\"\",\"'. $artist .'\",\"\"'.',\"'.$track_artwork_path.'\"'.',\"'.$artist_artwork_path.'\"'.',\"'.$album_artwork_path.'\"'.')"';
						exec($sql);
			
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
				$r = explode(':', $completeUri);
				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into playlists values (\"'. $completeUri .'\",\"'. str_replace("&apos;","'",str_replace("&amp;","&",$name)) .'\",'. $n .',\"'. $r[2] .'\")"';
				exec($sql);
			}
		};

		$getCount = "select count(*) from playlists";
		$dbfile = $w->data() . "/library.db";
		exec("sqlite3 \"$dbfile\" \"$getCount\"", $playlists);
	
		// update counters for playlists
		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"update counters set playlists='. $playlists[0] .'"';
		exec($sql);
				
		unlink($w->data() . "/playlists-tmp.json");	
	}
}

function refreshAlfredPlaylist()
{
	$w = new Workflows();
	
	//
	// Get alfred_playlist_uri from config
	//
		
	$getSettings = "select alfred_playlist_uri from settings";
	$dbfile = $w->data() . "/settings.db";
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\"", $settings);
	
	foreach($settings as $setting):
		$setting = explode("	",$setting);
		$alfred_playlist_uri = $setting[0];
	endforeach;


	$no_match = false;
	$n = 0;	
	$uri = $alfred_playlist_uri;
	$completeUri = $uri;
	
	$results = explode(':', $uri);
	
	if($results[4])
	{
		$playlist_name = $results[4];
		
	}elseif ($results[3] == "starred")
	{
		$playlist_name = "starred";
	}
			
	$get_context = stream_context_create(array('http'=>array('timeout'=>10)));
	@$get = file_get_contents('https://embed.spotify.com/?uri=' . $uri, false, $get_context);
	
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

		$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"delete from \"playlist_' . $playlist_name . '\""';
		exec($sql);

		if($name[1] && $artists[1] && $titles[1] && $uris[1])
		{
			$name = strstr($name[1][0], ' by', true);
			
			$n = 0;

			foreach($uris[1] as $uri)
			{
				$uri = 'spotify:track:' . $uri;
				$artist = $artists[1][$n];
				$title = ltrim(substr($titles[1][$n], strpos($titles[1][$n], ' ')));
				
				$sql = 'sqlite3 "' . $w->data() . '/library.db" ' . '"insert into \"playlist_' . $playlist_name . '\" values (0,0,\"'. $uri .'\",\"\",\"\",\"'.$title .'\",\"\",\"'. $artist .'\",\"\")"';
				exec($sql);

				$n++;
			}
		}
		else
		{
			$no_match = true;
		}
	}
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