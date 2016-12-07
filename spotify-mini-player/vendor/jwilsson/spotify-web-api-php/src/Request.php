<?php
namespace SpotifyWebAPI;

class Request
{
    protected $returnAssoc = false;

    const ACCOUNT_URL = 'https://accounts.spotify.com';
    const API_URL = 'https://api.spotify.com';

    /**
     * Parse the response body and handle API errors.
     *
     * @param string $body The raw, unparsed response body.
     * @param int $status The HTTP status code, used to see if additional error handling is needed.
     *
     * @throws SpotifyWebAPIException
     *
     * @return array|object The parsed response body. Type is controlled by Request::setReturnAssoc().
     */
    protected function parseBody($body, $status)
    {
        if ($status >= 200 && $status <= 299) {
            return json_decode($body, $this->returnAssoc);
        }

        $body = json_decode($body);
        $error = (isset($body->error)) ? $body->error : null;

        if (isset($error->message) && isset($error->status)) {
            // API call error
            throw new SpotifyWebAPIException($error->message, $error->status);
        } elseif (isset($body->error_description)) {
            // Auth call error
            throw new SpotifyWebAPIException($body->error_description, $status);
        } else {
            // Something went really wrong
            throw new SpotifyWebAPIException('An unknown error occurred.', $status);
        }
    }

    /**
     * Parse HTTP response headers.
     *
     * @param string $headers The raw, unparsed response headers.
     *
     * @return array Headers as key–value pairs.
     */
    protected function parseHeaders($headers)
    {
        $headers = str_replace("\r\n", "\n", $headers);
        $headers = explode("\n", $headers);

        array_shift($headers);

        $parsedHeaders = [];
        foreach ($headers as $header) {
            list($key, $value) = explode(':', $header, 2);

            $parsedHeaders[$key] = trim($value);
        }

        return $parsedHeaders;
    }

    /**
     * Make a request to the "account" endpoint.
     *
     * @param string $method The HTTP method to use.
     * @param string $uri The URI to request.
     * @param array $parameters Optional. Query parameters.
     * @param array $headers Optional. HTTP headers.
     *
     * @return array Response data.
     * - array|object body The response body. Type is controlled by Request::setReturnAssoc().
     * - string headers Response headers.
     * - int status HTTP status code.
     * - string url The requested URL.
     */
    public function account($method, $uri, $parameters = [], $headers = [])
    {
        return $this->send($method, self::ACCOUNT_URL . $uri, $parameters, $headers);
    }

    /**
     * Make a request to the "api" endpoint.
     *
     * @param string $method The HTTP method to use.
     * @param string $uri The URI to request.
     * @param array $parameters Optional. Query parameters.
     * @param array $headers Optional. HTTP headers.
     *
     * @return array Response data.
     * - array|object body The response body. Type is controlled by Request::setReturnAssoc().
     * - string headers Response headers.
     * - int status HTTP status code.
     * - string url The requested URL.
     */
    public function api($method, $uri, $parameters = [], $headers = [])
    {
        return $this->send($method, self::API_URL . $uri, $parameters, $headers);
    }

    /**
     * Get a value indicating the response body type.
     *
     * @return bool Whether the body is returned as an associative array or an stdClass.
     */
    public function getReturnAssoc()
    {
        return $this->returnAssoc;
    }

    /**
     * Make a request to Spotify.
     * You'll probably want to use one of the convenience methods instead.
     *
     * @param string $method The HTTP method to use.
     * @param string $url The URL to request.
     * @param array $parameters Optional. Query parameters.
     * @param array $headers Optional. HTTP headers.
     *
     * @return array Response data.
     * - array|object body The response body. Type is controlled by Request::setReturnAssoc().
     * - array headers Response headers.
     * - int status HTTP status code.
     * - string url The requested URL.
     */
    public function send($method, $url, $parameters = [], $headers = [])
    {
        // Sometimes a JSON object is passed
        if (is_array($parameters) || is_object($parameters)) {
            $parameters = http_build_query($parameters);
        }

        $mergedHeaders = [];
        foreach ($headers as $key => $val) {
            $mergedHeaders[] = "$key: $val";
        }

        $options = [
            CURLOPT_CAINFO => __DIR__ . '/cacert.pem',
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $mergedHeaders,
            CURLOPT_RETURNTRANSFER => true,
        ];

        $url = rtrim($url, '/');
        $method = strtoupper($method);

        switch ($method) {
            case 'DELETE': // No break
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = $method;
                $options[CURLOPT_POSTFIELDS] = $parameters;

                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $parameters;

                break;
            default:
                $options[CURLOPT_CUSTOMREQUEST] = $method;

                if ($parameters) {
                    $url .= '/?' . $parameters;
                }

                break;
        }

        $options[CURLOPT_URL] = $url;
        $options[CURLOPT_ENCODING] = "gzip";

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        if (curl_error($ch)) {
            throw new SpotifyWebAPIException('cURL transport error: ' . curl_errno($ch) . ' ' .  curl_error($ch));
        }

        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headers = $this->parseHeaders($headers);
        $body = $this->parseBody($body, $status);

        curl_close($ch);

        return [
            'body' => $body,
            'headers' => $headers,
            'status' => $status,
            'url' => $url,
        ];
    }

    /**
     * Set the return type for the response body.
     *
     * @param bool $returnAssoc Whether to return an associative array or an stdClass.
     *
     * @return void
     */
    public function setReturnAssoc($returnAssoc)
    {
        $this->returnAssoc = $returnAssoc;
    }
}
