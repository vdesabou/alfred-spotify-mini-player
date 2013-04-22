alfred-spotify-mini-player
==========================

This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Description

It is based on the fabulous work done for Spotifious Workflow, but has a different approach: instead of interrogating the complete Spotify Catalog (which tends to be quite slow due to Metadata Api), it only search in the user playlists (either "starred" playlist only or all playlists, this is configurable).

This allows the workflow to be responsive (which is the strength of the alfred built-in iTunes mini player) because the lookup is done locally and all artworks are cached.


## Screenshots

![Screenshot](http://d.pr/i/J49m+.png)



## Features
* Hotkey to trigger the workflow
* Direct call to Spotifious workflow
* Insta-search (just start typing at least 3 characters)
* Search for Albums, Artists or Tracks
* Search for playlists and Start it
* Browse by Artists, Albums
* Browse by Playlists
* Select a track with "alt" to play the album, or "cmd" to play the artist in Spotify
* Same control keywords as iTunes Mini Player: play, pause, mute, next, previous, volmax, volmid. And shuffle to activate shuffling.
* Settings: Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Settings: Set max number of results
* Settings: enable/disable Spotifious
* Settings: Cache all artworks at once. This is recommended to be done before you start using the workflow. Artworks are downloaded on the fly, but it  is better to cache everything for performances reasons
* Settings: Clear Artworks cache. Not sure why you would use it, but it's possible to do it
* Settings: Install/Update of the library (see next section for explanations)
* AlleyOop 2.0 support

## Library Installation

This workflow does not use the Spotify Metadata API, it uses instead a local copy of your Spotify library.
This library is a "JSON" file. In order to get a json version of your library, you need to to use the Spotify App [spotify-export](https://github.com/jamesrwhite/spotify-export).

You'll need to do the following steps:

1. Sign up for a developer account on Spotify
2. mkdir -p ~/Spotify
3. cd ~/Spotify
4. git clone git://github.com/jamesrwhite/spotify-export.git
5. ln -s ~/Spotify/spotify-export/export ~/Spotify
At this point, it should looks like:

![Screenshot](http://d.pr/i/lGwN+.png)


6. Download the latest version of Spotify and ***install*** it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)
7. Open Spotify and type *spotify:app:export* in the search bar (restart Spotify completely in case it doesn't find the App at first) If this still doesn't work, try to logout and login again in Spotify
8. After a brief bit of loading you should see something like this:

![Screenshot](http://d.pr/i/u9x1+.png)

9. Click in the JSON white box: all the text will be selected in blue. Copy the text (cmd+C). At this point the JSON library is located in your clipboard.
Type "spot_mini" to invoke Spotify Mini Player, and go in Settings section and select "Install or Update library for Spotify Mini Player" (this will paste the content of the clipboard, so the JSON library into a file called library.json in the app data directory : */Users/YOUR_USER/Library/Application Support/Alfred 2/Workflow Data/com.vdesabou.spotify.mini.player*)

## Library Update

If you modify your playlists, you'll need to update the library (it takes 30 seconds). You need to do steps 7 to 10 of the "Library Installation" section

## First time use

As explained above, the first time you'll run the workflow, the JSON library would not be present, so follow section "Library Installation"
I strongly recommend to use the setting "Cache all artworks for Spotify Mini Player" the first time. It can take a while to download all you artworks, so have a break and come back later :-)


## Download the workflow

Download the workflow below and open in Alfred.

[![Download Workflow](http://d.pr/i/L4IL+.png)](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/SpotifyMiniPlayer.alfredworkflow)


## History

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

*Fixes and improvements to playlists

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

## Credits

* Citelao and his fabulous [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [PhpFunk](https://github.com/phpfunk/alfred-spotify-controls) 
* [spotify-export](https://github.com/jamesrwhite/spotify-export)
