function spotifyExport() {

	var library = models.library.tracks;
	var tracks = [];
	var html = "";

	for (var index in library) {
		var track = new models.Track(library[index].data),
			artists;

		tracks.push(track);

		html += "<li><a href='" + track.uri + "'>" + track + "</a></li>";

	}

	$("#json").text(JSON.stringify(tracks));
	$("#tracks").append(html);
}
