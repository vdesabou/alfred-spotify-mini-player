<?php
error_reporting(-1);
ini_set('display_errors', 1);

require 'vendor/autoload.php';

$session = new SpotifyWebAPI\Session(
    '40eba578acc44b5cae8680ccd5542b4f',
    '8f9584aad3fa4edca92fac5d6858a38d',
    'http://localhost:8888/spotify-web-api-php/demo.php'
);

$api = new SpotifyWebAPI\SpotifyWebAPI();

if (isset($_GET['code'])) {
    $session->requestAccessToken($_GET['code']);
    $api->setAccessToken($session->getAccessToken());

    print_r($api->getAudioAnalysis('0eGsygTp906u18L0Oimnem'));
} else {
    $scopes = [
        'scope' => [
            'user-read-email',
            'user-library-modify',
        ],
    ];

    header('Location: ' . $session->getAuthorizeUrl($scopes));
}
