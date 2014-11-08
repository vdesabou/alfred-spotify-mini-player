<?php
$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

//Font formats
$font1 = new PHPRtfLite_Font(11, 'Times new Roman', '#000055');

//Paragraph formats
$parFC = new PHPRtfLite_ParFormat('center');

$parFL = new PHPRtfLite_ParFormat('left');

//Rtf document
$rtf = new PHPRtfLite();

//section
$sect = $rtf->addSection();
$sect->writeText('Chess tournamet information (write your data)' . "\n", new PHPRtfLite_Font(14, 'Arial'), new PHPRtfLite_ParFormat());

$chessPlayers = array('Mike Smith', 'Jim Morgan', 'Jochgan Berg', 'Bill Scott', 'Bill Martines', 'John Po', 'Aleck Harrison', 'Ann Scott', 'Johnatan Fredericson', 'Eva Carter');

/*$chessResults = (array(
				array(0  , 1  , 0.5, 1  , 0  , 1  , 0  , 0.5  , 1  , 1  ),
				array(0, 0, 0.5, 0.5, 0, 0, 0, 0, 0, 1),
				array(0.5, 1, 0, 1, 0.5, 1, 0.5, 0, 0, 1),
				array(0, 0.5, 0.5, 0, 0, 0.5, 0.5, 0, 0, 1),				


				array(1, 0.5, 1, 0, 0, 0.5, 0.5, 0, 0, 1),			

));*/

$count = count($chessPlayers);
$countCols = $count + 2;
$countRows = $count + 1;

$colWidth = ($sect->getLayoutWidth() - 5) / $count;

//table creating and rows ands columns adding
$table = $sect->addTable();
$table->addRows(1, 2);
$table->addRows($count, -0.6);

$table->addColumn(3);
for ($i = 1; $i <= count($chessPlayers); $i ++) {	
    $table->addColumn($colWidth);
}
$table->addColumn(2);

//borders
$border = PHPRtfLite_Border::create($rtf, 1, '#555555');
$table->setBorderForCellRange($border, 1, 1, $countRows, $countCols);

//top row
$table->rotateCellRange(PHPRtfLite_Table_Cell::ROTATE_RIGHT, 1, 2, 1, $countCols - 1);
$table->setVerticalAlignmentForCellRange(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER, 1, 2, 1, $countCols);

$i = 2;
foreach ($chessPlayers as $player) {
  	$table->writeToCell(1, $i, $player, $font1, null);
  	$table->writeToCell($i, 1, $player, $font1, new PHPRtfLite_ParFormat(), false);
        $border = PHPRtfLite_Border::create($rtf, 1, '#0000ff');
  	$table->setBorderForCellRange($border, $i, $i);
  	$table->setBackgroundForCellRange('#dddddd', $i, $i);
  	$i++;
}

//tournament result
/*$i = 1;
foreach ($chessResults as $playerResult) {
    $j = 1;
    $sum = 0;
    foreach ($playerResult as $result)  {
        if ($i != $j) {
            $table->writeToCell($i + 1, $j + 1, $result, new PHPRtfLite_Font(11, 'Times new Roman', '#7A2900'), new PHPRtfLite_ParFormat('center'));
            $sum += $result;
        }
        $j++;
    }
    $table->writeToCell($i + 1, $j + 1, '<b>'.$sum.'</b>', new PHPRtfLite_Font(11, 'Times new Roman', '#7A2900'), new PHPRtfLite_ParFormat('center'));
    $i++;
}*/

$fontBold = new PHPRtfLite_Font(11, 'Times new Roman', '#7A2900');
$fontBold->setBold();

$table->setTextAlignmentForCellRange('center', 2, 2, $countRows, $countCols);
$table->setFontForCellRange(new PHPRtfLite_Font(11, 'Times new Roman', '#7A2900'), 2, 2, $countRows, $countCols - 1);
$table->setFontForCellRange($fontBold, 2, $countCols, $countRows);

