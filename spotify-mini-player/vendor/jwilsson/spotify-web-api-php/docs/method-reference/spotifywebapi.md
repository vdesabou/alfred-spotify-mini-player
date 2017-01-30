---
layout: default
title: Method Reference - SpotifyWebAPI
---

## Constants
**RETURN_ASSOC**
**RETURN_OBJECT**

## Methods

### __construct


     SpotifyWebAPI\SpotifyWebAPI::__construct(\SpotifyWebAPI\Request $request)

Constructor<br>
Set up Request object.




### addMyAlbums


    boolean SpotifyWebAPI\SpotifyWebAPI::addMyAlbums(string|array $albums)

Add albums to the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/save-albums-user/](https://developer.spotify.com/web-api/save-albums-user/)


#### Return values
* **boolean** Whether the albums was successfully added.



### addMyTracks


    boolean SpotifyWebAPI\SpotifyWebAPI::addMyTracks(string|array $tracks)

Add tracks to the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/save-tracks-user/](https://developer.spotify.com/web-api/save-tracks-user/)


#### Return values
* **boolean** Whether the tracks was successfully added.



### addUserPlaylistTracks


    boolean SpotifyWebAPI\SpotifyWebAPI::addUserPlaylistTracks(string $userId, string $playlistId, string|array $tracks, array|object $options)

Add tracks to a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/add-tracks-to-playlist/](https://developer.spotify.com/web-api/add-tracks-to-playlist/)


#### Return values
* **boolean** Whether the tracks was successfully added.



### createUserPlaylist


    array|object SpotifyWebAPI\SpotifyWebAPI::createUserPlaylist(string $userId, array|object $options)

Create a new playlist for a user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/create-playlist/](https://developer.spotify.com/web-api/create-playlist/)


#### Return values
* **array\|object** The new playlist. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### currentUserFollows


    array SpotifyWebAPI\SpotifyWebAPI::currentUserFollows(string $type, string|array $ids)

Check to see if the current user is following one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-current-user-follows/](https://developer.spotify.com/web-api/check-current-user-follows/)


#### Return values
* **array** Whether each user or artist is followed.



### deleteMyAlbums


    boolean SpotifyWebAPI\SpotifyWebAPI::deleteMyAlbums(string|array $albums)

Delete albums from current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-albums-user/](https://developer.spotify.com/web-api/remove-albums-user/)


#### Return values
* **boolean** Whether the albums was successfully deleted.



### deleteMyTracks


    boolean SpotifyWebAPI\SpotifyWebAPI::deleteMyTracks(string|array $tracks)

Delete tracks from current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-tracks-user/](https://developer.spotify.com/web-api/remove-tracks-user/)


#### Return values
* **boolean** Whether the tracks was successfully deleted.



### deleteUserPlaylistTracks


    string|boolean SpotifyWebAPI\SpotifyWebAPI::deleteUserPlaylistTracks(string $userId, string $playlistId, array $tracks, string $snapshotId)

Delete tracks from a playlist and retrieve a new snapshot ID.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-tracks-playlist/](https://developer.spotify.com/web-api/remove-tracks-playlist/)


#### Return values
* **string\|boolean** A new snapshot ID or false if the tracks weren&#039;t successfully deleted.



### followArtistsOrUsers


    boolean SpotifyWebAPI\SpotifyWebAPI::followArtistsOrUsers(string $type, string|array $ids)

Add the current user as a follower of one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/follow-artists-users/](https://developer.spotify.com/web-api/follow-artists-users/)


#### Return values
* **boolean** Whether the artist or user was successfully followed.



### followPlaylist


    boolean SpotifyWebAPI\SpotifyWebAPI::followPlaylist(string $userId, string $playlistId, array|object $options)

Add the current user as a follower of a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/follow-playlist/](https://developer.spotify.com/web-api/follow-playlist/)


#### Return values
* **boolean** Whether the playlist was successfully followed.



### getAlbum


    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbum(string $albumId)

Get a album.<br>
[https://developer.spotify.com/web-api/get-album/](https://developer.spotify.com/web-api/get-album/)


#### Return values
* **array\|object** The requested album. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getAlbums


    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbums(array $albumIds, array|object $options)

Get multiple albums.<br>
[https://developer.spotify.com/web-api/get-several-albums/](https://developer.spotify.com/web-api/get-several-albums/)


#### Return values
* **array\|object** The requested albums. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getAlbumTracks


    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbumTracks(string $albumId, array|object $options)

Get a album's tracks.<br>
[https://developer.spotify.com/web-api/get-albums-tracks/](https://developer.spotify.com/web-api/get-albums-tracks/)


#### Return values
* **array\|object** The requested album tracks. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getArtist


    array|object SpotifyWebAPI\SpotifyWebAPI::getArtist(string $artistId)

Get an artist.<br>
[https://developer.spotify.com/web-api/get-artist/](https://developer.spotify.com/web-api/get-artist/)


#### Return values
* **array\|object** The requested artist. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getArtists


    array|object SpotifyWebAPI\SpotifyWebAPI::getArtists(array $artistIds)

Get multiple artists.<br>
[https://developer.spotify.com/web-api/get-several-artists/](https://developer.spotify.com/web-api/get-several-artists/)


#### Return values
* **array\|object** The requested artists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getArtistRelatedArtists


    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistRelatedArtists(string $artistId)

Get an artist's related artists.<br>
[https://developer.spotify.com/web-api/get-related-artists/](https://developer.spotify.com/web-api/get-related-artists/)


#### Return values
* **array\|object** The artist&#039;s related artists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getArtistAlbums


    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistAlbums(string $artistId, array|object $options)

Get an artist's albums.<br>
[https://developer.spotify.com/web-api/get-artists-albums/](https://developer.spotify.com/web-api/get-artists-albums/)


#### Return values
* **array\|object** The artist&#039;s albums. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getArtistTopTracks


    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistTopTracks(string $artistId, array|object $options)

Get an artist's top tracks in a country.<br>
[https://developer.spotify.com/web-api/get-artists-top-tracks/](https://developer.spotify.com/web-api/get-artists-top-tracks/)


#### Return values
* **array\|object** The artist&#039;s top tracks. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getAudioFeatures


    array|object SpotifyWebAPI\SpotifyWebAPI::getAudioFeatures(array $trackIds)

Get track audio features.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-several-audio-features/](https://developer.spotify.com/web-api/get-several-audio-features/)


#### Return values
* **array\|object** The tracks&#039; audio features. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getCategoriesList


    array|object SpotifyWebAPI\SpotifyWebAPI::getCategoriesList(array|object $options)

Get a list of categories used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-categories/](https://developer.spotify.com/web-api/get-list-categories/)


#### Return values
* **array\|object** The list of categories. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getCategory


    array|object SpotifyWebAPI\SpotifyWebAPI::getCategory(string $categoryId, array|object $options)

Get a single category used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-category/](https://developer.spotify.com/web-api/get-category/)


#### Return values
* **array\|object** The category. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getCategoryPlaylists


    array|object SpotifyWebAPI\SpotifyWebAPI::getCategoryPlaylists(string $categoryId, array|object $options)

Get a list of Spotify playlists tagged with a particular category.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-categorys-playlists/](https://developer.spotify.com/web-api/get-categorys-playlists/)


#### Return values
* **array\|object** The list of playlists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getFeaturedPlaylists


    array|object SpotifyWebAPI\SpotifyWebAPI::getFeaturedPlaylists(array|object $options)

Get Spotify featured playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-featured-playlists/](https://developer.spotify.com/web-api/get-list-featured-playlists/)


#### Return values
* **array\|object** The featured playlists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getGenreSeeds


    array|object SpotifyWebAPI\SpotifyWebAPI::getGenreSeeds()

Get a list of possible seed genres.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-recommendations/](https://developer.spotify.com/web-api/get-recommendations/)#available-genre-seeds


#### Return values
* **array\|object** All possible seed genres. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getLastResponse


    array SpotifyWebAPI\SpotifyWebAPI::getLastResponse()

Get the latest full response from the Spotify API.


#### Return values
* **array** Response data.
    * array\|object body The response body. Type is controlled by `SpotifyWebAPI::setReturnType()`.
    * array headers Response headers.
    * int status HTTP status code.
    * string url The requested URL.



### getNewReleases


    array|object SpotifyWebAPI\SpotifyWebAPI::getNewReleases(array|object $options)

Get new releases.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-new-releases/](https://developer.spotify.com/web-api/get-list-new-releases/)


#### Return values
* **array\|object** The new releases. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getMyPlaylists


    array|object SpotifyWebAPI\SpotifyWebAPI::getMyPlaylists(array|object $options)

Get the current user’s playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-a-list-of-current-users-playlists/](https://developer.spotify.com/web-api/get-a-list-of-current-users-playlists/)


#### Return values
* **array\|object** The user&#039;s playlists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getMySavedAlbums


    array|object SpotifyWebAPI\SpotifyWebAPI::getMySavedAlbums(array|object $options)

Get the current user’s saved albums.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-saved-albums/](https://developer.spotify.com/web-api/get-users-saved-albums/)


#### Return values
* **array\|object** The user&#039;s saved albums. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getMySavedTracks


    array|object SpotifyWebAPI\SpotifyWebAPI::getMySavedTracks(array|object $options)

Get the current user’s saved tracks.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-saved-tracks/](https://developer.spotify.com/web-api/get-users-saved-tracks/)


#### Return values
* **array\|object** The user&#039;s saved tracks. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getMyTop


    array|object SpotifyWebAPI\SpotifyWebAPI::getMyTop(string $type, $options)

Get the current user's top tracks or artists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-top-artists-and-tracks/](https://developer.spotify.com/web-api/get-users-top-artists-and-tracks/)


#### Return values
* **array\|object** A list with the requested top entity. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getRecommendations


    array|object SpotifyWebAPI\SpotifyWebAPI::getRecommendations(array|object $options)

Get recommendations based on artists, tracks, or genres.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-recommendations/](https://developer.spotify.com/web-api/get-recommendations/)


#### Return values
* **array\|object** The requested recommendations. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getReturnAssoc

_Deprecated: Use `SpotifyWebAPI::getReturnType()` instead._

    boolean SpotifyWebAPI\SpotifyWebAPI::getReturnAssoc()

Use `SpotifyWebAPI::getReturnType()` instead.


#### Return values
* **boolean** Whether an associative array or an stdClass is returned.



### getReturnType


    string SpotifyWebAPI\SpotifyWebAPI::getReturnType()

Get a value indicating the response body type.


#### Return values
* **string** A value indicating if the response body is an object or associative array.



### getRequest


    \SpotifyWebAPI\Request SpotifyWebAPI\SpotifyWebAPI::getRequest()

Get the Request object in use.


#### Return values
* **\SpotifyWebAPI\Request** The Request object in use.



### getTrack


    array|object SpotifyWebAPI\SpotifyWebAPI::getTrack(string $trackId, array|object $options)

Get a track.<br>
[https://developer.spotify.com/web-api/get-track/](https://developer.spotify.com/web-api/get-track/)


#### Return values
* **array\|object** The requested track. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getTracks


    array|object SpotifyWebAPI\SpotifyWebAPI::getTracks(array $trackIds, array|object $options)

Get multiple tracks.<br>
[https://developer.spotify.com/web-api/get-several-tracks/](https://developer.spotify.com/web-api/get-several-tracks/)


#### Return values
* **array\|object** The requested tracks. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getUser


    array|object SpotifyWebAPI\SpotifyWebAPI::getUser(string $userId)

Get a user.<br>
[https://developer.spotify.com/web-api/get-users-profile/](https://developer.spotify.com/web-api/get-users-profile/)


#### Return values
* **array\|object** The requested user. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getUserFollowedArtists


    array|object SpotifyWebAPI\SpotifyWebAPI::getUserFollowedArtists(array|object $options)

Get the artists followed by the current user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-followed-artists/](https://developer.spotify.com/web-api/get-followed-artists/)


#### Return values
* **array\|object** A list of artists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getUserPlaylist


    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylist(string $userId, string $playlistId, array|object $options)

Get a user's specific playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-playlist/](https://developer.spotify.com/web-api/get-playlist/)


#### Return values
* **array\|object** The user&#039;s playlist. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getUserPlaylists


    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylists(string $userId, array|object $options)

Get a user's playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-users-playlists/](https://developer.spotify.com/web-api/get-list-users-playlists/)


#### Return values
* **array\|object** The user&#039;s playlists. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### getUserPlaylistTracks


    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylistTracks(string $userId, string $playlistId, array|object $options)

Get the tracks in a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-playlists-tracks/](https://developer.spotify.com/web-api/get-playlists-tracks/)


#### Return values
* **array\|object** The tracks in the playlist. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### me


    array|object SpotifyWebAPI\SpotifyWebAPI::me()

Get the currently authenticated user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-current-users-profile/](https://developer.spotify.com/web-api/get-current-users-profile/)


#### Return values
* **array\|object** The currently authenticated user. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### myAlbumsContains


    array SpotifyWebAPI\SpotifyWebAPI::myAlbumsContains(string|array $albums)

Check if albums are saved in the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-users-saved-albums/](https://developer.spotify.com/web-api/check-users-saved-albums/)


#### Return values
* **array** Whether each album is saved.



### myTracksContains


    array SpotifyWebAPI\SpotifyWebAPI::myTracksContains(string|array $tracks)

Check if tracks are saved in the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-users-saved-tracks/](https://developer.spotify.com/web-api/check-users-saved-tracks/)


#### Return values
* **array** Whether each track is saved.



### reorderUserPlaylistTracks


    string|boolean SpotifyWebAPI\SpotifyWebAPI::reorderUserPlaylistTracks(string $userId, string $playlistId, array|object $options)

Reorder the tracks in a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/reorder-playlists-tracks/](https://developer.spotify.com/web-api/reorder-playlists-tracks/)


#### Return values
* **string\|boolean** A new snapshot ID or false if the tracks weren&#039;t successfully reordered.



### replaceUserPlaylistTracks


    boolean SpotifyWebAPI\SpotifyWebAPI::replaceUserPlaylistTracks(string $userId, string $playlistId, string|array $tracks)

Replace all tracks in a user's playlist with new ones.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/replace-playlists-tracks/](https://developer.spotify.com/web-api/replace-playlists-tracks/)


#### Return values
* **boolean** Whether the tracks was successfully replaced.



### search


    array|object SpotifyWebAPI\SpotifyWebAPI::search(string $query, string|array $type, array|object $options)

Search for an item.<br>
Requires a valid access token if market=from_token is used.<br>
[https://developer.spotify.com/web-api/search-item/](https://developer.spotify.com/web-api/search-item/)


#### Return values
* **array\|object** The search results. Type is controlled by `SpotifyWebAPI::setReturnType()`.



### setAccessToken


    void SpotifyWebAPI\SpotifyWebAPI::setAccessToken(string $accessToken)

Set the access token to use.


#### Return values
* **void** 



### setReturnAssoc

_Deprecated: Use `SpotifyWebAPI::setReturnType()` instead._

    void SpotifyWebAPI\SpotifyWebAPI::setReturnAssoc(boolean $returnAssoc)

Use `SpotifyWebAPI::setReturnType()` instead.


#### Return values
* **void** 



### setReturnType


    void SpotifyWebAPI\SpotifyWebAPI::setReturnType(string $returnType)

Set the return type for the response body.


#### Return values
* **void** 



### unfollowArtistsOrUsers


    boolean SpotifyWebAPI\SpotifyWebAPI::unfollowArtistsOrUsers(string $type, string|array $ids)

Remove the current user as a follower of one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/unfollow-artists-users/](https://developer.spotify.com/web-api/unfollow-artists-users/)


#### Return values
* **boolean** Whether the artist(s) or user(s) were successfully unfollowed.



### unfollowPlaylist


    boolean SpotifyWebAPI\SpotifyWebAPI::unfollowPlaylist(string $userId, string $playlistId)

Remove the current user as a follower of a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/unfollow-playlist/](https://developer.spotify.com/web-api/unfollow-playlist/)


#### Return values
* **boolean** Whether the playlist was successfully unfollowed.



### updateUserPlaylist


    boolean SpotifyWebAPI\SpotifyWebAPI::updateUserPlaylist(string $userId, string $playlistId, array|object $options)

Update the details of a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/change-playlist-details/](https://developer.spotify.com/web-api/change-playlist-details/)


#### Return values
* **boolean** Whether the playlist was successfully updated.



### userFollowsPlaylist


    array SpotifyWebAPI\SpotifyWebAPI::userFollowsPlaylist(string $ownerId, string $playlistId, array|object $options)

Check if a user is following a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-user-following-playlist/](https://developer.spotify.com/web-api/check-user-following-playlist/)


#### Return values
* **array** Whether each user is following the playlist.


