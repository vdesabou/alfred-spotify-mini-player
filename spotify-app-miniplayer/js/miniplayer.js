require([
        '$api/models',
        '$api/toplists#Toplist',
        '$api/library#Library'
        ], function(models, Toplist, Library) {

    // When application has loaded, run handleArgs function
    models.application.load('arguments').done(handleArgs);

    // When arguments change, run handleArgs function
    models.application.addEventListener('arguments', handleArgs);
 
    	
function handleArgs() {
	var args = models.application.arguments;
	
	// If there are multiple arguments, handle them accordingly
	if(args[0]) 
	{	
		appendText("\nNew command received: <" + args + "> \n==================");	
		switch(args[0]) 
		{
			case "random":
				randomTrack();
				break;
			case "star":
				starCurrentTrack();
				break;
			case "update_library":
				sleep(1000);
				getAll(function(matchedAll) {
					appendText("update_library finished");
					
					var conn = new WebSocket('ws://localhost:' + args[1]);
					conn.onopen = function(e) {
						appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
					    conn.send('update_library⇾' + JSON.stringify(matchedAll));
					};
					
					conn.onerror = function (e) {
                        appendText("Error received: " + e.data);
                    };
					
					conn.onclose = function (e) {
                        appendText("Workflow closed connection: " + e.reason);
                    };
					
					conn.onmessage = function(e) {
					    appendText("Received response from workflow: " + e.data);
					    conn.close();
					};
				});
				break;
			case "update_playlist_list":
				sleep(1000);
				getAllPlaylists(function(matchedAll) {
					appendText("update_playlist_list finished");
	
					var conn = new WebSocket('ws://localhost:' + args[1]);
					conn.onopen = function(e) {
						appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
					    conn.send('update_playlist_list⇾' + JSON.stringify(matchedAll));
					};
					
					conn.onerror = function (e) {
                        appendText("Error received: " + e.data);
                    };
					
					conn.onclose = function (e) {
                        appendText("Workflow closed connection: " + e.reason);
                    };
					
					conn.onmessage = function(e) {
					    appendText("Received response from workflow: " + e.data);
					    conn.close();
					};
				});
				break;					
			case "update_playlist":
				sleep(1000);
				var array_results = [];
				if(args[7])
				{
					var pl = models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]);
					var tcpport = args[6];
				}
				else if(args[4] == 'starred' || args[4] == 'toplist' )
				{
					var pl = models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]);
					var tcpport = args[5];
				}
				
				pl.load('name','uri').done(function() {
					getPlaylistTracks(pl.uri,function(matchedPlaylistTracks) {
			
						array_results.push(matchedPlaylistTracks);	
			
						appendText("update_playlist finished");
				
						var conn = new WebSocket('ws://localhost:' + tcpport);

						conn.onopen = function(e) {
						    appendText("Connection established with Spotify Mini Player workflow on port " + tcpport + ". Transmitting data..");
						    conn.send('update_playlist⇾' + JSON.stringify(array_results))
						};
						
						conn.onerror = function (e) {
                            appendText("Error received: " + e.data);
                        };
						
						conn.onclose = function (e) {
                            appendText("Workflow closed connection: " + e.reason);
                        };
						
						conn.onmessage = function(e) {
						    appendText("Received response from workflow: " + e.data);
						    conn.close();
						};
					});	
						
			        
				});	
				break;				
			case "addtoalfredplaylist":
				if(args[7] == 'starred')
				{
					starTrackOrAlbum(args);
				}
				else if(args[8])
				{
					addToAlfredPlaylist(args);
				}
				break;
			case "addplaylisttoalfredplaylist":
				if(args[10])
				{					
					addPlaylistToAlfredPlaylist(args);
				}
				else if(args[9])
				{
					if(args[9] == 'starred')
					{
						starPlaylist(args);
					}
					else
					{
						addTopOrStarredListToAlfredPlaylist(args);
					}

				}
				else if(args[8])
				{
					if(args[8] == 'starred')
					{
						starTopList(args);
					}					
				}

				break;
			case "playartistoralbum":
				if(args[3])
				{
					playArtistOrAlbum(args);
				}
				break;
			case "startplaylist":
				startPlaylist(args);
				break;
			case "clearplaylist":
				// make sure playlist is not starred or top list
				if(args[5])
				{
					clearPlaylist(args);
				}
				break;				
		}
	}		
}



