<?php

/**
 * Test class for PHPRtfLite_Border
 */
class PHPRtfLite_BorderTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var PHPRtfLite_Border
     */
    private $_border;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $rtf = new PHPRtfLite;
        $this->_border = new PHPRtfLite_Border($rtf);
    }

    /**
     * tests create
     */
    public function testCreateBorder()
    {
        $rtf = new PHPRtfLite;
        $border = PHPRtfLite_Border::create($rtf, 1.5, '#ff0', PHPRtfLite_Border_Format::TYPE_SINGLE, 5);

        $this->assertType('PHPRtfLite_Border_Format', $border->getBorderTop());
        $this->assertType('PHPRtfLite_Border_Format', $border->getBorderBottom());
        $this->assertType('PHPRtfLite_Border_Format', $border->getBorderRight());
        $this->assertType('PHPRtfLite_Border_Format', $border->getBorderLeft());
    }

    /**
     * tests setBorders
     */
    public function testSetBorders()
    {
        $borderFormat = new PHPRtfLite_Border_Format(1.5, '#ff0', PHPRtfLite_Border_Format::TYPE_SINGLE);
        $this->_border->setBorders($borderFormat);

        $this->assertType('PHPRtfLite_Border_Format', $this->_border->getBorderTop());
        $this->assertType('PHPRtfLite_Border_Format', $this->_border->getBorderBottom());
        $this->assertType('PHPRtfLite_Border_Format', $this->_border->getBorderRight());
        $this->assertType('PHPRtfLite_Border_Format', $this->_border->getBorderLeft());
    }

}