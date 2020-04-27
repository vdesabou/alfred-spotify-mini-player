<?php
namespace SpotifyWebAPI;

// Extends from SpotifyWebApiException for backwards compatibility
class SpotifyWebAPIAuthException extends SpotifyWebAPIException
{
    const INVALID_CLIENT = 'Invalid client';
    const INVALID_CLIENT_SECRET = 'Invalid client secret';
    const INVALID_REFRESH_TOKEN = 'Invalid refresh token';

    /**
     * Returns whether the exception was thrown because of invalid credentials.
     *
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
     * Returns whether the exception was thrown because of an invalid refresh token.
     *
     * @return bool
     */
    public function hasInvalidRefreshToken()
    {
        return $this->getMessage() === self::INVALID_REFRESH_TOKEN;
    }
}
