<?php
	require_once('workflows.php');
	
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

?>