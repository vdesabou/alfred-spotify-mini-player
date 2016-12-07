---
layout: default
title: Following artists, playlists and users
---

A Spotify user can follow artists, playlists and users.
The API contains methods for all of this functionality.

### Following an artist or user

    <?php
    $api->followArtistsOrUsers('artist', 'ARTIST_ID');

### Unfollowing an artist or user

    <?php
    $api->unfollowArtistsOrUsers('artist', 'ARTIST_ID');

### Checking if a user is following an artist or user

    <?php
    $following = $api->currentUserFollows('user', 'spotify');

    var_dump($following);

### Following a playlist

    <?php
    $api->followPlaylist('USER_ID', 'PLAYLIST_ID');

### Unfollowing a playlist

    <?php
    $api->unfollowPlaylist('USER_ID', 'PLAYLIST_ID');

### Checking if user(s) are following a playlist

    <?php
    $users = array(
        'USER_1',
        'USER_2',
    );

    $api->userFollowsPlaylist('OWNER_ID', 'PLAYLIST_ID', array(
        'ids' => $users
    ));

Please see the [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html) for more available options for each method.
