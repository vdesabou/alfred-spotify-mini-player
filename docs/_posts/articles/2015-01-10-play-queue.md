---
layout: article
title: "Play Queue"
date: 2015-01-10
modified: 2015-01-18
categories: articles
excerpt: "Get the list of queued tracks directly in the workflow."
image:
  teaser: teaser-play-queue.jpg
  feature:
  credit:
  creditlink:
noindex: false
toc: false
comments: true
onhomepage : true
---

* You can get the _Play Queue_ as in the Spotify desktop application from the *Main* menu:-

<figure>
	<img src="{{ site.url }}/images/play-queue1.jpg">
	<figcaption>Get Play Queue from Main menu.</figcaption>
</figure>

* Then you get the _Play Queue_, you can select a track go directly to this track in the _Play Queue_:-

<figure>
	<img src="{{ site.url }}/images/play-queue2.jpg">
	<figcaption>List of tracks in the Play Queue.</figcaption>
</figure>

The _Play Queue_ is truncated after displaying 150 results.

This is using same principles as the _Play Queue_ in the Spotify application:-

* If you launch a playlist, the _Play Queue_ will contain the entire playlist

* If you play an album, the _Play Queue_ will contain the entire album

* If you play an artist, the _Play Queue_ will contain the 10 top tracks of the artist

* If you play a track from a playlist, the _Play Queue_ will contain the entire playlist, starting at the position of the selected track

* If you play a track from an album, the _Play Queue_ will contain the entire album, starting at the position of the selected track

* If you play a track without context (i.e, not from an album or playlist) then the track will be added at the beginning of existing _Play Queue_

**Important:** You should use exclusively the workflow if you want to use _Play Queue_. If you use the Spotify application to launch a track or an album for example, the _Play Queue_ in the workflow will be empty.
If you use shuffling, the order presented in the _Play Queue_ will not be relevant.
{: .notice-danger}