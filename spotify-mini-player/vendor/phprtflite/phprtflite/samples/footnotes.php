<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

//rtf document
$rtf = new PHPRtfLite();
// restart footnote numbering on each page
$rtf->setRestartFootnoteNumberEachPage();

$font = new PHPRtfLite_Font(14, 'Arial', '#000066');
$defaultFontForNotes = new PHPRtfLite_Font(10);
$rtf->setDefaultFontForNotes($defaultFontForNotes);

// section with footnotes
$sect = $rtf->addSection();

$sectionText = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi eget arcu in nisi porttitor dapibus. Vivamus sit amet dolor id justo dignissim porttitor. Nullam a fermentum massa.';
$sect->writeText($sectionText, $font);
$sect->addFootnote('This is a footnote');

$sectionText = 'Fusce pharetra ante felis, nec pharetra massa. Etiam vitae scelerisque tortor. Nunc varius, ante at dignissim imperdiet, enim neque porta augue, vitae euismod purus tellus non nunc. Pellentesque euismod venenatis ligula, eget scelerisque ipsum sagittis a. Morbi vitae nunc nec nisi congue luctus. Proin dictum sagittis nisi, feugiat pharetra est aliquet nec.
Ut ultrices eleifend tellus, vitae vehicula leo ultrices a. Sed et mi a lorem condimentum hendrerit. Cras imperdiet nisi ac odio scelerisque convallis.';
$sect->writeText($sectionText, $font);
$sect->addFootnote('This is a another footnote');

// section with endnotes
$sect = $rtf->addSection();

$sectionText = 'Nulla a ante nec diam egestas tempus. Nullam odio mauris, mattis vitae gravida a, semper a elit.';
$sect->writeText($sectionText, $font);
$sect->addEndnote('This is a endnote');

$sectionText = 'Nulla a ante nec diam egestas tempus. Nullam odio mauris, mattis vitae gravida a, semper a elit.';
$sect->writeText($sectionText, $font);
$sect->addEndnote('This is a another endnote');

// font for footnote and endnote
$fontNotes = new PHPRtfLite_Font(24, 'Arial', '#333333');

// section with table
$sect = $rtf->addSection();
$table = $sect->addTable();
$table->addRows(2, 0.75);
$table->addColumnsList(array(7, 7));
$table->writeToCell(1, 1, 'Nulla a ante nec diam egestas tempus.');
$table->writeToCell(1, 2, 'Nulla a ante nec diam egestas tempus.');
$table->getCell(1, 2)->addFootnote('This is a footnote in a table', $fontNotes);
$table->writeToCell(2, 1, 'Nulla a ante nec diam egestas tempus.');
$table->getCell(2, 1)->addEndnote('This is a endnote in a table', $fontNotes);
$table->writeToCell(2, 2, 'Nulla a ante nec diam egestas tempus.');

// section with footnote again
$sect = $rtf->addSection();
$sectionText = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi eget arcu in nisi porttitor dapibus. Vivamus sit amet dolor id justo dignissim porttitor. Nullam a fermentum massa.';
$sect->writeText($sectionText, $font);
$sect->addFootnote('This is a footnote');

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');