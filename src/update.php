<?php
require './src/functions.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\MiniPlayer;

require_once './vendor/autoload.php';

// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';

$w = new Workflows('com.vdesabou.spotify.mini.player');

if (! $w->internet()) {
	displayNotificationWithArtwork("Error: No internet connection", './images/warning.png');
	return;
}
touch($w->data() . "/update_library_in_progress");
$w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time(), 'update_library_in_progress');

$tcpport = getFreeTcpPort();
exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:update_library:" . $tcpport . ":" . uniqid() . "\"'");

$server = IoServer::factory(
	new HttpServer(
		new WsServer(
			new MiniPlayer()
		)
	),
	$tcpport
);
// FIX THIS: server will exit when done
// Did not find a way to set a timeout
$server->run();
?>
