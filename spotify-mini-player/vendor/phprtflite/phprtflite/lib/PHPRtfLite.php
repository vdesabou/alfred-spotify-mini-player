<?php
/*
    PHPRtfLite
    Copyright 2007-2008 Denis Slaveckij <sinedas@gmail.com>
    Copyright 2010-2012 Steffen Zeidler <sigma_z@sigma-scripts.de>

    This file is part of PHPRtfLite.

    PHPRtfLite is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    PHPRtfLite is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with PHPRtfLite.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Class for creating rtf documents.
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 */
class PHPRtfLite
{

    const VERSION = '1.3.2';

    const PAPER_A3 = 'a3';
    const PAPER_A4 = 'a4';
    const PAPER_A5 = 'a5';
    const PAPER_LETTER = 'letter';
    const PAPER_LEGAL = 'legal';

    const SPACE_IN_POINTS           = 20;   // used for twips conversion
    const SPACE_IN_LINES            = 240;  // used for twips conversion

    /**
     * constants defining view modes
     */
    const VIEW_MODE_NONE            = 0;
    const VIEW_MODE_PAGE_LAYOUT     = 1;
    const VIEW_MODE_OUTLINE         = 2;
    const VIEW_MODE_MASTER          = 3;
    const VIEW_MODE_NORMAL          = 4;
    const VIEW_MODE_ONLINE_LAYOUT   = 5;

    /**
     * constants defining zoom modes
     */
    const ZOOM_MODE_NONE            = 0;
    const ZOOM_MODE_FULL_PAGE       = 1;
    const ZOOM_MODE_BEST_FIT        = 2;


    /**
     * @var array
     */
    protected $_paperFormats = array(
        self::PAPER_A3 => array(29.7, 42.0),
        self::PAPER_A4 => array(21.0, 29.7),
        self::PAPER_A5 => array(14.85, 21.0),
        self::PAPER_LETTER => array(21.59, 27.94),
        self::PAPER_LEGAL => array(21.59, 35.56)
    );

    /**
     * rtf sections
     * @var PHPRtfLite_Container_Section[]
     */
    protected $_sections    = array();

    /**
     * rtf headers
     * @var PHPRtfLite_Container_Header[]
     */
    protected $_headers     = array();

    /**
     * rtf footers
     * @var PHPRtfLite_Container_Footer[]
     */
    protected $_footers     = array();

    /**
     * charset of text inputs for rtf document, default is UTF-8
     * @var string
     */
    private $_charset     = 'UTF-8';

    /**
     * paper width
     * @var float
     */
    private $_paperWidth   = 0;

    /**
     * paper height
     * @var float
     */
    private $_paperHeight  = 0;

    /**
     * left margin
     * @var float
     */
    private $_marginLeft  = 3;

    /**
     * right margin
     * @var float
     */
    private $_marginRight = 3;

    /**
     * top margin
     * @var float
     */
    private $_marginTop   = 1;

    /**
     * bottom margin
     * @var float
     */
    private $_marginBottom = 2;

    /**
     * flag, if true, even and odd pages are using different layouts
     * @var boolean
     */
    private $_useOddEvenDifferent = false;

    /**
     * font table instance
     * @var PHPRtfLite_DocHead_FontTable
     */
    protected $_fontTable;

    /**
     * color table instance
     * @var PHPRtfLite_DocHead_ColorTable
     */
    protected $_colorTable;

    /**
     * default font
     * @var PHPRtfLite_Font
     */
    protected $_defaultFont;

    /**
     * rtf properties
     * @var array
     */
    protected $_properties      = array();

    /**
     * @var bool
     */
    protected $_borderSurroundsHeader = false;

    /**
     * @var bool
     */
    protected $_borderSurroundsFooter = false;

    /**
     * default tab width
     * @var float
     */
    private $_defaultTabWidth = 2.29;

    /**
     * view mode
     * @var string
     */
    private $_viewMode;

    /**
     * zoom level
     * @var integer
     */
    private $_zoomLevel;

    /**
     * zoom mode
     * @var integer
     */
    private $_zoomMode;

    /**
     * gutter
     * @var float
     */
    private $_gutter;

