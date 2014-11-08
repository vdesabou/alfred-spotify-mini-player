<?php


/**
 * Test class for PHPRtfLite_Border_Format
 */
class PHPRtfLite_Border_FormatTest extends PHPUnit_Framework_TestCase
{

    public function testGetContentWithTypeSingle()
    {
        $borderFormat = new PHPRtfLite_Border_Format(1, '#888', PHPRtfLite_Border_Format::TYPE_SINGLE);
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        $borderFormat->setColorTable($colorTable);
        $this->assertEquals('\brdrs\brdrw20\brsp0\brdrcf2' , trim($borderFormat->getContent()));
    }

    public function testGetContentWithTypeDot()
    {
        $borderFormat = new PHPRtfLite_Border_Format(1, '#888', PHPRtfLite_Border_Format::TYPE_DOT);
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        $borderFormat->setColorTable($colorTable);
        $this->assertEquals('\brdrdot\brdrw20\brsp0\brdrcf2' , trim($borderFormat->getContent()));
    }

    public function testGetContentWithTypeDash()
    {
        $borderFormat = new PHPRtfLite_Border_Format(1, '#888', PHPRtfLite_Border_Format::TYPE_DASH);
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        $borderFormat->setColorTable($colorTable);
        $this->assertEquals('\brdrdash\brdrw20\brsp0\brdrcf2' , trim($borderFormat->getContent()));
    }

    public function testGetContentWithTypeDotDash()
    {
        $borderFormat = new PHPRtfLite_Border_Format(1, '#888', PHPRtfLite_Border_Format::TYPE_DOTDASH);
        $colorTable = new PHPRtfLite_DocHead_ColorTable();
        $borderFormat->setColorTable($colorTable);
        $this->assertEquals('\brdrdashd\brdrw20\brsp0\brdrcf2' , trim($borderFormat->getContent()));
    }

}