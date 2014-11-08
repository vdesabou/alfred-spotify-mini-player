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
 * Class for creating cells of table in rtf documents.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>, Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Table
 */
class PHPRtfLite_Table_Cell extends PHPRtfLite_Container
{

    /**
     * constants for rotation directions
     */
    const ROTATE_RIGHT  = 'right';
    const ROTATE_LEFT   = 'left';

    /**
     * constants for text alignment
     */
    const TEXT_ALIGN_LEFT       = 'left';
    const TEXT_ALIGN_RIGHT      = 'right';
    const TEXT_ALIGN_CENTER     = 'center';
    const TEXT_ALIGN_JUSTIFY    = 'justify';

    /**
     * constants for vertical alignment
     */
    const VERTICAL_ALIGN_TOP    = 'top';
    const VERTICAL_ALIGN_BOTTOM = 'bottom';
    const VERTICAL_ALIGN_CENTER = 'center';


    /**
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     * @var PHPRtfLite_Table
     */
    protected $_table;

    /**
     * row index within the table
     * @var integer
     */
    protected $_rowIndex;

    /**
     * column index within the table
     * @var integer
     */
    protected $_columnIndex;

    /**
     * horizontal alignment
     * @var string
     */
    protected $_alignment;

    /**
     * vertical alignment
     * @var string
     */
    protected $_verticalAlignment;

    /**
     * font used for the cell
     * @var PHPRtfLite_Font
     */
    protected $_font;

    /**
     * rotation direction
     * @var string
     */
    protected $_rotateTo;

    /**
     * background color of the cell
     * @var string
     */
    protected $_backgroundColor;

    /**
     * border of the cell
     * @var PHPRtfLite_Border
     */
    protected $_border;

    /**
     * true, if cell is merged horizontally
     * @var boolean
     */
    protected $_horizontalMerged = false;

    /**
     * true, if cell is merged vertically
     * @var boolean
     */
    protected $_verticalMerged = false;

    /**
     * ture, if cell merge starts with this cell
     * @var boolean
     */
    protected $_verticalMergeStart = false;

    /**
     * width of cell
     * @var float
     */
    protected $_width;

    /**
     * @var string
     */
    protected $_pard = '';

    /**
     * cell padding left
     * @var float
     */
    protected $_paddingLeft;

    /**
     * cell padding right
     * @var float
     */
    protected $_paddingRight;

    /**
     * cell padding top
     * @var float
     */
    protected $_paddingTop;

    /**
     * cell padding bottom
     * @var float
     */
    protected $_paddingBottom;


    /**
     * constructor of cell
     *
     * @param   PHPRtfLite_Table    $table          table instance
     * @param   integer             $rowIndex       row index for cell in table
     * @param   integer             $columnIndex    column index for cell in table
     */
    public function __construct(PHPRtfLite_Table $table, $rowIndex, $columnIndex)
    {
        $this->_table       = $table;
        $this->_rowIndex    = $rowIndex;
        $this->_columnIndex = $columnIndex;
        $this->_rtf         = $table->getRtf();
    }


    /**
     * gets rtf
     *
     * @return PHPRtfLite
     */
    public function getRtf()
    {
        return $this->_rtf;
    }


    /**
     * adds nested table
     *
     * @param   string  $alignment
     * @return  PHPRtfLite_Table_Nested
     */
    public function addTable($alignment = PHPRtfLite_Table::ALIGN_LEFT)
    {
        $nestDepth = $this->_table->getNestDepth() + 1;
        $table = new PHPRtfLite_Table_Nested($this, $alignment, $nestDepth);
        $this->_elements[] = $table;

        return $table;
    }


    /**
     * gets table instance
     *
     * @return PHPRtfLite_Table
     */
    public function getTable()
    {
        return $this->_table;
    }


