<?php
namespace SpotifyWebAPI;

class SpotifyWebAPI
{
    private $accessToken = '';
    private $lastResponse = array();
    private $request = null;

    /**
     * Constructor
     * Set up Request object.
     *
     * @param Request $request Optional. The Request object to use.
     *
     * @return void
     */
    public function __construct($request = null)
    {
        if (is_null($request)) {
            $request = new Request();
        }

        $this->request = $request;
    }

    /**
     * Add authorization headers.
     *
     * @return array Authorization headers.
     */
    protected function authHeaders()
    {
        $headers = array();

        if ($this->accessToken) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

        return $headers;
    }

    /**
     * Convert Spotify object IDs to Spotify URIs.
     *
     * @param array|string $ids ID(s) to convert.
     *
     * @return array|string Spotify URI(s).
     */
    protected function idToUri($ids)
    {
        $ids = (array) $ids;

        for ($i = 0; $i < count($ids); $i++) {
            if (strpos($ids[$i], 'spotify:track:') !== false) {
                continue;
            }

            $ids[$i] = 'spotify:track:' . $ids[$i];
        }

        return (count($ids) == 1) ? $ids[0] : $ids;
    }

    /**
     * Add tracks to the current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/save-tracks-user/
     *
     * @param string|array $tracks ID(s) of the track(s) to add.
     *
     * @return bool Whether the tracks was successfully added.
     */
    public function addMyTracks($tracks)
    {
        $tracks = json_encode((array) $tracks);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/me/tracks';

        $this->lastResponse = $this->request->api('PUT', $uri, $tracks, $headers);

        return $this->lastResponse['status'] == 200;
    }

    /**
     * Add tracks to a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/add-tracks-to-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to add tracks to.
     * @param string|array $tracks ID(s) of the track(s) to add.
     * @param array|object $options Optional. Options for the new tracks.
     * - int position Optional. Zero-based track position in playlist. Tracks will be appened if omitted or false.
     *
     * @return bool Whether the tracks was successfully added.
     */
    public function addUserPlaylistTracks($userId, $playlistId, $tracks, $options = array())
    {
        $options = http_build_query($options);

        $tracks = $this->idToUri($tracks);
        $tracks = json_encode((array) $tracks);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a POST request
        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks?' . $options;

        $this->lastResponse = $this->request->api('POST', $uri, $tracks, $headers);

        return $this->lastResponse['status'] == 201;
    }

