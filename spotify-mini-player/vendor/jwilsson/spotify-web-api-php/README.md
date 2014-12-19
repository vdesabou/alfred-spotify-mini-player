# Spotify Web API PHP

[![Build Status](https://travis-ci.org/jwilsson/spotify-web-api-php.svg?branch=master)](https://travis-ci.org/jwilsson/spotify-web-api-php)
[![Coverage Status](https://img.shields.io/coveralls/jwilsson/spotify-web-api-php.svg)](https://coveralls.io/r/jwilsson/spotify-web-api-php?branch=master)
[![Latest Stable Version](https://poser.pugx.org/jwilsson/spotify-web-api-php/v/stable.svg)](https://packagist.org/packages/jwilsson/spotify-web-api-php)

This is a PHP implementation of the [Spotify Web API](https://developer.spotify.com/web-api/). It includes the following:

* Helper methods for all API methods (Information about artists, albums and tracks).
* Search the Spotify catalog.
* Get information about users and their music library.
* Manage playlists for users.
* Authorization flow helpers.
* PSR-4 autoloading support.

## Requirements
PHP 5.3 or greater.

## Installation

Please refer to the [documentation](http://jwilsson.github.io/spotify-web-api-php/) for installation and usage instructions.

## Examples

*We're in the middle of moving these examples to the [new site](http://jwilsson.github.io/spotify-web-api-php/examples/).*

Add tracks to a user's library

```php
$api->addMyTracks(array('1oR3KrPIp4CbagPa3PhtPp', '6lPb7Eoon6QPbscWbMsk6a'));
```

Add tracks to a user's playlist

```php
$api->addUserPlaylistTracks('username', 'playlist_id', array('1oR3KrPIp4CbagPa3PhtPp', '6lPb7Eoon6QPbscWbMsk6a'));
```

Create a new playlist for a user

```php
$api->createUserPlaylist('username', array('name' => 'My shiny playlist'));
```

Delete tracks from a user's library

```php
$api->deleteMyTracks(array('1oR3KrPIp4CbagPa3PhtPp', '6lPb7Eoon6QPbscWbMsk6a'));
```

Delete tracks from a user's playlist

```php
$tracks = array(
    array('id' => '1oR3KrPIp4CbagPa3PhtPp'),
    array('id' => '6lPb7Eoon6QPbscWbMsk6a')
);

$api->deletePlaylistTracks('username', 'playlist_id', $tracks, 'snapshot_id');
```

Get a album

```php
$album = $api->getAlbum('7u6zL7kqpgLPISZYXNTgYk');

print_r($album);
```

Get multiple albums

```php
$albums = $api->getAlbums(array('1oR3KrPIp4CbagPa3PhtPp', '6lPb7Eoon6QPbscWbMsk6a'));

print_r($albums);
```

Get all tracks from an album

```php
$tracks = $api->getAlbumTracks('1oR3KrPIp4CbagPa3PhtPp');

print_r($tracks);
```

Get an artist

```php
$artist = $api->getArtist('36QJpDe2go2KgaRleHCDTp');

print_r($artist);
```

Get an artist's related artists

```php
$artists = $api->getArtistRelatedArtists('36QJpDe2go2KgaRleHCDTp');

print_r($artists);
```

Get multiple artists

```php
$artists = $api->getArtists(array('6v8FB84lnmJs434UJf2Mrm', '6olE6TJLqED3rqDCT0FyPh'));

print_r($artists);
```

Get all albums by an artist

```php
$albums = $api->getArtistAlbums('6v8FB84lnmJs434UJf2Mrm');

print_r($albums);
```

Get an artist's top tracks in a country

```php
$tracks = $api->getArtistTopTracks('6v8FB84lnmJs434UJf2Mrm', 'se');

print_r($tracks);
```

Get Spotify featured playlists
```php
$playlists = $api->getFeaturedPlaylists();

print_r($playlists);
```

Get new releases
```php
$items = $api->getNewReleases(array(
    'country' => 'se'
));

print_r($items);
```

Get a user's saved tracks
```php
$tracks = $api->getMySavedTracks();

print_r($tracks);
```

Get a track

```php
$track = $api->getTrack('7EjyzZcbLxW7PaaLua9Ksb');

print_r($track);
```

Get multiple tracks

```php
$tracks = $api->getTracks(array('0eGsygTp906u18L0Oimnem', '1lDWb6b6ieDQ2xT7ewTC3G'));

print_r($tracks);
```

Get a user

```php
$user = $api->getUser('username');

print_r($user);
```

Get a user's playlists

```php
$playlists = $api->getUserPlaylists('username');

print_r($playlists);
```

Get a specific playlist

```php
$playlist = $api->getUserPlaylist('username', '606nLQuR41ZaA2vEZ4Ofb8');

print_r($playlist);
```

Get all tracks in a user's playlist

```php
$tracks = $api->getUserPlaylistTracks('username', '606nLQuR41ZaA2vEZ4Ofb8');

print_r($tracks);
```

Get the currently authenticated user

```php
$user = $api->me();

print_r($user);
```

See if a user's tracks contains the specified tracks

```php
$contains = $api->myTracksContains(array('0eGsygTp906u18L0Oimnem', '1lDWb6b6ieDQ2xT7ewTC3G'));

var_dump($contains);
```

Replace all tracks in a user's playlist with new ones

```php
$api->replacePlaylistTracks('username', 'playlist_id', array('0eGsygTp906u18L0Oimnem', '1lDWb6b6ieDQ2xT7ewTC3G'));
```

Search for an album

```php
$albums = $api->search('blur', 'album');

print_r($albums);
```

Search for an artist

```php
$artists = $api->search('blur', 'artist');

print_r($artists);
```

Search for a track

```php
$tracks = $api->search('song 2', 'track');

print_r($tracks);
```

Search with a limit

```php
$tracks = $api->search('song 2', 'track', array(
    'limit' => 5
));

print_r($tracks);
```

Search for tracks in a specific market

```php
$tracks = $api->search('song 2', 'track', array(
    'market' => 'se'
));

print_r($tracks);
```

Update a user's playlist

```php
$api->updateUserPlaylist('username', 'playlist_id', array('name' => 'New name'));
```

Browse through `src/SpotifyWebAPI.php` and look at the tests for more methods and examples.

## License
MIT license. Please see [LICENSE.md](LICENSE.md) for more information.
