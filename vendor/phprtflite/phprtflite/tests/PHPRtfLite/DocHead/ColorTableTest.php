<?php

/**
 * Test class for PHPRtfLite_DocHead_ColorTable
 */
class PHPRtfLite_DocHead_ColorTableTest extends PHPUnit_Framework_TestCase
{

    /**
     * tests getColorIndex
     * @testdox color index is false for a none added color
     */
    public function testGetColorIndexFalse()
    {
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        $this->assertFalse($colorTable->getColorIndex('#123456'));
    }

    /**
     * @dataProvider provideGetColorIndex
     * @param   PHPRtfLite_DocHead_ColorTable   $colorTable
     * @param   string                          $color
     * @param   integer                         $colorIndex
     */
    public function testGetColorIndex(PHPRtfLite_DocHead_ColorTable $colorTable, $color, $colorIndex)
    {
        $colorTable->add($color);
        $this->assertEquals($colorIndex, $colorTable->getColorIndex($color));
    }

    /**
     * provides test data for getColorIndex
     * @return array
     */
    public function provideGetColorIndex()
    {
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        return array(
            array($colorTable, '#ccc', 2),
            array($colorTable, '#CCC', 2),
            array($colorTable, '#F8F', 3),
            array($colorTable, '#000', 1),
            array($colorTable, '#Ff88fF', 3),
            array($colorTable, '#ff88ff', 3),
            array($colorTable, '#FF88FF', 3),
            array($colorTable, '#123456', 4),
        );
    }

}