    /**
     * Create a new playlist for a user.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/create-playlist/
     *
     * @param string $userId ID of the user to create the playlist for.
     * @param array|object $options Options for the new playlist.
     * - name string Required. Name of the playlist.
     * - public bool Optional. Whether the playlist should be public or not.
     *
     * @return array|object The new playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function createUserPlaylist($userId, $options)
    {
        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists';

        $this->lastResponse = $this->request->api('POST', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Check to see if the current user is following one or more artists or other Spotify users.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-current-user-follows/
     *
     * @param string $type The type to check: either 'artist' or 'user'.
     * @param string|array $ids ID(s) of the user(s) or artist(s) to check for.
     *
     * @return array Whether each user or artist is followed.
     */
    public function currentUserFollows($type, $ids)
    {
        $ids = implode(',', (array) $ids);
        $options = array(
            'ids' => $ids,
            'type' => $type,
        );

        $headers = $this->authHeaders();

        $uri = '/v1/me/following/contains';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Delete tracks from current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/remove-tracks-user/
     *
     * @param string|array $tracks ID(s) of the track(s) to delete.
     *
     * @return bool Whether the tracks was successfully deleted.
     */
    public function deleteMyTracks($tracks)
    {
        $tracks = json_encode(
            (array) $tracks
        );

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/me/tracks';

        $this->lastResponse = $this->request->api('DELETE', $uri, $tracks, $headers);

        return $this->lastResponse['status'] == 200;
    }

    /**
     * Delete tracks from a playlist and retrieve a new snapshot ID.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/remove-tracks-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to delete tracks from.
     * @param array $tracks Array of arrays with tracks to delete.
     * - id string Required. Spotify track ID.
     * - positions int|array Optional. The track's position(s) in the playlist.
     * @param string $snapshotId Optional. The playlist's snapshot ID.
     *
     * @return string|bool A new snapshot ID or false if the tracks weren't successfully deleted.
     */
    public function deleteUserPlaylistTracks($userId, $playlistId, $tracks, $snapshotId = '')
    {
        $options = array();
        if ($snapshotId) {
            $options['snapshot_id'] = $snapshotId;
        }

        $options['tracks'] = array();
        for ($i = 0; $i < count($tracks); $i++) {
            $track = array();

            if (isset($tracks[$i]['positions'])) {
                $track['positions'] = (array) $tracks[$i]['positions'];
            }

            $track['uri'] = $this->idToUri($tracks[$i]['id']);

            $options['tracks'][] = $track;
        }

        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $this->lastResponse = $this->request->api('DELETE', $uri, $options, $headers);
        $body = $this->lastResponse['body'];

        if (isset($body->snapshot_id)) {
            return $body->snapshot_id;
        }

        return false;
    }

    /**
     * Add the current user as a follower of one or more artists or other Spotify users.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/follow-artists-users/
     *
     * @param string $type The type to check: either 'artist' or 'user'.
     * @param string|array $ids ID(s) of the user(s) or artist(s) to follow.
     *
     * @return bool Whether the artist or user was successfully followed.
     */
    public function followArtistsOrUsers($type, $ids)
    {
        $ids = json_encode(array(
            'ids' => (array) $ids,
        ));

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a PUT request
        $uri = '/v1/me/following?type=' . $type;

        $this->lastResponse = $this->request->api('PUT', $uri, $ids, $headers);

        return $this->lastResponse['status'] == 204;
    }

    /**
     * Add the current user as a follower of a playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/follow-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to follow.
     * @param array|object $options Optional. Options for the followed playlist.
     * - public bool Optional. Whether the playlist should be followed publicly or not.
     *
     * @return bool Whether the playlist was successfully followed.
     */
    public function followPlaylist($userId, $playlistId, $options = array())
    {
        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/followers';

        $this->lastResponse = $this->request->api('PUT', $uri, $options, $headers);

        return $this->lastResponse['status'] == 200;
    }

    /**
     * Get a album.
     * https://developer.spotify.com/web-api/get-album/
     *
     * @param string $albumId ID of the album.
     *
     * @return array|object The requested album. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getAlbum($albumId)
    {
        $headers = $this->authHeaders();

        $uri = '/v1/albums/' . $albumId;

        $this->lastResponse = $this->request->api('GET', $uri, array(), $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get multiple albums.
     * https://developer.spotify.com/web-api/get-several-albums/
     *
     * @param array $albumIds IDs of the albums.
     * @param array|object $options Optional. Options for the albums.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The requested albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getAlbums($albumIds, $options = array())
    {
        $options['ids'] = implode(',', $albumIds);

        $headers = $this->authHeaders();

        $uri = '/v1/albums/';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a album's tracks.
     * https://developer.spotify.com/web-api/get-albums-tracks/
     *
     * @param string $albumId ID of the album.
     * @param array|object $options Optional. Options for the tracks.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The requested album tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getAlbumTracks($albumId, $options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/albums/' . $albumId . '/tracks';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get an artist.
     * https://developer.spotify.com/web-api/get-artist/
     *
     * @param string $artistId ID of the artist.
     *
     * @return array|object The requested artist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtist($artistId)
    {
        $headers = $this->authHeaders();

        $uri = '/v1/artists/' . $artistId;

        $this->lastResponse = $this->request->api('GET', $uri, array(), $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get multiple artists.
     * https://developer.spotify.com/web-api/get-several-artists/
     *
     * @param array $artistIds IDs of the artists.
     *
     * @return array|object The requested artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtists($artistIds)
    {
        $artistIds = implode(',', $artistIds);
        $options = array(
            'ids' => $artistIds,
        );

        $headers = $this->authHeaders();

        $uri = '/v1/artists/';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get an artist's related artists.
     * https://developer.spotify.com/web-api/get-related-artists/
     *
     * @param string $artistId ID of the artist.
     *
     * @return array|object The artist's related artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtistRelatedArtists($artistId)
    {
        $headers = $this->authHeaders();

        $uri = '/v1/artists/' . $artistId . '/related-artists';

        $this->lastResponse = $this->request->api('GET', $uri, array(), $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get an artist's albums.
     * https://developer.spotify.com/web-api/get-artists-albums/
     *
     * @param string $artistId ID of the artist.
     * @param array|object $options Optional. Options for the albums.
     * - string|array album_type Optional. Album type(s) to return. If omitted, all album types will be returned.
     * - string market Optional. Limit the results to items that are playable in this market, for example SE.
     * - int limit Optional. Limit the number of albums.
     * - int offset Optional. Number of albums to skip.
     *
     * @return array|object The artist's albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtistAlbums($artistId, $options = array())
    {
        $options = (array) $options;

        if (isset($options['album_type'])) {
            $options['album_type'] = implode(',', (array) $options['album_type']);
        }

        $headers = $this->authHeaders();

        $uri = '/v1/artists/' . $artistId . '/albums';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get an artist's top tracks in a country.
     * https://developer.spotify.com/web-api/get-artists-top-tracks/
     *
     * @param string $artistId ID of the artist.
     * @param array|object $options Options for the tracks.
     * - string $country Required. An ISO 3166-1 alpha-2 country code specifying the country to get the top tracks for.
     *
     * @return array|object The artist's top tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtistTopTracks($artistId, $options)
    {
        $headers = $this->authHeaders();

        $uri = '/v1/artists/' . $artistId . '/top-tracks';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get Spotify featured playlists.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-featured-playlists/
     *
     * @param array|object $options Optional. Options for the playlists.
     * - string locale Optional. Language to show playlists in, for example sv_SE.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show playlists from this country.
     * - string timestamp Optional. A ISO 8601 timestamp. Show playlists relevant to this date and time.
     * - int limit Optional. Limit the number of playlists.
     * - int offset Optional. Number of playlists to skip.
     *
     * @return array|object The featured playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getFeaturedPlaylists($options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/browse/featured-playlists';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a list of categories used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-categories/
     *
     * @param array|object $options Optional. Options for the categories.
     * - string locale Optional. Language to show categories in, for example sv_SE.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show categories from this country.
     * - int limit Optional. Limit the number of categories.
     * - int offset Optional. Number of categories to skip.
     *
     * @return array|object The list of categories. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getCategoriesList($options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/browse/categories';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a single category used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-category/
     *
     * @param string $categoryId The Spotify ID of the category.
     *
     * @param array|object $options Optional. Options for the category.
     * - string locale Optional. Language to show category in, for example sv_SE.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show category from this country.
     *
     * @return array|object The category. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getCategory($categoryId, $options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/browse/categories/' . $categoryId;

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a list of Spotify playlists tagged with a particular category.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-categorys-playlists/
     *
     * @param string $categoryId The Spotify ID of the category.
     *
     * @param array|object $options Optional. Options for the category's playlists.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show category playlists from this country.
     * - int limit Optional. Limit the number of playlists.
     * - int offset Optional. Number of playlists to skip.
     *
     * @return array|object The list of playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getCategoryPlaylists($categoryId, $options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/browse/categories/' . $categoryId . '/playlists';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the latest full response from the Spotify API.
     *
     * @return array Response data.
     * - array|object body The response body. Type is controlled by Request::setReturnAssoc().
     * - string headers Response headers.
     * - int status HTTP status code.
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Get new releases.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-new-releases/
     *
     * @param array|object $options Optional. Options for the items.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show items relevant to this country.
     * - int limit Optional. Limit the number of items.
     * - int offset Optional. Number of items to skip.
     *
     * @return array|object The new releases. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getNewReleases($options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/browse/new-releases';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the current user’s saved tracks.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-users-saved-tracks/
     *
     * @param array|object $options Optional. Options for the tracks.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The user's saved tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getMySavedTracks($options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/me/tracks';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the return type for the Request body element.
     *
     * @return bool Whether an associative array or an stdClass is returned.
     */
    public function getReturnAssoc()
    {
        return $this->request->getReturnAssoc();
    }

    /**
     * Get a track.
     * https://developer.spotify.com/web-api/get-track/
     *
     * @param string $trackId ID of the track.
     * @param array|object $options Optional. Options for the track.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The requested track. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getTrack($trackId, $options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/tracks/' . $trackId;

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get multiple tracks.
     * https://developer.spotify.com/web-api/get-several-tracks/
     *
     * @param array $trackIds IDs of the tracks.
     * @param array|object $options Optional. Options for the albums.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The requested tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getTracks($trackIds, $options = array())
    {
        $options['ids'] = implode(',', $trackIds);

        $headers = $this->authHeaders();

        $uri = '/v1/tracks/';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a user.
     * https://developer.spotify.com/web-api/get-users-profile/
     *
     * @param string $userId ID of the user.
     *
     * @return array|object The requested user. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUser($userId)
    {
        $headers = $this->authHeaders();

        $uri = '/v1/users/' . $userId;

        $this->lastResponse = $this->request->api('GET', $uri, array(), $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the artists followed by the current user.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-followed-artists/
     *
     * @param array|object $options Optional. Options for the artists.
     * - int limit Optional. Limit the number of artists returned.
     * - string after Optional. The last artist ID retrieved from the previous request.
     *
     * @return array|object A list of artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserFollowedArtists($options = array())
    {
        $options = (array) $options;

        if (!isset($options['type'])) {
            $options['type'] = 'artist'; // Undocumented until more values are supported.
        }

        $headers = $this->authHeaders();

        $uri = '/v1/me/following';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a user's specific playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-playlist/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Optional. Options for the playlist.
     * - string|array fields Optional. A list of fields to return. See Spotify docs for more info.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The user's playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserPlaylist($userId, $playlistId, $options = array())
    {
        $options = (array) $options;

        if (isset($options['fields'])) {
            $options['fields'] = implode(',', (array) $options['fields']);
        }

        $headers = $this->authHeaders();

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId;

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get a user's playlists.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-users-playlists/
     *
     * @param string $userId ID of the user.
     * @param array|object $options Optional. Options for the tracks.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     *
     * @return array|object The user's playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserPlaylists($userId, $options = array())
    {
        $headers = $this->authHeaders();

        $uri = '/v1/users/' . $userId . '/playlists';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the tracks in a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-playlists-tracks/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Optional. Options for the tracks.
     * - string|array fields Optional. A list of fields to return. See Spotify docs for more info.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     * - string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
     *
     * @return array|object The tracks in the playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserPlaylistTracks($userId, $playlistId, $options = array())
    {
        $options = (array) $options;

        if (isset($options['fields'])) {
            $options['fields'] = implode(',', (array) $options['fields']);
        }

        $headers = $this->authHeaders();

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Get the currently authenticated user.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-current-users-profile/
     *
     * @return array|object The currently authenticated user. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function me()
    {
        $headers = $this->authHeaders();

        $uri = '/v1/me';

        $this->lastResponse = $this->request->api('GET', $uri, array(), $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Check if tracks is saved in the current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-users-saved-tracks/
     *
     * @param string|array $tracks ID(s) of the track(s) to check for.
     *
     * @return array Whether each track is saved.
     */
    public function myTracksContains($tracks)
    {
        $tracks = implode(',', (array) $tracks);
        $options = array(
            'ids' => $tracks,
        );

        $headers = $this->authHeaders();

        $uri = '/v1/me/tracks/contains';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Reorder the tracks in a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/reorder-playlists-tracks/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Options for the new .
     * - int range_start Required. Position of the first track to be reordered.
     * - int range_length Optional. The amount of tracks to be reordered.
     * - int insert_before Required. Position where the tracks should be inserted.
     * - string snapshot_id Optional. The playlist's snapshot ID.
     *
     * @return string|bool A new snapshot ID or false if the tracks weren't successfully reordered.
     */
    public function reorderUserPlaylistTracks($userId, $playlistId, $options)
    {
        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $this->lastResponse = $this->request->api('PUT', $uri, $options, $headers);
        $body = $this->lastResponse['body'];

        if (isset($body->snapshot_id)) {
            return $body->snapshot_id;
        }

        return false;
    }

    /**
     * Replace all tracks in a user's playlist with new ones.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/replace-playlists-tracks/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param string|array $tracks ID(s) of the track(s) to add.
     *
     * @return bool Whether the tracks was successfully replaced.
     */
    public function replaceUserPlaylistTracks($userId, $playlistId, $tracks)
    {
        $tracks = $this->idToUri($tracks);
        $tracks = json_encode(array(
            'uris' => (array) $tracks,
        ));

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $this->lastResponse = $this->request->api('PUT', $uri, $tracks, $headers);

        return $this->lastResponse['status'] == 201;
    }

    /**
     * Search for an item.
     * Requires a valid access token if market=from_token is used.
     * https://developer.spotify.com/web-api/search-item/
     *
     * @param string $query The term to search for.
     * @param string|array $type The type of item to search for.
     * @param array|object $options Optional. Options for the search.
     * - string market Optional. Limit the results to items that are playable in this market, for example SE.
     * - int limit Optional. Limit the number of items.
     * - int offset Optional. Number of items to skip.
     *
     * @return array|object The search results. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function search($query, $type, $options = array())
    {
        $type = implode(',', (array) $type);
        $options = array_merge((array) $options, array(
            'q' => $query,
            'type' => $type,
        ));

        $headers = $this->authHeaders();

        $uri = '/v1/search';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }

    /**
     * Set the access token to use.
     *
     * @param string $accessToken The access token.
     *
     * @return void
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Set the return type for the Request body element.
     *
     * @param bool $returnAssoc Whether to return an associative array or an stdClass.
     *
     * @return void
     */
    public function setReturnAssoc($returnAssoc)
    {
        $this->request->setReturnAssoc($returnAssoc);
    }

    /**
     * Remove the current user as a follower of one or more artists or other Spotify users.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/unfollow-artists-users/
     *
     * @param string $type The type to check: either 'artist' or 'user'.
     * @param string|array $ids ID(s) of the user(s) or artist(s) to unfollow.
     *
     * @return bool Whether the artist(s) or user(s) were successfully unfollowed.
     */
    public function unfollowArtistsOrUsers($type, $ids)
    {
        $ids = json_encode(array(
            'ids' => (array) $ids,
        ));

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a DELETE request
        $uri = '/v1/me/following?type=' . $type;

        $this->lastResponse = $this->request->api('DELETE', $uri, $ids, $headers);

        return $this->lastResponse['status'] == 204;
    }

    /**
     * Remove the current user as a follower of a playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/unfollow-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to unfollow
     *
     * @return bool Whether the playlist was successfully unfollowed.
     */
    public function unfollowPlaylist($userId, $playlistId)
    {
        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/followers';

        $this->lastResponse = $this->request->api('DELETE', $uri, array(), $headers);

        return $this->lastResponse['status'] == 200;
    }

    /**
     * Update the details of a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/change-playlist-details/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to update.
     * @param array|object $options Options for the playlist.
     * - name string Optional. Name of the playlist.
     * - public bool Optional. Whether the playlist should be public or not.
     *
     * @return bool Whether the playlist was successfully updated.
     */
    public function updateUserPlaylist($userId, $playlistId, $options)
    {
        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId;

        $this->lastResponse = $this->request->api('PUT', $uri, $options, $headers);

        return $this->lastResponse['status'] == 200;
    }

    /**
     * Check if a user is following a playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-user-following-playlist/
     *
     * @param string $ownerId User ID of the playlist owner.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Options for the check.
     * - ids string|array Required. ID(s) of the user(s) to check for.
     *
     * @return array Whether each user is following the playlist.
     */
    public function userFollowsPlaylist($ownerId, $playlistId, $options)
    {
        $options = (array) $options;

        if (isset($options['ids'])) {
            $options['ids'] = implode(',', (array) $options['ids']);
        }

        $headers = $this->authHeaders();

        $uri = '/v1/users/' . $ownerId . '/playlists/' . $playlistId . '/followers/contains';

        $this->lastResponse = $this->request->api('GET', $uri, $options, $headers);

        return $this->lastResponse['body'];
    }
}