    /**
     * flag, if true margins will be the opposite for odd and even pages
     * @var boolean
     */
    private $_useMirrorMargins = false;

    /**
     * start with page number
     * @var integer
     */
    private $_pageNumberStart = 1;

    /**
     * flag, if true first page has special layout
     * @var boolean
     */
    private $_titlepg         = false;

    /**
     * rtf border
     * @var PHPRtfLite_Border
     */
    private $_border;

    /**
     * flag, if true use landscape layout
     * @var boolean
     */
    private $_isLandscape = false;

    /**
     * using hyphnation
     * @var boolean
     */
    private $_isHyphenation = false;

    /**
     * document head definition for notes
     * @var PHPRtfLite_DocHead_Note
     */
    private $_noteDocHead = null;

    /**
     * output stream
     * @var PHPRtfLite_Writer_Interface
     */
    private $_writer;

    /**
     * flag, if true PHPRtfLite writes the output into a temporary file,
     * which is slowing down a bit because of the io operations but uses less memory
     * @var boolean
     */
    private $_useTemporaryFile = false;


    public function __construct()
    {
        $this->setPaperFormat(self::PAPER_A4);
    }


    /**
     * registers autoloader for PHPRtfLite classes
     *
     * @return  boolean
     */
    public static function registerAutoloader()
    {
        $baseClassDir = dirname(__FILE__);
        require_once $baseClassDir . '/PHPRtfLite/Autoloader.php';
        PHPRtfLite_Autoloader::setBaseDir($baseClassDir);

        return spl_autoload_register(array('PHPRtfLite_Autoloader', 'autoload'));
    }


    /**
     * unregisters autoloader for PHPRtfLite classes
     *
     * @return  boolean
     */
    public static function unregisterAutoloader()
    {
        return spl_autoload_unregister(array('PHPRtfLite_Autoloader', 'autoload'));
    }


    /**
     * set that a temporary file should be used for creating the output
     * NOTE: is slowing down the rendering because of the io operations, but uses less memory
     *
     * @param boolean $flag default is true
     */
    public function setUseTemporaryFile($flag = true)
    {
        $this->_useTemporaryFile = $flag;
    }


    /**
     * sets charset for rtf text inputs
     *
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
    }


    /**
     * gets charset for rtf text inputs
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }


    /**
     * sets document information properties
     *
     * @param string $name Property of document. Possible properties: <br>
     *   'title' => title of the document (value string)<br>
     *   'subject' => subject of the document (value string)<br>
     *   'author' => author of the document (value string)<br>
     *   'manager' => manager of the document (value string)<br>
     *   'company' => company of author (value string)<br>
     *   'operator' => operator of document. Operator is a person who last made changes to the document. (value string) <br>
     *   'category' => category of document (value string)<br>
     *   'keywords' => keywords of document (value string)<br>
     *   'doccomm' => comments of document (value string)<br>
     *   'creatim' => creation time (value int) <br>
     *   'revtim' => last revision time (value int) <br>
     *   'buptim' => last backup time (value int) <br>
     *   'printim' => last print time (value int) <br>
     * @param mixed $value Value
     */
    public function setProperty($name, $value)
    {
        switch ($name) {
            case 'creatim':
            case 'revtim':
            case 'buptim':
            case 'printim':
                $year       = date('Y', $value);
                $month      = date('m', $value);
                $day        = date('d', $value);
                $hours      = date('H', $value);
                $minutes    = date('i', $value);

                $value = '\yr' . $year
                       . '\mo' . $month
                       . '\dy' . $day
                       . '\hr' . $hours
                       . '\min' . $minutes;
                break;
            default:
                $value = self::quoteRtfCode($value);
        }

        $this->_properties[$name] = $value;
    }


    /**
     * gets rtf property
     *
     * @param   string $name
     * @return  string
     */
    public function getProperty($name)
    {
        return isset($this->_properties[$name])
               ? $this->_properties[$name]
               : null;
    }


    /**
     * gets document head definition for notes
     *
     * @return PHPRtfLite_DocHead_Note
     */
    public function getNoteDocHead()
    {
        if ($this->_noteDocHead === null) {
            $this->_noteDocHead = new PHPRtfLite_DocHead_Note();
        }

        return $this->_noteDocHead;
    }


