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
 * Class for displaying images in rtf documents.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 */
abstract class PHPRtfLite_Image
{

    /**
     * class constants for image type
     */
    const TYPE_JPEG = 'jpeg';
    const TYPE_PNG  = 'png';
    const TYPE_WMF  = 'wmf';

    /**
     * rtf document
     * @var PHPRtfLite
     */
    protected $_rtf;
    /**
     * stream as php resource
     * @var resource
     */
    protected $_stream;
    /**
     * par format
     * @var PHPRtfLite_ParFormat
     */
    protected $_parFormat;

    /**
     * rtf border
     * @var PHPRtfLite_Border
     */
    protected $_border;

    /**
     * resize to width
     * @var float
     */
    protected $_width;
    /**
     * resize to height
     * @var float
     */
    protected $_height;
    /**
     * original image width
     * @var float
     */
    protected $_imageWidth;
    /**
     * original image height
     * @var integer
     */
    protected $_imageHeight;
    /**
     * image rtf type
     * @var string
     */
    protected $_imageRtfType;
    /**
     * flag, true if image file is missing
     * @var bool
     */
    protected $_isMissing = false;


    /**
     * constructor
     *
     * @param PHPRtfLite            $rtf
     * @param resource              $stream
     * @param float                 $width  optional
     * @param float                 $height optional
     */
    public function __construct(PHPRtfLite $rtf, $stream, $width = null, $height = null)
    {
        $this->_rtf    = $rtf;
        $this->_stream = $stream;
        $this->_width  = $width;
        $this->_height = $height;
    }


    /**
     * destructor - closes stream
     */
    public function __destruct()
    {
        if (is_resource($this->_stream)) {
            fclose($this->_stream);
        }
    }


    /**
     * sets paragraph format for image
     *
     * @param PHPRtfLite_ParFormat $parFormat
     */
    public function setParFormat(PHPRtfLite_ParFormat $parFormat)
    {
        $this->_parFormat = $parFormat;
        $parFormat->setColorTable($this->_rtf->getColorTable());
    }


    /**
     * gets paragraph format for image
     *
     * @return PHPRtfLite_ParFormat
     */
    public function getParFormat()
    {
        return $this->_parFormat;
    }


    /**
     * sets image width
     *
     * @param   float   $width  if not defined image is displayed by it's height.
     */
    public function setWidth($width)
    {
        $this->_width = $width;
    }


    /**
     * gets image width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->_width;
    }


    /**
     * sets image height
     *
     * @param   float   $height if not defined image is displayed by it's width.
     */
    public function setHeight($height)
    {
        $this->_height = $height;
    }


    /**
     * gets image height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->_height;
    }


    /**
     * sets border
     *
     * @param PHPRtfLite_Border $border
     */
    public function setBorder(PHPRtfLite_Border $border)
    {
        $this->_border = $border;
    }


    /**
     * gets border
     *
     * @return PHPRtfLite_Border
     */
    public function getBorder()
    {
        return $this->_border;
    }


    public function isMissing()
    {
        return $this->_isMissing;
    }


    public function setIsMissing()
    {
        $this->_isMissing = true;
    }


    /**
     * gets rtf image width
     *
     * @return integer
     */
    private function getImageRtfWidth()
    {
        if ($this->_width > 0) {
            return PHPRtfLite_Unit::getUnitInTwips($this->_width);
        }

        $imageWidth = $this->_imageWidth ? $this->_imageWidth : 100;
        if ($this->_height > 0) {
            $imageHeight = $this->_imageHeight ? $this->_imageHeight : 100;
            $width = ($imageWidth / $imageHeight) * $this->_height;
            return PHPRtfLite_Unit::getUnitInTwips($width);
        }

        return PHPRtfLite_Unit::getPointsInTwips($imageWidth);
    }


    /**
     * gets rtf image height
     *
     * @return integer
     */
    private function getImageRtfHeight()
    {
        if ($this->_height > 0) {
            return PHPRtfLite_Unit::getUnitInTwips($this->_height);
        }

        $imageHeight = $this->_imageHeight ? $this->_imageHeight : 100;
        if ($this->_width > 0) {
            $imageWidth = $this->_imageWidth ? $this->_imageWidth : 100;
            $height = ($imageHeight /$imageWidth) * $this->_width;
            return PHPRtfLite_Unit::getUnitInTwips($height);
        }

        return PHPRtfLite_Unit::getPointsInTwips($imageHeight);
    }


    /**
     * adds rtf image code to show that the file is missing
     */
    private static function getMissingImage()
    {
        $string = 'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAIAAAAC64paAAAAAXNSR0IArs4c6QAAAAlwSFlzAAALEwAA'
                . 'CxMBAJqcGAAAAAd0SU1FB9gMGgoaGog16G8AAAAZdEVYdENvbW1lbnQAQ3JlYXRlZCB3aXRoIEdJTVBX'
                . 'gQ4XAAAAfUlEQVQ4y62TsQ3AIAwEnRcSc2T/eVIzR7p0yAn+N4K4xD49cHBctl7FzM57hWzVsBHs4Fat'
                . '1TzNz2BsC5Im95OHfF/0F/RKZnxIBtseeUYG8IcXZAyPo+wh/ORZWGH+oK1of0h9Ch4zPhmPlBQ8tCTd'
                . 'KjMYm9nyXPQ31PUAECcxtbZxAkYAAAAASUVORK5CYII=';

        return $string;
    }


