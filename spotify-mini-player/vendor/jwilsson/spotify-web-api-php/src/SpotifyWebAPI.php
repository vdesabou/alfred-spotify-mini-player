<?php
namespace SpotifyWebAPI;

class SpotifyWebAPI
{
    private $accessToken = '';
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

        $response = $this->request->api('PUT', '/v1/me/tracks', $tracks, $headers);

        return $response['status'] == 200;
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
     * - int position Optional. Zero-based position of where in the playlist to add the tracks. Tracks will be appened if omitted or false.
     *
     * @return bool Whether the tracks was successfully added.
     */
    public function addUserPlaylistTracks($userId, $playlistId, $tracks, $options = array())
    {
        $defaults = array(
            'position' => false
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options, function ($value) {
            return $value !== false;
        });

        $options = http_build_query($options);
        $tracks = $this->idToUri($tracks);
        $tracks = json_encode((array) $tracks);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a POST request
        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks?' . $options;
        $response = $this->request->api('POST', $uri, $tracks, $headers);

        return $response['status'] == 201;
    }

    /**
     * Create a new playlist for a user.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/create-playlist/
     *
     * @param string $userId ID of the user to create the playlist for.
     * @param array|object $data Data for the new playlist.
     * - name string Required. Name of the playlist.
     * - public bool Optional. Whether the playlist should be public or not.
     *
     * @return array|object The new playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function createUserPlaylist($userId, $data)
    {
        $defaults = array(
            'name' =>  '',
            'public' => true
        );

        $data = array_merge($defaults, (array) $data);
        $data = json_encode($data);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $response = $this->request->api('POST', '/v1/users/' . $userId . '/playlists', $data, $headers);

        return $response['body'];
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
        $tracks = implode(',', (array) $tracks);
        $tracks = urlencode($tracks);
        $headers = $this->authHeaders();

        $response = $this->request->api('DELETE', '/v1/me/tracks?ids=' . $tracks, array(), $headers);

        return $response['status'] == 200;
    }

    /**
     * Delete tracks from a playlist and retrieve a new snapshot ID.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/remove-tracks-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to delete tracks from.
     * @param array $tracks Array of arrays with tracks to delete and optional position in the playlist where the track is located.
     * - id string Required. Spotify track ID.
     * - position array Optional. Position of the track in the playlist.
     * @param string $snapshotId Optional. The playlist's snapshot ID.
     *
     * @return string|bool A new snapshot ID or false if the tracks weren't deleted.
     */
    public function deletePlaylistTracks($userId, $playlistId, $tracks, $snapshotId = '')
    {
        $data = array();
        if ($snapshotId) {
            $data['snapshot_id'] = $snapshotId;
        }

        for ($i = 0; $i < count($tracks); $i++) {
            $tracks[$i] = (array) $tracks[$i];
            $tracks[$i]['uri'] = $this->idToUri($tracks[$i]['id']);
        }

        $data['tracks'] = $tracks;
        $data = json_encode($data);

        $headers = $this->authHeaders();
        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $response = $this->request->api('DELETE', $uri, $data, $headers);
        $response = $response['body'];

        if (isset($response->snapshot_id)) {
            return $response->snapshot_id;
        }

        return false;
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
        $response = $this->request->api('GET', '/v1/albums/' . $albumId, array(), $headers);

        return $response['body'];
    }

