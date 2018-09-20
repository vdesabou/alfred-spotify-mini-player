# Authorization Using the Authorization Code Flow

All API methods require authorization. Before using these methods you'll need to create an app at [Spotify's developer site](https://developer.spotify.com/web-api/).

The Authorization Code Flow method requires some interaction from the user but in turn allows access to user information. There are two steps required to authenticate the user. The first step is to request access to the user's account and data (known as *scopes*) and redirecting them to your app's authorize URL (also known as the callback URL).

### Step 1
Put the following code in its own file, lets call it `auth.php`. Replace `CLIENT_ID` and `CLIENT_SECRET` with the values given to you by Spotify. The `REDIRECT_URI` is the one you entered when creating the Spotify app, make sure it's an exact match.

```php
require 'vendor/autoload.php';

$session = new SpotifyWebAPI\Session(
    'CLIENT_ID',
    'CLIENT_SECRET',
    'REDIRECT_URI'
);

$options = [
    'scope' => [
        'playlist-read-private',
        'user-read-private',
    ],
];

header('Location: ' . $session->getAuthorizeUrl($options));
die();
```

To read more about scopes, see [Working with Scopes](/docs/examples/working-with-scopes.md). To see all of the available options for `getAuthorizeUrl()`, refer to the [method reference](/docs/method-reference/Session.md#getauthorizeurl).

### Step 2
When the user has approved your app, Spotify will redirect the user together with a `code` to the specifed redirect URI. You'll need to use this code to request a access token from Spotify.

__Note:__ The API wrapper does not include any token management. It's up to you to save the access token somewhere (in a database, a PHP session, or wherever appropriate for your application) and request a new access token when the old one has expired.

Lets put this code in a new file called `callback.php`:

```php
require 'vendor/autoload.php';

$session = new SpotifyWebAPI\Session(
    'CLIENT_ID',
    'CLIENT_SECRET',
    'REDIRECT_URI'
);

// Request a access token using the code from Spotify
$session->requestAccessToken($_GET['code']);

$accessToken = $session->getAccessToken();
$refreshToken = $session->getRefreshToken();

// Store the access and refresh tokens somewhere. In a database for example.

// Send the user along and fetch some data!
header('Location: app.php');
die();
```

When requesting a access token, a **refresh token** will also be included. This can be used to extend the validity of access tokens. It's recommended to also store this somewhere persistent, in a database for example. Read more about refresh tokens below.

### Step 3
In a third file, `app.php`, tell the API wrapper which access token to use, and then make some API calls!

```php
require 'vendor/autoload.php';

$api = new SpotifyWebAPI\SpotifyWebAPI();

// Fetch the saved access token from somewhere. A database for example.
$api->setAccessToken($accessToken);

// It's now possible to request data about the currently authenticated user
print_r(
    $api->me()
);

// Getting Spotify catalog data is of course also possible
print_r(
    $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb')
);
```

## Refreshing an access token
When the access token has expired, request a new one using the refresh token:

```php
// Fetch the refresh token from somewhere. A database for example.

$session->refreshAccessToken($refreshToken);

$accessToken = $session->getAccessToken();

// Set our new access token on the API wrapper and continue to use the API as usual
$api->setAccessToken($accessToken);
```

For more in-depth technical information about the Authorization Code Flow, please refer to the [Spotify Web API documentation](https://developer.spotify.com/web-api/authorization-guide/#authorization_code_flow).
