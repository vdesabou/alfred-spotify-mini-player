/**
 *      Largely inspired by @jamesrwhite             
 *		https://github.com/jamesrwhite/spotify-export
 *		
 */
function spotifyExport() {

	var library = models.library.tracks;
	var tracks = [];

	for (var index in library) {
		var track = new models.Track(library[index].data),
			artists;

		tracks.push(track);
	}

	$("#json").text(JSON.stringify(tracks));
}
