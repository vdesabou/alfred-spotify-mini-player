require(['$api/models', '$api/toplists#Toplist', '$api/library#Library'], function(models, Toplist, Library) {
	// When application has loaded, run handleArgs function
	models.application.load('arguments').done(handleArgs);
	// When arguments change, run handleArgs function
	models.application.addEventListener('arguments', handleArgs);
	/**
	 * handleArgs function.
	 *
	 * @access public
	 * @return void
	 */

	function handleArgs() {
		var args = models.application.arguments;
		// If there are multiple arguments, handle them accordingly
		if (args[0]) {
			appendText("\nNew command received: <" + args + "> \n==================");
			switch (args[0]) {
			case "random":
				randomTrack();
				break;
			case "star":
				sleep(1000);
				var array_results = [];
				appendText("star track started");
				appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
				var result = JSON.stringify(starCurrentTrack());
				var pl = models.Playlist.fromURI(Library.forCurrentUser().starred.uri);
				pl.load('name', 'uri').done(function() {
					getPlaylistTracks(pl.uri, function(matchedPlaylistTracks) {
						array_results.push(matchedPlaylistTracks);
						appendText("update_playlist for starred playlist finished");
						appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
						var conn = new WebSocket('ws://127.0.0.1:' + args[1]);
						conn.onopen = function(e) {
							appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
							conn.send('star▹' + JSON.stringify(array_results) + '▹' + result);
						};
						conn.onerror = function(e) {
							appendText("Error received");
						};
						conn.onclose = function(e) {
							appendText("Workflow closed connection " + e.reason);
						};
						conn.onmessage = function(e) {
							appendText("Received response from workflow: " + e.data);
							conn.close();
						};
					});
				});
				break;
			case "unstar":
				sleep(1000);
				var array_results = [];
				appendText("unstar track started");
				appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
				var result = JSON.stringify(unstarCurrentTrack());
				var pl = models.Playlist.fromURI(Library.forCurrentUser().starred.uri);
				pl.load('name', 'uri').done(function() {
					getPlaylistTracks(pl.uri, function(matchedPlaylistTracks) {
						array_results.push(matchedPlaylistTracks);
						appendText("update_playlist for starred playlist finished");
						appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
						var conn = new WebSocket('ws://127.0.0.1:' + args[1]);
						conn.onopen = function(e) {
							appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
							conn.send('unstar▹' + JSON.stringify(array_results) + '▹' + result);
						};
						conn.onerror = function(e) {
							appendText("Error received");
						};
						conn.onclose = function(e) {
							appendText("Workflow closed connection " + e.reason);
						};
						conn.onmessage = function(e) {
							appendText("Received response from workflow: " + e.data);
							conn.close();
						};
					});
				});
				break;
			case "playcurrenttrackalbum":
				playCurrentTrackAlbum();
				break;
			case "playcurrenttrackartist":
				playCurrentTrackArtist();
				break;
			case "addcurrenttrackalbumtoalfredplaylist":
				addCurrentTrackAlbumToAlfredPlaylist(args);
				break;
			case "current_track_get_artist":
				sleep(1000);
				appendText("current_track_get_artist started");
				appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
				var conn = new WebSocket('ws://127.0.0.1:' + args[1]);
				conn.onopen = function(e) {
					appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
					conn.send('current_track_get_artist▹' + JSON.stringify(getCurrentTrackArtist()));
				};
				conn.onerror = function(e) {
					appendText("Error received");
				};
				conn.onclose = function(e) {
					appendText("Workflow closed connection " + e.reason);
				};
				conn.onmessage = function(e) {
					appendText("Received response from workflow: " + e.data);
					conn.close();
				};
				break;
			case "update_library":
				sleep(1000);
				getAll(function(matchedAll) {
					appendText("update_library finished");
					appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
					var conn = new WebSocket('ws://127.0.0.1:' + args[1]);
					conn.onopen = function(e) {
						appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
						conn.send('update_library▹' + JSON.stringify(matchedAll));
					};
					conn.onerror = function(e) {
						appendText("Error received");
					};
					conn.onclose = function(e) {
						appendText("Workflow closed connection " + e.reason);
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
					appendText("Trying to connect with Spotify Mini Player workflow on port " + args[1] + ".");
					var conn = new WebSocket('ws://127.0.0.1:' + args[1]);
					conn.onopen = function(e) {
						appendText("Connection established with Spotify Mini Player workflow on port " + args[1] + ". Transmitting data..");
						conn.send('update_playlist_list▹' + JSON.stringify(matchedAll));
					};
					conn.onerror = function(e) {
						appendText("Error received");
					};
					conn.onclose = function(e) {
						appendText("Workflow closed connection " + e.reason);
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
				if (args[7]) {
					var pl = models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5]);
					var tcpport = args[6];
				} else if (args[4] == 'starred' || args[4] == 'toplist') {
					var pl = models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4]);
					var tcpport = args[5];
				}
				pl.load('name', 'uri').done(function() {
					getPlaylistTracks(pl.uri, function(matchedPlaylistTracks) {
						array_results.push(matchedPlaylistTracks);
						appendText("update_playlist finished");
						appendText("Trying to connect with Spotify Mini Player workflow on port " + tcpport + ".");
						var conn = new WebSocket('ws://127.0.0.1:' + tcpport);
						conn.onopen = function(e) {
							appendText("Connection established with Spotify Mini Player workflow on port " + tcpport + ". Transmitting data..");
							conn.send('update_playlist▹' + JSON.stringify(array_results))
						};
						conn.onerror = function(e) {
							appendText("Error received");
						};
						conn.onclose = function(e) {
							appendText("Workflow closed connection " + e.reason);
						};
						conn.onmessage = function(e) {
							appendText("Received response from workflow: " + e.data);
							conn.close();
						};
					});
				});
				break;
			case "addtoalfredplaylist":
				if (args[7] == 'starred') {
					starTrackOrAlbum(args);
				} else if (args[8]) {
					addToAlfredPlaylist(args);
				} else if (args[11]) {
					addToAlfredPlaylist(args);
				}
				break;
			case "playtrackwithplaylistcontext":
				playTrackWithPlaylistContext(args);
				break;
			case "addplaylisttoalfredplaylist":
				if (args[10]) {
					addPlaylistToAlfredPlaylist(args);
				} else if (args[9]) {
					if (args[9] == 'starred') {
						starPlaylist(args);
					} else {
						addTopOrStarredListToAlfredPlaylist(args);
					}
				} else if (args[8]) {
					if (args[8] == 'starred') {
						starTopList(args);
					}
				}
				break;
			case "playartistoralbum":
				playArtistOrAlbum(args);
				break;
			case "startplaylist":
				startPlaylist(args);
				break;
			case "clearplaylist":
				// make sure playlist is not starred or top list
				if (args[5]) {
					clearPlaylist(args);
				}
				break;
			}
		}
	}
	/**
	 * starTrackOrAlbum function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function starTrackOrAlbum(args) {
		if (args[2] == 'track') {
			models.Track.fromURI(args[1] + ':' + args[2] + ':' + args[3]).star();
		} else if (args[2] == 'album') {
			models.Album.fromURI(args[1] + ':' + args[2] + ':' + args[3]).load('tracks').done(function(a) {
				// This callback is fired when the album has loaded.
				// The album object has a tracks property, which is a standard array.
				a.tracks.snapshot().done(function(t) {
					var tracks = t.toArray();
					for (i = 0; i < tracks.length; i++) {
						//console.log(t.get(i).name);
						tracks[i].star();
					}
				});
			});
		}
	}
	/**
	 * clearPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function clearPlaylist(args) {
		// Get the playlist object from a URI
		models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5]).load('tracks').done(function(playlist) {
			appendText("clearPlaylist: " + args);
			playlist.tracks.clear();
		});
	}
	/**
	 * addToAlfredPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function addToAlfredPlaylist(args) {
		if (args[2] == 'track') {
			var orginalplaylistUri = args[4] + ':' + args[5] + ':' + args[6] + ':' + args[7] + ':' + args[8];
			var trackUri = args[1] + ':' + args[2] + ':' + args[3];
		} else if (args[2] == 'local') {
			var orginalplaylistUri = args[7] + ':' + args[8] + ':' + args[9] + ':' + args[10] + ':' + args[11];
			var trackUri = args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5] + ':' + args[6];
		} else if (args[2] == 'album') {
			var orginalplaylistUri = args[4] + ':' + args[5] + ':' + args[6] + ':' + args[7] + ':' + args[8];
		}
		// Get the playlist object from a URI
		models.Playlist.fromURI(orginalplaylistUri).load('tracks').done(function(playlist) {
			if (args[2] == 'track' || args[2] == 'local') {
				track = models.Track.fromURI(trackUri);
				playlist.tracks.add(track);
			} else if (args[2] == 'album') {
				models.Album.fromURI(args[1] + ':' + args[2] + ':' + args[3]).load('tracks').done(function(a) {
					// This callback is fired when the album has loaded.
					// The album object has a tracks property, which is a standard array.
					a.tracks.snapshot().done(function(t) {
						var tracks = t.toArray();
						for (i = 0; i < tracks.length; i++) {
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
	/**
	 * starTopList function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function starTopList(args) {
		// Get the playlist object from a URI
		models.Playlist.fromURI(args[5] + ':' + args[6] + ':' + args[7] + ':' + args[8]).load('tracks').done(function(alfredplaylist) {
			if (args[4] == 'toplist') {
				console.log(starTopList + args);
				models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4]).load('tracks').done(function(p) {
					// This callback is fired when the playlist has loaded.
					// The playlist object has a tracks property, which is a standard array.
					p.tracks.snapshot().done(function(t) {
						var tracks = t.toArray();
						for (i = 0; i < tracks.length; i++) {
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
	/**
	 * addTopOrStarredListToAlfredPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function addTopOrStarredListToAlfredPlaylist(args) {
		// Get the playlist object from a URI
		models.Playlist.fromURI(args[5] + ':' + args[6] + ':' + args[7] + ':' + args[8] + ':' + args[9]).load('tracks').done(function(alfredplaylist) {
			if (args[4] == 'toplist' || args[4] == 'starred') {
				models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4]).load('tracks').done(function(p) {
					// This callback is fired when the playlist has loaded.
					// The playlist object has a tracks property, which is a standard array.
					var sorted = p.tracks.sort('addTime', 'desc');
					sorted.snapshot().done(function(t) {
						var tracks = t.toArray();
						for (i = 0; i < tracks.length; i++) {
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
	/**
	 * addPlaylistToAlfredPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function addPlaylistToAlfredPlaylist(args) {
		// Get the playlist object from a URI
		models.Playlist.fromURI(args[6] + ':' + args[7] + ':' + args[8] + ':' + args[9] + ':' + args[10]).load('tracks').done(function(alfredplaylist) {
			if (args[4] == 'playlist') {
				models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5]).load('tracks').done(function(p) {
					// This callback is fired when the playlist has loaded.
					// The playlist object has a tracks property, which is a standard array.
					var sorted = p.tracks.sort('addTime', 'desc');
					sorted.snapshot().done(function(t) {
						var tracks = t.toArray();
						for (i = 0; i < tracks.length; i++) {
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
	/**
	 * starPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function starPlaylist(args) {
		models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5]).load('tracks').done(function(p) {
			// This callback is fired when the playlist has loaded.
			// The playlist object has a tracks property, which is a standard array.
			p.tracks.snapshot().done(function(t) {
				var tracks = t.toArray();
				for (i = 0; i < tracks.length; i++) {
					console.log(t.get(i).name);
					tracks[i].star();
				}
			});
		});
	}
	/**
	 * playArtistOrAlbum function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function playArtistOrAlbum(args) {
		if (args[2] == 'track') {
			var uri = args[1] + ':' + args[2] + ':' + args[3];
		} else if (args[2] == 'artist') {
			var uri = args[1] + ':' + args[2] + ':' + args[3];
		} else if (args[2] == 'album') {
			var uri = args[1] + ':' + args[2] + ':' + args[3];
		} else if (args[2] == 'local') {
			var uri = args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4];
		}
		appendText("playArtistOrAlbum: " + args + " uri " + uri);
		var album = models.Album.fromURI(uri);
		models.player.playContext(album);
	}
	/**
	 * playTrackWithPlaylistContext function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function playTrackWithPlaylistContext(args) {
		if (args[2] == 'track') {
			var orginalplaylistUri = args[4] + ':' + args[5] + ':' + args[6] + ':' + args[7] + ':' + args[8];
			var trackUri = args[1] + ':' + args[2] + ':' + args[3];
		} else if (args[2] == 'local') {
			var orginalplaylistUri = args[7] + ':' + args[8] + ':' + args[9] + ':' + args[10] + ':' + args[11];
			var trackUri = args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5] + ':' + args[6];
		}
		d = new Date();
		var playlistName = "Temp playlist for playTrackWithPlaylistContext " + d.toLocaleTimeString();
		models.Playlist.createTemporary(playlistName).done(function(playlist) {
			playlist.load("tracks").done(function(playlist) {
				// Clearing playlist. If there's a temporary playlist with the
				// same name, previously added tracks may be on the playlist.
				playlist.tracks.clear().done(function(emptyCollection) {
					var orginalplaylist = models.Playlist.fromURI(orginalplaylistUri);
					orginalplaylist.load('name', 'tracks').done(function() {
						var sorted = orginalplaylist.tracks.sort('addTime', 'desc');
						sorted.snapshot().done(function(t) {
							var tracks = t.toArray();
							var tracksNew = [];
							for (var i = 0; i < tracks.length; i++) {
								if (tracks[i].uri != trackUri) {
									tracksNew.push(tracks[i]);
								} else {
									appendText("playTrackWithPlaylistContext: track has been skipped " + args[1] + ':' + args[2] + ':' + args[3]);
								}
							}
							playlist.tracks.add(models.Track.fromURI(trackUri));
							playlist.tracks.add(tracksNew).done(function(addedTracks) {
								playlist.tracks.snapshot().done(function(snapshot) {
									for (var i = 0; i < snapshot.length; i++) {
										appendText("#" + i + " track: " + snapshot.get(i).name);
									}
									appendText("playTrackWithPlaylistContext: track " + trackUri + " playlist " + playlist);
									models.player.playContext(playlist);
								});
							});
						});
					});
				});
			});
		});
	}
	/**
	 * startPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function startPlaylist(args) {
		d = new Date();
		var playlistName = "Temp playlist for startPlaylist " + d.toLocaleTimeString();
		models.Playlist.createTemporary(playlistName).done(function(playlist) {
			playlist.load("tracks").done(function(playlist) {
				// Clearing playlist. If there's a temporary playlist with the
				// same name, previously added tracks may be on the playlist.
				playlist.tracks.clear().done(function(emptyCollection) {
					var orginalplaylist = models.Playlist.fromURI(args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5]);
					orginalplaylist.load('name', 'tracks').done(function() {
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
									appendText("startPlaylist: " + args);
									models.player.playContext(playlist);
								});
							});
						});
					});
				});
			});
		});
	}
	/**
	 * starCurrentTrack function.
	 *
	 * @access public
	 * @return void
	 */

	function starCurrentTrack() {
		var t = {};
		var track = models.player.track;
		if (track != null) {
			models.Track.fromURI(track.uri).star();
			t.name = track.name;
			t.uri = track.uri;
			appendText("starCurrentTrack: " + track.name);
			return t;
		} else {
			appendText("starCurrentTrack: Error cannot get current track " + models.player.track);
		}
		return t;
	}
	/**
	 * unstarCurrentTrack function.
	 *
	 * @access public
	 * @return void
	 */

	function unstarCurrentTrack() {
		var t = {};
		var track = models.player.track;
		if (track != null) {
			models.Track.fromURI(track.uri).unstar();
			t.name = track.name;
			t.uri = track.uri;
			appendText("unstarCurrentTrack: " + track.name);
			return t;
		} else {
			appendText("unstarCurrentTrack: Error cannot get current track " + models.player.track);
		}
		return t;
	}
	/**
	 * playCurrentTrackAlbum function.
	 *
	 * @access public
	 * @return void
	 */

	function playCurrentTrackAlbum() {
		var track = models.player.track;
		if (track != null) {
			var album = track.album;
			appendText("playCurrentTrackAlbum: " + album);
			models.player.playContext(album);
		} else {
			appendText("playCurrentTrackAlbum: Error cannot get current track " + models.player.track);
		}
	}
	/**
	 * playCurrentTrackArtist function.
	 *
	 * @access public
	 * @return void
	 */

	function playCurrentTrackArtist() {
		var track = models.player.track;
		if (track != null) {
			var artists = track.artists;
			if (artists.length > 0) {
				appendText("playCurrentTrackArtist: " + artists[0]);
				models.player.playContext(artists[0]);
			}
		} else {
			appendText("playCurrentTrackArtist: Error cannot get current track " + models.player.track);
		}
	}
	/**
	 * getCurrentTrackArtist function.
	 *
	 * @access public
	 * @return void
	 */

	function getCurrentTrackArtist() {
		var a = {};
		var track = models.player.track;
		if (track != null) {
			var artists = track.artists;
			if (artists.length > 0) {
				a.artist_name = artists[0].name;
				a.artist_uri = artists[0].uri;
				console.log("getCurrentTrackArtist: ", a);
				return a;
			}
		} else {
			appendText("getCurrentTrackArtist: Error cannot get current track " + models.player.track);
		}
		return a;
	}
	/**
	 * addCurrentTrackAlbumToAlfredPlaylist function.
	 *
	 * @access public
	 * @param mixed args
	 * @return void
	 */

	function addCurrentTrackAlbumToAlfredPlaylist(args) {
		var track = models.player.track;
		var orginalplaylistUri = args[1] + ':' + args[2] + ':' + args[3] + ':' + args[4] + ':' + args[5];
		if (track != null) {
			var album = track.album;
			// Get the playlist object from a URI
			models.Playlist.fromURI(orginalplaylistUri).load('tracks').done(function(playlist) {
				models.Album.fromURI(album.uri).load('tracks').done(function(a) {
					// This callback is fired when the album has loaded.
					// The album object has a tracks property, which is a standard array.
					a.tracks.snapshot().done(function(t) {
						var tracks = t.toArray();
						for (i = 0; i < tracks.length; i++) {
							console.log(t.get(i).name);
							playlist.tracks.add(tracks[i]);
						}
						// Verify the song was added to the playlist
						console.log(playlist);
					});
				});
			});
		}
	}
	/**
	 * sleep function.
	 *
	 * @access public
	 * @param mixed milliseconds
	 * @return void
	 */

	function sleep(milliseconds) {
		var start = new Date().getTime();
		for (var i = 0; i < 1e7; i++) {
			if ((new Date().getTime() - start) > milliseconds) {
				break;
			}
		}
	}
	/**
	 * randomTrack function.
	 *
	 * @access public
	 * @return void
	 */

	function randomTrack() {
		// Grab a random track from your library (cause it's more fun)
		Library.forCurrentUser().tracks.snapshot().done(function(snapshot) {
			appendText("randomTrack called");
			models.player.playTrack(snapshot.get(Math.floor(Math.random() * snapshot.length)));
		});
	}
	// Get the currently-playing track
	models.player.load('track').done(updateCurrentTrack);
	// Update the DOM when the song changes
	models.player.addEventListener('change:track', updateCurrentTrack);
	/**
	 * updateCurrentTrack function.
	 *
	 * @access public
	 * @return void
	 */

	function updateCurrentTrack() {
		var currentHTML = document.getElementById('now-playing');
		if (models.player.track == null) {
			currentHTML.innerHTML = 'No track currently playing';
		} else {
			var artists = models.player.track.artists;
			var artists_array = [];
			for (i = 0; i < artists.length; i++) {
				artists_array.push(artists[i].name);
			}
			currentHTML.innerHTML = '' + artists_array.join(', ');
			currentHTML.innerHTML += ' - ' + models.player.track.name;
		}
	}
	/**
	 * getAlbum function.
	 *
	 * @access public
	 * @param mixed objtrack
	 * @param mixed matchedAlbumCallback
	 * @return void
	 */

	function getAlbum(objtrack, matchedAlbumCallback) {
		models.Album.fromURI(objtrack.album_uri).load('name', 'uri').done(function(album) {
			// This callback is fired when the album has loaded.
			// The album object has a tracks property, which is a standard array.
			objtrack.album_name = album.name;
			matchedAlbumCallback(objtrack);
		}).fail(function() {
			console.log("Failed to get album name for " + objtrack.album_uri);
			objtrack.album_name = "";
			matchedAlbumCallback(objtrack);
			return;
		});
	}
	/**
	 * getExternalPlaylistUri function.
	 *
	 * @access public
	 * @param mixed uri
	 * @param mixed username
	 * @return void
	 */

	function getExternalPlaylistUri(uri, username) {
		var playlist_uri = "";
		var words = uri.split(":");
		if (words.length == 5) {
			return words[0] + ":" + words[1] + ":" + username + ":" + words[3] + ":" + words[4];
		} else if (words.length == 4) {
			return words[0] + ":" + words[1] + ":" + username + ":" + words[3];
		}
	}
	/**
	 * getPlaylistTracks function.
	 *
	 * @access public
	 * @param mixed uri
	 * @param mixed matchedPlaylistTracksCallback
	 * @return void
	 */

	function getPlaylistTracks(uri, matchedPlaylistTracksCallback) {
		var array_tracks = [];
		var array_artists = [];
		var array_tmp_tracks = [];
		if (uri == 'spotify:user:@:toplist') {
			var playlist = Library.forCurrentUser().toplist;
		} else {
			var playlist = models.Playlist.fromURI(uri);
		}
		playlist.load('tracks', 'name', 'owner').done(function() {
			appendText("Starting to retrieve all tracks for playlist " + playlist.name);
			playlist.owner.load('name', 'username', 'currentUser').done(function(owner) {
				var sorted = playlist.tracks.sort('addTime', 'desc');
				sorted.snapshot().done(function(snapshot) {
					//check for empty playlists
					if (snapshot.length == 0) {
						p = {};
						p.name = playlist.name;
						p.ownedbyuser = owner.currentUser;
						p.uri = getExternalPlaylistUri(playlist.uri, owner.username);
						p.owner = owner.name;
						p.username = owner.username;
						p.tracks = array_tracks;
						p.artists = array_artists;
						matchedPlaylistTracksCallback(p);
						return;
					}
					snapshot.loadAll('name', 'popularity', 'starred', 'artists', 'availability', 'playable').each(function(track) {
						// workaround for http://stackoverflow.com/questions/20440664/incorrect-snapshot-length-returned-for-a-specific-playlist
						// use tmp array to get the real snapshot length
						array_tmp_tracks.push(track);
					});
					if (array_tmp_tracks.length == 0) {
						p = {};
						p.name = playlist.name;
						p.ownedbyuser = owner.currentUser;
						p.uri = getExternalPlaylistUri(playlist.uri, owner.username);
						p.owner = owner.name;
						p.username = owner.username;
						p.tracks = array_tracks;
						p.artists = array_artists;
						matchedPlaylistTracksCallback(p);
						return;
					}
					for (var i = 0, l = array_tmp_tracks.length; i < l; i++) {
						var t = array_tmp_tracks[i];
						if (t != null) {
							objtrack = {};
							objtrack.playlist_name = playlist.name;
							objtrack.playlist_uri = getExternalPlaylistUri(playlist.uri, owner.username);
							objtrack.name = t.name;
							objtrack.uri = t.uri;
							objtrack.popularity = t.popularity;
							objtrack.starred = t.starred;
							objtrack.artist_name = t.artists[0].name;
							objtrack.artist_uri = t.artists[0].uri;
							objtrack.album_uri = t.album.uri;
							objtrack.availability = t.availability;
							objtrack.playable = t.playable;
							objartist = {};
							objartist.artist_name = t.artists[0].name;
							objartist.artist_uri = t.artists[0].uri;
							if (!checkIfArtistAlreadyThere(array_artists, objartist)) {
								array_artists.push(objartist);
							}
							getAlbum(objtrack, function(matchedAlbum) {
								array_tracks.push(matchedAlbum);
								if (array_tmp_tracks.length == array_tracks.length) {
									appendText("All tracks for playlist " + playlist.name + " have been retrieved");
									p = {};
									p.name = playlist.name;
									p.ownedbyuser = owner.currentUser;
									p.uri = getExternalPlaylistUri(playlist.uri, owner.username);
									p.owner = owner.name;
									p.username = owner.username;
									p.tracks = array_tracks;
									p.artists = array_artists;
									matchedPlaylistTracksCallback(p);
								}
							});
						}
					}
				}).fail(function() {
					appendText("Failed to load tracks for playlist " + uri);
					p = {};
					p.name = playlist.name;
					p.ownedbyuser = owner.currentUser;
					p.uri = getExternalPlaylistUri(playlist.uri, owner.username);;
					p.owner = owner.name;
					p.username = owner.username;
					p.tracks = array_tracks;
					p.artists = array_artists;
					matchedPlaylistTracksCallback(p);
					return;
				});
			}).fail(function() {
				appendText("Failed to load owner of playlist " + uri);
				p = {};
				p.name = playlist.name;
				p.ownedbyuser = false;
				p.uri = playlist.uri;
				p.owner = "unknown";
				p.username = "unknown";
				p.tracks = array_tracks;
				p.artists = array_artists;
				matchedPlaylistTracksCallback(p);
				return;
			});
		}).fail(function() {
			appendText("Failed to load playlist " + uri);
			p = {};
			p.name = playlist.name;
			p.ownedbyuser = false;
			p.uri = playlist.uri;
			p.owner = "unknown";
			p.username = "unknown";
			p.tracks = array_tracks;
			p.artists = array_artists;
			matchedPlaylistTracksCallback(p);
			return;
		});
	}
	/**
	 * checkIfArtistAlreadyThere function.
	 *
	 * @access public
	 * @param mixed array_artists
	 * @param mixed objartist
	 * @return void
	 */

	function checkIfArtistAlreadyThere(array_artists, objartist) {
		for (var i = 0, l = array_artists.length; i < l; i++) {
			var a = array_artists[i];
			if (a.artist_name == objartist.artist_name) {
				return true;
			}
		}
		return false;
	}
	/**
	 * getPlaylists function.
	 *
	 * @access public
	 * @param mixed matchedPlaylistsCallback
	 * @return void
	 */

	function getPlaylists(matchedPlaylistsCallback) {
		var array_results = [];
		// Add starred playlist at the start
		objstarredplaylist = {};
		objstarredplaylist.uri = Library.forCurrentUser().starred.uri;
		array_results.push(objstarredplaylist);
		objtoplistplaylist = {};
		objtoplistplaylist.uri = Library.forCurrentUser().toplist.uri;
		array_results.push(objtoplistplaylist);
		Library.forCurrentUser().playlists.snapshot().done(function(snapshot) {
			for (var i = 0, l = snapshot.length; i < l; i++) {
				var myplaylist = snapshot.get(i);
				if (myplaylist != null) {
					objplaylist = {};
					objplaylist.uri = myplaylist.uri;
					array_results.push(objplaylist);
				}
			}
			matchedPlaylistsCallback(array_results);
			return;
		}).fail(function() {
			appendText("Failed to get playlists for current user");
			matchedPlaylistsCallback(array_results);
		});
	}
	/**
	 * getRelatedArtistsPromise function.
	 *
	 * @access public
	 * @param mixed track
	 * @return void
	 */

	function getRelatedArtistsPromise(artist_name, artist_uri) {
		sleep(10);
		var array_artists = [];
		var array_tmp_artists = [];
		var promise = new models.Promise();
		objartist = {};
		objartist.artist_name = artist_name;
		objartist.artist_uri = artist_uri;
		var uri = objartist.artist_uri;
		var words = uri.split(":");
		if (words[1] == 'local') {
			// this happens when it is from a local track
			appendText("Local artist " + objartist.artist_name + " " + objartist.artist_uri);
			objartist.related = array_artists;
			objartist.biography = "";
			objartist.popularity = 0;
			var array_years = [];
			objartist.years = array_years;
			promise.setDone(objartist);
			return promise;
		}
		models.Artist.fromURI(objartist.artist_uri).load('name', 'related', 'uri', 'biography', 'popularity', 'years', 'albums').done(function(theartist) {
			objartist = {};
			objartist.artist_name = theartist.name;
			objartist.artist_uri = theartist.uri;
			theartist.related.snapshot().done(function(snapshot) {
				if (snapshot.length == 0) {
					objartist.related = array_artists;
					if (theartist.biography != null) {
						objartist.biography = theartist.biography.decodeForText();
					} else {
						objartist.biography = "";
					}
					objartist.popularity = theartist.popularity;
					objartist.years = theartist.years;
					promise.setDone(objartist);
					return promise;
				}
				snapshot.loadAll('name').each(function(artist) {
					// workaround for http://stackoverflow.com/questions/20440664/incorrect-snapshot-length-returned-for-a-specific-playlist
					// use tmp array to get the real snapshot length
					array_tmp_artists.push(artist);
				});
				objartist = {};
				objartist.artist_name = theartist.name;
				objartist.artist_uri = theartist.uri;
				if (array_tmp_artists.length == 0) {
					objartist.related = array_artists;
					if (theartist.biography != null) {
						objartist.biography = theartist.biography.decodeForText();
					} else {
						objartist.biography = "";
					}
					objartist.popularity = theartist.popularity;
					objartist.years = theartist.years;
					promise.setDone(objartist);
					return promise;
				}
				for (var i = 0; i < array_tmp_artists.length; i++) {
					var a = array_tmp_artists[i];
					if (a != null) {
						objrelatedartist = {};
						objrelatedartist.name = a.name;
						objrelatedartist.uri = a.uri;
						array_artists.push(objrelatedartist);
						if (array_tmp_artists.length == array_artists.length) {
							objartist.related = array_artists;
							if (theartist.biography != null) {
								objartist.biography = theartist.biography.decodeForText();
							} else {
								objartist.biography = "";
							}
							objartist.popularity = theartist.popularity;
							objartist.years = theartist.years;
							promise.setDone(objartist);
							return promise;
						}
					}
				}
			}).fail(function() {
				appendText("Failed to get related artists for " + objartist.artist_name);
				objartist.related = array_artists;
				if (theartist.biography != null) {
					objartist.biography = theartist.biography.decodeForText();
				} else {
					objartist.biography = "";
				}
				objartist.popularity = theartist.popularity;
				objartist.years = theartist.years;
				promise.setDone(objartist);
				return promise;
			});
		}).fail(function() {
			// this happens when it is from a local track
			// artist uri is then spotify:local:Damien+Rice
			appendText("Failed to load artists for " + objartist.artist_name + " " + objartist.artist_uri);
			objartist.related = array_artists;
			objartist.biography = "";
			objartist.popularity = 0;
			var array_years = [];
			objartist.years = array_years;
			promise.setDone(objartist);
			return promise;
		});
		return promise;
	}
	/**
	 * getAllRelatedArtists function.
	 *
	 * @access public
	 * @param mixed allplaylists
	 * @param mixed matchedAllRelatedArtistsCallback
	 * @return void
	 */

	function getAllRelatedArtists(allplaylists, matchedAllRelatedArtistsCallback) {
		var array_artists = [];
		var nb_artists = 0;
		var promises = [];
		for (var i = 0, l = allplaylists.length; i < l; i++) {
			var playlist = allplaylists[i];
			for (var j = 0, k = playlist.artists.length; j < k; j++) {
				var a = playlist.artists[j];
				if (!checkIfArtistAlreadyThere(array_artists, a)) {
					array_artists.push(a);
				}
			}
		}
		for (var i = 0, l = array_artists.length; i < l; i++) {
			var a = array_artists[i];
			var promise_artist = getRelatedArtistsPromise(a.artist_name, a.artist_uri);
			promises.push(promise_artist);
			models.Promise.join(promises).done(function(artists) {}).fail(function(artists) {
				console.log('getRelatedArtistsPromise Failed to load at least one artist ', artists);
			}).always(function(artists) {
				if (array_artists.length == artists.length) {
					console.log("All artists have been retrieved", artists);
					matchedAllRelatedArtistsCallback(artists);
				}
			});
		}
	}
	/**
	 * getAllPlaylists function.
	 *
	 * @access public
	 * @param mixed matchedAllCallback
	 * @return void
	 */

	function getAllPlaylists(matchedAllCallback) {
		var array_results = [];
		getPlaylists(function(matchedPlaylists) {
			appendText("The list of the playlists has been retrieved");
			for (var i = 0, l = matchedPlaylists.length; i < l; i++) {
				getPlaylistTracks(matchedPlaylists[i].uri, function(matchedPlaylistTracks) {
					array_results.push(matchedPlaylistTracks);
					if (array_results.length == matchedPlaylists.length) {
						matchedAllCallback(array_results);
					}
				});
			}
		});
	}
	/**
	 * getAll function.
	 *
	 * @access public
	 * @param mixed matchedAll
	 * @return void
	 */

	function getAll(matchedAll) {
		results = {};
		results.user = Library.forCurrentUser().owner;
		var session = models.session;
		session.load("country").done(function() {
			results.country = session.country;
			appendText("Country is set to " + session.country);
			getAllPlaylists(function(matchedAllPlaylists) {
				results.playlists = matchedAllPlaylists;
				appendText("All playlists have been processed");
				appendText("Starting retrieval of all related artists");
				getAllRelatedArtists(results.playlists, function(matchedAllRelatedArtists) {
					results.artists = matchedAllRelatedArtists;
					appendText("Ended retrieval of all related artists. Found " + matchedAllRelatedArtists.length + " results.");
					matchedAll(results);
				});
			});
		});
	}
	/**
	 * appendText function.
	 *
	 * @access public
	 * @param mixed myVar
	 * @return void
	 */

	function appendText(myVar) {
		var myTextArea = document.getElementById('debug_area');
		d = new Date();
		myTextArea.innerHTML += d.toLocaleTimeString() + ": ";
		myTextArea.innerHTML += myVar;
		myTextArea.innerHTML += '\n';
	}

	function getAlbumsForArtist(artist_uri) {
		models.Artist.fromURI(artist_uri).load('albums', 'name').done(function(artist) {
			artist.albums.snapshot().done(function(snapshot) {
				snapshot.loadAll().done(function(albums) {
					var promises = [];
					for (var i = 0; i < albums.length; i++) {
						var albumGroup = albums[i];
						var albums2 = albumGroup.albums;
						for (var j = 0; j < albums2.length; j++) {
							appendText("here " + albums2[j].uri);
							var promise = getAlbumPromise(albums2[j].uri);
							promises.push(promise);
						}
					}
					models.Promise.join(promises).done(function(albums) {
						// appendText('Loaded all albums' + albums);
					}).fail(function(albums) {
						appendText('getAlbumsForArtist ' + artist.name + ' Failed to load at least one albums ', albums);
					}).always(function(albums) {
						console.log('Always.', albums);
						appendText('getAlbumsForArtist ' + artist.name + ' albums ' + albums);
						return albums;
					});
				});
			});
		});
	}

	function getAlbumPromise(album_uri) {
		appendText("getAlbumPromise: " + album_uri);
		var promise = new models.Promise();
		models.Album.fromURI(album_uri).load('name', 'uri').done(function(album) {
			// This callback is fired when the album has loaded.
			// The album object has a tracks property, which is a standard array.
			promise.setDone(album)
		}).fail(function(f) {
			promise.setFail(f);
		});
		return promise;
	}
	$(function() {
		$("#commands a").click(function(e) {
			switch ($(this).attr('command')) {
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