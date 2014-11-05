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
 * abstract class for form fields in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_FormField
 */
abstract class PHPRtfLite_FormField
{

    /**
     * rtf instance
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     * font instance
     * @var PHPRtfLite_Font
     */
    protected $_font;

    /**
     * par format instance
     * @var PHPRtfLite_ParFormat
     */
    protected $_parFormat;

    /**
     * default value
     * @var string
     */
    protected $_defaultValue;


    /**
     * abstract method to get the form field's specific rtf code
     *
     * @return string
     */
    abstract protected function getRtfCode();


    /**
     * abstract method to get the form field's type
     *
     * @return string
     */
    abstract protected function getType();


    /**
     * constructor
     *
     * @param   PHPRtfLite              $rtf
     * @param   PHPRtfLite_Font         $font
     * @param   PHPRtfLite_ParFormat    $parFormat
     */
    public function __construct(PHPRtfLite $rtf, PHPRtfLite_Font $font = null, PHPRtfLite_ParFormat $parFormat = null)
    {
        $this->_rtf         = $rtf;
        $this->_font        = $font;
        $this->_parFormat   = $parFormat;
        if ($font) {
            $this->_rtf->registerFont($font);
        }
    }


    /**
     * sets default value
     *
     * @param   string  $value
     */
    public function setDefaultValue($value)
    {
        $this->_defaultValue = $value;
    }


    /**
     * gets font instance
     *
     * @return PHPRtfLite_Font
     */
    public function getFont()
    {
        return $this->_font;
    }


    /**
     * gets par format instance
     *
     * @return PHPRtfLite_ParFormat
     */
    public function getParFormat()
    {
        return $this->_parFormat;
    }


    /**
     * renders form field
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();

        $stream->write(' ');
        if ($this->_font) {
            $stream->write('{' . $this->_font->getContent());
        }

        $defaultValue = PHPRtfLite_Utf8::getUnicodeEntities($this->_defaultValue, $this->_rtf->getCharset());
        $content = '{\field'
            . '{\*\fldinst ' . $this->getType()
            . '  {\*\formfield' . $this->getRtfCode() . '}'
            . '}{\fldrslt ' . $defaultValue . '}}';

        $stream->write($content);

        if ($this->_font) {
            $stream->write($this->_font->getClosingContent() . '}');
        }
        $stream->write(' ');
    }

}
