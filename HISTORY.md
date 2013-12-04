## History

3.82:

* Reworked failure detection for first time use

3.8:

* Added playlists in now playing section

3.7.5:

* Fix: Browse by Artist and by Album is very slow for big libraries (>30K tracks)

3.7.4:

* Fixed output for update playlist and update playlists list

3.7.3:

* Fixed problem when antislash is in album,track,artist name

3.7.2:

* Minor Fixes.

3.7:

* Top list is now a playlist like any others. 

* Now check if track is playable before displaying it

3.6:

* Added Update Playlist list(use it when you have added or removed a playlist). Various fixes.

3.5:

* Added artworks to playlists! Please update your library to enjoy this new feature

3.4:

* Some fixes and improvements on Playlists

3.3:

* Added progress indicator during update of library and playlists

* Added possibility to update playlists directly from the workflow!

3.2:

* No more need to copy JSON data! Just click on "Update library"

* Added spot_mini_kill command to kill update library if it is stuck

3.1:

* Moving to spotify API 1.x: no more 200 tracks limitation on playlists

* Added spot_mini_debug command to help troubleshooting

* Improved robustness and error detections

3.0:

* Major update!!

* Major speed improvements. Using a library with 18000 tracks, search scope set to ALL, with artworks displayed, it takes 150ms to return 50 results

* Launch your top list

* Online mode: Lookup for artist online and then browse all albums and tracks

* Many more improvements


2.8:

* New icons

* Option to enable/disable artworks

* Option to enable/disable ***More from this artist/album***


2.7:

* Attempt to better detect problems with spotify-app-miniplayer app

2.6:

* Fix issue during library creation where nothing happened

2.5:

* Quick access to menus, for example start typing setting and Settings menu will be selectioned

* Add a playlist to Alfred Playlist using ⇧ modifier

2.4:

* Introducing Alfred Playlist: control a playlist from Alfred. Add Track with *fn* or Album with *shift* to the playlist, browse it or clear it from Alfred.

2.3:

* Using own Spotify app <spotify:app:miniplayer>. Nore more need to do manual install, this is automatically done. Using this allows more control and less hacking to make it work
* New *random* command, it will launch a random track from any of your playlists
* New *star* command, it will star the current track 

2.2:

* Move code to [GitHub](https://github.com/vdesabou/alfred-spotify-mini-player)
* Fix artworks not cached when playlist is not from the user

2.1:

* Display user for playlists
* Added settings to launch spotify:app:export app

2.0:

* Automatic support of playlists (including starred playlist)
* Setting to disable spotifious
* alt and cmd modifiers now open and play music

1.15:

* Fix for very large libraries

1.14:

* Fixes and improvements to playlists

1.13:

* Built-in support of playlists!

1.12:

* Updated (again) allocated memory to 256M
* Check json data is valid when creating/updating library
* minor fixes

1.11:

* Updated allocated memory to 128M.

1.10:

* Fix memory issue when library.json file size is big.
* New icons.

1.9:

* Do not fetch online artworks for current track(for performance reasons).
* Minor bug fixes.

1.8:

* Added same keywords as iTunes Mini Player: play, pause, mute, next, previous, volmax, volmid. And shuffle to activate shuffling.

1.7:

* Performance improvement when using Starred Playlist only Search Scope (only a subset of library.json is loaded)

1.6:

* Fix for duplicate tracks in results.
* Better handling of UTF-8 characters.

1.5:

* Display current track information.
* Select current track to play/pause the track
* Added More from this Artist and More from this album

1.4:

* Display a default artwork when not available.
* Fix Search playlist
* Add check that Max Results is a number and greater than 0

1.3:

* Added Browse by Playlists (if playlists.json is configured)
* Added configuration for Max number of results
* Code cleaning (using now workflows class from David Fergusson, awesome!)
* Added default result to search with Spotify

1.2:

* Fixed issue when browsing by Artist and by Album

1.1:

* library.json, playlists.json and artwork cache are now in the app data directory (/Users/YOUR_USER/Library/Application Support/Alfred 2/Workflow Data/com.vdesabou.spotify.mini.player). The workflow can now be updated without loosing cached artworks, playlists and library.

1.0:

* Initial Version

