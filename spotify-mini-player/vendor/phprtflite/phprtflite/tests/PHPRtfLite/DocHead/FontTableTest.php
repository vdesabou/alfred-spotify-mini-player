<?php

/**
 * Test class for PHPRtfLite_DocHead_FontTable
 */
class PHPRtfLite_DocHead_FontTableTest extends PHPUnit_Framework_TestCase
{

    /**
     * tests getFontIndex
     * @testdox font index is false for a none added font
     */
    public function testGetFontIndexFalse()
    {
        $fontTable = new PHPRtfLite_DocHead_FontTable();
        $this->assertFalse($fontTable->getFontIndex('Verdana'));
    }

    /**
     * @dataProvider provideGetFontIndex
     * @param   PHPRtfLite_DocHead_FontTable    $fontTable
     * @param   string                          $font
     * @param   integer                         $fontIndex
     */
    public function testGetFontIndex(PHPRtfLite_DocHead_FontTable $fontTable, $font, $fontIndex)
    {
        $fontTable->add($font);
        $this->assertEquals($fontIndex, $fontTable->getFontIndex($font));
    }

    /**
     * provides test data for getFontIndex
     * @return array
     */
    public function provideGetFontIndex()
    {
        $fontTable = new PHPRtfLite_DocHead_FontTable();
        return array(
            array($fontTable, 'Verdana', 1),
            array($fontTable, 'Arial', 2),
            array($fontTable, 'Courier News', 3),
            array($fontTable, 'Times New Roman', 0),
        );
    }

}