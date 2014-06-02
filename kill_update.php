<?php

// Turn off all error reporting
error_reporting(0);

// Require the bundler.
require_once('alfred.bundler.php');


// Load and use David Ferguson's Workflows.php class
//$files = __load( "Workflows" );
require_once('workflows.php');
$w = new Workflows;

unlink($w->data() . "/update_library_in_progress");
exec("kill -9 $(ps -efx | grep \"php\" | grep \"update_\" | awk '{print $2}')");    


echo "Update library/playlist was killed!";

?>