    /**
     * Get multiple albums.
     * https://developer.spotify.com/web-api/get-several-albums/
     *
     * @param array $albumIds IDs of the albums.
     *
     * @return array|object The requested albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getAlbums($albumIds)
    {
        $albumIds = implode(',', $albumIds);
        $options = array('ids' => $albumIds);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/albums/', $options, $headers);

        return $response['body'];
    }

    /**
     * Get a album's tracks.
     * https://developer.spotify.com/web-api/get-several-albums/
     *
     * @param string $albumId ID of the album.
     * @param array|object $options Optional. Options for the tracks.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     *
     * @return array|object The requested album tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getAlbumTracks($albumId, $options = array())
    {
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/albums/' . $albumId . '/tracks', $options, $headers);

        return $response['body'];
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
        $response = $this->request->api('GET', '/v1/artists/' . $artistId, array(), $headers);

        return $response['body'];
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
        $options = array('ids' => $artistIds);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/artists/', $options, $headers);

        return $response['body'];
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
        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/related-artists', array(), $headers);

        return $response['body'];
    }

    /**
     * Get an artist's albums.
     * https://developer.spotify.com/web-api/get-artists-albums/
     *
     * @param string $artistId ID of the artist.
     * @param array|object $options Optional. Options for the albums.
     * - array album_type Optional. Album types to return. If omitted, all album types will be returned.
     * - string market Optional. A ISO 3166-1 alpha-2 country code. Limit the results to tracks that are playable in this market.
     * - int limit Optional. Limit the number of albums.
     * - int offset Optional. Number of albums to skip.
     *
     * @return array|object The artist's albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtistAlbums($artistId, $options = array())
    {
        $defaults = array(
            'album_type' => array(),
            'market' => '',
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options['album_type'] = implode(',', $options['album_type']);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/albums', $options, $headers);

        return $response['body'];
    }

    /**
     * Get an artist's top tracks in a country.
     * https://developer.spotify.com/web-api/get-artists-top-tracks/
     *
     * @param string $artistId ID of the artist.
     * @param string $country An ISO 3166-1 alpha-2 country code specifying the country to get the top tracks for.
     *
     * @return array|object The artist's top tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getArtistTopTracks($artistId, $country)
    {
        $options = array('country' =>  $country);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/top-tracks', $options, $headers);

        return $response['body'];
    }

    /**
     * Get Spotify featured playlists.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-featured-playlists/
     *
     * @param array|object $options Optional. Options for the playlists.
     * - string locale Optional. An lowercase ISO 639 language code and an uppercase ISO 3166-1 alpha-2 country code. Separated by an underscore. Show playlists in this language.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show playlists from this country.
     * - string timestamp Optional. A ISO 8601 timestamp. Show playlists relevant to this date and time.
     * - int limit Optional. Limit the number of playlists.
     * - int offset Optional. Number of playlists to skip.
     *
     * @return array|object The featured playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getFeaturedPlaylists($options = array())
    {
        $defaults = array(
            'country' => '',
            'limit' => 0,
            'locale' => '',
            'offset' => 0,
            'timestamp' => ''
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/browse/featured-playlists', $options, $headers);

        return $response['body'];
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
        $defaults = array(
            'country' => '',
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/browse/new-releases', $options, $headers);

        return $response['body'];
    }

    /**
     * Get the current user’s saved tracks.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-users-saved-tracks/
     *
     * @param array|object $options Optional. Options for the tracks.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     *
     * @return array|object The user's saved tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getMySavedTracks($options = array())
    {
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/me/tracks', $options, $headers);

        return $response['body'];
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
     *
     * @return array|object The requested track. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getTrack($trackId)
    {
        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/tracks/' . $trackId, array(), $headers);

        return $response['body'];
    }

    /**
     * Get multiple tracks.
     * https://developer.spotify.com/web-api/get-several-tracks/
     *
     * @param array $trackIds IDs of the tracks.
     *
     * @return array|object The requested tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getTracks($trackIds)
    {
        $trackIds = implode(',', $trackIds);
        $options = array('ids' => $trackIds);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/tracks/', $options, $headers);

        return $response['body'];
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
        $response = $this->request->api('GET', '/v1/users/' . $userId, array(), $headers);

        return $response['body'];
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
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists', $options, $headers);

        return $response['body'];
    }

    /**
     * Get a user's specific playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-playlist/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Optional. Options for the playlist.
     * - array fields Optional. A list of fields to return. See Spotify docs for more info.
     *
     * @return array|object The user's playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserPlaylist($userId, $playlistId, $options = array())
    {
        $defaults = array(
            'fields' => array()
        );

        $options = array_merge($defaults, (array) $options);
        $options['fields'] = implode(',', $options['fields']);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists/' . $playlistId, $options, $headers);

        return $response['body'];
    }

    /**
     * Get the tracks in a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-playlists-tracks/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param array|object $options Optional. Options for the tracks.
     * - array fields Optional. A list of fields to return. See Spotify docs for more info.
     * - int limit Optional. Limit the number of tracks.
     * - int offset Optional. Number of tracks to skip.
     *
     * @return array|object The tracks in the playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function getUserPlaylistTracks($userId, $playlistId, $options = array())
    {
        $defaults = array(
            'fields' => array(),
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options['fields'] = implode(',', $options['fields']);
        $options = array_filter($options);

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks', $options, $headers);

        return $response['body'];
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
        $response = $this->request->api('GET', '/v1/me', array(), $headers);

        return $response['body'];
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
        $options = array('ids' => $tracks);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/me/tracks/contains', $options, $headers);

        return $response['body'];
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
    public function replacePlaylistTracks($userId, $playlistId, $tracks)
    {
        $tracks = $this->idToUri($tracks);
        $tracks = array('uris' => (array) $tracks);
        $tracks = json_encode($tracks);

        $headers = $this->authHeaders();
        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks';

        $response = $this->request->api('PUT', $uri, $tracks, $headers);

        return $response['status'] == 201;
    }

    /**
     * Search for an item.
     * Requires a valid access token if market=from_token is used.
     * https://developer.spotify.com/web-api/search-item/
     *
     * @param string $query The term to search for.
     * @param string|array $type The type of item to search for.
     * @param array|object $options Optional. Options for the search.
     * - string market Optional. A ISO 3166-1 alpha-2 country code. Limit the results to items that are playable in this market.
     * - int limit Optional. Limit the number of items.
     * - int offset Optional. Number of items to skip.
     *
     * @return array|object The search results. Type is controlled by SpotifyWebAPI::setReturnAssoc().
     */
    public function search($query, $type, $options = array())
    {
        $defaults = array(
            'limit' => 0,
            'market' => '',
            'offset' => 0
        );

        $type = implode(',', (array) $type);

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);
        $options =  array_merge($options, array(
            'query' => $query,
            'type' => $type
        ));

