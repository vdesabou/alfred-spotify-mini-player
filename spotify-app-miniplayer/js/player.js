function addToMiniPlayerPlaylist(args) {	
	// args[0] = page,  args[1] = command, args[2] = value 
	// e.g. spotify:app:miniplayer:search:play:the+cure+close+to+me
	//var query = unescape(args[2].replace(/\+/g, " ")); //clean up the search query
	//console.log(query);

	//addtominiplayerplaylist:7i105IWmto7KSH6jB27DO3
	
	// Create a track object from a URI
	

        var track = models.Track.fromURI(args[1]+':'+args[2]+':'+args[3]);
        console.log(track);
        // Create a playlist object from a URI
       // var playlist = models.Playlist.fromURI('spotify:user:vdesabou:playlist:1CQlXLTaSNJmpZVbT1qJOU');
        var playlist = new models.Playlist("Alfred Mini Player");
        
        playlist.data.collaborative = false;

        // Add the track
        playlist.add(track);

        // Verify the song was added to the playlist
        console.log(playlist);
}

function playArtistOrAlbum(args) {	

	console.log(args[1]+':'+args[2]+':'+args[3]);
	player.play (args[1]+':'+args[2]+':'+args[3],args[1]+':'+args[2]+':'+args[3],0)
				
}

function startPlaylist(args) {	
	//spotify:user:vdesabou:playlist:0CioVOCvmD2DBYokAjySiz
	console.log(args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5]);
	player.play (args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5],args[1]+':'+args[2]+':'+args[3]+':'+args[4]+':'+args[5],0)
				
}


function startStarredPlaylist(args) {	
	//spotify:user:vdesabou:starred
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
	//clearPlaylist(tempPlaylist);
	//tempPlaylist.add(track.data.uri);
	//player.play(track.data.uri, tempPlaylist.data.uri, 0);
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
			case "togglePause":
				// Check if playing and reverse it
				player.playing = !(player.playing);
				e.preventDefault();
				break;
			case "skip":
				// skip to next track
				player.next();
				e.preventDefault();
				break;
			case "export":
				setTimeout(function() {
					spotifyExport();
				
					}, 1000);
				
				$("textarea").on("click", function() {
				
				$(this).select();
				
				});
				e.preventDefault();
				break;				
			case "playTrackFromUri":
				// Grab a random track from your library (cause it's more fun)
				var tracks = library.tracks;
				var track = tracks[Math.floor(Math.random()*tracks.length)]
				clearPlaylist(tempPlaylist);
				tempPlaylist.add(track.data.uri);
				player.play(track.data.uri, tempPlaylist.data.uri, 0);
				e.preventDefault();
				break;
			case "playTrackFromContext":
				// Play an item (artist, album, playlist) from a particular position
				player.play(
					$(this).attr('href'),				// Item to play
					$(this).attr('href'),				// Context to use
					parseInt($(this).attr('pos'))		// Position to play from
				);
				e.preventDefault();
				break;
			case "showSharePopup":
				// skip to next track
				application.showSharePopup(document.getElementById($(this).attr('id')),player.track.uri); // This will fail if you're listening to a local track :(
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