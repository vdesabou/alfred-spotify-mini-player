---
layout: default
title: Managing a user's profile
---

There are lots of operations involving a user's profile that can be performed.
First off, you'll need an access token with the correct scope.
In this example, we'll request all available profile scopes, in a real world application you'll probably won't need all of them so just request the ones you need.

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
                'user-follow-modify',
                'user-follow-read',
                'user-read-email',
                'user-read-private',
            )
        )));
        die();
    }

### Getting the current user's profile

    <?php
    $me = $api->me();

    echo $me->display_name;

### Getting any user's profile

    <?php
    $user = $api->getUser('USER_ID');

    echo $user->display_name;

### Following another user or an artist

    <?php
    $api->followArtistsOrUsers('artist', '74ASZWbe4lXaubB36ztrGX');

### Unfollowing another user or an artist

    <?php
    $api->unfollowArtistsOrUsers('artist', '74ASZWbe4lXaubB36ztrGX');

### Checking if the current user is following another user or an artist

    <?php
    $following = $api->currentUserFollows('artist', '74ASZWbe4lXaubB36ztrGX');

    var_dump($following);

Please see the [method reference]({{ site.baseurl }}/method-reference/spotifywebapi.html) for more available options for each method.