    /**
     * sets footnote numbering type
     *
     * @param integer $numberingType
     */
    public function setFootnoteNumberingType($numberingType)
    {
        $this->getNoteDocHead()->setFootnoteNumberingType($numberingType);
    }


    /**
     * gets footnote numbering type
     *
     * @return integer
     */
    public function getFootnoteNumberingType()
    {
        return $this->getNoteDocHead()->getFootnoteNumberingType();
    }


    /**
     * sets endnote numbering type
     *
     * @param integer $numberingType
     */
    public function setEndnoteNumberingType($numberingType)
    {
        $this->getNoteDocHead()->setEndnoteNumberingType($numberingType);
    }


    /**
     * gets endnote numbering type
     *
     * @return integer
     */
    public function getEndnoteNumberingType()
    {
        return $this->getNoteDocHead()->getEndnoteNumberingType();
    }


    /**
     * sets footnote start number
     *
     * @param integer $startNumber
     */
    public function setFootnoteStartNumber($startNumber)
    {
        $this->getNoteDocHead()->setFootnoteStartNumber($startNumber);
    }


    /**
     * gets footnote start number
     *
     * @return integer
     */
    public function getFootnoteStartNumber()
    {
        return $this->getNoteDocHead()->getFootnoteStartNumber();
    }


    /**
     * sets endnote start number
     *
     * @param integer $startNumber
     */
    public function setEndnoteStartNumber($startNumber)
    {
        $this->getNoteDocHead()->setEndnoteStartNumber($startNumber);
    }


    /**
     * gets endnote start number
     *
     * @return integer
     */
    public function getEndnoteStartNumber()
    {
        return $this->getNoteDocHead()->getEndnoteStartNumber();
    }


    /**
     * sets restart footnote number on each page
     */
    public function setRestartFootnoteNumberEachPage()
    {
        $this->getNoteDocHead()->setRestartFootnoteNumberEachPage();
    }


    /**
     * checks, if footnote numbering shall be started on each page
     *
     * @return boolean
     */
    public function setRestartEndnoteNumberEachPage()
    {
        $this->getNoteDocHead()->setRestartEndnoteNumberEachPage();
    }


    /**
     * sets restart endnote number on each page
     *
     * @return bool
     */
    public function isRestartFootnoteNumberEachPage()
    {
        return $this->getNoteDocHead()->isRestartFootnoteNumberEachPage();
    }


    /**
     * checks, if endnote numbering shall be started on each page
     *
     * @return boolean
     */
    public function isRestartEndnoteNumberEachPage()
    {
        return $this->getNoteDocHead()->isRestartEndnoteNumberEachPage();
    }


    /**
     * sets default font for notes
     *
     * @param PHPRtfLite_Font $font
     */
    public function setDefaultFontForNotes(PHPRtfLite_Font $font)
    {
        PHPRtfLite_Footnote::setDefaultFont($font);
    }


    /**
     * gets default font for notes
     *
     * @return PHPRtfLite_Font
     */
    public function getDefaultFontForNotes()
    {
        return PHPRtfLite_Footnote::getDefaultFont();
    }


    /**
     * adds section to rtf document
     *
     * @param  PHPRtfLite_Container_Section $section
     * @return PHPRtfLite_Container_Section
     */
    public function addSection(PHPRtfLite_Container_Section $section = null)
    {
        if ($section === null) {
            $section = new PHPRtfLite_Container_Section($this);
        }

        $this->_sections[] = $section;

        return $section;
    }


    /**
     * gets sections
     *
     * @return array
     */
    public function getSections()
    {
        return $this->_sections;
    }


    /**
     * sets default tab width of the document
     *
     * @param float $defaultTabWidth Default tab width
     */
    public function setDefaultTabWidth($defaultTabWidth)
    {
        $this->_defaultTabWidth = $defaultTabWidth;
    }


    /**
     * sets default tab width of the document
     *
     * @return float $defaultTabWidth Default tab width
     */
    public function getDefaultTabWidth()
    {
        return $this->_defaultTabWidth;
    }


