<?php

$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();

$rtf = new PHPRtfLite();
$sect = $rtf->addSection();

$sect->writeText('Text before the list');

$enum = new PHPRtfLite_List_Enumeration($rtf);
$enum->addItem('hello world');
$enum->addItem('foo');
$enum->addItem('bar');
$subEnum = new PHPRtfLite_List_Enumeration($rtf);
$subEnum->addItem('hello world');
$subEnum->addItem('foo');
$subEnum->addItem('bar');
$enum->addList($subEnum);

$sect->addEnumeration($enum);

$subSubNumList = new PHPRtfLite_List_Numbering($rtf);
$subSubNumList->addItem('hello world');
$subSubNumList->addItem('foo');
$subSubNumList->addItem('bar');

$subNumList = new PHPRtfLite_List_Numbering($rtf);
$subNumList->addItem('hello world');
$subNumList->addItem('foo');
$subNumList->addItem('bar');
$subNumList->addList($subSubNumList);

$font = new PHPRtfLite_Font('26', 'Tahoma', '#f00');
$numList = new PHPRtfLite_List_Numbering($rtf, PHPRtfLite_List_Numbering::TYPE_ALPHA_LOWER, $font);
$numList->addItem('hello world');
$numList->addItem('foo');
$numList->addItem('bar');
$numList->addList($subNumList);
$numList->addItem('foobar');

$sect->addNumbering($numList);

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');
