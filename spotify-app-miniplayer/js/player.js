/**
 *      Largely inspired by @ptrwtts             
 *		https://github.com/ptrwtts/kitchensink
 *		
 */
 
/*
function playTopList() {
	var toplist = new models.Toplist();
	toplist.toplistType = models.TOPLISTTYPE.USER;
	toplist.matchType = models.TOPLISTMATCHES.TRACKS;
	toplist.userName = models.TOPLISTUSER_CURRENT;

	var playlist = new models.Playlist();
	
	toplist.observe(models.EVENT.CHANGE, function() {
	    toplist.results.forEach(function(track) {
	        var link = '<li><a href="' + track.uri + '">' + track.name + '</a></li>';
		    var library_track = models.Track.fromURI(track.uri);
	      
	        // Add the track
	        playlist.add(library_track);
	    });
	});
	 
	toplist.run();

    // Verify the song was added to the playlist
    console.log(playlist);
}
*/
/*
function addToAlfredPlaylist(args) {

	// Get the playlist object from a URI
	var playlist = models.Playlist.fromURI(args[4]+':'+args[5]+':'+args[6]+':'+args[7]+':'+args[8]);
	
	playlist.data.collaborative = false;
		
	if(args[2] == 'track')
	{
		// Create a track object from a URI
		var track = models.Track.fromURI(args[1]+':'+args[2]+':'+args[3]);
			
		// Add the track
		playlist.add(track);
	}
	else if(args[2] == 'album')
	{
		models.Album.fromURI(args[1]+':'+args[2]+':'+args[3], function(album) {
		    // This callback is fired when the album has loaded.
		    // The album object has a tracks property, which is a standard array.
		    
		   
			for (var i = 0, l = album.length; i < l; i++){
	      
					models.Track.fromURI(album.tracks[i].uri, function (track) {
					    // Track has loaded!
					    playlist.add(track);
					});
	        }
    	});
	}
		
	// Verify the song was added to the playlist
	console.log(playlist);			
}

function clearAlfredPlaylist(args) {

	models.Playlist.fromURI(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5], function(playlist) {
	    // This callback is fired when the playlist has loaded.
	    // The playlist object has a tracks property, which is a standard array.
	    	 
		for (var i = 0, l = playlist.length; i < l; i++){

			models.Track.fromURI(playlist.tracks[i].uri, function (track) {
			    // Track has loaded!
			    playlist.remove(track);
			});
             
        }	
	});
				
}
*/


function playArtistOrAlbum(args) {
	console.log(args[1]+':'+args[2]+':'+args[3]);
	player.play (args[1]+':'+args[2]+':'+args[3],args[1]+':'+args[2]+':'+args[3],0)
				
}

function startPlaylist(args) {	
	console.log(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]);
	player.play (args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5],args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5],0)
				
}


function startStarredPlaylist(args) {	
	console.log(args[1]+':'+args[2]+':'+args[3]+':'+args[4]);
	player.play (args[1]+':'+args[2]+':'+args[3]+':'+args[4],args[1]+':'+args[2]+':'+args[3]+':'+args[4],0)
				
}


function starCurrentTrack() {
	var track = player.track;

	if (track != null) {
		track.starred = true;
	} 	
}

function randomTrack() {
	// Grab a random track from your library (cause it's more fun)
	var tracks = library.tracks;
	var track = tracks[Math.floor(Math.random()*tracks.length)]
	player.play(track.data.uri, track.data.uri, 0);
}

tempPlaylist = new models.Playlist();

$(function(){
	// Update the page when the app loads
	nowPlaying();
	
	// Listen for track changes and update the page
	player.observe(models.EVENT.CHANGE, function (event) {
		if (event.data.curtrack == true) {
			var track = player.track;
			$("#play-history").append('<div>Track changed to: '+track.name+' by '+track.album.artist.name+'</div>');
		}
		nowPlaying();
		
	}); 
		
	$("#commands a").click(function(e){
		switch($(this).attr('command')) {
			case "export":
				setTimeout(function() {
					spotifyExport();
				
					}, 1000);
				
				$("textarea").on("click", function() {
				
				$(this).select();
				
				});
				e.preventDefault();
				break;
		}
	});
	
});

function clearPlaylist(playlist) {
	while (playlist.data.length > 0) {
		playlist.data.remove(0);
	}
}

function nowPlaying() {

	// This will be null if nothing is playing.
	var track = player.track;

	if (track == null) {
		$("#now-playing").html("Painful silence!");
	} else {
		var link = null;
		if (player.context)
			link = new models.Link(player.context);
		else
			link = new models.Link(player.track.uri);
			
		if (link.type === models.Link.TYPE.ARTIST)
			playerImage.context = models.Artist.fromURI(link.uri);
		else if (link.type === models.Link.TYPE.PLAYLIST)
			playerImage.context = models.Playlist.fromURI(link.uri);
		else if (link.type === models.Link.TYPE.INTERNAL) {
			if (tempPlaylist.length > 0)
				playerImage.context = tempPlaylist;
		}
			
		$("#now-playing").empty();
		var cover = $(document.createElement('div')).attr('id', 'player-image');

		if (link.type === models.Link.TYPE.TRACK || link.type === models.Link.TYPE.LOCAL_TRACK ||
			(link.type === models.Link.TYPE.INTERNAL && tempPlaylist.length == 0)) {
			cover.append($(document.createElement('a')).attr('href', track.data.album.uri));
			var img = new ui.SPImage(track.data.album.cover ? track.data.album.cover : "sp://import/img/placeholders/300-album.png");
			cover.children().append(img.node);
		} else {
			cover.append($(playerImage.node));
		}
		
		$("#now-playing").append(cover);
		
		var song = '<a href="'+track.uri+'">'+track.name+'</a>';
		var album = '<a href="'+track.album.uri+'">'+track.album.name+'</a>';
		var artist = '<a href="'+track.album.artist.uri+'">'+track.album.artist.name+'</a>';
		var context = player.context, extra ="";
		if(context) { extra = ' from <a href="'+context+'">here</a>'; } // too lazy to fetch the actual context name
		$("#now-playing").append(song+" by "+artist+" off "+album+extra);
	}
	
}