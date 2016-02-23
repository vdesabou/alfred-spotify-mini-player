<?php
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
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ));

        $this->assertObjectHasAttribute('id', $response['body']->albums[0]);
        $this->assertObjectHasAttribute('id', $response['body']->albums[1]);
    }

    public function testApiMalformed()
    {
        $this->setExpectedException('SpotifyWebAPI\SpotifyWebAPIException');

        $response = $this->request->api('GET', '/v1/albums/NON_EXISTING_ALBUM');
    }

    public function testAccountMalformed()
    {
        $clientID = 'INVALID_ID';
        $clientSecret = 'INVALID_SECRET';
        $payload = base64_encode($clientID . ':' . $clientSecret);

        $parameters = array(
            'grant_type' => 'client_credentials'
        );

        $headers = array(
            'Authorization' => 'Basic ' . $payload,
        );

        $this->setExpectedException('SpotifyWebAPI\SpotifyWebAPIException');

        $response = $this->request->account('POST', '/api/token', $parameters, $headers);
    }

    public function testSend()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testSendDelete()
    {
        $parameters = array(
            'foo' => 'bar',
        );

        $response = $this->request->send('DELETE', 'https://httpbin.org/delete', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPost()
    {
        $parameters = array(
            'foo' => 'bar',
        );

        $response = $this->request->send('POST', 'https://httpbin.org/post', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPut()
    {
        $parameters = array(
            'foo' => 'bar',
        );

        $response = $this->request->send('PUT', 'https://httpbin.org/put', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendParameters()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums', array(
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ));

        $this->assertObjectHasAttribute('id', $response['body']->albums[0]);
        $this->assertObjectHasAttribute('id', $response['body']->albums[1]);
    }

    public function testSendHeaders()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertInternalType('array', $response['headers']);
    }

    public function testSendHeadersParsingKey()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertArrayHasKey('Content-Type', $response['headers']);
    }

    public function testSendHeadersParsingValue()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertEquals('application/json; charset=utf-8', $response['headers']['Content-Type']);
    }

    public function testSendStatus()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertEquals(200, $response['status']);
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
