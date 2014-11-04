<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

//rtf document
$rtf = new PHPRtfLite();
$rtf->setHyphenation();

$header = $rtf->addHeader();
$header->writeText("PHPRtfLite class library. This is page - <pagenum> of <pagetotal> -", new PHPRtfLite_Font, new PHPRtfLite_ParFormat);

$table = $header->addTable();
$table->addRows(1);
$table->addColumnsList(array(3,4,3));

$cell = $table->getCell(1, 1);
$cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_LEFT);
$cell->writeText('Cell with left alignment');

$cell = $table->getCell(1, 2);
$cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER);
$cell->writeText('Cell with center alignment');

$cell = $table->getCell(1, 3);
$cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_RIGHT);
$cell->writeText('Cell with right alignment');

$header->writeText('Text after table');

$section = $rtf->addSection();
$section->writeText('
Text with hyphenation activated:

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus eleifend sem dapibus erat congue quis pharetra neque porta. Cras orci quam, consectetur id vestibulum non, iaculis sit amet neque. Maecenas in tincidunt urna. Curabitur quis justo ac augue volutpat laoreet. Pellentesque molestie vestibulum diam, eu facilisis est tincidunt et. Phasellus scelerisque elit et enim luctus mattis. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Curabitur semper, felis vehicula pharetra laoreet, erat augue porttitor nisl, quis ullamcorper purus felis vel augue. Etiam quis massa ultrices nisi rutrum auctor. Integer mollis sapien quis mauris suscipit nec dictum felis interdum.

Sed ut arcu metus. Cras lorem dolor, pharetra ut congue vel, pellentesque ac arcu. Praesent non lectus nulla. Nam mollis velit a dui lobortis tincidunt. Duis sed ipsum elit. Pellentesque urna risus, commodo in fringilla sit amet, euismod sit amet lectus. Nulla purus elit, porttitor ac viverra nec, consectetur at arcu. Pellentesque eget magna eget justo euismod mattis ut nec odio. Proin gravida est eu augue ullamcorper eu mollis orci convallis. Curabitur adipiscing lacus vitae ante accumsan ut ornare elit ultricies. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos.');

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');