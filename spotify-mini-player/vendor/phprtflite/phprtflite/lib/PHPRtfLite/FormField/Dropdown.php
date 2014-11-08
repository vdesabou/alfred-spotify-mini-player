<?php
/*
    PHPRtfLite
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
 * class for dropdown form fields in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_FormField
 */
class PHPRtfLite_FormField_Dropdown extends PHPRtfLite_FormField
{

    /**
     * dropdown items
     * @var array
     */
    protected $_items = array();


    /**
     * adds dropdown item
     *
     * @param string $text
     */
    public function addItem($text)
    {
        $this->_items[] = $text;
    }


    /**
     * sets dropdown items
     *
     * @param array $items array of strings
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }


    /**
     * gets form field type
     *
     * @return string
     */
    protected function getType()
    {
        return 'FORMDROPDOWN';
    }


    /**
     * gets rtf code for dropdown form field
     *
     * @return string
     */
    public function getRtfCode()
    {
        $content = '{\fftype2\ffres25\fftypetxt0\ffhaslistbox\ffdefres0';
        foreach ($this->_items as $text) {
            $text = PHPRtfLite_Utf8::getUnicodeEntities($text, $this->_rtf->getCharset());
            $content .= '{\*\ffl ' . $text . '}';
        }
        $content .= '}';
        return $content;
    }

}