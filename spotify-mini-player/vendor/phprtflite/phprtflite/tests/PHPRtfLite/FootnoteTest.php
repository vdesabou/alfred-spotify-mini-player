<?php

/**
 * Test class for PHPRtfLite_Footnote.
 */
class PHPRtfLite_FootnoteTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPRtfLite
     */
    private $_rtf;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        // register PHPRtfLite class loader
        $this->_rtf = new PHPRtfLite();
        $writer = new PHPRtfLite_Writer_String();
        $this->_rtf->setWriter($writer);
    }

    /**
     * tests render
     */
    public function testRender()
    {
        $footnote = new PHPRtfLite_Footnote($this->_rtf, 'hello rtf world!');
        $footnote->render();
        $this->assertEquals('\chftn {\footnote\pard\plain \lin283\fi-283 \fs20 {\up6\chftn}'
                          . "\r\n" . 'hello rtf world!} ',
                            $this->_rtf->getWriter()->getContent());
    }
}