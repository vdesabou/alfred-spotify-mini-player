# alfred-spotify-mini-player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a JSON library file representing the user library, including subscribed playlists. You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can control Spotify using keywords such as play/next/volmax/random/shuffle/star/etc...

## Features

* Hotkey to trigger the workflow
* Insta-search (just start typing at least 3 characters)
* Search for Albums, Artists or Tracks
* Search for playlists and Start it
* Browse by Artists, Albums or Playlists
* Alfred Playlist: manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)
* Select a track with ⌥ to play the album, or ⌘ to play the artist
* Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.
* Star current track
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow
* [AlleyOop](http://www.alfredforum.com/topic/1582-alleyoop-update-alfred-workflows/) support

## Settings

* Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Set max number of results
* enable/disable Spotifious or Alfred Playlist
* Cache all artworks at once. 
This is recommended to be done before you start using the workflow. Artworks are downloaded on the fly, but it is better to cache everything for performances reasons
* Clear Artworks cache. Not sure why you would use it, but it's possible to do it
* Install/Update of the library (see next section for explanations)

## Screenshots

![Screenshot](http://d.pr/i/gFYc+.png)


## First time use

You'll need to do the following steps:

* Sign up for a [developer account on Spotify](https://developer.spotify.com/technologies/apps/#developer-account)
* Download the [latest version](https://www.spotify.com/fr/download/mac/) of Spotify and ***install*** it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)

* Open Spotify Mini Player app (it is automatically installed) by invoking *spot_mini* or configured hotkey in Alfred, and select "Open Spotify Mini Player App":

![Screenshot](http://d.pr/i/GH1O+.png)

**Note**: If Spotify cannot open the Spotify Mini Player App, restart Spotify completely. If this still doesn't work, try to logout and login again in Spotify

* Spotify shall open the Mini Player app:

![Screenshot](http://d.pr/i/EOch+.png)

* Click the **BLUE** button to generate JSON data

* Once there is some text in JSON white box, click in the box: all the text will be selected in blue. Copy the text (cmd+C). At this point the JSON library is located in your clipboard.

* Type "spot_mini" or configured hotkey to invoke Spotify Mini Player, and select "Install library" 

![Screenshot](http://d.pr/i/wF47+.png)

* Technically speaking, it will paste the content of the clipboard, so the JSON library into a file called library.json in the app data directory : */Users/YOUR_USER/Library/Application Support/Alfred 2/Workflow Data/com.vdesabou.spotify.mini.player. All After some time, you should get a notification saying that library has been created

* I strongly recommend to use the setting "Cache all artworks for Spotify Mini Player" the first time. It can take a while to download all you artworks, so have a break and come back later :-). You'll get a notification when all artworks are cached.

![Screenshot](http://d.pr/i/Jris+.png)



## Library Update

If you modify your playlists, you'll need to update the library.

* Go in Spotify Mini Player Settings

![Screenshot](http://d.pr/i/kET3+.png)

* Select Open Spotify Mini Player App

![Screenshot](http://d.pr/i/8CD+.png)

* Generate JSON Data by pressing blue button

* Copy JSON data in your clipboard

* Select Update library

* That's it!

## Alfred Playlist

* Create a playlist within Spotify and name it "Alfred Playlist"

* Right click on it and copy Spotify URI

* Enter copied URI in Spotify Mini Player Alfred Playlist section

* To add a track to your playlist select it with *fn* modifier

* To add an album to your playlist select it with *shift* modifier

## Download the workflow

Download the workflow below and open in Alfred.

[Download Workflow](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/SpotifyMiniPlayer.alfredworkflow)


## History

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

## Credits

* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [PhpFunk](https://github.com/phpfunk/alfred-spotify-controls) 
* [spotify-export](https://github.com/jamesrwhite/spotify-export)
* [kitchensink](https://github.com/ptrwtts/kitchensink)