    /**
     * sets text alignment for cell
     * The method PHPRtfLite_Table->writeToCell() overrides it with alignment of an instance of PHPRtfLite_ParFormat.
     *
     * @param   string  $alignment  alignment of cell<br>
     *   Possible values:<br>
     *     TEXT_ALIGN_LEFT      => 'left'       - left alignment<br>
     *     TEXT_ALIGN_CENTER    => 'center'     - center alignment<br>
     *     TEXT_ALIGN_RIGHT     => 'right'      - right alignment<br>
     *     TEXT_ALIGN_JUSTIFY   => 'justify'    - justify alignment
     */
    public function setTextAlignment($alignment)
    {
        $this->_alignment = $alignment;
    }


    /**
     * gets text alignment for cell
     *
     * @return string
     */
    public function getTextAlignment()
    {
        return $this->_alignment;
    }


    /**
     * sets font to a cell
     * The method PHPRtfLite_Table->writeToCell() overrides it with another Font.
     *
     * @param   PHPRtfLite_Font $font
     */
    public function setFont(PHPRtfLite_Font $font)
    {
        $this->_rtf->registerFont($font);
        $this->_font = $font;
    }


    /**
     * gets font of cell
     *
     * @return PHPRtfLite_Font
     */
    public function getFont()
    {
        return $this->_font;
    }


    /**
     * sets vertical alignment of cell
     *
     * @param   string  $verticalAlignment vertical alignment of cell (default top).<br>
     *   Possible values:<br>
     *     VERTICAL_ALIGN_TOP       => 'top'    - top alignment<br>
     *     VERTICAL_ALIGN_CENTER    => 'center' - center alignment<br>
     *     VERTICAL_ALIGN_BOTTOM    => 'bottom' - bottom alignment
     */
    public function setVerticalAlignment($verticalAlignment)
    {
        $this->_verticalAlignment = $verticalAlignment;
    }


    /**
     * gets vertical alignment of cell
     *
     * @return string
     */
    public function getVerticalAlignment()
    {
        return $this->_verticalAlignment;
    }


    /**
     * rotates text of cell
     *
     * @param   string  $rotateTo  direction of rotation.<br>
     *   Possible values:<br>
     *     ROTATE_RIGHT => 'right'  - right<br>
     *     ROTATE_LEFT  => 'left'   - left
     */
    public function rotateTo($rotateTo)
    {
        $this->_rotateTo = $rotateTo;
    }


    /**
     * gets rotation direction of cell
     *
     * @return string
     */
    public function getRotateTo()
    {
        return $this->_rotateTo;
    }


    /**
     * sets background color
     *
     * @param string $color background color
     */
    public function setBackgroundColor($color)
    {
        $this->_backgroundColor = $color;
        $this->_rtf->getColorTable()->add($color);
    }


    /**
     * gets background color
     *
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->_backgroundColor;
    }


    /**
     * sets that cell is horizontal merged
     */
    public function setHorizontalMerged()
    {
        $this->_horizontalMerged = true;
    }


    /**
     * returns true, if cell is horizontal merged
     *
     * @return boolean
     */
    public function isHorizontalMerged()
    {
        return $this->_horizontalMerged;
    }


    /**
     * sets that cell is vertical merged
     */
    public function setVerticalMerged()
    {
        $this->_verticalMerged = true;
    }


    /**
     * returns true, if cell is horizontal merged
     *
     * @return boolean
     */
    public function isVerticalMerged()
    {
        return $this->_verticalMerged;
    }


    /**
     * sets vertical merge start
     */
    public function setVerticalMergeStart()
    {
        $this->_verticalMergeStart = true;
        $this->_verticalMerged = true;
    }


    /**
     * checks, if cell is first cell of a vertical cell range merge
     *
     * @return boolean
     */
    public function isVerticalMergedFirstInRange()
    {
        return $this->_verticalMergeStart;
    }


    /**
     * sets cell width
     *
     * @param float $width
     */
    public function setWidth($width)
    {
        $this->_width = $width;
    }


    /**
     * gets cell width
     *
     * @return float
     */
    public function getWidth()
    {
        if ($this->_width) {
            return $this->_width;
        }
        return $this->_table->getColumn($this->_columnIndex)->getWidth();
    }


