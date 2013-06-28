<?php
include_once('functions.php');
require_once('workflows.php');

$query = $argv[1];
# thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
$query = iconv("UTF-8-MAC", "UTF-8", $query);

$w = new Workflows();

# increase memory_limit
if (file_exists($w->data() . "/library.json"))
{
	ini_set('memory_limit', '256M' );
}

//
// Install spotify-app-miniplayer app if needed
// very first time use
//
if (!file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
{	
	mkdir($w->home() . "/Spotify");
	symlink($w->path() . "/spotify-app-miniplayer", $w->home() . "/Spotify/spotify-app-miniplayer");
}

//
// Get all_playlists from config
//
$ret = $w->get( 'all_playlists', 'settings.plist' );

if ( $ret == false)
{
	// all_playlists not set
	// set it to default
	$w->set( 'all_playlists', 'false', 'settings.plist' );
	$ret = 'false';
}

if ($ret == 'true')
{
	$all_playlists = true;
}
else
{
	$all_playlists = false;
}

//
// Get is_spotifious_active from config
//
$ret = $w->get( 'is_spotifious_active', 'settings.plist' );

if ( $ret == false)
{
	// is_spotifious_active not set
	// set it to default
	$w->set( 'is_spotifious_active', 'false', 'settings.plist' );
	$ret = 'false';
}

if ($ret == 'true')
{
	$is_spotifious_active = true;
}
else
{
	$is_spotifious_active = false;
}

//
// Get is_alfred_playlist_active from config
//
$ret = $w->get( 'is_alfred_playlist_active', 'settings.plist' );

if ( $ret == false)
{
	// $is_alfred_playlist_active not set
	// set it to default
	$w->set( 'is_alfred_playlist_active', 'true', 'settings.plist' );
	$ret = 'true';
}

if ($ret == 'true')
{
	$is_alfred_playlist_active = true;
}
else
{
	$is_alfred_playlist_active = false;
}



//
// Get max_results from config
//
$ret = $w->get( 'max_results', 'settings.plist' );

if ( $ret == false)
{
	// all_playlists not set
	// set it to default
	$w->set( 'max_results', '10', 'settings.plist' );
	$ret = '10';
}

$max_results = $ret;

//
// Get alfred_playlist_uri from config
//
$ret = $w->get( 'alfred_playlist_uri', 'settings.plist' );

if ( $ret == false)
{
	// alfred_playlist_uri not set
	// set it to empty
	$w->set( 'alfred_playlist_uri', '', 'settings.plist' );
	$ret = "";
}

$alfred_playlist_uri = $ret;


# thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding("UTF-8");
if(mb_strlen($query) < 3 || 
	((substr_count( $query, '→' ) == 1) && (strpos("Settings→",$query) !== false))
)
{					
	if ( substr_count( $query, '→' ) == 0 )
	{
		// check for correct configuration
		if (file_exists($w->data() . "/library.json") && file_exists($w->home() . "/Spotify/spotify-app-miniplayer/manifest.json"))
		{
			if($all_playlists == true)
			{
				$w->result( '', '', "Search for music in all your playlists", "Begin typing to search (at least 3 characters)", './images/allplaylists.png', 'no', '' );
			}
			else
			{
				$w->result( '', '', "Search for music in your ★ playlist", "Begin typing to search (at least 3 characters)", './images/star.png', 'no', '' );
			}
			
			// get info on current song
			$command_output = exec("./track_info.sh");
	
			if(substr_count( $command_output, '→' ) > 0)
			{
				$results = explode('→', $command_output);
				$currentArtwork = getTrackArtwork($results[4],false);
				$currentArtistArtwork = getArtistArtwork($results[1],false);
				$w->result( '', '||||playpause||||', "$results[0]", "$results[2] by $results[1]", ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', '' );
				$w->result( '', '', "$results[1]", "More from this artist..", $currentArtistArtwork, 'no', $results[1] );
				$w->result( '', '', "$results[2]", "More from this album..", $currentArtwork, 'no', $results[2] );
			}
			if ($is_alfred_playlist_active == true)
			{
				$w->result( '', '', "Alfred Playlist", "Control your Alfred Playlist", './images/alfred_playlist.png', 'no', 'Alfred Playlist→' );	
			}
			if (file_exists($w->data() . "/playlists.json"))
			{
				$w->result( '', '', "Playlists", "Browse by playlist", './images/playlists.png', 'no', 'Playlist→' );
			}
			$w->result( '', '', "Artists", "Browse by artist", './images/artists.png', 'no', 'Artist→' );
			$w->result( '', '', "Albums", "Browse by album", './images/albums.png', 'no', 'Album→' );			
		}
		else
		{
			if(!file_exists($w->data() . "/library.json"))
			{
				$w->result( '', '', "Workflow is not configured, library.json is missing", "Select Open Spotify Mini Player App below, and copy json data", './images/warning.png', 'no', '' );
			}
			elseif(!file_exists($w->home() . "/Spotify/spotify-app-miniplayer"))
			{
				$w->result( '', '', "Workflow is not configured, Spotify Mini Player App is missing", "Select Install library below, and make sure ~/Spotify/spotify-app-miniplayer directory exists", './images/warning.png', 'no', '' );				
			}
			$w->result( '', "|||||||" . "open_spotify_export_app|", "Open Spotify Mini Player App <spotify:app:miniplayer>", "Once clipboard contains json data, get back here and use Install library.", './images/app_miniplayer.png', 'yes', '' );
			$w->result( '', "|||||||" . "update_library_json|", "Install library", "Make sure the clipboard contains the json data from the Spotify App <spotify:app:miniplayer>", './images/update.png', 'yes', '' );
		}

		if ($is_spotifious_active == true)
		{
			$spotifious_state = 'enabled';
		}
		else
		{
			$spotifious_state = 'disabled';		
		}
		if ($is_alfred_playlist_active == true)
		{
			$alfred_playlist_state = 'enabled';
		}
		else
		{
			$alfred_playlist_state = 'disabled';		
		}
		if ($all_playlists == true)
		{
			$w->result( '', '', "Settings", "Current: Search Scope=<all>, Max Results=" . $max_results . ", Spotifious is " . $spotifious_state . ", Alfred Playlist is " . $alfred_playlist_state, './images/settings.png', 'no', 'Settings→' );
		}
		else
		{
			$w->result( '', '', "Settings", "Current: Search Scope=<only ★>, Max Results=" . $max_results  . ", Spotifious is " . $spotifious_state . ", Alfred Playlist is " . $alfred_playlist_state, './images/settings.png', 'no', 'Settings→' );
		}	
		
	}
	//
	// Settings
	//
	elseif ( substr_count( $query, '→' ) == 1 )
	{	
		if ($all_playlists == true)
		{
			// argument is csv form: track_uri|album_uri|artist_uri|playlist_uri|spotify_command|query|other_settings|other_action
			$w->result( '', "|||||||" . "disable_all_playlist|", "Change Search Scope", "Select to change to ★ playlist only", './images/search.png', 'yes', '' );
		}
		else
		{
			$w->result( '', "|||||||" . "enable_all_playlist|", "Change Search Scope", "Select to change to ALL playlists", './images/search.png', 'yes', '' );
		}
		$w->result( '', "|||||||" . "open_spotify_export_app|", "Open Spotify Mini Player App <spotify:app:miniplayer>", "Once clipboard contains json data, get back here and use Update library.", './images/app_miniplayer.png', 'yes', '' );
		$w->result( '', "|||||||" . "update_library_json|", "Update library", "Make sure the clipboard contains the json data from the Spotify Mini Player App <spotify:app:miniplayer>", './images/update.png', 'yes', '' );
		$w->result( '', '', "Configure Max Number of Results", "Number of results displayed", './images/numbers.png', 'no', 'Settings→MaxResults→' );
		$w->result( '', "|||||||" . "cache|", "Cache All Artworks", "This is recommended to do it before using the player", './images/cache.png', 'yes', '' );
		$w->result( '', "|||||||" . "clear|", "Clear Cached Artworks", "All cached artworks will be deleted", './images/uncheck.png', 'yes', '' );
		if ($is_spotifious_active == true)
		{
			$w->result( '', "|||||||" . "disable_spotifiuous|", "Disable Spotifious", "Do not display Spotifious in default results", './images/uncheck.png', 'yes', '' );
		}
		else
		{
			$w->result( '', "|||||||" . "enable_spotifiuous|", "Enable Spotifious", "Display Spotifious in default results", './images/check.png', 'yes', '' );
		}
		if ($is_alfred_playlist_active == true)
		{
			$w->result( '', "|||||||" . "disable_alfred_playlist|", "Disable Alfred Playlist", "Do not display Alfred Playlist", './images/uncheck.png', 'yes', '' );
		}
		else
		{
			$w->result( '', "|||||||" . "enable_alfred_playlist|", "Enable Alfred Playlist", "Display Alfred Playlist", './images/check.png', 'yes', '' );
		}		
	}
} 
else 
{
	////////////
	//
	// NO DELIMITER
	//
	////////////	
	if ( substr_count( $query, '→' ) == 0 )
	{	
		//
		// Search categories for fast access
		//		
		if (strpos(strtolower("playlist"),strtolower($query)) !== false)
		{	
			$w->result( '', '', "Playlists", "Browse by playlist", './images/playlists.png', 'no', 'Playlist→' );
		}
		else if (strpos(strtolower("album"),strtolower($query)) !== false)
		{
			$w->result( '', '', "Albums", "Browse by album", './images/albums.png', 'no', 'Album→' );	
		}
		else if (strpos(strtolower("artist"),strtolower($query)) !== false)
		{
			$w->result( '', '', "Artists", "Browse by artist", './images/artists.png', 'no', 'Artist→' );	
		}
		else if (strpos(strtolower("alfred"),strtolower($query)) !== false)
		{
			$w->result( '', '', "Alfred Playlist", "Control your Alfred Playlist", './images/alfred_playlist.png', 'no', 'Alfred Playlist→' );	
		}
		else if (strpos(strtolower("setting"),strtolower($query)) !== false)
		{
			$w->result( '', '', "Settings", "Go to settings", './images/settings.png', 'no', 'Settings→' );
		}		
		
			
		
		//
		// Search in Playlists
		//
		if (file_exists($w->data() . "/playlists.json"))
		{		
			$json = file_get_contents($w->data() . "/playlists.json");
			$json = json_decode($json,true);
			
			foreach ($json as $key => $val) 
			{				
				if (strpos(strtolower($val),strtolower($query)) !== false &&
					$val != "Alfred Playlist" )
				{	
					$w->result( "spotify_mini-spotify-playlist-$val", '', ucfirst($val), "Browse Playlist", './images/playlists.png', 'no', "Playlist→" . $val . "→" );
				}
			};
		}

		//
		// Search everything
		//
		
		if($all_playlists == false)
		{
			$json = file_get_contents($w->data() . "/library_starred_playlist.json");
		}
		else
		{
			$json = file_get_contents($w->data() . "/library.json");
		}
		$json = json_decode($json,true);
				
		$currentResultNumber = 1;
		foreach ($json as $item) 
		{	
			if($currentResultNumber > $max_results)
				break;			
			if (strpos(strtolower($item['data']['album']['artist']['name']),strtolower($query)) !== false ||
				strpos(strtolower($item['data']['album']['name']),strtolower($query)) !== false ||
				strpos(strtolower($item['data']['name']),strtolower($query)) !== false)
			{				
				// Figure out search rank
				$popularity = $item['data']['popularity'];
				$popularity/=100;
				
				// Convert popularity to stars
				$stars = floor($popularity * 5);
				$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
					
				$subtitle = $item['data']['album']['name'] . " - ⌥ → ▶ album, ⌘ →▶ artist";
				if($is_alfred_playlist_active ==true)
				{
					$subtitle = "$subtitle ,fn → add track to ♫, ⇧ → add album to ♫";
				}
				$subtitle = "$starString $subtitle";
				
				if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name']) == false)
				{					
					$w->result( "spotify_mini-spotify-track" . $item['data']['uri'], $item['data']['uri'] . "|" . $item['data']['album']['uri'] . "|" . $item['data']['album']['artist']['uri'] . "|||||"  . "|" . $alfred_playlist_uri, ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name'], $subtitle, getTrackArtwork($item['data']['uri'],true), 'yes', '' );
				}
				$currentResultNumber++;
			}
		};

		$w->result( '', "||||activate (open location \"spotify:search:" . $query . "\")||||", "Search for " . $query . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
		if($is_spotifious_active == true)
		{
			$w->result( '', "|||||" . "$query" . "|||", "Search for " . $query . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
		}
	}
	////////////
	//
	// FIRST DELIMITER: Artist→, Album→, Playlist→, Alfred Playlist→ or Settings→
	//
	////////////
	elseif ( substr_count( $query, '→' ) == 1 )
	{		
		$words = explode('→', $query);
		
		$kind=$words[0];

		if($kind == "Playlist")
		{
			//
			// Search playlists
			//
			$playlist=$words[1];
			
			if(mb_strlen($playlist) < 3)
			{
				//
				// Display all playlists
				//
				$json = file_get_contents($w->data() . "/playlists.json");
				$json = json_decode($json,true);
				
				foreach ($json as $key => $val) 
				{	
					if($key != $w->get( 'alfred_playlist_uri', 'settings.plist' ))
					{
						$r = explode(':', $key);
						$playlist_user = $r[2];
						$w->result( "spotify_mini-spotify-playlist-$val", '', ucfirst($val), "by " . $playlist_user, './images/playlists.png', 'no', "Playlist→" . $val . "→" );
					}
				};
			}
			else
			{
				$json = file_get_contents($w->data() . "/playlists.json");
				$json = json_decode($json,true);
				
				foreach ($json as $key => $val) 
				{
					$r = explode(':', $key);
					$playlist_user = $r[2];
								
					if (strpos(strtolower($val),strtolower($playlist)) !== false ||
						strpos(strtolower($playlist_user),strtolower($playlist)) !== false )
					{	
						$w->result( "spotify_mini-spotify-playlist-$val", '', ucfirst($val), "by " . $playlist_user, './images/playlists.png', 'no', "Playlist→" . $val . "→" );
					}
				};
			}
		} // search by Playlist end	
		elseif($kind == "Alfred Playlist")
		{
			//
			// Alfred Playlist
			//
			$playlist=$words[1];
						
			if($alfred_playlist_uri == "")
			{
				$w->result( "spotify_mini-spotify-alfredplaylist-set", '', "Set your Alfred playlist URI", "define the URI of your Alfred playlist",'./images/alfred_playlist.png', 'no', 'Alfred Playlist→Set Alfred Playlist URI→');				
			}
			else
			{
				$w->result( "spotify_mini-spotify-alfredplaylist-browse", '', "Browse your Alfred playlist", "browse your alfred playlist",'./images/alfred_playlist.png', 'no', 'Playlist→Alfred Playlist→');
			
				$w->result( "spotify_mini-spotify-alfredplaylist-set", '', "Update your Alfred playlist URI", "define the URI of your Alfred playlist",'./images/settings.png', 'no', 'Alfred Playlist→Set Alfred Playlist URI→');
				
				$w->result( "spotify_mini-spotify-alfredplaylist-refresh", "|||||||" . "refresh_alfred_playlist|" . $alfred_playlist_uri, "Refresh your Alfred playlist", "this will refresh your Alfred playlist",'./images/update.png', 'yes', '');
				
				$w->result( "spotify_mini-spotify-alfredplaylist-clear", "|||||||" . "clear_alfred_playlist|" . $alfred_playlist_uri, "Clear your Alfred playlist", "this will clear your Alfred playlist",'./images/uncheck.png', 'yes', '');
			
			}
		} //  Alfred Playlist end	
		elseif($kind == "Artist")
		{
			if($all_playlists == false)
			{
				$json = file_get_contents($w->data() . "/library_starred_playlist.json");
			}
			else
			{
				$json = file_get_contents($w->data() . "/library.json");
			}	
			$json = json_decode($json,true);
			
			//
			// Search artists
			//
			$artist=$words[1];
			
			if(mb_strlen($artist) < 3)
			{
				// display all artists
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
						
					if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name'])) == false)
					{													
						$w->result( "spotify_mini-spotify-artist-" . $item['data']['album']['artist']['name'], '', ucfirst($item['data']['album']['artist']['name']), "Get tracks from this artist", getArtistArtwork($item['data']['album']['artist']['name'],true), 'no', "Artist→" . $item['data']['album']['artist']['name'] . "→" );
						
						$currentResultNumber++;
					}
				};
			}
			else
			{
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
								
					if (strpos(strtolower($item['data']['album']['artist']['name']),strtolower($artist)) !== false)
					{	
						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name'])) == false)
						{									
							$w->result( "spotify_mini-spotify-artist-" . $item['data']['album']['artist']['name'], '', ucfirst($item['data']['album']['artist']['name']), "Get tracks from this artist", getArtistArtwork($item['data']['album']['artist']['name'],true), 'no', "Artist→" . $item['data']['album']['artist']['name'] . "→" );
							
							$currentResultNumber++;
						}
					}			
				};
				$w->result( '', "||||activate (open location \"spotify:search:" . $artist . "\")||||", "Search for " . $artist . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "|||||" . $artist . "|||", "Search for " . $artist . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}
		} // search by Album end
		elseif($kind == "Album")
		{
			if($all_playlists == false)
			{
				$json = file_get_contents($w->data() . "/library_starred_playlist.json");
			}
			else
			{
				$json = file_get_contents($w->data() . "/library.json");
			}	
			$json = json_decode($json,true);
		
			//
			// Search albums
			//
			$album=$words[1];
			
			if(mb_strlen($album) < 3)
			{
				// display all artists
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
								
					if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['name'])) == false)
					{						
						$w->result( "spotify_mini-spotify-album" . $item['data']['album']['name'], '', ucfirst($item['data']['album']['name']), "by " . $item['data']['album']['artist']['name'] . " (" . $item['data']['album']['year'] . ")", getTrackArtwork($item['data']['album']['uri'],true), 'no', "Album→" . $item['data']['album']['name'] . "→" );
						
						$currentResultNumber++;
					}
				};
			}
			else
			{
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
		
					if (strpos(strtolower($item['data']['album']['name']),strtolower($album)) !== false)
					{	
						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['name'])) == false)
						{								
							$w->result( "spotify_mini-spotify-album" . $item['data']['album']['name'], '', ucfirst($item['data']['album']['name']), "by " . $item['data']['album']['artist']['name'] . " (" . $item['data']['album']['year'] . ")", getTrackArtwork($item['data']['album']['uri'],true), 'no', "Album→" . $item['data']['album']['name'] . "→" );
							
							$currentResultNumber++;
						}
					}
				};
				$w->result( '', "||||activate (open location \"spotify:search:" . $album . "\")||||", "Search for " . $album . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "||||||" . "$album" . "||", "Search for " . $album . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}
		} // search by Album end
	}
	////////////
	//
	// SECOND DELIMITER: Artist→the_artist→tracks , Album→the_album→tracks, Playlist→the_playlist→tracks or Settings→MaxResults→max_numbers, Alfred Playlist→Set Alfred Playlist URI→alfred_playlist_uri
	//
	////////////
	elseif ( substr_count( $query, '→' ) == 2 )
	{		
		//
		// Get all songs for selected artist
		//
		
		$words = explode('→', $query);
		
		$kind=$words[0];
		if($kind == "Artist")
		{	
			if($all_playlists == false)
			{
				$json = file_get_contents($w->data() . "/library_starred_playlist.json");
			}
			else
			{
				$json = file_get_contents($w->data() . "/library.json");
			}	
			$json = json_decode($json,true);
			//		
			// display tracks for selected artists
			//
			$artist=$words[1];
			$track=$words[2];
			
			if(mb_strlen($track) < 3)
			{
				$currentResultNumber = 1;
				$artist_uri = "";
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;		
					if (strpos(strtolower($item['data']['album']['artist']['name']),strtolower($artist)) !== false)
					{				
						// Figure out search rank
						$popularity = $item['data']['popularity'];
						$popularity/=100;
						
						// Convert popularity to stars
						$stars = floor($popularity * 5);
						$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
							
						$subtitle = $item['data']['album']['name'] . " - ⌥ → ▶ album, ⌘ →▶ artist";
						if($is_alfred_playlist_active ==true)
						{
							$subtitle = "$subtitle ,fn → add track to ♫, ⇧ → add album to ♫";
						}
						$subtitle = "$starString $subtitle";

						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name']) == false)
						{	
							$w->result( "spotify_mini-spotify-track-" . $item['data']['name'], $item['data']['uri'] . "|" . $item['data']['album']['uri'] . "|" . $item['data']['album']['artist']['uri'] . "|||||"  . "|" . $alfred_playlist_uri, ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name'], $subtitle, getTrackArtwork($item['data']['uri'],true), 'yes', '' );
						}
						if($artist_uri == "")
							$artist_uri = $item['data']['album']['artist']['uri'];
						$currentResultNumber++;
					}			
				}
				$w->result( '', "||||activate (open location \"spotify:search:" . $artist . "\")|||", "Search for " . $artist . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "|||||" . $artist_uri . " ► " . $artist . " ►" . "|||", "Search for " . $artist . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}
			else
			{
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
								
					if (strpos(strtolower($item['data']['album']['artist']['name']),strtolower($artist)) !== false &&
						strpos(strtolower($item['data']['name']),strtolower($track)) !== false)
					{				
						// Figure out search rank
						$popularity = $item['data']['popularity'];
						$popularity/=100;
						
						// Convert popularity to stars
						$stars = floor($popularity * 5);
						$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
							
						$subtitle = $item['data']['album']['name'] . " - ⌥ → ▶ album, ⌘ →▶ artist";
						if($is_alfred_playlist_active ==true)
						{
							$subtitle = "$subtitle ,fn → add track to ♫, ⇧ → add album to ♫";
						}
						$subtitle = "$starString $subtitle";

						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name']) == false)
						{								
							$w->result( "spotify_mini-spotify-track-" . $item['data']['name'], $item['data']['uri'] . "|" . $item['data']['album']['uri'] . "|" . $item['data']['album']['artist']['uri'] . "|||||"  . "|" . $alfred_playlist_uri, ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name'], $subtitle, getTrackArtwork($item['data']['uri'],true), 'yes', '' );
						}
						$currentResultNumber++;
					}
				};
				$w->result( '', "||||activate (open location \"spotify:search:" . $track . "\")||||", "Search for " . $track . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "|||||" . "$track" . "|||", "Search for " . $track . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}
		}// end of tracks by artist
		elseif($kind == "Album")
		{
			if($all_playlists == false)
			{
				$json = file_get_contents($w->data() . "/library_starred_playlist.json");
			}
			else
			{
				$json = file_get_contents($w->data() . "/library.json");
			}	
			$json = json_decode($json,true);

			//		
			// display tracks for selected album
			//
			$album=$words[1];
			$track=$words[2];
			
			if(mb_strlen($track) < 3)
			{
				$currentResultNumber = 1;
				$album_uri = "";
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
	
					if (strpos(strtolower($item['data']['album']['name']),strtolower($album)) !== false)
					{				
						// Figure out search rank
						$popularity = $item['data']['popularity'];
						$popularity/=100;
						
						// Convert popularity to stars
						$stars = floor($popularity * 5);
						$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
							
						$subtitle = $item['data']['album']['name'] . " - ⌥ → ▶ album, ⌘ →▶ artist";
						if($is_alfred_playlist_active ==true)
						{
							$subtitle = "$subtitle ,fn → add track to ♫, ⇧ → add album to ♫";
						}
						$subtitle = "$starString $subtitle";

						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name']) == false)
						{	
							$w->result( "spotify_mini-spotify-track-" . $item['data']['name'], $item['data']['uri'] . "|" . $item['data']['album']['uri'] . "|" . $item['data']['album']['artist']['uri'] . "|||||"  . "|" . $alfred_playlist_uri, ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name'], $subtitle, getTrackArtwork($item['data']['uri'],true), 'yes', '' );
						}
						if($album_uri == "")
							$album_uri = $item['data']['album']['uri'];
						$currentResultNumber++;
					}			
				}
				$w->result( '', "||||activate (open location \"spotify:search:" . $album . "\")||||", "Search for " . $album . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "|||||" . $album_uri . " ► " . $album . " ►" . "|||", "Search for " . $album . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}
			else
			{
				$currentResultNumber = 1;
				foreach ($json as $item) 
				{	
					if($currentResultNumber > $max_results)
						break;
								
					if (strpos(strtolower($item['data']['album']['name']),strtolower($album)) !== false &&
						strpos(strtolower($item['data']['name']),strtolower($track)) !== false)
					{				
						// Figure out search rank
						$popularity = $item['data']['popularity'];
						$popularity/=100;
						
						// Convert popularity to stars
						$stars = floor($popularity * 5);
						$starString = str_repeat("⭑", $stars) . str_repeat("⭒", 5 - $stars);
							
						$subtitle = $item['data']['album']['name'] . " - ⌥ → ▶ album, ⌘ →▶ artist";
						if($is_alfred_playlist_active ==true)
						{
							$subtitle = "$subtitle ,fn → add track to ♫, ⇧ → add album to ♫";
						}
						$subtitle = "$starString $subtitle";

						if(checkIfResultAlreadyThere($w->results(),ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name']) == false)
						{	
							$w->result( "spotify_mini-spotify-track-" . $item['data']['name'], $item['data']['uri'] . "|" . $item['data']['album']['uri'] . "|" . $item['data']['album']['artist']['uri'] . "|||||"  . "|" . $alfred_playlist_uri, ucfirst($item['data']['album']['artist']['name']) . " - " . $item['data']['name'], $subtitle, getTrackArtwork($item['data']['uri'],true), 'yes', '' );
						}
						$currentResultNumber++;
					}
				};
				$w->result( '', "||||activate (open location \"spotify:search:" . $track . "\")||||", "Search for " . $track . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
				if($is_spotifious_active == true)
				{
					$w->result( '', "||||||" . "$track" . "||", "Search for " . $track . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
				}
			}			
		}// end of tracks by album
		elseif($kind == "Playlist")
		{
			//		
			// display tracks for selected playlist
			//
			$playlist=$words[1];
			$track=$words[2];
			
			// retrieve playlist uri from playlist name
			if(file_exists($w->data() . "/playlists.json"))
			{
				$json = file_get_contents($w->data() . "/playlists.json");
				$json = json_decode($json,true);
				
				$playlist_file = "nonexistant";
				foreach ($json as $key => $val) 
				{
					if (strpos(str_replace(")","\)",str_replace("(","\(",strtolower($val))),strtolower($playlist)) !== false)
					{
						$res = explode(':', $key);
						$playlist_name = $res[4];
						$playlist_user = $res[2];
						$playlist_file = 'playlist_' . $playlist_name . '.json';
						break;
					}
				}
				
				if(file_exists($w->data() . "/" . $playlist_file))
				{
					$json = file_get_contents($w->data() . "/" . $playlist_file);
					$json = json_decode($json,true);	

					$subtitle = "Launch Playlist";
					if($is_alfred_playlist_active ==true &&
						$val != "Alfred Playlist")
					{
						$subtitle = "$subtitle ,⇧ → add playlist to ♫";
					}
					$w->result( "spotify_mini-spotify-playlist-$val", "|||" . $key . "||||" . "|" . $alfred_playlist_uri, ucfirst($val) . " by " . $playlist_user, $subtitle, './images/playlists.png', 'yes', '' );
									
					if(mb_strlen($track) < 3)
					{
						//
						// display all tracks from playlist
						//
						$currentResultNumber = 1;
						foreach ($json as $item) 
						{	
							if($currentResultNumber > $max_results)
								break;
		
							$w->result( "spotify_mini-spotify-" . $playlist . "-" . $item[1], $item[2] . "|||||||" . "|" . $alfred_playlist_uri, ucfirst($item[0]) . " - " . $item[1], "▶ track", getTrackArtwork($item[2],true), 'yes', '' );
							$currentResultNumber++;		
						}
						$w->result( '', "||||activate (open location \"spotify:search:" . $playlist . "\")||||", "Search for " . $playlist . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
						if($is_spotifious_active == true)
						{
							$w->result( '', "|||||" . "$playlist" . "|||", "Search for " . $playlist . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );
						}

					}
					else
					{
						$currentResultNumber = 1;
						foreach ($json as $item) 
						{	
							if($currentResultNumber > $max_results)
								break;
										
							if (strpos(strtolower($item[1]),strtolower($track)) !== false ||
								strpos(strtolower($item[0]),strtolower($track)) !== false)
							{					
								$w->result( "spotify_mini-spotify-" . $playlist . "-" . $item[1], $item[2] . "|||||||" . "|" . $alfred_playlist_uri, ucfirst($item[0]) . " - " . $item[1], "▶ track", getTrackArtwork($item[2],true), 'yes', '' );
								$currentResultNumber++;
							}	
						};
						$w->result( '', "||||activate (open location \"spotify:search:" . $track . "\")||||", "Search for " . $track . " with Spotify", "This will start a new search in Spotify", 'fileicon:/Applications/Spotify.app', 'yes', '' );
						if($is_spotifious_active == true)
						{
							$w->result( '', "|||||" . "$track" . "|||", "Search for " . $track . " with Spotifious", "Spotifious workflow must be installed", './images/spotifious.png', 'yes', '' );	
						}
					}									
				}				
				
			}			
		}// end of tracks by Playlist
		elseif($kind == "Settings")
		{
			$max_results=$words[2];
			
			if(mb_strlen($max_results) == 0)
			{					
				$w->result( '', '', "Enter the Max Results number (must be greater than 0):", "The number of results has impact on performances", './images/settings.png', 'no', '' );
			}
			else
			{
				// max results has been set
				if(is_numeric($max_results) == true && $max_results > 0)
				{
					$w->result( '', "||||||$max_results||", "Max Results will be set to <" . $max_results . ">", "Type enter to validate the Max Results", './images/settings.png', 'yes', '' );
				}
				else
				{
					$w->result( '', '', "The Max Results value entered is not valid", "Please fix it", './images/warning.png', 'no', '' );

				}
			}			
		}// end of Settings
		elseif($kind == "Alfred Playlist")
		{
			$alfred_playlist_uri=$words[2];
			
			if(mb_strlen($alfred_playlist_uri) == 0)
			{					
				$w->result( '', '', "Enter the Alfred Spotify URI:", "Create the playlist in Spotify(shall be named <Alfred Playlist>, right click on it and select copy spotify URI", './images/settings.png', 'no', '' );
			}
			else
			{
				// alfred_playlist_uri has been set
				if(substr_count( $alfred_playlist_uri, ':' ) == 4)
				{
					$playlistName = getPlaylistName($alfred_playlist_uri);
					if($playlistName == "Alfred Playlist")
					{
						$w->result( '', "||||||$alfred_playlist_uri||", "Alfred Playlist URI will be set to <" . $alfred_playlist_uri . ">", "Type enter to validate", './images/settings.png', 'yes', '' );
					}
					else
					{
						$w->result( '', '', "The playlist name entered <" . $playlistName . "> is not valid", "shall be <Alfred Playlist>", './images/warning.png', 'no', '' );						
					}
				}
				else
				{
					$w->result( '', '', "The playlist URI entered is not valid", "format is spotify:user:myuser:playlist:20SZYrktr658JNa42Lt1vV", './images/warning.png', 'no', '' );

				}
			}			
		}// end of Settings
	}
}

echo $w->toxml();

?>