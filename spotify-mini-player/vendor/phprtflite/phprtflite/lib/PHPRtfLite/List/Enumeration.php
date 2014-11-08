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
 * Class for rtf enumerations.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_List
 */
class PHPRtfLite_List_Enumeration extends PHPRtfLite_List
{

    /**
     * constants for enumeration types
     */
    const TYPE_BULLET   = 1;
    const TYPE_ARROW    = 2;
    const TYPE_CIRCLE   = 3;
    const TYPE_SQUARE   = 4;
    const TYPE_DIAMOND  = 5;


    /**
     * @var PHPRtfLite_Font
     */
    protected $_listCharFont;

    /**
     * list charactor for the enumeration
     * @var string
     */
    protected $_listChar;


    /**
     * constructor
     *
     * @param   PHPRtfLite              $rtf
     * @param   integer                 $type
     * @param   PHPRtfLite_Font         $font
     * @param   PHPRtfLite_ParFormat    $parFormat
     */
    public function __construct(PHPRtfLite $rtf, $type = null,
                                PHPRtfLite_Font $font = null, PHPRtfLite_ParFormat $parFormat = null)
    {
        parent::__construct($rtf, $type, $font, $parFormat);

        $this->initListCharDefinition();
    }


    /**
     * inits list character definition
     */
    protected function initListCharDefinition()
    {
        switch ($this->_type) {
            case self::TYPE_ARROW:
                $this->_listCharFont = new PHPRtfLite_Font(10, 'Wingdings');
                $this->_listChar = '\\\'d8';
                break;
            case self::TYPE_CIRCLE:
                $this->_listCharFont = new PHPRtfLite_Font(10, 'Courier New');
                $this->_listChar = ' o';
                break;
            case self::TYPE_DIAMOND:
                $this->_listCharFont = new PHPRtfLite_Font(10, 'Wingdings');
                $this->_listChar = ' v';
                break;
            case self::TYPE_SQUARE:
                $this->_listCharFont = new PHPRtfLite_Font(10, 'Wingdings');
                $this->_listChar = '\\\'a7';
                break;
            // default is bullet
            default:
                $this->_listCharFont = new PHPRtfLite_Font(10, 'Symbol');
                $this->_listChar = '\\\'B7';
        }

        $this->_rtf->registerFont($this->_listCharFont);
    }


    /**
     * gets font index for list character
     *
     * @return string
     */
    protected function getListCharFontIndex()
    {
        $fontFamily = $this->_listCharFont->getFontFamily();
        return $this->_rtf->getFontTable()->getFontIndex($fontFamily);
    }


    /**
     * gets list character
     *
     * @param   integer $number
     * @return  string
     */
    protected function getListCharacter($number)
    {
        return $this->_listChar;
    }

}