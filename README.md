# Alfred Spotify Mini Player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a database(it includes all the playlists you created and playlists that you're subscribed to). You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can also lookup for artists online, search for lyrics, get related artists, display biography, etc..
You can also control Spotify using keywords such as play/next/volmax/random/shuffle/star/etc...

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
* Display artist bigraphy
* Browse Related artists
* Display current track lyrics
* Two themes available: black and green
* Auto-Updater: it checks once per day and download automatically the new version
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow

## Settings

* Configurable Search Scope: Only Starred playlist (by default) or All your playlists
* Set max number of results. 50 by default
* enable/disable Spotifious or Alfred Playlist
* enable/disable ***Lookup this artist online***
* Check for workflow update
* Choose workflow theme: ***black*** or ***green***
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

![Screenshot](http://d.pr/i/5wQt+.png)


## Library/Playlist Update

You can now update your entire library and/or only one specific playlist directly from the Spotify Mini Player!

For example:

![Screenshot](http://d.pr/i/TcpN+.png)

## Alfred Playlist

The Alfred Playlist is one of your playlists where tracks, albums and even playlists can be added from within the workflow.

* Simply choose one of your playlists as the current "Alfred Playlist" (you can even choose your starred playlist)

![Screenshot](http://d.pr/i/1lVo+.png)

* To add a track to your playlist select it with *fn* modifier

![Screenshot](http://d.pr/i/9TZ0+.png)

* To add an album or another playlist to your playlist select it with *shift* modifier

![Screenshot](http://d.pr/i/Zmow+.png)

![Screenshot](http://d.pr/i/nJGw+.png)

* You can also clear all tracks from your Alfred Playlist (for security, you cannot do it if your starred playlist is the Alfred Playlist), be careful when doing it. 

A confirmation will be asked:

![Screenshot](http://d.pr/i/xUFE+.png)


## Now Playing

This is an option to diplay various options based on the current track (play/pause, browse artist, display lyrics, show playlists where the track belongs)

![Screenshot](http://d.pr/i/ldgN+.png)

## Commands

* Modifer keys

![Screenshot](http://d.pr/i/RBqX+.png)

* Other commands

Note: These commands can also be triggered from the main workflow window

![Screenshot](http://d.pr/i/DVSn+.png)


## Auto-Update

The workflow checks for update once per day, if an update is available, it will download the new version in your Downloads folder and you'll get:

![Screenshot](http://d.pr/i/yLGp+.png)

Note that you can also force a check for update in Settings section


## Troubleshooting

If you experience an issue with the workflow, use the "spot_mini_debug" command, it will generate a spot_mini_debug.tgz file in your Downloads directory. Then send this file to me.

![Screenshot](http://d.pr/i/4zSE+.png)

If the update library is stuck (be aware the first time, it can take hours as all artworks are downloaded, next times it shall not exceed 10 minutes), you can kill it by invoking "spot_mini_kill" command:

![Screenshot](http://d.pr/i/q8Rs+.png)

If you want to report an issue or propose an enhancement, use the "spot_mini_issue" command.

## Credits

* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)
* [Ratchet](http://socketo.me)
* [SpotCommander](https://github.com/olejon/spotcommander)
* [Terminal-Notifier](https://github.com/alloy/terminal-notifier)


## Download the workflow

Download the workflow below and open in Alfred.

[![Download Workflow](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/images/alfred-workflow-icon.png)](https://github.com/packal/repository/raw/master/com.vdesabou.spotify.mini.player/spotifyminiplayer.alfredworkflow)

