<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

$rowCount = 5;
$rowHeight = 1;
$columnCount = 4;
$columnWidth = 3;

PHPRtfLite::registerAutoloader();
$rtf = new PHPRtfLite();
$sect = $rtf->addSection();

$table = $sect->addTable();
$table->addRows($rowCount, $rowHeight);
$table->addColumnsList(array_fill(0, $columnCount, $columnWidth));

for ($rowIndex = 1; $rowIndex <= $rowCount; $rowIndex++) {
    for ($columnIndex = 1; $columnIndex <= $columnCount; $columnIndex++) {
        $cell = $table->getCell($rowIndex, $columnIndex);
        $cell->writeText("Cell $rowIndex:$columnIndex");
        $cell->setTextAlignment(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER);
        $cell->setVerticalAlignment(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER);
    }
}

$borderTop = new PHPRtfLite_Border($rtf);
$borderTop->setBorderTop(new PHPRtfLite_Border_Format(2, '#f33'));
$table->setBorderForCellRange($borderTop, 1, 1, 1, $columnCount);
$borderBottom = new PHPRtfLite_Border($rtf);
$borderBottom->setBorderBottom(new PHPRtfLite_Border_Format(2, '#33f'));
$table->setBorderForCellRange($borderBottom, $rowCount, 1, $rowCount, $columnCount);

$sect->writeText('Table with cell paddings
(Using Microsoft Word top and bottom cell paddings are applied to all cells in a row)');
$table = $sect->addTable();
$table->addRows(4);
$table->addColumnsList(array_fill(0, 4, $columnWidth));

$cell = $table->getCell(1, 1);
$cell->writeText('cell 1:1 (with left padding)');
$cell->setPaddingLeft(1);

$cell = $table->getCell(2, 2);
$cell->writeText('cell 2:2 (with right padding)');
$cell->setPaddingRight(1);

$cell = $table->getCell(3, 3);
$cell->writeText('cell 3:3 (with top padding)');
$cell->setPaddingTop(1);

$cell = $table->getCell(4, 4);
$cell->writeText('cell 4:4 (with bottom padding)');
$cell->setPaddingBottom(1);


$table->addRow(1.5);
$cell = $table->getCell(5, 1);
$cell->writeText('cell 5:1 (with left padding)');
$cell->setPaddingLeft(1);

$cell = $table->getCell(5, 2);
$cell->writeText('cell 5:2 (with right padding)');
$cell->setPaddingRight(1);

$cell = $table->getCell(5, 3);
$cell->writeText('cell 5:3 (with top padding)');
$cell->setPaddingTop(1);

$cell = $table->getCell(5, 4);
$cell->writeText('cell 5:4 (with bottom padding)');
$cell->setPaddingBottom(1);


// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');