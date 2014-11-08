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
 * Class for rtf numberings.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_List
 */
class PHPRtfLite_List_Numbering extends PHPRtfLite_List
{

    /**
     * constants for numbering types
     */
    const TYPE_ALPHA_UPPER  = 1;
    const TYPE_ALPHA_LOWER  = 2;
    const TYPE_ROMAN_UPPER  = 3;
    const TYPE_ROMAN_LOWER  = 4;
    const TYPE_ARABIC_NUM   = 5;


    /**
     * @var string
     */
    protected $_prefix  = '';

    /**
     * @var string
     */
    protected $_suffix  = '.';

    /**
     * @var string
     */
    protected $_separator = '.';


    /**
     * sets prefix
     *
     * @param   string  $prefix
     * @return  $this
     */
    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;
        return $this;
    }


    /**
     * sets suffix
     *
     * @param   string  $suffix
     * @return  $this
     */
    public function setSuffix($suffix)
    {
        $this->_suffix = $suffix;
        return $this;
    }


    /**
     * sets separator
     *
     * @param   string  $separator
     * @return  $this
     */
    public function setSeparator($separator)
    {
        $this->_separator = $separator;
        return $this;
    }


    /**
     * gets number
     *
     * @param   integer $number
     * @return  string
     */
    public function getNumber($number)
    {
        switch ($this->_type) {
            case self::TYPE_ALPHA_UPPER:
                return $this->getAlphaNumber($number);
            case self::TYPE_ALPHA_LOWER:
                return $this->getAlphaNumber($number, true);
            case self::TYPE_ROMAN_UPPER:
                return $this->getRomanNumber($number);
            case self::TYPE_ROMAN_LOWER:
                return $this->getRomanNumber($number, true);
            // default is alpha
            default:
                return $number;
        }
    }


    /**
     * gets alpha number
     *
     * @param   integer $number
     * @param   boolean $lowerCase
     * @return  string
     */
    private function getAlphaNumber($number, $lowerCase = false)
    {
        $asciiStartIndex = $lowerCase ? 97 : 65;
        $alpha = '';
        while ($number > 0) {
            $modulus = ($number - 1) % 26;
            $alpha .= chr($modulus + $asciiStartIndex);
            $number = floor(($number - 1) / 26);
        }
        return strrev($alpha);
    }


    /**
     * gets roman number
     * Code based from:
     *
     * @see http://www.sajithmr.me/php-decimal-to-roman-number-conversion/
     *
     * @param  integer $number
     * @param  boolean $lowerCase
     * @return string
     */
    private function getRomanNumber($number, $lowerCase = false)
    {
        $roman = '';
        $romanCharMapping = array(
            'M'     => 1000,
            'CM'    => 900,
            'D'     => 500,
            'CD'    => 400,
            'C'     => 100,
            'XC'    => 90,
            'L'     => 50,
            'XL'    => 40,
            'X'     => 10,
            'IX'    => 9,
            'V'     => 5,
            'IV'    => 4,
            'I'     => 1
        );

        foreach ($romanCharMapping as $romanChar => $romanValue) {
            // Determine the number of matches
            $matches = intval($number / $romanValue);

             // Store that many characters
            $roman .= str_repeat($romanChar, $matches);

            // Substract that from the number
            $number = $number % $romanValue;
        }

        // The Roman numeral should be built, return it
        return $lowerCase ? strtolower($roman) : $roman;
    }


    /**
     * gets font index for list character
     *
     * @return string
     */
    protected function getListCharFontIndex()
    {
        if ($this->_font) {
            $fontFamily = $this->_font->getFontFamily();
            return $this->_rtf->getFontTable()->getFontIndex($fontFamily);
        }

        return parent::getListCharFontIndex();
    }


    /**
     * gets list character
     *
     * @param  integer $number
     * @return string
     */
    protected function getListCharacter($number)
    {
        $listCharacter = $this->_prefix . $this->getNumber($number) . $this->_suffix;
        return PHPRtfLite::quoteRtfCode($listCharacter);
    }

}
