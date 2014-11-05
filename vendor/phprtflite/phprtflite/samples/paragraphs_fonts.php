<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

//Rtf document
$rtf = new PHPRtfLite();

//Fonts
$fontHead = new PHPRtfLite_Font(12, 'Arial');
$fontSmall = new PHPRtfLite_Font(3);
$fontAnimated = new PHPRtfLite_Font(10);
$fontLink = new PHPRtfLite_Font(10, 'Helvetica', '#0000cc');

$parBlack = new PHPRtfLite_ParFormat();
$parBlack->setIndentRight(12.5);
$parBlack->setBackgroundColor('#000000');
$parBlack->setSpaceBefore(12);

$parHead = new PHPRtfLite_ParFormat();
$parHead->setSpaceBefore(3);
$parHead->setSpaceAfter(8);

$parSimple = new PHPRtfLite_ParFormat();
$parSimple->setIndentLeft(5);
$parSimple->setIndentRight(0.5);

$parPhp = new PHPRtfLite_ParFormat();
$parPhp->setShading(5);
$border = PHPRtfLite_Border::create($rtf, 1, '#000000', 'dash', 0.3);
$parPhp->setBorder($border);
$parPhp->setIndentLeft(5);
$parPhp->setIndentRight(0.5);

$rtf->setMargins(3, 1, 1 ,2);

//Section
$sect = $rtf->addSection();
$sect->writeText('Paragraphs, fonts and other', new PHPRtfLite_Font(14, 'Arial'), $parHead);

$sect->addEmptyParagraph($fontSmall, $parBlack);
$sect->writeText('Various fonts', $fontHead, $parHead);

$sect->writeText('Times New Roman, 9pt, Red', new PHPRtfLite_Font(9, 'Times New Roman', '#ff0000'), $parSimple);
$sect->writeText('Times New Roman, 10pt, Red, Pattern Yellow', new PHPRtfLite_Font(10, 'Times New Roman', '#ff0000', '#ffff00'), $parSimple);
$sect->writeText('Tahoma, 10pt, Blue', new PHPRtfLite_Font(10, 'Tahoma', '#0000ff'), $parSimple);
$sect->writeText('Verdana, 8pt, Green', new PHPRtfLite_Font(8, 'Verdana', '#00cc00'), $parSimple);

$sect->addEmptyParagraph($fontSmall, $parBlack);
$sect->writeText('Various paragraphs', $fontHead, $parHead);

$par = new PHPRtfLite_ParFormat('center');
$par->setIndentLeft(10);
$par->setBackgroundColor('#99ccff');
$par->setSpaceBetweenLines(2);

$sect->writeText('Alignment: center
Indent Left: 10
BackColor: #99ccff', new PHPRtfLite_Font(8, 'Verdana'), $par);

$par = new PHPRtfLite_ParFormat('right');
$par->setIndentLeft(5);
$par->setIndentRight(5);
$par->setBackgroundColor('#ffcc99');
$border = PHPRtfLite_Border::create($rtf, 1, '#ff0000');
$par->setBorder($border);

$sect->addEmptyParagraph(new PHPRtfLite_Font, new PHPRtfLite_ParFormat());

$sect->writeText('Alignment: right
Indent Left: 5
Indent Right: 10
BackColor: #ffcc99
Border: red', new PHPRtfLite_Font(8, 'Verdana'), $par);

$sect->addEmptyParagraph($fontSmall, $parBlack);
$sect->writeText('Using hyperlinks', $fontHead, $parHead);
$sect->writeHyperlink('http://www.php.lt', 'Official phpRtf site.', $fontLink, $parSimple);

$sect->addEmptyParagraph($fontSmall, $parBlack);
$sect->writeText('Using tags', $fontHead, $parHead);

$sect->writeText('<b>Bold text.</b><i>Italic<u>Underline text.</u></i><tab>.Current date- <chdate>. Bullet <bullet><br>', new PHPRtfLite_Font(), $parSimple);
$sect->writeText('<b>Bold text.</b><i>Italic<u>Underline text.</u></i><tab>.Current date- <chdate>. Bullet <bullet>.<br>', new PHPRtfLite_Font(), $parSimple, false);

$sect->addEmptyParagraph($fontSmall, $parBlack);
$sect->writeText('PHP highlighting sample', $fontHead, $parHead);

$sect->writeText('//sample php code<br/ >', new PHPRtfLite_Font(11, 'Courier New', '#ff8800'), $parPhp);

$sect->writeText('$sum = $a + $b;<br/ >', new PHPRtfLite_Font(11, 'Courier New', '#0000AA'), null);
$sect->writeText('echo ', new PHPRtfLite_Font(11, 'Courier New', '#008800'), null);
$sect->writeText('"The sum is - "', new PHPRtfLite_Font(11, 'Courier New', '#AA0000'), null);
$sect->writeText('.$sum.', new PHPRtfLite_Font(11, 'Courier New', '#0000AA'), null);
$sect->writeText('" ."', new PHPRtfLite_Font(11, 'Courier New', '#AA0000'), null);
$sect->writeText(';', new PHPRtfLite_Font(11, 'Courier New', '#000000'), null);

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');