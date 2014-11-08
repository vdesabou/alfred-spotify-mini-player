<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../PHPRtfLiteSampleTestCase.php';

/**
 * Created on 08.04.2010
 *
 * @author sz
 */
class ChessTournamentSampleTest extends PHPRtfLiteSampleTestCase
{

    private $_name = 'chess_tournament';

    public function test()
    {
        $this->processTest($this->_name . '.php');
    }

    protected function getSampleFile()
    {
        return $this->getSampleDir() . '/generated/' . $this->_name . '.rtf';
    }

}