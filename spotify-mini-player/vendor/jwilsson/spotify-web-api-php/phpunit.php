<?php
error_reporting(-1);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('UTC');

// Test helper functions
function get_fixture($fixture)
{
    $fixture = __DIR__ . '/tests/fixtures/' . $fixture . '.json';
    $fixture = file_get_contents($fixture);

    return json_decode($fixture);
}
