---
layout: default
title: Searching the Spotify catalog
---

The whole Spotify catalog, including playlists, can be searched in various ways.
Since the Spotify search contains so many features, this page just includes a basic example and one should refer to the
[Spotify documentation](https://developer.spotify.com/web-api/search-item/) and [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html)
for more information.

    <?php
    require 'vendor/autoload.php';

    $api = new SpotifyWebAPI\SpotifyWebAPI();
    $results = $api->search('blur', 'artist');

    foreach ($results->artists->items as $artist) {
        echo $artist->name, '<br>';
    }
