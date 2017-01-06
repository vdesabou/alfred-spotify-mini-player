---
layout: article
title: "Issue with latest Spotify update"
date: 2015-03-20
modified: 2015-03-31
categories: blog
excerpt: "Version below 1.0.3 breaks the workflow"
image:
  teaser: 
  feature:
  credit:
  creditlink:
noindex: false
toc: true
comments: true
onhomepage : false
---


**Update (31st March):** Spotify version [1.0.3](https://community.spotify.com/t5/Spotify-Announcements/Release-Notes-Spotify-for-Desktop/m-p/1075314) fixes the problem, this is currently being deployed. Users must wait for the auto-update, there is no way to force update of the Spotify desktop application.
{: .notice-info}

Spotify has deployed a version 1.0.2.x (latest one is `1.0.2.6.g9977a14b`) which breaks the [AppleScript](https://community.spotify.com/t5/Help-Desktop-Linux-Mac-and/Apple-scripting-broken-in-1-0-1-988-g8f17a348/td-p/1029434) support.

If you're one of the users who have been upgraded to 1.0.2 version, the workflow will no more work. See this [issue](https://github.com/vdesabou/alfred-spotify-mini-player/issues/66) for more details. 

If you have latest version 6.1.2 of the workflow, you'll get a warning message indicating an AppleScript error :-

<figure>
    <img src="{{ site.url }}/images/blog/spotify_update_problem2.jpg">
    <figcaption>Error message you get with version 6.1.1</figcaption>
</figure> 

There is a [workaround](http://dangercove.github.io/Spotify-AppleScript-Patch/) to have AppleScript working with version 1.x *BUT* the `play track` is still broken with the workaround, so the workflow will not be able to launch tracks or playlists, which is pretty useless.

The only way is to revert back to previous Spotify version by following steps [here](http://supraliminal.net/blog/2013/4/21/how-to-revert-back-to-the-older-better-spotify-client), see section _MAC OS X_.

Note that you can download `0.9.15.27` and not `0.8.5.1356` as described in the article.

After applying the workaround, you should see a message _There was a problem updating Spotify (BAD FILE SIG)_ :-

<figure>
    <img src="{{ site.url }}/images/blog/spotify_update_problem.jpg">
    <figcaption>Message: There was a problem updating Spotify</figcaption>
</figure>





