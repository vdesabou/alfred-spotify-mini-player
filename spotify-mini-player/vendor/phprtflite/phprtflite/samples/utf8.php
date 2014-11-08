<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

//Rtf document
$rtf = new PHPRtfLite();

//Font
$times12 = new PHPRtfLite_Font(12, 'Times new Roman');

//Section
$sect = $rtf->addSection();
//Write utf-8 encoded text.
//Text is from file. But you can use another resouce: db, sockets and other
$sect->writeText(file_get_contents($dir . '/sources/utf8.txt'), $times12, null);

// save rft document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');