    /**
     * sets the paper format
     *
     * @param  string $paperFormat represented by class constants PAPER_*
     * @throws PHPRtfLite_Exception
     */
    public function setPaperFormat($paperFormat)
    {
        if (!isset($this->_paperFormats[$paperFormat])) {
            throw new PHPRtfLite_Exception("Paper format: '$paperFormat' is not supported!");
        }

        list($width, $height) = $this->_paperFormats[$paperFormat];

        $defaultUnit = PHPRtfLite_Unit::getGlobalUnit();
        $this->_paperWidth = PHPRtfLite_Unit::convertTo($width, PHPRtfLite_Unit::UNIT_CM, $defaultUnit);
        $this->_paperHeight = PHPRtfLite_Unit::convertTo($height, PHPRtfLite_Unit::UNIT_CM, $defaultUnit);
    }


    /**
     * sets the paper width of document
     *
     * @param float $width pager width
     */
    public function setPaperWidth($width)
    {
        $this->_paperWidth = $width;
    }


    /**
     * gets the paper width of document
     *
     * @return float $paperWidth paper width
     */
    public function getPaperWidth()
    {
        return $this->_paperWidth;
    }


    /**
     * sets the paper height of document
     *
     * @param float $height paper height
     */
    public function setPaperHeight($height)
    {
        $this->_paperHeight = $height;
    }


    /**
     * gets the paper height of document
     *
     * @return float $paperHeight paper height
     */
    public function getPaperHeight()
    {
        return $this->_paperHeight;
    }


    /**
     * sets the margins of document pages
     *
     * @param float $marginLeft     Margin left (default 3 cm)
     * @param float $marginTop      Margin top (default 1 cm)
     * @param float $marginRight    Margin right (default 3 cm)
     * @param float $marginBottom   Margin bottom (default 2 cm)
     */
    public function setMargins($marginLeft, $marginTop, $marginRight, $marginBottom)
    {
        $this->_marginLeft      = $marginLeft;
        $this->_marginTop       = $marginTop;
        $this->_marginRight     = $marginRight;
        $this->_marginBottom    = $marginBottom;
    }


    /**
     * sets the left margin of document pages
     *
     * @param float $margin
     */
    public function setMarginLeft($margin)
    {
        $this->_marginLeft = $margin;
    }


    /**
     * gets the left margin of document pages
     *
     * @return float $margin
     */
    public function getMarginLeft()
    {
        return $this->_marginLeft;
    }


    /**
     * sets the right margin of document pages
     *
     * @param float $margin
     */
    public function setMarginRight($margin)
    {
        $this->_marginRight = $margin;
    }


    /**
     * gets the right margin of document pages
     *
     * @return float $margin
     */
    public function getMarginRight()
    {
        return $this->_marginRight;
    }


    /**
     * sets the top margin of document pages
     *
     * @param float $margin
     */
    public function setMarginTop($margin)
    {
        $this->_marginTop = $margin;
    }


    /**
     * gets the top margin of document pages
     *
     * @return float $margin
     */
    public function getMarginTop()
    {
        return $this->_marginTop;
    }


    /**
     * sets the bottom margin of document pages
     *
     * @param float $margin
     */
    public function setMarginBottom($margin)
    {
        $this->_marginBottom = $margin;
    }


    /**
     * gets the bottom margin of document pages
     *
     * @return float $margin
     */
    public function getMarginBottom()
    {
        return $this->_marginBottom;
    }


    /**
     * sets the margin definitions on left and right pages. <br>
     * NOTICE: Does not work with OpenOffice.
     */
    public function setMirrorMargins()
    {
        $this->_useMirrorMargins = true;
    }


    /**
     * returns true, if use mirror margins should be used
     * @return boolean
     */
    public function isMirrorMargins()
    {
        return $this->_useMirrorMargins;
    }


    /**
     * sets the gutter width. <br>
     * NOTICE: Does not work with OpenOffice.
     *
     * @param float $gutter gutter width
     */
    public function setGutter($gutter)
    {
        $this->_gutter = $gutter;
    }


