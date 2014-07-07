[size=7][b]Alfred Spotify Mini Player[/b][/size]


This is "Spotify Mini Player", like the alfred built-in iTunes Mini Player, but for Spotify!

[size=6][b]Description[/b][/size]

Speed is the key word here: instead of using slow Spotify API, it uses a local version of your library stored in a database(it includes all the playlists you created and playlists that you're subscribed to). You can decide to search in your starred playlist only, or in all all your playlists. You can browse by Artist, Album or Playlist. You can also lookup for artists online, search for lyrics, get related artists, display biography, etc..
You can also control Spotify using keywords such as play/next/volmax/random/shuffle/star/unstar/etc...

[size=6][b]Screencast[/b][/size]

See it in action here [url=http://quick.as/nmwxcxx0]screencast[/url]

[size=6][b]Animated Gifs[/b][/size]

[list]
[*]Search in all your playlists[/*]
[/list]

[img=https://i.cloudup.com/pKTGG2faiC.gif]

[list]
[*]Browse and launch your playlists[/*]
[/list]

[img=https://i.cloudup.com/A9tePlpX1S.gif]

[list]
[*]Update your playlist and see progress[/*]
[/list]

[img=https://i.cloudup.com/tx8lqblTEu.gif]


[list]
[*]Lookup artist "online"[/*]
[/list]

[img=https://i.cloudup.com/zbv6NCInTi.gif]

[size=6][b]Features[/b][/size]

[list]
[*]Hotkey to trigger the workflow[/*]
[*]Update of playlists directly from the workflow![/*]
[*]Search for Albums, Artists or Tracks[/*]
[*]Search for playlists (including your Top List), browse them and launch them[/*]
[*]Browse by Artists, Albums or Playlists[/*]
[*][b]Lookup artist online[/b] by using ctrl key on a track[/*]
[*][b]Alfred Playlist[/b] manage a playlist from Alfred: add track (using fn), albums or playlist (using ⇧)[/*]
[*]Select a track with ⌥ to play the album, or ⌘ to play the artist[/*]
[*]Same control keywords as iTunes Mini Player: play, pause, mute, next, random, previous, volmax, volmid. And shuffle to activate shuffling.[/*]
[*]Star/Unstar currently played track with keyword [i]star[/i]/[i]unstar[/i][/*]
[*]Add current track to Alfred Playlist[/*]
[*]Display artist bigraphy[/*]
[*]Browse Related artists[/*]
[*]Display current track lyrics[/*]
[*]Two themes available: black and green[/*]
[*]Auto-Updater: it checks once per day and download automatically the new version[/*]
[*]Direct call to [url=http://www.alfredforum.com/topic/1644-spotifious-a-natural-spotify-controller-for-alfred]Spotifious[/url] workflow[/*]
[/list]

[size=6][b]Settings[/b][/size]

[list]
[*]Configurable Search Scope: Only Starred playlist (by default) or All your playlists[/*]
[*]Set max number of results. 50 by default[/*]
[*]enable/disable Spotifious or Alfred Playlist[/*]
[*]enable/disable [b][i]Lookup this artist online[/i][/b][/*]
[*]Check for workflow update[/*]
[*]Choose workflow theme: [b][i]black[/i][/b] or [b][i]green[/i][/b][/*]
[*]Install/Update of the library (see next section for explanations)[/*]
[/list]

[size=6][b]First time use[/b][/size]

You'll need to do the following steps:

[list]
[*]Sign up for a [url=https://devaccount.spotify.com/my-account/]developer account on Spotify[/url] (this is for both free and premium users).[/*]
[/list]

[list]
[*]Download the [url=https://www.spotify.com/fr/download/mac/]latest version[/url] of Spotify and [b][i]install[/i][/b] it (I had to do it, even though I was pretty sure to already have the latest version, so please do it!)[/*]
[/list]

[list]
[*]Type "spot_mini" or configured hotkey to invoke Spotify Mini Player, and select "1/ Open Spotify Mini Player App <spotify:app:miniplayer>" [/*]
[/list]

[img=https://i.cloudup.com/QVFwkPR7V7.png]

[list]
[*]If it doesn't work (Spotify indicates "Failed to load application miniplayer."):[/*]
[/list]

  * try to restart Spotify multiple times
  
  * try to logout from Spotify and login again
  
  * make sure you see in [url=https://devaccount.spotify.com/my-account/]Developer Account[/url]:

[code=auto:0]  
You are successfully registered with us as a Spotify apps developer.
[/code]

[list]
[*]If it works, invoke the Spotify Mini Player workflow again and select "2/ Install library"[/*]
[/list]

[list]
[*]After some time, you should get a notification saying that library has been created.[/*]
[/list]

[b]Note that the first time the library is created, all artworks are downloaded, so it can take a while![/b]

You can check progress by invoking the workflow again:-

[img=https://i.cloudup.com/NajHMexvb7.png]


[size=6][b]Library/Playlist Update[/b][/size]

You can now update your entire library and/or only one specific playlist directly from the Spotify Mini Player!

For example:

[img=https://i.cloudup.com/QER69TlaZa.png]

[size=6][b]Alfred Playlist[/b][/size]

The Alfred Playlist is one of your playlists where tracks, albums and even playlists can be added from within the workflow.

[list]
[*]Simply choose one of your playlists as the current "Alfred Playlist" (you can even choose your starred playlist)[/*]
[/list]

[img=https://i.cloudup.com/CkEfC9emQI.png]

[list]
[*]To add a track to your playlist select it with [i]fn[/i] modifier[/*]
[/list]

[img=https://i.cloudup.com/O9I8lqCvl3.png]

[list]
[*]To add an album or another playlist to your playlist select it with [i]shift[/i] modifier[/*]
[/list]

[img=https://i.cloudup.com/I6zvg23d39.png]


[list]
[*]You can also clear all tracks from your Alfred Playlist (for security, you cannot do it if your starred playlist is the Alfred Playlist), be careful when doing it. [/*]
[/list]

A confirmation will be asked:

[img=https://i.cloudup.com/y6qlZZaGaK.png]


[size=6][b]Now Playing[/b][/size]

This is an option to diplay various options based on the current track (play/pause, browse artist, display lyrics, show playlists where the track belongs)

[img=https://i.cloudup.com/PZrDLRlLwb.png]

[size=6][b]Commands[/b][/size]

[list]
[*]Modifer keys[/*]
[/list]

[img=https://i.cloudup.com/8TSup8JB2l.png]

[list]
[*]Other commands[/*]
[/list]

Note: These commands can also be triggered from the main workflow window

[img=https://i.cloudup.com/nGQOVeLbdr.png]


[size=6][b]Auto-Update[/b][/size]

The workflow checks for update once per day, if an update is available, it will download the new version in your Downloads folder.

Note that you can also force a check for update in Settings section


[size=6][b]Troubleshooting[/b][/size]

If you experience an issue with the workflow, use the "spot_mini_debug" command, it will generate a spot_mini_debug.tgz file in your Downloads directory. Then send this file to me.

[img=https://i.cloudup.com/rkqaeTehJK.png]

If the update library is stuck (be aware the first time, it can take hours as all artworks are downloaded, next times it shall not exceed 10 minutes), you can kill it by invoking "spot_mini_kill" command:

[img=https://i.cloudup.com/rusuJc78Wg.png]

If you want to report an issue or propose an enhancement, use the "spot_mini_issue" command.

[size=6][b]Credits[/b][/size]

[list]
[*][url=https://github.com/citelao/Spotify-for-Alfred]Spotifious workflow[/url][/*]
[*][url=http://socketo.me]Ratchet[/url][/*]
[*][url=https://github.com/olejon/spotcommander]SpotCommander[/url][/*]
[*][url=https://github.com/alloy/terminal-notifier]Terminal-Notifier[/url][/*]
[/list]


[size=6][b]Download the workflow[/b][/size]

Download the workflow below and open in Alfred.

[url=https://raw.githubusercontent.com/packal/repository/master/com.vdesabou.spotify.mini.player/spotifyminiplayer.alfredworkflow][img=https://raw.github.com/vdesabou/alfred-spotify-mini-player/master/images/alfred-workflow-icon.png][/url]

