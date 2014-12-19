<?php
use \SpotifyWebAPI;

class RequestTest extends PHPUnit_Framework_TestCase
{
    private $request = null;

    public function setUp()
    {
        $this->request = new SpotifyWebAPI\Request();
    }

    public function testApi()
    {
        $response = $this->request->api('GET', '/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testApiParameters()
    {
        $response = $this->request->api('GET', '/v1/albums', array(
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a'
        ));

        $this->assertObjectHasAttribute('id', $response['body']->albums[0]);
        $this->assertObjectHasAttribute('id', $response['body']->albums[1]);
    }

    public function testSend()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testSendParameters()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums', array(
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a'
        ));

        $this->assertObjectHasAttribute('id', $response['body']->albums[0]);
        $this->assertObjectHasAttribute('id', $response['body']->albums[1]);
    }

    public function testSendHeaders()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertInternalType('string', $response['headers']);
    }

    public function testSendStatus()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertEquals(200, $response['status']);
    }

    public function testSendMalformed()
    {
        $this->setExpectedException('SpotifyWebAPI\SpotifyWebAPIException');

        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/NON_EXISTING_ALBUM');
    }

    public function testSetReturnAssoc()
    {
        $request = new SpotifyWebAPI\Request();
        $this->assertFalse($request->getReturnAssoc());

        $request->setReturnAssoc(true);
        $this->assertTrue($request->getReturnAssoc());

        $request->setReturnAssoc(false);
        $this->assertFalse($request->getReturnAssoc());
    }

    public function testSendReturnAssoc()
    {
        $request = new SpotifyWebAPI\Request();
        $request->setReturnAssoc(true);

        $response = $request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');
        $this->assertArrayHasKey('id', $response['body']);
    }
}