    /**
     * gets border for specific cell
     *
     * @param   integer     $rowIndex
     * @param   integer     $columnIndex
     * @return  PHPRtfLite_Border
     */
    protected function getBorderForCell($rowIndex, $columnIndex)
    {
        $cell = $this->_table->getCell($rowIndex, $columnIndex);
        $border = $cell->getBorder();
        if ($border === null) {
            $border = new PHPRtfLite_Border($this->_rtf);
            $cell->setCellBorder($border);
        }

        return $border;
    }


    /**
     * sets border to a cell
     *
     * @param PHPRtfLite_Border $border
     */
    public function setBorder(PHPRtfLite_Border $border)
    {
        $borderFormatTop    = $border->getBorderTop();
        $borderFormatBottom = $border->getBorderBottom();
        $borderFormatLeft   = $border->getBorderLeft();
        $borderFormatRight  = $border->getBorderRight();

        if ($this->_border === null) {
            $this->_border = new PHPRtfLite_Border($this->_rtf);
        }
        if ($borderFormatLeft) {
            $this->_border->setBorderLeft($borderFormatLeft);
        }
        if ($borderFormatRight) {
            $this->_border->setBorderRight($borderFormatRight);
        }
        if ($borderFormatTop) {
            $this->_border->setBorderTop($borderFormatTop);
        }
        if ($borderFormatBottom) {
            $this->_border->setBorderBottom($borderFormatBottom);
        }

        if ($borderFormatTop && $this->_table->checkIfCellExists($this->_rowIndex - 1, $this->_columnIndex)) {
            $this->getBorderForCell($this->_rowIndex - 1, $this->_columnIndex)->setBorderBottom($borderFormatTop);
        }

        if ($borderFormatBottom && $this->_table->checkIfCellExists($this->_rowIndex + 1, $this->_columnIndex)) {
            $this->getBorderForCell($this->_rowIndex + 1, $this->_columnIndex)->setBorderTop($borderFormatBottom);
        }

        if ($borderFormatLeft && $this->_table->checkIfCellExists($this->_rowIndex, $this->_columnIndex - 1)) {
            $this->getBorderForCell($this->_rowIndex, $this->_columnIndex - 1)->setBorderRight($borderFormatLeft);
        }

        if ($borderFormatRight && $this->_table->checkIfCellExists($this->_rowIndex, $this->_columnIndex + 1)) {
            $this->getBorderForCell($this->_rowIndex, $this->_columnIndex + 1)->setBorderLeft($borderFormatRight);
        }
    }


    /**
     * sets cell border
     *
     * @param PHPRtfLite_Border $border
     */
    protected function setCellBorder(PHPRtfLite_Border $border)
    {
        $this->_border = $border;
    }


    /**
     * gets cell border
     *
     * @return PHPRtfLite_Border
     */
    public function getBorder()
    {
        return $this->_border;
    }


    /**
     * gets row index of cell
     *
     * @return integer
     */
    public function getRowIndex()
    {
        return $this->_rowIndex;
    }


    /**
     * gets column index of cell
     *
     * @return integer
     */
    public function getColumnIndex()
    {
        return $this->_columnIndex;
    }


    /**
     * sets cell paddings
     *
     * @param   integer $paddingLeft
     * @param   integer $paddingTop
     * @param   integer $paddingRight
     * @param   integer $paddingBottom
     */
    public function setCellPaddings($paddingLeft, $paddingTop, $paddingRight, $paddingBottom)
    {
        $this->_paddingLeft     = $paddingLeft;
        $this->_paddingTop      = $paddingTop;
        $this->_paddingRight    = $paddingRight;
        $this->_paddingBottom   = $paddingBottom;
    }


    /**
     * sets cell padding left
     *
     * @param integer $padding
     */
    public function setPaddingLeft($padding)
    {
        $this->_paddingLeft = $padding;
    }


    /**
     * sets cell padding right
     *
     * @param integer $padding
     */
    public function setPaddingRight($padding)
    {
        $this->_paddingRight = $padding;
    }


    /**
     * sets cell padding top
     *
     * @param integer $padding
     */
    public function setPaddingTop($padding)
    {
        $this->_paddingTop = $padding;
    }


