<?php
// thanks to http://www.alfredforum.com/topic/1788-prevent-flash-of-no-result
mb_internal_encoding("UTF-8");
date_default_timezone_set('America/New_York');

require './vendor/autoload.php';
require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$response = Array();

if(!array_key_exists('id', $_GET) || !array_key_exists('secret', $_GET)) {
    $response["status"] = "error";
    $response["message"] = "You're missing some data!";
    echo json_encode($response);
    exit();
}

// Test connection
$session = new SpotifyWebAPI\Session($_GET["id"], $_GET["secret"], 'http://localhost:15298/callback.php');

$scopes = array(
    'user-library-read',
    'user-read-email',
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
    'user-read-playback-position');

try {
    $session->requestCredentialsToken($scopes);
} catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
    $response["status"] = "error";

    $response["message"] = "Invalid data - ";
    if ($e->getMessage() == "Invalid client") {
        $response["message"] .= "invalid ID";
    } elseif($e->getMessage() == "Invalid client secret") {
        $response["message"] .= "incorrect secret";
    } else {
        $response["message"] .= $e->getMessage();
    }
    echo json_encode($response);
    exit();
}

// Save data
updateSetting($w,'oauth_client_id',$_GET["id"]);
updateSetting($w,'oauth_client_secret',$_GET["secret"]);

$response["status"] = "success";
$response["message"] = "Your Client ID and Client Secret are correct! Make sure to do next step now !";
echo json_encode($response);
exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
exit();