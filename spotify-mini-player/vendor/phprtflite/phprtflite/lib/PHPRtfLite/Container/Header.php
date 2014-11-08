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
 * Class for creating headers within the rtf document or section.
 * @version     1.2
 * @author      Denis Slaveckij <sinedas@gmail.com>
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2007-2008 Denis Slaveckij, 2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Container
 */
class PHPRtfLite_Container_Header extends PHPRtfLite_Container_Base
{

    /**
     * constants defining header types
     */
    const TYPE_ALL      = 'all';
    const TYPE_LEFT     = 'left';
    const TYPE_RIGHT    = 'right';
    const TYPE_FIRST    = 'first';


    /**
     * @var string
     */
    protected $_type;

    /**
     * @var float
     */
    protected $_offsetHeight;

    /**
     * @var string
     */
    protected $_rtfType = 'header';


    /**
     * Constructor
     *
     * @param PHPRtfLite    $rtf
     * @param string        $type
     */
    public function __construct(PHPRtfLite $rtf, $type = self::TYPE_ALL)
    {
        $this->_rtf = $rtf;
        $this->_type = $type;

        if ($this->_type == self::TYPE_FIRST) {
            $rtf->setSpecialLayoutForFirstPage(true);
        }
    }


    /**
     * Set vertical header position from the top of the page
     *
     * @param   float   $height
     */
    public function setPosition($height)
    {
        $this->_offsetHeight = $height;
    }


    /**
     * Gets type as rtf code
     *
     * @return string rtf code
     * @throws PHPRtfLite_Exception, if type is not allowed,
     *   because of the rtf document specific settings.
     */
    protected function getTypeAsRtfCode()
    {
        $rtfType = $this->getRtfType();

        switch ($this->_type) {
            case self::TYPE_ALL:
                if (!$this->_rtf->isOddEvenDifferent()) {
                    return $rtfType;
                }

                throw new PHPRtfLite_Exception('Header/Footer type ' . $this->_type . ' is not allowed, '
                                             . 'when using odd even different!');

            case self::TYPE_LEFT:
                if ($this->_rtf->isOddEvenDifferent()) {
                    return $rtfType . 'l';
                }

                throw new PHPRtfLite_Exception('Header/Footer type ' . $this->_type . ' is not allowed, '
                                             . 'when using not odd even different!');

            case self::TYPE_RIGHT:
                if ($this->_rtf->isOddEvenDifferent()) {
                    return $rtfType . 'r';
                }

                throw new PHPRtfLite_Exception('Header/Footer type ' . $this->_type . ' is not allowed, '
                                             . 'when using not odd even different!');

            case self::TYPE_FIRST:
                if ($this->_rtf->hasSpecialLayoutForFirstPage()) {
                    return $rtfType . 'f';
                }

                throw new PHPRtfLite_Exception('Header/Footer type ' . $this->_type . ' is not allowed, '
                                             . 'when using not special layout for first page!');

            default:
                throw new PHPRtfLite_Exception('Header/Footer type is not defined! You gave me: ', $this->_type);
        }
    }


    /**
     * gets rtf type
     * @return string
     */
    protected function getRtfType()
    {
        return $this->_rtfType;
    }


    /**
     * streams rtf code for header/footer
     * @return string rtf code
     */
    public function render()
    {
        $stream = $this->_rtf->getWriter();

        if (isset($this->_offsetHeight)) {
            $stream->write('\\' . $this->getRtfType() . 'y' . PHPRtfLite_Unit::getUnitInTwips($this->_offsetHeight));
        }

        $stream->write('{\\' . $this->getTypeAsRtfCode() . ' ');

        parent::render();

        $containerElements = $this->getElements();
        if ($containerElements && $containerElements[count($containerElements)-1] instanceof PHPRtfLite_Element) {
            $stream->write('\par');
        }
        $stream->write('}' . "\r\n");
    }

}