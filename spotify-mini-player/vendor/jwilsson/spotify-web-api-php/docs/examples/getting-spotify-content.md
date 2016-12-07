---
layout: default
title: Getting Spotify featured content
---

If you wish to access content that's featured/curated by Spotify there are a number of methods to achieve that.

*Note: All of these methods require authentication.*

    <?php
    require 'vendor/autoload.php';

    $session = new SpotifyWebAPI\Session('SPOTIFY_CLIENT_ID', 'SPOTIFY_CLIENT_SECRET', 'SPOTIFY_REDIRECT_URI');
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    if (isset($_GET['code'])) {
        $session->requestAccessToken($_GET['code']);
        $api->setAccessToken($session->getAccessToken());
    } else {
        header('Location: ' . $session->getAuthorizeUrl());
        die();
    }

### Getting a list of new releases

    <?php
    $releases = $api->getNewReleases(array(
        'country' => 'se'
    ));

    foreach ($releases->albums->items as $album) {
        echo '<a href="' . $album->external_urls->spotify . '">' . $album->name . '</a> <br>';
    }

### Getting a list of featured playlists

    <?php
    $playlists = $api->getFeaturedPlaylists(array(
        'country' => 'se',
        'locale' => 'sv_SE',
        'timestamp' => '2015-01-17T21:00:00', // Saturday night
    ));

    foreach ($playlists->playlists->items as $playlist) {
        echo '<a href="' . $playlist->external_urls->spotify . '">' . $playlist->name . '</a> <br>';
    }

### Getting a list of Spotify categories

    <?php
    $categories = $api->getCategoriesList(array(
        'country' => 'se',
        'locale' => 'sv_SE',
        'limit' => 10,
        'offset' => 0,
    ));

    foreach ($categories->categories->items as $category) {
        echo '<a href="' . $category->href . '">' . $category->name . '</a><br>';
    }

### Getting a single Spotify category

    <?php
    $category = $api->getCategory('dinner', array(
        'country' => 'se'
    ));

    echo '<a href="' . $category->href . '">' . $category->name . '</a>';

### Getting a category's playlists

    <?php
    $playlists = $api->getCategoryPlaylists('dinner', array(
        'country' => 'se',
        'limit' => 10,
        'offset' => 0
    ));

    foreach ($playlists->playlists->items as $playlist) {
        echo '<a href="' . $playlist->href . '">' . $playlist->name . '</a><br>';
    }

Please see the [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html) for more available options for each method.
