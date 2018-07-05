# Managing a User's Playlists

There are lots of operations involving user's playlists that can be performed. Remember to request the correct [scopes](working-with-scopes.md) beforehand.

## Listing a user's playlists

```php
$playlists = $api->getUserPlaylists('USER_ID', [
    'limit' => 5
]);

foreach ($playlists->items as $playlist) {
    echo '<a href="' . $playlist->external_urls->spotify . '">' . $playlist->name . '</a> <br>';
}
```

## Getting info about a specific playlist

```php
$playlist = $api->getPlaylist('USER_ID', 'PLAYLIST_ID');

echo $playlist->name;
```

## Getting all tracks in a playlist

```php
$playlistTracks = $api->getPlaylistTracks('USER_ID', 'PLAYLIST_ID');

foreach ($playlistTracks->items as $track) {
    $track = $track->track;

    echo '<a href="' . $track->external_urls->spotify . '">' . $track->name . '</a> <br>';
}
```

## Creating a new playlist

```php
$api->createPlaylist('USER_ID', [
    'name' => 'My shiny playlist'
]);
```

## Updating the details of a user's playlist

```php
$api->updatePlaylist('USER_ID', 'PLAYLIST_ID', [
    'name' => 'New name'
]);
```

## Updating the image of a user's playlist
```php
$imageData = base64_encode(file_get_contents('image.jpg'));

$api->updatePlaylistImage('USER_ID', 'PLAYLIST_ID', $imageData);
```

## Adding tracks to a user's playlist

```php
$api->addPlaylistTracks('USER_ID', 'PLAYLIST_ID', [
    'TRACK_ID',
    'TRACK_ID'
]);
```

## Delete tracks from a user's playlist based on IDs

```php
$tracks = [
    'tracks' => [
        ['id' => 'TRACK_ID'],
        ['id' => 'TRACK_ID'],
    ],
];

$api->deletePlaylistTracks('USER_ID', 'PLAYLIST_ID', $tracks, 'SNAPSHOT_ID');
```

## Delete tracks from a user's playlist based on positions

```php
$trackPositions = [
    5,
    12,
];

$api->deletePlaylistTracks('USER_ID', 'PLAYLIST_ID', $trackPositions, 'SNAPSHOT_ID');
```

## Replacing all tracks in a user's playlist with new ones

```php
$api->replacePlaylistTracks('USER_ID', 'PLAYLIST_ID', [
    'TRACK_ID',
    'TRACK_ID'
]);
```

## Reorder the tracks in a user's playlist

```php
$api->reorderPlaylistTracks('USER_ID', 'PLAYLIST_ID', [
    'range_start' => 1,
    'range_length' => 5,
    'insert_before' => 10,
    'snapshot_id' => 'SNAPSHOT_ID'
]);
```

Please see the [method reference](/docs/method-reference/SpotifyWebAPI.md) for more available options for each method.
