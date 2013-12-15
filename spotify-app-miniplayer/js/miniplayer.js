/**
 *      Largely inspired by @ptrwtts             
 *		https://github.com/ptrwtts/kitchensink
 *		
 */
require([
        '$api/models',
        '$api/location#Location',
        '$api/search#Search',
        '$api/toplists#Toplist',
        '$views/buttons',
        '$views/list#List',
        '$views/image#Image',
        '$api/library#Library'
        ], function(models, Location, Search, Toplist, buttons, List, Image, Library) {

    // When application has loaded, run handleArgs function
    models.application.load('arguments').done(handleArgs);

    // When arguments change, run handleArgs function
    models.application.addEventListener('arguments', handleArgs);

    // Drag content into an HTML element from Spotify
    var dropBox = document.querySelector('#drop-box');
    dropBox.addEventListener('dragstart', function(e){
        e.dataTransfer.setData('text/html', this.innerHTML);
        e.dataTransfer.effectAllowed = 'copy';
    }, false);

    dropBox.addEventListener('dragenter', function(e){
        if (e.preventDefault) e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        this.classList.add('over');
    }, false);

    dropBox.addEventListener('dragover', function(e){
        if (e.preventDefault) e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        return false;
    }, false);

    dropBox.addEventListener('drop', function(e){
        if (e.preventDefault) e.preventDefault();
        var drop = models.Playlist.fromURI(e.dataTransfer.getData('text'));
        console.log(drop);
        this.classList.remove('over');
        var success_message = document.createElement('p');
        success_message.innerHTML = 'Playlist successfully dropped: ' + drop.uri;
       	 
        var array_results = [];
		drop.load('tracks','name','uri').done(function() {
			console.log("drop loaded");
			getPlaylistTracks(drop.uri,function(matchedPlaylistTracks) {
	
				array_results.push(matchedPlaylistTracks);	
	
				console.log("Drop playlist finished", array_results);
		
				$("#json").text(JSON.stringify(array_results));	
				$("textarea").on("click", function() {
				
				$(this).select();
				
				});
			});	
				
	        
		});	
		this.appendChild(success_message);

    }, false);
    
    // Drag content into the sidebar
    models.application.addEventListener('dropped', function(){
        console.log(models.application.dropped);
    });
    
    
    	
function handleArgs() {
	var args = models.application.arguments;
	console.log(args);
	
	// If there are multiple arguments, handle them accordingly
	if(args[0]) 
	{		
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
					console.log("update_library finished", matchedAll);
	
					var conn = new WebSocket('ws://localhost:17693');
					conn.onopen = function(e) {
					    console.log("Connection established!");
					    conn.send('update_library⇾' + JSON.stringify(matchedAll));
					};
					
					conn.onerror = function (e) {
                        console.log("Error: ", e.data);
                    };
					
					conn.onclose = function (e) {
                        console.log("On Close: ", e.reason);
                    };
					
					conn.onmessage = function(e) {
					    console.log("Received response: ",e.data);
					    conn.close();
					};
				});
				break;
			case "update_playlist_list":
				sleep(1000);
				getAll(function(matchedAll) {
					console.log("update_playlist_list finished", matchedAll);
	
					var conn = new WebSocket('ws://localhost:17693');
					conn.onopen = function(e) {
					    console.log("Connection established!");
					    conn.send('update_playlist_list⇾' + JSON.stringify(matchedAll));
					};
					
					conn.onerror = function (e) {
                        console.log("Error: ", e.data);
                    };
					
					conn.onclose = function (e) {
                        console.log("On Close: ", e.reason);
                    };
					
					conn.onmessage = function(e) {
					    console.log("Received response: ",e.data);
					    conn.close();
					};
				});
				break;					
			case "update_playlist":
				sleep(1000);
				var array_results = [];
				if(args[6])
				{
					var pl = models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]);
				}
				else if(args[4] == 'starred' || args[4] == 'toplist' )
				{
					var pl = models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]);
				}
				
				pl.load('name','uri').done(function() {
					getPlaylistTracks(pl.uri,function(matchedPlaylistTracks) {
			
						array_results.push(matchedPlaylistTracks);	
			
						console.log("update_playlist finished", array_results);
				
						var conn = new WebSocket('ws://localhost:17693');
						conn.onopen = function(e) {
						    console.log("Connection established!");
						    conn.send('update_playlist⇾' + JSON.stringify(array_results))
						};
						
						conn.onerror = function (e) {
                            console.log("Error: ", e.data);
                        };
						
						conn.onclose = function (e) {
                            console.log("On Close: ", e.reason);
                        };
						
						conn.onmessage = function(e) {
						    console.log("Received response: ",e.data);
						    conn.close();
						};
					});	
						
			        
				});	
				break;				
			case "addtoalfredplaylist":
				if(args[8])
				{
					addToAlfredPlaylist(args);
				}
				break;
			case "addplaylisttoalfredplaylist":
				if(args[9])
				{
					addTopListToAlfredPlaylist(args);
				}
				else if(args[10])
				{
					addPlaylistToAlfredPlaylist(args);
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
		}
	}		
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

