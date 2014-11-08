<?php

/**
 * Test class for PHPRtfLite.
 */
class PHPRtfLite_TableTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PHPRtfLite_Table
     */
    private $_table;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $rtf = new PHPRtfLite;
        $this->_table = $rtf->addSection()->addTable();
    }

    /**
     * tests addRows
     */
    public function testAddRows()
    {
        $this->_table->addRows(4);
        $this->assertEquals(4, $this->_table->getRowsCount());
        return $this->_table;
    }

    /**
     * tests addRowList
     * @depends testAddRows
     */
    public function testAddRowList(PHPRtfLite_Table $table)
    {
        $rowHeights = array(2, 5);
        $table->addRowList($rowHeights);
        $this->assertEquals(6, $table->getRowsCount());

        return $table;
    }

    /**
     * tests addRow
     * @depends testAddRowList
     */
    public function testAddRow(PHPRtfLite_Table $table)
    {
        $rowHeight = 2.5;
        $table->addRow($rowHeight);
        $this->assertEquals(7, $table->getRowsCount());
    }

    /**
     * tests if height is set correctly when using addRow
     */
    public function testCorrectHeightWhenAddRow()
    {
        $this->_table->addRow(5.1);
        $row = $this->_table->getRow(1);
        $this->assertEquals(5.1, $row->getHeight());
    }

    /**
     * tests getRow
     * @expectedException PHPRtfLite_Exception
     */
    public function testGetRow()
    {
        $this->_table->getRow(1);
    }

    public function testAddColumnList()
    {
        $columnWidths = array(3, 4);
        $this->_table->addColumnsList($columnWidths);
        $this->assertEquals(2,$this->_table->getColumnsCount());

        return $this->_table;
    }

    /**
     * test addColumn
     * @depends testAddColumnList
     */
    public function testAddColumn(PHPRtfLite_Table $table)
    {
        $table->addColumn(7);
        $this->assertEquals(3, $table->getColumnsCount());

        return $table;
    }

    /**
     * tests getColumn
     * @depends testAddColumn
     */
    public function testGetColumn(PHPRtfLite_Table $table)
    {
        $this->assertType('PHPRtfLite_Table_Column', $table->getColumn(3));

        return $table;
    }

    /**
     * tests getColumn
     * @depends testGetColumn
     * @expectedException PHPRtfLite_Exception
     */
    public function testGetColumnWithInvalidIndex(PHPRtfLite_Table $table)
    {
        $this->assertType('PHPRtfLite_Table_Column', $table->getColumn(4));
    }

    /**
     * tests getCell
     */
    public function testGetCell()
    {
        $this->_table->addRow(1);
        $this->_table->addColumnsList(array(2, 2));
        $this->assertType('PHPRtfLite_Table_Cell', $this->_table->getCell(1, 2));
    }

    /**
     * tests getCell
     * @expectedException PHPRtfLite_Exception
     */
    public function testGetCellWithInvalidIndex()
    {
        $this->_table->addRow(1);
        $this->_table->addColumnsList(array(2, 2));
        $this->_table->getCell(1, 3);
    }

    /**
     * tests setVerticalAlignmentForCellRange
     */
    public function testSetVerticalAlignmentForCellRange()
    {
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setVerticalAlignmentForCellRange(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_BOTTOM, 2, 2, 3, 3);
        for ($rowIndex = 2; $rowIndex < 4; $rowIndex++) {
            for ($columnIndex = 2; $columnIndex < 4; $columnIndex++) {
                $cell = $this->_table->getCell($rowIndex, $columnIndex);
                $this->assertEquals(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_BOTTOM, $cell->getVerticalAlignment());
            }
        }
    }

    /**
     * tests setVerticalAlignmentForCellRange
     */
    public function testSetVerticalAlignmentForCell()
    {
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setVerticalAlignmentForCellRange(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER, 2, 2);
        $cell = $this->_table->getCell(2, 2);
        $this->assertEquals(PHPRtfLite_Table_Cell::VERTICAL_ALIGN_CENTER, $cell->getVerticalAlignment());
    }

    /**
     * tests setTextAlignmentForCellRange
     */
    public function testSetTextAlignmentForCellRange()
    {
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setTextAlignmentForCellRange(PHPRtfLite_Table_Cell::TEXT_ALIGN_RIGHT, 2, 2, 3, 3);
        for ($rowIndex = 2; $rowIndex < 4; $rowIndex++) {
            for ($columnIndex = 2; $columnIndex < 4; $columnIndex++) {
                $cell = $this->_table->getCell($rowIndex, $columnIndex);
                $this->assertEquals(PHPRtfLite_Table_Cell::TEXT_ALIGN_RIGHT, $cell->getTextAlignment());
            }
        }
    }

    /**
     * tests setTextAlignmentForCellRange
     */
    public function testSetTextAlignmentForCell()
    {
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setTextAlignmentForCellRange(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER, 2, 2);
        $cell = $this->_table->getCell(2, 2);
        $this->assertEquals(PHPRtfLite_Table_Cell::TEXT_ALIGN_CENTER, $cell->getTextAlignment());
    }

    /**
     * tests setFontForCellRange
     */
    public function testSetFontForCellRange()
    {
        $font = new PHPRtfLite_Font(2, 'Arial', '#F33', '#ff0');
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setFontForCellRange($font, 2, 2, 3, 3);
        for ($rowIndex = 2; $rowIndex < 4; $rowIndex++) {
            for ($columnIndex = 2; $columnIndex < 4; $columnIndex++) {
                $cell = $this->_table->getCell($rowIndex, $columnIndex);
                $this->assertEquals($font, $cell->getFont());
            }
        }
    }

    /**
     * tests setFontForCellRange
     */
    public function testSetFontForCell()
    {
        $font = new PHPRtfLite_Font(2, 'Arial', '#F33', '#ff0');
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setFontForCellRange($font, 2, 2);
        $cell = $this->_table->getCell(2, 2);
        $this->assertEquals($font, $cell->getFont());
    }

    /**
     * tests rotateCellRange
     */
    public function testRotateCellRange()
    {
        $rotateTo = PHPRtfLite_Table_Cell::ROTATE_RIGHT;
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->rotateCellRange($rotateTo, 2, 2, 3, 3);
        for ($rowIndex = 2; $rowIndex < 4; $rowIndex++) {
            for ($columnIndex = 2; $columnIndex < 4; $columnIndex++) {
                $cell = $this->_table->getCell($rowIndex, $columnIndex);
                $this->assertEquals($rotateTo, $cell->getRotateTo());
            }
        }
    }

    /**
     * tests setBackgroundForCellRange
     */
    public function testSetBackgroundForCellRange()
    {
        $backgroundColor = '#00F';
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5));
        $this->_table->setBackgroundForCellRange($backgroundColor, 1, 1, 2, 2);
        for ($rowIndex = 1; $rowIndex <= 2; $rowIndex++) {
            for ($columnIndex = 1; $columnIndex <= 2; $columnIndex++) {
                $cell = $this->_table->getCell($rowIndex, $columnIndex);
                $this->assertEquals($backgroundColor, $cell->getBackgroundColor());
            }
        }
    }

    /**
     * tests checkIfCellExists
     */
    public function testCheckIfCellExistsStartIndex()
    {
        $this->_table->addRows(3);
        $this->_table->addColumnsList(array(5, 5, 5, 4));
        $this->assertTrue($this->_table->checkIfCellExists(1, 1));

        return $this->_table;
    }

    /**
     * tests checkIfCellExists
     * @depends testCheckIfCellExistsStartIndex
     */
    public function testCheckIfCellExistsEndIndex(PHPRtfLite_Table $table)
    {
        $this->assertTrue($table->checkIfCellExists(3, 4));
    }

    /**
     * tests checkIfCellExists
     * @depends testCheckIfCellExistsStartIndex
     */
    public function testCheckIfCellExistsOutOfIndex(PHPRtfLite_Table $table)
    {
        $this->assertFalse($table->checkIfCellExists(4, 4));
    }

    /**
     * @todo Implement testSetBorderForCellRange().
     */
    public function testSetBorderForCellRange()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testMergeCellRange().
     */
    public function testMergeCellRange()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

}