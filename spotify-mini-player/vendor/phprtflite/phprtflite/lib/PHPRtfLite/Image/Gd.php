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
 * Class for displaying images supported by gd library in rtf documents.
 * @since       1.2
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Image
 */
class PHPRtfLite_Image_Gd extends PHPRtfLite_Image
{

    /**
     * sets image type
     *
     * @param string $imageType
     */
    protected function setImageType($imageType)
    {
        switch ($imageType) {
            case self::TYPE_JPEG:
                $this->_imageRtfType = '\jpegblip';
                break;
            default:
                $this->_imageRtfType = '\\' . $imageType . 'blip';
                break;
        }
    }

}