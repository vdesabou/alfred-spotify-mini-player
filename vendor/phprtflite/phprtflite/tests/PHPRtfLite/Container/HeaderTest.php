<?php

/**
 * Test class for PHPRtfLite_Container_Header.
 */
class PHPRtfLite_Container_HeaderTest extends PHPUnit_Framework_TestCase
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
     * tests render
     */
    public function testRender()
    {
        $header = new PHPRtfLite_Container_Header($this->_rtf);
        $header->writeText('hello world and see my rtf header!');
        $header->render();
        $this->assertEquals('{\header {hello world and see my rtf header!}'
                          . "\r\n\par}\r\n",
                            $this->_rtf->getWriter()->getContent());
    }


}