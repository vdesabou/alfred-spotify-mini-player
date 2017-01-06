---
layout: article
title: "Library update"
date: 2014-12-23T11:39:03-04:00
modified:
categories: articles
excerpt: "Fast and automatic library updates (with artworks downloaded in background)."
image:
  teaser: teaser-refresh-library.jpg
  feature:
  credit: 
  creditlink:
noindex: false
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

## Background download of artworks

Since version 6, the workflow downloads artworks in background, which means that the Create/Refresh Library is very fast.

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

If for some reasons you want to delete and re-create your entire library, you can select ```Re-Create your library from scratch``` in Settings menu:-

<figure>
	<img src="{{ site.url }}/images/refresh-library3.jpg">
	<figcaption>Re-Create your library from scratch.</figcaption>
</figure>

