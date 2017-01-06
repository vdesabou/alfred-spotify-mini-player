---
layout: article
permalink: /known-issues/
title: "Known Issues"
modified: 2016-09-23
excerpt: "List of Known Issues."
image:
  feature:
  teaser:
  thumb:
share: false
toc: true
noindex: false
---

{% include toc.html %}

This is the list of current known issues:

## Spotify AppleScript bug

* The local tracks [cannot be launched](https://github.com/vdesabou/alfred-spotify-mini-player/issues/82) due to a [bug](https://community.spotify.com/t5/Help-Desktop-Linux-Mac-Windows/AppleScript-play-track-not-working-with-local-tracks/m-p/1143252#M129641) with Spotify AppleScript.

## Spotify WEB API limitations

* The *Starred* playlist is not returned by Web API. Spotify has [removed](https://support.spotify.com/us/learn-more/faq/#!/article/what-happened-to-starred-tracks) _starred tracks_ and replaced by _Your Music_.
As a workaround, you can create a new playlist (called 'Starred' for example) and copy all your starred tracks in it.

* You cannot have more than 10000 tracks in _Your Music_.

<a name="php_requirement"></a>

## PHP Requirement

* PHP 5.5.0 or later is required for authentication. This is because I am using the PHP CLI [built-in web server](http://php.net/manual/en/features.commandline.webserver.php) for Oauth process. If you use an older version, there is a [workround](https://github.com/vdesabou/alfred-spotify-mini-player/issues/44#issuecomment-72003149). Ask for more details if required.

 
## Mopidy

* Local tracks are not supported by Mopidy, this is a [known issue](https://github.com/mopidy/mopidy/issues/519). The workflow will not display local tracks when Mopidy is used.

