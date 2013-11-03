# alfred-spotify-mini-player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!
See related topic on [alfredforum](http://www.alfredforum.com/topic/1892-spotify-mini-player-version-28)

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a SQL database(it includes your starred tracks, your playlists and playlists that you subscribed to). You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can also lookup for artists online. You can also control Spotify using keywords such as play/next/volmax/random/shuffle/star/etc...

## Performances with version 3.x

Using a library with 18000 tracks, search scope set to ALL, with artworks displayed, I get:

* 150ms to return 50 results
* 200ms to return 100 results

Hope you will appreciate the performance bump as I do :-)

See it in action in this [video](https://vimeo.com/70175318) 

## Features

* Hotkey to trigger the workflow
* Search for Albums, Artists or Tracks
* Search for playlists and launch it
* Browse by Artists, Albums or Playlists
* **Lookup artist online** by using ctrl key on a track
* Launch your **Top List**
* **Alfred Playlist** manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)
* Select a track with ⌥ to play the album, or ⌘ to play the artist
* Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.
* Star currently played track with keyword *start*
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow
* [AlleyOop](http://www.alfredforum.com/topic/1582-alleyoop-update-alfred-workflows/) support

## Settings

* Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Set max number of results. 50 by default
* enable/disable Spotifious or Alfred Playlist
* enable/disable ***Lookup this artist online***
* Install/Update of the library (see next section for explanations)

## Screenshots

![Screenshot](http://d.pr/i/7xFz+.png)


## First time use

You'll need to do the following steps:

* Sign up for a [developer account on Spotify](https://developer.spotify.com/technologies/apps/#developer-account) (you must allow app API).
* Download the [latest version](https://www.spotify.com/fr/download/mac/) of Spotify and ***install*** it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)

* Type "spot_mini" or configured hotkey to invoke Spotify Mini Player, and select "Install library" 

![Screenshot](http://d.pr/i/Bsdc+.png)

* After some time, you should get a notification saying that library has been created.

**Note that the first time the library is created, all artworks are downloaded, so it can take a while!**



## Library Update

If you modify your playlists, you'll need to update the library.

* Go in Spotify Mini Player Settings

* Select Update library

* That's it!

## Alfred Playlist

* Create a playlist within Spotify and name it "Alfred Playlist"

* Right click on it and copy Spotify URI

* Enter copied URI in Spotify Mini Player Alfred Playlist section

* To add a track to your playlist select it with *fn* modifier

* To add an album or another playlist to your playlist select it with *shift* modifier

## Download the workflow

Download the workflow below and open in Alfred.

[Download Workflow](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/SpotifyMiniPlayer.alfredworkflow)

## Troubleshooting

If you experience an issue with the workflow, use the "spot_mini_debug" command, it will generate a spot_mini_debug.tgz file in your Downloads directory. Then send this file to me.

![Screenshot](http://d.pr/i/4zSE+.png)


## Credits

* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [Ratchet](http://socketo.me)