function starTrackOrAlbum(args) {
		if(args[2] == 'track')
		{
	        models.Track.fromURI(args[1]+':'+args[2]+':'+args[3]).star();
		}
		else if(args[2] == 'album')
		{
			
			models.Album.fromURI(args[1]+':'+args[2]+':'+args[3]).load('tracks').done(function(a) {
			    // This callback is fired when the album has loaded.
			    // The album object has a tracks property, which is a standard array.
							
				a.tracks.snapshot().done(function(t){
								
				                var tracks = t.toArray();
				                for(i=0;i<tracks.length;i++){
				                	//console.log(t.get(i).name);
				                    tracks[i].star();
				                }
				            });
	    	});
		}
}

function clearPlaylist(args) {
	// Get the playlist object from a URI
	models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]).load('tracks').done(function(playlist) {

			
	    playlist.tracks.clear();
			
		// Verify the song was added to the playlist
		//console.log(playlist);	
	});	
}

function addToAlfredPlaylist(args) {
	// Get the playlist object from a URI
	models.Playlist.fromURI(args[4]+':'+args[5]+':'+args[6]+':'+args[7]+':'+args[8]).load('tracks').done(function(playlist) {

			
		if(args[2] == 'track')
		{
	        track = models.Track.fromURI(args[1]+':'+args[2]+':'+args[3]);
	        playlist.tracks.add(track);
		}
		else if(args[2] == 'album')
		{
			
			models.Album.fromURI(args[1]+':'+args[2]+':'+args[3]).load('tracks').done(function(a) {
			    // This callback is fired when the album has loaded.
			    // The album object has a tracks property, which is a standard array.
							
				a.tracks.snapshot().done(function(t){
								
				                var tracks = t.toArray();
				                for(i=0;i<tracks.length;i++){
				                	console.log(t.get(i).name);
				                    playlist.tracks.add(tracks[i]);
				                }
				            });
	    	});
		}
			
		// Verify the song was added to the playlist
		console.log(playlist);	
	});	
}

function starTopList(args) {
	// Get the playlist object from a URI

	models.Playlist.fromURI(args[5]+':'+args[6]+':'+args[7]+':'+args[8]).load('tracks').done(function(alfredplaylist) {
		
		if(args[4] == 'toplist')
		{
			console.log(starTopList + args);
			models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]).load('tracks').done(function(p) {
			    // This callback is fired when the playlist has loaded.
			    // The playlist object has a tracks property, which is a standard array.
							
				p.tracks.snapshot().done(function(t){
								
				                var tracks = t.toArray();
				                for(i=0;i<tracks.length;i++){
				                	console.log(t.get(i).name);
				                    tracks[i].star();
				                }
				            });
	    	});
		}
			
		// Verify the song was added to the playlist
		console.log(alfredplaylist);	
	});	
}

function addTopOrStarredListToAlfredPlaylist(args) {
	// Get the playlist object from a URI

	models.Playlist.fromURI(args[5]+':'+args[6]+':'+args[7]+':'+args[8]+':'+args[9]).load('tracks').done(function(alfredplaylist) {
		
		if(args[4] == 'toplist' || args[4] == 'starred')
		{
			models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]).load('tracks').done(function(p) {
			    // This callback is fired when the playlist has loaded.
			    // The playlist object has a tracks property, which is a standard array.
				
				var sorted = p.tracks.sort('addTime', 'desc');
						
				sorted.snapshot().done(function(t){
								
				                var tracks = t.toArray();
				                for(i=0;i<tracks.length;i++){
				                	console.log(t.get(i).name);
				                    alfredplaylist.tracks.add(tracks[i]);
				                }
				            });
	    	});
		}
			
		// Verify the song was added to the playlist
		console.log(alfredplaylist);	
	});	
}


