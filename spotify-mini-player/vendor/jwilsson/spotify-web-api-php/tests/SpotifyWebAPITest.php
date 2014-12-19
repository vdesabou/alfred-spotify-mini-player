<?php
use \SpotifyWebAPI;

class SpotifyWebAPITest extends PHPUnit_Framework_TestCase
{
    private $playlistID = '0UZ0Ll4HJHR7yvURYbHJe9';

    private function setupMock($fixture = 200)
    {
        if (is_int($fixture)) {
            $return = array(
                'status' => $fixture
            );
        } else {
            $fixture = __DIR__ . '/fixtures/' . $fixture . '.json';
            $fixture = file_get_contents($fixture);

            $response = json_decode($fixture);
            $return = array(
                'body' => $response
            );
        }

        $request = $this->getMock('SpotifyWebAPI\Request');
        $request->method('api')
                ->willReturn($return);

        $api = new SpotifyWebAPI\SpotifyWebAPI($request);
        return $api;
    }

    public function testAddMyTracksSingle()
    {
        $api = $this->setupMock();
        $response = $api->addMyTracks('7EjyzZcbLxW7PaaLua9Ksb');

        $this->assertTrue($response);
    }

    public function testAddMyTracksMultiple()
    {
        $api = $this->setupMock();
        $response = $api->addMyTracks(array(
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l'
        ));

        $this->assertTrue($response);
    }

    public function testAddUserPlaylistTracksSingle()
    {
        $api = $this->setupMock(201);
        $response = $api->addUserPlaylistTracks('mcgurk', $this->playlistID, '7EjyzZcbLxW7PaaLua9Ksb');

        $this->assertTrue($response);
    }

    public function testAddUserPlaylistTracksMultiple()
    {
        $api = $this->setupMock(201);
        $response = $api->addUserPlaylistTracks('mcgurk', $this->playlistID, array(
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l'
        ));

        $this->assertTrue($response);
    }

