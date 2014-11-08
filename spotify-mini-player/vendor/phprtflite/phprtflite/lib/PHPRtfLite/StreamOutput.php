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
 * Class for writing the rtf output.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 */
class PHPRtfLite_StreamOutput implements PHPRtfLite_Writer_Interface
{

    /**
     * filename
     * @var string
     */
    private $_filename;

    /**
     * handler for stream
     * @var resource
     */
    private $_handle;


    /**
     * set filename
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->_filename = $filename;
    }


    /**
     * gets filename
     *
     * @return  string
     */
    public function getFilename()
    {
        return $this->_filename;
    }


    /**
     * opens file stream
     */
    public function open()
    {
        if ($this->_handle !== null) {
            return;
        }

        $this->_handle = fopen($this->_filename, 'wr', false);
        if (!$this->_handle) {
            throw new PHPRtfLite_Exception("Could not open rtf output stream (url: $url)!");
        }
        flock($this->_handle, LOCK_EX);
    }


    /**
     * closes file handler
     */
    public function close()
    {
        if ($this->_handle !== null) {
            fclose($this->_handle);
            $this->_handle = null;
        }
    }


    /**
     * writes string to file handler
     *
     * @param string $data
     */
    public function write($data)
    {
        if ($this->_handle === null) {
            throw new PHPRtfLite_Exception('Can not write on an undefined handle! Forgot to call open()?');
        }
        fwrite($this->_handle, $data);
    }


    /**
     * gets written content
     *
     * @return type
     */
    public function getContent()
    {
        $this->close();
        if (file_exists($this->_filename)) {
            return file_get_contents($this->_filename);
        }
        return '';
    }

}