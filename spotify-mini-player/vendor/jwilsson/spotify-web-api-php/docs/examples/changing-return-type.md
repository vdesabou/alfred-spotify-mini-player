---
layout: default
title: Changing return type
---

When requesting data from Spotify the default format is an PHP object, sometimes wrapped in an array if there are multiple entries. For example when using `SpotifyWebAPI::getArtists()`. However, it's possible to get an associative array instead of an object.

## Handling errors

    <?php
    $api->setReturnType(SpotifyWebAPI::RETURN_ASSOC);

    $track = $api->me(); // Will be an associative array

## Checking the current return type

    <?php
    var_dump($api->getReturnType()); // 'assoc'

The possible values are:
* `SpotifyWebAPI::RETURN_ASSOC`
* `SpotifyWebAPI::RETURN_OBJECT` (default)