    /**
     * gets the gutter width
     *
     * @return float $gutter gutter width
     */
    public function getGutter()
    {
        return $this->_gutter;
    }


    /**
     * sets the beginning page number
     *
     * @param integer $pageNumber Beginning page number (if not defined, Word uses 1)
     */
    public function setPageNumberStart($pageNumber)
    {
        $this->_pageNumberStart = $pageNumber;
    }


    /**
     * gets the beginning page number
     *
     * @return integer
     */
    public function getPageNumberStart()
    {
        return $this->_pageNumberStart;
    }


    /**
     * sets the view mode of the document
     *
     * @param integer $viewMode View Mode. Represented as class constants VIEW_MODE_*<br>
     *   Possible values: <br>
     *     VIEW_MODE_NONE           => 0 - None <br>
     *     VIEW_MODE_PAGE_LAYOUT    => 1 - Page Layout view <br>
     *     VIEW_MODE_OUTLINE        => 2 - Outline view <br>
     *     VIEW_MODE_MASTER         => 3 - Master Document view <br>
     *     VIEW_MODE_NORMAL         => 4 - Normal view <br>
     *     VIEW_MODE_ONLINE_LAYOUT  => 5 - Online Layout view
     */
    public function setViewMode($viewMode)
    {
        $this->_viewMode = $viewMode;
    }


    /**
     * gets the view mode of the document
     *
     * @return integer view mode represented as class constants VIEW_MODE_*
     */
    public function getViewMode()
    {
        return $this->_viewMode;
    }


    /**
     * sets the zoom level (in percents) of the document. By default word uses 100%. <br>
     * NOTICE: if zoom mode is defined, zoom level is not used.
     *
     * @param integer $zoom zoom level
     */
    public function setZoomLevel($zoom)
    {
        $this->_zoomLevel = $zoom;
    }


    /**
     * gets the zoom level (in percents) of the document
     *
     * @return integer $zoom zoom level
     */
    public function getZoomLevel()
    {
        return $this->_zoomLevel;
    }


    /**
     * sets the zoom mode of the document
     *
     * @param integer $zoomMode zoom mode. Represented as class constants.
     *   Possible values: <br>
     *     ZOOM_MODE_NONE       => 0 - None <br>
     *     ZOOM_MODE_FULL_PAGE  => 1 - Full Page <br>
     *     ZOOM_MODE_BEST_FIT   => 2 - Best Fit
     */
    public function setZoomMode($zoomMode)
    {
        $this->_zoomMode = $zoomMode;
    }


    /**
     * gets the zoom mode of the document
     *
     * @return integer
     */
    public function getZoomMode()
    {
        return $this->_zoomMode;
    }


    /**
     * sets landscape orientation for the document
     */
    public function setLandscape()
    {
        $this->_isLandscape = true;
    }


    /**
     * returns true, if landscape layout should be used
     *
     * @return boolean
     */
    public function isLandscape()
    {
        return $this->_isLandscape;
    }


    /**
     * sets using hyphenation
     */
    public function setHyphenation()
    {
        $this->_isHyphenation = true;
    }


    /**
     * Sets border to rtf document. Sections may override this border.
     *
     * @param PHPRtfLite_Border $border
     */
    public function setBorder(PHPRtfLite_Border $border)
    {
        $this->_border = $border;
    }

    /**
     * gets border of document
     *
     * @return PHPRtfLite_Border
     */
    public function getBorder()
    {
        return $this->_border;
    }


    /**
     * sets border surrounds header
     *
     * @param bool $borderSurroundsHeader
     * @return PHPRtfLite
     */
    public function setBorderSurroundsHeader($borderSurroundsHeader = true)
    {
        $this->_borderSurroundsHeader = $borderSurroundsHeader;
        return $this;
    }


    /**
     * checks, if border surrounds header
     *
     * @return bool
     */
    public function borderSurroundsHeader()
    {
        return $this->_borderSurroundsHeader;
    }


    /**
     * @param bool $borderSurroundsFooter
     * @return PHPRtfLite
     */
    public function setBorderSurroundsFooter($borderSurroundsFooter = true)
    {
        $this->_borderSurroundsFooter = $borderSurroundsFooter;
        return $this;
    }


