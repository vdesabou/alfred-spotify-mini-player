<?php
namespace SpotifyWebAPI;

class Request
{
    const ACCOUNT_URL = 'https://accounts.spotify.com';
    const API_URL = 'https://api.spotify.com';

    /**
     * Make a request to the "account" endpoint.
     *
     * @param string $method The HTTP method to use.
     * @param string $uri The URI to request.
     * @param array $parameters Optional. Query parameters.
     * @param array $headers Optional. HTTP headers.
     *
     * @return array
     */
    public function account($method, $uri, $parameters = array(), $headers = array())
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
     * @return array
     */
    public function api($method, $uri, $parameters = array(), $headers = array())
    {
        return $this->send($method, self::API_URL . $uri, $parameters, $headers);
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
     * @return array
     */
    public function send($method, $url, $parameters = array(), $headers = array())
    {
        // Sometimes a JSON object is passed
        if (is_array($parameters) || is_object($parameters)) {
            $parameters = http_build_query($parameters);
        }

        $mergedHeaders = array();
        foreach ($headers as $key => $val) {
            $mergedHeaders[] = "$key: $val";
        }

        $options = array(
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $mergedHeaders,
            CURLOPT_RETURNTRANSFER => true
        );

        $url = rtrim($url, '/');
        $method = strtoupper($method);

        switch ($method) {
            case 'DELETE':
                $options[CURLOPT_CUSTOMREQUEST] = $method;
                $options[CURLOPT_POSTFIELDS] = $parameters;

                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = $parameters;

                break;
            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
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

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $body = json_decode($body);

        if ($status < 200 || $status > 299) {
            if (isset($body->error)) {
                $error = $body->error;

                // These properties only exist on API calls, not auth calls
                if (isset($error->message) && isset($error->status)) {
                    throw new SpotifyWebAPIException($error->message, $error->status);
                } elseif (isset($body->error_description)) {
                    throw new SpotifyWebAPIException($body->error_description, $status);
                } else {
                    throw new SpotifyWebAPIException($error, $status);
                }
            } else {
                throw new SpotifyWebAPIException('No \'error\' provided in response body', $status);
            }
        }

        return array(
            'body' => $body,
            'headers' => $headers,
            'status' => $status
        );
    }
}
