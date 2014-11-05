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
 * class for creating footnotes in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite_Footnote
 */
class PHPRtfLite_Footnote
{

    /**
     * constants for numbering type
     *  0 => Arabic numbering (1, 2, 3, ...)
     *  1 => Alphabetic lowercase (a, b, c, ...)
     *  2 => Alphabetic uppercase (A, B, C, ...)
     *  3 => Roman lowercase (i, ii, iii, ...)
     *  4 => Roman uppercase (I, II, III, ...)
     *  5 => Chicago Manual of Style (*, [dagger], [daggerdbl], §)
     *  6 => Footnote Korean numbering 1 (*chosung).
     *  7 => Footnote Korean numbering 2 (*ganada).
     *  8 => Footnote Circle numbering (*circlenum).
     *  9 => Footnote kanji numbering without the digit character (*dbnum1).
     *  10 => Footnote kanji numbering with the digit character (*dbnum2).
     *  11 => Footnote kanji numbering 3 (*dbnum3).
     *  12 => Footnote kanji numbering 4 (*dbnum4).
     *  13 => Footnote double-byte numbering (*dbchar).
     *  14 => Footnote Chinese numbering 1 (*gb1).
     *  15 => Footnote Chinese numbering 2 (*gb2).
     *  16 => Footnote Chinese numbering 3 (*gb3).
     *  17 => Footnote Chinese numbering 4 (*gb4).
     *  18 => Footnote numbering—Chinese Zodiac numbering 1 (* zodiac1).
     *  19 => Footnote numbering—Chinese Zodiac numbering 2 (* zodiac2).
     *  20 => Footnote numbering—Chinese Zodiac numbering 3 (* zodiac3).
     */
    const NUMTYPE_ARABIC_NUMBERS    = 0;
    const NUMTYPE_ALPHABETH_LC      = 1;
    const NUMTYPE_ALPHABETH_UC      = 2;
    const NUMTYPE_ROMAN_LC          = 3;
    const NUMTYPE_ROMAN_UC          = 4;
    const NUMTYPE_CHICAGO           = 5;
    const NUMTYPE_KOREAN_1          = 6;
    const NUMTYPE_KOREAN_2          = 7;
    const NUMTYPE_CIRCLE            = 8;
    const NUMTYPE_KANJI_1           = 9;
    const NUMTYPE_KANJI_2           = 10;
    const NUMTYPE_KANJI_3           = 11;
    const NUMTYPE_KANJI_4           = 12;
    const NUMTYPE_DOUBLE_BYTE       = 13;
    const NUMTYPE_CHINESE_1         = 14;
    const NUMTYPE_CHINESE_2         = 15;
    const NUMTYPE_CHINESE_3         = 16;
    const NUMTYPE_CHINESE_4         = 17;
    const NUMTYPE_CHINESE_ZODIAC_1  = 18;
    const NUMTYPE_CHINESE_ZODIAC_2  = 19;
    const NUMTYPE_CHINESE_ZODIAC_3  = 20;

    const TYPE_SUPER    = 'super';
    const TYPE_SUB      = 'sub';
    const TYPE_NORMAL   = 'normal';

    /**
     * footnote/endnote text
     * @var string
     */
    protected $_text;

    /**
     * font
     * @var PHPRtfLite_Font
     */
    protected $_font;

    /**
     * paragraph format
     * @var PHPRtfLite_ParFormat
     */
    protected $_parFormat;

    /**
     * rtf document
     * @var PHPRtfLite
     */
    protected $_rtf;

    /**
     * type setting type (superscript/subscript/normal)
     * @var string
     */
    protected $_typeSettingType = self::TYPE_SUPER;

    /**
     * default font
     * @var PHPRtfLite_Font
     */
    protected static $_defaultFont;


    /**
     * constructor
     *
     * @param PHPRtfLite            $rtf
     * @param string                $text
     * @param PHPRtfLite_Font       $font       if font is not set, use defaultFont
     * @param PHPRtfLite_ParFormat  $parFormat
     */
    public function __construct(
        PHPRtfLite $rtf,
        $text,
        PHPRtfLite_Font $font = null,
        PHPRtfLite_ParFormat $parFormat = null
    ) {
        $this->_rtf         = $rtf;
        $this->_text        = $text;

        if (!$font && self::$_defaultFont) {
            $font = self::$_defaultFont;
        }

        if ($font) {
            $rtf->registerFont($font);
        }

        if ($parFormat) {
            $rtf->registerParFormat($parFormat);
        }

        $this->_font        = $font;
        $this->_parFormat   = $parFormat;
    }


    /**
     * sets default font for notes
     *
     * @param PHPRtfLite_Font $font
     */
    public static function setDefaultFont(PHPRtfLite_Font $font)
    {
        self::$_defaultFont = $font;
    }


    /**
     * gets default font
     *
     * @return PHPRtfLite_Font
     */
    public static function getDefaultFont()
    {
        return self::$_defaultFont;
    }


    /**
     * sets font
     *
     * @param PHPRtfLite_Font $font
     */
    public function setFont(PHPRtfLite_Font $font)
    {
        $this->_rtf->registerFont($font);
        $this->_font = $font;
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
     * sets paragraph format
     *
     * @param PHPRtfLite_ParFormat $parFormat
     */
    public function setParFormat(PHPRtfLite_ParFormat $parFormat)
    {
        $this->_rtf->registerParFormat($parFormat);
        $this->_parFormat = $parFormat;
    }


    /**
     * gets paragraph format
     *
     * @return PHPRtfLite_ParFormat
     */
    public function getParFormat()
    {
        return $this->_parFormat;
    }


    /**
     * @param string $type
     */
    public function setTypeSettingType($type)
    {
        $this->_typeSettingType = $type;
    }


    /**
     * gets type as rtf code
     *
     * @return string
     */
    protected function getTypeAsRtfCode()
    {
        return '\footnote';
    }


    /**
     * renders footnote/endnote
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();

        $typeSetting = $this->_typeSettingType != self::TYPE_NORMAL
            ? '\\' . $this->_typeSettingType
            : '';

        $stream->write(
            '{' . $typeSetting . '\chftn}'
            . '{' . $this->getTypeAsRtfCode()
            . '\pard\plain\lin283\fi-283 '
        );

        if ($this->_parFormat) {
            $stream->write($this->_parFormat->getContent());
        }
        if ($this->_font) {
            $stream->write($this->_font->getContent());
        }
        $charset = $this->_rtf->getCharset();
        $textEncoded = PHPRtfLite::quoteRtfCode($this->_text);
        $stream->write(
            '{\up6\chftn}' . "\r\n"
            . PHPRtfLite_Utf8::getUnicodeEntities($textEncoded, $charset)
            . '} '
        );
    }

}