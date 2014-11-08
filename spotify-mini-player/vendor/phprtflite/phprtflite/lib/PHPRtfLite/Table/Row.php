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
 * Class for creating rows of table in rtf documents.
 * @version     1.2
 * @author      Steffen Zeidler <sigma_z@sigma-scripts.de>
 * @copyright   2010-2012 Steffen Zeidler
 * @package     PHPRtfLite
 * @subpackage  PHPRtfLite_Table
 */
class PHPRtfLite_Table_Row
{

    /**
     * @var PHPRtfLite_Table
     */
    protected $_table;

    /**
     * row index in table
     * @var integer
     */
    protected $_rowIndex;

    /**
     * instances of PHPRtfLite_Table_Cell
     * @var array
     */
    protected $_cells = array();

    /**
     * row height
     * @var float
     */
    protected $_height;


    /**
     * constructor
     *
     * @param PHPRtfLite_Table  $table
     * @param integer           $rowIndex
     * @param float             $height
     */
    public function __construct(PHPRtfLite_Table $table, $rowIndex, $height = null)
    {
        $this->_table       = $table;
        $this->_rowIndex    = $rowIndex;
        $this->_height      = $height;
    }


    /**
     * sets row height
     *
     * @param float $height
     */
    public function setHeight($height)
    {
        $this->_height = $height;
    }


    /**
     * gets row height
     *
     * @return float
     */
    public function getHeight()
    {
        return $this->_height;
    }


    /**
     * Gets cell instance for the given column index
     *
     * @param   integer                 $columnIndex
     * @return  PHPRtfLite_Table_Cell
     */
    public function getCellByIndex($columnIndex)
    {
        if (!isset($this->_cells[$columnIndex - 1])) {
            $this->_cells[$columnIndex - 1] = new PHPRtfLite_Table_Cell($this->_table, $this->_rowIndex, $columnIndex);
        }

        return $this->_cells[$columnIndex - 1];
    }


    /**
     * sets default font for all cells in the row
     *
     * @param PHPRtfLite_Font $font
     */
    public function setFont(PHPRtfLite_Font $font)
    {
        $columns = $this->_table->getColumns();
        foreach ($columns as $i => $column) {
            $cell = $this->_table->getCell($this->_rowIndex, $column->getColumnIndex());
            $cell->setFont($font);
        }
    }


    /**
     * gets row index of row
     *
     * @return integer
     */
    public function getRowIndex()
    {
        return $this->_rowIndex;
    }

}