function addPlaylistToAlfredPlaylist(args) {
	// Get the playlist object from a URI

	models.Playlist.fromURI(args[6]+':'+args[7]+':'+args[8]+':'+args[9]+':'+args[10]).load('tracks').done(function(alfredplaylist) {
		
		if(args[4] == 'playlist')
		{
			models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]).load('tracks').done(function(p) {
			    // This callback is fired when the playlist has loaded.
			    // The playlist object has a tracks property, which is a standard array.
				
				var sorted = p.tracks.sort('addTime', 'desc');
							
				sorted.snapshot().done(function(t){
								
				                var tracks = t.toArray();
				                for(i=0;i<tracks.length;i++){
				                	console.log(t.get(i).name);
				                    alfredplaylist.tracks.add(tracks[i]);
				                }
				            });
	    	});
		}
			
		// Verify the song was added to the playlist
		console.log(alfredplaylist);	
	});	
}

function starPlaylist(args) {

		models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]).load('tracks').done(function(p) {
		    // This callback is fired when the playlist has loaded.
		    // The playlist object has a tracks property, which is a standard array.
						
			p.tracks.snapshot().done(function(t){
							
			                var tracks = t.toArray();
			                for(i=0;i<tracks.length;i++){
			                	console.log(t.get(i).name);
			                    tracks[i].star();
			                }
			            });
    	});
}

function playArtistOrAlbum(args) {
	console.log(args[1]+':'+args[2]+':'+args[3]);
	
	var album = models.Album.fromURI(args[1]+':'+args[2]+':'+args[3]);
	models.player.playContext(album);				
}

function startPlaylist(args) {	

		var playlistName = "I'm a test playlist"; 
	 
		models.Playlist.createTemporary(playlistName).done(function(playlist) {
			playlist.load("tracks").done(function(playlist) {
				// Clearing playlist. If there's a temporary playlist with the
				// same name, previously added tracks may be on the playlist.
				playlist.tracks.clear().done(function(emptyCollection) {
				
				 	var orginalplaylist = models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]);
				 	orginalplaylist.load('name','tracks').done(function() {
				 	
				 		var sorted = orginalplaylist.tracks.sort('addTime', 'desc');
				 		sorted.snapshot().done(function(t) {
				 			var tracks = t.toArray();
				 			
							playlist.tracks.add(tracks).done(function(addedTracks) {
								playlist.tracks.snapshot().done(function(snapshot) {

	/*
								for (var i = 0; i < snapshot.length; i++) {
										console.log("#" + i + " In array: " + tracks[i] + " -  In Playlist: " + snapshot.get(i).uri);
									}
*/
									models.player.playContext(playlist);
								});
							});
						});
					});
				});
			});
		});
						
}

function starCurrentTrack() {
	var track = models.player.track;
	
	if (track != null) {
		models.Track.fromURI(track.uri).star();
	} 
}

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

function randomTrack() {

	// Grab a random track from your library (cause it's more fun)
    Library.forCurrentUser().tracks.snapshot().done(function(snapshot){
    
    	models.player.playTrack(snapshot.get(Math.floor(Math.random()*snapshot.length)));

    });
}

// Get the currently-playing track
models.player.load('track').done(updateCurrentTrack);
// Update the DOM when the song changes
models.player.addEventListener('change:track', updateCurrentTrack);


function updateCurrentTrack(){
    var currentHTML = document.getElementById('now-playing');
    if (models.player.track == null) {
        currentHTML.innerHTML = 'No track currently playing';
    } else {
        var artists = models.player.track.artists;
        var artists_array = [];
        for(i=0;i<artists.length;i++) {
            artists_array.push(artists[i].name);
        }
        currentHTML.innerHTML = '' + artists_array.join(', ');
        currentHTML.innerHTML += ' - ' + models.player.track.name;
    }
}
    

function getAlbum(objtrack,matchedAlbumCallback) {
	
	models.Album.fromURI(objtrack.album_uri).load('name','uri').done(function(album) {
			// This callback is fired when the album has loaded.
			// The album object has a tracks property, which is a standard array.
			objtrack.album_name=album.name;
			
			matchedAlbumCallback(objtrack);
		}).fail(function() 
          	 { 
          	 	console.log("Failed to get album name for " + objtrack.album_uri);
          	 	objtrack.album_name = ""; 
		  	 	matchedAlbumCallback(objtrack);
		  	 	return;
			 });	
}
/*

function doGetTopTrack(artist, num, callback) {
    var artistTopList = Toplist.forArtist(artist);

    artistTopList.tracks.snapshot(0,num).done(function (snapshot) { //only get the number of tracks we need

        snapshot.loadAll('name').done(function (tracks) {
            var i, num_toptracks;
            num_toptracks = num; //this probably should be minimum of num and tracks.length

            for (i = 0; i < num_toptracks; i++) {
                callback(artist, tracks[i]);
            }
        });
    });
};
*/

