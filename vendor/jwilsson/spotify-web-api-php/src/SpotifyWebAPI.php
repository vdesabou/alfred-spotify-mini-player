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
     * Convert Spotify object IDs to Spotify URIs.
     *
     * @param array|string $ids ID(s) to convert.
     *
     * @return array|string
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
     * Add track(s) to the current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/save-tracks-user/
     *
     * @param string|array ID of the track(s) to add.
     *
     * @return bool
     */
    public function addMyTracks($tracks)
    {
        $tracks = json_encode((array) $tracks);

        $response = $this->request->api('PUT', '/v1/me/tracks', $tracks, array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ));

        return $response['status'] == 200;
    }

    /**
     * Add track(s) to a user's playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/add-tracks-to-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to add tracks to.
     * @param string|array $tracks ID of the track(s) to add.
     * @param array|object $options Optional. Options for the new tracks.
     * - int position Optional. Zero-based position of where in the playlist to add the tracks. Tracks will be appened if omitted or false.
     *
     * @return bool
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

        // We need to manually append data to the URI since it's a POST request
        $response = $this->request->api('POST', '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks?' . $options, $tracks, array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ));

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
     * @return object
     */
    public function createUserPlaylist($userId, $data)
    {
        $defaults = array(
            'name' =>  '',
            'public' => true
        );

        $data = array_merge($defaults, (array) $data);
        $data = json_encode($data);

        $response = $this->request->api('POST', '/v1/users/' . $userId . '/playlists', $data, array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ));

        return $response['body'];
    }

    /**
     * Delete track(s) from current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/remove-tracks-user/
     *
     * @param string|array ID of the track(s) to delete.
     *
     * @return bool
     */
    public function deleteMyTracks($tracks)
    {
        $tracks = implode(',', (array) $tracks);
        $tracks = urlencode($tracks);

        $response = $this->request->api('DELETE', '/v1/me/tracks?ids=' . $tracks, array(), array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['status'] == 200;
    }

    /**
     * Delete tracks from a playlist and retrieve a new snapshot ID.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/remove-tracks-playlist/
     *
     * @param string $userId ID of the user who owns the playlist.
     * @param string $playlistId ID of the playlist to delete tracks from.
     * @param array $tracks Tracks to delete and optional position in the playlist where the track is located.
     * - id string Required. Spotify track ID.
     * - position array Optional. Position of the track in the playlist.
     * @param string $snapshotId Optional. The playlist's snapshot ID.
     *
     * @return string|bool
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

        $response = $this->request->api('DELETE', '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks', $data, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));
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
     * @return object
     */
    public function getAlbum($albumId)
    {
        $response = $this->request->api('GET', '/v1/albums/' . $albumId);

        return $response['body'];
    }

    /**
     * Get multiple albums.
     *
     * @param array $albumIds ID of the albums.
     *
     * @return object
     */
    public function getAlbums($albumIds)
    {
        $albumIds = implode(',', $albumIds);
        $response = $this->request->api('GET', '/v1/albums/', array('ids' => $albumIds));

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
     * @return object
     */
    public function getAlbumTracks($albumId, $options = array())
    {
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $response = $this->request->api('GET', '/v1/albums/' . $albumId . '/tracks', $options);

        return $response['body'];
    }

    /**
     * Get a artist.
     * https://developer.spotify.com/web-api/get-artist/
     *
     * @param string $artistId ID of the artist.
     *
     * @return object
     */
    public function getArtist($artistId)
    {
        $response = $this->request->api('GET', '/v1/artists/' . $artistId);

        return $response['body'];
    }

    /**
     * Get multiple artists.
     * https://developer.spotify.com/web-api/get-several-artists/
     *
     * @param array $artistIds ID of the artists.
     *
     * @return object
     */
    public function getArtists($artistIds)
    {
        $artistIds = implode(',', $artistIds);
        $response = $this->request->api('GET', '/v1/artists/', array('ids' => $artistIds));

        return $response['body'];
    }

    /**
     * Get an artist's related artists.
     * https://developer.spotify.com/web-api/get-related-artists/
     *
     * @param string $artistId ID of the artist.
     *
     * @return object
     */
    public function getArtistRelatedArtists($artistId)
    {
        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/related-artists');

        return $response['body'];
    }

    /**
     * Get a artist's albums.
     * https://developer.spotify.com/web-api/get-artists-albums/
     *
     * @param string $artistId ID of the artist.
     * @param array|object $options Optional. Options for the albums.
     * - array album_type Optional. Album types to return. If omitted, all album types will be returned.
     * - string market Optional. A ISO 3166-1 alpha-2 country code. Limit the results to tracks that are playable in this market.
     * - int limit Optional. Limit the number of albums.
     * - int offset Optional. Number of albums to skip.
     *
     * @return object
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

        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/albums', $options);

        return $response['body'];
    }

    /**
     * Get a artist's top tracks in a country.
     * https://developer.spotify.com/web-api/get-artists-top-tracks/
     *
     * @param string $artistId ID of the artist.
     * @param string $country An ISO 3166-1 alpha-2 country code specifying the country to get the top tracks for.
     *
     * @return object
     */
    public function getArtistTopTracks($artistId, $country)
    {
        $response = $this->request->api('GET', '/v1/artists/' . $artistId . '/top-tracks', array('country' =>  $country));

        return $response['body'];
    }

    /**
     * Get Spotify featured playlists.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-list-featured-playlists/
     *
     * @param array|object $options Optional. Options for the playlists.
     * - string locale Optional. An lowercase ISO 639 language code and an uppercase ISO 3166-1 alpha-2 country code. Show playlists in this language.
     * - string country Optional. An ISO 3166-1 alpha-2 country code. Show playlists from this country.
     * - string timestamp Optional. A ISO 8601 timestamp. Show playlists relevant to this date and time.
     * - int limit Optional. Limit the number of playlists.
     * - int offset Optional. Number of playlists to skip.
     *
     * @return object
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

        $response = $this->request->api('GET', '/v1/browse/featured-playlists', $options, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

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
     * @return object
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

        $response = $this->request->api('GET', '/v1/browse/new-releases', $options, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

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
     * @return array
     */
    public function getMySavedTracks($options = array())
    {
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $response = $this->request->api('GET', '/v1/me/tracks', $options, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['body'];
    }

    /**
     * Get a track.
     * https://developer.spotify.com/web-api/get-track/
     *
     * @param string $trackId ID of the track.
     *
     * @return object
     */
    public function getTrack($trackId)
    {
        $response = $this->request->api('GET', '/v1/tracks/' . $trackId);

        return $response['body'];
    }

    /**
     * Get multiple tracks.
     * https://developer.spotify.com/web-api/get-several-tracks/
     *
     * @param array $trackIds ID of the tracks.
     *
     * @return object
     */
    public function getTracks($trackIds)
    {
        $trackIds = implode(',', $trackIds);
        $response = $this->request->api('GET', '/v1/tracks/', array('ids' => $trackIds));

        return $response['body'];
    }

    /**
     * Get a user.
     * https://developer.spotify.com/web-api/get-users-profile/
     *
     * @param string $userId ID of the user.
     *
     * @return object
     */
    public function getUser($userId)
    {
        $response = $this->request->api('GET', '/v1/users/' . $userId);

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
     * @return object
     */
    public function getUserPlaylists($userId, $options = array())
    {
        $defaults = array(
            'limit' => 0,
            'offset' => 0
        );

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);

        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists', $options, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['body'];
    }

    /**
     * Get a user's specific playlist.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-playlist/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     *
     * @return object
     */
    public function getUserPlaylist($userId, $playlistId)
    {
        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists/' . $playlistId, array(), array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

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
     * @return object
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

        $response = $this->request->api('GET', '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks', $options, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['body'];
    }

    /**
     * Get the currently authenticated user.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/get-current-users-profile/
     *
     * @return object
     */
    public function me()
    {
        $response = $this->request->api('GET', '/v1/me', array(), array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['body'];
    }

    /**
     * Check if the track(s) is saved in the current user's Spotify library.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/check-users-saved-tracks/
     *
     * @param string|array $tracks ID of the track(s) to check for.
     *
     * @return array
     */
    public function myTracksContains($tracks)
    {
        $tracks = implode(',', (array) $tracks);

        $response = $this->request->api('GET', '/v1/me/tracks/contains', array('ids' => $tracks), array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['body'];
    }

    /**
     * Replace all tracks in a user's playlist with new ones.
     * Requires a valid access token.
     * https://developer.spotify.com/web-api/replace-playlists-tracks/
     *
     * @param string $userId ID of the user.
     * @param string $playlistId ID of the playlist.
     * @param string|array $tracks ID of the track(s) to add.
     *
     * @return bool
     */
    public function replacePlaylistTracks($userId, $playlistId, $tracks)
    {
        $tracks = $this->idToUri($tracks);
        $tracks = array('uris' => (array) $tracks);
        $tracks = json_encode($tracks);

        $response = $this->request->api('PUT', '/v1/users/' . $userId . '/playlists/' . $playlistId . '/tracks', $tracks, array(
            'Authorization' => 'Bearer ' . $this->accessToken
        ));

        return $response['status'] == 201;
    }

    /**
     * Search for an item.
     * Requires a valid access token if market=from_token is used.
     * https://developer.spotify.com/web-api/search-item/
     *
     * @param string $query The term to search for.
     * @param string|array $type The type of item to search for; "album", "artist", or "track".
     * @param array|object $options Optional. Options for the search.
     * - string market Optional. A ISO 3166-1 alpha-2 country code. Limit the results to items that are playable in this market.
     * - int limit Optional. Limit the number of items.
     * - int offset Optional. Number of items to skip.
     *
     * @return array
     */
    public function search($query, $type, $options = array())
    {
        $defaults = array(
            'market' => '',
            'limit' => 0,
            'offset' => 0
        );

        $type = implode(',', (array) $type);

        $options = array_merge($defaults, (array) $options);
        $options = array_filter($options);
        $options =  array_merge($options, array(
            'query' => $query,
            'type' => $type
        ));

        $headers = array();
        if (isset($options['market']) && $options['market'] == 'from_token') {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }

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
     * @return bool
     */
    public function updateUserPlaylist($userId, $playlistId, $data)
    {
        $defaults = array(
            'name' =>  '',
            'public' => true
        );

        $data = array_merge($defaults, (array) $data);
        $data = json_encode($data);

        $response = $this->request->api('PUT', '/v1/users/' . $userId . '/playlists/' . $playlistId, $data, array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ));

        return $response['status'] == 200;
    }
}
