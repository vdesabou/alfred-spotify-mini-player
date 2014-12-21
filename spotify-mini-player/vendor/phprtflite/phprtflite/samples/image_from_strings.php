<?php

$dir = dirname(__FILE__);
$jpeg = $dir . '/sources/rtf_thumb.jpg';
$png = $dir . '/sources/html.png';
$wmf = $dir . '/sources/test.wmf';

require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

// rtf document
$rtf = new PHPRtfLite();


$sect = $rtf->addSection();
$sect->writeText('Images (as files) with PHPRtfLite.');

$table = $sect->addTable();
$table->addRows(4);
$table->addColumnsList(array(4, 4));

$table->writeToCell(1, 1, 'JPEG');
$table->addImageToCell(1, 2, $jpeg);

$table->writeToCell(2, 1, 'PNG');
$table->addImageToCell(2, 2, $png);

$table->writeToCell(3, 1, 'WMF');
$table->addImageToCell(3, 2, $wmf, null, 4);

$missingImage = '/some/image/that/does/not/!exist!.png';
$table->writeToCell(4, 1, 'Missing image or not readable');
$table->addImageToCell(4, 2, $missingImage);


$sect = $rtf->addSection();
$sect->writeText('Images (as strings) with PHPRtfLite.');

$table = $sect->addTable();
$table->addRows(3);
$table->addColumnsList(array(4, 4));

$table->getCell(1, 1)->writeText('JPEG');
$table->addImageFromStringToCell(1, 2, file_get_contents($jpeg), PHPRtfLite_Image::TYPE_JPEG);
// alternative code:
// $table->getCell(1, 2)->addImageFromString(file_get_contents($jpeg), PHPRtfLite_Image::TYPE_JPEG);

$table->getCell(2, 1)->writeText('PNG');
$table->addImageFromStringToCell(2, 2, file_get_contents($png), PHPRtfLite_Image::TYPE_PNG);
// alternative code:
// $table->getCell(2, 2)->addImageFromString(file_get_contents($png), PHPRtfLite_Image::TYPE_PNG);

$table->getCell(3, 1)->writeText('WMF');
$table->addImageFromStringToCell(3, 2, file_get_contents($wmf), PHPRtfLite_Image::TYPE_WMF, null, 4);
// alternative code:
// $table->getCell(3, 2)->addImageFromString(file_get_contents($wmf), PHPRtfLite_Image::TYPE_WMF);


// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');
