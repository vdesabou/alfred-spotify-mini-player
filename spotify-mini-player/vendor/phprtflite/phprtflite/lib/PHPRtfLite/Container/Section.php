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
 * Class for creating sections within the rtf document.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Container
 */
class PHPRtfLite_Container_Section extends PHPRtfLite_Container
{

    /**
     * border for section
     * @var PHPRtfLite_Border
     */
    protected $_border;

    /**
     * number of columns within the section
     * @var integer
     */
    protected $_numberOfColumns = 1;

    /**
     * array of column widths. only used when using more than one column within the section
     * @var array
     */
    protected $_columnWidths = array();

    /**
     * flag for not breaking within the section, if true do not break
     * @var boolean
     */
    protected $_doNotBreak = false;

    /**
     * flag, if true using lines between the section columns
     * @var boolean
     */
    protected $_lineBetweenColumns = false;

    /**
     * defines space between the section columns
     * @var float
     */
    protected $_spaceBetweenColumns;

    /**
     * flag, if true use landscape layout for this section, isLandscape of rtf must be set to portrait
     * @var boolean
     */
    private $_isLandscape = false;

    /**
     * paper width
     * @var float
     */
    protected $_paperWidth;

    /**
     * paper height
     * @var float
     */
    protected $_paperHeight;

    /**
     * left margin
     * @var float
     */
    protected $_marginLeft;

    /**
     * right margin
     * @var float
     */
    protected $_marginRight;

    /**
     * top margin
     * @var float
     */
    protected $_marginTop;

    /**
     * bottom margin
     * @var float
     */
    protected $_marginBottom;

    /**
     * gutter
     * @var float
     */
    protected $_gutter;

    /**
     * flag, if true margins will be the opposite for odd and even pages
     * @var boolean
     */
    protected $_useMirrorMargins;

    /**
     * rtf headers
     * @var PHPRtfLite_Container_Header[]
     */
    protected $_headers = array();

    /**
     * rtf footers
     * @var PHPRtfLite_Container_Footer[]
     */
    protected $_footers = array();

    /**
     * @var PHPRtfLite_Font
     */
    protected $_font;

    /**
     * @var bool
     */
    protected $_borderSurroundsHeader = false;

    /**
     * @var bool
     */
    protected $_borderSurroundsFooter = false;


    /**
     * sets landscape orientation for the section
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
     * sets the paper width of pages in section.
     *
     * @param float $width paper width
     */
    public function setPaperWidth($width)
    {
        $this->_paperWidth = $width;
    }


    /**
     * gets the paper width of pages in section.
     *
     * @return float paper width
     */
    public function getPaperWidth()
    {
        return $this->_paperWidth;
    }


    /**
     * sets the paper height of pages in section.
     *
     * @param float $height paper height
     */
    public function setPaperHeight($height)
    {
        $this->_paperHeight = $height;
    }


    /**
     * gets the paper height of pages in section.
     *
     * @return float paper height
     */
    public function getPaperHeight()
    {
        return $this->_paperHeight;
    }


    /**
     * sets the margins of pages in section.
     *
     * @param float $marginLeft Margin left
     * @param float $marginTop Margin top
     * @param float $marginRight Margin right
     * @param float $marginBottom Margin bottom
     */
    public function setMargins($marginLeft, $marginTop, $marginRight, $marginBottom)
    {
        $this->_marginLeft      = $marginLeft;
        $this->_marginTop       = $marginTop;
        $this->_marginRight     = $marginRight;
        $this->_marginBottom    = $marginBottom;
    }


    /**
     * sets the left margin of document pages.
     *
     * @param float $margin
     */
    public function setMarginLeft($margin)
    {
        $this->_marginLeft = $margin;
    }


    /**
     * gets the left margin of document pages.
     *
     * @return float $margin
     */
    public function getMarginLeft()
    {
        return $this->_marginLeft;
    }


