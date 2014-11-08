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
 * Class for writing the rtf output into a string.
 * @since       1.2
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 */
class PHPRtfLite_Writer_String implements PHPRtfLite_Writer_Interface
{

    /**
     * falg, true if handle is closed
     * @var boolean
     */
    private $_closed = true;
    /**
     * content
     * @var string
     */
    private $_content = '';


    /**
     * opens the handle
     */
    public function open()
    {
        $this->_closed = false;
        $this->_content = '';
    }


    /**
     * closes the handle
     */
    public function close()
    {
        $this->_closed = true;
    }


    /**
     * gets written content
     *
     * @return string
     */
    public function getContent()
    {
        $this->close();
        return $this->_content;
    }


    /**
     * write content - internal use
     * NOTE: Re-opens the handle if it's closed and empties content.
     *       May be not the best behavior, yet, but it is as it is.
     *       It is used for PHPRtfLite's output generation.
     *
     * @param string $data
     */
    public function write($data)
    {
        if ($this->_closed) {
            $this->open();
        }
        $this->_content .= $data;
    }

}