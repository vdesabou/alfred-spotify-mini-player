<?php
/**
 * PHPRtfLiteSampleTest
 *
 * Created on 08.04.2010
 *
 * @author sz
 */
abstract class PHPRtfLiteSampleTestCase extends PHPUnit_Framework_TestCase
{

    abstract protected function getSampleFile();


    protected function tearDown()
    {
        $this->copyTempFileToSample();
    }


    protected function getSampleDir()
    {
        return dirname(__FILE__) . '/../samples';
    }


    protected function copyTempFileToSample()
    {
        $sampleFile     = $this->getSampleFile();
        $sampleTmpFile  = $this->getSampleTempFile();
        if (!file_exists($sampleTmpFile)) {
            $this->fail();
        }
        rename($sampleTmpFile, $sampleFile);
    }


    protected function copySampleFileToTemp()
    {
        $sampleFile     = $this->getSampleFile();
        $sampleTmpFile  = $this->getSampleTempFile();
        if (!file_exists($sampleFile)) {
            $this->fail();
        }
        rename($sampleFile, $sampleTmpFile);
    }


    protected function getSampleTempFile()
    {
        return $this->getSampleFile() . '-tmp';
    }


    protected function processTest($samplePhp)
    {
        $sampleFile = $this->getSampleFile();
        $sampleTmpFile = $sampleFile . '-tmp';
        $this->copySampleFileToTemp();

        require $this->getSampleDir() . '/' . $samplePhp;

        #file_put_contents($sampleFile . '2', $rtf->getContent());
        $this->assertEquals(file_get_contents($sampleTmpFile), $rtf->getContent());
    }

}