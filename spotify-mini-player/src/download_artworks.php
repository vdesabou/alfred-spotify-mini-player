<?php
require './src/action.php';
$type = $argv[1];
$query = serialize(array("", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", "", ""));
main($query, $type, $type);
