<?php

require('./src/functions.php');

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\MiniPlayer;

require_once './vendor/autoload.php';

$tcpport = getFreeTcpPort();
exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:random_track:" . $tcpport . ":" . uniqid() . "\"'");

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
return;

?>