        $headers = $this->authHeaders();
        $response = $this->request->api('GET', '/v1/search', $options, $headers);

        return $response['body'];
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
     * Update the details of a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/change-playlist-details/
     *
     * @param array|object $data Data for the new playlist.
     * - name string Required. Name of the playlist.
     * - public bool Optional. Whether the playlist should be public or not.
     *
     * @return bool Whether the playlist was successfully updated.
     */
    public function updateUserPlaylist($userId, $playlistId, $data)
    {
        $defaults = array(
            'name' =>  '',
            'public' => true
        );

        $data = array_merge($defaults, (array) $data);
        $data = json_encode($data);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId;
        $response = $this->request->api('PUT', $uri, $data, $headers);

        return $response['status'] == 200;
    }

    /**
     * Check if a user is following a playlist
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-user-following-playlist/
     *
     * @param string $ownerId User ID of the playlist owner.
     * @param string $playlistId ID of the playlist.
     * @param $options array|object Options for the check.
     * - ids array Required. IDs of the user(s) to check for.
     *
     * @return array Whether each user is following the playlist.
     */
    public function userFollowsPlaylist($ownerId, $playlistId, $options)
    {
        $defaults = array(
            'ids' => array()
        );

        $options = array_merge($defaults, (array) $options);
        $options['ids'] = implode(',', $options['ids']);
        $headers = $this->authHeaders();

        $url = '/v1/users/' . $ownerId . '/playlists/' . $playlistId . '/followers/contains';
        $response = $this->request->api('GET', $url, $options, $headers);

        return $response['body'];
    }

    /**
     * Check to see if the current user is following one or more artists or other Spotify users
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-current-user-follows/
     *
     * @param string The ID type: either 'artist' or 'user'.
     * @param string|array ID(s) of the user(s) or artist(s) to check for.
     *
     * @return array Whether each id (for user or artist) is followed.
     */
    public function currentUserFollows($type, $ids)
    {
        $ids = implode(',', (array) $ids);
        $options = array(
            'ids' => $ids,
            'type' => $type);
        $headers = $this->authHeaders();

        $response = $this->request->api('GET', '/v1/me/following/contains', $options, $headers);

        return $response['body'];
    }

    /**
     * Add the current user as a follower of one or more artists or other Spotify users
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/follow-artists-users/
     *
     * @param string The ID type: either 'artist' or 'user'.
     * @param string|array ID(s) of the user(s) or artist(s) to follow.
     *
     * @return bool Whether it worked or not.
     */
    public function followArtistsOrUsers($type, $ids)
    {
        $ids = array('ids' => (array) $ids);
        $ids = json_encode($ids);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a PUT request
        $uri = '/v1/me/following?type=' . $type;

        $response = $this->request->api('PUT', $uri, $ids, $headers);

        return $response['status'] == 204;
    }

    /**
     * Remove the current user as a follower of one or more artists or other Spotify users
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/unfollow-artists-users/
     *
     * @param string The ID type: either 'artist' or 'user'.
     * @param string|array ID(s) of the user(s) or artist(s) to unfollow.
     *
     * @return bool Whether it worked or not.
     */
    public function unfollowArtistsOrUsers($type, $ids)
    {
        $ids = array('ids' => (array) $ids);
        $ids = json_encode($ids);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        // We need to manually append data to the URI since it's a DELETE request
        $uri = '/v1/me/following?type=' . $type;

        $response = $this->request->api('DELETE', $uri, $ids, $headers);

        return $response['status'] == 204;
    }

    /**
     * Add the current user as a follower of a playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/follow-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to follow.
     * @param array|object $options Optional. Options for the followed playlist.
     * - public bool Optional. Whether the followed playlist should be public or not.
     *
     * @return bool Whether it worked or not.
     */
    public function followPlaylist($userId, $playlistId, $options = array())
    {
        $defaults = array(
            'public' => true
        );

        $options = array_merge($defaults, (array) $options);
        $options = json_encode($options);

        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/followers';

        $response = $this->request->api('PUT', $uri, $options, $headers);

        return $response['status'] == 200;
    }


    /**
     * Remove the current user as a follower of a playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/unfollow-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to unfollow
     *
     * @return bool Whether it worked or not.
     */
    public function unfollowPlaylist($userId, $playlistId)
    {
        $headers = $this->authHeaders();
        $headers['Content-Type'] = 'application/json';

        $uri = '/v1/users/' . $userId . '/playlists/' . $playlistId . '/followers';

        $response = $this->request->api('DELETE', $uri, null, $headers);

        return $response['status'] == 200;
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
}
