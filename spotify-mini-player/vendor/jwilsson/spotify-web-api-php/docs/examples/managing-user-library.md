---
layout: default
title: Managing a user's library
---

There are lots of operations involving a user's library that can be performed.
First off, you'll need an access token with the correct scope.
In this example, we'll request all available library scopes, in a real world application you'll probably won't need all of them so just request the ones you need.

    <?php
    require 'vendor/autoload.php';

    $session = new SpotifyWebAPI\Session('SPOTIFY_CLIENT_ID', 'SPOTIFY_CLIENT_SECRET', 'SPOTIFY_REDIRECT_URI');
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    if (isset($_GET['code'])) {
        $session->requestAccessToken($_GET['code']);
        $api->setAccessToken($session->getAccessToken());
    } else {
        header('Location: ' . $session->getAuthorizeUrl(array(
            'scope' => array(
                'user-library-modify',
                'user-library-read',
            )
        )));
        die();
    }

### Listing the tracks in a user's library

    <?php
    $tracks = $api->getMySavedTracks('USER_ID', array(
        'limit' => 5
    ));

    foreach ($tracks->items as $track) {
        $track = $track->track;

        echo '<a href="' . $track->external_urls->spotify . '">' . $track->name . '</a> <br>';
    }

### Adding tracks to a user's library

    <?php
    $api->addMyTracks(array(
        '1oR3KrPIp4CbagPa3PhtPp',
        '6lPb7Eoon6QPbscWbMsk6a'
    ));

### Deleting tracks from a user's library

    <?php
    $api->deleteMyTracks(array(
        '1oR3KrPIp4CbagPa3PhtPp',
        '6lPb7Eoon6QPbscWbMsk6a'
    ));

### Checking if tracks are present in a user's library

    <?php
    $contains = $api->myTracksContains(array(
        '0eGsygTp906u18L0Oimnem',
        '1lDWb6b6ieDQ2xT7ewTC3G'
    ));

    var_dump($contains);

Please see the [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html) for more available options for each method.
