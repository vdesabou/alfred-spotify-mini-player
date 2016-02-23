# Spotify Web API PHP

[![Build Status](https://travis-ci.org/jwilsson/spotify-web-api-php.svg?branch=master)](https://travis-ci.org/jwilsson/spotify-web-api-php)
[![Coverage Status](https://coveralls.io/repos/jwilsson/spotify-web-api-php/badge.svg?branch=master)](https://coveralls.io/r/jwilsson/spotify-web-api-php?branch=master)
[![Latest Stable Version](https://poser.pugx.org/jwilsson/spotify-web-api-php/v/stable.svg)](https://packagist.org/packages/jwilsson/spotify-web-api-php)

This is a PHP implementation of the [Spotify Web API](https://developer.spotify.com/web-api/). It includes the following:

* Helper methods for all API methods:
    * Information about artists, albums, tracks, and users.
    * Spotify catalog search.
    * Playlist and user music library management.
    * Music featured by Spotify.
* Authorization flow helpers.
* PSR-4 autoloading support.

## Requirements
* PHP 5.5 or greater.
* PHP [cURL extension](http://php.net/manual/en/book.curl.php) (Usually included with PHP).

## Installation
Add `spotify-web-api-php` as a dependency to your `composer.json`:

```json
"require": {
    "jwilsson/spotify-web-api-php": "^1.0.0"
}
```

For more instructions, please refer to the [documentation](http://jwilsson.github.io/spotify-web-api-php/).

## Examples

Add albums to the current user's library
```php
$api->addMyAlbums(array(
    '1oR3KrPIp4CbagPa3PhtPp',
    '6lPb7Eoon6QPbscWbMsk6a',
));
```

Add tracks to the current user's library
```php
$api->addMyTracks(array(
    '1id6H6vcwSB9GGv9NXh5cl',
    '3mqRLlD9j92BBv1ueFhJ1l',
));
```

Add tracks to a user's playlist
```php
$api->addUserPlaylistTracks('username', 'playlist_id', array(
    '1id6H6vcwSB9GGv9NXh5cl',
    '3mqRLlD9j92BBv1ueFhJ1l',
));
```

Create a new playlist for a user
```php
$api->createUserPlaylist('username', array(
    'name' => 'My shiny playlist',
));
```

Check if the current user follows a user or artist
```php
$follows = $api->currentUserFollows('user', array(
    'spotify',
    'spotify_france',
));

var_dump($follows);
```

Delete albums from the current user's library
```php
$api->deleteMyAlbums(array(
    '1oR3KrPIp4CbagPa3PhtPp',
    '6lPb7Eoon6QPbscWbMsk6a'
));
```

Delete tracks from the current user's library
```php
$api->deleteMyTracks(array(
    '1id6H6vcwSB9GGv9NXh5cl',
    '3mqRLlD9j92BBv1ueFhJ1l',
));
```

Delete tracks from a user's playlist
```php
$tracks = array(
    array('id' => '1id6H6vcwSB9GGv9NXh5cl'),
    array('id' => '3mqRLlD9j92BBv1ueFhJ1l'),
);

$api->deleteUserPlaylistTracks('username', 'playlist_id', $tracks, 'snapshot_id');
```

Follow an artist or user
```php
$api->followArtistsOrUsers('artist', array(
    '74ASZWbe4lXaubB36ztrGX',
    '2t9yJDJIEtvPmr2iRIdqBf',
));
```

Follow a playlist
```php
$api->followPlaylist('username', 'playlist_id');
```

Get an album
```php
$album = $api->getAlbum('7u6zL7kqpgLPISZYXNTgYk');

print_r($album);
```

Get multiple albums
```php
$albums = $api->getAlbums(array(
    '1oR3KrPIp4CbagPa3PhtPp',
    '6lPb7Eoon6QPbscWbMsk6a',
));

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
$artists = $api->getArtists(array(
    '6v8FB84lnmJs434UJf2Mrm',
    '6olE6TJLqED3rqDCT0FyPh',
));

print_r($artists);
```

Get all albums by an artist
```php
$albums = $api->getArtistAlbums('6v8FB84lnmJs434UJf2Mrm');

print_r($albums);
```

Get an artist's top tracks in a country
```php
$tracks = $api->getArtistTopTracks('6v8FB84lnmJs434UJf2Mrm', array(
    'country' => 'se',
));

print_r($tracks);
```

Get Spotify featured playlists
```php
$playlists = $api->getFeaturedPlaylists();

print_r($playlists);
```

Get Spotify list of categories
```php
$categories = $api->getCategoriesList(array(
    'country' => 'se',
));

print_r($categories);
```

Get Spotify category
```php
$category = $api->getCategory('dinner', array(
    'country' => 'se',
));

print_r($category);
```

Get playlists of a Spotify category
```php
$playlists = $api->getCategoryPlaylists('dinner', array(
    'country' => 'se',
));

print_r($playlists);
```

Get new releases
```php
$items = $api->getNewReleases(array(
    'country' => 'se',
));

print_r($items);
```

Get the current user's playlists
```php
$playlists = $api->getMyPlaylists();

print_r($playlists);
```

Get the current user's saved albums
```php
$albums = $api->getMySavedAlbums();

print_r($albums);
```

Get the current user's saved tracks
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
$tracks = $api->getTracks(array(
    '0eGsygTp906u18L0Oimnem',
    '1lDWb6b6ieDQ2xT7ewTC3G',
));

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

See if the current user's albums contains the specified ones
```php
$contains = $api->myAlbumsContains(array(
    '1oR3KrPIp4CbagPa3PhtPp',
    '6lPb7Eoon6QPbscWbMsk6a'
));

var_dump($contains);
```

See if the current user's tracks contains the specified tracks
```php
$contains = $api->myTracksContains(array(
    '0eGsygTp906u18L0Oimnem',
    '1lDWb6b6ieDQ2xT7ewTC3G',
));

var_dump($contains);
```

Reorder the tracks in a user's playlist
```php
$api->reorderUserPlaylistTracks('username', 'playlist_id', array(
    'range_start' => 1,
    'range_length' => 5,
    'insert_before' => 10,
    'snapshot_id' => 'GLiKqjND5IDWQCO9PwtLvHVjRXYYjEvpoliIQ5/gK7M5BMcxJ7rnGMGTKbmDRgU3',
));
```

Replace all tracks in a user's playlist with new ones
```php
$api->replaceUserPlaylistTracks('username', 'playlist_id', array(
    '0eGsygTp906u18L0Oimnem',
    '1lDWb6b6ieDQ2xT7ewTC3G',
));
```

Search for an album
```ph
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
    'limit' => 5,
));

print_r($tracks);
```

Search for tracks in a specific market
```php
$tracks = $api->search('song 2', 'track', array(
    'market' => 'se',
));

print_r($tracks);
```

Update a user's playlist
```php
$api->updateUserPlaylist('username', 'playlist_id', array(
    'name' => 'New name',
));
```

Unfollow an artist or user
```php
$api->unfollowArtistsOrUsers('user', array(
    'spotify',
    'spotify_france',
));
```

Unfollow a playlist
```php
$api->unfollowPlaylist('username', 'playlist_id');
```

Check if a user is following a playlist
```php
$users = array(
    'user1',
    'user2',
);

$api->userFollowsPlaylist('owner_id', 'playlist_id', array(
    'ids' => $users,
));
```

For more examples, please see the [homepage](http://jwilsson.github.io/spotify-web-api-php/examples/).

## License
MIT license. Please see [LICENSE.md](LICENSE.md) for more information.