    /**
     * sets the right margin of document pages.
     *
     * @param float $margin
     */
    public function setMarginRight($margin)
    {
        $this->_marginRight = $margin;
    }


    /**
     * gets the right margin of document pages.
     *
     * @return float $margin
     */
    public function getMarginRight()
    {
        return $this->_marginRight;
    }


    /**
     * sets the top margin of document pages.
     *
     * @param float $margin
     */
    public function setMarginTop($margin)
    {
        $this->_marginTop = $margin;
    }


    /**
     * gets the top margin of document pages.
     *
     * @return float $margin
     */
    public function getMarginTop()
    {
        return $this->_marginTop;
    }


    /**
     * sets the bottom margin of document pages.
     *
     * @param float $margin
     */
    public function setMarginBottom($margin)
    {
        $this->_marginBottom = $margin;
    }


    /**
     * gets the bottom margin of document pages.
     *
     * @return float $margin
     */
    public function getMarginBottom()
    {
        return $this->_marginBottom;
    }


    /**
     * sets the gutter width. <br>
     * NOTICE: Does note work with OpenOffice.
     *
     * @param float $gutter Gutter width
     */
    public function setGutter($gutter)
    {
        $this->_gutter = $gutter;
    }


    /**
     * gets the gutter width.
     *
     * @return float $gutter gutter width
     */
    public function getGutter()
    {
        return $this->_gutter;
    }


    /**
     * sets the margin definitions on left and right pages.<br>
     * Notice: Does not work with OpenOffice.
     */
    public function setMirrorMargins()
    {
        $this->_useMirrorMargins = true;
    }


    /**
     * returns true, if use mirror margins should be used
     *
     * @return boolean
     */
    public function isMirrorMargins()
    {
        return $this->_useMirrorMargins;
    }


    /**
     * gets width of page layout
     *
     * @return float
     * @throws PHPRtfLite_Exception thrown if paper layout width is lower or equal 0
     */
    public function getLayoutWidth()
    {
        $paperWidth     = $this->_paperWidth !== null   ? $this->_paperWidth    : $this->_rtf->getPaperWidth();
        $marginLeft     = $this->_marginLeft !== null   ? $this->_marginLeft    : $this->_rtf->getMarginLeft();
        $marginRight    = $this->_marginRight !== null  ? $this->_marginRight   : $this->_rtf->getMarginRight();
        $layoutWidth    = $paperWidth - $marginLeft - $marginRight;

        if ($layoutWidth <= 0) {
            throw new PHPRtfLite_Exception('The paper layout width is lower or equal zero!');
        }

        return $layoutWidth;
    }


    /**
     * sets border to rtf document
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
     * sets borders to rtf document.
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
            $this->_border = new PHPRtfLite_Border($this->_rtf);
        }
        $this->_border->setBorders($borderFormat, $left, $top, $right, $bottom);
    }


    /**
     * sets number of columns in section.
     *
     * @param integer $columnsCount number of columns
     */
    public function setNumberOfColumns($columnsCount)
    {
        $this->_numberOfColumns = $columnsCount;
        $this->_columnWidths    = array();
    }


    /**
     * gets number of columns in section.
     *
     * @return  integer $columnsCount number of columns
     */
    public function getNumberOfColumns()
    {
        return $this->_numberOfColumns;
    }


    /**
     * sets space (width) between columns
     *
     * @param float $spaceBetweenColumns Space between columns
     */
    public function setSpaceBetweenColumns($spaceBetweenColumns)
    {
        $this->_spaceBetweenColumns = $spaceBetweenColumns;
    }


    /**
     * gets space (width) between columns
     *
     * @return float
     */
    public function getSpaceBetweenColumns()
    {
        return $this->_spaceBetweenColumns;
    }


