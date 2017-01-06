---
layout: article
permalink: /release-notes/
title: "Release Notes"
modified: 2016-11-11
excerpt: "Release Notes."
image:
  feature:
  teaser:
  thumb:
share: false
toc: false
noindex: false
---

<a name="v6.6"></a>
6.6:

* <span class="badge danger">FIXED</span> Launching playlist when Spotify app is not opened[#147](https://github.com/vdesabou/alfred-spotify-mini-player/issues/147)


<a name="v6.5"></a>
6.5:

* <span class="badge info">ADDED</span> Add option to allow to download a green theme [#145](https://github.com/vdesabou/alfred-spotify-mini-player/issues/145)

* <span class="badge danger">FIXED</span> Make workflow more resilient to AppleScript output errors[#144](https://github.com/vdesabou/alfred-spotify-mini-player/issues/144)


<a name="v6.4"></a>
6.4:

* <span class="badge info">ADDED</span> Allow users to rename all commands [#132](https://github.com/vdesabou/alfred-spotify-mini-player/issues/132)

* <span class="badge info">ADDED</span> Toggle repeat on / off from the workflow. [#122](https://github.com/vdesabou/alfred-spotify-mini-player/issues/122)

* <span class="badge info">ADDED</span> Add Spotify Mini Player fallback search. [#134](https://github.com/vdesabou/alfred-spotify-mini-player/issues/134)

* <span class="badge danger">FIXED</span> Playlist starting with emoji is causing issues [#129](https://github.com/vdesabou/alfred-spotify-mini-player/issues/129)

* <span class="badge danger">FIXED</span> Charts is broken [#118](https://github.com/vdesabou/alfred-spotify-mini-player/issues/118)

* <span class="badge danger">FIXED</span> Biography is broken [#138](https://github.com/vdesabou/alfred-spotify-mini-player/issues/138)

* <span class="badge danger">FIXED</span> Radio not working [#137](https://github.com/vdesabou/alfred-spotify-mini-player/issues/137)

<a name="v6.3"></a>
6.3:

***WARNING***: This version is only compatible with Alfred v3

* <span class="badge info">ADDED</span> Compatible with Alfred v3

* <span class="badge danger">FIXED</span> Lyrics not found [#115](https://github.com/vdesabou/alfred-spotify-mini-player/issues/120)


<a name="v6.2.6"></a>
6.2.6:

***WARNING***: To use Top Artists and Top Tracks new feature, you need to re-authorize the workflow by executing ```spot_mini_reset_settings``` 

* <span class="badge info">ADDED</span> Add top artists and tracks for user [#116](https://github.com/vdesabou/alfred-spotify-mini-player/issues/116)

* <span class="badge success">IMPROVED</span> Trigger OS X notifications without changing the current music [#109](https://github.com/vdesabou/alfred-spotify-mini-player/issues/109)

* <span class="badge danger">FIXED</span> Search online broken if space is used [#113](https://github.com/vdesabou/alfred-spotify-mini-player/issues/113)

* <span class="badge danger">FIXED</span> localhost site not starting [#114](https://github.com/vdesabou/alfred-spotify-mini-player/issues/114)

* <span class="badge danger">FIXED</span> Now playing pops up even when disabled [#115](https://github.com/vdesabou/alfred-spotify-mini-player/issues/115)

<a name="v6.2.5"></a>
6.2.5:

* <span class="badge info">ADDED</span> Growl notifications [#107](https://github.com/vdesabou/alfred-spotify-mini-player/issues/107)


* <span class="badge danger">FIXED</span> Web API Exception: invalid id when browsing an artist with only local tracks [#111](https://github.com/vdesabou/alfred-spotify-mini-player/issues/111)

* <span class="badge danger">FIXED</span> Bug with Remove track from Playlist[#112](https://github.com/vdesabou/alfred-spotify-mini-player/issues/112)


<a name="v6.2.4"></a>
6.2.4:

* <span class="badge info">ADDED</span> Add volume percent configuration in Settings menu [#94](https://github.com/vdesabou/alfred-spotify-mini-player/issues/94)

* <span class="badge info">ADDED</span> Add possibility to remove stars/ratings from track names [#98](https://github.com/vdesabou/alfred-spotify-mini-player/issues/98)

* <span class="badge info">ADDED</span> Add autoplay when starting radio [#99](https://github.com/vdesabou/alfred-spotify-mini-player/issues/99)

* <span class="badge info">ADDED</span> Improve spot_mini_debug by directly uploading TGZ file to transfer.sh [#105](https://github.com/vdesabou/alfred-spotify-mini-player/issues/105)

* <span class="badge danger">FIXED</span> Search does not handle spaces [#97](https://github.com/vdesabou/alfred-spotify-mini-player/issues/87)

* <span class="badge danger">FIXED</span> No 'error' provided in response body happening almost every time for one user [#104](https://github.com/vdesabou/alfred-spotify-mini-player/issues/104)


<a name="v6.2.3"></a>
6.2.3:

* <span class="badge info">ADDED</span> Add option to create a [Complete Collection](http://alfred-spotify-mini-player.com/articles/complete-collection/) playlist for artist 

* <span class="badge danger">FIXED</span> Fix for issue [#86](https://github.com/vdesabou/alfred-spotify-mini-player/issues/86) Lyrics are broken

* <span class="badge danger">FIXED</span> Fix for issue [#92](https://github.com/vdesabou/alfred-spotify-mini-player/issues/92) Volume controls not working (does not decrease)

<a name="v6.2.2"></a>
6.2.2:

* <span class="badge info">ADDED</span> Add back volmid and volmax commands [#80](https://github.com/vdesabou/alfred-spotify-mini-player/issues/80)

* <span class="badge info">ADDED</span> Add collaborative playlist support [#81](https://github.com/vdesabou/alfred-spotify-mini-player/issues/81)

* <span class="badge danger">FIXED</span> Fix for issue [#78](https://github.com/vdesabou/alfred-spotify-mini-player/issues/78) Just 5 albums in "My Music"


<a name="v6.2.1"></a>
6.2.1:

* <span class="badge info">ADDED</span> Add Play/Pause (Remote and Action) command [#71](https://github.com/vdesabou/alfred-spotify-mini-player/issues/71)

* <span class="badge info">ADDED</span> Order albums by date added (newest on top) [#77](https://github.com/vdesabou/alfred-spotify-mini-player/issues/77)

* <span class="badge success">IMPROVED</span> Better handling of artworks, with lower size (64x64 px)[#74](https://github.com/vdesabou/alfred-spotify-mini-player/issues/74)

* <span class="badge danger">FIXED</span> Fix for issue [#73](https://github.com/vdesabou/alfred-spotify-mini-player/issues/73) 404 error while updating playlist with "!" in owner id name

* <span class="badge danger">FIXED</span> Fix for issue [#72](https://github.com/vdesabou/alfred-spotify-mini-player/issues/72) "Playlist" (browse by playlist) list is empty

<a name="v6.2"></a>
6.2:

* <span class="badge info">ADDED</span> Added [Mopidy](https://www.mopidy.com) compatibility for premium users, see [article](http://alfred-spotify-mini-player.com/articles/mopidy/) [#40](https://github.com/vdesabou/alfred-spotify-mini-player/issues/40)


<a name="v6.1.2"></a>
6.1.2:

* <span class="badge success">IMPROVED</span> Better handling of local files [#68](https://github.com/vdesabou/alfred-spotify-mini-player/issues/68). A local track will be identified with a :pushpin:.

<a name="v6.1.1"></a>
6.1.1:

* <span class="badge danger">FIXED</span> Fix for issue [#65](https://github.com/vdesabou/alfred-spotify-mini-player/issues/65) Ignore playlists with empty names

* <span class="badge success">IMPROVED</span> Detect AppleScript execution errors [#67](https://github.com/vdesabou/alfred-spotify-mini-player/issues/67). This will help users to detect if they have the latest [bugged Spotify update](http://alfred-spotify-mini-player.com/blog/issue-with-latest-spotify-update/).

<a name="v6.1"></a>
6.1:

* <span class="badge info">ADDED</span> Added [Browse Spotify categories](http://alfred-spotify-mini-player.com/articles/browse-categories/)

* <span class="badge info">ADDED</span> Added [Quick Mode](http://alfred-spotify-mini-player.com/articles/quick-mode/)

* <span class="badge success">IMPROVED</span> The [Back Button](http://alfred-spotify-mini-player.com/articles/back-button/) does not appear by default now. You can now make it appear by typing `bb` at any time.

<a name="v6.0.4"></a>
6.0.4:

* <span class="badge info">ADDED</span> Add `Play Random Album` [#60](https://github.com/vdesabou/alfred-spotify-mini-player/issues/60)

* <span class="badge success">IMPROVED</span> Better handling of playable tracks. Not playable tracks are displayed with :no_entry_sign: and are not actionable [#57](https://github.com/vdesabou/alfred-spotify-mini-player/issues/57)

* <span class="badge success">IMPROVED</span> Added check for [minimal PHP](http://alfred-spotify-mini-player.com/known-issues/#php_requirement) version which is 5.4 (shipped in Mavericks) [#59](https://github.com/vdesabou/alfred-spotify-mini-player/issues/59)

<a name="v6.0.3"></a>
6.0.3:

* <span class="badge danger">FIXED</span> Fix for issue [#52](https://github.com/vdesabou/alfred-spotify-mini-player/issues/52) and [#53](https://github.com/vdesabou/alfred-spotify-mini-player/issues/53)

<a name="v6.0.21"></a>
6.0.21:

* <span class="badge danger">FIXED</span> Fix for issue [#49](https://github.com/vdesabou/alfred-spotify-mini-player/issues/49) and [#50](https://github.com/vdesabou/alfred-spotify-mini-player/issues/50)

* <span class="badge danger">FIXED</span> Control Spotify volume instead of System volume [#47](https://github.com/vdesabou/alfred-spotify-mini-player/issues/47)


<a name="v6.0.1"></a>
6.0.1:

* <span class="badge danger">FIXED</span> Fix for issue [#42](https://github.com/vdesabou/alfred-spotify-mini-player/issues/42), [#45](https://github.com/vdesabou/alfred-spotify-mini-player/issues/45), [#46](https://github.com/vdesabou/alfred-spotify-mini-player/issues/46), [#48](https://github.com/vdesabou/alfred-spotify-mini-player/issues/48)

<a name="v6.0"></a>
6.0:

* <span class="badge info">ADDED</span> Full _Alfred Remote_ support

* <span class="badge info">ADDED</span> [Now Playing]( {{ site.url }}/articles/now-playing): display a notification every time a track is played (or un-paused).

* <span class="badge info">ADDED</span> [Play Queue]( {{ site.url }}/articles/play-queue): get the _Play Queue_ directly in the workflow.

* <span class="badge info">ADDED</span> New icons

* <span class="badge info">ADDED</span> Added [charts]( {{ site.url }}/articles/charts)

* <span class="badge info">ADDED</span> Background download of artworks and better, quicker [library refresh]( {{ site.url }}/articles/update-library)

* <span class="badge info">ADDED</span> Added [Search Online]( {{ site.url }}/articles/search-online) (i.e not in your library) for albums, artists or tracks and playlists

* <span class="badge success">IMPROVED</span> Better [lyrics]( {{ site.url }}/articles/lyrics) handling

* <span class="badge info">ADDED</span> Added [new album releases]( {{ site.url }}/articles/new-releases)

* <span class="badge info">ADDED</span> [Follow or Unfollow]( {{ site.url }}/articles/follow-artist) an artist

* <span class="badge info">ADDED</span> [Follow or remove]( {{ site.url }}/articles/follow-or-remove-playlist) a playlist

* <span class="badge success">IMPROVED</span> And many more improvements and bug fixes...

5.2:

* <span class="badge info">ADDED</span> Added Create Artist Radio playlist (number of tracks is configurable)

* <span class="badge info">ADDED</span> Added Create Song Radio playlist feature (number of tracks is configurable)

* <span class="badge info">ADDED</span> Added command `artist_radio` to create artist radio playlist from current artist

* <span class="badge info">ADDED</span> Added command `song_radio` to create song radio playlist from current track

* <span class="badge success">IMPROVED</span> Continue using the workflow while updating library (playlist, all playlists or _Your Music_)!

* <span class="badge info">ADDED</span> Added command `lyrics` to get lyrics from current track

* <span class="badge info">ADDED</span> New sub-menu for Current Track (hotkey available for quick access)


5.1.2:

* <span class="badge danger">FIXED</span> Fix for issue #29 (If you had more than 50 playlists, say 58, only the last 8 were processed)

* <span class="badge danger">FIXED</span> Fix for playlist artworks not downloaded


5.1.1:

* <span class="badge success">IMPROVED</span> Added more validations for authentication (check length is 32, check that Client Secret is different than Client ID).

* <span class="badge info">ADDED</span> Added command `spot_mini_reset` to reset settings

5.1:

* <span class="badge danger">FIXED</span> Fixed hard-code user id in update library

* <span class="badge success">IMPROVED</span> Enhancements to play/pause

5.0:

* <span class="badge success">IMPROVED</span> Using now Spotify WEB API instead of Spotify App API

* <span class="badge success">IMPROVED</span> Using _OAuth 2.0_ authentication

* <span class="badge info">ADDED</span> Control _Your Music_, you can choose to control the Alfred Playlist or _Your Music_ now

* <span class="badge info">ADDED</span> Featured Playlists in your country, US and UK

* <span class="badge success">IMPROVED</span>Update Playlists now also update playlists which have been updated (not only the ones added or removed as before)

* <span class="badge success">IMPROVED</span> Reworked biography

* <span class="badge success">IMPROVED</span> Many more improvements


4.6:

* <span class="badge danger">FIXED</span> Fixed broken Play Random Track

* <span class="badge info">ADDED</span> Now displaying notification for random track

* <span class="badge info">ADDED</span> Add 'Get current track information'

* <span class="badge info">ADDED</span> Add notification for previous and next track

4.5:

* <span class="badge info">ADDED</span> Add Play artist option in Browse this artist mode

4.4.91:

* <span class="badge danger">FIXED</span> Fix for artworks being downloaded in online mode (regression)

4.4.9:

* <span class="badge info">ADDED</span> Get a maximum of 1000 related artists

4.4.8:

* <span class="badge danger">FIXED</span> Temporarily fixed problem with update library


4.4.7:

* <span class="badge danger">FIXED</span> Fixed artwork download issues

4.4.6:

* <span class="badge danger">FIXED</span> Fixed `spot_mini_debug`

4.4.5:

* <span class="badge danger">FIXED</span> Second attempt to get related artists more reliable

* <span class="badge success">IMPROVED</span> Improved performances of Update Library, Update Playlist and Update Playlist List


4.4.4:

* <span class="badge success">IMPROVED</span> Moved to new Spotify WEB API for online lookups

* <span class="badge info">ADDED</span> Display release date of album in online mode


4.4.3:

* <span class="badge danger">FIXED</span> Get related artists is more reliable

* <span class="badge info">ADDED</span> Display in playlist for every track


4.4.2:

* <span class="badge danger">FIXED</span> Fixed check for updates

4.4.1:

* <span class="badge success">IMPROVED</span> Compliant with latest Spotifious version

4.4:

* <span class="badge success">IMPROVED</span> Improved performances

* <span class="badge info">ADDED</span> New command to unstar a track

* <span class="badge info">ADDED</span> Added new command (with external trigger) `spot_mini_update_library` to update library

* <span class="badge success">IMPROVED</span> Automatically update Alfred Playlist after adding track, album or playlist

* <span class="badge success">IMPROVED</span> Automatically update starred playlist after star/unstar track

* <span class="badge info">ADDED</span> New theme

* <span class="badge info">ADDED</span> Shortcuts to Settings/Alfred Playlist/Playlists (need to configure hotkeys)

4.3.6:

* <span class="badge success">IMPROVED</span> Improved notifications

4.3.5:

* <span class="badge danger">FIXED</span> Fix issue with `add_current_track_to_alfred_playlist_or_your_music command

4.3.4:

* <span class="badge info">ADDED</span> Star track display track info and artwork

* <span class="badge success">IMPROVED</span> Search artist online even when not in library

4.3.3:

* <span class="badge danger">FIXED</span> Fix display of results in a playlist when searching tracks

4.3.2:

* <span class="badge danger">FIXED</span> Fix release package 4.3.1

4.3.1:

* <span class="badge danger">FIXED</span> Fix release package 4.3

4.3:

* <span class="badge info">ADDED</span> New command `add_current_track_to_alfred_playlist_or_your_music`: Add current track to Alfred Playlist

* <span class="badge success">IMPROVED</span> Modifer action are now working on the now playing track (result which has the play/pause option)

* <span class="badge info">ADDED</span> New notifications now display artworks(10.9+ only)

* <span class="badge success">IMPROVED</span> Various improvements

4.2.3:

* <span class="badge info">ADDED</span> Option to disable Get Lyrics


4.2.1:

* <span class="badge danger">FIXED</span> Fix a problem with local files in playlist

* <span class="badge success">IMPROVED</span> Updated to Packal

* <span class="badge success">IMPROVED</span> Updated to new Spotifious version

4.2:

* <span class="badge success">IMPROVED</span> Implemented suggestions described in <a href="https://github.com/vdesabou/alfred-spotify-mini-player/issues/14">issue 14</a>

4.1.2:

* <span class="badge info">ADDED</span> Add spot_mini_issue command to report a bug or propose enhancement.

* <span class="badge danger">FIXED</span> Fix a potential issue with Library update.

4.1.1:

* <span class="badge info">ADDED</span> Now use free TCP port for communication between workflow and Spotify App Helper.

4.1:

* <span class="badge success">IMPROVED</span> Improved Mini player App. Removed configuration of country code, it is now done automatically.

4.0.2:

* <span class="badge info">ADDED</span> Added debug area in Spotify App, this will help debugging issues with library update, if any

4.0.1:

* <span class="badge danger">FIXED</span> Fixed a problem where notifications are not working

4.0:

* <span class="badge info">ADDED</span> Choose workflow themes: green or black

* <span class="badge info">ADDED</span> Display and browse Related Artists

* <span class="badge info">ADDED</span> Display Artist biography

* <span class="badge info">ADDED</span> Display current track lyrics

* <span class="badge info">ADDED</span> Use any of your playlists (including the starred playlist) as the Alfred Playlist

* <span class="badge info">ADDED</span> Clear your Alfred Playlist (exluding the starred playlist)

* <span class="badge info">ADDED</span> Auto-Updater: check for update once per day

* <span class="badge info">ADDED</span> Check for update in Settings

* <span class="badge success">IMPROVED</span> Various enhancements

3.8.5:

* <span class="badge danger">FIXED</span> Fix issue with some weird playlists with some kind of corrupted tracks

3.8.4:

* <span class="badge info">ADDED</span> Added more error checks when setting Alfred Playlist URL

3.8.3:

* <span class="badge danger">FIXED</span> Attempt to fix corrupted spotify app directories

3.8.2:

* <span class="badge success">IMPROVED</span> Reworked failure detection for first time use

3.8:

* <span class="badge info">ADDED</span> Added playlists in now playing section

3.7.5:

* <span class="badge danger">FIXED</span> Fix: Browse by Artist and by Album is very slow for big libraries (>30K tracks)

3.7.4:

* <span class="badge danger">FIXED</span> Fixed output for update playlist and update playlists list

3.7.3:

* <span class="badge danger">FIXED</span> Fixed problem when antislash is in album,track,artist name

3.7.2:

* <span class="badge danger">FIXED</span> Minor Fixes.

3.7:

* <span class="badge success">IMPROVED</span> Top list is now a playlist like any others.

* <span class="badge success">IMPROVED</span> Now check if track is playable before displaying it

3.6:

* <span class="badge info">ADDED</span> Added Update Playlist list(use it when you have added or removed a playlist). Various fixes.

3.5:

* <span class="badge success">IMPROVED</span> Added artworks to playlists! Please update your library to enjoy this new feature

3.4:

* <span class="badge danger">FIXED</span> Some fixes and improvements on Playlists

3.3:

* <span class="badge info">ADDED</span> Added progress indicator during update of library and playlists

* <span class="badge info">ADDED</span> Added possibility to update playlists directly from the workflow!

3.2:

* <span class="badge success">IMPROVED</span> No more need to copy JSON data! Just click on "Update library"

* <span class="badge info">ADDED</span> Added `spot_mini_kill` command to kill update library if it is stuck

3.1:

* <span class="badge success">IMPROVED</span> Moving to spotify API 1.x: no more 200 tracks limitation on playlists

* <span class="badge info">ADDED</span> Added `spot_mini_debug` command to help troubleshooting

* <span class="badge success">IMPROVED</span> Improved robustness and error detections

3.0:

* <span class="badge info">ADDED</span> Major speed improvements. Using a library with 18000 tracks, search scope set to ALL, with artworks displayed, it takes 150ms to return 50 results

* <span class="badge info">ADDED</span> Launch your top list

* <span class="badge info">ADDED</span> Online mode: Lookup for artist online and then browse all albums and tracks

* <span class="badge success">IMPROVED</span> Many more improvements


2.8:

* <span class="badge info">ADDED</span> New icons

* <span class="badge info">ADDED</span> Option to enable/disable artworks

* <span class="badge info">ADDED</span> Option to enable/disable ***More from this artist/album***


2.7:

* <span class="badge success">IMPROVED</span> Attempt to better detect problems with spotify-app-miniplayer app

2.6:

* <span class="badge danger">FIXED</span> Fix issue during library creation where nothing happened

2.5:

* <span class="badge info">ADDED</span> Quick access to menus, for example start typing setting and Settings menu will be selected

* <span class="badge info">ADDED</span> Add a playlist to Alfred Playlist using ⇧ modifier

2.4:

* <span class="badge info">ADDED</span> Introducing Alfred Playlist: control a playlist from Alfred. Add Track with *fn* or Album with *shift* to the playlist, browse it or clear it from Alfred.

2.3:

* <span class="badge success">IMPROVED</span> Using own Spotify app <spotify:app:miniplayer>. Nore more need to do manual install, this is automatically done. Using this allows more control and less hacking to make it work
* <span class="badge info">ADDED</span> New *random* command, it will launch a random track from any of your playlists
* <span class="badge info">ADDED</span> New *star* command, it will star the current track

2.2:

* <span class="badge success">IMPROVED</span> Move code to [GitHub](https://github.com/vdesabou/alfred-spotify-mini-player)
* <span class="badge danger">FIXED</span> Fix artworks not cached when playlist is not from the user

2.1:

* <span class="badge info">ADDED</span> Display user for playlists
* <span class="badge info">ADDED</span> Added settings to launch spotify:app:export app

2.0:

* <span class="badge success">IMPROVED</span> Automatic support of playlists (including starred playlist)
* <span class="badge info">ADDED</span> Setting to disable spotifious
* <span class="badge info">ADDED</span> alt and cmd modifiers now open and play music

1.15:

* <span class="badge danger">FIXED</span> Fix for very large libraries

1.14:

* <span class="badge danger">FIXED</span> Fixes and improvements to playlists

1.13:

* <span class="badge info">ADDED</span> Built-in support of playlists!

1.12:

* <span class="badge info">ADDED</span> Updated (again) allocated memory to 256M
* <span class="badge success">IMPROVED</span> Check json data is valid when creating/updating library
* <span class="badge danger">FIXED</span> minor fixes

1.11:

* <span class="badge danger">FIXED</span> Updated allocated memory to 128M.

1.10:

* <span class="badge danger">FIXED</span> Fix memory issue when library.json file size is big.
* <span class="badge info">ADDED</span> New icons.

1.9:

* <span class="badge success">IMPROVED</span> Do not fetch online artworks for current track(for performance reasons).
* <span class="badge danger">FIXED</span> Minor bug fixes.

1.8:

* <span class="badge info">ADDED</span> Added same keywords as iTunes Mini Player: play, pause, mute, next, previous, volmax, volmid. And shuffle to activate shuffling.

1.7:

* <span class="badge success">IMPROVED</span> Performance improvement when using Starred Playlist only Search Scope (only a subset of library.json is loaded)

1.6:

* <span class="badge danger">FIXED</span> Fix for duplicate tracks in results.
* <span class="badge danger">FIXED</span> Better handling of UTF-8 characters.

1.5:

* <span class="badge info">ADDED</span> Display current track information.
* <span class="badge info">ADDED</span> Select current track to play/pause the track
* <span class="badge info">ADDED</span> Added More from this Artist and More from this album

1.4:

* <span class="badge success">IMPROVED</span> Display a default artwork when not available.
* <span class="badge danger">FIXED</span> Fix Search playlist
* <span class="badge info">ADDED</span> Add check that Max Results is a number and greater than 0

1.3:

* <span class="badge info">ADDED</span> Added Browse by Playlists (if playlists.json is configured)
* <span class="badge info">ADDED</span> Added configuration for Max number of results
* <span class="badge info">ADDED</span> Added default result to search with Spotify

1.2:

* <span class="badge danger">FIXED</span> Fixed issue when browsing by Artist and by Album

1.1:

* <span class="badge success">IMPROVED</span> library.json, playlists.json and artwork cache are now in the app data directory (/Users/YOUR_USER/Library/Application Support/Alfred 2/Workflow Data/com.vdesabou.spotify.mini.player). The workflow can now be updated without loosing cached artworks, playlists and library.

1.0:

* <span class="badge info">ADDED</span> Initial Version