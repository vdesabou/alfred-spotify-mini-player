# Alfred Spotify Mini Player


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

## Download the workflow on Packal

[![Download Workflow](https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/images/alfred-workflow-icon.png)](http://www.packal.org/workflow/spotify-mini-player)

## Description

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a database(it includes everything inside *Your Music* and all the playlists you created and playlists that you're subscribed to). You can decide to search in *Your Music* only, or your complete library (all playlists and *Your Music*). You can browse by Artist, Album or Playlist. You can also lookup for artists online, search for lyrics, get related artists, get featured playlists etc..
You can also control Spotify using keywords such as play/next/volmax/random/shuffle/etc...

## Screencast

See it in action here [screencast](http://cl.ly/YNyq)

## Animated Gifs


![Screenshot](http://cl.ly/image/210g0k1M3w0i/Screen%20Recording%202014-11-05%20at%2007.49%20PM.gif)

* Browse and launch your playlists

![Screenshot](https://i.cloudup.com/v5zZ3SKm-U.gif)



## Features

* Update of playlists directly from the workflow!
* Search for Albums, Artists or Tracks and Playlists, browse them and launch them
* **Lookup artist online** by using ctrl key on a track
* **Alfred Playlist** manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)
* Select a track with ⌥ to play the album, or ⌘ to play the artist
* Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.
* Display information currently played track with keyword *current*
* Add current track to Alfred Playlist or Your Music
* Browse Related artists
* Get featured playlists from Spotify in your country, US and UK
* Display current track lyrics
* Three themes available: black, green and new theme from Spotify
* Auto-Updater: it checks once per day and download automatically the new version
* Direct call to [Spotifious](http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred) workflow

## Settings

* Configurable Search Scope: Only in *Your Music* or in your complete library, including *Your Music* and all playlists (by default)
* Set max number of results. 50 by default
* enable/disable Spotifious
* Set controller to Alfred Playlist or Your Music. 
* enable/disable ***Lookup this artist online***
* Check for workflow update
* Choose workflow theme: ***black*** or ***green*** or ***new***
* Install/Update of the library (see next section for explanations)

## First time use

You'll need to do the following steps:

* Create an [Application on Spotify](https://developer.spotify.com/my-applications) (this is for both free and premium users)
    * You can set Application Name and Description to whatever you want
    * ***Redirect URI must be set to*** *http://localhost:15298/callback.php*

![Example](http://cl.ly/image/0h2F1z232Q2p/Capture_d%E2%80%99e%CC%81cran_2014-11-04_a%CC%80_11_13_50.png)


* Invoke the workflow and follow the steps

* After some time, you should get a notification saying that library has been created.

**Note that the first time the library is created, all artworks are downloaded, so i takes quite some time. But don't worry next updates are very quick!**

You can check progress by invoking the workflow again:-

![Screenshot](https://i.cloudup.com/NajHMexvb7.png)


## Library/Playlist Update

You can now update your entire library and/or only one specific playlist directly from the Spotify Mini Player!

For example:

![Screenshot](https://i.cloudup.com/QER69TlaZa.png)

## Alfred Playlist

The Alfred Playlist is one of your playlists where tracks, albums and even playlists can be added from within the workflow.

* Simply choose one of your playlists as the current "Alfred Playlist" (you can even choose your starred playlist)

![Screenshot](https://i.cloudup.com/CkEfC9emQI.png)

* To add a track to your playlist select it with *fn* modifier

![Screenshot](https://i.cloudup.com/O9I8lqCvl3.png)

* To add an album or another playlist to your playlist select it with *shift* modifier

![Screenshot](https://i.cloudup.com/I6zvg23d39.png)


* You can also clear all tracks from your Alfred Playlist (for security, you cannot do it if your starred playlist is the Alfred Playlist), be careful when doing it. 

A confirmation will be asked:

![Screenshot](https://i.cloudup.com/y6qlZZaGaK.png)


## Now Playing

This is an option to diplay various options based on the current track (play/pause, browse artist, display lyrics, show playlists where the track belongs)

![Screenshot](https://i.cloudup.com/PZrDLRlLwb.png)

## Commands

* Modifer keys

![Screenshot](https://i.cloudup.com/8TSup8JB2l.png)

* Other commands

Note: These commands can also be triggered from the main workflow window

![Screenshot](https://i.cloudup.com/nGQOVeLbdr.png)


## Auto-Update

The workflow checks for update once per day, if an update is available, it will download the new version in your Downloads folder.

Note that you can also force a check for update in Settings section

## Troubleshooting

If you experience an issue with the workflow, use the "spot_mini_debug" command, it will generate a spot_mini_debug.tgz file in your Downloads directory. Then send this file to me.

![Screenshot](https://i.cloudup.com/rkqaeTehJK.png)

If the update library is stuck (be aware the first time, it can take hours as all artworks are downloaded, next times it shall not exceed 10 minutes), you can kill it by invoking "spot_mini_kill" command:

![Screenshot](https://i.cloudup.com/rusuJc78Wg.png)

If you want to report an issue or propose an enhancement, use the "spot_mini_issue" command.

## Credits

* [jwilsson/spotify-web-api-php](https://github.com/jwilsson/spotify-web-api-php)
* [SpotCommander](https://github.com/olejon/spotcommander)
* [Terminal-Notifier](https://github.com/alloy/terminal-notifier)
* [Spotifious workflow](https://github.com/citelao/Spotify-for-Alfred)