function getRelatedArtists(objartist,matchedRelatedArtistsCallback) {
	
	var array_artists= [];
	var array_tmp_artists = [];
		
    models.Artist.fromURI(objartist.artist_uri).load('name', 'related', 'uri', 'biography', 'popularity', 'years').done(function (theartist) {
		
          theartist.related.snapshot().done(function(snapshot) {
          
			if(snapshot.length == 0)
			{
				objartist.related=array_artists;
				if(theartist.biography != null) {
					objartist.biography=theartist.biography.decodeForText();
				}
				else {
					objartist.biography="";
				}
				objartist.popularity=theartist.popularity;
				objartist.years=theartist.years;
				matchedRelatedArtistsCallback(objartist);
				return;
			}
			
            snapshot.loadAll('name').each(function(artist) {
		    	// workaround for http://stackoverflow.com/questions/20440664/incorrect-snapshot-length-returned-for-a-specific-playlist
		    	// use tmp array to get the real snapshot length
				array_tmp_artists.push(artist);
            });

			if(array_tmp_artists.length == 0)
			{	
				objartist.related=array_artists;
				if(theartist.biography != null) {
					objartist.biography=theartist.biography.decodeForText();
				}
				else {
					objartist.biography="";
				}
				objartist.popularity=theartist.popularity;
				objartist.years=theartist.years;
				matchedRelatedArtistsCallback(objartist);
				return;
			}
					              		    	
              for (var i = 0; i < array_tmp_artists.length; i++) {

				var a = array_tmp_artists[i];
	
				if(a != null) 
				{
					objrelatedartist={};
					objrelatedartist.name=a.name;
					objrelatedartist.uri=a.uri;
					array_artists.push(objrelatedartist);
					
					if(array_tmp_artists.length == array_artists.length)
					{	
						objartist.related=array_artists;
						if(theartist.biography != null) {
							objartist.biography=theartist.biography.decodeForText();
						}
						else {
							objartist.biography="";
						}
						objartist.popularity=theartist.popularity;
						objartist.years=theartist.years;
						matchedRelatedArtistsCallback(objartist);
						return;
					}
												
/*
					doGetTopTrack(a, 1, function (artist, toptrack) {
					      
							objartist={};
							objartist.name=artist.name;
							objartist.uri=artist.uri;
							objartist.toptrack=toptrack.name;
							array_artists.push(objartist);
							
							if(array_tmp_artists.length == array_artists.length)
							{
			
								matchedRelatedArtistsCallback(array_artists);
							}
					
					});	
*/		
				
				}

              }


          }).fail(function() 
          	 { 
          	 	appendText("Failed to get related artists for " + objartist.artist_name);
          	 	objartist.related=array_artists;
				if(theartist.biography != null) {
					objartist.biography=theartist.biography.decodeForText();
				}
				else {
					objartist.biography="";
				}
				objartist.popularity=theartist.popularity;
				objartist.years=theartist.years; 
		  	 	matchedRelatedArtistsCallback(objartist);
		  	 	return;
			 });
      }).fail(function() 
          	 { 
          	 	// this happens when it is from a local track
          	 	// artist uri is then spotify:local:Damien+Rice
          	 	//console.log("Failed to load artists for " + objartist.artist_name  + " " + objartist.artist_uri);
          	 	// ignore it by setting artist_name to unknown artist
          	 	objartist.artist_name="unknown artist";
          	 	objartist.related=array_artists;
				objartist.biography="";
				objartist.popularity=0;
				//objartist.years=theartist.years;
		  	 	matchedRelatedArtistsCallback(objartist);
		  	 	return;
			 });
}

function getExternalPlaylistUri(uri,username) {
	var playlist_uri = "";
	var words = uri.split(":");
	
	if(words.length == 5) {
		return words[0] + ":" + words[1] + ":" + username + ":" + words[3] + ":" + words[4];
	} else if (words.length == 4) {
		return words[0] + ":" + words[1] + ":" + username + ":" + words[3];
	}
}

