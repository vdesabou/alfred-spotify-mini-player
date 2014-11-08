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
 * Abstract class for rtf lists (numberings and enumerations).
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_List
 */
abstract class PHPRtfLite_List
{

    /**
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     * type of list
     * @var integer
     */
    protected $_type;

    /**
     * @var PHPRtfLite_Font
     */
    protected $_font;

    /**
     * @var PHPRtfLite_ParFormat
     */
    protected $_parFormat;

    /**
     * indent to list character in centimeter
     * @var float
     */
    protected $_listIndent = PHPRtfLite_Unit::UNIT_CM;

    /**
     * indent to text in centimeter
     * @var float
     */
    protected $_textIndent = PHPRtfLite_Unit::UNIT_CM;

    /**
     * @var array
     */
    protected $_items = array();


    /**
     * gets list character
     *
     * @param   integer $number
     * @return  string
     */
    abstract protected function getListCharacter($number);


    /**
     * constructor
     *
     * @param   PHPRtfLite              $rtf
     * @param   integer                 $type
     * @param   PHPRtfLite_Font         $font
     * @param   PHPRtfLite_ParFormat    $parFormat
     */
    public function __construct(
        PHPRtfLite $rtf,
        $type = null,
        PHPRtfLite_Font $font = null,
        PHPRtfLite_ParFormat $parFormat = null
    ) {
        $this->_rtf         = $rtf;
        $this->_type        = $type;
        $this->_font        = $font;
        $this->_parFormat   = $parFormat;
    }


    /**
     * adds item to list
     *
     * @param   string                  $text
     * @param   PHPRtfLite_Font         $font
     * @param   PHPRtfLite_ParFormat    $parFormat
     * @param   boolean                 $convertTagsToRtf
     * @return  $this
     */
    public function addItem($text,
        PHPRtfLite_Font $font = null,
        PHPRtfLite_ParFormat $parFormat = null,
        $convertTagsToRtf = true
    ) {
        if ($font === null) {
            $font = $this->_font;
        }
        if ($parFormat === null) {
            $parFormat = $this->_parFormat;
        }

        $element = new PHPRtfLite_Element($this->_rtf, $text, $font, $parFormat);
        if ($convertTagsToRtf) {
            $element->setConvertTagsToRtf();
        }
        $this->_items[] = $element;

        return $this;
    }


    /**
     * adds element to list
     *
     * @param   PHPRtfLite_Element  $element
     * @return  PHPRtfLite_List
     */
    public function addElement(PHPRtfLite_Element $element)
    {
        $this->_items[] = $element;
        return $this;
    }


    /**
     * adds list to list
     *
     * @param   PHPRtfLite_List  $list
     * @return  $this
     */
    public function addList(PHPRtfLite_List $list)
    {
        $this->_items[] = $list;
        $list->_textIndent += $this->_textIndent;
        return $this;
    }


    /**
     * sets list indent
     *
     * @param   float   $indent
     */
    public function setListIndent($indent)
    {
        $this->_listIndent = PHPRtfLite_Unit::getUnitInTwips($indent);
    }


    /**
     * sets text indent
     *
     * @param   float   $indent
     */
    public function setTextIndent($indent)
    {
        $this->_textIndent = PHPRtfLite_Unit::getUnitInTwips($indent);
    }


    /**
     * gets font
     *
     * @return PHPRtfLite_Font
     */
    public function getFont()
    {
        return $this->_font;
    }


    /**
     * gets par format
     *
     * @return PHPRtfLite_ParFormat
     */
    public function getParFormat()
    {
        return $this->_parFormat;
    }


    /**
     * default implementation for getting font index for list character
     *
     * @return string
     */
    protected function getListCharFontIndex()
    {
        return '0';
    }


    /**
     * renders list
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();
        $number = 0;

        foreach ($this->_items as $item) {
            // item is a list
            if ($item instanceof PHPRtfLite_List_Numbering) {
                if ($this instanceof PHPRtfLite_List_Numbering) {
                    $item->setPrefix($this->_prefix . $this->getNumber($number) . $this->_separator);
                    $item->setSuffix($this->_suffix);
                }
            }
            // item is a element
            else {
                $number++;
                $listCharFontIndex  = $this->getListCharFontIndex();
                $listCharacter      = $this->getListCharacter($number);
                $listCharDefinition = '{\*\pn\pnlvlblt' . '\pnf' . $listCharFontIndex;
                if ($this->_font) {
                    $listCharDefinition .= '\pnfs' . ($this->_font->getSize() * 2);
                    if (($color = $this->_font->getColor())) {
                        $listCharDefinition .= '\pncf' . $this->_rtf->getColorTable()->getColorIndex($color);
                    }
                }
                $listCharDefinition .= '\pnindent0{\pntxtb ' . $listCharacter . '}}';

                $textIndent = $this->_listIndent + $this->_textIndent;
                $stream->write('\nowidctlpar\fi-' . $this->_listIndent . '\li' . $textIndent . "\r\n");
                $stream->write($listCharDefinition);
            }

            // renders item
            $item->render();

            if (false == ($item instanceof PHPRtfLite_List)) {
                $stream->write('\par\pard' . "\r\n");
            }
        }
    }

}
