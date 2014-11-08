<?php
require_once 'PHPUnit/Framework.php';
require_once dirname(__FILE__) . '/../PHPRtfLiteSampleTestCase.php';

/**
 * Created on 09.06.2010
 *
 * @author sz
 */
class ListsSampleTest extends PHPRtfLiteSampleTestCase
{

    private $_name = 'lists';

    public function test()
    {
        $this->processTest($this->_name . '.php');
    }

    protected function getSampleFile()
    {
        return $this->getSampleDir() . '/generated/' . $this->_name . '.rtf';
    }

}