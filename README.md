# alfred-spotify-mini-player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a database(it includes your starred tracks, your playlists and playlists that you subscribed to). You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can also lookup for artists online. You can also control Spotify using keywords such as play/next/volmax/random/shuffle/star/etc...

## Animated Gifs

* Search in all your playlists

![Screenshot](http://d.pr/i/MeWd+.gif)

* Browse and launch your playlists

![Screenshot](http://d.pr/i/ECCf+.gif)

* Update your playlist and see progress

![Screenshot](http://d.pr/i/uSB4+.gif)

* Browse by artist

![Screenshot](http://d.pr/i/QJU1+.gif)

* Lookup artist "online"

![Screenshot](http://d.pr/i/y1Wb+.gif)

## Features

* Hotkey to trigger the workflow
* Update of playlists directly from the workflow!
* Search for Albums, Artists or Tracks
* Search for playlists (including your Top List), browse them and launch them
* Browse by Artists, Albums or Playlists
* **Lookup artist online** by using ctrl key on a track
* **Alfred Playlist** manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)
* Select a track with ⌥ to play the album, or ⌘ to play the artist
* Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.
* Star currently played track with keyword *star*
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow
* [AlleyOop/Monkey Patch](http://www.alfredforum.com/topic/2218-monkey-patch-update-alfred-workflows-via-alleyoop) support

## Settings

* Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Set max number of results. 50 by default
* enable/disable Spotifious or Alfred Playlist
* enable/disable ***Lookup this artist online***
* Install/Update of the library (see next section for explanations)

## First time use

You'll need to do the following steps:

* Sign up for a [developer account on Spotify](https://developer.spotify.com/technologies/apps/#developer-account) (you must allow app API).

* Download the [latest version](https://www.spotify.com/fr/download/mac/) of Spotify and ***install*** it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)

* Type "spot_mini" or configured hotkey to invoke Spotify Mini Player, and select "1/ Open Spotify Mini Player App <spotify:app:miniplayer>" 

![Screenshot](http://d.pr/i/ssf0+.png)

* If it doesn't work (Spotify indicates "Failed to load application miniplayer."):

  * try to restart Spotify multiple times
  
  * try to logout from Spotify and login again
  
  * make sure you see in [Developer Account](https://developer.spotify.com/technologies/apps/):

```  
Developer Account
Your account has already been enabled to use the Spotify Apps API. Happy hacking!
```

* If it works, invoke the Spotify Mini Player workflow again and select "2/ Install library"

* After some time, you should get a notification saying that library has been created.

**Note that the first time the library is created, all artworks are downloaded, so it can take a while!**

You can check progress by invoking the workflow again:-

![Screenshot](http://d.pr/i/8yDg+.png)


## Library/Playlist Update

You can now update your entire library and/or only one specific playlist directly from the Spotify Mini Player!

For example:

![Screenshot](http://d.pr/i/TcpN+.png)

## Alfred Playlist

The Alfred Playlist is a playlist where tracks, albums and even playlists can be added from within the workflow.

* Create a playlist within Spotify and name it "Alfred Playlist"

![Screenshot](http://d.pr/i/A0vQ+.png)

* Right click on it and copy Spotify URI

![Screenshot](http://d.pr/i/BjPA+.png)

* Enter copied URI in Spotify Mini Player Alfred Playlist section

![Screenshot](http://d.pr/i/aWFZ+.png)

* To add a track to your playlist select it with *fn* modifier

![Screenshot](http://d.pr/i/9TZ0+.png)

* To add an album or another playlist to your playlist select it with *shift* modifier

![Screenshot](http://d.pr/i/Zmow+.png)

![Screenshot](http://d.pr/i/nJGw+.png)


## Commands

* Modifer keys

![Screenshot](http://d.pr/i/RBqX+.png)

* Other commands

![Screenshot](http://d.pr/i/DVSn+.png)



## Troubleshooting

If you experience an issue with the workflow, use the "spot_mini_debug" command, it will generate a spot_mini_debug.tgz file in your Downloads directory. Then send this file to me.

![Screenshot](http://d.pr/i/4zSE+.png)

If the update library is stuck (be aware the first time, it can take hours as all artworks are downloaded, next times it shall not exceed 10 minutes), you can kill it by invoking "spot_mini_kill" command:

![Screenshot](http://d.pr/i/q8Rs+.png)


## Performances with version 3.x

Using a library with 18000 tracks, search scope set to ALL, with artworks displayed, I get:

* 150ms to return 50 results
* 200ms to return 100 results

## Credits

* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [Ratchet](http://socketo.me)
* [SpotCommander](https://github.com/olejon/spotcommander)
* [Terminal-Notifier](https://github.com/alloy/terminal-notifier)


## Download the workflow

Download the workflow below and open in Alfred.

[![Download Workflow](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/images/alfred-workflow-icon.png)](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/SpotifyMiniPlayer.alfredworkflow)

