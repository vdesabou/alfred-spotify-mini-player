<?php

/**
 * Test class for PHPRtfLite.
 */
class PHPRtfLiteTest extends PHPUnit_Framework_TestCase
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
        $this->_rtf = new PHPRtfLite;
    }

    /**
     * tests unregisterAutoloader
     */
    public function testUnregisterAutoloader()
    {
        $this->assertTrue(PHPRtfLite::unregisterAutoloader());
    }

    /**
     * tests registerAutoloader
     * @depends testUnregisterAutoloader
     */
    public function testRegisterAutoloader()
    {
        $this->assertTrue(PHPRtfLite::registerAutoloader());
    }

    /**
     * @covers PHPRtfLite::getProperty
     * @dataProvider provideSetProperty
     */
    public function testSetProperty($name, $timestamp, $expected)
    {
        $this->_rtf->setProperty($name, $timestamp);
        $this->assertEquals($expected, $this->_rtf->getProperty($name));
    }


    public function provideSetProperty()
    {
        return array(
            array('creatim', mktime(14, 25, 0, 6, 21, 2010), '\yr2010\mo06\dy21\hr14\min25'),
            array('buptim', mktime(13, 45, 0, 5, 20, 2010), '\yr2010\mo05\dy20\hr13\min45'),
            array('title', 'hello world!', 'hello world!')
        );
    }

    /**
     * tests addSection
     * @covers PHPRtfLite::getSections
     */
    public function testAddSection()
    {
        $section = $this->_rtf->addSection();
        $this->assertType('PHPRtfLite_Container_Section', $section);
        $this->_rtf->addSection(new PHPRtfLite_Container_Section($this->_rtf));
        $this->assertEquals(2, count($this->_rtf->getSections()));
    }

    /**
     * tests setBorders
     * @covers PHPRtfLite::getBorder
     */
    public function testSetBorders()
    {
        $borderFormat = new PHPRtfLite_Border_Format();
        $this->_rtf->setBorders($borderFormat);
        $border = $this->_rtf->getBorder();
        if ($border) {
            $this->assertEquals($borderFormat, $border->getBorderBottom());
            $this->assertEquals($borderFormat, $border->getBorderTop());
            $this->assertEquals($borderFormat, $border->getBorderLeft());
            $this->assertEquals($borderFormat, $border->getBorderRight());
            return;
        }
        $this->fail();
    }

    /**
     * tests addHeader
     */
    public function testAddHeader()
    {
        $header = $this->_rtf->addHeader();
        $this->assertType('PHPRtfLite_Container_Header', $header);
    }

    /**
     * tests addHeader with expected exception for odd even pages
     * @expectedException PHPRtfLite_Exception
     */
    public function _testAddHeaderEvenOddException()
    {
        $this->_rtf->addHeader(PHPRtfLite_Container_Header::TYPE_LEFT);
        $this->_rtf->getContent();
    }

    /**
     * tests addHeader with odd and even page headers
     */
    public function _testAddHeaderEvenOdd()
    {
        $this->_rtf->setOddEvenDifferent();
        $this->_rtf->addHeader(PHPRtfLite_Container_Header::TYPE_LEFT);
        $this->_rtf->addHeader(PHPRtfLite_Container_Header::TYPE_RIGHT);
        $this->assertEquals(2, count($this->_rtf->getHeaders()));
        $this->_rtf->getContent();
    }

    /**
     * tests addHeader with first page
     */
    public function _testAddHeaderFirst()
    {
        $this->_rtf->addHeader(PHPRtfLite_Container_Header::TYPE_FIRST);
        $this->assertEquals(1, count($this->_rtf->getHeaders()));
        $this->_rtf->getContent();
    }

    /**
     * tests addHeader with expected exception for all page header
     * @expectedException PHPRtfLite_Exception
     */
    public function testAddHeaderAllException()
    {
        $this->_rtf->setOddEvenDifferent();
        $this->_rtf->addHeader(PHPRtfLite_Container_Header::TYPE_ALL);
        $this->_rtf->getContent();
    }

    /**
     * tests addFooter
     */
    public function testAddFooter()
    {
        $footer = $this->_rtf->addFooter();
        $this->assertType('PHPRtfLite_Container_Footer', $footer);
    }

    /**
     * @todo Implement testSave().
     */
    public function testSave()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @dataProvider provideQuoteRtfCode()
     */
    public function testQuoteRtfCode($testString, $resultString)
    {
        $this->assertEquals($resultString, PHPRtfLite::quoteRtfCode($testString));
    }


    /**
     * data provider for quoteRtfCode()
     *
     * @return array
     */
    public function provideQuoteRtfCode()
    {
        return array(
            array("\r", '\line '),
            array("\n", '\line '),
            array("\r\n", '\line '),
            array('\\', '\\\\')
        );
    }

}