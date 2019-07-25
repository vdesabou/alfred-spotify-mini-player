<?php

namespace SpotifyWebAPI;

class SpotifyWebAPIAuthException extends SpotifyWebAPIException
{
    // extends from SpotifyWebApiException for backwards compatibility
    const INVALID_CLIENT = 'Invalid client';
    const INVALID_CLIENT_SECRET = 'Invalid client secret';
    const INVALID_REFRESH_TOKEN = 'Invalid refresh token';

    /**
     * Returns if the exception was thrown because of invalid credentials.
     * @return bool
     */
    public function hasInvalidCredentials()
    {
        return in_array($this->getMessage(), [
            self::INVALID_CLIENT,
            self::INVALID_CLIENT_SECRET,
        ]);
    }

    /**
     * Returns if the exception was thrown because of invalid refresh token.
     * @return bool
     */
    public function hasInvalidRefreshToken()
    {
        return $this->getMessage() === self::INVALID_REFRESH_TOKEN;
    }
}
