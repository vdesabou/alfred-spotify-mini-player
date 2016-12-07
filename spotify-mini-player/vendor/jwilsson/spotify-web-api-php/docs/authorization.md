---
layout: default
title: Authorization
---

Some API methods require authorization. Before using these methods you'll need to create an app at [Spotify's developer site](https://developer.spotify.com).
Authorization can also be used to increase the rate limit of your API calls, the [Client Credentials Flow](#client-credentials-flow) is best suited for this scenario.

There are a few different ways to authenticate a user, `spotify-web-api-php` supports two of these.

*Note: All examples assume the use of Composer or a autoloader.*

### Authorization Code Flow
This method requires some interaction from the user but in turn allows access to user information.

#### Step 1

There are two steps required to authenticate the user. The first step is to request permissions (known as **scopes**) from the user and redirecting to the authorize URL.


    require 'vendor/autoload.php';

    $session = new SpotifyWebAPI\Session('CLIENT_ID', 'CLIENT_SECRET', 'REDIRECT_URI');

    $scopes = array(
        'playlist-read-private',
        'user-read-private'
    );

    $authorizeUrl = $session->getAuthorizeUrl(array(
        'scope' => $scopes
    ));

    header('Location: ' . $authorizeUrl);
    die();


To read more about **scopes**, please refer to the [Spotify documentation](https://developer.spotify.com/web-api/using-scopes/).
To see the other available options for `getAuthorizeUrl()`, refer to the [method reference]({{ site.baseurl }}/method-reference/session.html#getauthorizeurl).

#### Step 2
When the user has approved your app, Spotify will redirect the user together with a `code` to the specifed redirect URI.
You'll need to use this code to request a access token from Spotify and tell the API wrapper about the access token to use, like this:

    require 'vendor/autoload.php';

    $session = new SpotifyWebAPI\Session('CLIENT_ID', 'CLIENT_SECRET', 'REDIRECT_URI');
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    // Request a access token using the code from Spotify
    $session->requestAccessToken($_GET['code']);
    $accessToken = $session->getAccessToken();

    // Set the access token on the API wrapper
    $api->setAccessToken($accessToken);

    // Start using the API!

When requesting a access token, a **refresh token** will also be included. This can be used to extend the validity of access tokens.
To refresh a access token, the `refreshAccessToken()` method can be used:


    $session->refreshAccessToken($refreshToken);


You can also retrieve the refresh token and store it for later use:

    $refreshToken = $session->getRefreshToken();

    // Store it somewhere...

Later, when you want to refresh a session with it, fetch it and request a new access token:

    // Fetch an old refresh token from somewhere...

    $session->refreshAccessToken($refreshToken);

    $accessToken = $session->getAccessToken();

    // Set the new access token on the API wrapper
    $api->setAccessToken($accessToken);

    // Continue as usual...

### Client Credentials Flow
This method doesn't require any user interaction and no access to user information are therefore granted.

    require 'vendor/autoload.php';

    $session = new SpotifyWebAPI\Session('CLIENT_ID', 'CLIENT_SECRET', 'REDIRECT_URI');
    $api = new SpotifyWebAPI\SpotifyWebAPI();

    // Request a access token with optional scopes
    $scopes = array(
        'playlist-read-private',
        'user-read-private'
    );

    $session->requestCredentialsToken($scopes);
    $accessToken = $session->getAccessToken(); // We're good to go!

    // Set the code on the API wrapper
    $api->setAccessToken($accessToken);

    // Start using the API!

As you can see, this flow is very similar to the Authorization Code Flow, however no refresh token is available.

One big advantage with this flow is that it requires no user interaction and still increases the rate limit on your API calls.