    /**
     * sets section columns with different widths.<br>
     * NOTE: If you use this function, you shouldn't use {@see setNumberOfColumns}.
     *
     * @param   array   $columnWidths array with columns widths
     * @throws  PHPRtfLite_Exception, if column widths are exceeding the defined layout width
     */
    public function setColumnWidths($columnWidths)
    {
        if (is_array($columnWidths)) {
            $this->_numberOfColumns = count($columnWidths);
            $layoutWidth = $this->getLayoutWidth();
            $usedWidth = array_sum($columnWidths);

            if ($usedWidth <= $layoutWidth) {
                $this->_columnWidths = $columnWidths;
            }
            else {
                throw new PHPRtfLite_Exception('The section column widths are exceeding the defined layout width!');
            }
        }
    }


    /**
     * gets widths for columns
     *
     * @return array
     */
    public function getColumnWidths()
    {
        return $this->_columnWidths;
    }


    /**
     * sets that it should not do a break within the section
     * NOTE: If foot notes are used in different sections, MS Word will always break sections.
     *
     * @param boolean $doNotBreak
     */
    public function setNoBreak($doNotBreak = true)
    {
        $this->_doNotBreak = $doNotBreak;
    }


    /**
     * sets line between columns.
     *
     * @param boolean $flag
     */
    public function setLineBetweenColumns($flag = true)
    {
        $this->_lineBetweenColumns = $flag;
    }


    /**
     * returns true, if line between columns is sets
     *
     * @return boolean
     */
    public function hasLineBetweenColumns()
    {
        return $this->_lineBetweenColumns;
    }


    /**
     * creates header for sections.
     *
     * @param string $type Represented by class constants PHPRtfLite_Container_Header::TYPE_*
     * Possible values: <br>
     *   PHPRtfLite_Container_Header::TYPE_ALL      => 'all' - all pages (different odd and even headers/footers must be not set) <br>
     *   PHPRtfLite_Container_Header::TYPE_LEFT     => 'left' - left pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Header::TYPE_RIGHT    => 'right' - right pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Header::TYPE_FIRST    => 'first' - first page
     *
     * @return PHPRtfLite_Container_Header
     */
    public function addHeader($type = PHPRtfLite_Container_Header::TYPE_ALL)
    {
        $header = new PHPRtfLite_Container_Header($this->_rtf, $type);
        $this->_headers[$type] = $header;

        return $header;
    }


    /**
     * gets defined headers for document pages.
     *
     * @return array contains PHPRtfLite_Container_Header objects
     */
    public function getHeaders()
    {
        return $this->_headers;
    }


    /**
     * creates footer for the document.
     *
     * @param string $type Represented by class constants PHPRtfLite_Container_Footer::TYPE_*
     *   PHPRtfLite_Container_Footer::TYPE_ALL      => 'all' - all pages (different odd and even headers/footers must be not set) <br>
     *   PHPRtfLite_Container_Footer::TYPE_LEFT     => 'left' - left pages (different odd and even headers/footers must be set) <br>
     *   PHPRtfLite_Container_Footer::TYPE_RIGHT    => 'right' - right pages (different odd and even headers/footers must be set)     <br>
     *   PHPRtfLite_Container_Footer::TYPE_FIRST    => 'first' - first page
     *
     * @return PHPRtfLite_Container_Footer
     */
    public function addFooter($type = PHPRtfLite_Container_Footer::TYPE_ALL)
    {
        $footer = new PHPRtfLite_Container_Footer($this->_rtf, $type);
        $this->_footers[$type] = $footer;

        return $footer;
    }


    /**
     * gets defined footers for document pages.
     *
     * @return array contains PHPRtfLite_Container_FOOTER objects
     */
    public function getFooters()
    {
        return $this->_footers;
    }


    /**
     * insert a page break
     */
    public function insertPageBreak()
    {
        $this->writeRtfCode('\page');
    }


    /**
     * @param PHPRtfLite_Font $font
     */
    public function setFont(PHPRtfLite_Font $font)
    {
        $this->_font = $font;
        $this->_rtf->registerFont($font);
    }


