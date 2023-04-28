---
layout: article
permalink: /known-issues/
title: "Known Issues"
modified: 2021-10-25
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

## Authentication issues

* Make sure to **disable** *Mac OS http proxy settings for scripts* option in Alfred advanced preferences during time of authentication.

* **Wappalyzer** browser extension interfere with authentication process: make sure to disable it during time of authentication.

* Safari [does not work](https://github.com/vdesabou/alfred-spotify-mini-player/issues/341) with authentication, if it is default on your system, the workflow will try to launch (in this order) Google Chrome, Firefox, Brave, Chromium, Microsoft Edge or Vivaldi instead.


## Spotify WEB API limitations

* The special playlists like _Daily Mix_ or _Discover Weekly_ are not supported by the workflow, there is no API available to do that.

## Mopidy

* Mopidy support in the workflow is deprecated, see reasons [here](https://github.com/vdesabou/alfred-spotify-mini-player/issues/340).

## Sonos

* Sonos speakers are not appearing in the workflow due to a Spotify issue, see [bug](https://github.com/vdesabou/alfred-spotify-mini-player/issues/207)

## Install PHP (MacOS Monterey and above)

PHP is no more installed by default starting from MacOS Monterey, so you need to install `php` from [brew](https://brew.sh) before using the workflow:

* Install brew: `/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"`

* Install PHP: `brew install php`


Note:

PHP 5.5.0 or later is required for authentication. This is because I am using the PHP CLI [built-in web server](http://php.net/manual/en/features.commandline.webserver.php) for Oauth process. If you use an older version, install php from brew as explained above.