    /**
     * sets image original width
     *
     * @param float $width
     */
    protected function setImageWidth($width)
    {
        $this->_imageWidth = $width;
    }

    /**
     * sets image original height
     *
     * @param float $height
     */
    protected function setImageHeight($height)
    {
        $this->_imageHeight = $height;
    }


    /**
     * creates rtf image from file
     *
     * @param   PHPRtfLite              $rtf
     * @param   string                  $file
     * @param   float                   $width  optional
     * @param   float                   $height optional
     * @return  PHPRtfLite_Image
     */
    public static function createFromFile(PHPRtfLite $rtf, $file, $width = null, $height = null)
    {
        if (file_exists($file) && is_readable($file)) {
            $stream = fopen($file, 'rb');
            $pathInfo = pathinfo($file);
            $type = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : 'jpeg';
            $image = self::create($rtf, $stream, $type, $width, $height);

            if ($type != self::TYPE_WMF) {
                list($width, $height, $imageType) = getimagesize($file);
                $imageType = image_type_to_extension($imageType, false);
                $image->setImageWidth($width);
                $image->setImageHeight($height);
                if ($type != $imageType) {
                    $image->setImageType($imageType);
                }
            }
            return $image;
        }

        return self::createMissingImage($rtf, $width, $height);
    }


    /**
     * creates rtf image from string
     *
     * @param   PHPRtfLite              $rtf
     * @param   string                  $string
     * @param   string                  $type   (represented by class constants)
     * @param   float                   $width  optional
     * @param   float                   $height optional
     * @return  PHPRtfLite_Image
     */
    public static function createFromString(PHPRtfLite $rtf, $string, $type, $width = null, $height = null)
    {
        $stream = fopen('data://text/plain;base64,' . base64_encode($string), 'rb');
        $image = self::create($rtf, $stream, $type, $width, $height);
        if ($type != self::TYPE_WMF) {
            $imageResource = imagecreatefromstring($string);
            $image->setImageWidth(imagesx($imageResource));
            $image->setImageHeight(imagesy($imageResource));
        }
        return $image;
    }


    /**
     * factory method
     * creates rtf image from stream
     *
     * @param   PHPRtfLite $rtf
     * @param   resource   $stream
     * @param   string     $type   (represented by class constants)
     * @param   float      $width  optional
     * @param   float      $height optional
     * @return  PHPRtfLite_Image_Wmf|PHPRtfLite_Image_Gd|PHPRtfLite_Image
     */
    private static function create(PHPRtfLite $rtf, $stream, $type, $width, $height)
    {
        switch ($type) {
            case self::TYPE_WMF:
                return new PHPRtfLite_Image_Wmf($rtf, $stream, $width, $height);
            default:
                $image = new PHPRtfLite_Image_Gd($rtf, $stream, $width, $height);
                $image->setImageType($type);
                return $image;
        }
    }


    /**
     * creates a missing image instance
     *
     * @param   PHPRtfLite  $rtf
     * @param   float       $width
     * @param   float       $height
     * @return  PHPRtfLite_Image_Gd
     */
    private static function createMissingImage($rtf, $width, $height)
    {
        $stream = fopen('data://text/plain;base64,' . self::getMissingImage(), 'rb');
        $image = new PHPRtfLite_Image_Gd($rtf, $stream, $width, $height);
        $image->setImageType(self::TYPE_PNG);
        $image->setIsMissing();
        return $image;
    }


    /**
     * renders rtf image for rtf document
     */
    public function render()
    {
        $this->writeIntoRtfStream();
    }


    /**
     * writes image into rtf stream
     *
     * @param integer $startFrom
     */
    protected function writeIntoRtfStream($startFrom = 0)
    {
        fseek($this->_stream, $startFrom);
        $rtfImageType = $this->getImageTypeAsRtf();

        $rtfStream = $this->_rtf->getWriter();
        $rtfStream->write('{\*\shppict {\pict');

        if ($this->_border) {
            $rtfStream->write($this->_border->getContent());
        }

        $rtfStream->write($rtfImageType . '\picscalex100\picscaley100');
        $rtfStream->write('\picwgoal' . $this->getImageRtfWidth());
        $rtfStream->write('\pichgoal' . $this->getImageRtfHeight());
        $rtfStream->write(' ');

        while (!feof($this->_stream)) {
            $stringBuffer = fread($this->_stream, 1024);
            $stringHex = bin2hex($stringBuffer);
            $rtfStream->write($stringHex);
        }

        fclose($this->_stream);

        $rtfStream->write('}}');
    }


    /**
     * gets image rtf type
     *
     * @return string
     */
    protected function getImageTypeAsRtf()
    {
        return $this->_imageRtfType;
    }

}