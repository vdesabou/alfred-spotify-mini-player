<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();
$sect = $rtf->addSection();

$table = $sect->addTable();
$table->addRows(1, 1);
$table->addColumnsList(array(5, 5));

$cell = $table->getCell(1, 1);
$cell->writeText('Before nested table');
$nestedTable = $cell->addTable();
$cell->writeText('default cell I');

$nestedTable->addRows(2, 1);
$nestedTable->addColumnsList(array(4));
$cell = $nestedTable->getCell(1, 1);
$cell->writeText('Before double nested table' . "\r\n");
$doubleNestedTable = $cell->addTable();
$doubleNestedTable->addRows(2, 1);
$doubleNestedTable->addColumnsList(array(2, 2));

$cell = $doubleNestedTable->getCell(1, 1);
$cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_RIGHT);
$cell->setBackgroundColor('#AA3333');
$cell->writeText('Before three times nested table');
$threeTimesNestedTable = $cell->addTable();
$threeTimesNestedTable->addRows(1, 1);
$threeTimesNestedTable->addColumnsList(array(2));
$threeTimesNestedTable->writeToCell(1, 1, 'three times nested table I');
$cell->writeText('text between nested tables');
$threeTimesNestedTable = $cell->addTable();
$threeTimesNestedTable->addRows(1, 1);
$threeTimesNestedTable->addColumnsList(array(2));
$threeTimesNestedTable->writeToCell(1, 1, 'three times nested table II');
$doubleNestedTable->writeToCell(1, 1, 'double nested cell 1:1');
$doubleNestedTable->writeToCell(2, 1, 'double nested cell 2:1');
$doubleNestedTable->writeToCell(1, 2, 'double nested cell 1:2');
$doubleNestedTable->writeToCell(2, 2, 'double nested cell 2:2');

$nestedTable->writeToCell(2, 1, 'nested cell');

$table->writeToCell(1, 2, 'default cell II');

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');