<?php

// Turn off all error reporting
error_reporting(0);

require './spotify-mini-player/src/functions.php';

// Load and use David Ferguson's Workflows.php class
require_once './spotify-mini-player/src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

displayNotificationWithArtwork("Update library/playlist was killed!", 'icon.png');
if ( file_exists($w->data() . "/update_library_in_progress") )
	unlink($w->data() . "/update_library_in_progress");

exec("kill -9 $(ps -efx | grep \"php\" | egrep \"update_|php -S localhost:15298|ADDTOPLAYLIST|UPDATE_\" | grep -v grep | awk '{print $2}')");

?>