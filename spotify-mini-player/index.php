<?php

require './vendor/autoload.php';
require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$oauth_client_id = getSetting($w,'oauth_client_id');
$oauth_client_secret = getSetting($w,'oauth_client_secret');
$oauth_redirect_uri = getSetting($w,'oauth_redirect_uri');

try {
    $session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);

    // Get the authorization URL and send the user there
    header('Location: '.$session->getAuthorizeUrl(array(
                'scope' => array(
                    'user-library-read',
                    'user-read-private',
                    'user-library-modify',
                    'user-follow-modify',
                    'user-follow-read',
                    'playlist-read-private',
                    'playlist-modify-public',
                    'playlist-modify-private',
                    'playlist-read-collaborative',
                    'user-top-read',
                    'user-read-recently-played',
                    'user-read-playback-state',
                    'user-modify-playback-state',
                    'user-read-currently-playing',
                    'user-read-playback-position'),
                'show_dialog' => true, )));
} catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
    echo 'There was an error during the authentication flow (exception '.jTraceEx($e).')';
    displayNotificationWithArtwork($w, 'Web server killed', './images/warning.png', 'Error!');
    exec("kill -9 $(ps -efx | grep \"php -S 127.0.0.1:15298\"  | grep -v grep | awk '{print $2}')");

    return;
}