    /**
     * checks, if border surrounds footer
     *
     * @return bool
     */
    public function borderSurroundsFooter()
    {
        return $this->_borderSurroundsFooter;
    }


    /**
     * Sets borders to rtf document. Sections may override this border.
     *
     * @param PHPRtfLite_Border_Format  $borderFormat
     * @param boolean                   $left
     * @param boolean                   $top
     * @param boolean                   $right
     * @param boolean                   $bottom
     */
    public function setBorders(PHPRtfLite_Border_Format $borderFormat,
                               $left = true, $top = true,
                               $right = true, $bottom = true)
    {
        if ($this->_border === null) {
            $this->_border = new PHPRtfLite_Border($this);
        }
        $this->_border->setBorders($borderFormat, $left, $top, $right, $bottom);
    }


    /**
     * sets if odd and even headers/footers are different
     *
     * @param boolean $different
     */
    public function setOddEvenDifferent($different = true)
    {
         $this->_useOddEvenDifferent = $different;
    }


    /**
     * gets if odd and even headers/footers are different
     *
     * @return boolean
     */
    public function isOddEvenDifferent()
    {
        return $this->_useOddEvenDifferent;
    }


    /**
     * creates header for the document
     *
     * @param   string  $type
     * Represented by class constants PHPRtfLite_Container_Header::TYPE_* <br>
     * Possible values: <br>
     *   PHPRtfLite_Container_Header::TYPE_ALL
     *     all pages (different odd and even headers/footers must be not set) <br>
     *   PHPRtfLite_Container_Header::TYPE_LEFT
     *     left pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Header::TYPE_RIGHT
     *     right pages (different odd and even headers/footers must be set <br>
     *   PHPRtfLite_Container_Header::TYPE_FIRST
     *     first page
     * @param   PHPRtfLite_Container_Header $header
     *
     * @return  PHPRtfLite_Container_Header
     */
    public function addHeader($type = PHPRtfLite_Container_Header::TYPE_ALL, PHPRtfLite_Container_Header $header = null)
    {
        if ($header === null) {
            $header = new PHPRtfLite_Container_Header($this, $type);
        }
        $this->_headers[$type] = $header;

        return $header;
    }


    /**
     * gets defined headers for document pages
     *
     * @return array contains PHPRtfLite_Container_Header objects
     */
    public function getHeaders()
    {
        return $this->_headers;
    }


    /**
     * creates footer for the document
     *
     * @param   string                      $type
     * Represented by class constants PHPRtfLite_Container_Footer::TYPE_* <br>
     * Possible values: <br>
     *   PHPRtfLite_Container_Footer::TYPE_ALL
     *     all pages (different odd and even headers/footers must be not set) <br>
     *   PHPRtfLite_Container_Footer::TYPE_LEFT
     *     left pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Footer::TYPE_RIGHT
     *     right pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Footer::TYPE_FIRST
     *     first page
     * @param   PHPRtfLite_Container_Footer $footer
     *
     * @return  PHPRtfLite_Container_Footer
     */
    public function addFooter($type = PHPRtfLite_Container_Footer::TYPE_ALL, PHPRtfLite_Container_Footer $footer = null)
    {
        if ($footer === null) {
            $footer = new PHPRtfLite_Container_Footer($this, $type);
        }
        $this->_footers[$type] = $footer;

        return $footer;
    }


    /**
     * gets defined footers for document pages
     *
     * @return array contains PHPRtfLite_Container_FOOTER objects
     */
    public function getFooters()
    {
        return $this->_footers;
    }


    /**
     * gets color table
     *
     * @return PHPRtfLite_DocHead_ColorTable
     */
    public function getColorTable()
    {
        if ($this->_colorTable === null) {
            $this->_colorTable = new PHPRtfLite_DocHead_ColorTable();
        }
        return $this->_colorTable;
    }

    /**
     * gets font table
     *
     * @return PHPRtfLite_DocHead_FontTable
     */
    public function getFontTable()
    {
        if ($this->_fontTable === null) {
            $this->_fontTable = new PHPRtfLite_DocHead_FontTable();
        }
        return $this->_fontTable;
    }


