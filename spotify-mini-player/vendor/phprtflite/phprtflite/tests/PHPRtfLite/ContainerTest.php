<?php

/**
 * Test class for PHPRtfLite_Container
 */
class PHPRtfLite_ContainerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var PHPRtfLite_Container mock object
     */
    protected $_container;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $rtf = new PHPRtfLite();
        $writer = new PHPRtfLite_Writer_String();
        $rtf->setWriter($writer);

        $this->_container = $this->getMockForAbstractClass('PHPRtfLite_Container',
                                                           array(),
                                                           '',
                                                           false);
        $this->_container->__construct($rtf);
    }

    /**
     * tests writeRtfCode
     */
    public function testWriteRftCode()
    {
        $this->_container->writeRtfCode('test text');
        $this->_container->render();
        $this->assertEquals('{test text}', trim($this->_container->getRtf()->getWriter()->getContent()));
    }

    /**
     * tests addEmptyParagraph
     */
    public function testAddEmptyParagraph()
    {
        $this->_container->addEmptyParagraph();
        $this->_container->render();
        $this->assertEquals('\pard \ql {\par}', trim($this->_container->getRtf()->getWriter()->getContent()));
    }

    /**
     * tests addEmptyParagraph
     */
    public function testAddEmptyParagraphWithFontAndParFormat()
    {
        $font = new PHPRtfLite_Font();
        $parFormat = new PHPRtfLite_ParFormat();
        $this->_container->addEmptyParagraph($font, $parFormat);
        $this->_container->render();
        $this->assertEquals('\pard \ql {\fs20 \par}', trim($this->_container->getRtf()->getWriter()->getContent()));
    }

    /**
     * tests writeText
     *
     * @return PHPRtfLite_Container
     */
    public function testWriteTextForEmptyParagraph()
    {
        $this->_container->writeText('Hello world!');
        $this->_container->render();
        $this->assertEquals('{Hello world!}', trim($this->_container->getRtf()->getWriter()->getContent()));

        return $this->_container;
    }

    /**
     * tests addEmptyParagraph
     * @depends testWriteTextForEmptyParagraph
     *
     * @param PHPRtfLite_Container $container
     */
    public function testWriteTextAddEmptyParagraphWithFontAndParFormat(PHPRtfLite_Container $container)
    {
        $font = new PHPRtfLite_Font();
        $parFormat = new PHPRtfLite_ParFormat();
        $container->addEmptyParagraph($font, $parFormat);
        $container->render();
        $this->assertEquals('{Hello world!}' . "\r\n" . '\par \pard \ql {\fs20 \par}',
                            trim($container->getRtf()->getWriter()->getContent()));
    }

    /**
     * @dataProvider provideWriteText
     * tests writeText
     */
    public function testWriteText($input, $expected)
    {
        #$this->_container->getRtf()->getWriter()->content = '';
        $this->_container->writeText($input);
        $this->_container->render();
        $this->assertEquals($expected, trim($this->_container->getRtf()->getWriter()->getContent()));
    }

    /**
     *
     * @return array
     */
    public function provideWriteText()
    {
        return array(
            array('Hello world!',                   '{Hello world!}'),
            array('<strong>Hello world!</strong>',  '{\b Hello world!\b0 }'),
            array('<STRONG>Hello world!</STRONG>',  '{\b Hello world!\b0 }'),
            array('<b>Hello world!</b>',            '{\b Hello world!\b0 }'),
            array('<B>Hello world!</B>',            '{\b Hello world!\b0 }'),
            array('<em>Hello world!</em>',          '{\i Hello world!\i0 }'),
            array('<EM>Hello world!</EM>',          '{\i Hello world!\i0 }'),
            array('<i>Hello world!</i>',            '{\i Hello world!\i0 }'),
            array('<I>Hello world!</I>',            '{\i Hello world!\i0 }'),
            array('<u>Hello world!</u>',            '{\ul Hello world!\ul0 }'),
            array('<U>Hello world!</U>',            '{\ul Hello world!\ul0 }'),
            array('Hello<br><BR>world!',            '{Hello\line \line world!}'),
            array('Hello<br/><BR/>world!',          '{Hello\line \line world!}'),
            array(
              'Hello<hr><HR/>world!',
              '{Hello{\pard \brdrb \brdrs \brdrw10 \brsp20 \par}{\pard \brdrb \brdrs \brdrw10 \brsp20 \par}world!}'
            ),
            array('', '{}'),
            array('<CHDATE>',   '{\chdate }'),
            array('<CHDPL>',    '{\chdpl }'),
            array('<CHDPA>',    '{\chdpa }'),
            array('<CHTIME>',   '{\chtime }'),
            array('<CHPGN>',    '{\chpgn }'),
            array('<TAB>',      '{\tab }'),
            array('<BULLET>',   '{\bullet }'),
            array('<PAGENUM>',  '{\chpgn }'),
            array('<SECTNUM>',  '{\sectnum }'),
            array('<LINE>',     '{\line }'),
        );
    }


}