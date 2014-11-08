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
class PHPRtfLite_Element
{

    /**
     * @var string
     */
    protected $_text                = '';
    /**
     * @var boolean
     */
    protected $_isRtfCode       = false;
    /**
     * @var boolean
     */
    protected $_convertTagsToRtf    = false;
    /**
     * @var PHPRtfLite_Font
     */
    protected $_font;
    /**
     * @var PHPRtfLite_ParFormat
     */
    protected $_parFormat;


    /**
     * constructor
     *
     * @param PHPRtfLite            $rtf
     * @param string                $text
     * @param PHPRtfLite_Font       $font
     * @param PHPRtfLite_ParFormat  $parFormat
     */
    public function __construct(PHPRtfLite $rtf,
                                $text,
                                PHPRtfLite_Font $font = null,
                                PHPRtfLite_ParFormat $parFormat = null)
    {
        if ($font) {
            $rtf->registerFont($font);
        }
        if ($parFormat) {
            $rtf->registerParFormat($parFormat);
        }

        $this->_rtf         = $rtf;
        $this->_text        = $text;
        $this->_font        = $font;
        $this->_parFormat   = $parFormat;
    }


    /**
     * checks, if element is an empty paragraph
     *
     * @return boolean
     */
    public function isEmptyParagraph()
    {
        return ($this->_parFormat && $this->_text == '\\par' && $this->_isRtfCode);
    }


    /**
     * sets flag, that text tags shall be converted into rtf code
     */
    public function setConvertTagsToRtf()
    {
        $this->_convertTagsToRtf = true;
    }


    /**
     * sets rtf code
     */
    public function setIsRtfCode()
    {
        $this->_isRtfCode = true;
    }


    /**
     * returns true, if text is rtf code
     *
     * @return boolean
     */
    public function isRtfCode()
    {
        return $this->_isRtfCode;
    }


    /**
     * gets font
     *
     * @return PHPRtfLite_Font
     */
    public function getFont()
    {
        return $this->_font;
    }


    /**
     * gets par format
     *
     * @return PHPRtfLite_ParFormat
     */
    public function getParFormat()
    {
        return $this->_parFormat;
    }


    /**
     * converts text tags into rtf code
     *
     * @param  string $text
     * @param  string $charset
     * @return string
     */
    public static function convertTagsToRtf($text, $charset)
    {
        $search = array(
            // bold
            '|<STRONG\s*>(.*?)</STRONG\s*>|smi',
            '|<B\s*>(.*?)</B\s*>|smi',
            // italic
            '|<EM\s*>(.*?)</EM\s*>|smi',
            '|<I\s*>(.*?)</I\s*>|smi',
            // underline
            '|<U\s*>(.*?)</U\s*>|smi',
            // break
            '|<BR\s*(/)?\s*>|smi',
            '|<LINE\s*(/)?\s*>|smi',
            // horizontal rule
            '|<HR\s*(/)?\s*>|smi',
            '|<CHDATE\s*(/)?\s*>|smi',
            '|<CHDPL\s*(/)?\s*>|smi',
            '|<CHDPA\s*(/)?\s*>|smi',
            '|<CHTIME\s*(/)?\s*>|smi',
            '|<CHPGN\s*(/)?\s*>|smi',
            // tab
            '|<TAB\s*(/)?\s*>|smi',
            // bullet
            '|<BULLET\s*(/)?\s*>|smi',
            '|<PAGENUM\s*(/)?\s*>|smi',
            '|<PAGETOTAL\s*(/)?\s*>|smi',
            '|<SECTNUM\s*(/)?\s*>|smi',
            '|<SUP\s*>(.*?)</SUP\s*>|smi',
            '|<SUB\s*>(.*?)</SUB\s*>|smi',
//            '|<PAGE\s*(/)?\s*>|smi',
//            '|<SECT\s*(/)?\s*>|smi'
        );

        $replace = array(
            // bold
            '\b \1\b0 ',
            '\b \1\b0 ',
            // italic
            '\i \1\i0 ',
            '\i \1\i0 ',
            // underline
            '\ul \1\ul0 ',
            // break
            '\line ',
            '\line ',
            // horizontal rule
            '{\pard \brdrb \brdrs \brdrw10 \brsp20 \par}',
            '\chdate ',
            '\chdpl ',
            '\chdpa ',
            '\chtime ',
            '\chpgn ',
            // tab
            '\tab ',
            // bullet
            '\bullet ',
            '\chpgn ',
            '{\field {\*\fldinst {NUMPAGES }}}',
            '\sectnum ',
            '{\super \1\super0}',
            '{\sub \1\sub0}',
//            '\page ',
//            '\sect '
        );

        $text = preg_replace($search, $replace, $text);
        $text = html_entity_decode($text, ENT_COMPAT, $charset);

        return $text;
    }


    /**
     * gets opening token
     *
     * @return string
     */
    protected function getOpeningToken()
    {
        return '{';
    }


    /**
     * gets closing token
     *
     * @return string
     */
    protected function getClosingToken()
    {
        return '}';
    }


    /**
     * renders the element
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();
        $text = $this->_text;

        if (!$this->_isRtfCode) {
            $charset = $this->_rtf->getCharset();
            $text = PHPRtfLite::quoteRtfCode($text);
            if ($this->_convertTagsToRtf) {
                $text = self::convertTagsToRtf($text, $charset);
            }
            $text = PHPRtfLite_Utf8::getUnicodeEntities($text, $charset);
        }

        $stream->write($this->getOpeningToken());

        if ($this->_font) {
            $stream->write($this->_font->getContent());
        }
        if (!$this->isEmptyParagraph() || $this->_isRtfCode) {
            $stream->write($text);
        }

        $stream->write($this->getClosingToken() . "\r\n");
    }

}