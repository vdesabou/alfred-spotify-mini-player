<?php

// Turn off all error reporting
error_reporting(0);

require_once('workflows.php');

$w = new Workflows();

unlink($w->data() . "/update_library_in_progress");
exec("kill -9 $(ps -efx | grep \"|||||||update_library||\" | awk '{print $2}')");    


echo "Update library was killed!";

?>