    /**
     * registers the font in color table and font table
     *
     * @param PHPRtfLite_Font $font
     */
    public function registerFont(PHPRtfLite_Font $font)
    {
        $font->setColorTable($this->getColorTable());
        $font->setFontTable($this->getFontTable());
    }


    /**
     * sets default font
     *
     * @param PHPRtfLite_Font $font
     */
    public function setDefaultFont(PHPRtfLite_Font $font)
    {
        $this->_defaultFont = $font;
        $this->registerFont($font);
    }


    /**
     * gets default font
     *
     * @return PHPRtfLite_Font
     */
    public function getDefaultFont()
    {
        return $this->_defaultFont;
    }


    /**
     * registers the par format in color table
     *
     * @param PHPRtfLite_ParFormat $parFormat
     */
    public function registerParFormat(PHPRtfLite_ParFormat $parFormat)
    {
        $parFormat->setColorTable($this->getColorTable());
    }


    /**
     * gets rtf document code
     *
     * @return string
     */
    public function getContent()
    {
        $this->createWriter();
        $this->render();
        return $this->_writer->getContent();
    }


    /**
     * saves rtf document to file
     *
     * @param string $file Name of file
     */
    public function save($file)
    {
        $this->createWriter($file);
        $this->render();
    }


    /**
     * sends rtf content as file attachment
     *
     * @param string $filename
     */
    public function sendRtf($filename = 'simple')
    {
        $pathInfo = pathinfo($filename);

        if (empty($pathInfo['extension'])) {
            $filename .= '.rtf';
        }

        if (false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 5.5')) {
            header('Content-Disposition: filename="' . $filename . '"');
        }
        else {
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }
        header('Content-type: application/msword');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        $this->createWriter();
        $this->render();
        echo $this->_writer->getContent();
    }


    /**
     * gets rtf info part
     *
     * @return string
     */
    protected function getInfoPart()
    {
        $part = '{\info'."\r\n";

        foreach ($this->_properties as $key => $value) {
            $value = PHPRtfLite_Utf8::getUnicodeEntities($value, $this->_charset);
            $part .= '{\\' . $key . ' ' . $value . '}'."\r\n";
        }

        $part .= '}'."\r\n";

        return $part;
    }


    /**
     * sets that first page has a special layout
     *
     * @param boolean $specialLayout
     */
    public function setSpecialLayoutForFirstPage($specialLayout = true)
    {
        $this->_titlepg = $specialLayout;
    }


    /**
     * returns true, if first page has special layout
     *
     * @return boolean
     */
    public function hasSpecialLayoutForFirstPage()
    {
        return $this->_titlepg;
    }


    /**
     * quotes rtf code
     *
     * @param  string   $text
     * @param  boolean  $convertNewlines
     * @return string
     */
    public static function quoteRtfCode($text, $convertNewlines = true)
    {
        // escape backslashes and curly brackets
        $text = str_replace(array('\\', '{', '}'), array('\\\\', '\\{', '\\}'), $text);
        if ($convertNewlines) {
            $text = self::convertNewlinesToRtfCode($text);
        }

        return $text;
    }


    /**
     * convert new lines to rtf line breaks
     *
     * @param  string $text
     * @return string
     */
    public static function convertNewlinesToRtfCode($text)
    {
        // convert breaks into rtf break
        $text = str_replace(array("\r\n", "\n", "\r"), '\line ', $text);

        return $text;
    }


    /**
     * creates writer
     *
     * @param string $file
     */
    private function createWriter($file = null)
    {
        if ($file || $this->_useTemporaryFile) {
            if (!($this->_writer instanceof PHPRtfLite_StreamOutput)) {
                $this->_writer = new PHPRtfLite_StreamOutput();
            }
            if (is_null($file)) {
                $file = sys_get_temp_dir() . '/' . md5(microtime(true)) . '.rtf';
            }
            $this->_writer->setFilename($file);
        }
        else if (!($this->_writer instanceof PHPRtfLite_Writer_String)) {
            $this->_writer = new PHPRtfLite_Writer_String();
        }
    }