function addTopListToAlfredPlaylist(args) {
	// Get the playlist object from a URI

	models.Playlist.fromURI(args[5]+':'+args[6]+':'+args[7]+':'+args[8]+':'+args[9]).load('tracks').done(function(alfredplaylist) {
		
		if(args[4] == 'toplist')
		{
			console.log(addTopListToAlfredPlaylist + args);
			models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]).load('tracks').done(function(p) {
			    // This callback is fired when the playlist has loaded.
			    // The playlist object has a tracks property, which is a standard array.
							
				p.tracks.snapshot().done(function(t){
								
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
							
				p.tracks.snapshot().done(function(t){
								
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


function playArtistOrAlbum(args) {
	console.log(args[1]+':'+args[2]+':'+args[3]);
	
	var album = models.Album.fromURI(args[1]+':'+args[2]+':'+args[3]);
	models.player.playContext(album);				
}

function startPlaylist(args) {	
	models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]).load('name').done(function(playlist) {
	  console.log(playlist.uri + ': ' + playlist.name.decodeForText());
	  models.player.playContext(playlist);
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
        currentHTML.innerHTML = 'Now playing: ' + artists_array.join(', ');
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

function getRelatedArtists(objtrack,matchedRelatedArtistsCallback) {
	
	var array_artists= [];
	var array_tmp_artists = [];
		
    models.Artist.fromURI(objtrack.artist_uri).load('name', 'related', 'uri').done(function (theartist) {
		
          theartist.related.snapshot().done(function(snapshot) {
          
			if(snapshot.length == 0)
			{
				objtrack.related=array_artists;
				matchedRelatedArtistsCallback(objtrack);
				return;
			}
			
            snapshot.loadAll('name').each(function(artist) {
		    	// workaround for http://stackoverflow.com/questions/20440664/incorrect-snapshot-length-returned-for-a-specific-playlist
		    	// use tmp array to get the real snapshot length
				array_tmp_artists.push(artist);
            });

			if(array_tmp_artists.length == 0)
			{	
				objtrack.related=array_artists;
				matchedRelatedArtistsCallback(objtrack);
				return;
			}
					              		    	
              for (var i = 0; i < array_tmp_artists.length; i++) {

				var a = array_tmp_artists[i];
	
				if(a != null) 
				{
					objartist={};
					objartist.name=a.name;
					objartist.uri=a.uri;
					array_artists.push(objartist);
					
					if(array_tmp_artists.length == array_artists.length)
					{	
						objtrack.related=array_artists;
						matchedRelatedArtistsCallback(objtrack);
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
          	 	console.log("Failed to get related artists for " + objtrack.artist_name);
          	 	objtrack.related=array_artists; 
		  	 	matchedRelatedArtistsCallback(objtrack);
		  	 	return;
			 });
      }).fail(function() 
          	 { 
          	 	console.log("Failed to load artists for " + objtrack.artist_name);
          	 	objtrack.related=array_artists; 
		  	 	matchedRelatedArtistsCallback(objtrack);
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
	var array_tmp_tracks = [];	
	var playlist = models.Playlist.fromURI(uri);
	
	
	playlist.load('tracks','name','owner').done(function() {
	  console.log("getPlaylistTracks started ",playlist.name);	
	  playlist.owner.load('name','username').done(function (owner) {
		  
		  playlist.tracks.snapshot().done(function(snapshot) {

		  console.log("getPlaylistTracks started 2",playlist.name);		  		 
		  	//check for empty playlists
			if(snapshot.length == 0)
			{
				p={};
				
				p.name=playlist.name;
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks; 
	
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
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks; 
	
				matchedPlaylistTracksCallback(p);
				return;
			}
					    
			for (var i = 0, l = array_tmp_tracks.length; i < l; i++) 
			{
/* 				console.log("getPlaylistTracks processing ",array_tmp_tracks[i]); */
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

					getAlbum(objtrack,function(matchedAlbum) {
											
						getRelatedArtists(matchedAlbum,function(matchedRelatedArtists) {

								array_tracks.push(matchedRelatedArtists);
								
								if(array_tmp_tracks.length == array_tracks.length)
								{
									console.log("getPlaylistTracks ended ",playlist.name);
									p={};
									p.name=playlist.name;
									p.uri=getExternalPlaylistUri(playlist.uri,owner.username);
									p.owner=owner.name;
									p.username=owner.username;
									p.tracks=array_tracks; 
									matchedPlaylistTracksCallback(p);
								}							
							});							
						});	
				}
			}
	    }).fail(function() 
          	 { 
          	 	console.log("Failed to load tracks for playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.uri=getExternalPlaylistUri(playlist.uri,owner.username);;
				p.owner=owner.name;
				p.username=owner.username;
				p.tracks=array_tracks; 
	
				matchedPlaylistTracksCallback(p);
		  	 	return;
			 });
	  }).fail(function() 
          	 { 
          	 	console.log("Failed to load owner of playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.uri=playlist.uri;
				p.owner="unknown";
				p.username="unknown";
				p.tracks=array_tracks; 
	
				matchedPlaylistTracksCallback(p);
		  	 	return;
			 });
	}).fail(function() 
          	 { 
          	 	console.log("Failed to load playlist " + uri);
				p={};
				
				p.name=playlist.name;
				p.uri=playlist.uri;
				p.owner="unknown";
				p.username="unknown";
				p.tracks=array_tracks; 
	
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

/*
	objtoplistplaylist={};
	objtoplistplaylist.uri=Library.forCurrentUser().toplist.uri;
	array_results.push(objtoplistplaylist);
*/
									
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
          	 	console.log("Failed to get playlists for current user");
				matchedPlaylistsCallback(array_results);
			 });	
		
}


function getAll(matchedAllCallback) {

	var array_results = [];
	getPlaylists(function(matchedPlaylists) {
	    console.log("getPlaylists finished", matchedPlaylists);

		for (var i = 0, l = matchedPlaylists.length; i < l; i++) 
		{
			getPlaylistTracks(matchedPlaylists[i].uri,function(matchedPlaylistTracks) {

				array_results.push(matchedPlaylistTracks);	
				if(array_results.length==matchedPlaylists.length)
				{
					// it's over Michael
					console.log("it's over Michael",array_results);						
					matchedAllCallback(array_results);
				}

			});					

		}
	});
}
											

$(function(){
		
	$("#commands a").click(function(e){
		switch($(this).attr('command')) {
			case "export":


/*
				getPlaylistTracks("spotify:user:@:toplist",function(matchedAll) {
					console.log("getPlaylistTracks finished", matchedAll);
	
					$("#json").text(JSON.stringify(matchedAll));
				
				});
*/
										
				getAll(function(matchedAll) {
					console.log("getAll finished", matchedAll);
	
					$("#json").text(JSON.stringify(matchedAll));
				
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