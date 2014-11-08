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
 * class for creating elements used in containers like sections, footers and headers.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Element
 */
class PHPRtfLite_Element_Hyperlink extends PHPRtfLite_Element
{

    /**
     * @var string
     */
    protected $_hyperlink = '';


    /**
     * sets hyperling
     *
     * @param string $hyperlink
     */
    public function setHyperlink($hyperlink)
    {
        $this->_hyperlink = $hyperlink;
    }


    /**
     * gets opening token
     *
     * @return string
     */
    protected function getOpeningToken()
    {
        $hyperlink = PHPRtfLite::quoteRtfCode($this->_hyperlink);
        return '{\field {\*\fldinst {HYPERLINK "' . $hyperlink . '"}}{\\fldrslt {';
    }


    /**
     * gets closing token
     *
     * @return string
     */
    protected function getClosingToken()
    {
        return '}}}';
    }

}