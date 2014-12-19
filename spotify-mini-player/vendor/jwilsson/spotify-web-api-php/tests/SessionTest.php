<?php
use \SpotifyWebAPI;

class SessionTest extends PHPUnit_Framework_TestCase
{
    private $session;

    public function setUp()
    {
        $this->session = new SpotifyWebAPI\Session(getenv('SPOTIFY_CLIENT_ID'), getenv('SPOTIFY_CLIENT_SECRET'), getenv('SPOTIFY_REDIRECT_URI'));
    }

    public function testGetAuthorizeUrl()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=&show_dialog=false&state=";
        $url = $this->session->getAuthorizeUrl();

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlScope()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $scope = array('user-read-email');
        $scopeOut = urlencode(implode(' ', $scope));

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=$scopeOut&show_dialog=false&state=";
        $url = $this->session->getAuthorizeUrl(array(
            'scope' => $scope
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlMultipleScope()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $scope = array('user-read-email', 'playlist-modify-public');
        $scopeOut = urlencode(implode(' ', $scope));

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=$scopeOut&show_dialog=false&state=";
        $url = $this->session->getAuthorizeUrl(array(
            'scope' => $scope
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlDefaultShowDialog()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=&show_dialog=false&state=";
        $url = $this->session->getAuthorizeUrl();

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlShowDialog()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=&show_dialog=true&state=";
        $url = $this->session->getAuthorizeUrl(array(
            'show_dialog' => true
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlState()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $state = 'foobar';

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=&show_dialog=false&state=$state";
        $url = $this->session->getAuthorizeUrl(array(
            'state' => $state
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlScopeAndState()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $scope = array('user-read-email');
        $scopeOut = urlencode(implode(' ', $scope));
        $state = 'foobar';

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=$scopeOut&show_dialog=false&state=$state";
        $url = $this->session->getAuthorizeUrl(array(
            'scope' => $scope,
            'state' => $state
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlOptions()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $scope = array('user-read-email');
        $scopeOut = urlencode(implode(' ', $scope));
        $state = 'foobar';

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=$scopeOut&show_dialog=true&state=$state";
        $url = $this->session->getAuthorizeUrl(array(
            'scope' => $scope,
            'show_dialog' => true,
            'state' => $state
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetAuthorizeUrlMultipleScopeAndState()
    {
        $clientID = getenv('SPOTIFY_CLIENT_ID');
        $redirectUri = urlencode(getenv('SPOTIFY_REDIRECT_URI'));
        $scope = array('user-read-email', 'playlist-modify-public');
        $scopeOut = urlencode(implode(' ', $scope));
        $state = 'foobar';

        $expected = "https://accounts.spotify.com/authorize/?client_id=$clientID&redirect_uri=$redirectUri&response_type=code&scope=$scopeOut&show_dialog=false&state=$state";
        $url = $this->session->getAuthorizeUrl(array(
            'scope' => $scope,
            'state' => $state
        ));

        $this->assertEquals($expected, $url);
    }

    public function testGetClientId()
    {
        $expected = getenv('SPOTIFY_CLIENT_ID');
        $this->session->setClientId(getenv('SPOTIFY_CLIENT_ID'));

        $clientID = $this->session->getClientId();

        $this->assertEquals($expected, $clientID);
    }

    public function testGetClientSecret()
    {
        $expected = getenv('SPOTIFY_CLIENT_SECRET');
        $this->session->setClientSecret(getenv('SPOTIFY_CLIENT_SECRET'));

        $clientSecret = $this->session->getClientSecret();

        $this->assertEquals($expected, $clientSecret);
    }

    public function testGetExpires()
    {
        $this->session = new SpotifyWebAPI\Session(getenv('SPOTIFY_CLIENT_ID'), getenv('SPOTIFY_CLIENT_SECRET'), getenv('SPOTIFY_REDIRECT_URI'));
        $this->session->requestCredentialsToken();

        $this->assertGreaterThan(0, $this->session->getExpires());
    }

    public function testGetRedirectUri()
    {
        $expected = getenv('SPOTIFY_REDIRECT_URI');
        $this->session->setRedirectUri(getenv('SPOTIFY_REDIRECT_URI'));

        $redirectUri = $this->session->getRedirectUri();

        $this->assertEquals($expected, $redirectUri);
    }

    public function testGetRefreshToken()
    {
        $expected = getenv('SPOTIFY_REFRESH_TOKEN');
        $this->session->setRefreshToken(getenv('SPOTIFY_REFRESH_TOKEN'));

        $refreshToken = $this->session->getRefreshToken();

        $this->assertEquals($expected, $refreshToken);
    }

    public function testRequestCredentialsToken()
    {
        $this->session = new SpotifyWebAPI\Session(getenv('SPOTIFY_CLIENT_ID'), getenv('SPOTIFY_CLIENT_SECRET'), getenv('SPOTIFY_REDIRECT_URI'));
        $this->session->requestCredentialsToken();

        $this->assertNotEmpty($this->session->getAccessToken());
    }

    public function testRequestCredentialsTokenScope()
    {
        $this->session = new SpotifyWebAPI\Session(getenv('SPOTIFY_CLIENT_ID'), getenv('SPOTIFY_CLIENT_SECRET'), getenv('SPOTIFY_REDIRECT_URI'));
        $this->session->requestCredentialsToken(array('user-read-email'));

        $this->assertNotEmpty($this->session->getAccessToken());
    }

    public function testSetClientId()
    {
        $expected = getenv('SPOTIFY_CLIENT_ID');
        $this->session->setClientId(getenv('SPOTIFY_CLIENT_ID'));

        $clientID = $this->session->getClientId();

        $this->assertEquals($expected, $clientID);
    }

    public function testSetClientSecret()
    {
        $expected = getenv('SPOTIFY_CLIENT_SECRET');
        $this->session->setClientSecret(getenv('SPOTIFY_CLIENT_SECRET'));

        $clientSecret = $this->session->getClientSecret();

        $this->assertEquals($expected, $clientSecret);
    }

    public function testSetRedirectUri()
    {
        $expected = getenv('SPOTIFY_REDIRECT_URI');
        $this->session->setRedirectUri(getenv('SPOTIFY_REDIRECT_URI'));

        $redirectUri = $this->session->getRedirectUri();

        $this->assertEquals($expected, $redirectUri);
    }

    public function testSetRefreshToken()
    {
        $expected = getenv('SPOTIFY_REFRESH_TOKEN');
        $this->session->setRefreshToken(getenv('SPOTIFY_REFRESH_TOKEN'));

        $refreshToken = $this->session->getRefreshToken();

        $this->assertEquals($expected, $refreshToken);
    }
}
