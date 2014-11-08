<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();

$sect = $rtf->addSection();
$table = $sect->addTable();
$table->addRows(1, 1);
$table->addRows(1, 2);
$table->addColumnsList(array(5, 5, 5));


$cell = $table->getCell(1, 1);
$cell->writeText('<b>Checkbox</b>');

$cell = $table->getCell(2, 1);
$fontCheckbox = new PHPRtfLite_Font('20', 'Arial', '#cc3333', '#8888cc');
$checkbox = $cell->addCheckbox($fontCheckbox);
$cell->writeText('red checkbox with blue background');
$cell->addEmptyParagraph();

$checkbox = $cell->addCheckbox();
$checkbox->setChecked();
$cell->writeText('checked checkbox');
$cell->addEmptyParagraph();

$checkbox = $cell->addCheckbox();
$checkbox->setChecked();
$checkbox->setSize(40);
$cell->writeText('big checked checkbox');


$cell = $table->getCell(1, 2);
$cell->writeText('<b>Dropdown</b>');

$cell = $table->getCell(2, 2);
$cell->writeText('dropdown labels');
$fontDropdown = new PHPRtfLite_Font('12', 'Arial', '#cc3333', '#cccccc');
$dropdown = $cell->addDropdown($fontDropdown);
$dropdown->addItem('Хороший день');
$dropdown->addItem('अच्छा दिन');
$dropdown->addItem('Buenos días');
$dropdown->addItem('Guten Tag');
$dropdown->setDefaultValue('Guten Tag');


$cell = $table->getCell(1, 3);
$cell->writeText('<b>Textfeld</b>');

$cell = $table->getCell(2, 3);
$cell->writeText('textfield label');
$fontTextfield = new PHPRtfLite_Font('12', 'Arial', '#cc3333', '#cceecc');
$textfield = $cell->addTextfield($fontTextfield);
$textfield->setDefaultValue('Lorem ipsum.');


// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');