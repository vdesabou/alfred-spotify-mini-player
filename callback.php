<?php

require 'vendor/autoload.php';
require './src/functions.php';


// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');


//
// Read settings from DB
//
$getSettings = 'select oauth_client_id,oauth_client_secret,oauth_redirect_uri from settings';
$dbfile = $w->data() . '/settings.db';

try {
	$dbsettings = new PDO("sqlite:$dbfile", "", "", array(PDO::ATTR_PERSISTENT => true));
	$dbsettings->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbsettings->query("PRAGMA synchronous = OFF");
	$dbsettings->query("PRAGMA journal_mode = OFF");
	$dbsettings->query("PRAGMA temp_store = MEMORY");
	$dbsettings->query("PRAGMA count_changes = OFF");
	$dbsettings->query("PRAGMA PAGE_SIZE = 4096");
	$dbsettings->query("PRAGMA default_cache_size=700000");
	$dbsettings->query("PRAGMA cache_size=700000");
	$dbsettings->query("PRAGMA compile_options");
} catch (PDOException $e) {
	displayNotification("Error[callback.php]: cannot set PDO settings");
	$dbsettings=null;
	return;
}

try {
	$stmt = $dbsettings->prepare($getSettings);
	$settings = $stmt->execute();

} catch (PDOException $e) {
	displayNotification("Error[callback.php]: cannot prepare settings");
	$dbsettings=null;
	return;
}

try {
	$setting = $stmt->fetch();
}
catch (PDOException $e) {
	displayNotification("Error[callback.php]: cannot fetch settings");
	return;
}

$oauth_client_id = $setting[0];
$oauth_client_secret = $setting[1];
$oauth_redirect_uri = $setting[2];

try {
	$session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);

	if (!empty($_GET['code'])) {

		// Request a access token using the code from Spotify
		$ret = $session->requestToken($_GET['code']);

		if($ret == true) {
			$api = new SpotifyWebAPI\SpotifyWebAPI();
			// Set the code on the API wrapper
			$api->setAccessToken($session->getAccessToken());
			$user = $api->me();

			$updateSettings = "update settings set oauth_access_token=:oauth_access_token,oauth_expires=:oauth_expires,oauth_refresh_token=:oauth_refresh_token,country_code=:country_code,display_name=:display_name,userid=:userid";
			try {
				$stmt = $dbsettings->prepare($updateSettings);
				$stmt->bindValue(':oauth_access_token', $session->getAccessToken());
				$stmt->bindValue(':oauth_expires', time());
				$stmt->bindValue(':oauth_refresh_token', $session->getRefreshToken());
				$stmt->bindValue(':country_code', $user->country);
				$stmt->bindValue(':display_name', $user->display_name);
				$stmt->bindValue(':userid', $user->id);
				$stmt->execute();

			} catch (PDOException $e) {
				handleDbIssuePdo($theme, $dbsettings);
				$dbsettings=null;;
				displayNotification("Web server killed");
				exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
				return;
			}

			echo "Hello $user->display_name ! You are now successfully logged and you can close this window.";

		} else {
			echo "There was an error during the authentication (could not get token)";
		}
	} else {
		echo "There was an error during the authentication (could not get code)";
	}

}
catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	echo "There was an error during the authentication (exception " . $e . ")";
}

displayNotification("Web server killed");
exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");



?>