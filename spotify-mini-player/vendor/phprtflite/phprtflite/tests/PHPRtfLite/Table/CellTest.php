<?php

/**
 * Test class for PHPRtfLite_Table_Cell
 */
class PHPRtfLite_Table_CellTest extends PHPUnit_Framework_TestCase
{

    /**
     * tests setBorder on cell 1x1
     *
     * @param  PHPRtfLite_Table $table
     * @return PHPRtfLite_Table
     */
    public function testSetBorderForCellWithRow1Column1()
    {
        $rtf = new PHPRtfLite;
        $section = $rtf->addSection();
        $table = $section->addTable();
        // creating cells (2 rows x 2 columns)
        $table->addRowList(array(2, 2));
        $table->addColumnsList(array(7, 7));

        $border = new PHPRtfLite_Border($table->getRtf());
        $border->setBorders(new PHPRtfLite_Border_Format(1, '#000'));
        $cell1x1 = $table->getCell(1, 1);
        $cell1x2 = $table->getCell(1, 2);
        $cell2x1 = $table->getCell(2, 1);
        $cell1x1->setBorder($border);
        $this->assertEquals('#000', $cell1x1->getBorder()->getBorderRight()->getColor());
        $this->assertEquals('#000', $cell1x2->getBorder()->getBorderLeft()->getColor());
        $this->assertEquals('#000', $cell2x1->getBorder()->getBorderTop()->getColor());

        return $table;
    }

    /**
     * tests setBorder on cell 1x2
     * @depends testSetBorderForCellWithRow1Column1
     *
     * @param  PHPRtfLite_Table $table
     * @return PHPRtfLite_Table
     */
    public function testSetBorderForCellWithRow1Column2(PHPRtfLite_Table $table)
    {
        $border = new PHPRtfLite_Border($table->getRtf());
        $border->setBorders(new PHPRtfLite_Border_Format(1, '#3F3'));
        $cell1x2 = $table->getCell(1, 2);
        $cell2x2 = $table->getCell(2, 2);
        $cell1x2->setBorder($border);
        $this->assertEquals('#3F3', $cell1x2->getBorder()->getBorderLeft()->getColor());
        $this->assertEquals('#3F3', $cell2x2->getBorder()->getBorderTop()->getColor());

        return $table;
    }

    /**
     * tests setBorder on cell 2x1
     * @depends testSetBorderForCellWithRow1Column2
     *
     * @param  PHPRtfLite_Table $table
     * @return PHPRtfLite_Table
     */
    public function testSetBorderForCellWithRow2Column1(PHPRtfLite_Table $table)
    {
        $border = new PHPRtfLite_Border($table->getRtf());
        $border->setBorders(new PHPRtfLite_Border_Format(1, '#F38'));
        $cell2x1 = $table->getCell(2, 1);
        $cell1x1 = $table->getCell(1, 1);
        $cell2x2 = $table->getCell(2, 2);
        $cell2x1->setBorder($border);
        $this->assertEquals('#F38', $cell1x1->getBorder()->getBorderBottom()->getColor());
        $this->assertEquals('#F38', $cell2x2->getBorder()->getBorderLeft()->getColor());

        return $table;
    }

    /**
     * tests setBorder on cell 2x2
     * @depends testSetBorderForCellWithRow2Column1
     *
     * @param  PHPRtfLite_Table $table
     */
    public function testSetBorderForCellWithRow2Column2(PHPRtfLite_Table $table)
    {
        $border = new PHPRtfLite_Border($table->getRtf());
        $border->setBorders(new PHPRtfLite_Border_Format(1, '#888'));
        $cell2x2 = $table->getCell(2, 2);
        $cell1x2 = $table->getCell(1, 2);
        $cell2x1 = $table->getCell(2, 1);
        $cell2x2->setBorder($border);
        $this->assertEquals('#3F3', $cell1x2->getBorder()->getBorderTop()->getColor());
        $this->assertEquals('#888', $cell1x2->getBorder()->getBorderBottom()->getColor());
        $this->assertEquals('#888', $cell2x1->getBorder()->getBorderRight()->getColor());
    }

}