    /**
     * @return PHPRtfLite_Font
     */
    public function getFont()
    {
        return $this->_font;
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
     * renders rtf code of section
     */
    public function render()
    {
        $writer = $this->_rtf->getWriter();

        //headers
        $headers = $this->_headers ? $this->_headers : $this->_rtf->getHeaders();
        if (!empty($headers)) {
            foreach ($headers as $header) {
                $header->render();
            }
        }

        //footers
        $footers = $this->_footers ? $this->_footers : $this->_rtf->getFooters();
        if (!empty($footers)) {
            foreach ($footers as $footer) {
                $footer->render();
            }
        }

        //borders
        if ($this->_border) {
            if ($this->_borderSurroundsHeader) {
                $writer->write('\pgbrdrhead');
            }
            if ($this->_borderSurroundsFooter) {
                $writer->write('\pgbrdrfoot');
            }
            $writer->write($this->_border->getContent('\pg'));
        }
        else if ($border = $this->_rtf->getBorder()) {
            if ($this->_rtf->borderSurroundsHeader()) {
                $writer->write('\pgbrdrhead');
            }
            if ($this->_rtf->borderSurroundsFooter()) {
                $writer->write('\pgbrdrfoot');
            }
            $writer->write($border->getContent('\pg'));
        }

        //do not break within the section
        if ($this->_doNotBreak) {
            $writer->write('\sbknone ');
        }

        //set column index, when using more than one column for this section
        if ($this->_numberOfColumns > 1) {
            $writer->write('\cols' . $this->_numberOfColumns . ' ');
        }

        if (empty($this->_columnWidths)) {
            if ($this->_spaceBetweenColumns) {
                  $writer->write('\colsx' . PHPRtfLite_Unit::getUnitInTwips($this->_spaceBetweenColumns) . ' ');
            }
        }
        else {
            $width = 0;
            foreach ($this->_columnWidths as $value) {
                $width += PHPRtfLite_Unit::getUnitInTwips($value);
            }

            $printableWidth = $this->_rtf->getPaperWidth() - $this->_rtf->getMarginLeft() - $this->_rtf->getMarginRight();
            $space = round((PHPRtfLite_Unit::getUnitInTwips($printableWidth) - $width) / (count($this->_columnWidths) - 1));

            $i = 1;
            foreach ($this->_columnWidths as $key => $value) {
                $writer->write('\colno' . $i . '\colw' . PHPRtfLite_Unit::getUnitInTwips($value));
                if (!empty($this->_columnWidths[$key])) {
                    $writer->write('\colsr' . $space);
                }
                $i++;
            }
            $writer->write(' ');
        }

        if ($this->_lineBetweenColumns) {
            $writer->write('\linebetcol ');
        }

        /*---Page part---*/
        if ($this->_isLandscape) {
            $writer->write('\lndscpsxn ');
        }

        if ($this->_paperWidth) {
            $writer->write('\pgwsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_paperWidth) . ' ');
        }

        if ($this->_paperHeight) {
            $writer->write('\pghsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_paperHeight) . ' ');
        }

        if ($this->_marginLeft) {
            $writer->write('\marglsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_marginLeft) . ' ');
        }

        if ($this->_marginRight) {
            $writer->write('\margrsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_marginRight) . ' ');
        }

        if ($this->_marginTop) {
            $writer->write('\margtsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_marginTop) . ' ');
        }

        if ($this->_marginBottom) {
            $writer->write('\margbsxn' . PHPRtfLite_Unit::getUnitInTwips($this->_marginBottom) . ' ');
        }

        if ($this->_gutter) {
            $writer->write('\guttersxn' . PHPRtfLite_Unit::getUnitInTwips($this->_gutter) . ' ');
        }

        if ($this->_useMirrorMargins) {
            $writer->write('\margmirsxn ');
        }

        if ($this->_font) {
            $writer->write($this->_font->getContent());
        }

        $writer->write("\r\n");
        parent::render();
        $writer->write("\r\n");
    }

}