$table->writeToCell(1, $countCols, 'TOTAL', $font1, new PHPRtfLite_ParFormat('center'));

$border = PHPRtfLite_Border::create($rtf, 1.5, '#000000');
$table->setBorderForCellRange($border, 1, $countCols, $countRows, $countCols);
$borderFormat = new PHPRtfLite_Border_Format(1, '#0000ff', 'dash');
$border = new PHPRtfLite_Border($rtf, null, null, null, $borderFormat);
//Registry::$debug = true;
$table->setBorderForCellRange($border, 2, $countCols, $countRows - 1, $countCols);

$sect->writeText('Chess tournamet play-offs (write your data)' . "\n", new PHPRtfLite_Font(14, 'Arial'), new PHPRtfLite_ParFormat());

$countSmall = 5;
$countLarge = 6;

$smallWidth = '0.75';
$bigWidth = ($sect->getLayoutWidth() - $countSmall * $smallWidth) / $countLarge;

$table = $sect->addTable();
$table->addRows(16, -0.5);
$table->addColumnsList(array($smallWidth, $bigWidth, $bigWidth, $smallWidth, $smallWidth, $bigWidth, $bigWidth, $smallWidth, $smallWidth, $bigWidth, $bigWidth));

$table->setTextAlignmentForCellRange('center', 1, 1, 16, 11);
$table->setFontForCellRange(new PHPRtfLite_Font(11, 'Times new Roman', '#7A2900'), 1, 1, 16, 11);


$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 2, 1, 3, 3);
$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 6, 1, 7, 3);
$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 10, 1, 11, 3);
$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 14, 1, 15, 3);

$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 4, 5, 5, 7);
$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 12, 5, 13, 7);

$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 8, 9, 9, 11);
$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 14, 9, 15, 11);

$table->setBorderForCellRange(PHPRtfLite_Border::create($rtf, 1), 1, 10, 3, 11);

$table->writeToCell(2, 1, 'P1', $font1, null);
$table->writeToCell(3, 1, 'P8', $font1, null);
$table->writeToCell(6, 1, 'P2', $font1, null);
$table->writeToCell(7, 1, 'P7', $font1, null);
$table->writeToCell(10, 1, 'P3', $font1, null);
$table->writeToCell(11, 1, 'P6', $font1, null);
$table->writeToCell(14, 1, 'P4', $font1, null);
$table->writeToCell(15, 1, 'P5', $font1, null);

$table->writeToCell(1, 1, 'A1', $font1, null);
$table->writeToCell(5, 1, 'A2', $font1, null);
$table->writeToCell(9, 1, 'A3', $font1, null);
$table->writeToCell(13, 1, 'A4', $font1, null);

$table->writeToCell(3, 5, 'B1', $font1, null);
$table->writeToCell(4, 5, 'A1', $font1, null);
$table->writeToCell(5, 5, 'A2', $font1, null);

$table->writeToCell(11, 5, 'B2', $font1, null);
$table->writeToCell(12, 5, 'A3', $font1, null);
$table->writeToCell(13, 5, 'A4', $font1, null);

$table->writeToCell(7, 10, '1-st place', $font1, null);
$table->writeToCell(8, 9, 'B1', $font1, null);
$table->writeToCell(9, 9, 'B2', $font1, null);

$table->writeToCell(13, 10, '3-d place', $font1, null);
$table->writeToCell(14, 9, 'B1', $font1, null);
$table->writeToCell(15, 9, 'B2', $font1, null);


$table->setBackgroundForCellRange('#ffff88', 1, 10, 1, 11);
$table->setBackgroundForCellRange('#cccccc', 2, 10, 2, 11);
$table->setBackgroundForCellRange('#ffAA66', 3, 10, 3, 11);


$table->writeToCell(1, 10, '1-st place', $font1, null);
$table->writeToCell(2, 10, '2-st place', $font1, null);
$table->writeToCell(3, 10, '3-d place', $font1, null);

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');