# alfred-spotify-mini-player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a SQL database(it includes your starred tracks, your playlists and playlists that you subscribed to). You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can also lookup for artists online. You can also control Spotify using keywords such as play/next/volmax/random/shuffle/star/etc...

## Features

* Hotkey to trigger the workflow
* Search for Albums, Artists or Tracks
* Search for playlists and launch it
* Browse by Artists, Albums or Playlists
* Launch your Top List
* Alfred Playlist: manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)
* Select a track with ⌥ to play the album, or ⌘ to play the artist
* Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.
* Star current track
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow
* [AlleyOop](http://www.alfredforum.com/topic/1582-alleyoop-update-alfred-workflows/) support

## Settings

* Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Set max number of results. 50 by default
* enable/disable Spotifious or Alfred Playlist
* enable/disable ***Lookup this artist online***
* Install/Update of the library (see next section for explanations)

## Screenshots

![Screenshot](http://d.pr/i/ZcrY+.png)


## First time use

You'll need to do the following steps:

* Sign up for a [developer account on Spotify](https://developer.spotify.com/technologies/apps/#developer-account) (you must allow app API).
* Download the [latest version](https://www.spotify.com/fr/download/mac/) of Spotify and ***install*** it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)

* Open Spotify Mini Player app (it is automatically installed) by invoking *spot_mini* or configured hotkey in Alfred, and select "Open Spotify Mini Player App":

![Screenshot](http://d.pr/i/U3Va+.png)

**Note**: If Spotify cannot open the Spotify Mini Player App, restart Spotify completely. If this still doesn't work, try to logout and login again in Spotify

* Spotify shall open the Mini Player app:

![Screenshot](http://d.pr/i/EOch+.png)

* Click the **BLUE** button to generate JSON data

* Once there is some text in JSON white box, click in the box: all the text will be selected in blue. Copy the text (cmd+C). At this point the JSON library is located in your clipboard.

* Type "spot_mini" or configured hotkey to invoke Spotify Mini Player, and select "Install library" 

![Screenshot](http://d.pr/i/LuC1+.png)

* After some time, you should get a notification saying that library has been created. Note that the first time the library is created, all artworks are downloaded, so it can take a while.



## Library Update

If you modify your playlists, you'll need to update the library.

* Go in Spotify Mini Player Settings

* Select Open Spotify Mini Player App

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

## Credits

* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [PhpFunk](https://github.com/phpfunk/alfred-spotify-controls) 
* [spotify-export](https://github.com/jamesrwhite/spotify-export)
* [kitchensink](https://github.com/ptrwtts/kitchensink)
