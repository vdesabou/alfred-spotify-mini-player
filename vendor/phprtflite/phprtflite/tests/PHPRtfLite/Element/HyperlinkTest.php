<?php

/**
 * Test class for PHPRtfLite_Element_Hyperlink
 */
class PHPRtfLite_Element_HyperlinkTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_rtf = new PHPRtfLite();
        $writer = new PHPRtfLite_Writer_String();
        $this->_rtf->setWriter($writer);
    }

    /**
     * tests render().
     */
    public function testRender()
    {
        $hyperlink = new PHPRtfLite_Element_Hyperlink($this->_rtf, 'My link text!');
        $hyperlink->setHyperlink('http://www.phprtf.com/');
        $hyperlink->render();
        $expected = '{\field {\*\fldinst {HYPERLINK "http://www.phprtf.com/"}}{\fldrslt {My link text!}}}';
        $this->assertEquals($expected, trim($this->_rtf->getWriter()->getContent()));
    }

}