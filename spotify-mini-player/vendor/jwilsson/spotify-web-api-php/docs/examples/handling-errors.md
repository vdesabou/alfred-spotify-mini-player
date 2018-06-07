# Handling Errors

Whenever the API returns a error of some sort, a `SpotifyWebAPIException` extending from the native [PHP Exception](http://php.net/manual/en/language.exceptions.php) will be thrown.

The `message` property will be set to the error message returned by the Spotify API and the `code` property will be set to the HTTP status code returned by the Spotify API.

```php
try {
    $track = $api->getTrack('non-existing-track');
} catch (Exception $e) {
    echo 'Spotify API Error: ' . $e->getCode(); // Will be 404
}
```

When an authentication error occurs, a `SpotifyWebAPIAuthException` will be thrown. This will contain the same properties as above.

## Handling rate limit errors
If your application should hit the Spotify API rate limit, you will get an error back and the number of seconds you need to wait before sending another request.

Here's an example of how to handle this:

```php
try {
    $track = $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb');
} catch (SpotifyWebAPIException $e) {
    if ($e->getCode() == 429) { // 429 is Too Many Requests
        $lastResponse = $api->getRequest()->getLastResponse(); // Note "getRequest()" since $api->getLastResponse() won't be set

        $retryAfter = $lastResponse['headers']['Retry-After']; // Number of seconds to wait before sending another request
    } else {
        // Some other kind of error
    }
}
```

Read more about the exact mechanics of rate limiting in the [Spotify API docs](https://developer.spotify.com/web-api/user-guide/#rate-limiting).
