---
layout: article
title: "Notifications"
date: 2014-12-23T11:39:03-04:00
modified: 2018-01-26
categories: articles
excerpt: "Display an OS X or Growl notification for listened tracks."
image:
  teaser: bullet-now-playing.png
  feature:
  credit:
  creditlink:
noindex: false
share: false
toc: false
comments: true
onhomepage : true
---

MacOS and [growl](http://growl.info) notifications are supported.

* You can enable "Now Playing" notifications (enabled by default), to get a notification every time a song changes:-

<figure class="half">
	<img src="{{ site.url }}/images/now-playing.jpg">
	<img src="{{ site.url }}/images/now-playing3.jpg">
	<figcaption>Examples of notifications.</figcaption>
</figure>

You get in the notification:-

  * the name of the song
  * the artist and album name
  * track length
  * track popularity
  * a beautiful artwork. (only with Mac OS notifications and before Big Sur)
  * ♥ if track is liked, ♡ otherwise

* You can disable this option at any time in Settings menu:-

<figure>
	<img src="{{ site.url }}/images/now-playing2.jpg">
	<figcaption>Disable Now Playing option in Settings menu.</figcaption>
</figure>


* To disable completely all notifications, disable notifications using the standard OS X way for Spotify Mini Player application:-

<figure>
	<img src="{{ site.url }}/images/now-playing4.jpg">
	<figcaption>Disable all notifications in OS X preferences.</figcaption>
</figure>


* Also, as explained in [Customization]( {{ site.url }}/articles/customization), you can reduce the number of notifications, by modifying variable `reduce_notifications`:-

<figure>
	<img src="{{ site.url }}/images/customization8.jpg">
	<figcaption>Reduce notifications. 1 for reducing, 0 otherwise.</figcaption>
</figure>