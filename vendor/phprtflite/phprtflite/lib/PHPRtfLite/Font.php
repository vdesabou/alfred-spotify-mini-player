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
 * Class for fonts in rtf documents.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 */
class PHPRtfLite_Font
{

    /**
     * constants for types of animated text
     */
    const ANIMATE_LAS_VEGAS_LIGHTS     = 1;
    const ANIMATE_BLINKING_BACKGROUND  = 2;
    const ANIMATE_SPARKLE_TEXT         = 3;
    const ANIMATE_MARCHING_BLACK_ANTS  = 4;
    const ANIMATE_MARCHING_RED_ANTS    = 5;
    const ANIMATE_SHIMMER              = 6;

    /**
     * @var PHPRtfLite_DocHead_ColorTable
     */
    protected $_colorTable;

    /**
     * @var PHPRtfLite_DocHead_FontTable
     */
    protected $_fontTable;

    /**
     * font size
     * @var integer
     */
    protected $_size;

    /**
     * font family
     * @var string
     */
    protected $_fontFamily;

    /**
     * font color
     * @var string
     */
    protected $_color;

    /**
     * background color
     * @var string
     */
    protected $_backgroundColor;

    /**
     * true, if font is bold
     * @var boolean
     */
    protected $_isBold;

    /**
     * true, if font is italic
     * @var boolean
     */
    protected $_isItalic;

    /**
     * true, if font is underlined
     * @var boolean
     */
    protected $_isUnderlined;

    /**
     * true, if font is striked
     * @var boolean
     */
    protected $_isStriked;

    /**
     * true, if font is double striked
     * @var boolean
     */
    protected $_isDoubleStriked;

    /**
     * text animation
     * @var string
     */
    protected $_animation;


    /**
     * constructor
     *
     * @param   integer $size               font size
     * @param   string  $fontFamily         font family (etc. "Times new Roman", "Arial" and other)
     * @param   string  $color              font color
     * @param   string  $backgroundColor    background color of font
     */
    public function __construct($size = 10, $fontFamily = null, $color = null, $backgroundColor = null)
    {
        $this->_size            = $size;
        $this->_fontFamily      = $fontFamily;
        $this->_color           = $color;
        $this->_backgroundColor = $backgroundColor;
    }


    /**
     * sets rtf color table
     *
     * @param PHPRtfLite_DocHead_ColorTable $colorTable
     */
    public function setColorTable(PHPRtfLite_DocHead_ColorTable $colorTable)
    {
        if (!empty($this->_color)) {
            $colorTable->add($this->_color);
        }
        if (!empty($this->_backgroundColor)) {
            $colorTable->add($this->_backgroundColor);
        }
        $this->_colorTable = $colorTable;
    }


    /**
     * sets rtf font table
     *
     * @param PHPRtfLite_DocHead_FontTable $fontTable
     */
    public function setFontTable(PHPRtfLite_DocHead_FontTable $fontTable)
    {
        if (!empty($this->_fontFamily)) {
            $fontTable->add($this->_fontFamily);
        }
        $this->_fontTable = $fontTable;
    }


    /**
     * gets font family
     *
     * @return string
     */
    public function getFontFamily()
    {
        return $this->_fontFamily;
    }


    /**
     * gets font size
     *
     * @return integer
     */
    public function getSize()
    {
        return $this->_size;
    }


    /**
     * gets font color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->_color;
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
     * sets text bold
     *
     * @param boolean $bold
     */
    public function setBold($bold = true)
    {
        $this->_isBold = $bold;
    }


    /**
     * returns true, if text is styled bold
     *
     * @return boolean
     */
    public function isBold()
    {
        return $this->_isBold;
    }


    /**
     * sets text italic
     *
     * @param boolean $italic
     */
    public function setItalic($italic = true)
    {
        $this->_isItalic = $italic;
    }


    /**
     * returns true, if text is styled italic
     *
     * @return boolean
     */
    public function isItalic()
    {
        return $this->_isItalic;
    }


