---
layout: default
title: Method Reference - SpotifyWebAPI
---


### __construct

     SpotifyWebAPI\SpotifyWebAPI::__construct(\SpotifyWebAPI\Request $request)

Constructor<br>
Set up Request object.

#### Arguments
* `$request` **\SpotifyWebAPI\Request** - Optional. The Request object to use.




### addMyAlbums

    boolean SpotifyWebAPI\SpotifyWebAPI::addMyAlbums(string|array $albums)

Add albums to the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/save-albums-user/](https://developer.spotify.com/web-api/save-albums-user/)

#### Arguments
* `$albums` **string\|array** - ID(s) of the album(s) to add.


#### Return values
* **boolean** Whether the albums was successfully added.



### addMyTracks

    boolean SpotifyWebAPI\SpotifyWebAPI::addMyTracks(string|array $tracks)

Add tracks to the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/save-tracks-user/](https://developer.spotify.com/web-api/save-tracks-user/)

#### Arguments
* `$tracks` **string\|array** - ID(s) of the track(s) to add.


#### Return values
* **boolean** Whether the tracks was successfully added.



### addUserPlaylistTracks

    boolean SpotifyWebAPI\SpotifyWebAPI::addUserPlaylistTracks(string $userId, string $playlistId, string|array $tracks, array|object $options)

Add tracks to a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/add-tracks-to-playlist/](https://developer.spotify.com/web-api/add-tracks-to-playlist/)

#### Arguments
* `$userId` **string** - ID of the user who owns the playlist.
* `$playlistId` **string** - ID of the playlist to add tracks to.
* `$tracks` **string\|array** - ID(s) of the track(s) to add.
* `$options` **array\|object** - Optional. Options for the new tracks.
    * int position Optional. Zero-based track position in playlist. Tracks will be appened if omitted or false.



#### Return values
* **boolean** Whether the tracks was successfully added.



### createUserPlaylist

    array|object SpotifyWebAPI\SpotifyWebAPI::createUserPlaylist(string $userId, array|object $options)

Create a new playlist for a user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/create-playlist/](https://developer.spotify.com/web-api/create-playlist/)

#### Arguments
* `$userId` **string** - ID of the user to create the playlist for.
* `$options` **array\|object** - Options for the new playlist.
    * name string Required. Name of the playlist.
    * public bool Optional. Whether the playlist should be public or not.



#### Return values
* **array\|object** The new playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### currentUserFollows

    array SpotifyWebAPI\SpotifyWebAPI::currentUserFollows(string $type, string|array $ids)

Check to see if the current user is following one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-current-user-follows/](https://developer.spotify.com/web-api/check-current-user-follows/)

#### Arguments
* `$type` **string** - The type to check: either &#039;artist&#039; or &#039;user&#039;.
* `$ids` **string\|array** - ID(s) of the user(s) or artist(s) to check for.


#### Return values
* **array** Whether each user or artist is followed.



### deleteMyAlbums

    boolean SpotifyWebAPI\SpotifyWebAPI::deleteMyAlbums(string|array $albums)

Delete albums from current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-albums-user/](https://developer.spotify.com/web-api/remove-albums-user/)

#### Arguments
* `$albums` **string\|array** - ID(s) of the album(s) to delete.


#### Return values
* **boolean** Whether the albums was successfully deleted.



### deleteMyTracks

    boolean SpotifyWebAPI\SpotifyWebAPI::deleteMyTracks(string|array $tracks)

Delete tracks from current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-tracks-user/](https://developer.spotify.com/web-api/remove-tracks-user/)

#### Arguments
* `$tracks` **string\|array** - ID(s) of the track(s) to delete.


#### Return values
* **boolean** Whether the tracks was successfully deleted.



### deleteUserPlaylistTracks

    string|boolean SpotifyWebAPI\SpotifyWebAPI::deleteUserPlaylistTracks(string $userId, string $playlistId, array $tracks, string $snapshotId)

Delete tracks from a playlist and retrieve a new snapshot ID.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/remove-tracks-playlist/](https://developer.spotify.com/web-api/remove-tracks-playlist/)

#### Arguments
* `$userId` **string** - ID of the user who owns the playlist.
* `$playlistId` **string** - ID of the playlist to delete tracks from.
* `$tracks` **array** - Array of arrays or objects with tracks to delete.
    * id string Required. Spotify track ID.
    * positions int\|array Optional. The track&#039;s position(s) in the playlist.

* `$snapshotId` **string** - Optional. The playlist&#039;s snapshot ID.


#### Return values
* **string\|boolean** A new snapshot ID or false if the tracks weren&#039;t successfully deleted.



### followArtistsOrUsers

    boolean SpotifyWebAPI\SpotifyWebAPI::followArtistsOrUsers(string $type, string|array $ids)

Add the current user as a follower of one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/follow-artists-users/](https://developer.spotify.com/web-api/follow-artists-users/)

#### Arguments
* `$type` **string** - The type to check: either &#039;artist&#039; or &#039;user&#039;.
* `$ids` **string\|array** - ID(s) of the user(s) or artist(s) to follow.


#### Return values
* **boolean** Whether the artist or user was successfully followed.



### followPlaylist

    boolean SpotifyWebAPI\SpotifyWebAPI::followPlaylist(string $userId, string $playlistId, array|object $options)

Add the current user as a follower of a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/follow-playlist/](https://developer.spotify.com/web-api/follow-playlist/)

#### Arguments
* `$userId` **string** - ID of the user who owns the playlist.
* `$playlistId` **string** - ID of the playlist to follow.
* `$options` **array\|object** - Optional. Options for the followed playlist.
    * public bool Optional. Whether the playlist should be followed publicly or not.



#### Return values
* **boolean** Whether the playlist was successfully followed.



### getAlbum

    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbum(string $albumId)

Get a album.<br>
[https://developer.spotify.com/web-api/get-album/](https://developer.spotify.com/web-api/get-album/)

#### Arguments
* `$albumId` **string** - ID of the album.


#### Return values
* **array\|object** The requested album. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getAlbums

    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbums(array $albumIds, array|object $options)

Get multiple albums.<br>
[https://developer.spotify.com/web-api/get-several-albums/](https://developer.spotify.com/web-api/get-several-albums/)

#### Arguments
* `$albumIds` **array** - IDs of the albums.
* `$options` **array\|object** - Optional. Options for the albums.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The requested albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getAlbumTracks

    array|object SpotifyWebAPI\SpotifyWebAPI::getAlbumTracks(string $albumId, array|object $options)

Get a album's tracks.<br>
[https://developer.spotify.com/web-api/get-albums-tracks/](https://developer.spotify.com/web-api/get-albums-tracks/)

#### Arguments
* `$albumId` **string** - ID of the album.
* `$options` **array\|object** - Optional. Options for the tracks.
    * int limit Optional. Limit the number of tracks.
    * int offset Optional. Number of tracks to skip.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The requested album tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getArtist

    array|object SpotifyWebAPI\SpotifyWebAPI::getArtist(string $artistId)

Get an artist.<br>
[https://developer.spotify.com/web-api/get-artist/](https://developer.spotify.com/web-api/get-artist/)

#### Arguments
* `$artistId` **string** - ID of the artist.


#### Return values
* **array\|object** The requested artist. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getArtists

    array|object SpotifyWebAPI\SpotifyWebAPI::getArtists(array $artistIds)

Get multiple artists.<br>
[https://developer.spotify.com/web-api/get-several-artists/](https://developer.spotify.com/web-api/get-several-artists/)

#### Arguments
* `$artistIds` **array** - IDs of the artists.


#### Return values
* **array\|object** The requested artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getArtistRelatedArtists

    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistRelatedArtists(string $artistId)

Get an artist's related artists.<br>
[https://developer.spotify.com/web-api/get-related-artists/](https://developer.spotify.com/web-api/get-related-artists/)

#### Arguments
* `$artistId` **string** - ID of the artist.


#### Return values
* **array\|object** The artist&#039;s related artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getArtistAlbums

    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistAlbums(string $artistId, array|object $options)

Get an artist's albums.<br>
[https://developer.spotify.com/web-api/get-artists-albums/](https://developer.spotify.com/web-api/get-artists-albums/)

#### Arguments
* `$artistId` **string** - ID of the artist.
* `$options` **array\|object** - Optional. Options for the albums.
    * string\|array album_type Optional. Album type(s) to return. If omitted, all album types will be returned.
    * string market Optional. Limit the results to items that are playable in this market, for example SE.
    * int limit Optional. Limit the number of albums.
    * int offset Optional. Number of albums to skip.



#### Return values
* **array\|object** The artist&#039;s albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getArtistTopTracks

    array|object SpotifyWebAPI\SpotifyWebAPI::getArtistTopTracks(string $artistId, array|object $options)

Get an artist's top tracks in a country.<br>
[https://developer.spotify.com/web-api/get-artists-top-tracks/](https://developer.spotify.com/web-api/get-artists-top-tracks/)

#### Arguments
* `$artistId` **string** - ID of the artist.
* `$options` **array\|object** - Options for the tracks.
    * string $country Required. An ISO 3166-1 alpha-2 country code specifying the country to get the top tracks for.



#### Return values
* **array\|object** The artist&#039;s top tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getAudioFeatures

    array|object SpotifyWebAPI\SpotifyWebAPI::getAudioFeatures(array $trackIds)

Get track audio features.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-several-audio-features/](https://developer.spotify.com/web-api/get-several-audio-features/)

#### Arguments
* `$trackIds` **array** - IDs of the tracks.


#### Return values
* **array\|object** The tracks&#039; audio features. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getCategoriesList

    array|object SpotifyWebAPI\SpotifyWebAPI::getCategoriesList(array|object $options)

Get a list of categories used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-categories/](https://developer.spotify.com/web-api/get-list-categories/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the categories.
    * string locale Optional. Language to show categories in, for example sv_SE.
    * string country Optional. An ISO 3166-1 alpha-2 country code. Show categories from this country.
    * int limit Optional. Limit the number of categories.
    * int offset Optional. Number of categories to skip.



#### Return values
* **array\|object** The list of categories. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getCategory

    array|object SpotifyWebAPI\SpotifyWebAPI::getCategory(string $categoryId, array|object $options)

Get a single category used to tag items in Spotify (on, for example, the Spotify player’s "Browse" tab).<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-category/](https://developer.spotify.com/web-api/get-category/)

#### Arguments
* `$categoryId` **string** - The Spotify ID of the category.
* `$options` **array\|object** - Optional. Options for the category.
    * string locale Optional. Language to show category in, for example sv_SE.
    * string country Optional. An ISO 3166-1 alpha-2 country code. Show category from this country.



#### Return values
* **array\|object** The category. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getCategoryPlaylists

    array|object SpotifyWebAPI\SpotifyWebAPI::getCategoryPlaylists(string $categoryId, array|object $options)

Get a list of Spotify playlists tagged with a particular category.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-categorys-playlists/](https://developer.spotify.com/web-api/get-categorys-playlists/)

#### Arguments
* `$categoryId` **string** - The Spotify ID of the category.
* `$options` **array\|object** - Optional. Options for the category&#039;s playlists.
    * string country Optional. An ISO 3166-1 alpha-2 country code. Show category playlists from this country.
    * int limit Optional. Limit the number of playlists.
    * int offset Optional. Number of playlists to skip.



#### Return values
* **array\|object** The list of playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getFeaturedPlaylists

    array|object SpotifyWebAPI\SpotifyWebAPI::getFeaturedPlaylists(array|object $options)

Get Spotify featured playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-featured-playlists/](https://developer.spotify.com/web-api/get-list-featured-playlists/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the playlists.
    * string locale Optional. Language to show playlists in, for example sv_SE.
    * string country Optional. An ISO 3166-1 alpha-2 country code. Show playlists from this country.
    * string timestamp Optional. A ISO 8601 timestamp. Show playlists relevant to this date and time.
    * int limit Optional. Limit the number of playlists.
    * int offset Optional. Number of playlists to skip.



#### Return values
* **array\|object** The featured playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getGenreSeeds

    array|object SpotifyWebAPI\SpotifyWebAPI::getGenreSeeds()

Get a list of possible seed genres.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-recommendations/](https://developer.spotify.com/web-api/get-recommendations/)#available-genre-seeds


#### Return values
* **array\|object** All possible seed genres. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getLastResponse

    array SpotifyWebAPI\SpotifyWebAPI::getLastResponse()

Get the latest full response from the Spotify API.


#### Return values
* **array** Response data.
    * array\|object body The response body. Type is controlled by SpotifyWebAPI::setReturnAssoc().
    * array headers Response headers.
    * int status HTTP status code.
    * string url The requested URL.



### getNewReleases

    array|object SpotifyWebAPI\SpotifyWebAPI::getNewReleases(array|object $options)

Get new releases.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-new-releases/](https://developer.spotify.com/web-api/get-list-new-releases/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the items.
    * string country Optional. An ISO 3166-1 alpha-2 country code. Show items relevant to this country.
    * int limit Optional. Limit the number of items.
    * int offset Optional. Number of items to skip.



#### Return values
* **array\|object** The new releases. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getMyPlaylists

    array|object SpotifyWebAPI\SpotifyWebAPI::getMyPlaylists(array|object $options)

Get the current user’s playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-a-list-of-current-users-playlists/](https://developer.spotify.com/web-api/get-a-list-of-current-users-playlists/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the playlists.
    * int limit Optional. Limit the number of playlists.
    * int offset Optional. Number of playlists to skip.



#### Return values
* **array\|object** The user&#039;s playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getMySavedAlbums

    array|object SpotifyWebAPI\SpotifyWebAPI::getMySavedAlbums(array|object $options)

Get the current user’s saved albums.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-saved-albums/](https://developer.spotify.com/web-api/get-users-saved-albums/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the albums.
    * int limit Optional. Limit the number of albums.
    * int offset Optional. Number of albums to skip.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The user&#039;s saved albums. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getMySavedTracks

    array|object SpotifyWebAPI\SpotifyWebAPI::getMySavedTracks(array|object $options)

Get the current user’s saved tracks.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-saved-tracks/](https://developer.spotify.com/web-api/get-users-saved-tracks/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the tracks.
    * int limit Optional. Limit the number of tracks.
    * int offset Optional. Number of tracks to skip.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The user&#039;s saved tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getMyTop

    array|object SpotifyWebAPI\SpotifyWebAPI::getMyTop(string $type, $options)

Get the current user's top tracks or artists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-users-top-artists-and-tracks/](https://developer.spotify.com/web-api/get-users-top-artists-and-tracks/)

#### Arguments
* `$type` **string** - The type of entity to fetch.
* `$options` **mixed**


#### Return values
* **array\|object** A list with the requested top entity. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getRecommendations

    array|object SpotifyWebAPI\SpotifyWebAPI::getRecommendations(array|object $options)

Get recommendations based on artists, tracks, or genres.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-recommendations/](https://developer.spotify.com/web-api/get-recommendations/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the recommendations.
    * int limit Optional. Limit the number of recommendations.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.
    * mixed max_* Optional. Max value for one of the tunable track attributes.
    * mixed min_* Optional. Min value for one of the tunable track attributes.
    * array seed_artists Artist IDs to seed by.
    * array seed_genres Genres to seed by. Call SpotifyWebAPI::getGenreSeeds() for a complete list.
    * array seed_tracks Track IDs to seed by.
    * mixed target_* Optional. Target value for one of the tunable track attributes.



#### Return values
* **array\|object** The requested recommendations. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getReturnAssoc

    boolean SpotifyWebAPI\SpotifyWebAPI::getReturnAssoc()

Get the return type for the Request body element.


#### Return values
* **boolean** Whether an associative array or an stdClass is returned.



### getRequest

    \SpotifyWebAPI\Request SpotifyWebAPI\SpotifyWebAPI::getRequest()

Get the Request object in use.


#### Return values
* **\SpotifyWebAPI\Request** The Request object in use.



### getTrack

    array|object SpotifyWebAPI\SpotifyWebAPI::getTrack(string $trackId, array|object $options)

Get a track.<br>
[https://developer.spotify.com/web-api/get-track/](https://developer.spotify.com/web-api/get-track/)

#### Arguments
* `$trackId` **string** - ID of the track.
* `$options` **array\|object** - Optional. Options for the track.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The requested track. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getTracks

    array|object SpotifyWebAPI\SpotifyWebAPI::getTracks(array $trackIds, array|object $options)

Get multiple tracks.<br>
[https://developer.spotify.com/web-api/get-several-tracks/](https://developer.spotify.com/web-api/get-several-tracks/)

#### Arguments
* `$trackIds` **array** - IDs of the tracks.
* `$options` **array\|object** - Optional. Options for the albums.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The requested tracks. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getUser

    array|object SpotifyWebAPI\SpotifyWebAPI::getUser(string $userId)

Get a user.<br>
[https://developer.spotify.com/web-api/get-users-profile/](https://developer.spotify.com/web-api/get-users-profile/)

#### Arguments
* `$userId` **string** - ID of the user.


#### Return values
* **array\|object** The requested user. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getUserFollowedArtists

    array|object SpotifyWebAPI\SpotifyWebAPI::getUserFollowedArtists(array|object $options)

Get the artists followed by the current user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-followed-artists/](https://developer.spotify.com/web-api/get-followed-artists/)

#### Arguments
* `$options` **array\|object** - Optional. Options for the artists.
    * int limit Optional. Limit the number of artists returned.
    * string after Optional. The last artist ID retrieved from the previous request.



#### Return values
* **array\|object** A list of artists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getUserPlaylist

    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylist(string $userId, string $playlistId, array|object $options)

Get a user's specific playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-playlist/](https://developer.spotify.com/web-api/get-playlist/)

#### Arguments
* `$userId` **string** - ID of the user.
* `$playlistId` **string** - ID of the playlist.
* `$options` **array\|object** - Optional. Options for the playlist.
    * string\|array fields Optional. A list of fields to return. See Spotify docs for more info.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The user&#039;s playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getUserPlaylists

    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylists(string $userId, array|object $options)

Get a user's playlists.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-list-users-playlists/](https://developer.spotify.com/web-api/get-list-users-playlists/)

#### Arguments
* `$userId` **string** - ID of the user.
* `$options` **array\|object** - Optional. Options for the tracks.
    * int limit Optional. Limit the number of tracks.
    * int offset Optional. Number of tracks to skip.



#### Return values
* **array\|object** The user&#039;s playlists. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### getUserPlaylistTracks

    array|object SpotifyWebAPI\SpotifyWebAPI::getUserPlaylistTracks(string $userId, string $playlistId, array|object $options)

Get the tracks in a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-playlists-tracks/](https://developer.spotify.com/web-api/get-playlists-tracks/)

#### Arguments
* `$userId` **string** - ID of the user.
* `$playlistId` **string** - ID of the playlist.
* `$options` **array\|object** - Optional. Options for the tracks.
    * string\|array fields Optional. A list of fields to return. See Spotify docs for more info.
    * int limit Optional. Limit the number of tracks.
    * int offset Optional. Number of tracks to skip.
    * string market Optional. An ISO 3166-1 alpha-2 country code, provide this if you wish to apply Track Relinking.



#### Return values
* **array\|object** The tracks in the playlist. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### me

    array|object SpotifyWebAPI\SpotifyWebAPI::me()

Get the currently authenticated user.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/get-current-users-profile/](https://developer.spotify.com/web-api/get-current-users-profile/)


#### Return values
* **array\|object** The currently authenticated user. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### myAlbumsContains

    array SpotifyWebAPI\SpotifyWebAPI::myAlbumsContains(string|array $albums)

Check if albums are saved in the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-users-saved-albums/](https://developer.spotify.com/web-api/check-users-saved-albums/)

#### Arguments
* `$albums` **string\|array** - ID(s) of the album(s) to check for.


#### Return values
* **array** Whether each album is saved.



### myTracksContains

    array SpotifyWebAPI\SpotifyWebAPI::myTracksContains(string|array $tracks)

Check if tracks are saved in the current user's Spotify library.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-users-saved-tracks/](https://developer.spotify.com/web-api/check-users-saved-tracks/)

#### Arguments
* `$tracks` **string\|array** - ID(s) of the track(s) to check for.


#### Return values
* **array** Whether each track is saved.



### reorderUserPlaylistTracks

    string|boolean SpotifyWebAPI\SpotifyWebAPI::reorderUserPlaylistTracks(string $userId, string $playlistId, array|object $options)

Reorder the tracks in a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/reorder-playlists-tracks/](https://developer.spotify.com/web-api/reorder-playlists-tracks/)

#### Arguments
* `$userId` **string** - ID of the user.
* `$playlistId` **string** - ID of the playlist.
* `$options` **array\|object** - Options for the new tracks.
    * int range_start Required. Position of the first track to be reordered.
    * int range_length Optional. The amount of tracks to be reordered.
    * int insert_before Required. Position where the tracks should be inserted.
    * string snapshot_id Optional. The playlist&#039;s snapshot ID.



#### Return values
* **string\|boolean** A new snapshot ID or false if the tracks weren&#039;t successfully reordered.



### replaceUserPlaylistTracks

    boolean SpotifyWebAPI\SpotifyWebAPI::replaceUserPlaylistTracks(string $userId, string $playlistId, string|array $tracks)

Replace all tracks in a user's playlist with new ones.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/replace-playlists-tracks/](https://developer.spotify.com/web-api/replace-playlists-tracks/)

#### Arguments
* `$userId` **string** - ID of the user.
* `$playlistId` **string** - ID of the playlist.
* `$tracks` **string\|array** - ID(s) of the track(s) to add.


#### Return values
* **boolean** Whether the tracks was successfully replaced.



### search

    array|object SpotifyWebAPI\SpotifyWebAPI::search(string $query, string|array $type, array|object $options)

Search for an item.<br>
Requires a valid access token if market=from_token is used.<br>
[https://developer.spotify.com/web-api/search-item/](https://developer.spotify.com/web-api/search-item/)

#### Arguments
* `$query` **string** - The term to search for.
* `$type` **string\|array** - The type of item to search for.
* `$options` **array\|object** - Optional. Options for the search.
    * string market Optional. Limit the results to items that are playable in this market, for example SE.
    * int limit Optional. Limit the number of items.
    * int offset Optional. Number of items to skip.



#### Return values
* **array\|object** The search results. Type is controlled by SpotifyWebAPI::setReturnAssoc().



### setAccessToken

    void SpotifyWebAPI\SpotifyWebAPI::setAccessToken(string $accessToken)

Set the access token to use.

#### Arguments
* `$accessToken` **string** - The access token.


#### Return values
* **void** 



### setReturnAssoc

    void SpotifyWebAPI\SpotifyWebAPI::setReturnAssoc(boolean $returnAssoc)

Set the return type for the Request body element.

#### Arguments
* `$returnAssoc` **boolean** - Whether to return an associative array or an stdClass.


#### Return values
* **void** 



### unfollowArtistsOrUsers

    boolean SpotifyWebAPI\SpotifyWebAPI::unfollowArtistsOrUsers(string $type, string|array $ids)

Remove the current user as a follower of one or more artists or other Spotify users.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/unfollow-artists-users/](https://developer.spotify.com/web-api/unfollow-artists-users/)

#### Arguments
* `$type` **string** - The type to check: either &#039;artist&#039; or &#039;user&#039;.
* `$ids` **string\|array** - ID(s) of the user(s) or artist(s) to unfollow.


#### Return values
* **boolean** Whether the artist(s) or user(s) were successfully unfollowed.



### unfollowPlaylist

    boolean SpotifyWebAPI\SpotifyWebAPI::unfollowPlaylist(string $userId, string $playlistId)

Remove the current user as a follower of a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/unfollow-playlist/](https://developer.spotify.com/web-api/unfollow-playlist/)

#### Arguments
* `$userId` **string** - ID of the user who owns the playlist.
* `$playlistId` **string** - ID of the playlist to unfollow


#### Return values
* **boolean** Whether the playlist was successfully unfollowed.



### updateUserPlaylist

    boolean SpotifyWebAPI\SpotifyWebAPI::updateUserPlaylist(string $userId, string $playlistId, array|object $options)

Update the details of a user's playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/change-playlist-details/](https://developer.spotify.com/web-api/change-playlist-details/)

#### Arguments
* `$userId` **string** - ID of the user who owns the playlist.
* `$playlistId` **string** - ID of the playlist to update.
* `$options` **array\|object** - Options for the playlist.
    * name string Optional. Name of the playlist.
    * public bool Optional. Whether the playlist should be public or not.



#### Return values
* **boolean** Whether the playlist was successfully updated.



### userFollowsPlaylist

    array SpotifyWebAPI\SpotifyWebAPI::userFollowsPlaylist(string $ownerId, string $playlistId, array|object $options)

Check if a user is following a playlist.<br>
Requires a valid access token.<br>
[https://developer.spotify.com/web-api/check-user-following-playlist/](https://developer.spotify.com/web-api/check-user-following-playlist/)

#### Arguments
* `$ownerId` **string** - User ID of the playlist owner.
* `$playlistId` **string** - ID of the playlist.
* `$options` **array\|object** - Options for the check.
    * ids string\|array Required. ID(s) of the user(s) to check for.



#### Return values
* **array** Whether each user is following the playlist.


