<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../../../lib/PHPRtfLite.php';


/**
 * Test class for PHPRtfLite_Table_Row.
 */
class PHPRtfLite_Table_RowTest extends PHPUnit_Framework_TestCase
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
     * tests getCellByIndex
     * @return void
     */
    public function testGetCellByIndex()
    {
        $this->_table->addRow(5);
        $row = $this->_table->getRow(1);
        $this->assertType('PHPRtfLite_Table_Cell', $row->getCellByIndex(5));
    }

}