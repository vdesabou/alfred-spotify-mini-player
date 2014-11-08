<?php
$dir = dirname(__FILE__);
require_once $dir . '/../lib/PHPRtfLite.php';

// register PHPRtfLite class loader
PHPRtfLite::registerAutoloader();


function writeSectionText(PHPRtfLite_Container_Section $sect, $arial14, $times12, $text, $text2, $text3) {
    $sect->writeText('Sample RTF document', $arial14, new PHPRtfLite_ParFormat());
    $sect->writeText($text, $times12, new PHPRtfLite_ParFormat());

    $sect->writeText('Character encoding', $arial14, new PHPRtfLite_ParFormat());
    $sect->writeText($text2, $times12, new PHPRtfLite_ParFormat());

    $sect->writeText('Common implementations', $arial14, new PHPRtfLite_ParFormat());
    $sect->writeText($text3, $times12, new PHPRtfLite_ParFormat());
}

$text = '
As an example, the following RTF code:

{\rtf1\ansi{\fonttbl\f0\fswiss Helvetica;}\f0
Hello!\par\par
This is some {\b bold} text.\par
}
would be rendered like this when read by an appropriate word processor:

Hello!

This is some bold text.

A backslash (\) starts an RTF control code. The \par control code indicates a new line, and \b switches to a bold typeface. Braces ({ and }) define a group; the example uses a group to limit the scope of the \b control code. Everything else will be treated as clear text, or the text to be formatted. A valid RTF document is a group starting with the \rtf control code.
';

$text2 = '
RTF is a 7-bit format. That would limit it to ASCII, but RTF can encode characters beyond ASCII by escape sequences. The character escapes are of two types: code page escapes and Unicode escapes. In a code page escape, two hexadecimal digits following an apostrophe are used for denoting a character taken from a Windows code page. For example, if control codes specifying Windows-1256 are present, the sequence \'c8 will encode the Arabic letter beh (?).

If a Unicode escape is required, the control word \u is used, followed by a 16-bit signed decimal integer giving the Unicode codepoint number. For the benefit of programs without Unicode support, this must be followed by the nearest representation of this character in the specified code page. For example, \u1576? would give the Arabic letter beh, specifying that older programs which do not have Unicode support should render it as a question mark instead.

The control word \uc0 can be used to indicate that subsequent Unicode escape sequences within the current group do not specify a substitution character.
';

$text3 = '
Most word processing software implementations support RTF format import and export, often making it a "common" format between otherwise incompatible word processing software.

The WordPad editor in Microsoft Windows creates RTF files by default. It once defaulted to the Microsoft Word 6.0 file format, but write support for Word documents was dropped in a security update.

The free software/open-source word processors AbiWord and OpenOffice.org can view and edit RTF files.

The default editor for Mac OS X, TextEdit, can also view and edit RTF files.

Since RTF files are text files, it\'s easy to produce RTF with many programming languages, like Perl, Java, C++, Pascal, COBOL, or Lisp. Perl, for example, has the RTF::Writer module for this purpose.
';


$times12 = new PHPRtfLite_Font(13, 'Times new Roman');
$arial14 = new PHPRtfLite_Font(14, 'Arial', '#000066');

$parFormat = new PHPRtfLite_ParFormat();

//rtf document
$rtf = new PHPRtfLite();

//borders
$borderFormatBlue = new PHPRtfLite_Border_Format(1, '#0000ff');
$borderFormatRed = new PHPRtfLite_Border_Format(2, '#ff0000');
$border = new PHPRtfLite_Border($rtf, $borderFormatBlue, $borderFormatRed, $borderFormatBlue, $borderFormatRed);
$rtf->setBorder($border);
$rtf->setBorderSurroundsHeader();
$rtf->setBorderSurroundsFooter();

//headers
$rtf->setOddEvenDifferent();

$header = $rtf->addHeader(PHPRtfLite_Container_Header::TYPE_LEFT);
$header->writeText("PHPRtfLite class library. Left document header. This is page - <pagenum> of <pagetotal> -", $times12, $parFormat);

$header = $rtf->addHeader(PHPRtfLite_Container_Header::TYPE_RIGHT);
$header->writeText("PHPRtfLite class library. Right document header. This is page - <pagenum> of <pagetotal> -", $times12, $parFormat);

//section 1
$sect = $rtf->addSection();
$sect->setPaperHeight(16);
$sect->setPaperWidth(25);

//Borders overridden: No Borders
$border = PHPRtfLite_Border::create($rtf, 0);
$sect->setBorder($border);
$sect->setSpaceBetweenColumns(1);
$sect->setNumberOfColumns(2);
$sect->setLineBetweenColumns();

writeSectionText($sect, $arial14, $times12, $text, $text2, $text3);

//section 2
$sect = $rtf->addSection();
$sect->setBorderSurroundsHeader();
$sect->setBorderSurroundsFooter();

//Header overridden
$header = $sect->addHeader(PHPRtfLite_Container_Header::TYPE_RIGHT);
$header->writeText("PHPRtfLite class library. Overriden right section header. This is page - <pagenum> of <pagetotal> -", $times12, $parFormat);
$header = $sect->addHeader(PHPRtfLite_Container_Header::TYPE_LEFT);
$header->writeText("PHPRtfLite class library. Overriden left section header. This is page - <pagenum> of <pagetotal> -", $times12, $parFormat);
//Borders overridden: Green border
$border = PHPRtfLite_Border::create($rtf, 1, '#00ff00', PHPRtfLite_Border_Format::TYPE_DASH, 1);
$sect->setBorder($border);

writeSectionText($sect, $arial14, $times12, $text, $text2, $text3);
//
//section 3
$sect = $rtf->addSection();
$sect->setColumnWidths(array(3, 3, 8));
//Border from rtf
//....

writeSectionText($sect, $arial14, $times12, $text, $text2, $text3);

// save rtf document
$rtf->save($dir . '/generated/' . basename(__FILE__, '.php') . '.rtf');