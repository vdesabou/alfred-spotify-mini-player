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
			getPlaylistTracks(drop.uri,drop.name,function(matchedPlaylistTracks) {
	
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
			case "toplist":
				playTopList();
				break;
			case "update_library":
				getAll(function(matchedAll) {
					console.log("update_library finished", matchedAll);
	
					var conn = new WebSocket('ws://localhost:8080');
					conn.onopen = function(e) {
					    console.log("Connection established!");
					    conn.send(JSON.stringify(matchedAll));
					};
					
					conn.onmessage = function(e) {
					    console.log("Received response: ",e.data);
					    conn.close();
					};
				});
				break;				
			case "addtoalfredplaylist":
				if(args[8])
				{
					addToAlfredPlaylist(args);
				}
				break;
			case "addplaylisttoalfredplaylist":
				if(args[10])
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
				if(args[5])
				{
					startPlaylist(args);
					break;
				}
				else if(args[4] == 'starred' )
				{
					startStarredPlaylist(args);
					break;
				}
				break;
		}
	}		
}

function playTopList() {

    models.player.playContext(Toplist.forCurrentUser());
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


function startStarredPlaylist(args) {	
	models.Playlist.fromURI(Library.forCurrentUser().starred.uri).load('name').done(function(playlist) {
	  console.log(Library.forCurrentUser().starred.uri + ': ' + playlist.name.decodeForText());
	  models.player.playContext(playlist);
	});	
				
}


function starCurrentTrack() {
	var track = models.player.track;
	
	if (track != null) {
		models.Track.fromURI(track.uri).star();
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
    

function getAlbum(uri,matchedAlbumCallback) {
	

	models.Album.fromURI(uri).load('name','uri').done(function(album) {
			// This callback is fired when the album has loaded.
			// The album object has a tracks property, which is a standard array.

			objalbum={};
			objalbum.name=album.name;
			objalbum.uri=album.uri;
			
			matchedAlbumCallback(objalbum);
		});	
}

function getPlaylistTracks(uri,name,matchedPlaylistTracksCallback) {
		
	var array_tracks = [];	
	var playlist = models.Playlist.fromURI(uri);
	playlist.load('tracks','name','uri').done(function() {
	  playlist.tracks.snapshot().done(function(snapshot) {
	    snapshot.loadAll('name','popularity','starred','artists').each(function(track) {
	    
		getAlbum(track.album.uri,function(matchedAlbum) {
				//console.log("getAlbum finished", matchedAlbum);
	
	
				objtrack={};
				objtrack.playlist_name=playlist.name;
				objtrack.playlist_uri=playlist.uri;
				objtrack.name=track.name;
				objtrack.uri=track.uri;
				objtrack.popularity=track.popularity;
				objtrack.starred=track.starred;
				objtrack.artist_name=track.artists[0].name;
				objtrack.artist_uri=track.artists[0].uri;
				objtrack.album_name=matchedAlbum.name;
				objtrack.album_uri=matchedAlbum.uri;
				//console.log(playlist.name,playlist.uri,track.uri,track.name,matchedAlbum.name,matchedAlbum.uri);
				array_tracks.push(objtrack);
				
				if(snapshot.length == array_tracks.length)
				{
					p={};
					p.name=name;
					p.uri=uri;
					p.tracks=array_tracks; 

					matchedPlaylistTracksCallback(p);
				}
			
			});	    
	    });
	    
	  });
	});			
}

function getPlaylists(matchedPlaylistsCallback) {
		
	var array_results = [];
						
    Library.forCurrentUser().playlists.snapshot().done(function(snapshot){
		for (var i = 0, l = snapshot.length; i < l; i++) 
		{
			var myplaylist = snapshot.get(i);

			if(myplaylist != null) 
			{			
				objplaylist={};
	
				objplaylist.name=myplaylist.name;
				objplaylist.uri=myplaylist.uri;
				array_results.push(objplaylist);
			}
		}
		// Add starred playlist at the end
		objstarredplaylist={};
		objstarredplaylist.name="Starred";
		objstarredplaylist.uri=Library.forCurrentUser().starred.uri;
		array_results.push(objstarredplaylist);
		
		matchedPlaylistsCallback(array_results);
    });
		
}


function getAll(matchedAllCallback) {

	var array_results = [];
	getPlaylists(function(matchedPlaylists) {
	    console.log("getPlaylists finished", matchedPlaylists);

		for (var i = 0, l = matchedPlaylists.length; i < l; i++) 
		{
			getPlaylistTracks(matchedPlaylists[i].uri,matchedPlaylists[i].name,function(matchedPlaylistTracks) {

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