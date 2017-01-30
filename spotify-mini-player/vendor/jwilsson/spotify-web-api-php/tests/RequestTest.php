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
        $response = $this->request->api('GET', '/v1/albums', [
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ]);

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

        $parameters = [
            'grant_type' => 'client_credentials'
        ];

        $headers = [
            'Authorization' => 'Basic ' . $payload,
        ];

        $this->setExpectedException('SpotifyWebAPI\SpotifyWebAPIException');

        $response = $this->request->account('POST', '/api/token', $parameters, $headers);
    }

    public function testGetLastResponse()
    {
        $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $response = $this->request->getLastResponse();

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testSend()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testSendDelete()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $response = $this->request->send('DELETE', 'https://httpbin.org/delete', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPost()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $response = $this->request->send('POST', 'https://httpbin.org/post', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPut()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $response = $this->request->send('PUT', 'https://httpbin.org/put', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendParameters()
    {
        $response = $this->request->send('GET', 'https://api.spotify.com/v1/albums', [
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ]);

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

    public function testSendReturnType()
    {
        $request = new SpotifyWebAPI\Request();
        $request->setReturnType(SpotifyWebAPI\Request::RETURN_ASSOC);

        $response = $request->send('GET', 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk');
        $this->assertArrayHasKey('id', $response['body']);
    }

    public function testSetReturnAssoc()
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $request = new SpotifyWebAPI\Request();
        $this->assertFalse($request->getReturnAssoc());

        $request->setReturnAssoc(true);
        $this->assertTrue($request->getReturnAssoc());
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_ASSOC, $request->getReturnType());

        $request->setReturnAssoc(false);
        $this->assertFalse($request->getReturnAssoc());
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_OBJECT, $request->getReturnType());

        PHPUnit_Framework_Error_Deprecated::$enabled = true;
    }

    public function testSetReturnType()
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $request = new SpotifyWebAPI\Request();
        $this->assertFalse($request->getReturnAssoc());

        $request->setReturnType(SpotifyWebAPI\Request::RETURN_ASSOC);
        $this->assertTrue($request->getReturnAssoc());
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_ASSOC, $request->getReturnType());

        $request->setReturnType(SpotifyWebAPI\Request::RETURN_OBJECT);
        $this->assertFalse($request->getReturnAssoc());
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_OBJECT, $request->getReturnType());

        PHPUnit_Framework_Error_Deprecated::$enabled = true;
    }
}
