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
 * Class for displaying wmf images in rtf documents.
 * @since       1.2
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Image
 */
class PHPRtfLite_Image_Wmf extends PHPRtfLite_Image
{

    /**
     * true, if Aldus header has been found in wmf image
     * @var boolean
     */
    private $_headerStartBytesFound = false;


    /**
     * constructor
     *
     * @param PHPRtfLite    $rtf
     * @param resource      $stream
     * @param float         $width  optional
     * @param float         $height optional
     */
    public function __construct(PHPRtfLite $rtf, $stream, $width = null, $height = null)
    {
        parent::__construct($rtf, $stream, $width, $height);
        $this->_imageRtfType = '\wmetafile8';
        $this->setImageDimension();
    }


    /**
     * code for parsing the wmf is originally from: http://www.fpdf.de/downloads/addons/55/
     * as an addon for FPDF, written by Martin HALL-MAY
     */
    private function setImageDimension()
    {
        $headerSize = $this->getHeaderSize();
        fseek($this->_stream, $headerSize);

        while (!feof($this->_stream)) {
            $recordInfo = unpack('Lsize/Sfunc', fread($this->_stream, 6));

            // size of record given in WORDs (= 2 bytes)
            $size = $recordInfo['size'];

            // func is number of GDI function
            $func = $recordInfo['func'];

            // parameters are read as one block and processed
            // as necessary by the case statement below.
            // the data are stored in little-endian format and are unpacked using:
            // s - signed 16-bit int
            // S - unsigned 16-bit int (or WORD)
            // L - unsigned 32-bit int (or DWORD)
            // NB. parameters to GDI functions are stored in reverse order
            // however structures are not reversed,
            // e.g. POINT { int x, int y } where x=3000 (0x0BB8) and y=-1200 (0xFB50)
            // is stored as B8 0B 50 FB

            switch ($func) {
                case 0x020c:  // SetWindowExt
                    if ($size > 3) {
                        $params = fread($this->_stream, 2 * ($size - 3));
                        $sizes = array_reverse(unpack('s2', $params));
                        $this->setImageWidth(PHPRtfLite_Unit::getPointsInTwips($sizes[0]));
                        $this->setImageHeight(PHPRtfLite_Unit::getPointsInTwips($sizes[1]));
                    }
                    return;
                case 0x0000:
                    return;
            }
        }
    }


    /**
     * Get header size of wmf.
     *
     * @return integer $headerSize
     */
    private function getHeaderSize()
    {
        // reset stream pointer to 0
        fseek($this->_stream, 0);
        // check for Aldus placeable metafile header
        // L: vorzeichenloser Long-Typ (immer 32 Bit, Byte-Folge maschinenabhängig)
        // magic:
        $magic = fread($this->_stream, 4);
        $headerSize = 18; // WMF header
        if ($magic == "\xd7\xcd\xc6\x9a") {
            $this->_headerStartBytesFound = true;
            $headerSize += 22; // Aldus header
        }
        return $headerSize;
    }


    /**
     * gets rtf code of wmf
     *
     * @return string rtf code
     */
    public function render()
    {
        $startFrom = $this->_headerStartBytesFound ? 22 : 0;
        $this->writeIntoRtfStream($startFrom);
    }

}