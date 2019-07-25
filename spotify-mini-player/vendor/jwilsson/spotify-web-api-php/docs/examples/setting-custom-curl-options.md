# Setting custom cURL options

Sometimes, you need to override the default cURL options. For example incresing the timeout or setting some proxy setting.

In order to set custom cURL options, you'll need to instantiate a `Request` object yourself and passing it to `SpotifyWebAPI` instead of letting it set it up itself.

For example:
```php
$request = new SpotifyWebAPI\Request();
$request->setCurlOptions([
    CURLOPT_TIMEOUT => 60,
]);

$api = SpotifyWebAPI\SpotifyWebAPI($request);

// Continue as usual
```

The options you pass using `setCurlOptions` will be merged with the default ones and existing options with the same key will be overwritten by the ones passed by you.

Refer to the [PHP docs](https://www.php.net/manual/en/function.curl-setopt.php) for a complete list of cURL options.
