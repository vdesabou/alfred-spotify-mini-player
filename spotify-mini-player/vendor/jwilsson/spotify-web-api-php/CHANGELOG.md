# Changelog
### 1.2.0 (2015-12-01)
* The following methods have been added:
  `SpotifyWebAPI::getMyPlaylists()` ([ea8f0a2](https://github.com/jwilsson/spotify-web-api-php/commit/ea8f0a2c23fb6bc4e496b6fb6885b5517626860f))
* Updated CA bundle. ([e6161fd](https://github.com/jwilsson/spotify-web-api-php/commit/e6161fd81d9851799315eb175a95ca8c001f31d3))

### 1.1.0 (2015-11-24)
* The following methods have been added:
    * `SpotifyWebAPI::addMyAlbums()` ([0027122](https://github.com/jwilsson/spotify-web-api-php/commit/0027122fe543ec9c3df9db3543be86683c7cd0d1))
    * `SpotifyWebAPI::deleteMyAlbums()` ([1d52172](https://github.com/jwilsson/spotify-web-api-php/commit/1d5217219095e0dded3f3afe300f72b91443d510))
    * `SpotifyWebAPI::getMySavedAlbums()` ([1bea486](https://github.com/jwilsson/spotify-web-api-php/commit/1bea4865d8323fa49d5b9f4ba4edc4cb68299115))
    * `SpotifyWebAPI::myAlbumsContains()` ([6f4ecfc](https://github.com/jwilsson/spotify-web-api-php/commit/6f4ecfc5ae929768f235367cf6deb259c8e75561))

### 1.0.0 (2015-10-13)
* **This release contains breaking changes, read through this list before updating.**
* The following, deprecated, methods have been removed:
    * `Session::refreshToken()` ([4d46e8c](https://github.com/jwilsson/spotify-web-api-php/commit/4d46e8ce5cda30924fb7afaa9886434a9a6e5c3c))
    * `Session::requestToken()` ([4d46e8c](https://github.com/jwilsson/spotify-web-api-php/commit/4d46e8ce5cda30924fb7afaa9886434a9a6e5c3c))
    * `SpotifyWebAPI::deletePlaylistTracks()` ([4d46e8c](https://github.com/jwilsson/spotify-web-api-php/commit/4d46e8ce5cda30924fb7afaa9886434a9a6e5c3c))
    * `SpotifyWebAPI::reorderPlaylistTracks()` ([4d46e8c](https://github.com/jwilsson/spotify-web-api-php/commit/4d46e8ce5cda30924fb7afaa9886434a9a6e5c3c))
    * `SpotifyWebAPI::replacePlaylistTracks()` ([4d46e8c](https://github.com/jwilsson/spotify-web-api-php/commit/4d46e8ce5cda30924fb7afaa9886434a9a6e5c3c))
* Added docs for the `market` parameter to the following methods:
    * `SpotifyWebAPI::getAlbums()` ([b83a131](https://github.com/jwilsson/spotify-web-api-php/commit/b83a1312a18039ba097c631194a01cef074f5f38))
    * `SpotifyWebAPI::getAlbumTracks()` ([c0a24d5](https://github.com/jwilsson/spotify-web-api-php/commit/c0a24d57cd15176df725ae8ea4217204a89c7ff8))
    * `SpotifyWebAPI::getMySavedTracks()` ([06ef152](https://github.com/jwilsson/spotify-web-api-php/commit/06ef15289c9533ce0d1a40e58821ae55aa4078da))
    * `SpotifyWebAPI::getTrack()` ([b48c2ff](https://github.com/jwilsson/spotify-web-api-php/commit/b48c2ff0e82603fefa37451cd83b317d78c2f11b))
    * `SpotifyWebAPI::getTracks()` ([ad7430a](https://github.com/jwilsson/spotify-web-api-php/commit/ad7430a6d91aa58eaace67e761623dffc43b6cdb))
    * `SpotifyWebAPI::getUserPlaylist()` ([a32ee7c](https://github.com/jwilsson/spotify-web-api-php/commit/a32ee7c2de48546f6a1b964ee7b379735e252cf2))
    * `SpotifyWebAPI::getUserPlaylistTracks()` ([0c104e8](https://github.com/jwilsson/spotify-web-api-php/commit/0c104e87db7076cbb363cd35ac8a307655c1c1c2))
* `Session::setRefreshToken()` has been removed, a refresh token is now passed directly to `Session::refreshAccessToken()` instead. ([62e7383](https://github.com/jwilsson/spotify-web-api-php/commit/62e7383d6cf732ff6c0fc4393711e29f1b12c69f))
* `Session::getExpires()` has been removed and `Session::getTokenExpiration()` has been added instead, returning the exact token expiration time. ([62e7383](https://github.com/jwilsson/spotify-web-api-php/commit/62e7383d6cf732ff6c0fc4393711e29f1b12c69f))
* The minimum required PHP version has been increased to 5.5 and support for PHP 7 has been added. ([b68ae3b](https://github.com/jwilsson/spotify-web-api-php/commit/b68ae3b524f462f3d3f0435617dd0cb21555a693), [6a8ac8d](https://github.com/jwilsson/spotify-web-api-php/commit/6a8ac8d309c4e6fbc076cb85897681fdb00f7a20))
* HTTP response headers returned by `Request::send()` and `SpotifyWebAPI::getLastResponse()` are now parsed to an array. ([9075bd3](https://github.com/jwilsson/spotify-web-api-php/commit/9075bd3289f02cee9b23ad596e308ad33dae0076))
* In `SpotifyWebAPI::deleteUserPlaylistTracks()`, `position` has been renamed to `positions` (note the extra "s"). This change was made to better align with the official Spotify docs. ([09f2636](https://github.com/jwilsson/spotify-web-api-php/commit/09f26369dc4c5f22ba8aee81cd858b9eb3584209))
* The `positions` argument to `SpotifyWebAPI::deleteUserPlaylistTracks()` now also accept `int`s. ([09f2636](https://github.com/jwilsson/spotify-web-api-php/commit/09f26369dc4c5f22ba8aee81cd858b9eb3584209))
* `SpotifyWebAPI::getArtistTopTracks()` now accepts an array of options. ([79543ac](https://github.com/jwilsson/spotify-web-api-php/commit/79543ac51850b91b4bf90a92c3482575524d0505))
* `Session::getAuthorizeUrl()` no longer sends empty query strings. ([c3e83e8](https://github.com/jwilsson/spotify-web-api-php/commit/c3e83e857560a299480ba7a41940835a0543c758))
* Stopped `SpotifyWebAPI::deleteUserPlaylistTracks()` from sending internal, leftover data. ([09f2636](https://github.com/jwilsson/spotify-web-api-php/commit/09f26369dc4c5f22ba8aee81cd858b9eb3584209))
* Clarified docs for `SpotifyWebAPI::followPlaylist()` and `SpotifyWebAPI::reorderUserPlaylistTracks()`. ([09f2636](https://github.com/jwilsson/spotify-web-api-php/commit/09f26369dc4c5f22ba8aee81cd858b9eb3584209))
* Fixed an issue where `SpotifyWebAPI::reorderUserPlaylistTracks()` couldn't reorder the first track. ([748592e](https://github.com/jwilsson/spotify-web-api-php/commit/748592ee7cc5a59f992d0ed0d49c1937931643cd))
* Better tests and coverage. ([09f2636](https://github.com/jwilsson/spotify-web-api-php/commit/09f26369dc4c5f22ba8aee81cd858b9eb3584209))

### 0.10.0 (2015-09-05)
* The following methods have been added:
    * `SpotifyWebAPI::getUserFollowedArtists()` ([b7142fa](https://github.com/jwilsson/spotify-web-api-php/commit/b7142fa466c307b56f285ab2aef546ecb8f998e2))

### 0.9.0 (2015-07-06)
* **This release contains breaking changes, read through this list before updating.**
* As we're moving closer to 1.0 the work to make the API more consistent and stable is continuing. This time with an effort to make method names and signatures more consistent.
* Thus, the following methods have been renamed and the old names are deprecated:
    * `SpotifyWebAPI::deletePlaylistTracks()` -> `SpotifyWebAPI::deleteUserPlaylistTracks()` ([8768328](https://github.com/jwilsson/spotify-web-api-php/commit/8768328aeeca1a82ebf652ad0ee557329ded6783))
    * `SpotifyWebAPI::reorderPlaylistTracks` -> `SpotifyWebAPI::reorderUserPlaylistTracks()` ([2ce8fc5](https://github.com/jwilsson/spotify-web-api-php/commit/2ce8fc51cc2a42d6b9055bc6ced1a0f777400486))
    * `SpotifyWebAPI::replacePlaylistTracks()` -> `SpotifyWebAPI::replaceUserPlaylistTracks()` ([6362510](https://github.com/jwilsson/spotify-web-api-php/commit/6362510344f746a37a75612d3f41030a60d81f2d))
* The following method arguments now also accepts strings:
    * `fields` in `SpotifyWebAPI::getUserPlaylistTracks()`. ([7a3c200](https://github.com/jwilsson/spotify-web-api-php/commit/7a3c200fb07ebcf11b60c5d778bbc4792855a5b9))
    * `fields` in `SpotifyWebAPI::getUserPlaylist()`. ([80cd7d0](https://github.com/jwilsson/spotify-web-api-php/commit/80cd7d08a8983a0519510445f122846d4939893d))
    * `album_type` in `SpotifyWebAPI::getArtistAlbums()`. ([4af0a53](https://github.com/jwilsson/spotify-web-api-php/commit/4af0a539df9b18550f6a7df337a07038775a5bed))
    * `ids` in `SpotifyWebAPI::userFollowsPlaylist()`. ([9cc11bb](https://github.com/jwilsson/spotify-web-api-php/commit/9cc11bba082e4accea0364d97a1c8486a9634971))
* A new method, `SpotifyWebAPI::getLastResponse()` has been introduced which allows for retrieval of the latest full response from the Spotify API. ([9b54074](https://github.com/jwilsson/spotify-web-api-php/commit/9b54074eb7ff3e223c1015580fb2dd975351975b))
* Lots of internal changes to increase code consistency and ensure full PSR-2 compatibility. ([2b8fda3](https://github.com/jwilsson/spotify-web-api-php/commit/2b8fda341176dddb8c9d4ef8ec808071efc54f49))
* Better handling of errors from cURL. ([c7b5529](https://github.com/jwilsson/spotify-web-api-php/commit/c7b5529cdac854de81fe87c79da5b318af15ca6a))

### 0.8.2 (2015-05-02)
* CA Root Certificates are now included with the library, allowing cURL to always find it. ([4ebee9b](https://github.com/jwilsson/spotify-web-api-php/commit/4ebee9b1b2ce53e622ace071f319e882d7c94cef))

### 0.8.1 (2015-03-29)
* Fixed an issue where `SpotifyWebAPI::updateUserPlaylist()` would fail without `name` set. ([39232f5](https://github.com/jwilsson/spotify-web-api-php/commit/39232f52c7efe090695dbf26e7dff1e1841db035))

### 0.8.0 (2015-03-22)
* **This release contains breaking changes, read through this list before updating.**
* The following methods have been renamed:
    * `Session::refreshToken()` -> `Session::refreshAccessToken()` ([7b6f31a](https://github.com/jwilsson/spotify-web-api-php/commit/7b6f31af4db435f1d3a94bef5758bdf3e864c65a))
    * `Session::requestToken()` -> `Session::requestAccessToken()` ([98c4a2a](https://github.com/jwilsson/spotify-web-api-php/commit/98c4a2a5b58e939bcfeba6ed72d07776c717698a))
* The following methods have been added:
    * `SpotifyWebAPI::currentUserFollows()` ([6dbab19](https://github.com/jwilsson/spotify-web-api-php/commit/6dbab19c39713126fa5172e959e157506a067f6d))
    * `SpotifyWebAPI::followArtistsOrUsers()` ([6dbab19](https://github.com/jwilsson/spotify-web-api-php/commit/6dbab19c39713126fa5172e959e157506a067f6d))
    * `SpotifyWebAPI::followPlaylist()` ([12ff351](https://github.com/jwilsson/spotify-web-api-php/commit/12ff3511deb732dbda11d547164eec34c5f47243))
    * `SpotifyWebAPI::getCategoriesList()` ([f09b4b8](https://github.com/jwilsson/spotify-web-api-php/commit/f09b4b8e9edcfe43cfad082123d49c5e2bbae873))
    * `SpotifyWebAPI::getCategory()` ([f09b4b8](https://github.com/jwilsson/spotify-web-api-php/commit/f09b4b8e9edcfe43cfad082123d49c5e2bbae873))
    * `SpotifyWebAPI::getCategoryPlaylists()` ([f09b4b8](https://github.com/jwilsson/spotify-web-api-php/commit/f09b4b8e9edcfe43cfad082123d49c5e2bbae873))
    * `SpotifyWebAPI::reorderPlaylistTracks()` ([0744904](https://github.com/jwilsson/spotify-web-api-php/commit/07449042143a87a5f8b0d73086c803bc4073407d))
    * `SpotifyWebAPI::unfollowArtistsOrUsers()` ([6dbab19](https://github.com/jwilsson/spotify-web-api-php/commit/6dbab19c39713126fa5172e959e157506a067f6d))
    * `SpotifyWebAPI::unfollowPlaylist()` ([12ff351](https://github.com/jwilsson/spotify-web-api-php/commit/12ff3511deb732dbda11d547164eec34c5f47243))
    * `SpotifyWebAPI::userFollowsPlaylist()` ([4293919](https://github.com/jwilsson/spotify-web-api-php/commit/42939192801bf69f915093f5d997ceab7599f8f9))
* The `$redirectUri` argument in `Session::__construct()` is now optional. ([8591ea8](https://github.com/jwilsson/spotify-web-api-php/commit/8591ea8f60373be953dceb41949bfc70aa1663c3))

## 0.7.0 (2014-12-06)
* The following methods to control the return type of all API methods were added:
    * `Request::getReturnAssoc()` ([b95bf3f](https://github.com/jwilsson/spotify-web-api-php/commit/b95bf3f3e4f702486e1de36633b131531b4a0546))
    * `Request::setReturnAssoc()` ([b95bf3f](https://github.com/jwilsson/spotify-web-api-php/commit/b95bf3f3e4f702486e1de36633b131531b4a0546))
    * `SpotifyWebAPI::getReturnAssoc()` ([b95bf3f](https://github.com/jwilsson/spotify-web-api-php/commit/b95bf3f3e4f702486e1de36633b131531b4a0546))
    * `SpotifyWebAPI::setReturnAssoc()` ([b95bf3f](https://github.com/jwilsson/spotify-web-api-php/commit/b95bf3f3e4f702486e1de36633b131531b4a0546))
* Added `fields` option to `SpotifyWebAPI::getUserPlaylist()`. ([c35e44d](https://github.com/jwilsson/spotify-web-api-php/commit/c35e44db2151e246a8b847653a2210d284125f7b))
* All methods now automatically send authorization headers (if a access token is supplied), increasing rate limits. ([a5e95a9](https://github.com/jwilsson/spotify-web-api-php/commit/a5e95a9015c076bfb30ca14336b6ca7f3a764e41))
* Lots of inline documentation improvements.

## 0.6.0 (2014-10-26)
* **This release contains breaking changes, read through this list before updating.**
* All static methods on `Request` have been removed. `Request` now needs to be instantiated before using. ([59207ac](https://github.com/jwilsson/spotify-web-api-php/commit/59207ac5705e8b43c1687b2e371e8133ddcf02fe))
* All methods that accepted the `limit` option now uses the correct Spotify default value if nothing has been specified. ([a291018](https://github.com/jwilsson/spotify-web-api-php/commit/a29101830b019e6acee0d03e1f11813a4a4a7a1b))
* It's now possible to specify your own `Request` object in `SpotifyWebAPI` and `Session` constructors. ([59207ac](https://github.com/jwilsson/spotify-web-api-php/commit/59207ac5705e8b43c1687b2e371e8133ddcf02fe))
* `SpotifyWebAPI::getArtistAlbums()` now supports the `album_type` option. ([1bd7014](https://github.com/jwilsson/spotify-web-api-php/commit/1bd7014f4d27d836e90128bf1c72dedcd7814645))
* `Request::send()` will only modify URLs when needed. ([0241f3b](https://github.com/jwilsson/spotify-web-api-php/commit/0241f3bf5c06dfb7a8ea0cd17f89d3ea06bb0688))

## 0.5.0 (2014-10-25)
* The following methods have been added:
    * `Session::getExpires()` ([c9c6da6](https://github.com/jwilsson/spotify-web-api-php/commit/c9c6da69333e74d8c8ae755998be8076e5e2deee))
    * `Session::getRefreshToken()` ([0d21147](https://github.com/jwilsson/spotify-web-api-php/commit/0d21147376196ab794d534197bc20227d67b6d14))
    * `Session::setRefreshToken()` ([ff83455](https://github.com/jwilsson/spotify-web-api-php/commit/ff83455439200f806eadc20d28e51b9d34502d78))
    * `SpotifyWebAPI::getFeaturedPlaylists()` ([c99537a](https://github.com/jwilsson/spotify-web-api-php/commit/c99537a907b802cfa5ee70b976ffe2f6e8135e6b))
    * `SpotifyWebAPI::getNewReleases()` ([7a8533c](https://github.com/jwilsson/spotify-web-api-php/commit/7a8533c0b0f8012cc84e360c8d472fce20a2fc48))
* The following options has been added:
    * `offset` and `limit` to `SpotifyWebAPI::getUserPlaylists()` ([3346857](https://github.com/jwilsson/spotify-web-api-php/commit/3346857ae82e8895741621d283ea57749ec9da48))
    * `offset` and `limit` to `SpotifyWebAPI::getUserPlaylistTracks()` ([1660600](https://github.com/jwilsson/spotify-web-api-php/commit/1660600fb35481e86a2ea8bd4bb915c0942b452a))
    * `fields` to `SpotifyWebAPI::getUserPlaylistTracks()` ([9a61003](https://github.com/jwilsson/spotify-web-api-php/commit/9a61003e904ec4b906487c28c91f1c0306d6ae0a))
    * `market` to `SpotifyWebAPI::getArtistAlbums()` ([98194dd](https://github.com/jwilsson/spotify-web-api-php/commit/98194dddd0e2e7f88f9b98429845c3d251afcbed))
    * `market` to `SpotifyWebAPI::search()` ([8883e79](https://github.com/jwilsson/spotify-web-api-php/commit/8883e799f997d477aa1b1c7ea44451c9087fb90b))
* Better handling of HTTP response codes in `Request::send()`. ([351be62](https://github.com/jwilsson/spotify-web-api-php/commit/351be62d3246dbd3beee2015a767d95ae6330e0a))
* Fixed a bug where `SpotifyWebAPIException` messages weren't correctly set. ([c764894](https://github.com/jwilsson/spotify-web-api-php/commit/c764894c4ab1e2fe7e872bcb1dc9670fdcde9135))
* Fixed various issues related to user playlists. ([9929d45](https://github.com/jwilsson/spotify-web-api-php/commit/9929d45c4dba49b3f76aa6ca0fde61ed4857a223))

## 0.4.0 (2014-09-01)
* **This release contains lots of breaking changes, read through this list before updating.**
* All methods which previously required a Spotify URI now just needs an ID. ([f1f14bd](https://github.com/jwilsson/spotify-web-api-php/commit/f1f14bd2ed0a77e1a6fdbee7091319c33cbfc634))
* `deletePlaylistTrack()` has been renamed to `deletePlaylistTracks()`. ([e54d703](https://github.com/jwilsson/spotify-web-api-php/commit/e54d703bd94d62a64058898e7d6cddf096b5a86a))
* When something goes wrong, a `SpotifyWebAPIException` is thrown. ([d98bb8a](https://github.com/jwilsson/spotify-web-api-php/commit/d98bb8aca96a73eb3495c3d84f5884117599d648))
* The `SpotifyWebAPI` methods are no longer static, you'll need to instantiate the class now. ([67c4e8b](https://github.com/jwilsson/spotify-web-api-php/commit/67c4e8ba1ce9e7f3bdd2d7acd6785e40a0949a4e))

## 0.3.0 (2014-08-23)
* The following methods have been added:
    * `SpotifyWebAPI::getMySavedTracks()` ([30c865d](https://github.com/jwilsson/spotify-web-api-php/commit/30c865d40771417646391bdd843dc1c7f5494c15))
    * `SpotifyWebAPI::myTracksContains()` ([3f99367](https://github.com/jwilsson/spotify-web-api-php/commit/3f9936710f1f1bdd11ea1cb36c87f101f94e0781))
    * `SpotifyWebAPI::addMyTracks()` ([20d80ef](https://github.com/jwilsson/spotify-web-api-php/commit/20d80efe183e5c484642d821eb37a6a53443f660))
    * `SpotifyWebAPI::deleteMyTracks()` ([ee17c69](https://github.com/jwilsson/spotify-web-api-php/commit/ee17c69b8d56c9466cfaac22d2243487dd3eff8c))
    * `SpotifyWebAPI::updateUserPlaylist()` ([5d5874d](https://github.com/jwilsson/spotify-web-api-php/commit/5d5874dd565e8156e123aed94f607eace3f28fb4))
    * `SpotifyWebAPI::deletePlaylistTrack()` ([3b17104](https://github.com/jwilsson/spotify-web-api-php/commit/3b1710494ce04ddae69b6edbccddc1b3530ca0fb))
    * `SpotifyWebAPI::deletePlaylistTrack()` ([3b5e23a](https://github.com/jwilsson/spotify-web-api-php/commit/3b5e23a30460ed4235259b23ff20eb1d0a87a43b))
* Added support for the Client Credentials Authorization Flow. ([0892e59](https://github.com/jwilsson/spotify-web-api-php/commit/0892e59022a15c79f6222ec82f596ca24af8fca3))
* Added support for more HTTP methods in `Request::send()`. ([d4df8c1](https://github.com/jwilsson/spotify-web-api-php/commit/d4df8c10f4f9f94ad4e0f2241bcbf0be0a81dede))

## 0.2.0 (2014-07-26)
* The following methods have been added:
    * `SpotifyWebAPI::getArtistRelatedArtists()` ([5a3ea0e](https://github.com/jwilsson/spotify-web-api-php/commit/5a3ea0e203d9b0285b1a671533aa64f81172eb49))
* Added `offset` and `limit` options for `SpotifyWebAPI::getAlbumTracks()` and `SpotifyWebAPI::getArtistAlbums()`. ([21c98ec](https://github.com/jwilsson/spotify-web-api-php/commit/21c98ec57f1714192d40b3f0736b3974cf1432f5), [8b0c417](https://github.com/jwilsson/spotify-web-api-php/commit/8b0c4170be46dcb6db25f942f264fa6fc77ac7fe))
* Replaced PSR-0 autoloading with PSR-4 autoloading. ([40878a9](https://github.com/jwilsson/spotify-web-api-php/commit/40878a93fcf158971d4d3674eeed7c44e44d1b97))
* Changed method signature of `Session::getAuthorizeUrl()` and added `show_dialog` option. ([8fe7a6e](https://github.com/jwilsson/spotify-web-api-php/commit/8fe7a6e5ada1c2195fdedfd560cb98cf7a422355), [57c36af](https://github.com/jwilsson/spotify-web-api-php/commit/57c36af84644393c801c86ca6542f4ab71d1eaf7))
* Added missing returns for `SpotifyWebAPI::getUserPlaylist()` and `SpotifyWebAPI::getUserPlaylistTracks()`. ([b8c87d7](https://github.com/jwilsson/spotify-web-api-php/commit/b8c87d7dfc830f6b4549ae564c1e3d78a6b6359c))
* Fixed a bug where search terms were double encoded. ([9f1eec6](https://github.com/jwilsson/spotify-web-api-php/commit/9f1eec6f4eeceb43a29f5f2748b88b1a1390b058))

## 0.1.0 (2014-06-28)
* Initial release