function getPlaylistTracks(uri,matchedPlaylistTracksCallback) {	
	var array_tracks = [];
	var array_artists = [];
	var array_tmp_tracks = [];
	
	if(uri == 'spotify:user:@:toplist')
	{
		var playlist = Library.forCurrentUser().toplist;
	}
	else
	{	
		var playlist = models.Playlist.fromURI(uri);
	}
	
	
	playlist.load('tracks','name','owner').done(function() {
	  appendText("Starting to retrieve all tracks for playlist " + playlist.name);	
	  playlist.owner.load('name','username','currentUser').done(function (owner) {
		  
		  var sorted = playlist.tracks.sort('addTime', 'desc');
		  
		  sorted.snapshot().done(function(snapshot) {
		  		 
		  	//check for empty playlists
			if(snapshot.length == 0)
			{
				p={};
				
				p.name=playlist.name;
				p.ownedbyuser=owner.currentUser;
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks;
				p.artists=array_artists;
	
				matchedPlaylistTracksCallback(p);
				return;
			}
						
		    snapshot.loadAll('name','popularity','starred','artists','availability','playable').each(function(track) {
		    	// workaround for http://stackoverflow.com/questions/20440664/incorrect-snapshot-length-returned-for-a-specific-playlist
		    	// use tmp array to get the real snapshot length
				array_tmp_tracks.push(track);
		    });
		    
			if(array_tmp_tracks.length == 0)
			{
				p={};
				
				p.name=playlist.name;
				p.ownedbyuser=owner.currentUser;
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks;
				p.artists=array_artists; 
	
				matchedPlaylistTracksCallback(p);
				return;
			}
					    
			for (var i = 0, l = array_tmp_tracks.length; i < l; i++) 
			{
				var t = array_tmp_tracks[i];
	
				if(t != null) 
				{
					objtrack={};
					objtrack.playlist_name=playlist.name;
					objtrack.playlist_uri=getExternalPlaylistUri(playlist.uri,owner.username);
					objtrack.name=t.name;
					objtrack.uri=t.uri;
					objtrack.popularity=t.popularity;
					objtrack.starred=t.starred;
					objtrack.artist_name=t.artists[0].name;
					objtrack.artist_uri=t.artists[0].uri;
					objtrack.album_uri=t.album.uri;
					objtrack.availability=t.availability;
					objtrack.playable=t.playable;

					objartist={};
					objartist.artist_name=t.artists[0].name;
					objartist.artist_uri=t.artists[0].uri;
					array_artists.push(objartist);
					
					getAlbum(objtrack,function(matchedAlbum) {

								array_tracks.push(matchedAlbum);
								
								if(array_tmp_tracks.length == array_tracks.length)
								{
									appendText("All tracks for playlist " + playlist.name + " have been retrieved");
									p={};
									p.name=playlist.name;
									p.ownedbyuser=owner.currentUser;
									p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
									p.owner=owner.name;
									p.username=owner.username;
									p.tracks=array_tracks;
									p.artists=array_artists;
									matchedPlaylistTracksCallback(p);
								}					
						});	
				}
			}
	    }).fail(function() 
          	 { 
          	 	appendText("Failed to load tracks for playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.ownedbyuser=owner.currentUser;
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);;
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks;
				p.artists=array_artists;
	
				matchedPlaylistTracksCallback(p);
		  	 	return;
			 });
	  }).fail(function() 
          	 { 
          	 	appendText("Failed to load owner of playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.ownedbyuser=false;
				p.uri=playlist.uri;
				p.owner="unknown";
				p.username="unknown";
				p.tracks=array_tracks;
				p.artists=array_artists; 
	
				matchedPlaylistTracksCallback(p);
		  	 	return;
			 });
	}).fail(function() 
          	 { 
          	 	appendText("Failed to load playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.ownedbyuser=false;
				p.uri=playlist.uri;
				p.owner="unknown";
				p.username="unknown";
				p.tracks=array_tracks;
				p.artists=array_artists; 
	
				matchedPlaylistTracksCallback(p);
		  	 	return;
			 });			
}


function getPlaylists(matchedPlaylistsCallback) {
		
	var array_results = [];

	// Add starred playlist at the start
	objstarredplaylist={};
	objstarredplaylist.uri=Library.forCurrentUser().starred.uri;
	array_results.push(objstarredplaylist);


	objtoplistplaylist={};
	objtoplistplaylist.uri=Library.forCurrentUser().toplist.uri;
	array_results.push(objtoplistplaylist);

									
    Library.forCurrentUser().playlists.snapshot().done(function(snapshot){
		for (var i = 0, l = snapshot.length; i < l; i++) 
		{
			var myplaylist = snapshot.get(i);

			if(myplaylist != null) 
			{			
				objplaylist={};
	
				objplaylist.uri=myplaylist.uri;
				array_results.push(objplaylist);
			}
		}
		
		matchedPlaylistsCallback(array_results);
		return;
    }).fail(function() 
          	 { 
          	 	appendText("Failed to get playlists for current user");
				matchedPlaylistsCallback(array_results);
			 });	
		
}


function getAllRelatedArtists(allplaylists,matchedAllRelatedArtistsCallback)
{
	var array_artists= [];
	var nb_artists_total=0;
	var nb_artists= 0;
	
	for (var i = 0, l = allplaylists.length; i < l; i++) 
	{
		var playlist = allplaylists[i];
		
		for (var j = 0, k = playlist.artists.length; j < k; j++) 
		{
			nb_artists_total+=1;
			var a = playlist.artists[j];
			
			objartist={};
			objartist.artist_name=a.artist_name;
			objartist.artist_uri=a.artist_uri;
	
			getRelatedArtists(objartist,function(matchedRelatedArtists) {
	
						var found = 0;
						for(m=0;m<array_artists.length;m++){
						    if(array_artists[m].artist_name == matchedRelatedArtists.artist_name){
						        ++found; // value was found
						        break;
						    }
						}
						if(found==0)
							array_artists.push(matchedRelatedArtists);
						
						nb_artists+=1;

						if(nb_artists == nb_artists_total)
						{
							matchedAllRelatedArtistsCallback(array_artists);
						}					
				});		
		
		}

	}



}



function getAllPlaylists(matchedAllCallback) {

	var array_results = [];
	getPlaylists(function(matchedPlaylists) {
	    appendText("The list of the playlists has been retrieved");

		for (var i = 0, l = matchedPlaylists.length; i < l; i++) 
		{
			getPlaylistTracks(matchedPlaylists[i].uri,function(matchedPlaylistTracks) {

				array_results.push(matchedPlaylistTracks);	
				if(array_results.length==matchedPlaylists.length)
				{						
					matchedAllCallback(array_results);
				}

			});					

		}
	});
}

function getAll(matchedAll) {

	results={};
	
	results.user=Library.forCurrentUser().owner;
	
	var session = models.session;
	session.load("country").done(function() {
	    results.country=session.country;
	    
	    appendText("Country is set to " + session.country);
	    
		getAllPlaylists(function(matchedAllPlaylists) {
			results.playlists=matchedAllPlaylists;
			
			appendText("All playlists have been processed");
			appendText("Starting retrieval of all related artists");
			getAllRelatedArtists(results.playlists,function(matchedAllRelatedArtists) {
	
				results.artists=matchedAllRelatedArtists;
				appendText("Ended retrieval of all related artists");
				
				matchedAll(results);
			});			
		});	
	  });	
}											

function appendText(myVar) {
	var myTextArea = document.getElementById('debug_area');
	d = new Date();
	myTextArea.innerHTML += d.toLocaleTimeString() + ": ";
	myTextArea.innerHTML += myVar;
	myTextArea.innerHTML += '\n';	
}

$(function(){
		
	$("#commands a").click(function(e){
		switch($(this).attr('command')) {
			case "simulate_update_library":
			
			appendText("Simulate update library");
  			
			getAll(function(matchedAll) {
				appendText("Success!!");

				//$("#debug_area").text(JSON.stringify(matchedAll));
			
			});	
		
			$("textarea").on("click", function() {
				$(this).select();
			});
			e.preventDefault();
			break;
		}
	});
	
});


}); // require