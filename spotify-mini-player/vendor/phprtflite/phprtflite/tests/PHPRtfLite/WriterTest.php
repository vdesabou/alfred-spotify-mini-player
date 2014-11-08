<?php

class PHPRtfLite_WriterTest extends PHPUnit_Framework_TestCase
{

    public function testWriteOutputAsString()
    {
        $streamOutput = new PHPRtfLite_Writer_String();
        $this->processStream($streamOutput);
    }


    public function testFileStream()
    {
        $filename = sys_get_temp_dir() . '/' . md5(microtime(true)) . '.txt';
        $streamOutput = new PHPRtfLite_StreamOutput();
        $streamOutput->setFilename($filename);
        $this->processStream($streamOutput);
    }


    private function processStream(PHPRtfLite_Writer_Interface $streamOutput)
    {
        $streamOutput->open();
        $contents = array(
            'Hello world!',
            "\n",
            "Teststring: äüö"
        );
        foreach ($contents as $part) {
            $streamOutput->write($part);
        }

        $this->assertEquals(implode('', $contents), $streamOutput->getContent());
    }

}