    public function testCreateUserPlaylist()
    {
        $api = $this->setupMock('user-playlist');
        $response = $api->createUserPlaylist('mcgurk', array(
            'name' => 'Test playlist',
            'public' => false
        ));

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testCreateUserPlaylistPublic()
    {
        $api = $this->setupMock('user-playlist-public');
        $response = $api->createUserPlaylist('mcgurk', array(
            'name' => 'Test playlist'
        ));

        $this->assertTrue($response->public);
    }

    public function testDeleteMyTracksSingle()
    {
        $api = $this->setupMock();
        $response = $api->deleteMyTracks('7EjyzZcbLxW7PaaLua9Ksb');

        $this->assertTrue($response);
    }

    public function testDeleteMyTracksMultiple()
    {
        $api = $this->setupMock();
        $response = $api->deleteMyTracks(array(
            '1id6H6vcwSB9GGv9NXh5cl',
            '3mqRLlD9j92BBv1ueFhJ1l'
        ));

        $this->assertTrue($response);
    }

    public function testDeletePlaylistTracksSingle()
    {
        $api = $this->setupMock('user-playlist-snapshot-id');
        $response = $api->deletePlaylistTracks('mcgurk', $this->playlistID, array(
            array(
                'id' => '7EjyzZcbLxW7PaaLua9Ksb'
            )
        ));

        $this->assertNotFalse($response);
    }

    public function testDeletePlaylistTracksMultiple()
    {
        $api = $this->setupMock('user-playlist-snapshot-id');
        $response = $api->deletePlaylistTracks('mcgurk', $this->playlistID, array(
            array(
                'id' => '1id6H6vcwSB9GGv9NXh5cl'
            ),
            array(
                'id' => '3mqRLlD9j92BBv1ueFhJ1l'
            )
        ));

        $this->assertNotFalse($response);
    }

    public function testGetAlbum()
    {
        $api = $this->setupMock('album');
        $response = $api->getAlbum('7u6zL7kqpgLPISZYXNTgYk');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetAlbums()
    {
        $api = $this->setupMock('albums');
        $response = $api->getAlbums(array(
            '1oR3KrPIp4CbagPa3PhtPp',
            '6lPb7Eoon6QPbscWbMsk6a'
        ));

        $this->assertObjectHasAttribute('id', $response->albums[0]);
        $this->assertObjectHasAttribute('id', $response->albums[1]);
    }

    public function testGetAlbumTracks()
    {
        $api = $this->setupMock('album-tracks');
        $response = $api->getAlbumTracks('1oR3KrPIp4CbagPa3PhtPp');

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetArtist()
    {
        $api = $this->setupMock('artist');
        $response = $api->getArtist('36QJpDe2go2KgaRleHCDTp');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetArtistRelatedArtists()
    {
        $api = $this->setupMock('artist-related-artists');
        $response = $api->getArtistRelatedArtists('36QJpDe2go2KgaRleHCDTp');

        $this->assertNotEmpty($response->artists);
    }

    public function testGetArtists()
    {
        $api = $this->setupMock('artists');
        $response = $api->getArtists(array(
            '6v8FB84lnmJs434UJf2Mrm',
            '6olE6TJLqED3rqDCT0FyPh'
        ));

        $this->assertObjectHasAttribute('id', $response->artists[0]);
        $this->assertObjectHasAttribute('id', $response->artists[1]);
    }

    public function testGetArtistAlbums()
    {
        $api = $this->setupMock('artist-albums');
        $response = $api->getArtistAlbums('6v8FB84lnmJs434UJf2Mrm');

        $this->assertObjectHasAttribute('items', $response);
    }

    public function testGetArtistTopTracks()
    {
        $api = $this->setupMock('artist-top-tracks');
        $response = $api->getArtistTopTracks('6v8FB84lnmJs434UJf2Mrm', 'se');

        $this->assertObjectHasAttribute('tracks', $response);
    }

    public function testGetFeaturedPlaylists()
    {
        $api = $this->setupMock('featured-playlists');
        $response = $api->getFeaturedPlaylists(array(
            'timestamp' => '2014-10-25T21:00:00' // Saturday night
        ));

        $this->assertObjectHasAttribute('playlists', $response);
    }

    public function testGetNewReleases()
    {
        $api = $this->setupMock('albums');
        $response = $api->getNewReleases(array(
            'country' => 'se',
        ));

        $this->assertObjectHasAttribute('albums', $response);
    }

    public function testGetMySavedTracks()
    {
        $api = $this->setupMock('user-tracks');
        $response = $api->getMySavedTracks();

        $this->assertNotEmpty($response->items);
    }

    public function testGetTrack()
    {
        $api = $this->setupMock('track');
        $response = $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetTracks()
    {
        $api = $this->setupMock('tracks');
        $response = $api->getTracks(array(
            '0eGsygTp906u18L0Oimnem',
            '1lDWb6b6ieDQ2xT7ewTC3G'
        ));

        $this->assertObjectHasAttribute('id', $response->tracks[0]);
        $this->assertObjectHasAttribute('id', $response->tracks[1]);
    }

    public function testGetUser()
    {
        $api = $this->setupMock('user');
        $response = $api->getUser('mcgurk');

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetUserPlaylists()
    {
        $api = $this->setupMock('user-playlists');
        $response = $api->getUserPlaylists('mcgurk');

        $this->assertNotEmpty($response->items);
    }

    public function testGetUserPlaylist()
    {
        $api = $this->setupMock('user-playlist');
        $response = $api->getUserPlaylist('mcgurk', $this->playlistID);

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testGetUserPlaylistTracks()
    {
        $api = $this->setupMock('user-playlist-tracks');
        $response = $api->getUserPlaylistTracks('mcgurk', $this->playlistID);

        $this->assertObjectHasAttribute('track', $response->items[0]);
        $this->assertObjectHasAttribute('track', $response->items[1]);
    }

    public function testMe()
    {
        $api = $this->setupMock('user');
        $response = $api->me();

        $this->assertObjectHasAttribute('id', $response);
    }

    public function testMyTracksContainsSingle()
    {
        $api = $this->setupMock('user-tracks-contain');
        $response = $api->myTracksContains('0oks4FnzhNp5QPTZtoet7c');

        $this->assertTrue($response[0]);
    }

    public function testMyTracksContainsMultiple()
    {
        $api = $this->setupMock('user-tracks-contains');
        $response = $api->myTracksContains(array(
            '0oks4FnzhNp5QPTZtoet7c',
            '69kOkLUCkxIZYexIgSG8rq'
        ));

        $this->assertTrue($response[0]);
        $this->assertTrue($response[1]);
    }

    public function testReplacePlaylistTracksSingle()
    {
        $api = $this->setupMock(201);
        $response = $api->replacePlaylistTracks('mcgurk', $this->playlistID, '7eEfbAG7wgV4AgkdTakVFT');

        $this->assertTrue($response);
    }

    public function testReplacePlaylistTracksMultiple()
    {
        $api = $this->setupMock(201);
        $response = $api->replacePlaylistTracks('mcgurk', $this->playlistID, array(
            '7kz6GbFr2MCI7PgXJOdq8c',
            '6HM9UgDB38hLDFm7e1RF6W'
        ));

        $this->assertTrue($response);
    }

    public function testSearchAlbum()
    {
        $api = $this->setupMock('search-album');
        $response = $api->search('blur', 'album');

        $this->assertNotEmpty($response->albums->items);
    }

    public function testSearchArtist()
    {
        $api = $this->setupMock('search-artist');
        $response = $api->search('blur', 'artist');

        $this->assertNotEmpty($response->artists->items);
    }

    public function testSearchTrack()
    {
        $api = $this->setupMock('search-track');
        $response = $api->search('song 2', 'track');

        $this->assertNotEmpty($response->tracks->items);
    }

    public function testUpdateUserPlaylist()
    {
        $api = $this->setupMock();
        $response = $api->updateUserPlaylist('mcgurk', $this->playlistID, array(
            'public' => false
        ));

        $this->assertTrue($response);
    }

    public function testSetReturnAssoc()
    {
        $request = $this->getMock('SpotifyWebAPI\Request');
        $request->expects($this->once())->method('setReturnAssoc')->with(true);

        $api = new SpotifyWebAPI\SpotifyWebAPI($request);
        $api->setReturnAssoc(true);
    }

    public function testGetReturnAssoc()
    {
        $request = $this->getMock('SpotifyWebAPI\Request');
        $request->expects($this->once())
            ->method('getReturnAssoc')
            ->willReturn(true);

        $api = new SpotifyWebAPI\SpotifyWebAPI($request);
        $this->assertTrue($api->getReturnAssoc(true));
    }
}
