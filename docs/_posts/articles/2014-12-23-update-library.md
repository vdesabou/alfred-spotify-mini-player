---
layout: article
title: "Library update"
date: 2014-12-23T11:39:03-04:00
modified: 2020-05-25
categories: articles
excerpt: "Fast and automatic library updates (with artworks downloaded in background)."
image:
  teaser: bullet-library-update.png
  feature:
  credit:
  creditlink:
noindex: false
share: false
toc: true
comments: true
onhomepage : true
---

{% include toc.html %}

## Refresh Library

* Every time you update Your Music or add a track/album/playlist to a playlist, the workflow automatically updates the library and you'll get notifications:-

<figure>
	<img src="{{ site.url }}/images/refresh-library.jpg">
	<figcaption>Notifications during a Refresh Library.</figcaption>
</figure>

Note: This can be disabled by setting `automatically_refresh_library` environment variable to `0`

* In case you have modified a playlist or added tracks to Your Music using the Spotify application directly, then you can force a Refresh Library:-

  * Use the `refresh_library` command:-

<figure>
	<img src="{{ site.url }}/images/refresh-library1.jpg">
	<figcaption>refresh_library command.</figcaption>
</figure>

  * Use the `Refresh your library` in Settings menu:-

<figure>
	<img src="{{ site.url }}/images/refresh-library2.jpg">
	<figcaption>Refresh your Library in Settings menu.</figcaption>
</figure>

* You can activate automatic refresh of your library every x minutes (enter 0 to de-activate) in Settings menu:-

<figure>
	<img src="{{ site.url }}/images/refresh-library5.jpg">
	<figcaption>Automatic refresh of library.</figcaption>
</figure>

## Refresh only selected playlists

If you have a large library where only a handful of playlists actually change, you can restrict the Refresh Library to those playlists. The others are left completely alone: no track fetch at all, and they keep the tracks they already have in the library.

* Select `Refresh only selected playlists` in Settings menu to turn it on (`Refresh all playlists` turns it back off).

* Then, in the menu of each playlist you want to keep up to date, select `Add playlist ... to the refresh list` (and `Remove playlist ... from the refresh list` to take it out again).

Notes:

* Playlists that are not in the refresh list still get their name and privacy updated, since that costs no additional Spotify API call. Only their tracks are frozen.
* Playlists you follow or create after turning the option on are still added to the library, so that you can put them in the refresh list.
* Playlists you remove from Spotify are still removed from the library.
* The list is stored in the `__refresh_playlists` workflow configuration variable, so you can also paste playlist uris or ids there directly, one per line.

## Background download of artworks

The workflow downloads artworks in background, which means that the Create/Refresh Library is very fast.

You get a notification when background download of artworks starts:-

<figure>
	<img src="{{ site.url }}/images/setup4.jpg">
	<figcaption>Notification for the start of background download of artworks.</figcaption>
</figure>

* During that time, you can use the workflow and you'll see the progress at the top of main menu:-

<figure>
	<img src="{{ site.url }}/images/refresh-library4.jpg">
	<figcaption>Progress bar for background download of artworks.</figcaption>
</figure>

* Until the download is complete, you can see some blank artworks, that's expected:-

<figure>
	<img src="{{ site.url }}/images/setup5.jpg">
	<figcaption>Example of blank artworks until the end of background download is over.</figcaption>
</figure>

## Re-Create Library from scratch

If for some reason you want to delete and re-create your entire library, you can select ```Re-Create your library from scratch``` in Settings menu:-

<figure>
	<img src="{{ site.url }}/images/refresh-library3.jpg">
	<figcaption>Re-Create your library from scratch.</figcaption>
</figure>

