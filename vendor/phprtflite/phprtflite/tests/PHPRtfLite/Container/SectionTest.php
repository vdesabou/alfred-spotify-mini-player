<?php


/**
 * Test class for PHPRtfLite.
 */
class PHPRtfLite_Container_SectionTest extends PHPUnit_Framework_TestCase
{

    /**
     *
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     *
     * @var PHPRtfLite_Container_Section
     */
    protected $_section;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->_rtf = new PHPRtfLite;
        $this->_section = new PHPRtfLite_Container_Section($this->_rtf);
    }

    /**
     * tests set margins
     */
    public function testSetMargins()
    {
        $expected = array(.1, .2, .3, 1.4);
        $this->_section->setMargins($expected[0], $expected[1], $expected[2], $expected[3]);
        $actual = array($this->_section->getMarginLeft(),
                        $this->_section->getMarginTop(),
                        $this->_section->getMarginRight(),
                        $this->_section->getMarginBottom());

        $this->assertEquals($expected, $actual);
    }

    /**
     * tests PHPRtfLite_Container_Section::getLayoutWidth
     */
    public function testGetLayoutWidthUsingRtfValues()
    {
        $this->_rtf->setPaperWidth(20);
        $this->_rtf->setMarginLeft(2.3);
        $this->_rtf->setMarginRight(2.7);
        $this->assertEquals(15, $this->_section->getLayoutWidth());

        return $this->_section;
    }

    /**
     * tests PHPRtfLite_Container_Section::getLayoutWidth
     * @depends testGetLayoutWidthUsingRtfValues
     */
    public function testGetLayoutWidthUsingSectionValues(PHPRtfLite_Container_Section $section)
    {
        $section->setMarginLeft(1.3);
        $section->setMarginRight(1.7);
        $this->assertEquals(17, $section->getLayoutWidth());

        $section->setPaperWidth(15);
        $this->assertEquals(12, $section->getLayoutWidth());
    }

    /**
     * tests PHPRtfLite_Container_Section::getLayoutWidth
     * @depends testGetLayoutWidthUsingSectionValues
     * @expectedException PHPRtfLite_Exception
     */
    public function testGetLayoutWidthUsingSectionValuesException()
    {
        $this->_section->setMarginLeft(5.3);
        $this->_section->setMarginRight(5.7);
        $this->_section->setPaperWidth(11);
        $this->_section->getLayoutWidth();
    }

    /**
     * test PHPRtfLite_Container_Section::setNumberOfColumns
     */
    public function testSetNumberOfColumns()
    {
        $this->_section->setNumberOfColumns(2);
        $this->assertEquals(2, $this->_section->getNumberOfColumns());
        $this->assertEquals(array(), $this->_section->getColumnWidths());
    }

    /**
     * test PHPRtfLite_Container_Section::setColumnWidths
     */
    public function testSetColumnsWidths()
    {
        $this->_section->setMarginLeft(0);
        $this->_section->setMarginRight(0);
        $this->_section->setPaperWidth(15);
        $expected = array(1.5, 5, 2.3, 2);
        $this->_section->setColumnWidths($expected);
        $this->assertEquals($expected, $this->_section->getColumnWidths());
    }

    /**
     * test PHPRtfLite_Container_Section::setColumnWidths
     * @depends testSetColumnsWidths
     * @expectedException PHPRtfLite_Exception
     */
    public function testSetColumnWidthsExceedingLayoutWidths()
    {
        $this->_section->setPaperWidth(10);
        $expected = array(1.5, 5, 2.3, 2);
        $this->_section->setColumnWidths($expected);
    }

    /**
     * tests addHeader
     */
    public function testAddHeader()
    {
        $header = $this->_section->addHeader();
        $this->assertType('PHPRtfLite_Container_Header', $header);
    }

    /**
     * tests addFooter
     */
    public function testAddFooter()
    {
        $footer = $this->_section->addFooter();
        $this->assertType('PHPRtfLite_Container_Footer', $footer);
    }

    /**
     * tests addTable
     */
    public function testAddTable()
    {
        $table = $this->_section->addTable();
        $this->assertType('PHPRtfLite_Table', $table);
    }

    /**
     * tests addImage
     */
    public function testAddImage()
    {
        $fileName = dirname(__FILE__) . '/../../../samples/sources/cats.jpg';
        $image = $this->_section->addImage($fileName);
        $this->assertType('PHPRtfLite_Image', $image);
    }

    /**
     * tests addFootnote
     */
    public function testAddFootnote()
    {
        $footnote = $this->_section->addFootnote('footnote test');
        $this->assertType('PHPRtfLite_Footnote', $footnote);
    }

    /**
     * tests addEndnote
     */
    public function testAddEndnote()
    {
        $endnote = $this->_section->addEndnote('endnote test');
        $this->assertType('PHPRtfLite_Endnote', $endnote);
    }

    public function testWriteRtfCode()
    {
        $section = $this->_rtf->addSection();
        $section->writeRtfCode('This is a Unit Test text!');
        $this->assertEquals(1, $section->countElements());
    }

    /**
     * @todo Implement testWriteText().
     */
    public function testWriteText()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testWriteTextHyperlink().
     */
    public function testWriteHyperlink()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testEmptyParagraph().
     */
    public function testEmptyParagraph()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

}