    /**
     * sets text underline
     *
     * @param boolean $underlined
     */
    public function setUnderline($underlined = true)
    {
        $this->_isUnderlined = $underlined;
    }


    /**
     * returns true, if text is styled underlined
     *
     * @return boolean
     */
    public function isUnderlined()
    {
        return $this->_isUnderlined;
    }


    /**
     * sets striked text
     *
     * @param boolean $strike
     */
    public function setStriked($striked = true)
    {
        $this->_isStriked = $striked;

        if ($striked) {
            $this->_isDoubleStriked = false;
        }
    }


    /**
     * returns true, if text is striked
     *
     * @return  boolean
     */
    public function isStriked()
    {
        return $this->_isStriked;
    }


    /**
     * sets double striked text
     *
     * @param boolean $strike
     */
    public function setDoubleStriked($striked = true)
    {
        $this->_isDoubleStriked = $striked;

        if ($striked) {
            $this->_isStriked = false;
        }
    }


    /**
     * returns true, if text is striked double
     *
     * @return boolean
     */
    public function isDoubleStriked()
    {
        return $this->_isDoubleStriked;
    }


    /**
     * sets animation for text
     *
     * @param integer $animation animation<br>
     *   Represented by class constants ANIMATE_*<br>
     *   Possible values:<br>
     *     ANIMATE_LAS_VEGAS_LIGHTS     => 1    - las vegas lights
     *     ANIMATE_BLINKING_BACKGROUND  => 2    - blinking brackground
     *     ANIMATE_SPARKLE_TEXT         => 3    - sparkle text
     *     ANIMATE_MARCHING_BLACK_ANTS  => 4    - marching black ants
     *     ANIMATE_MARCHING_RED_ANTS    => 5    - marching red ants
     *     ANIMATE_SHIMMER              => 6    - shimmer
     */
    public function setAnimation($animation)
    {
        $this->_animation = $animation;
    }


    /**
     * gets rtf code of font
     *
     * @return string rtf code
     */
    public function getContent()
    {
        $content = '';

        if ($this->_size > 0) {
            $content .= '\fs' . ($this->_size * 2) . ' ';
        }

        if ($this->_fontFamily && $this->_fontTable) {
            $fontIndex = $this->_fontTable->getFontIndex($this->_fontFamily);
            if ($fontIndex !== false) {
                $content .= '\f' . $fontIndex . ' ';
            }
        }

        if ($this->_color && $this->_colorTable) {
            $colorIndex = $this->_colorTable->getColorIndex($this->_color);
            if ($colorIndex !== false) {
                $content .= '\cf' . $colorIndex . ' ';
            }
        }

        if ($this->_backgroundColor && $this->_colorTable) {
            $colorIndex = $this->_colorTable->getColorIndex($this->_backgroundColor);
            if ($colorIndex !== false) {
                $content .= '\chcbpat' . $colorIndex . ' ';
            }
        }

        if ($this->_isBold) {
            $content .= '\b ';
        }
        if ($this->_isItalic) {
            $content .= '\i ';
        }
        if ($this->_isUnderlined) {
            $content .= '\ul ';
        }
        if ($this->_animation) {
            $content .= '\animtext' . $this->_animation;
        }
        if ($this->_isStriked) {
            $content .= '\strike ' . $this->_animation;
        }
        elseif ($this->_isDoubleStriked) {
            $content .= '\striked1 ' . $this->_animation;
        }

        return $content;
    }


    /**
     * closes font content
     *
     * @return string
     */
    public function getClosingContent()
    {
        $content = '';
        if ($this->_color) {
            $content .= '\cf0 ';
        }
        if ($this->_backgroundColor) {
            $content .= '\chcbpat0 ';
        }
        if ($this->_isBold) {
            $content .= '\b0 ';
        }
        if ($this->_isItalic) {
            $content .= '\i0 ';
        }
        if ($this->_isUnderlined) {
            $content .= '\ul0 ';
        }
        return $content;
    }

}