    /**
     * sets writer
     *
     * @param  PHPRtfLite_Writer_Interface $writer
     */
    public function setWriter(PHPRtfLite_Writer_Interface $writer)
    {
        $this->_writer = $writer;
    }


    /**
     * gets writer
     *
     * @return PHPRtfLite_Writer_Interface
     */
    public function getWriter()
    {
        return $this->_writer;
    }


    /**
     * prepares rtf contents
     */
    protected function render()
    {
        $this->_writer->open();

        $defaultFontSize = 20;
        $defaultFontIndex = 0;
        if ($this->_defaultFont) {
            $defaultFontIndex = $this->getFontTable()->getFontIndex($this->_defaultFont->getFontFamily());
            $defaultFontSize = $this->_defaultFont->getSize() * 2;
        }

        $this->_writer->write('{\rtf\ansi\deff' . $defaultFontIndex . '\fs' . $defaultFontSize . "\r\n");

        $this->_writer->write($this->getFontTable()->getContent());
        $this->_writer->write($this->getColorTable()->getContent());

        $this->_writer->write($this->getInfoPart());

        $paperWidth = $this->_paperWidth;
        $paperHeight = $this->_paperHeight;

        // page properties
        if ($this->_isLandscape) {
            $this->_writer->write('\landscape ');
            if ($paperWidth < $paperHeight) {
                $tmp = $paperHeight;
                $paperHeight = $paperWidth;
                $paperWidth = $tmp;
            }
        }

        $this->_writer->write('\paperw' . PHPRtfLite_Unit::getUnitInTwips($paperWidth)  .' ');
        $this->_writer->write('\paperh' . PHPRtfLite_Unit::getUnitInTwips($paperHeight) . ' ');

        // hyphenation
        if ($this->_isHyphenation) {
            $this->_writer->write('\hyphauto1');
        }
        $this->_writer->write('\deftab' . PHPRtfLite_Unit::getUnitInTwips($this->_defaultTabWidth) . ' ');
        $this->_writer->write('\margl' . PHPRtfLite_Unit::getUnitInTwips($this->_marginLeft) . ' ');
        $this->_writer->write('\margr' . PHPRtfLite_Unit::getUnitInTwips($this->_marginRight) . ' ');
        $this->_writer->write('\margt' . PHPRtfLite_Unit::getUnitInTwips($this->_marginTop) . ' ');
        $this->_writer->write('\margb' . PHPRtfLite_Unit::getUnitInTwips($this->_marginBottom) . ' ');

        if (null !== $this->_gutter) {
            $this->_writer->write('\gutter' . PHPRtfLite_Unit::getUnitInTwips($this->_gutter) . ' ');
        }

        if (true == $this->_useMirrorMargins) {
            $this->_writer->write('\margmirror ');
        }

        if (null !== $this->_viewMode) {
            $this->_writer->write('\viewkind' . $this->_viewMode . ' ');
        }

        if (null !== $this->_zoomMode) {
            $this->_writer->write('\viewzk' . $this->_zoomMode . ' ');
        }

        if (null !== $this->_zoomLevel) {
            $this->_writer->write('\viewscale' . $this->_zoomLevel . ' ');
        }

        // page numbering start
        $this->_writer->write('\pgnstart' . $this->_pageNumberStart);

        // headers and footers properties
        if ($this->_useOddEvenDifferent) {
            $this->_writer->write('\facingp ');
        }
        if ($this->_titlepg) {
            $this->_writer->write('\titlepg ');
        }

        // document header definition for footnotes and endnotes
        $this->_writer->write($this->getNoteDocHead()->getContent());

        // default font
        if ($this->_defaultFont) {
            $this->_writer->write($this->_defaultFont->getContent());
        }

        // headers and footers if there are no sections
        if (count($this->_sections) == 0) {
            foreach ($this->_headers as $header) {
                $header->render();
            }

            foreach ($this->_footers as $footer) {
                $footer->render();
            }
        }

        // sections
        foreach ($this->_sections as $key => $section) {
            if ($key != 0) {
                $this->_writer->write('\sect\sectd ');
            }
            $section->render();
        }

        $this->_writer->write('}');
        $this->_writer->close();
    }

}
