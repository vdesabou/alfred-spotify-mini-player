---
layout: default
title: Managing a user's playlists
---

There are lots of operations involving user's playlists that can be performed.
First off, you'll need an access token with the correct scope.
In this example, we'll request all available playlist scopes, in a real world application you'll probably won't need all of them so just request the ones you need.

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
                'playlist-modify-private',
                'playlist-modify-public',
                'playlist-read-private',
            )
        )));
        die();
    }

### Listing a user's playlists

    <?php
    $playlists = $api->getUserPlaylists('USER_ID', array(
        'limit' => 5
    ));

    foreach ($playlists->items as $playlist) {
        echo '<a href="' . $playlist->external_urls->spotify . '">' . $playlist->name . '</a> <br>';
    }

### Getting info about a specific playlist

    <?php
    $playlist = $api->getUserPlaylist('USER_ID', 'PLAYLIST_ID');

    echo $playlist->name;

### Getting all tracks in a playlist

    <?php
    $playlistTracks = $api->getUserPlaylistTracks('USER_ID', 'PLAYLIST_ID');

    foreach ($playlistTracks->items as $track) {
        $track = $track->track;

        echo '<a href="' . $track->external_urls->spotify . '">' . $track->name . '</a> <br>';
    }

### Creating a new playlist

    <?php
    $api->createUserPlaylist('USER_ID', array(
        'name' => 'My shiny playlist'
    ));


### Updating the details of a user's playlist

    <?php
    $api->updateUserPlaylist('USER_ID', 'PLAYLIST_ID', array(
        'name' => 'New name'
    ));


### Adding tracks to a user's playlist

    <?php
    $api->addUserPlaylistTracks('USER_ID', 'PLAYLIST_ID', array(
        '1oR3KrPIp4CbagPa3PhtPp',
        '6lPb7Eoon6QPbscWbMsk6a'
    ));

### Delete tracks from a user's playlist

    <?php
    $tracks = array(
        array('id' => '1oR3KrPIp4CbagPa3PhtPp'),
        array('id' => '6lPb7Eoon6QPbscWbMsk6a')
    );

    $api->deleteUserPlaylistTracks('USER_ID', 'PLAYLIST_ID', $tracks, 'SNAPSHOT_ID');

### Replacing all tracks in a user's playlist with new ones

    <?php
    $api->replaceUserPlaylistTracks('USER_ID', 'PLAYLIST_ID', array(
        '0eGsygTp906u18L0Oimnem',
        '1lDWb6b6ieDQ2xT7ewTC3G'
    ));

### Reorder the tracks in a user's playlist

    <?php
    $api->reorderUserPlaylistTracks('USER_ID', 'PLAYLIST_ID', array(
        'range_start' => 1,
        'range_length' => 5,
        'insert_before' => 10,
        'snapshot_id' => 'SNAPSHOT_ID'
    ));

Please see the [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html) for more available options for each method.
