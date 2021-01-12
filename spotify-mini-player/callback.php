<?php

require './vendor/autoload.php';
require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

//
// Read settings from JSON
//

$success = false;
$settings = getSettings($w);

$oauth_client_id = $settings->oauth_client_id;
$oauth_client_secret = $settings->oauth_client_secret;
$oauth_redirect_uri = $settings->oauth_redirect_uri;

$retry = true;
$nb_retry = 0;
while ($retry) {
    try {
        $session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);

        if (!empty($_GET['code'])) {

            // Request a access token using the code from Spotify
            $ret = $session->requestAccessToken($_GET['code']);

            if ($ret == true) {
                $api = new SpotifyWebAPI\SpotifyWebAPI();
                // Set the code on the API wrapper
                $api->setAccessToken($session->getAccessToken());
                $user = $api->me();

                $ret = updateSetting($w,'oauth_access_token',$session->getAccessToken());
                if($ret == false) {
                    $retry = false;
                    $message = "There was an error when updating settings";
                    exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                }

                $ret = updateSetting($w,'oauth_refresh_token',$session->getRefreshToken());
                if($ret == false) {
                    $retry = false;
                    $message = "There was an error when updating settings";
                    exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                }

                $ret = updateSetting($w,'country_code',$user->country);
                if($ret == false) {
                    $retry = false;
                    $message = "There was an error when updating settings";
                    exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                }

                $ret = updateSetting($w,'display_name',$user->display_name);
                if($ret == false) {
                    $retry = false;
                    $message = "There was an error when updating settings";
                    exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                }

                $ret = updateSetting($w,'userid',$user->id);
                if($ret == false) {
                    $retry = false;
                    $message = "There was an error when updating settings";
                    exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                }

                if (isUserPremiumSubscriber($w)) {
                    $ret = updateSetting($w,'output_application','CONNECT');
                    if($ret == false) {
                        $retry = false;
                        $message = "There was an error when updating settings";
                        exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
                    }
                }

                $success = true;
                $retry = false;
            } else {
                $message = "There was an error during the authentication (could not get token)";
            }
        } else {
            $message = "There was an error during the authentication (could not get code)";
        }

    }
    catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
        logMsg($w,'Error(callback.php): retry '.$nb_retry.' (exception '.jTraceEx($e).')');
        if (strpos(strtolower($e->getMessage()), 'ssl') !== false) {
            // cURL transport error: 35 LibreSSL SSL_connect: SSL_ERROR_SYSCALL error #251
            // https://github.com/vdesabou/alfred-spotify-mini-player/issues/251
            // retry any SSL error
            ++$nb_retry;
        } else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 504) {
            // retry
            if ($nb_retry > 3) {
                $message = "There was an error during authentication (exception " . jTraceEx($e) . ")";
                $retry = false;
            }
            ++$nb_retry;
            sleep(5);
        } else {
            $message = "There was an error during authentication (exception " . jTraceEx($e) . ")";
            $retry = false;
        }
    }
}

exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
?>

<html>
<head>
    <title>Alfred Spotify Mini Player Setup</title>

    <link rel="stylesheet" href="include/setup/style/normalize.css" />
    <link rel="stylesheet" href="include/setup/style/style.css">
    <link rel="stylesheet" href="https://alfred-spotify-mini-player.com/css/main.css">
</head>

<body>
    <div id="wrapper" class="wrapper">
    <?php if($success): ?>
        <section>
            <h1>🎉 Alfred Spotify Mini Player is setup 🎉</h1>
            <p>
                You should be able to start using Alfred Spotify Mini Player now!
            </p>
            <img src="https://media4.giphy.com/media/lTZvj21tbQSTC/giphy.gif?cid=e1bb72ffuu13xl14uo27hi6zoxx060wif2j88l6r5vd3odme&rid=giphy.gif" alt="gif">
            <p>
                You can now close this window.
            </p>
        </section>
    <?php else: ?>
        <section>
            <h1>⚠️ Alfred Spotify Mini Player could not be set up correctly ⚠️</h1>
            <p>
                Error message: <code><?php print $_GET['error']; print $message; ?></code>
            </p>

            <img src="https://media.giphy.com/media/Qvm2704d1Dqus/giphy.gif" alt="gif">

            <p>👉 You'll need to try logging in again. If you're still unable to proceed, follow this <a href="https://alfred-spotify-mini-player.com/articles/support/">link</a> to get some help</p>
        </section>
    <?php endif; ?>
    </div>
</body>