# Changelog
## 0.6.0
* **This release contains breaking changes, read through this list before updating.**
* All static methods on `Request` have been removed. `Request` now needs to be instantiated before using.
* All methods that accepted the `limit` option now uses the correct Spotify default value if nothing has been specified.
* It's now possible to specify your own `Request` object in `SpotifyWebAPI` and `Session` constructors.
* `SpotifyWebAPI::getArtistAlbums()` now supports the `album_type` option.
* `Request::send()` will only modify URLs when needed.

## 0.5.0
* The following methods has been added
    * `Session::getExpires()`
    * `Session::getRefreshToken()`
    * `Session::setRefreshToken()`
    * `SpotifyWebAPI::getFeaturedPlaylists()`
    * `SpotifyWebAPI::getNewReleases()`
* The following options has been added
    * `offset` and `limit` to `SpotifyWebAPI::getUserPlaylists()`
    * `offset` and `limit` to `SpotifyWebAPI::getUserPlaylistTracks()`
    * `fields` to `SpotifyWebAPI::getUserPlaylistTracks()`
    * `market` to `SpotifyWebAPI::getArtistAlbums()`
    * `market` to `SpotifyWebAPI::search()`
* Better handling of HTTP response codes in `Request::send()`.
* Fixed a bug where `SpotifyWebAPIException` messages weren't correctly set.
* Fixed various issues related to user playlists.

## 0.4.0
* **This release contains lots of breaking changes, read through this list before updating.**
* All methods which previously required a Spotify URI now just needs an ID.
* `deletePlaylistTrack()` has been renamed to `deletePlaylistTracks()`.
* When something goes wrong, a `SpotifyWebAPIException` is thrown.
* The `SpotifyWebAPI` methods are no longer static, you'll need to instantiate the class now.

## 0.3.0
* Added new methods to
    * Get Current User’s Saved Tracks.
    * Check Current User’s Saved Tracks.
    * Save Tracks for Current User.
    * Remove Tracks for Current User.
    * Change a Playlist’s Details.
    * Remove Tracks from a Playlist.
    * Replace a Playlist’s Tracks.
* Added support for the Client Credentials Authorization Flow.
* Added support for more HTTP methods in `Request::send()`.

## 0.2.0
* Added Artist’s Related Artists endpoint.
* Added `offset` and `limit` options for `SpotifyWebAPI::getAlbumTracks()` and `SpotifyWebAPI::getArtistAlbums()`.
* Replaced PSR-0 autoloading with PSR-4 autoloading.
* Changed method signature of `Session::getAuthorizeUrl()` and added `show_dialog` option.
* Added missing returns for `SpotifyWebAPI::getUserPlaylist()` and `SpotifyWebAPI::getUserPlaylistTracks()`.
* Fixed a bug where search terms were double encoded.

## 0.1.0
* Initial release
