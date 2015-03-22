<?php
namespace SpotifyWebAPI;

class Session
{
    private $accessToken = '';
    private $clientId = '';
    private $clientSecret = '';
    private $expires = 0;
    private $redirectUri = '';
    private $refreshToken = '';

    private $request = null;

    /**
     * Constructor
     * Set up client credentials.
     *
     * @param string $clientId The client ID.
     * @param string $clientSecret The client secret.
     * @param string $redirectUri Optional. The redirect URI.
     * @param Request $request Optional. The Request object to use.
     *
     * @return void
     */
    public function __construct($clientId, $clientSecret, $redirectUri = '', $request = null)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setRedirectUri($redirectUri);

        if (is_null($request)) {
            $request = new Request();
        }

        $this->request = $request;
    }

    /**
     * Get the authorization URL.
     *
     * @param array|object $options Optional. Options for the authorization URL.
     * - array scope Optional. Scope(s) to request from the user.
     * - boolean show_dialog Optional. Whether or not to force the user to always approve the app. Default is false.
     * - string state Optional. A CSRF token.
     *
     * @return string The authorization URL.
     */
    public function getAuthorizeUrl($options = array())
    {
        $defaults = array(
            'scope' => array(),
            'show_dialog' => false,
            'state' => '',
        );

        $options = array_merge($defaults, (array) $options);
        $parameters = array(
            'client_id' => $this->getClientId(),
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => implode(' ', $options['scope']),
            'show_dialog' => $options['show_dialog'] ? 'true' : 'false',
            'state' => $options['state'],
        );

        return Request::ACCOUNT_URL . '/authorize/?' . http_build_query($parameters);
    }

    /**
     * Get the access token.
     *
     * @return string The access token.
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Get the client ID.
     *
     * @return string The client ID.
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Get the client secret.
     *
     * @return string The client secret.
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Get the number of seconds for which the access token is valid.
     *
     * @return int The time period (in seconds) for which the access token is valid.
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Get the client's redirect URI.
     *
     * @return string The redirect URI.
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Get the refresh token.
     *
     * @return string The refresh token.
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @deprecated Use Session::refreshAccessToken instead. This dummy function will be removed in 1.0.0.
     */
    public function refreshToken()
    {
        trigger_error('Use Session::refreshAccessToken instead', E_USER_DEPRECATED);
        return $this->refreshAccessToken();
    }

    /**
     * Refresh an access token.
     *
     * @return bool Whether the access token was successfully refreshed.
     */
    public function refreshAccessToken()
    {
        $payload = base64_encode($this->getClientId() . ':' . $this->getClientSecret());

        $parameters = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
        );

        $headers = array(
            'Authorization' => 'Basic ' . $payload,
        );

        $response = $this->request->account('POST', '/api/token', $parameters, $headers);
        $response = $response['body'];

        if (isset($response->access_token)) {
            $this->accessToken = $response->access_token;
            $this->expires = $response->expires_in;

            return true;
        }

        return false;
    }

    /**
     * Request an access token using the Client Credentials Flow.
     *
     * @param array $scope Optional. Scope(s) to request from the user.
     *
     * @return bool True when an access token was successfully granted, false otherwise.
     */
    public function requestCredentialsToken($scope = array())
    {
        $payload = base64_encode($this->getClientId() . ':' . $this->getClientSecret());

        $parameters = array(
            'grant_type' => 'client_credentials',
            'scope' => implode(' ', $scope),
        );

        $headers = array(
            'Authorization' => 'Basic ' . $payload,
        );

        $response = $this->request->account('POST', '/api/token', $parameters, $headers);
        $response = $response['body'];

        if (isset($response->access_token)) {
            $this->accessToken = $response->access_token;
            $this->expires = $response->expires_in;

            return true;
        }

        return false;
    }

    /**
     * @deprecated Use Session::requestAccessToken instead. This dummy function will be removed in 1.0.0.
     */
    public function requestToken($code)
    {
        trigger_error('Use Session::requestAccessToken instead', E_USER_DEPRECATED);
        return $this->requestAccessToken($code);
    }

    /**
     * Request an access token given an authorization code.
     *
     * @param string $authorizationCode The authorization code from Spotify.
     *
     * @return bool True when the access token was successfully granted, false otherwise.
     */
    public function requestAccessToken($authorizationCode)
    {
        $parameters = array(
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getRedirectUri(),
        );

        $response = $this->request->account('POST', '/api/token', $parameters);
        $response = $response['body'];

        if (isset($response->refresh_token)
                && isset($response->access_token)) {
            $this->refreshToken = $response->refresh_token;
            $this->accessToken = $response->access_token;
            $this->expires = $response->expires_in;

            return true;
        }

        return false;
    }

    /**
     * Set the client ID.
     *
     * @param string $clientId The client ID.
     *
     * @return void
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * Set the client secret.
     *
     * @param string $clientSecret The client secret.
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * Set the client's redirect URI.
     *
     * @param string $redirectUri The redirect URI.
     *
     * @return void
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Set the refresh token.
     *
     * @param string $refreshToken The refresh token.
     *
     * @return void
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }
}
