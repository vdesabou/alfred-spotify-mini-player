<?php
class SpotifyWebAPITest extends PHPUnit_Framework_TestCase
{
    private $accessToken = 'access_token';

    private function setupStub($expectedMethod, $expectedUri, $expectedParameters, $expectedHeaders, $expectedReturn)
    {
        $stub = $this->getMockBuilder('Request')
                ->setMethods(['api'])
                ->getMock();

        $stub->expects($this->once())
                 ->method('api')
                 ->with(
                     $this->equalTo($expectedMethod),
                     $this->equalTo($expectedUri),
                     $this->equalTo($expectedParameters),
                     $this->equalTo($expectedHeaders)
                 )
                ->willReturn($expectedReturn);

        return $stub;
    }

    public function testAddMyAlbums()
    {
        $albums = [
            '1oR3KrPIp4CbagPa3PhtPp',
            '6lPb7Eoon6QPbscWbMsk6a',
        ];

        $expected = json_encode($albums);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/me/albums',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->addMyAlbums($albums);

        $this->assertTrue($response);
    }

    public function testAddMyTracks()
    {
        $tracks = [
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l',
        ];

        $expected = json_encode($tracks);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/me/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->addMyTracks($tracks);

        $this->assertTrue($response);
    }

    public function testAddUserPlaylistTracks()
    {
        $tracks = [
            'spotify:track:1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l',
        ];

        $options = [
            'position' => 0,
        ];

        $expected = json_encode([
            'spotify:track:1id6H6vcwSB9GGv9NXh5cl',
            'spotify:track:3mqRLlD9j92BBv1ueFhJ1l',
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 201,
        ];

        $stub = $this->setupStub(
            'POST',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/tracks?position=0',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->addUserPlaylistTracks(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $tracks,
            $options
        );

        $this->assertTrue($response);
    }

    public function testCreateUserPlaylist()
    {
        $options = [
            'name' => 'Test playlist',
            'public' => false,
        ];

        $expected = json_encode($options);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'body' => get_fixture('user-playlist'),
        ];

        $stub = $this->setupStub(
            'POST',
            '/v1/users/mcgurk/playlists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->createUserPlaylist(
            'mcgurk',
            $options
        );

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testCurrentUserFollows()
    {
        $options = [
            '74ASZWbe4lXaubB36ztrGX',
            '36QJpDe2go2KgaRleHCDTp',
        ];

        $expected = [
            'ids' => '74ASZWbe4lXaubB36ztrGX,36QJpDe2go2KgaRleHCDTp',
            'type' => 'artist',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-follows'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/following/contains',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->currentUserFollows(
            'artist',
            $options
        );

        $this->assertTrue($response[0]);
    }

    public function testDeleteMyAlbums()
    {
        $albums = [
            '1oR3KrPIp4CbagPa3PhtPp',
            '6lPb7Eoon6QPbscWbMsk6a',
        ];

        $expected = json_encode($albums);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'DELETE',
            '/v1/me/albums',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->deleteMyAlbums($albums);

        $this->assertTrue($response);
    }

    public function testDeleteMyTracks()
    {
        $tracks = [
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l',
        ];

        $expected = json_encode($tracks);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'DELETE',
            '/v1/me/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->deleteMyTracks($tracks);

        $this->assertTrue($response);
    }

    public function testDeleteUserPlaylistTracks()
    {
        $tracks = [
            [
                'id' => '1id6H6vcwSB9GGv9NXh5cl',
                'positions' => 0,
            ],
            [
                'id' => '3mqRLlD9j92BBv1ueFhJ1l',
                'positions' => [1, 2],
            ],
            [
                'id' => '4iV5W9uYEdYUVa79Axb7Rh',
            ],
        ];

        $expected = json_encode([
            'snapshot_id' => 'snapshot_id',
            'tracks' => [
                [
                    'positions' => [0],
                    'uri' => 'spotify:track:1id6H6vcwSB9GGv9NXh5cl',
                ],
                [
                    'positions' => [1, 2],
                    'uri' => 'spotify:track:3mqRLlD9j92BBv1ueFhJ1l',
                ],
                [
                    'uri' => 'spotify:track:4iV5W9uYEdYUVa79Axb7Rh',
                ]
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'body' => get_fixture('snapshot-id'),
        ];

        $stub = $this->setupStub(
            'DELETE',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->deleteUserPlaylistTracks(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $tracks,
            'snapshot_id'
        );

        $this->assertNotFalse($response);
    }

    public function testFollowArtistsOrUsers()
    {
        $options = [
            '74ASZWbe4lXaubB36ztrGX',
            '36QJpDe2go2KgaRleHCDTp'
        ];

        $expected = json_encode([
            'ids' => [
                '74ASZWbe4lXaubB36ztrGX',
                '36QJpDe2go2KgaRleHCDTp',
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 204,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/me/following?type=artist',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->followArtistsOrUsers(
            'artist',
            $options
        );

        $this->assertTrue($response);
    }

    public function testFollowPlaylist()
    {
        $options = [
            'public' => false,
        ];

        $expected = json_encode($options);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/followers',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->followPlaylist(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $options
        );

        $this->assertTrue($response);
    }

    public function testGetAlbum()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('album'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/albums/7u6zL7kqpgLPISZYXNTgYk',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getAlbum('7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetAlbums()
    {
        $albums = [
            '1oR3KrPIp4CbagPa3PhtPp',
            '6lPb7Eoon6QPbscWbMsk6a',
        ];

        $options = [
            'market' => 'SE'
        ];

        $expected = [
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('albums'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/albums/',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getAlbums($albums, $options);

        $this->assertNotEmpty($response->albums);
    }

    public function testGetAlbumTracks()
    {
        $options = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $expected = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('album-tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/albums/1oR3KrPIp4CbagPa3PhtPp/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getAlbumTracks('1oR3KrPIp4CbagPa3PhtPp', $options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetArtist()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('artist'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/artists/36QJpDe2go2KgaRleHCDTp',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getArtist('36QJpDe2go2KgaRleHCDTp');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetArtistRelatedArtists()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('artist-related-artists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/artists/36QJpDe2go2KgaRleHCDTp/related-artists',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getArtistRelatedArtists('36QJpDe2go2KgaRleHCDTp');

        $this->assertNotEmpty($response->artists);
    }

    public function testGetArtists()
    {
        $artists = [
            '6v8FB84lnmJs434UJf2Mrm',
            '6olE6TJLqED3rqDCT0FyPh',
        ];

        $expected = [
            'ids' => '6v8FB84lnmJs434UJf2Mrm,6olE6TJLqED3rqDCT0FyPh',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('artists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/artists/',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getArtists($artists);

        $this->assertNotEmpty($response->artists);
    }

    public function testGetArtistAlbums()
    {
        $options = [
            'album_type' => ['album', 'single'],
            'limit' => 10,
            'market' => 'SE',
        ];

        $expected = [
            'album_type' => 'album,single',
            'market' => 'SE',
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('artist-albums'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/artists/36QJpDe2go2KgaRleHCDTp/albums',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getArtistAlbums('36QJpDe2go2KgaRleHCDTp', $options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetArtistTopTracks()
    {
        $options = [
            'country' => 'SE',
        ];

        $expected = [
            'country' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('artist-top-tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/artists/36QJpDe2go2KgaRleHCDTp/top-tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getArtistTopTracks('36QJpDe2go2KgaRleHCDTp', $options);

        $this->assertObjectHasAttribute('tracks', $response);
    }

    public function testGetAudioFeatures()
    {
        $tracks = [
            '0eGsygTp906u18L0Oimnem',
            '1lDWb6b6ieDQ2xT7ewTC3G',
        ];

        $expected = [
            'ids' => '0eGsygTp906u18L0Oimnem,1lDWb6b6ieDQ2xT7ewTC3G',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('audio-features'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/audio-features',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getAudioFeatures($tracks);

        $this->assertObjectHasAttribute('audio_features', $response);
    }

    public function testGetCategoriesList()
    {
        $options = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $expected = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('categories-list'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/browse/categories',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getCategoriesList($options);

        $this->assertObjectHasAttribute('categories', $response);
    }

    public function testGetCategory()
    {
        $options = [
            'country' => 'SE',
            'locale' => 'sv-SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('category'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/browse/categories/party',
            $options,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getCategory('party', $options);

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetCategoryPlaylists()
    {
        $options = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $expected = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('category-playlists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/browse/categories/party/playlists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getCategoryPlaylists('party', $options);

        $this->assertObjectHasAttribute('playlists', $response);
    }

    public function testGetFeaturedPlaylists()
    {
        $options = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $expected = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('featured-playlists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/browse/featured-playlists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getFeaturedPlaylists($options);

        $this->assertObjectHasAttribute('playlists', $response);
    }

    public function testGetGenreSeeds()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('available-genre-seeds'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/recommendations/available-genre-seeds',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getGenreSeeds();

        $this->assertObjectHasAttribute('genres', $response);
    }

    public function testGetLastResponse()
    {
        $return = [
            'body' => get_fixture('track'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/tracks/7EjyzZcbLxW7PaaLua9Ksb',
            [],
            [],
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb');

        $response = $api->getLastResponse();

        $this->assertArrayHasKey('body', $response);
    }

    public function testGetNewReleases()
    {
        $options = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $expected = [
            'country' => 'SE',
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('albums'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/browse/new-releases',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getNewReleases($options);

        $this->assertObjectHasAttribute('albums', $response);
    }

    public function testGetMyPlaylists()
    {
        $options = [
            'limit' => 10,
        ];

        $expected = [
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('my-playlists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/playlists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getMyPlaylists($options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetMySavedAlbums()
    {
        $options = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $expected = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-albums'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/albums',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getMySavedAlbums($options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetMySavedTracks()
    {
        $options = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $expected = [
            'limit' => 10,
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getMySavedTracks($options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetMyTop()
    {
        $options = [
            'limit' => 10,
            'time_range' => 'long_term',
        ];

        $expected = [
            'limit' => 10,
            'time_range' => 'long_term',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('top-artists-and-tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/top/artists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getMyTop('artists', $options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetRecommendations()
    {
        $options = [
            'limit' => 10,
            'seed_tracks' => ['0eGsygTp906u18L0Oimnem', '1lDWb6b6ieDQ2xT7ewTC3G'],
        ];

        $expected = [
            'limit' => 10,
            'seed_tracks' => '0eGsygTp906u18L0Oimnem,1lDWb6b6ieDQ2xT7ewTC3G',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('recommendations'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/recommendations',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getRecommendations($options);

        $this->assertObjectHasAttribute('seeds', $response);
    }

    public function testGetReturnAssoc()
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $stub = $this->getMock('SpotifyWebAPI\Request');
        $stub->expects($this->once())
            ->method('getReturnAssoc')
            ->willReturn(true);

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);

        $this->assertTrue($api->getReturnAssoc());

        PHPUnit_Framework_Error_Deprecated::$enabled = true;
    }

    public function testGetReturnType()
    {
        $stub = $this->getMock('SpotifyWebAPI\Request');
        $stub->expects($this->once())
            ->method('getReturnType')
            ->willReturn(SpotifyWebAPI\SpotifyWebAPI::RETURN_ASSOC);

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);

        $this->assertEquals(SpotifyWebAPI\SpotifyWebAPI::RETURN_ASSOC, $api->getReturnType());
    }

    public function testGetRequest()
    {
        $api = new SpotifyWebAPI\SpotifyWebAPI();

        $this->assertInstanceOf(SpotifyWebAPI\Request::class, $api->getRequest());
    }

    public function testGetTrack()
    {
        $options = [
            'market' => 'SE',
        ];

        $expected = [
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('track'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/tracks/0eGsygTp906u18L0Oimnem',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getTrack('0eGsygTp906u18L0Oimnem', $options);

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetTracks()
    {
        $tracks = [
            '0eGsygTp906u18L0Oimnem',
            '1lDWb6b6ieDQ2xT7ewTC3G',
        ];

        $options = [
            'market' => 'SE',
        ];

        $expected = [
            'ids' => '0eGsygTp906u18L0Oimnem,1lDWb6b6ieDQ2xT7ewTC3G',
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/tracks/',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getTracks($tracks, $options);

        $this->assertNotEmpty($response->tracks);
    }

    public function testGetUser()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/users/mcgurk',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getUser('mcgurk');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetUserFollowedArtists()
    {
        $options = [
            'limit' => 10,
        ];

        $expected = [
            'limit' => 10,
            'type' => 'artist',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-followed-artists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/following',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getUserFollowedArtists($options);

        $this->assertObjectHasAttribute('artists', $response);
    }

    public function testGetUserPlaylist()
    {
        $options = [
            'fields' => ['id', 'uri'],
            'market' => 'SE',
        ];

        $expected = [
            'fields' => 'id,uri',
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-playlist'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getUserPlaylist('mcgurk', '0UZ0Ll4HJHR7yvURYbHJe9', $options);

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetUserPlaylists()
    {
        $options = [
            'limit' => 10,
        ];

        $expected = [
            'limit' => 10,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-playlists'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/users/mcgurk/playlists',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getUserPlaylists('mcgurk', $options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetUserPlaylistTracks()
    {
        $options = [
            'fields' => ['id', 'uri'],
            'limit' => 10,
            'market' => 'SE',
        ];

        $expected = [
            'fields' => 'id,uri',
            'limit' => 10,
            'market' => 'SE',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-playlist-tracks'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->getUserPlaylistTracks('mcgurk', '0UZ0Ll4HJHR7yvURYbHJe9', $options);

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testMe()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->me();

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testMyAlbumsContains()
    {
        $albums = [
            '1oR3KrPIp4CbagPa3PhtPp',
            '6lPb7Eoon6QPbscWbMsk6a',
        ];

        $expected = [
            'ids' => '1oR3KrPIp4CbagPa3PhtPp,6lPb7Eoon6QPbscWbMsk6a',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-albums-contains'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/albums/contains',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->myAlbumsContains($albums);

        $this->assertTrue($response[0]);
    }

    public function testMyTracksContains()
    {
        $tracks = [
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l'
        ];

        $expected = [
            'ids' => '1id6H6vcwSB9GGv9NXh5cl,3mqRLlD9j92BBv1ueFhJ1l',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('user-tracks-contains'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/me/tracks/contains',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->myTracksContains($tracks);

        $this->assertTrue($response[0]);
    }

    public function testReorderUserPlaylistTracks()
    {
        $options = [
            'insert_before' => 20,
            'range_length' => 5,
            'range_start' => 0,
        ];

        $expected = json_encode([
            'insert_before' => 20,
            'range_length' => 5,
            'range_start' => 0,
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'body' => get_fixture('snapshot-id'),
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->reorderUserPlaylistTracks(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $options
        );

        $this->assertNotFalse($response);
    }

    public function testReplaceUserPlaylistTracks()
    {
        $tracks = [
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l',
        ];

        $expected = json_encode([
            'uris' => [
                'spotify:track:1id6H6vcwSB9GGv9NXh5cl',
                'spotify:track:3mqRLlD9j92BBv1ueFhJ1l',
            ],
        ]);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 201,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/tracks',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->replaceUserPlaylistTracks(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $tracks
        );

        $this->assertTrue($response);
    }

    public function testSearch()
    {
        $types = [
            'album',
            'artist',
        ];

        $options = [
            'limit' => 10,
        ];

        $expected = [
            'limit' => 10,
            'q' => 'blur',
            'type' => 'album,artist',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('search-album'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/search',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->search(
            'blur',
            $types,
            $options
        );

        $this->assertNotEmpty($response->albums);
    }

    public function testSetReturnAssoc()
    {
        PHPUnit_Framework_Error_Deprecated::$enabled = false;

        $stub = $this->getMock('SpotifyWebAPI\Request');
        $stub->expects($this->once())
            ->method('setReturnType')
            ->with(SpotifyWebAPI\SpotifyWebAPI::RETURN_ASSOC);

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setReturnAssoc(true);

        PHPUnit_Framework_Error_Deprecated::$enabled = true;
    }

    public function testSetReturnType()
    {
        $stub = $this->getMock('SpotifyWebAPI\Request');
        $stub->expects($this->once())
            ->method('setReturnType')
            ->with(SpotifyWebAPI\SpotifyWebAPI::RETURN_ASSOC);

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setReturnType(SpotifyWebAPI\SpotifyWebAPI::RETURN_ASSOC);
    }

    public function testUnfollowArtistsOrUsers()
    {
        $options = [
            'ids' => [
                '74ASZWbe4lXaubB36ztrGX',
                '36QJpDe2go2KgaRleHCDTp',
            ],
        ];

        $expected = json_encode($options);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 204,
        ];

        $stub = $this->setupStub(
            'DELETE',
            '/v1/me/following?type=artist',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->unFollowArtistsOrUsers(
            'artist',
            ['74ASZWbe4lXaubB36ztrGX', '36QJpDe2go2KgaRleHCDTp']
        );

        $this->assertTrue($response);
    }

    public function testUnfollowPlaylist()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'DELETE',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/followers',
            [],
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->unFollowPlaylist(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9'
        );

        $this->assertTrue($response);
    }

    public function testUpdateUserPlaylist()
    {
        $options = [
            'name' => 'New playlist name',
            'public' => false,
        ];

        $expected = json_encode($options);

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json',
        ];

        $return = [
            'status' => 200,
        ];

        $stub = $this->setupStub(
            'PUT',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->updateUserPlaylist(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $options
        );

        $this->assertTrue($response);
    }

    public function testUserFollowsPlaylist()
    {
        $options = [
            'ids' => [
                '74ASZWbe4lXaubB36ztrGX',
                '36QJpDe2go2KgaRleHCDTp',
            ],
        ];

        $expected = [
            'ids' => '74ASZWbe4lXaubB36ztrGX,36QJpDe2go2KgaRleHCDTp',
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ];

        $return = [
            'body' => get_fixture('users-follows-playlist'),
        ];

        $stub = $this->setupStub(
            'GET',
            '/v1/users/mcgurk/playlists/0UZ0Ll4HJHR7yvURYbHJe9/followers/contains',
            $expected,
            $headers,
            $return
        );

        $api = new SpotifyWebAPI\SpotifyWebAPI($stub);
        $api->setAccessToken($this->accessToken);
        $response = $api->userFollowsPlaylist(
            'mcgurk',
            '0UZ0Ll4HJHR7yvURYbHJe9',
            $options
        );

        $this->assertTrue($response[0]);
    }
}
