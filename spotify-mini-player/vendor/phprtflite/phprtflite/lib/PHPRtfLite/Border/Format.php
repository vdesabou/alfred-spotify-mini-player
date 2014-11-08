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
 * Class for border format.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Border
 */
class PHPRtfLite_Border_Format
{

    /**
     * constants for border format type
     */
    const TYPE_SINGLE   = 'single';
    const TYPE_DOT      = 'dot';
    const TYPE_DASH     = 'dash';
    const TYPE_DOTDASH  = 'dotdash';


    /**
     * @var integer
     */
    protected $_size;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var string
     */
    protected $_color;

    /**
     * rtf color table
     * @var PHPRtfLite_DocHead_ColorTable
     */
    protected $_colorTable;

    /**
     * @var integer
     */
    protected $_space;


    /**
     * constructor
     *
     * @param   float       $size   size of border
     * @param   string      $color  color of border (example '#ff0000' or '#f00')
     * @param   string      $type   represented by class constants PHPRtfLite_Border_Format::TYPE_*<br>
     *   Possible values:<br>
     *     TYPE_SINGLE:     single (default)<br>
     *     TYPE_DOT:        dot<br>
     *     TYPE_DASH:       dash<br>
     *     TYPE_DOTDASH:    dotdash<br>
     * @param   float       $space  space between borders and the paragraph
     */
    public function __construct($size = 0.0, $color = null, $type = null, $space = 0.0)
    {
        $this->_size    = round($size * PHPRtfLite::SPACE_IN_POINTS); // convert points to twips
        $this->_type    = $type;
        $this->_color   = $color;
        $this->_space   = PHPRtfLite_Unit::getUnitInTwips($space);
    }


    /**
     * Gets border format type as rtf code
     *
     * @return string rtf code
     */
    private function getTypeAsRtfCode()
    {
        switch ($this->_type) {
            case self::TYPE_DOT:
                return '\brdrdot';

            case self::TYPE_DASH:
                return '\brdrdash';

            case self::TYPE_DOTDASH:
                return '\brdrdashd';

            default:
                return '\brdrs';
        }
    }


    /**
     * gets border format type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }


    /**
     * gets border color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->_color;
    }


    /**
     * gets size in twips
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->_size;
    }


    /**
     * gets border space
     *
     * @return float
     */
    public function getSpace()
    {
        return $this->_space;
    }


    /**
     * sets rtf color table
     *
     * @param PHPRtfLite_DocHead_ColorTable $colorTable
     */
    public function setColorTable(PHPRtfLite_DocHead_ColorTable $colorTable)
    {
        if ($this->_color) {
            $colorTable->add($this->_color);
        }
        $this->_colorTable = $colorTable;
    }


    /**
     * gets rtf code
     *
     * @return string rtf code
     */
    public function getContent()
    {
        $content = ($this->_size > 0 ? $this->getTypeAsRtfCode() : '')
            . '\brdrw' . $this->_size
            . '\brsp' . $this->_space;

        if ($this->_color && $this->_colorTable) {
            $colorIndex = $this->_colorTable->getColorIndex($this->_color);
            if ($colorIndex !== false) {
                $content .= '\brdrcf' . $colorIndex;
            }
        }

        return $content . ' ';
    }

}