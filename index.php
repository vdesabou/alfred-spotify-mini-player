<?php

require 'vendor/autoload.php';
require './src/functions.php';


// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');


//
// Read settings from DB
//
$getSettings = 'select oauth_client_id,oauth_client_secret,oauth_redirect_uri,oauth_access_token from settings';
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
	displayNotification("Error[index.php]: cannot set PDO settings");
	$dbsettings=null;
	return;
}

try {
	$stmt = $dbsettings->prepare($getSettings);
	$settings = $stmt->execute();

} catch (PDOException $e) {
	displayNotification("Error[index.php]: cannot set prepare settings");
	return;
}

try {
	$setting = $stmt->fetch();
}
catch (PDOException $e) {
	displayNotification("Error[index.php]: cannot set fetch settings");
	return;
}

$oauth_client_id = $setting[0];
$oauth_client_secret = $setting[1];
$oauth_redirect_uri = $setting[2];

try {
	$session = new SpotifyWebAPI\Session($oauth_client_id, $oauth_client_secret, $oauth_redirect_uri);

	// Get the authorization URL and send the user there
	header('Location: ' . $session->getAuthorizeUrl(array(
		'scope' => array(   'user-library-read',
							'user-read-email',
							'user-read-private',
							'user-library-modify',
							'playlist-read-private',
							'playlist-modify-public',
							'playlist-modify-private'),
		'show_dialog' => true)));
}
catch (SpotifyWebAPI\SpotifyWebAPIException $e) {
	echo "There was an error during the authentication flow (exception " . $e . ")";
	displayNotification("Web server killed");
	exec("kill -9 $(ps -efx | grep \"php -S localhost:15298\"  | grep -v grep | awk '{print $2}')");
	return;
}
?>