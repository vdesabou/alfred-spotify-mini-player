<?php
class RequestTest extends PHPUnit\Framework\TestCase
{
    private function setupStub($expectedMethod, $expectedUri, $expectedParameters, $expectedHeaders, $expectedReturn)
    {
        $stub = $this->getMockBuilder('SpotifyWebAPI\Request')
                ->setMethods(['send'])
                ->getMock();

        $stub->expects($this->once())
                 ->method('send')
                 ->with(
                     $this->equalTo($expectedMethod),
                     $this->equalTo($expectedUri),
                     $this->equalTo($expectedParameters),
                     $this->equalTo($expectedHeaders)
                 )
                ->willReturn($expectedReturn);

        return $stub;
    }

    public function testApi()
    {
        $return = [
            'body' => get_fixture('album'),
            'url' => 'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk',
        ];

        $request = $this->setupStub(
            'GET',
            'https://api.spotify.com/v1/albums/7u6zL7kqpgLPISZYXNTgYk',
            [],
            [],
            $return
        );

        $response = $request->api('GET', '/v1/albums/7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response['body']);
    }

    public function testApiParameters()
    {
        $return = [
            'body' => get_fixture('albums'),
            'url' => 'https://api.spotify.com/v1/albums?ids=1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ];

        $request = $this->setupStub(
            'GET',
            'https://api.spotify.com/v1/albums',
            [
                'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
            ],
            [],
            $return
        );

        $response = $request->api('GET', '/v1/albums', [
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ]);

        $this->assertObjectHasAttribute('id', $response['body']->albums[0]);
        $this->assertObjectHasAttribute('id', $response['body']->albums[1]);
    }

    public function testApiMalformed()
    {
        $this->expectException(SpotifyWebAPI\SpotifyWebAPIException::class);

        $request = new SpotifyWebAPI\Request();
        $response = $request->api('GET', '/v1/albums/NON_EXISTING_ALBUM');
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

        $this->expectException(SpotifyWebAPI\SpotifyWebAPIException::class);

        $request = new SpotifyWebAPI\Request();
        $response = $request->account('POST', '/api/token', $parameters, $headers);
    }

    public function testGetLastResponse()
    {
        $request = new SpotifyWebAPI\Request();
        $request->send('GET', 'https://httpbin.org/get');

        $response = $request->getLastResponse();

        $this->assertObjectHasAttribute('url', $response['body']);
    }

    public function testSend()
    {
        $request = new SpotifyWebAPI\Request();
        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertObjectHasAttribute('url', $response['body']);
    }

    public function testSendDelete()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $request = new SpotifyWebAPI\Request();
        $response = $request->send('DELETE', 'https://httpbin.org/delete', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPost()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $request = new SpotifyWebAPI\Request();
        $response = $request->send('POST', 'https://httpbin.org/post', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendPut()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $request = new SpotifyWebAPI\Request();
        $response = $request->send('PUT', 'https://httpbin.org/put', $parameters);

        $this->assertObjectHasAttribute('foo', $response['body']->form);
    }

    public function testSendGetParameters()
    {
        $parameters = [
            'foo' => 'bar',
        ];

        $request = new SpotifyWebAPI\Request();

        /**
         * httpbin doesn't like trailing slashes which we append to
         * GET requests with parameters. So it'll throw which we ignore.
         * Not super pretty, but we really just want to assert the URL creation.
         */
        $this->expectException(SpotifyWebAPI\SpotifyWebAPIException::class);

        $response = $request->send('GET', 'https://httpbin.org/get', $parameters);

        $this->assertEquals('https://httpbin.org/get/?foo=bar', $response['url']);
    }

    public function testSendHeaders()
    {
        $request = new SpotifyWebAPI\Request();
        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertInternalType('array', $response['headers']);
    }

    public function testSendHeadersParsingKey()
    {
        $request = new SpotifyWebAPI\Request();
        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertArrayHasKey('Content-Type', $response['headers']);
    }

    public function testSendHeadersParsingValue()
    {
        $request = new SpotifyWebAPI\Request();
        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertEquals('application/json', $response['headers']['Content-Type']);
    }

    public function testSendStatus()
    {
        $request = new SpotifyWebAPI\Request();
        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertEquals(200, $response['status']);
    }

    public function testSendReturnType()
    {
        $request = new SpotifyWebAPI\Request();
        $request->setReturnType(SpotifyWebAPI\Request::RETURN_ASSOC);

        $response = $request->send('GET', 'https://httpbin.org/get');

        $this->assertArrayHasKey('url', $response['body']);
    }

    public function testSetReturnType()
    {
        $request = new SpotifyWebAPI\Request();

        $request->setReturnType(SpotifyWebAPI\Request::RETURN_ASSOC);
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_ASSOC, $request->getReturnType());

        $request->setReturnType(SpotifyWebAPI\Request::RETURN_OBJECT);
        $this->assertEquals(SpotifyWebAPI\Request::RETURN_OBJECT, $request->getReturnType());
    }
}
