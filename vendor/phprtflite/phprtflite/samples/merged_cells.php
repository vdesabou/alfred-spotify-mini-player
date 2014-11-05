<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();
$sect = $rtf->addSection();

$table = $sect->addTable();

$table->addRows(5, 0.75);
$table->addColumnsList(array(3, 3, 3, 3, 3));

$table->mergeCellRange(1, 1, 3, 1);
$table->writeToCell(1, 1, 'Vertical merged cells.', new PHPRtfLite_Font(), new PHPRtfLite_ParFormat());
$border = PHPRtfLite_Border::create($rtf, 1, '#ff0000');
$table->setBorderForCellRange($border, 1, 1, 3, 1);

$table->mergeCellRange(1, 3, 1, 5);
$table->writeToCell(1, 3, 'Horizontal merged cells', new PHPRtfLite_Font(), new PHPRtfLite_ParFormat());
$border = PHPRtfLite_Border::create($rtf, 1, '#0000ff');
$table->setBorderForCellRange($border, 1, 3, 1, 5);

$table->mergeCellRange(3, 3, 5, 5);
$table->writeToCell(3, 3, 'Horizontal and vertical merged cells', new PHPRtfLite_Font(), new PHPRtfLite_ParFormat());
$border = PHPRtfLite_Border::create($rtf, 1, '#00ff00');
$table->setBorderForCellRange($border, 3, 3, 5, 5);

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');