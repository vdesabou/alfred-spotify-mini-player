<?php
/**
 * thirdDelimiterAdd function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function thirdDelimiterAdd($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $is_public_playlists = $settings->is_public_playlists;

    $tmp = explode('∙', $words[1]);
    $uri = $tmp[0];

    $track_name = '';
    $track_uri = '';
    $album_name = '';
    $album_uri = '';
    $playlist_name = '';
    $playlist_uri = '';

    $message = '';
    $type = '';

    $href = explode(':', $uri);
    if ($href[1] == 'track') {
        $type = 'track';
        $track_name = $tmp[1];
        $track_uri = $uri;
        $message = 'track ' . $track_name;
    }
    elseif ($href[1] == 'album') {
        $type = 'album';
        $album_name = $tmp[1];
        $album_uri = $uri;
        $message = 'album  ' . $album_name;
    }
    elseif ($href[1] == 'user' || $href[1] == 'playlist') {
        $type = 'playlist';
        $playlist_name = base64_decode($tmp[1]);
        $playlist_uri = $uri;
        $message = 'playlist ' . $playlist_name;
    }

    $the_query = $words[3];

    if ($update_in_progress == true) {
        $w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', array('Please retry when update is finished', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

        echo $w->tojson();

        return;
    }

    if (countCharacters($the_query) == 0) {
        $privacy_status = 'private';
        if ($is_public_playlists) {
            $privacy_status = 'public';
        }
        $w->result(null, '', 'Enter the name of the new playlist: ', array('This will create a new ' . $privacy_status . ' playlist with the name entered', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/create_playlist.png', 'no', null, '');

        if ($album_name != '' || $playlist_name != '') {
            $w->result(null, 'help', 'Or choose an alternative below', array('Some playlists names are proposed below', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }

        if ($album_name != '') {
            $w->result(null, serialize(array($track_uri
            /*track_uri*/, $album_uri
            /* album_uri */, ''
            /* artist_uri */, $playlist_uri
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . $album_name /* other_settings*/, ''
            /* other_action */,

            ''
            /* artist_name */, $track_name
            /* track_name */, $album_name
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, $playlist_name
            /* playlist_name */, '', /* playlist_artwork_path */
            )), "Create a playlist named '" . $album_name . "'", 'This will create a playlist ' . $album_name . ' with content of the album', './images/add.png', 'yes', null, '');
        }

        if ($playlist_name != '') {
            $w->result(null, serialize(array($track_uri
            /*track_uri*/, $album_uri
            /* album_uri */, ''
            /* artist_uri */, $playlist_uri
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . $playlist_name /* other_settings*/, ''
            /* other_action */,

            ''
            /* artist_name */, $track_name
            /* track_name */, $album_name
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, $playlist_name
            /* playlist_name */, '', /* playlist_artwork_path */
            )), "Create a copy of playlist named '" . $playlist_name . "'", 'This will copy the existing playlist ' . $playlist_name . ' to a new one', './images/add.png', 'yes', null, '');
        }
    }
    else {
        // playlist name has been set
        $w->result(null, serialize(array($track_uri
        /*track_uri*/, $album_uri
        /* album_uri */, ''
        /* artist_uri */, $playlist_uri
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, 'ADD_TO_PLAYLIST▹' . 'notset' . '▹' . $the_query /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, $track_name
        /* track_name */, $album_name
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, $playlist_name
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Create playlist ' . $the_query, 'This will create the playlist and add the ' . $message, './images/add.png', 'yes', null, '');
    }
}

/**
 * thirdDelimiterBrowse function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function thirdDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $use_artworks = $settings->use_artworks;

    $country = $words[1];
    $category = $words[2];
    $search = $words[3];

    try {
        $offsetCategoryPlaylists = 0;
        $limitCategoryPlaylists = 50;
        $api = getSpotifyWebAPI($w);
        do {
            $listPlaylists = $api->getCategoryPlaylists($category, array('country' => $country, 'limit' => $limitCategoryPlaylists, 'offset' => $offsetCategoryPlaylists,));

            $subtitle = 'Launch Playlist';
            $playlists = $listPlaylists->playlists;
            $items = $playlists->items;
            foreach ($items as $playlist) {
                if (countCharacters($search) < 2 || strpos(strtolower($playlist->name), strtolower($search)) !== false) {
                    $w->result(null, '', getenv('emoji_playlist') . escapeQuery($playlist->name), 'by ' . $playlist
                        ->owner->id . ' '.getenv('emoji_separator').' ' . $playlist
                        ->tracks->total . ' tracks', getPlaylistArtwork($w, $playlist->uri, false, false, $use_artworks), 'no', null, 'Online Playlist▹' . $playlist->uri . '∙' . base64_encode($playlist->name) . '▹');
                }
            }

            $offsetCategoryPlaylists += $limitCategoryPlaylists;
        } while ($offsetCategoryPlaylists < $listPlaylists
            ->playlists
            ->total);
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }
}

