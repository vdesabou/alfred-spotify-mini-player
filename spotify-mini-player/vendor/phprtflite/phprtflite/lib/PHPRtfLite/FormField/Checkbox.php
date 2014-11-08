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
 * class for check box form fields in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_FormField
 */
class PHPRtfLite_FormField_Checkbox extends PHPRtfLite_FormField
{

    /**
     * flag, if checkbox is checked
     * @var boolean
     */
    private $_checked   = false;

    /**
     * size of checkbox
     * @var integer
     */
    private $_size      = 20;


    /**
     * gets form field type
     *
     * @return string
     */
    protected function getType()
    {
        return 'FORMCHECKBOX';
    }


    /**
     * sets checkbox to be set
     */
    public function setChecked()
    {
        $this->_checked = true;
    }


    /**
     * sets default value
     *
     * @param   boolean $value
     */
    public function setDefaultValue($value)
    {
        $this->_checked = $value == true || $value == '1';
    }


    /**
     * sets size of checkbox
     *
     * @param   integer s$size
     */
    public function setSize($size)
    {
        $this->_size = $size > 0 ? $size : 20;
    }


    /**
     * gets checkbox rtf code
     *
     * @return string
     */
    protected function getRtfCode()
    {
        return '{\fftype1\ffres25\ffhps' . $this->_size . '\ffdefres' . ($this->_checked ? '1' : '0') . '}';
    }

}
