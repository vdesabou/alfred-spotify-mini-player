<?php

// Turn off all error reporting
error_reporting(0);

require './src/functions.php';

// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

displayNotificationWithArtwork("Update library/playlist was killed!", '05F86AA1-D3EE-4409-9A58-898B36FFE503.png');
if ( file_exists($w->data() . "/update_library_in_progress") )
	unlink($w->data() . "/update_library_in_progress");

exec("kill -9 $(ps -efx | grep \"php\" | egrep \"update_|update.php|add_to_alfred_playlist.php|ADDTOPLAYLIST\" | grep -v grep | awk '{print $2}')");

?>