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
 * class for doucment head definition for footnotes and endnotes
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_DocHead
 */
class PHPRtfLite_DocHead_Note
{

    /**
     * footnote type
     * @var integer
     */
    protected $_footnoteNumberingType   = PHPRtfLite_Footnote::NUMTYPE_ARABIC_NUMBERS;

    /**
     * endnote type
     * @var integer
     */
    protected $_endnoteNumberingType    = PHPRtfLite_Endnote::NUMTYPE_ROMAN_LC;

    /**
     * flag for restarting footnote numbering on each page
     * @var boolean
     */
    protected $_footnoteRestartEachPage = false;

    /**
     * flag for restarting endnote numbering on each page
     * @var boolean
     */
    protected $_endnoteRestartEachPage  = false;

    /**
     * start number for footnotes
     * @var integer
     */
    protected $_footnoteStartNumber     = 1;

    /**
     * start number for endnotes
     * @var integer
     */
    protected $_endnoteStartNumber      = 1;


    /**
     * sets footnote numbering type
     *
     * @param integer $numberingType
     */
    public function setFootnoteNumberingType($numberingType)
    {
        $this->_footnoteNumberingType = $numberingType;
    }


    /**
     * gets footnote numbering type
     *
     * @return integer
     */
    public function getFootnoteNumberingType()
    {
        return $this->_footnoteNumberingType;
    }


    /**
     * sets endnote numbering type
     *
     * @param integer $numberingType
     */
    public function setEndnoteNumberingType($numberingType)
    {
        $this->_endnoteNumberingType = $numberingType;
    }


    /**
     * gets endnote numbering type
     *
     * @return integer
     */
    public function getEndnoteNumberingType()
    {
        return $this->_endnoteNumberingType;
    }


    /**
     * sets footnote start number
     *
     * @param integer $startNumber
     */
    public function setFootnoteStartNumber($startNumber)
    {
        $this->_footnoteStartNumber = $startNumber;
    }


    /**
     * gets footnote start number
     *
     * @return integer
     */
    public function getFootnoteStartNumber()
    {
        return $this->_footnoteStartNumber;
    }


    /**
     * sets endnote start number
     *
     * @param integer $startNumber
     */
    public function setEndnoteStartNumber($startNumber)
    {
        $this->_endnoteStartNumber = $startNumber;
    }


    /**
     * gets endnote start number
     *
     * @return integer
     */
    public function getEndnoteStartNumber()
    {
        return $this->_endnoteStartNumber;
    }


    /**
     * sets restart footnote number on each page
     */
    public function setRestartFootnoteNumberEachPage()
    {
        $this->_footnoteRestartEachPage = true;
    }


    /**
     * checks, if footnote numbering shall be started on each page
     *
     * @return boolean
     */
    public function isRestartFootnoteNumberEachPage()
    {
        return $this->_endnoteRestartEachPage;
    }


    /**
     * sets restart endnote number on each page
     */
    public function setRestartEndnoteNumberEachPage()
    {
        $this->_endnoteRestartEachPage = true;
    }


    /**
     * checks, if endnote numbering shall be started on each page
     *
     * @return boolean
     */
    public function isRestartEndnoteNumberEachPage()
    {
        return $this->_endnoteRestartEachPage;
    }


    /**
     * gets numbering type for notes
     *
     * @param  integer $numbering
     * @param  string  $prefix
     * @return string
     */
    public static function getNumberingTypeAsRtf($numbering, $prefix = '\ftnn')
    {
        switch ($numbering) {
            default:
                // const name NUMTYPE_ARABIC_NUMBERS
                return $prefix . 'ar';
            case PHPRtfLite_Footnote::NUMTYPE_ALPHABETH_LC:
                return $prefix . 'alc';
            case PHPRtfLite_Footnote::NUMTYPE_ALPHABETH_UC:
                return $prefix . 'auc';
            case PHPRtfLite_Footnote::NUMTYPE_ROMAN_LC:
                return $prefix . 'rlc';
            case PHPRtfLite_Footnote::NUMTYPE_ROMAN_UC:
                return $prefix . 'ruc';
            case PHPRtfLite_Footnote::NUMTYPE_CHICAGO;
                return $prefix . 'chi';
            case PHPRtfLite_Footnote::NUMTYPE_KOREAN_1:
                return $prefix . 'chosung';
            case PHPRtfLite_Footnote::NUMTYPE_KOREAN_2:
                return $prefix . 'ganada';
            case PHPRtfLite_Footnote::NUMTYPE_CIRCLE:
                return $prefix . 'cnum';
            case PHPRtfLite_Footnote::NUMTYPE_KANJI_1:
                return $prefix . 'dbnum';
            case PHPRtfLite_Footnote::NUMTYPE_KANJI_2:
                return $prefix . 'dbnumd';
            case PHPRtfLite_Footnote::NUMTYPE_KANJI_3:
                return $prefix . 'dbnumt';
            case PHPRtfLite_Footnote::NUMTYPE_KANJI_4:
                return $prefix . 'dbnumk';
            case PHPRtfLite_Footnote::NUMTYPE_DOUBLE_BYTE:
                return $prefix . 'dbchar';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_1:
                return $prefix . 'gbnum';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_2:
                return $prefix . 'gbnumd';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_3:
                return $prefix . 'gbnuml';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_4:
                return $prefix . 'gbnumk';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_ZODIAC_1:
                return $prefix . 'zodiac';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_ZODIAC_2:
                return $prefix . 'zodiacd';
            case PHPRtfLite_Footnote::NUMTYPE_CHINESE_ZODIAC_3:
                return $prefix . 'zodiacl';
        }
    }


    /**
     * renders document definition head for footnotes/endnotes
     *
     * @return string
     */
    public function getContent()
    {
        $content = self::getNumberingTypeAsRtf($this->_footnoteNumberingType) . ' '
                 . self::getNumberingTypeAsRtf($this->_endnoteNumberingType, '\aftnn') . ' '
                 . '\ftnstart' . $this->_footnoteStartNumber . ' '
                 . '\aftnstart' . $this->_endnoteStartNumber . ' ';

        if ($this->_footnoteRestartEachPage) {
            $content .= '\ftnrstpg ';
        }
        if ($this->_endnoteRestartEachPage) {
            $content .= '\aftnrstpg ';
        }

        return $content;
    }

}