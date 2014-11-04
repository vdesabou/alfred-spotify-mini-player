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
 * Class for creating columns of table in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Table
 */
class PHPRtfLite_Table_Column
{

    /**
     * column width
     * @var float
     */
    protected $_width;

    /**
     * table
     * @var PHPRtfLite_Table
     */
    protected $_table;

    /**
     * column index
     * @var integer
     */
    protected $_columnIndex;


    /**
     * constructor
     *
     * @param   PHPRtfLite_Table    $table
     * @param   integer             $columnIndex
     * @param   float               $width
     */
    public function __construct(PHPRtfLite_Table $table, $columnIndex, $width)
    {
        $this->_table = $table;
        $this->_width = $width;
        $this->_columnIndex = $columnIndex;
    }


    /**
     * sets column width
     *
     * @param float $width
     */
    public function setWidth($width)
    {
        $this->_width = $width;
    }


    /**
     * gets column width
     *
     * @return float
     */
    public function getWidth()
    {
        return $this->_width;
    }


    /**
     * sets default font for all cells in the row
     *
     * @param PHPRtfLite_Font $font
     */
    public function setFont(PHPRtfLite_Font $font)
    {
        $rows = $this->_table->getRows();
        foreach ($rows as $row) {
            $cell = $this->_table->getCell($row->getRowIndex(), $this->_columnIndex);
            $cell->setFont($font);
        }
    }


    /**
     * gets column index
     *
     * @return integer
     */
    public function getColumnIndex()
    {
        return $this->_columnIndex;
    }

}