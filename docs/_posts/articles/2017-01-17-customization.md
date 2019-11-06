---
layout: article
title: "Customization"
date: 2017-01-17T11:39:03-04:00
modified: 2019-11-05
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