    /**
     * sets cell padding bottom
     *
     * @param integer $padding
     */
    public function setPaddingBottom($padding)
    {
        $this->_paddingBottom = $padding;
    }


    /**
     * renders cell definition
     */
    public function renderDefinition()
    {
        $stream = $this->_rtf->getWriter();
        if ($this->isVerticalMerged()) {
            if ($this->isVerticalMergedFirstInRange()) {
                $stream->write('\clvmgf');
            }
            else {
                $stream->write('\clvmrg');
            }
        }

        $backgroundColor = $this->getBackgroundColor();
        if ($backgroundColor) {
            $colorTable = $this->_rtf->getColorTable();
            $stream->write('\clcbpat' . $colorTable->getColorIndex($backgroundColor) . ' ');
        }

        switch ($this->getVerticalAlignment()) {
            case self::VERTICAL_ALIGN_TOP:
                $stream->write('\clvertalt');
                break;

            case self::VERTICAL_ALIGN_CENTER:
                $stream->write('\clvertalc');
                break;

            case self::VERTICAL_ALIGN_BOTTOM:
                $stream->write('\clvertalb');
                break;
        }

        switch ($this->getRotateTo()) {
            case self::ROTATE_RIGHT:
                $stream->write('\cltxtbrl');
                break;

            case self::ROTATE_LEFT:
                $stream->write('\cltxbtlr');
                break;
        }

        // NOTE: microsoft and all other rtf readers I know confound left with top cell padding
        if ($this->_paddingLeft) {
            $stream->write('\clpadft3\clpadt' . PHPRtfLite_Unit::getUnitInTwips($this->_paddingLeft) . ' ');
        }
        if ($this->_paddingTop) {
            $stream->write('\clpadfl3\clpadl' . PHPRtfLite_Unit::getUnitInTwips($this->_paddingTop) . ' ');
        }
        if ($this->_paddingBottom) {
            $stream->write('\clpadfb3\clpadb' . PHPRtfLite_Unit::getUnitInTwips($this->_paddingBottom) . ' ');
        }
        if ($this->_paddingRight) {
            $stream->write('\clpadfr3\clpadr' . PHPRtfLite_Unit::getUnitInTwips($this->_paddingRight) . ' ');
        }

        $border = $this->getBorder();
        if ($border) {
            $stream->write($border->getContent('\cl'));
        }
    }


    /**
     * renders rtf code for cell
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();
        $stream->write("\r\n");

        // renders container elements
        parent::render();

        $containerElements = $this->getElements();
        $numOfContainerElements = count($containerElements);

        if ($this->_table->isNestedTable()) {
            // if last container element is not a nested table, close cell
            if ($numOfContainerElements == 0
                || !($containerElements[$numOfContainerElements - 1] instanceof PHPRtfLite_Table_Nested))
            {
                $stream->write('{\nestcell{\nonesttables\par}\pard}' . "\r\n");

                // if last cell of row, close row
                if ($this->getColumnIndex() == $this->_table->getColumnsCount()) {
                    $stream->write('{\*\nesttableprops ');
                    $row = $this->_table->getRow($this->_rowIndex);
                    $this->_table->renderRowDefinition($row);
                    $stream->write('\nestrow}');
                }
            }
        }
        else {
            if ($numOfContainerElements > 0
                && $containerElements[$numOfContainerElements - 1] instanceof PHPRtfLite_Table_Nested)
            {
                $stream->write('\intbl\itap1\~');
            }
            // closing tag for cell definition
            $stream->write('\cell');
        }
        $stream->write("\r\n");
    }


    /**
     * gets cell alignment
     *
     * @return string
     */
    public function getCellAlignment()
    {
        switch ($this->_alignment) {
            case self::TEXT_ALIGN_CENTER:
                return '\qc';

            case self::TEXT_ALIGN_RIGHT:
                return '\qr';

            case self::TEXT_ALIGN_JUSTIFY:
                return '\qj';

            default:
                return '\ql';
        }
    }

}