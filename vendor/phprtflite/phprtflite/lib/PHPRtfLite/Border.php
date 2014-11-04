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
 * Class for creating borders within rtf documents.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Border
 */
class PHPRtfLite_Border
{

    /**
     * @var PHPRtfLite
     */
    protected $_rtf;
    /**
     * @var PHPRtfLite_Border_Format
     */
    protected $_borderLeft;
    /**
     * @var PHPRtfLite_Border_Format
     */
    protected $_borderRight;
    /**
     * @var PHPRtfLite_Border_Format
     */
    protected $_borderTop;
    /**
     * @var PHPRtfLite_Border_Format
     */
    protected $_borderBottom;


    /**
     * constructor
     *
     * @param PHPRtfLite                $rtf
     * @param PHPRtfLite_Border_Format  $left
     * @param PHPRtfLite_Border_Format  $top
     * @param PHPRtfLite_Border_Format  $right
     * @param PHPRtfLite_Border_Format  $bottom
     */
    public function __construct(PHPRtfLite $rtf,
                                PHPRtfLite_Border_Format $left  = null,
                                PHPRtfLite_Border_Format $top   = null,
                                PHPRtfLite_Border_Format $right = null,
                                PHPRtfLite_Border_Format $bottom = null)
    {
        $this->_rtf = $rtf;
        if ($left) {
            $left->setColorTable($rtf->getColorTable());
        }
        $this->_borderLeft = $left;

        if ($top) {
            $top->setColorTable($rtf->getColorTable());
        }
        $this->_borderTop = $top;

        if ($right) {
            $right->setColorTable($rtf->getColorTable());
        }
        $this->_borderRight = $right;

        if ($bottom) {
            $bottom->setColorTable($rtf->getColorTable());
        }
        $this->_borderBottom    = $bottom;
    }


    /**
     * creates border by defining border format
     *
     * @param   PHPRtfLite  $rtf
     * @param   integer     $size   size of border
     * @param   string      $color  color of border (example '#ff0000' or '#f00')
     * @param   string      $type   represented by class constants PHPRtfLite_Border_Format::TYPE_*<br>
     *   Possible values:<br>
     *     PHPRtfLite_Border_Format::TYPE_SINGLE  'single'<br>
     *     PHPRtfLite_Border_Format::TYPE_DOT     'dot'<br>
     *     PHPRtfLite_Border_Format::TYPE_DASH    'dash'<br>
     *     PHPRtfLite_Border_Format::TYPE_DOTDASH 'dotdash'
     * @param   float       $space  space between borders and the paragraph
     * @param   boolean     $left   left border
     * @param   boolean     $top    top border
     * @param   boolean     $right  right border
     * @param   boolean     $bottom bottom border
     * @return  PHPRtfLite_Border
     */
    public static function create(PHPRtfLite $rtf, $size = 0, $color = null, $type = null, $space = 0.0,
                                  $left = true, $top = true, $right = true, $bottom = true)
    {
        $border = new self($rtf);
        $border->setBorders(new PHPRtfLite_Border_Format($size, $color, $type, $space), $left, $top, $right, $bottom);

        return $border;
    }


    /**
     * sets border format of element
     *
     * @param   PHPRtfLite_Border_Format $borderFormat
     * @param   boolean $left
     * @param   boolean $top
     * @param   boolean $right
     * @param   boolean $bottom
     */
    public function setBorders(PHPRtfLite_Border_Format $borderFormat,
                               $left = true, $top = true, $right = true, $bottom = true)
    {
        $borderFormat->setColorTable($this->_rtf->getColorTable());

        if ($left) {
            $this->_borderLeft  = $borderFormat;
        }

        if ($top) {
            $this->_borderTop   = $borderFormat;
        }

        if ($right) {
            $this->_borderRight = $borderFormat;
        }

        if ($bottom) {
            $this->_borderBottom = $borderFormat;
        }
    }


    /**
     * sets border format for left border
     *
     * @param PHPRtfLite_Border_Format $borderFormat
     */
    public function setBorderLeft(PHPRtfLite_Border_Format $borderFormat)
    {
        $borderFormat->setColorTable($this->_rtf->getColorTable());
        $this->_borderLeft = $borderFormat;
    }


    /**
     * gets border format of left border
     *
     * @return PHPRtfLite_Border_Format
     */
    public function getBorderLeft()
    {
        return $this->_borderLeft;
    }


    /**
     * sets border format for right border
     *
     * @param PHPRtfLite_Border_Format $borderFormat
     */
    public function setBorderRight(PHPRtfLite_Border_Format $borderFormat)
    {
        $borderFormat->setColorTable($this->_rtf->getColorTable());
        $this->_borderRight = $borderFormat;
    }


    /**
     * gets border format of right border
     *
     * @return PHPRtfLite_Border_Format
     */
    public function getBorderRight()
    {
        return $this->_borderRight;
    }


    /**
     * sets border format for top border
     *
     * @param PHPRtfLite_Border_Format $borderFormat
     */
    public function setBorderTop(PHPRtfLite_Border_Format $borderFormat)
    {
        $borderFormat->setColorTable($this->_rtf->getColorTable());
        $this->_borderTop = $borderFormat;
    }


    /**
     * gets border format of top border
     *
     * @return PHPRtfLite_Border_Format
     */
    public function getBorderTop()
    {
        return $this->_borderTop;
    }


    /**
     * sets border format for bottom border
     *
     * @param PHPRtfLite_Border_Format $borderFormat
     */
    public function setBorderBottom(PHPRtfLite_Border_Format $borderFormat)
    {
        $borderFormat->setColorTable($this->_rtf->getColorTable());
        $this->_borderBottom = $borderFormat;
    }


    /**
     * gets border format of bottom border
     *
     * @return PHPRtfLite_Border_Format
     */
    public function getBorderBottom()
    {
        return $this->_borderBottom;
    }


    /**
     * gets rtf code of object
     *
     * @param   string $type rtf code part
     * @return  string rtf code
     */
    public function getContent($type = '\\')
    {
        $content = '';
        if ($this->_borderLeft) {
            $content .= $type . 'brdrl' . $this->_borderLeft->getContent();
        }
        if ($this->_borderRight) {
            $content .= $type . 'brdrr' . $this->_borderRight->getContent();
        }
        if ($this->_borderTop) {
            $content .= $type . 'brdrt' . $this->_borderTop->getContent();
        }
        if ($this->_borderBottom) {
            $content .= $type . 'brdrb' . $this->_borderBottom->getContent();
        }
        return $content;
    }

}