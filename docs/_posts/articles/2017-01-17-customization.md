---
layout: article
title: "Customization"
date: 2017-01-17T11:39:03-04:00
modified: 2020-09-23
categories: articles
excerpt: "You can customize the workflow by changing settings or variables..."
image:
  teaser: bullet-customization.png
  feature:
  credit:
  creditlink:
noindex: false
share: false
toc: false
comments: true
onhomepage : true
---

* Most of the settings are located in *Settings* menu:-

<figure>
	<img src="{{ site.url }}/images/customization1.jpg">
  <figcaption>Settings menu (1/2).</figcaption>
</figure>

<figure>
	<img src="{{ site.url }}/images/customization2.jpg">
  <figcaption>Settings menu (2/2).</figcaption>
</figure>

But you can also use [Alfred variables](https://www.alfredapp.com/help/workflows/advanced/variables/) to customize the workflow:-

**IMPORTANT**: variables starting with '__' are handled by the workflow. They can be changed bu at your own risk.

* You can change any workflow command name by modifying variables starting with `c_`, for example replace *spot_mini* by *s*:-

<figure>
	<img src="{{ site.url }}/images/customization3.jpg">
	<figcaption>Change workflow command names.</figcaption>
</figure>

* You can hide some main menu entries by putting `0`(or any other value) by modifying variables starting with `menu_`:-

<figure>
	<img src="{{ site.url }}/images/customization4.jpg">
	<figcaption>Hide menu entry by putting value other than 1.</figcaption>
</figure>

* You can change the volume min, mid, max values by modifying variables starting with `settings_volume`:-

<figure>
	<img src="{{ site.url }}/images/customization5.jpg">
	<figcaption>Change volume default values.</figcaption>
</figure>

* You can change the default text for [sharing]( {{ site.url }}/articles/share) by modifying variables starting with `sharing_`:-

<figure>
	<img src="{{ site.url }}/images/customization6.jpg">
	<figcaption>Change default sharing texts.</figcaption>
</figure>

* You can decide to append to the playlist when adding track(s), instead of putting tracks at beginning, by modifying variable `append_to_playlist_when_adding_tracks`:-

<figure>
	<img src="{{ site.url }}/images/customization7.jpg">
	<figcaption>Append to the playlist when adding track(s). 1 for appending, 0 otherwise.</figcaption>
</figure>

* You can reduce the number of notifications, by modifying variable `reduce_notifications`:-

<figure>
	<img src="{{ site.url }}/images/customization8.jpg">
	<figcaption>Reduce notifications. 1 for reducing, 0 otherwise.</figcaption>
</figure>

* You can decide to not add to your library the radio playlist, by modifying variable `add_created_radio_playlist_to_library`:-

<figure>
	<img src="{{ site.url }}/images/customization9.jpg">
	<figcaption>Add radio playlist to library. 1 for adding to library, 0 otherwise.</figcaption>
</figure>

* You can change behaviour of `Previous Track`, by modifying variable `previous_track_for_real`:-

  * 1 for going to previous track
  * 0 for going back to beginning of the track

* You can decide to not automatically refresh the library when you modify your library (adding a track to a playlist for example), by modifying variable `automatically_refresh_library`:-

  * 1 for automatically refresh library
  * 0 for not automatically refresh library

* You can decide to add all album tracks in your music when you add an album in Your Music (otherwise it just likes the album), by modifying variable `add_all_tracks_from_album_when_adding_to_yourmusic`:-

  * 1 for adding all album tracks
  * 0 for not not adding all album tracks

* You can customize Now Playing notifications for episodes (podcasts), by modifying variables `now_playing_episode_title` for title and `now_playing_episode_text` for text. You can use those variables:-

  * `{episode_name}` for episode name
  * `{show_name}` for show name
  * `{duration}` for duration

  Defaults are:

    * `now_playing_episode_title`: `Now Playing ({duration})`
    * `now_playing_episode_text`: `🔈🎙 {episode_name} in show {show_name}`

* You can customize Now Playing notifications for tracks, by modifying variables `now_playing_track_title` for title and `now_playing_track_text` for text. You can use those variables:-

  * `{track_name}` for track name
  * `{album_name}` for album name
  * `{artist_name}` for artist name
  * `{duration}` for duration
  * `{popularity}` for popularity ⭐ (emoji can be customized, see below)
  * `{liked}` ♥ if track is liked, ♡ otherwise (emoji can be customized, see below)

  Defaults are:

    * `now_playing_track_title`: `Now Playing {popularity} ({duration})`
    * `now_playing_track_text`: `🔈 {track_name} by {artist_name} in album {album_name}`

* You can change default emojis used in the workflow by modifying those variables:-

<figure>
	<img src="{{ site.url }}/images/customization10.jpg">
	<figcaption>Change default emojis used in workflow.</figcaption>
</figure>

* You can customize text copied in clipboard in current track section for episodes (podcasts), by modifying variable `clipboard_current_track_episode_text`. You can use those variables:-

  * `{episode_name}` for episode name
  * `{url}` for spotify url

  Defaults are:

    * `clipboard_current_track_episode_text`: `#NowPlaying {episode_name} {url}`

* You can customize text copied in clipboard in current track section for tracks, by modifying variable `clipboard_current_track_track_text`. You can use those variables:-

  * `{track_name}` for track name
  * `{artist_name}` for artist name
  * `{url}` for spotify url

  Defaults are:

    * `clipboard_current_track_track_text`: `#NowPlaying {track_name} by {artist_name} {url}`

* You can ignore *unplayable* tracks (which are represented by default with symbol 🚫) by setting variable `ignore_unplayable_tracks`to 1. An example is if you have disabled explicit lyrics in Spotify desktop, but you still want to be able to play those tracks with the workflow.