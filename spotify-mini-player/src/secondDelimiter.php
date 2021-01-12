<?php
/**
 * secondDelimiterShows function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterShows($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $use_artworks = $settings->use_artworks;
    $fuzzy_search = $settings->fuzzy_search;

    // display episodes for selected show
    $tmp = explode('∙', $words[1]);
    $show_uri = $tmp[0];
    $show_name = $tmp[1];
    $episode = $words[2];

    if (countCharacters($episode) < 2) {
        $show_artwork_path = getShowArtwork($w, $show_uri, false, false, false, $use_artworks);
        // $w->result(null, serialize(array(
        //             $show_uri /*track_uri*/,
        //             '' /* album_uri */,
        //             '' /* artist_uri */,
        //             '' /* playlist_uri */,
        //             '' /* spotify_command */,
        //             '' /* query */,
        //             '' /* other_settings*/,
        //             'playshow' /* other_action */,
        //             '' /* artist_name */,
        //             $show_name /* track_name */,
        //             '' /* album_name */,
        //             '' /* track_artwork_path */,
        //             $show_artwork_path /* artist_artwork_path */,
        //             '' /* album_artwork_path */,
        //             '' /* playlist_name */,
        //             '', /* playlist_artwork_path */
        //         )), getenv('emoji_show').' '.$show_name, 'Play show', $show_artwork_path, 'yes', null, '');


        $w->result(null, '', 'Follow/Unfollow Show', array('Display options to follow/unfollow the show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/follow.png', 'no', null, 'Follow/Unfollow▹' . $show_uri . '@' . $show_name . '▹');

        if($update_in_progress && file_exists($w->data() . '/create_library')) {
            $results = getExternalResults($w, 'episodes', array('uri', 'name', 'uri', 'show_uri', 'show_name', 'description', 'episode_artwork_path', 'is_playable', 'languages', 'nb_times_played', 'is_externally_hosted', 'duration_ms', 'explicit', 'release_date', 'release_date_precision', 'audio_preview_url', 'fully_played', 'resume_position_ms'), 'order by release_date desc limit ' . $max_results, "where show_uri=\"".$show_uri."\"");
        } else {
            $getEpisodes = 'select uri, name, uri, show_uri, show_name, description, episode_artwork_path, is_playable, languages, nb_times_played, is_externally_hosted, duration_ms, explicit, release_date, release_date_precision, audio_preview_url, fully_played, resume_position_ms from episodes where show_uri=:show_uri order by release_date desc limit ' . $max_results;
            $stmt = $db->prepare($getEpisodes);
            $stmt->bindValue(':show_uri', $show_uri);
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    }
    else {
        if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
            $results = getFuzzySearchResults($w, $update_in_progress, $episode, 'episodes', array('uri', 'name', 'uri', 'show_uri', 'show_name', 'description', 'episode_artwork_path', 'is_playable', 'languages', 'nb_times_played', 'is_externally_hosted', 'duration_ms', 'explicit', 'release_date', 'release_date_precision', 'audio_preview_url', 'fully_played', 'resume_position_ms'), $max_results, '2,4', "where show_uri=\"".$show_uri."\"");
        } else {
            $getEpisodes = 'select uri, name, uri, show_uri, show_name, description, episode_artwork_path, is_playable, languages, nb_times_played, is_externally_hosted, duration_ms, explicit, release_date, release_date_precision, audio_preview_url, fully_played, resume_position_ms from episodes where show_uri=:show_uri and name_deburr like :name order by release_date desc limit ' . $max_results;
            $stmt = $db->prepare($getEpisodes);
            $stmt->bindValue(':show_uri', $show_uri);
            $stmt->bindValue(':name', '%' . deburr($episode) . '%');
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    }

    $noresult = true;
    foreach ($results as $episodes) {
        $noresult = false;
        $subtitle = $episodes[6];

        $fully_played = '';
        if ($episodes[16] == 1) {
            // fully_played
            $fully_played = '✔️';
        }
        if (checkIfResultAlreadyThere($w->results(), $fully_played . $episodes[1]) == false) {
            if ($episodes[7] == true) {
                $w->result(null, serialize(array($episodes[2] /*track_uri*/, $episodes[3] /* album_uri */, $episodes[4] /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'play_episode'
                /* other_action */, $episodes[7] /* artist_name */, $episodes[5] /* track_name */, $episodes[6] /* album_name */, $episodes[9] /* track_artwork_path */, $episodes[10] /* artist_artwork_path */, $episodes[11] /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $fully_played . $episodes[1], array('Progress: ' . floatToCircles(intval($episodes[17]) / intval($episodes[11])) . ' Duration ' . beautifyTime($episodes[11] / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episodes[13] . ' '.getenv('emoji_separator').' Languages: ' . $episodes[8], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $episodes[6], 'yes', null, '');
            }
            else {
                $w->result(null, '', getenv('emoji_not_playable').' ' . $fully_played . $episodes[1], 'Progress: ' . floatToCircles(intval($episodes[17]) / intval($episodes[11])) . ' Duration ' . beautifyTime($episodes[11] / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episodes[13] . ' '.getenv('emoji_separator').' Languages: ' . $episodes[8], $episodes[6], 'no', null, '');
            }
        }
    }

    if ($noresult) {
        if (countCharacters($episode) < 2) {
            $w->result(null, 'help', 'There is no episode in your library for the show ' . escapeQuery($show_name), array('Choose one of the options above', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        else {
            $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }

    if ($output_application != 'MOPIDY') {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, base64_encode('show:' . $show_name) /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, ''
        /* other_action */,

        ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Search for show ' . $show_name . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
    }
}

/**
 * secondDelimiterArtists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterArtists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $radio_number_tracks = $settings->radio_number_tracks;
    $max_results = $settings->max_results;
    $country_code = $settings->country_code;
    $is_public_playlists = $settings->is_public_playlists;
    $output_application = $settings->output_application;
    $use_artworks = $settings->use_artworks;
    $fuzzy_search = $settings->fuzzy_search;

    // display tracks for selected artists
    $tmp = explode('∙', $words[1]);
    $artist_uri = $tmp[0];
    $artist_name = $tmp[1];
    $track = $words[2];

    $href = explode(':', $artist_uri);
    if ($href[1] == 'track') {
        $track_uri = $artist_uri;
        $artist_uri = getArtistUriFromTrack($w, $track_uri);
        if ($artist_uri == false) {
            $w->result(null, 'help', 'The artist cannot be retrieved from track uri', array('URI was ' . $tmp[0], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();

            exit;
        }
    }
    if ($href[1] == 'local') {
        $artist_uri = getArtistUriFromSearch($w, $href[2], $country_code);
        if ($artist_uri == false) {
            $w->result(null, 'help', 'The artist cannot be retrieved from local track uri', array('URI was ' . $tmp[0], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();

            exit;
        }
    }
    if (countCharacters($track) < 2) {
        $artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false, false, false, $use_artworks);
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, $artist_uri
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'playartist'
        /* other_action */, $artist_name
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, $artist_artwork_path
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), getenv('emoji_artist').' ' . $artist_name, 'Play artist', $artist_artwork_path, 'yes', null, '');
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, $artist_uri
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'lookup_artist'
        /* other_action */, $artist_name
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), getenv('emoji_artist').' ' . $artist_name, getenv('emoji_online').'  all albums/tracks from this artist online..', './images/online_artist.png', 'yes', null, '');

        $w->result(null, '', 'Follow/Unfollow Artist', array('Display options to follow/unfollow the artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/follow.png', 'no', null, 'Follow/Unfollow▹' . $artist_uri . '@' . $artist_name . '▹');

        $w->result(null, '', 'Related Artists', array('Browse related artists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/related.png', 'no', null, 'OnlineRelated▹' . $artist_uri . '@' . $artist_name . '▹');

        if ($update_in_progress == false) {
            $privacy_status = 'private';
            if ($is_public_playlists) {
                $privacy_status = 'public';
            }
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, $artist_uri
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'radio_artist'
            /* other_action */, $artist_name
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Create a Radio Playlist for ' . $artist_name, 'This will create a ' . $privacy_status . ' radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
        }

        if ($update_in_progress == false) {
            $privacy_status = 'private';
            if ($is_public_playlists) {
                $privacy_status = 'public';
            }
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, $artist_uri
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'complete_collection_artist'
            /* other_action */, $artist_name
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Create a Complete Collection Playlist for ' . $artist_name, 'This will create a ' . $privacy_status . ' playlist for the artist with all the albums and singles', './images/complete_collection.png', 'yes', null, '');
        }

        if($update_in_progress && file_exists($w->data() . '/create_library')) {
            $results = getExternalResults($w, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), 'limit ' . $max_results,"where artist_uri=\"".$artist_uri."\"");
        } else {
            $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where artist_uri=:artist_uri limit ' . $max_results;

            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':artist_uri', $artist_uri);
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    }
    else {
        if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
            $results = getFuzzySearchResults($w, $update_in_progress, $track, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6', "where artist_uri=\"".$artist_uri."\"");

        } else {
            $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where artist_uri=:artist_uri and track_name_deburr like :track limit ' . $max_results;

            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':artist_uri', $artist_uri);
            $stmt->bindValue(':track', '%' . deburr($track) . '%');
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    }
    $noresult = true;
    foreach ($results as $track) {
        $noresult = false;
        $subtitle = $track[6];

        $added = '';
        if ($track[18] == true) {
            if ($output_application == 'MOPIDY') {
                // skip local tracks if using Mopidy
                continue;
            }
            $added = getenv('emoji_local_track').' ';
        }
        if (checkIfResultAlreadyThere($w->results(), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
            if ($track[14] == true) {
                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, ''
                /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], array($track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', null, '');
            }
            else {
                $w->result(null, '', getenv('emoji_not_playable').' ' . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
            }
        }
    }

    if ($noresult) {
        if (countCharacters($track) < 2) {
            $w->result(null, 'help', 'There is no track in your library for the artist ' . escapeQuery($artist_name), array('Choose one of the options above', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        else {
            $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }

    if ($output_application != 'MOPIDY') {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, base64_encode('artist:' . $artist_name) /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, ''
        /* other_action */,

        ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Search for artist ' . $artist_name . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
    }
}

/**
 * secondDelimiterAlbums function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterAlbums($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $all_playlists = $settings->all_playlists;
    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $use_artworks = $settings->use_artworks;
    $fuzzy_search = $settings->fuzzy_search;

    // display tracks for selected album
    $tmp = explode('∙', $words[1]);
    $album_uri = $tmp[0];
    $album_name = $tmp[1];
    $track = $words[2];

    $href = explode(':', $album_uri);
    if ($href[1] == 'track' || $href[1] == 'local') {
        $track_uri = $album_uri;
        $album_uri = getAlbumUriFromTrack($w, $track_uri);
        if ($album_uri == false) {
            $w->result(null, 'help', 'The album cannot be retrieved from track uri', array('URI was ' . $tmp[0], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();

            exit;
        }
    }

    try {
        if (countCharacters($track) < 2) {

            if($update_in_progress && file_exists($w->data() . '/create_library')) {

                if ($all_playlists == false) {
                    $where_clause = 'where yourmusic=1 and album_uri="'.$album_uri.'"';
                }
                else {
                    $where_clause = 'where album_uri="'.$album_uri.'"';
                }
                $results = getExternalResults($w, 'tracks', array('yourmusic_album', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), '', $where_clause);
            } else {
                if ($all_playlists == false) {
                    $getTracks = 'select yourmusic_album, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic_album=1 and album_uri=:album_uri limit ' . $max_results;
                }
                else {
                    $getTracks = 'select yourmusic_album, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where album_uri=:album_uri limit ' . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':album_uri', $album_uri);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $where_clause = 'where yourmusic=1 and album_uri="'.$album_uri.'"';
                }
                else {
                    $where_clause = 'where album_uri="'.$album_uri.'"';
                }
                $results = getFuzzySearchResults($w, $update_in_progress, $track, 'tracks', array('yourmusic_album', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6..8', $where_clause);
            } else {
                if ($all_playlists == false) {
                    $getTracks = 'select yourmusic_album, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic_album=1 and (album_uri=:album_uri and track_name_deburr like :track limit ' . $max_results;
                }
                else {
                    $getTracks = 'select yourmusic_album, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where album_uri=:album_uri and track_name_deburr like :track limit ' . $max_results;
                }

                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':album_uri', $album_uri);
                $stmt->bindValue(':track', '%' . deburr($track) . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    $album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false, false, false, $use_artworks);
    $w->result(null, serialize(array(''
    /*track_uri*/, $album_uri
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, ''
    /* other_settings*/, 'playalbum'
    /* other_action */,

    ''
    /* artist_name */, ''
    /* track_name */, $album_name
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, $album_artwork_path
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), getenv('emoji_album').' ' . $album_name, 'Play album', $album_artwork_path, 'yes', null, '');

    try {
        $getArtist = 'select artist_uri,artist_name from tracks where album_uri=:album_uri limit 1';
        $stmtGetArtist = $db->prepare($getArtist);
        $stmtGetArtist->bindValue(':album_uri', $album_uri);
        $stmtGetArtist->execute();
        $onetrack = $stmtGetArtist->fetch();
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    $w->result(null, '', getenv('emoji_album').' ' . $album_name, array(getenv('emoji_online').'  all tracks from this album online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_album.png', 'no', null, 'Online▹' . $onetrack[0] . '@' . $onetrack[1] . '@' . $album_uri . '@' . $album_name . '▹');

    if ($update_in_progress == false) {
        $w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', array('This will add the album to Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
    }
    $noresult = true;
    foreach ($results as $track) {
        $noresult = false;
        $subtitle = $track[6];

        $added = '';
        if ($track[18] == true) {
            if ($output_application == 'MOPIDY') {
                // skip local tracks if using Mopidy
                continue;
            }
            $added = getenv('emoji_local_track').' ';
        }
        if (checkIfResultAlreadyThere($w->results(), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
            if ($track[14] == true) {
                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'play_track_in_album_context'
                /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], array($track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', null, '');
            }
            else {
                $w->result(null, '', getenv('emoji_not_playable').' ' . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
            }
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }

    if ($output_application != 'MOPIDY') {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, base64_encode('album:' . $album_name) /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Search for album ' . $album_name . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
    }
}

/**
 * secondDelimiterPlaylists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterPlaylists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $alfred_playlist_uri = $settings->alfred_playlist_uri;
    $userid = $settings->userid;
    $fuzzy_search = $settings->fuzzy_search;
    $output_application = $settings->output_application;

    $theplaylisturi = $words[1];
    $thetrack = $words[2];

    try {
        // display tracks for selected playlist
        if($update_in_progress && file_exists($w->data() . '/create_library')) {
            $results_playlist = getExternalResults($w, 'playlists', array('uri','name','nb_tracks','author','username','playlist_artwork_path','ownedbyuser','nb_playable_tracks','duration_playlist','collaborative','public'), '', "where uri=\"".$theplaylisturi."\"");
        } else {
            $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where uri=:uri';
            $stmt = $db->prepare($getPlaylists);
            $stmt->bindValue(':uri', $theplaylisturi);
            $stmt->execute();
            $results_playlist = $stmt->fetchAll();
        }

        $noresultplaylist = true;
        foreach ($results_playlist as $playlist) {
            $noresultplaylist = false;
            if (countCharacters($thetrack) < 2) {
                if ($playlist[9]) {
                    $public_status = 'collaborative';
                }
                else {
                    if ($playlist[10]) {
                        $public_status = 'public';
                    }
                    else {
                        $public_status = 'private';
                    }
                }
                if ($playlist[10]) {
                    $public_status_contrary = 'private';
                }
                else {
                    $public_status_contrary = 'public';
                }
                $subtitle = 'Launch Playlist';
                $subtitle = $subtitle . ' ,⇧ ▹ add playlist to ..., ⌘ ▹ change playlist privacy to ' . $public_status_contrary;
                $added = ' ';
                if ($userid == $playlist[4] && $public_status != 'collaborative') {
                    $cmdMsg = 'Change playlist privacy to ' . $public_status_contrary;
                }
                else {
                    $cmdMsg = '';
                }
                if (startswith($playlist[1], 'Artist radio for')) {
                    $added = getenv('emoji_radio').' ';
                }
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, $playlist[0] /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'set_playlist_privacy_to_' . $public_status_contrary /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, $playlist[1] /* playlist_name */, $playlist[5], /* playlist_artwork_path */
                )), getenv('emoji_playlist') . $added . $playlist[1] . ' by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8], array($subtitle, 'alt' => '', 'cmd' => $cmdMsg, 'shift' => 'Add playlist ' . $playlist[1] . ' to ...', 'fn' => '', 'ctrl' => '',), $playlist[5], 'yes', null, '');
                if ($output_application != 'MOPIDY') {
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, base64_encode('activate (open location "' . $playlist[0] . '")') /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, ''
                    /* other_action */,

                    ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Open playlist ' . escapeQuery($playlist[1]) . ' in Spotify', 'This will open the playlist in Spotify', './images/spotify.png', 'yes', null, '');
                }

                if ($update_in_progress == false) {
                    $w->result(null, '', 'Add playlist ' . escapeQuery($playlist[1]) . ' to...', array('This will add the playlist to Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, 'Add▹' . $playlist[0] . '∙' . base64_encode($playlist[1]) . '▹');
                }

                if ($update_in_progress == false) {
                    $w->result(null, '', 'Remove playlist ' . escapeQuery($playlist[1]), array('A confirmation will be asked in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/uncheck.png', 'no', null, 'Confirm Remove Playlist▹' . $playlist[0] . '∙' . base64_encode($playlist[1]) . '▹');
                }
                if ($update_in_progress == false) {
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, $theplaylisturi
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'create_similar_playlist'
                    /* other_action */,

                    ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, escapeQuery($playlist[1]) /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Create a similar playlist for ' . escapeQuery($playlist[1]), 'This will create a similar playlist', './images/playlists.png', 'yes', null, '');
                }
                if($update_in_progress && file_exists($w->data() . '/create_library')) {
                    $results = getExternalResults($w, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), 'order by added_at desc limit ' . $max_results, "where playlist_uri=\"".$theplaylisturi."\"");
                } else {
                    $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where playlist_uri=:theplaylisturi order by added_at desc limit ' . $max_results;
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':theplaylisturi', $theplaylisturi);
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
            }
            else {
                if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                    $results = getFuzzySearchResults($w, $update_in_progress, $thetrack, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6..8', "where playlist_uri=\"".$theplaylisturi."\"");
                } else {
                    $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where playlist_uri=:theplaylisturi and (artist_name_deburr like :track or album_name_deburr like :track or track_name_deburr like :track)' . ' order by added_at desc limit ' . $max_results;
                    $stmt = $db->prepare($getTracks);
                    $stmt->bindValue(':theplaylisturi', $theplaylisturi);
                    $stmt->bindValue(':track', '%' . deburr($thetrack) . '%');
                    $stmt->execute();
                    $results = $stmt->fetchAll();
                }
            }

            if ($theplaylisturi == $alfred_playlist_uri) {
                if ($update_in_progress == false) {
                    $w->result(null, '', getenv('emoji_alfred') . 'Change your Alfred playlist', array('Select one of your playlists as your Alfred playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
                }
            }

            $noresult = true;
            foreach ($results as $track) {
                $noresult = false;
                $subtitle = $track[6];
                $added = '';
                if ($track[18] == true) {
                    if ($output_application == 'MOPIDY') {
                        // skip local tracks if using Mopidy
                        continue;
                    }
                    $added = getenv('emoji_local_track').' ';
                }
                if (checkIfResultAlreadyThere($w->results(), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
                    if ($track[14] == true) {
                        $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, $theplaylisturi
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, ''
                        /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, $playlist[1] /* playlist_name */, '', /* playlist_artwork_path */
                        )), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], array($track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', null, '');
                    }
                    else {
                        $w->result(null, '', getenv('emoji_not_playable').' ' . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
                    }
                }
            }

            if ($noresult) {
                $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }

        if ($theplaylisturi == $alfred_playlist_uri) {
            if ($update_in_progress == false) {
                $w->result(null, '', getenv('emoji_alfred') . 'Clear your Alfred Playlist', array('This will remove all the tracks in your current Alfred Playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/uncheck.png', 'no', null, 'Alfred Playlist▹Confirm Clear Alfred Playlist▹');
            }
        }

        // can happen only with Alfred Playlist deleted
        if ($noresultplaylist) {
            $w->result(null, 'help', 'It seems your Alfred Playlist was deleted', array('Choose option below to change it', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            $w->result(null, '', getenv('emoji_alfred') . 'Change your Alfred playlist', array('Select one of your playlists below as your Alfred playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Alfred Playlist▹Set Alfred Playlist▹');
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }
}

/**
 * secondDelimiterOnline function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterOnline($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[2];

    $radio_number_tracks = $settings->radio_number_tracks;
    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;

    if (substr_count($query, '@') == 1) {

        $tmp = $words[1];
        $words = explode('@', $tmp);
        $uri = $words[0];
        $tmp_uri = explode(':', $uri);

        if ($tmp_uri[1] == 'artist') {

            // Search Artist Online
            $artist_uri = $uri;

            $artist_name = $words[1];

            $artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false, false, false, $use_artworks);
            if (countCharacters($search) < 2) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, $artist_uri
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'playartist'
                /* other_action */, $artist_name
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, $artist_artwork_path
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), getenv('emoji_artist').' ' . escapeQuery($artist_name), 'Play artist', $artist_artwork_path, 'yes', null, '');
            }

            if (countCharacters($search) < 2) {
                $w->result(null, '', 'Follow/Unfollow Artist', array('Display options to follow/unfollow the artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/follow.png', 'no', null, 'Follow/Unfollow▹' . $artist_uri . '@' . $artist_name . '▹');

                $w->result(null, '', 'Related Artists', array('Browse related artists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/related.png', 'no', null, 'OnlineRelated▹' . $artist_uri . '@' . $artist_name . '▹');
            }

            if ($update_in_progress == false) {
                if (countCharacters($search) < 2) {
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, $artist_uri
                    /* artist_uris */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'radio_artist'
                    /* other_action */, $artist_name
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Create a Radio Playlist for ' . $artist_name, 'This will create a radio playlist with ' . $radio_number_tracks . ' tracks for the artist', './images/radio_artist.png', 'yes', null, '');
                }
            }

            // call to web api, if it fails,
            // it displays an error in main window
            $albums = getTheArtistAlbums($w, $artist_uri, $country_code);

            if (countCharacters($search) < 2) {
                $w->result(null, 'help', 'Select an album below to browse it', array('singles and compilations are also displayed', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
            }

            $noresult = true;
            foreach ($albums as $album) {
                if (checkIfResultAlreadyThere($w->results(), $album->name . ' (' . count($album
                    ->tracks
                    ->items) . ' tracks)') == false) {
                    $noresult = false;
                    $genre = (count($album->genres) > 0) ? ' '.getenv('emoji_separator').' Genre: ' . implode('|', $album->genres) : '';
                    $tracks = $album->tracks;
                    if (countCharacters($search) < 2 || strpos(strtolower($artist_name), strtolower($search)) !== false || strpos(strtolower($album->name), strtolower($search)) !== false) {
                        $w->result(null, '', $album->name . ' (' . count($album
                            ->tracks
                            ->items) . ' tracks)', $album->album_type . ' by ' . $artist_name . ' '.getenv('emoji_separator').' Release date: ' . $album->release_date . $genre, getTrackOrAlbumArtwork($w, $album->uri, false, false, false, $use_artworks), 'no', null, 'Online▹' . $artist_uri . '@' . $artist_name . '@' . $album->uri . '@' . $album->name . '▹');
                    }
                }
            }

            if ($noresult) {
                $w->result(null, 'help', 'There is no album for this artist', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
        else if ($tmp_uri[1] == 'show') {
            // Search Show Online
            $show_uri = $uri;

            $show_name = $words[1];

            $show_artwork_path = getShowArtwork($w, $show_uri, false, false, false, $use_artworks);
            if (countCharacters($search) < 2) {
                // $w->result(null, serialize(array(
                //         '' /*track_uri*/,
                //         '' /* album_uri */,
                //         $show_uri /* artist_uri */,
                //         '' /* playlist_uri */,
                //         '' /* spotify_command */,
                //         '' /* query */,
                //         '' /* other_settings*/,
                //         'playshow' /* other_action */,
                //         $show_name /* show_name */,
                //         '' /* track_name */,
                //         '' /* album_name */,
                //         '' /* track_artwork_path */,
                //         $show_artwork_path /* show_artwork_path */,
                //         '' /* album_artwork_path */,
                //         '' /* playlist_name */,
                //         '', /* playlist_artwork_path */
                //     )), getenv('emoji_show').' '.escapeQuery($show_name), 'Play show', $show_artwork_path, 'yes', null, '');

            }

            if (countCharacters($search) < 2) {
                $w->result(null, '', 'Follow/Unfollow Show', array('Display options to follow/unfollow the show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/follow.png', 'no', null, 'Follow/Unfollow▹' . $show_uri . '@' . $show_name . '▹');
            }

            // call to web api, if it fails,
            // it displays an error in main window
            $episodes = getTheShowEpisodes($w, $show_uri, $country_code);

            if (countCharacters($search) < 2) {
                $w->result(null, 'help', 'Select an episode below to play it', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
            }

            $iso = new Matriphe\ISO639\ISO639;

            $noresult = true;
            foreach ($episodes as $episode) {
                if (checkIfResultAlreadyThere($w->results(), $episode->name) == false) {
                    $noresult = false;
                    if (countCharacters($search) < 2 || strpos(strtolower($episode->name), strtolower($search)) !== false) {

                        $array_languages = array();
                        foreach ($episode->languages as $language) {
                            if (strpos($language, '-') !== false) {
                                $language = strstr($language, '-', true);
                            }
                            $array_languages[] = $iso->languageByCode1($language);
                        }
                        $w->result(null, serialize(array($episode->uri
                        /*track_uri*/, $show_uri
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'play_episode'
                        /* other_action */, ''
                        /* artist_name */, escapeQuery($episode->name) /* track_name */, escapeQuery($show_name) /* album_name */, $show_artwork_path
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $episode->name, array($episode->episode_type . 'Progress: ' . floatToCircles(intval($episode
                            ->resume_point
                            ->resume_position_ms) / intval($episode->duration_ms)) . ' Duration ' . beautifyTime($episode->duration_ms / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episode->release_date . ' '.getenv('emoji_separator').' Languages: ' . implode(',', $array_languages), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $show_artwork_path, 'yes', null, '');
                    }
                }
            }

            if ($noresult) {
                $w->result(null, 'help', 'There is no episode for this show', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    }
    elseif (substr_count($query, '@') == 3) {

        // Search Album Online
        $tmp = $words[1];
        $words = explode('@', $tmp);
        $artist_uri = $words[0];
        $artist_name = $words[1];
        $album_uri = $words[2];
        $album_name = $words[3];

        $href = explode(':', $album_uri);
        if ($href[1] == 'track' || $href[1] == 'local') {
            $track_uri = $album_uri;
            $album_uri = getAlbumUriFromTrack($w, $track_uri);
            if ($album_uri == false) {
                $w->result(null, 'help', 'The album cannot be retrieved from track uri', array('URI was ' . $track_uri, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                echo $w->tojson();
                exit;
            }
        }
        $href = explode(':', $artist_uri);
        if ($href[1] == 'track') {
            $track_uri = $artist_uri;
            $artist_uri = getArtistUriFromTrack($w, $track_uri);
            if ($artist_uri == false) {
                $w->result(null, 'help', 'The artist cannot be retrieved from track uri', array('URI was ' . $track_uri, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                echo $w->tojson();
                exit;
            }
        }
        $album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false, false, false, $use_artworks);
        if (countCharacters($search) < 2) {
            $w->result(null, serialize(array(''
            /*track_uri*/, $album_uri
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'playalbum'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, $album_name
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, $album_artwork_path
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), getenv('emoji_album').' ' . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');
        }

        if ($update_in_progress == false) {
            if (countCharacters($search) < 2) {
                $w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', array('This will add the album to Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
            }
        }

        // call to web api, if it fails,
        // it displays an error in main window
        $tracks = getTheAlbumFullTracks($w, $album_uri);
        $noresult = true;
        foreach ($tracks as $track) {
            $artists = $track->artists;
            $artist = $artists[0];

            $track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
            if (isset($track->is_playable) && $track->is_playable) {
                $noresult = false;
                if (countCharacters($search) < 2 || strpos(strtolower($artist->name), strtolower($search)) !== false || strpos(strtolower($track->name), strtolower($search)) !== false) {
                    $w->result(null, serialize(array($track->uri
                    /*track_uri*/, $album_uri
                    /* album_uri */, $artist_uri
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'play_track_in_album_context'
                    /* other_action */, $artist->name
                    /* artist_name */, $track->name
                    /* track_name */, $album_name
                    /* album_name */, $track_artwork
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . $album_name, 'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online',), $track_artwork, 'yes', null, '');
                }
            }
            else {
                if (countCharacters($search) < 2 || strpos(strtolower($artist->name), strtolower($search)) !== false || strpos(strtolower($track->name), strtolower($search)) !== false) {
                    $w->result(null, '', getenv('emoji_not_playable').' ' . escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . $album_name, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track_artwork, 'no', null, '');
                }
            }
        }
    }
}

/**
 * secondDelimiterOnlineRelated function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterOnlineRelated($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[2];

    $use_artworks = $settings->use_artworks;

    if (substr_count($query, '@') == 1) {

        // Search Related Artist Online
        $tmp = $words[1];
        $words = explode('@', $tmp);
        $artist_uri = $words[0];
        $artist_name = $words[1];

        // call to web api, if it fails,
        // it displays an error in main window
        $relateds = getTheArtistRelatedArtists($w, trim($artist_uri));

        foreach ($relateds as $related) {
            if (countCharacters($search) < 2 || strpos(strtolower($related->name), strtolower($search)) !== false) {
                $w->result(null, '', getenv('emoji_artist').' ' . $related->name, getenv('emoji_online').'  all albums/tracks from this artist online..', getArtistArtwork($w, $related->uri, $related->name, false, false, false, $use_artworks), 'no', null, 'Online▹' . $related->uri . '@' . $related->name . '▹');
            }
        }
    }
}

/**
 * secondDelimiterOnlinePlaylist function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterOnlinePlaylist($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $is_alfred_playlist_active = $settings->is_alfred_playlist_active;
    $max_results = $settings->max_results;
    $alfred_playlist_name = $settings->alfred_playlist_name;
    $country_code = $settings->country_code;
    $is_public_playlists = $settings->is_public_playlists;
    $output_application = $settings->output_application;
    $use_artworks = $settings->use_artworks;

    // display tracks for selected online playlist
    $tmp = explode('∙', $words[1]);
    $theplaylisturi = $tmp[0];
    $url = explode(':', $theplaylisturi);
    if (isset($url[4])) {
        $playlist_id = $url[4];
    }
    else {
        $playlist_id = $url[2];
    }

    // playlist name is encoded in base64
    $theplaylistname = base64_decode($tmp[1]);
    $search = $words[2];
    $savedPlaylistTracks = array();
    $duration_playlist = 0;
    $nb_tracks = 0;
    try {
        $api = getSpotifyWebAPI($w);
        $offsetGetUserPlaylistTracks = 0;
        $limitGetUserPlaylistTracks = 100;
        do {
            $userPlaylistTracks = $api->getPlaylistTracks($playlist_id, array('fields' => array('total', 'items(added_at)', 'items(is_local)', 'items.track(is_playable,duration_ms,uri,popularity,name)', 'items.track.album(album_type,images,uri,name)', 'items.track.artists(name,uri)',), 'limit' => $limitGetUserPlaylistTracks, 'offset' => $offsetGetUserPlaylistTracks, 'market' => $country_code,));

            foreach ($userPlaylistTracks->items as $item) {
                $track = $item->track;
                $savedPlaylistTracks[] = $item;
                $nb_tracks += 1;
                $duration_playlist += $track->duration_ms;
            }
            $offsetGetUserPlaylistTracks += $limitGetUserPlaylistTracks;
        } while ($offsetGetUserPlaylistTracks < $userPlaylistTracks->total);
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }

    $subtitle = 'Launch Playlist';
    if ($is_alfred_playlist_active == true) {
        $subtitle = "$subtitle ,⇧ ▹ add playlist to ...";
    }
    $playlist_artwork_path = getPlaylistArtwork($w, $theplaylisturi, false, false, $use_artworks);
    if (countCharacters($search) < 2) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, $theplaylisturi
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, ''
        /* other_action */,

        ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, $theplaylistname
        /* playlist_name */, $playlist_artwork_path
        /* playlist_artwork_path */, $alfred_playlist_name,
        /* alfred_playlist_name */
        )), getenv('emoji_playlist') . $theplaylistname . ' '.getenv('emoji_separator').' ' . $nb_tracks . ' tracks '.getenv('emoji_separator').' ' . beautifyTime($duration_playlist / 1000, true), array($subtitle, 'alt' => '', 'cmd' => '', 'shift' => 'Add playlist ' . $theplaylistname . ' to your Alfred Playlist', 'fn' => '', 'ctrl' => '',), $playlist_artwork_path, 'yes', null, '');
    }

    if ($output_application != 'MOPIDY') {
        if (countCharacters($search) < 2) {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, base64_encode('activate (open location "' . $theplaylisturi . '")') /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, ''
            /* other_action */,

            ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Open playlist ' . $theplaylistname . ' in Spotify', 'This will open the playlist in Spotify', './images/spotify.png', 'yes', null, '');
        }
    }
    if ($update_in_progress == false) {
        $added = 'privately';
        $privacy_status = 'private';
        if ($is_public_playlists) {
            $added = 'publicly';
            $privacy_status = 'public';
        }
        if (countCharacters($search) < 2) {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, $theplaylisturi
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'follow_playlist'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, $theplaylistname
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Follow ' . $added . ' playlist ' . $theplaylistname, 'This will add the playlist (marked as ' . $privacy_status . ') to your library', './images/follow.png', 'yes', null, '');
        }
    }

    $noresult = true;
    $nb_results = 0;
    foreach ($savedPlaylistTracks as $item) {
        if ($nb_results > $max_results) {
            break;
        }
        $track = $item->track;
        $noresult = false;
        $artists = $track->artists;
        $artist = $artists[0];
        $album = $track->album;

        $track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
        if (isset($track->is_playable) && $track->is_playable) {

            if (countCharacters($search) < 2 || strpos(strtolower($artist->name), strtolower($search)) !== false || strpos(strtolower($track->name), strtolower($search)) !== false || strpos(strtolower($album->name), strtolower($search)) !== false) {
                $w->result(null, serialize(array($track->uri
                /*track_uri*/, $album->uri
                /* album_uri */, $artist->uri
                /* artist_uri */, $theplaylisturi
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, ''
                /* other_action */, escapeQuery($artist->name) /* artist_name */, escapeQuery($track->name) /* track_name */, escapeQuery($album->name) /* album_name */, $track_artwork_path
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . escapeQuery($album->name), 'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online',), $track_artwork_path, 'yes', null, '');
                ++$nb_results;
            }
        }
        else {
            $added = '';
            if (isset($item->is_local) && $item->is_local) {
                $added = getenv('emoji_local_track').' ';
            }
            else {
                $added = getenv('emoji_not_playable').' ';
            }
            if (countCharacters($search) < 2 || strpos(strtolower($artist->name), strtolower($search)) !== false || strpos(strtolower($track->name), strtolower($search)) !== false || strpos(strtolower($album->name), strtolower($search)) !== false) {
                $w->result(null, '', $added . escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . escapeQuery($album->name), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track_artwork_path, 'no', null, '');
                ++$nb_results;
            }
        }
    }
}

/**
 * secondDelimiterYourMusicTracks function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterYourMusicTracks($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $thetrack = $words[2];

    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $fuzzy_search = $settings->fuzzy_search;

    // display tracks for Your Music
    $search = $words[2];

    if (countCharacters($thetrack) < 2) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'play_liked_songs'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, '' /* playlist_name */, '', /* playlist_artwork_path */
        )), '♥️ Play your Liked Songs', 'This will play your liked songs', './images/star.png', 'yes', null, '');

        if($update_in_progress && file_exists($w->data() . '/create_library')) {
            $results = getExternalResults($w, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), 'order by added_at desc limit ' . $max_results, 'where yourmusic=1');
        } else {
            $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 order by added_at desc limit ' . $max_results;
            $stmt = $db->prepare($getTracks);
            $stmt->execute();
            $results = $stmt->fetchAll();
        }
    }
    else {

        if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
            $results = getFuzzySearchResults($w, $update_in_progress, $thetrack, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6..8', 'where yourmusic=1');
        } else {
            // Search tracks
            $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name_deburr like :query or album_name_deburr like :query or track_name_deburr like :query)' . ' limit ' . $max_results;
            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':query', '%' . deburr($thetrack) . '%');
            try {
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
            catch(PDOException $e) {
                handleDbIssuePdoXml($e);

                return;
            }
        }
    }

    $noresult = true;
    foreach ($results as $track) {
        $noresult = false;
        $subtitle = $track[6];

        $added = '';
        if ($track[18] == true) {
            if ($output_application == 'MOPIDY') {
                // skip local tracks if using Mopidy
                continue;
            }
            $added = getenv('emoji_local_track').' ';
        }
        if (checkIfResultAlreadyThere($w->results(), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
            if ($track[14] == true) {
                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, ''
                /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $added . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], array($track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', null, '');
            }
            else {
                $w->result(null, '', getenv('emoji_not_playable').' ' . $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), $track[9], 'no', null, '');
            }
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }

    if (countCharacters($thetrack) > 0) {
        if ($output_application != 'MOPIDY') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, base64_encode($thetrack) /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Search for ' . $thetrack . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
        }

        $w->result(null, null, 'Search for ' . $thetrack . ' online', array('This will search online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online.png', 'no', null, 'Search Online▹' . $thetrack);
    }
}

/**
 * secondDelimiterYourMusicAlbums function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterYourMusicAlbums($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $fuzzy_search = $settings->fuzzy_search;

    $max_results = $settings->max_results;

    // Search albums
    $album = $words[2];
    try {
        if (countCharacters($album) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                $results = getExternalResults($w, 'tracks', array('album_name','album_artwork_path','artist_name','album_uri','album_type'), 'group by album_name order by max(added_at) desc limit ' . $max_results,'where yourmusic_album=1');
            } else {
                $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic_album=1 group by album_name order by max(added_at) desc limit ' . $max_results;
                $stmt = $db->prepare($getTracks);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $album, 'tracks', array('album_name','album_artwork_path','artist_name','album_uri','album_type'), $max_results, '1,3', 'where yourmusic_album=1');
            } else {
                // Search albums
                $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic=1 and album_name != "" and (album_name_deburr like :album_name or artist_name_deburr like :album_name) group by album_name order by max(added_at) desc limit ' . $max_results;
                $stmt = $db->prepare($getTracks);
                $stmt->bindValue(':album_name', '%' . deburr($album) . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    // display all albums
    $noresult = true;
    foreach ($results as $track) {
        $noresult = false;
        $nb_album_tracks = getNumberOfTracksForAlbum($update_in_progress, $w, $db, $track[3], true);
        if (checkIfResultAlreadyThere($w->results(), $track[0] . ' (' . $nb_album_tracks . ' tracks)') == false) {
            $w->result(null, '', $track[0] . ' (' . $nb_album_tracks . ' tracks)', array($track[4] . ' by ' . $track[2], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track[1], 'no', null, 'Album▹' . $track[3] . '∙' . $track[0] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }
}

/**
 * secondDelimiterYourTopArtists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterYourTopArtists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $time_range = $words[2];

    $max_results = $settings->max_results;
    $use_artworks = $settings->use_artworks;

    try {
        $api = getSpotifyWebAPI($w);
        $topArtists = $api->getMyTop('artists', array('time_range' => $time_range, 'limit' => ($max_results <= 50) ? $max_results : 50,));

        $items = $topArtists->items;
        $noresult = true;
        foreach ($items as $artist) {
            $noresult = false;
            $w->result(null, '', getenv('emoji_artist').' ' . $artist->name, 'Browse this artist', getArtistArtwork($w, $artist->uri, $artist->name, false, false, false, $use_artworks), 'no', null, 'Artist▹' . $artist->uri . '∙' . $artist->name . '▹');
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no result for your top artists', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }
}

/**
 * secondDelimiterYourTopTracks function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterYourTopTracks($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $time_range = $words[2];

    $max_results = $settings->max_results;
    $use_artworks = $settings->use_artworks;

    try {
        $api = getSpotifyWebAPI($w);
        $topTracks = $api->getMyTop('tracks', array('time_range' => $time_range, 'limit' => ($max_results <= 50) ? $max_results : 50,));

        $noresult = true;

        $items = $topTracks->items;

        foreach ($items as $track) {
            $noresult = false;
            $artists = $track->artists;
            $artist = $artists[0];
            $album = $track->album;

            $track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
            $w->result(null, serialize(array($track->uri
            /*track_uri*/, $album->uri
            /* album_uri */, $artist->uri
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, ''
            /* other_action */, escapeQuery($artist->name) /* artist_name */, escapeQuery($track->name) /* track_name */, escapeQuery($album->name) /* album_name */, $track_artwork_path
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . escapeQuery($album->name), 'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online',), $track_artwork_path, 'yes', null, '');
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no result for your top tracks', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }
}

/**
 * secondDelimiterYourMusicArtists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterYourMusicArtists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $fuzzy_search = $settings->fuzzy_search;

    // Search artists
    $artist = $words[2];

    try {
        if (countCharacters($artist) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                $results = getExternalResults($w, 'followed_artists', array('name','artist_artwork_path','uri'), 'group by name' . ' limit ' . $max_results);
            } else {
                $getArtists = 'select name,artist_artwork_path,uri from followed_artists group by name' . ' limit ' . $max_results;
                $stmt = $db->prepare($getArtists);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $artist, 'followed_artists', array('name','artist_artwork_path','uri'), $max_results, '1', '');
            } else {
                // Search artists
                $getArtists = 'select name,artist_artwork_path,uri from followed_artists where name_deburr like :query limit ' . $max_results;
                $stmt = $db->prepare($getArtists);
                $stmt->bindValue(':query', '%' . deburr($artist) . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    // display all artists
    $noresult = true;
    foreach ($results as $artists) {
        $noresult = false;
        $nb_artist_tracks = getNumberOfTracksForArtist($update_in_progress, $w, $db, $artists[0], false);
        if (checkIfResultAlreadyThere($w->results(), getenv('emoji_artist').' ' . $artists[0] . ' (' . $nb_artist_tracks . ' tracks)') == false) {
            $uri = $artists[2];

            $w->result(null, '', getenv('emoji_artist').' ' . $artists[0] . ' (' . $nb_artist_tracks . ' tracks)', array('Browse this artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artists[1], 'no', null, 'Artist▹' . $uri . '∙' . $artists[0] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }
}

/**
 * secondDelimiterSettings function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterSettings($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $output_application = $settings->output_application;

    $setting_kind = $words[1];
    $the_query = $words[2];

    if ($setting_kind == 'MaxResults') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the Max Results number (must be greater than 0):', array('Recommendation is between 10 to 100', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            // max results has been set
            if (is_numeric($the_query) == true && $the_query > 0) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'MAX_RESULTS▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Max Results will be set to <' . $the_query . '>', 'Type enter to validate the Max Results', './images/settings.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The Max Results value entered is not valid', array('Please fix it', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    } elseif ($setting_kind == 'SetVolume') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the Volume number (must be between 0 and 100 %):', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/volume_up.png', 'no', null, '');
        }
        else {
            // max results has been set
            if (is_numeric($the_query) == true && $the_query >= 0 && $the_query <= 100) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'SET_VOLUME▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Volume will be set to <' . $the_query . '>', 'Type enter to validate the volume', './images/volume_up.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The volume value entered is not valid', array('Please fix it', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    } elseif ($setting_kind == 'AutomaticRefreshLibrary') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the frequency in minutes for automatic refresh of your library (0 to disable):', array('Set to 0 to disable', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            // interval has been set
            if (is_numeric($the_query) == true) {
                if($the_query == 0) {
                    $text = 'Automatic refresh of your library will be disabled';
                } else {
                    $text = 'Automatic refresh of your library will be done every <' . $the_query . ' minutes>';
                }
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'AUTOMATICREFRESHLIBRARY▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $text, 'Type enter to validate', './images/settings.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The interval in minutes entered is not valid', array('Please fix it', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    }
    elseif ($setting_kind == 'Users') {

        $user_id = getCurrentUser($w);
        $w->result(null, '', 'Current user is ' . $user_id . '', array('Select one of the options below', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');

        $users_folder = $w->data() . '/users/';

        $users = scandir($users_folder);

        // loop on users
        foreach ($users as $user) {
            if ($user == '.' || $user == '..' || $user == $user_id || $user == '.DS_Store') {
                continue;
            }
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'SWITCH_USER▹' . $user /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Switch user to ' . $user . '', 'Type enter to validate', getUserArtwork($w, $user), 'yes', null, '');
        }

        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, 'SWITCH_USER▹NEW_USER'
        /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Add a new user', 'Type enter to validate', './images/artists.png', 'yes', null, '');

    }
    elseif ($setting_kind == 'RadioTracks') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the number of tracks to get when creating a radio Playlist:', array('Must be between 1 and 100', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            // number radio tracks has been set
            if (is_numeric($the_query) == true && $the_query > 0 && $the_query <= 100) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'RADIO_TRACKS▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, ''
                /* playlist_artwork_path */, '', /* $alfred_playlist_name */
                )), 'Number of Radio Tracks will be set to <' . $the_query . '>', 'Type enter to validate the Radio Tracks number', './images/settings.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The number of tracks value entered is not valid', array('Please fix it: it must be a number between 1 and 100', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    }
    elseif ($setting_kind == 'VolumePercentage') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the percentage of volume:', array('Must be between 1 and 50', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            // volume percent
            if (is_numeric($the_query) == true && $the_query > 0 && $the_query <= 50) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'VOLUME_PERCENT▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, ''
                /* playlist_artwork_path */, '', /* $alfred_playlist_name */
                )), 'Volume Percentage will be set to <' . $the_query . '>', 'Type enter to validate the Volume Percentage number', './images/settings.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The number of volume percentage entered is not valid', array('Please fix it: it must be a number between 1 and 50', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    }
    elseif ($setting_kind == 'Output') {
        if ($output_application != 'APPLESCRIPT') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'enable_applescript'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Use Spotify Desktop', array('You will use Spotify Desktop application with AppleScript', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
        }

        if (isUserPremiumSubscriber($w)) {
            // only propose if user is premimum
            if ($output_application != 'CONNECT') {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'enable_connect'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Use Spotify Connect', array('You will use Spotify Connect to control your devices', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'yes', null, '');
            }

            if ($output_application != 'MOPIDY') {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'enable_mopidy'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Use Mopidy (This is deprecated, use at your own risk !)', array('You will use Mopidy', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_mopidy.png', 'yes', null, '');
            }
        }
        else {
            $w->result(null, 'help', 'Only premium users can use Spotify Connect', array('This is a Spotify limitation', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }

    }
    elseif ($setting_kind == 'MopidyServer') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the server name or IP where Mopidy server is running:', array('Example: 192.168.0.5 or myserver.mydomain.mydomainextension', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'MOPIDY_SERVER▹' . $the_query /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, ''
            /* playlist_artwork_path */, '', /* $alfred_playlist_name */
            )), 'Mopidy server will be set to <' . $the_query . '>', 'Type enter to validate the Mopidy server name or IP', './images/settings.png', 'yes', null, '');
        }
    }
    elseif ($setting_kind == 'MopidyPort') {
        if (countCharacters($the_query) == 0) {
            $w->result(null, '', 'Enter the TCP port number where Mopidy server is running:', array('Must be a numeric value', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');
        }
        else {
            // tcp port has been set
            if (is_numeric($the_query) == true) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'MOPIDY_PORT▹' . $the_query /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, ''
                /* playlist_artwork_path */, '', /* $alfred_playlist_name */
                )), 'Mopidy TCP port will be set to <' . $the_query . '>', 'Type enter to validate the Mopidy TCP port number', './images/settings.png', 'yes', null, '');
            }
            else {
                $w->result(null, '', 'The TCP port value entered is not valid', array('Please fix it: it must be a numeric value', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
    }
}

/**
 * secondDelimiterFeaturedPlaylist function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterFeaturedPlaylist($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;

    $country = $words[1];
    $search = $words[2];

    if ($country == 'Choose a Country') {

        $spotify_country_codes = getSpotifyCountryCodesList();

        foreach ($spotify_country_codes as $spotify_country_code) {
            if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {

                if (countCharacters($search) < 1 || strpos(strtolower($spotify_country_code), strtolower($search)) !== false || strpos(strtolower(getCountryName(strtoupper($spotify_country_code))), strtolower($search)) !== false) {

                    $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), array('Browse the current featured playlists in ' . getCountryName(strtoupper($spotify_country_code)), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹' . strtoupper($spotify_country_code) . '▹');
                }
            }
        }
    }
    else {
        try {
            $api = getSpotifyWebAPI($w);
            $featuredPlaylists = $api->getFeaturedPlaylists(array('country' => $country, 'limit' => ($max_results <= 50) ? $max_results : 50, 'offset' => 0,));

            $subtitle = 'Launch Playlist';
            $playlists = $featuredPlaylists->playlists;
            $w->result(null, '', $featuredPlaylists->message, array('' . $playlists->total . ' playlists available', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
            $items = $playlists->items;
            foreach ($items as $playlist) {
                $w->result(null, '', getenv('emoji_playlist') . escapeQuery($playlist->name), 'by ' . $playlist
                    ->owner->id . ' '.getenv('emoji_separator').' ' . $playlist
                    ->tracks->total . ' tracks', getPlaylistArtwork($w, $playlist->uri, false, false, $use_artworks), 'no', null, 'Online Playlist▹' . $playlist->uri . '∙' . base64_encode($playlist->name) . '▹');
            }
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }
    }
}

/**
 * secondDelimiterNewReleases function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterNewReleases($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;

    $country = $words[1];
    $search = $words[2];

    if ($country == 'Choose a Country') {

        $spotify_country_codes = getSpotifyCountryCodesList();

        foreach ($spotify_country_codes as $spotify_country_code) {
            if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {

                if (countCharacters($search) < 1 || strpos(strtolower($spotify_country_code), strtolower($search)) !== false || strpos(strtolower(getCountryName(strtoupper($spotify_country_code))), strtolower($search)) !== false) {
                    $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), array('Browse the new album releases in ' . getCountryName(strtoupper($spotify_country_code)), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹' . strtoupper($spotify_country_code) . '▹');
                }
            }
        }
    }
    else {
        if (substr_count($query, '@') == 0) {

            // Get New Releases Online
            // call to web api, if it fails,
            // it displays an error in main window
            $albums = getTheNewReleases($w, $country, $max_results);

            if (countCharacters($search) < 2) {
                $w->result(null, 'help', 'Select an album below to browse it', array('singles and compilations are also displayed', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
            }

            $noresult = true;
            foreach ($albums as $album) {
                if (checkIfResultAlreadyThere($w->results(), $album->name . ' (' . count($album
                    ->tracks
                    ->items) . ' tracks)') == false) {
                    $noresult = false;
                    $genre = (count($album->genres) > 0) ? ' '.getenv('emoji_separator').' Genre: ' . implode('|', $album->genres) : '';
                    $tracks = $album->tracks;

                    if (countCharacters($search) < 2 || strpos(strtolower($album->name), strtolower($search)) !== false || strpos(strtolower($album->artists[0]
                        ->name), strtolower($search)) !== false) {
                        $w->result(null, '', $album->name . ' (' . count($album
                            ->tracks
                            ->items) . ' tracks)', array($album->album_type . ' by ' . $album->artists[0]->name . ' '.getenv('emoji_separator').' Release date: ' . $album->release_date . $genre, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), getTrackOrAlbumArtwork($w, $album->uri, false, false, false, $use_artworks), 'no', null, 'New Releases▹' . $country . '▹' . $album->uri . '@' . $album->name . getenv('emoji_separator'));
                    }
                }
            }

            if ($noresult) {
                $w->result(null, 'help', 'There is no album for this artist', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
        elseif (substr_count($query, '@') == 1) {

            // Search Album Online
            $tmp = $words[2];
            $tmp2 = explode(getenv('emoji_separator'), $tmp);
            $data = $tmp2[0];
            $search = $tmp2[1];
            $words = explode('@', $data);
            $album_uri = $words[0];
            $album_name = $words[1];

            $album_artwork_path = getTrackOrAlbumArtwork($w, $album_uri, false, false, false, $use_artworks);
            if (countCharacters($search) < 2) {
                $w->result(null, serialize(array(''
                /*track_uri*/, $album_uri
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'playalbum'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, $album_name
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, $album_artwork_path
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), getenv('emoji_album').' ' . escapeQuery($album_name), 'Play album', $album_artwork_path, 'yes', null, '');
            }

            if ($update_in_progress == false) {
                if (countCharacters($search) < 2) {
                    $w->result(null, '', 'Add album ' . escapeQuery($album_name) . ' to...', array('This will add the album to Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, 'Add▹' . $album_uri . '∙' . escapeQuery($album_name) . '▹');
                }
            }

            // call to web api, if it fails,
            // it displays an error in main window
            $tracks = getTheAlbumFullTracks($w, $album_uri);

            foreach ($tracks as $track) {

                if (countCharacters($search) < 2 || strpos(strtolower($track->name), strtolower($search)) !== false || strpos(strtolower($track->artists[0]
                    ->name), strtolower($search)) !== false) {

                    $track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
                    $w->result(null, serialize(array($track->uri
                    /*track_uri*/, $album_uri
                    /* album_uri */, $track->artists[0]->uri /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'play_track_in_album_context'
                    /* other_action */, $track->artists[0]->name /* artist_name */, $track->name
                    /* track_name */, $album_name
                    /* album_name */, $track_artwork_path
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), escapeQuery($track->artists[0]
                        ->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . $album_name, 'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($track->artists[0]
                        ->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($track->artists[0]
                        ->name) . ' online',), $track_artwork_path, 'yes', null, '');
                }
            }
        }
    }
}

/**
 * secondDelimiterAdd function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterAdd($w, $query, $settings, $db, $update_in_progress) {

    $words = explode('▹', $query);

    $is_alfred_playlist_active = $settings->is_alfred_playlist_active;
    $alfred_playlist_uri = $settings->alfred_playlist_uri;
    $alfred_playlist_name = $settings->alfred_playlist_name;
    $is_public_playlists = $settings->is_public_playlists;
    $fuzzy_search = $settings->fuzzy_search;
    $max_results = $settings->max_results;

    if ($update_in_progress == true) {
        $w->result(null, '', 'Cannot add tracks/albums/playlists while update is in progress', array('Please retry when update is finished', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

        echo $w->tojson();

        return;
    }

    $tmp = explode('∙', $words[1]);
    $uri = $tmp[0];

    $track_name = '';
    $track_uri = '';
    $album_name = '';
    $album_uri = '';
    $playlist_name = '';
    $playlist_uri = '';

    $href = explode(':', $uri);
    $message = '';
    $type = '';
    $value = '';
    if ($href[1] == 'track') {
        $type = 'track';
        $track_name = $tmp[1];
        $track_uri = $uri;
        $message = 'track ' . $track_name;
        $value = $track_name;
    }
    elseif ($href[1] == 'album') {
        $type = 'album';
        $album_name = $tmp[1];
        $album_uri = $uri;
        $message = 'album  ' . $album_name;
        $value = $album_name;
    }
    elseif ($href[1] == 'user' || $href[1] == 'playlist') {
        $type = 'playlist';
        $playlist_name = base64_decode($tmp[1]);
        $playlist_uri = $uri;
        $message = 'playlist ' . $playlist_name;
        $value = $playlist_name;
    }
    elseif ($href[1] == 'local') {
        $w->result(null, '', 'Cannot add local track to playlist using the Web API', array('This is a limitation of Spotify Web API', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();

        return;
    }
    $theplaylist = $words[2];

    try {
        if (countCharacters($theplaylist) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                $results = getExternalResults($w, 'playlists', array('uri','name','nb_tracks','author','username','playlist_artwork_path','ownedbyuser','nb_playable_tracks','duration_playlist','collaborative','public','nb_times_played'), '', 'where ownedbyuser=1');
            } else {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1';
                $stmt = $db->prepare($getPlaylists);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }

            $w->result(null, '', 'Add ' . $type . ' ' . $value . ' to Your Music or one of your playlists below..', array('Select Your Music or one of your playlists below to add the ' . $message, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, '');

            $privacy_status = 'private';
            if ($is_public_playlists) {
                $privacy_status = 'public';
            }
            $w->result(null, '', 'Create a new playlist ', array('Create a new ' . $privacy_status . ' playlist and add the ' . $message, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/create_playlist.png', 'no', null, $query . 'Enter Playlist Name▹');

            // put Alfred Playlist at beginning
            if ($is_alfred_playlist_active == true) {
                if ($alfred_playlist_uri != '' && $alfred_playlist_name != '') {
                    $w->result(null, serialize(array($track_uri
                    /*track_uri*/, $album_uri
                    /* album_uri */, ''
                    /* artist_uri */, $playlist_uri
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, 'ADD_TO_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/, ''
                    /* other_action */,

                    ''
                    /* artist_name */, $track_name
                    /* track_name */, $album_name
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, $playlist_name
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), getenv('emoji_alfred') . ' Alfred Playlist ' . ' '.getenv('emoji_separator').' ' . $alfred_playlist_name, 'Select the playlist to add the ' . $message, './images/alfred_playlist.png', 'yes', null, '');
                }
            }

            $w->result(null, serialize(array($track_uri
            /*track_uri*/, $album_uri
            /* album_uri */, ''
            /* artist_uri */, $playlist_uri
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'ADD_TO_YOUR_MUSIC▹'
            /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, $track_name
            /* track_name */, $album_name
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, $playlist_name
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Your Music', 'Select to add the ' . $message . ' to Your Music', './images/yourmusic.png', 'yes', null, '');
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $theplaylist, 'playlists', array('uri','name','nb_tracks','author','username','playlist_artwork_path','ownedbyuser','nb_playable_tracks','duration_playlist','collaborative','public','nb_times_played'), $max_results, '2,4', '');
            } else {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public,nb_times_played from playlists where (name_deburr like :query or author like :query) order by nb_times_played desc';
                $stmt = $db->prepare($getPlaylists);
                $stmt->bindValue(':query', '%' . deburr($theplaylist) . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    foreach ($results as $playlist) {
        if ($playlist[0] != $alfred_playlist_uri) {
            $added = ' ';
            if (startswith($playlist[1], 'Artist radio for')) {
                $added = getenv('emoji_radio').' ';
            }
            $w->result(null, serialize(array($track_uri
            /*track_uri*/, $album_uri
            /* album_uri */, ''
            /* artist_uri */, $playlist_uri
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'ADD_TO_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, $track_name
            /* track_name */, $album_name
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, $playlist_name
            /* playlist_name */, '', /* playlist_artwork_path */
            )), getenv('emoji_playlist') . $added . $playlist[1], $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' Select the playlist to add the ' . $message, $playlist[5], 'yes', null, '');
        }
    }
}

/**
 * secondDelimiterRemove function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterRemove($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    if ($update_in_progress == true) {
        $w->result(null, '', 'Cannot remove tracks while update is in progress', array('Please retry when update is finished', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

        echo $w->tojson();

        return;
    }

    $tmp = explode('∙', $words[1]);
    $uri = $tmp[0];
    $href = explode(':', $uri);
    // it is necessarly a track:
    $type = 'track';
    $track_name = $tmp[1];
    $track_uri = $uri;
    $message = 'track ' . $track_name;
    $theplaylist = $words[2];

    if ($href[1] == 'local') {
        $w->result(null, '', 'Cannot remove local tracks from playlists using the Web API', array('This is a limitation of Spotify Web API', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();

        return;
    }

    $noresult = true;
    $getPlaylistsForTrack = 'select distinct playlist_uri from tracks where uri=:uri';
    try {
        $stmt = $db->prepare($getPlaylistsForTrack);
        $stmt->bindValue(':uri', '' . $track_uri . '');
        $stmt->execute();
        $results = $stmt->fetchAll();

        foreach ($results as $playlistsForTrack) {
            if ($playlistsForTrack[0] == '') {
                if ($noresult == true) {
                    $w->result(null, '', 'Remove ' . $type . ' ' . $tmp[1] . ' from Your Music or one of your playlists below..', array('Select Your Music or one of your playlists below to remove the ' . $message, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, '');
                }
                // Your Music
                $w->result(null, serialize(array($track_uri
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'REMOVE_FROM_YOUR_MUSIC▹'
                /* other_settings*/, ''
                /* other_action */, ''
                /* artist_name */, $track_name
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Your Music', 'Select to remove the ' . $message . ' from Your Music', './images/yourmusic.png', 'yes', null, '');
                $noresult = false;
            }
            else {
                if (countCharacters($theplaylist) < 2) {
                    $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and uri=:playlist_uri';
                    $stmtGetPlaylists = $db->prepare($getPlaylists);
                    $stmtGetPlaylists->bindValue(':playlist_uri', $playlistsForTrack[0]);
                }
                else {
                    $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name_deburr like :playlist or author like :playlist) and uri=:playlist_uri';
                    $stmtGetPlaylists = $db->prepare($getPlaylists);
                    $stmtGetPlaylists->bindValue(':playlist_uri', $playlistsForTrack[0]);
                    $stmtGetPlaylists->bindValue(':playlist', '%' . deburr($theplaylist) . '%');
                }

                $playlists = $stmtGetPlaylists->execute();

                while ($playlist = $stmtGetPlaylists->fetch()) {
                    if ($noresult == true) {
                        $w->result(null, '', 'Remove ' . $type . ' ' . $tmp[1] . ' from Your Music or one of your playlists below..', array('Select Your Music or one of your playlists below to remove the ' . $message, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, '');
                    }
                    $added = ' ';
                    if (startswith($playlist[1], 'Artist radio for')) {
                        $added = getenv('emoji_radio').' ';
                    }
                    $w->result(null, serialize(array($track_uri
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, 'REMOVE_FROM_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/, ''
                    /* other_action */, ''
                    /* artist_name */, $track_name
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), getenv('emoji_playlist') . $added . $playlist[1], $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' Select the playlist to remove the ' . $message, $playlist[5], 'yes', null, '');
                    $noresult = false;
                }
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    if ($noresult) {
        $w->result(null, 'help', 'The current track is not in Your Music or one of your playlists', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }
}

/**
 * secondDelimiterAlfredPlaylist function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterAlfredPlaylist($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $alfred_playlist_uri = $settings->alfred_playlist_uri;
    $alfred_playlist_name = $settings->alfred_playlist_name;

    $setting_kind = $words[1];
    $theplaylist = $words[2];

    if ($setting_kind == 'Set Alfred Playlist') {
        $w->result(null, '', 'Set your Alfred playlist', array('Select one of your playlists below as your Alfred playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, '');

        try {
            if (countCharacters($theplaylist) < 2) {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1';
                $stmt = $db->prepare($getPlaylists);
            }
            else {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist from playlists where ownedbyuser=1 and ( name_deburr like :playlist or author like :playlist)';
                $stmt = $db->prepare($getPlaylists);
                $stmt->bindValue(':playlist', '%' . deburr($theplaylist) . '%');
            }

            $stmt->execute();
            $results = $stmt->fetchAll();
        }
        catch(PDOException $e) {
            handleDbIssuePdoXml($e);

            return;
        }

        foreach ($results as $playlist) {
            $added = ' ';
            if (startswith($playlist[1], 'Artist radio for')) {
                $added = getenv('emoji_radio').' ';
            }
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'ALFRED_PLAYLIST▹' . $playlist[0] . '▹' . $playlist[1] /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), getenv('emoji_playlist') . $added . $playlist[1], $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' Select the playlist to set it as your Alfred Playlist', $playlist[5], 'yes', null, '');
        }
    }
    elseif ($setting_kind == 'Confirm Clear Alfred Playlist') {
        $w->result(null, '', '⚠️ Are you sure? ⚠️', array('❗❗This will remove all the tracks in your current Alfred Playlist, this is NOT undoable❗❗', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

        $w->result(null, '', 'No, cancel', array('Return to Alfred Playlist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/uncheck.png', 'no', null, 'Alfred Playlist▹');

        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, 'CLEAR_ALFRED_PLAYLIST▹' . $alfred_playlist_uri . '▹' . $alfred_playlist_name /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Yes, go ahead', '❗❗This is NOT undoable❗❗', './images/check.png', 'yes', null, '');
    }
}

/**
 * secondDelimiterFollowUnfollow function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterFollowUnfollow($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $use_artworks = $settings->use_artworks;

    if (substr_count($query, '@') == 1) {

        $tmp = $words[1];
        $words = explode('@', $tmp);
        $uri = $words[0];
        $tmp_uri = explode(':', $uri);

        if ($tmp_uri[1] == 'artist') {
            $artist_uri = $words[0];
            // Follow / Unfollow artist Option menu
            $artist_name = $words[1];

            try {
                $api = getSpotifyWebAPI($w);
                $isArtistFollowed = $api->currentUserFollows('artist', $tmp_uri[2]);

                $artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false, false, false, $use_artworks);
                if (!$isArtistFollowed[0]) {
                    $w->result(null, '', 'Follow artist ' . $artist_name, array('You are not currently following the artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artist_artwork_path, 'no', null, 'Follow▹' . $artist_uri . '@' . $artist_name . '▹');
                }
                else {
                    $w->result(null, '', 'Unfollow artist ' . $artist_name, array('You are currently following the artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artist_artwork_path, 'no', null, 'Unfollow▹' . $artist_uri . '@' . $artist_name . '▹');
                }
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                echo $w->tojson();
                exit;
            }
        }
        else if ($tmp_uri[1] == 'show') {
            $show_uri = $words[0];

            // Follow / Unfollow show Option menu
            $show_name = $words[1];

            try {
                $api = getSpotifyWebAPI($w);
                $isShowFollowed = $api->myShowsContains($show_uri);

                $show_artwork_path = getShowArtwork($w, $show_uri, false, false, false, $use_artworks);
                if (!$isShowFollowed[0]) {
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, $show_uri
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'follow_show'
                    /* other_action */, ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, $show_artwork_path
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Follow show ' . $show_name, 'This will add the show to your library', $show_artwork_path, 'yes', null, '');
                }
                else {
                    $w->result(null, serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, $show_uri
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'unfollow_show'
                    /* other_action */, ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, $show_artwork_path
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Unfollow show ' . $show_name, 'This will remove the show from your library', $show_artwork_path, 'yes', null, '');
                }
            }
            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                echo $w->tojson();
                exit;
            }
        }
    }
}

/**
 * secondDelimiterFollowOrUnfollow function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterFollowOrUnfollow($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $kind = $words[0];

    if (substr_count($query, '@') == 1) {

        if ($kind == 'Follow') {
            $follow = true;
        }
        else {
            $follow = false;
        }

        $tmp = $words[1];
        $words = explode('@', $tmp);
        $uri = $words[0];
        $tmp_uri = explode(':', $uri);

        // Follow / Unfollow artist actions
        $artist_uri = $words[0];
        $artist_name = $words[1];

        try {
            $api = getSpotifyWebAPI($w);
            if ($follow) {
                $ret = $api->followArtistsOrUsers('artist', $tmp_uri[2]);
            }
            else {
                $ret = $api->unfollowArtistsOrUsers('artist', $tmp_uri[2]);
            }

            if ($ret) {
                if ($follow) {
                    displayNotificationWithArtwork($w, 'You are now following the artist ' . $artist_name, './images/follow.png', 'Follow');
                    exec("osascript -e 'tell application id \"" . getAlfredName() . "\" to search \"" . getenv('c_spot_mini') . ' Artist▹' . $artist_uri . '∙' . escapeQuery($artist_name) . '▹' . "\"'");
                }
                else {
                    displayNotificationWithArtwork($w, 'You are no more following the artist ' . $artist_name, './images/follow.png', 'Unfollow');
                    exec("osascript -e 'tell application id \"" . getAlfredName() . "\" to search \"" . getenv('c_spot_mini') . ' Artist▹' . $artist_uri . '∙' . escapeQuery($artist_name) . '▹' . "\"'");
                }
            }
            else {
                $w->result(null, '', 'Error!', array('An error happened! try again or report to the author', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }
    }
}

/**
 * secondDelimiterDisplayBiography function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterDisplayBiography($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $use_artworks = $settings->use_artworks;

    if (substr_count($query, '∙') == 1) {

        // Search Biography
        $tmp = $words[1];
        $words = explode('∙', $tmp);
        $artist_uri = $words[0];
        $artist_name = $words[1];

        list($biography_url, $source, $biography, $twitter_url, $official_url) = getBiography($w, $artist_uri, $artist_name);

        if ($biography_url != false) {
            if ($source == 'Last FM') {
                $image = './images/lastfm.png';
            }
            elseif ($source == 'Wikipedia') {
                $image = './images/wikipedia.png';
            }
            else {
                $image = './images/biography.png';
            }

            if ($twitter_url != '') {
                $twitter_account = end((explode('/', rtrim($twitter_url, '/'))));
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'Open▹' . $twitter_url /* other_settings*/, ''
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'See twitter account @' . $twitter_account, 'This will open your default browser with the twitter of the artist', './images/twitter.png', 'yes', null, '');
            }

            if ($official_url != '') {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, 'Open▹' . $official_url /* other_settings*/, ''
                /* other_action */,

                ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'See official website for the artist (' . $official_url . ')', 'This will open your default browser with the official website of the artist', './images/artists.png', 'yes', null, '');
            }

            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . $biography_url /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'See biography for ' . $artist_name . ' on ' . $source, 'This will open your default browser', $image, 'yes', null, '');

            $wrapped = wordwrap($biography, 70, "\n", false);
            $biography_sentances = explode("\n", $wrapped);
            $artist_artwork_path = getArtistArtwork($w, $artist_uri, $artist_name, false, false, false, $use_artworks);
            for ($i = 0;$i < count($biography_sentances);++$i) {
                $w->result(null, '', $biography_sentances[$i], array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artist_artwork_path, 'no', null, '');
            }
        }
        else {
            $w->result(null, 'help', 'No biography found!', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }
    }
}

/**
 * secondDelimiterDisplayConfirmRemovePlaylist function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterDisplayConfirmRemovePlaylist($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    if (substr_count($query, '∙') == 1) {
        $tmp = $words[1];
        $words = explode('∙', $tmp);
        $playlist_uri = $words[0];
        $playlist_name = $words[1];
        $w->result(null, '', 'Are you sure?', array('This will remove the playlist from your library.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

        $w->result(null, '', 'No, cancel', array('Return to the playlist menu', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/uncheck.png', 'no', null, 'Playlist▹' . $playlist_uri . '▹');

        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, $playlist_uri
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'unfollow_playlist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, $playlist_name
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Yes, go ahead', 'You can always recover a removed playlist by choosing option below', './images/check.png', 'yes', null, '');

        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, 'Open▹' . 'https://www.spotify.com/us/account/recover-playlists/' /* other_settings*/, ''
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Open Spotify web page to recover any of your playlists', 'This will open the Spotify page with your default browser', './images/spotify.png', 'yes', null, '');
    }
}

/**
 * secondDelimiterBrowse function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;

    $country = $words[1];
    $search = $words[2];

    if ($country == 'Choose a Country') {

        $spotify_country_codes = getSpotifyCountryCodesList();

        foreach ($spotify_country_codes as $spotify_country_code) {
            if (strtoupper($spotify_country_code) != 'US' && strtoupper($spotify_country_code) != 'GB' && strtoupper($spotify_country_code) != strtoupper($country_code)) {

                if (countCharacters($search) < 1 || strpos(strtolower($spotify_country_code), strtolower($search)) !== false || strpos(strtolower(getCountryName(strtoupper($spotify_country_code))), strtolower($search)) !== false) {
                    $w->result(null, '', getCountryName(strtoupper($spotify_country_code)), array('Browse the Spotify categories in ' . getCountryName(strtoupper($spotify_country_code)), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹' . strtoupper($spotify_country_code) . '▹');
                }
            }
        }
    }
    else {
        try {
            $api = getSpotifyWebAPI($w);
            $offsetListCategories = 0;
            $limitListCategories = 50;
            do {
                $listCategories = $api->getCategoriesList(array('country' => $country, 'limit' => $limitListCategories, 'locale' => '', 'offset' => $offsetListCategories,));
                $offsetListCategories += $limitListCategories;
            } while ($offsetListCategories < $listCategories
                ->categories
                ->total);

            foreach ($listCategories
                ->categories->items as $category) {

                if (countCharacters($search) < 2 || strpos(strtolower($category->name), strtolower($search)) !== false) {
                    $w->result(null, '', escapeQuery($category->name), 'Browse this category', getCategoryArtwork($w, $category->id, $category->icons[0]->url, true, false, $use_artworks), 'no', null, 'Browse▹' . $country . '▹' . $category->id . '▹');
                }
            }
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();

            exit;
        }
    }
}

/**
 * secondDelimiterPreview function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function secondDelimiterPreview($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;
    $is_display_rating = $settings->is_display_rating;
    $output_application = $settings->output_application;

    if (!file_exists('/usr/local/bin/mpg123')) {
        $w->result(null, '', 'mpg123 is not installed, install using brew install mpg123', array('install using brew install mpg123', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }

    $track_uri = $words[1];
    $search = $words[2];

    $tmp = explode(':', $track_uri);
    if ($tmp[1] == 'track') {
        $track = getTheFullTrack($w, $track_uri, $country_code);
        if (isset($track->preview_url) && $track->preview_url != null) {
            $track_artwork_path = getTrackOrAlbumArtwork($w, $track_uri, false, false, false, $use_artworks);
            $popularity = '';
            if ($is_display_rating) {
                $popularity = floatToStars($track->popularity / 100);
            }
            $subtitle = '⌥ (play album) ⌘ (play artist) ctrl (lookup online)';
            $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
            $w->result(null, serialize(array($track->uri
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, ''
            /* other_action */, escapeQuery($track->artists[0]
                ->name) /* artist_name */, escapeQuery($track->name) /* track_name */, escapeQuery($track
                ->album
                ->name) /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), '👁Previewing ' . escapeQuery($track->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->artists[0]
                ->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track
                ->album
                ->name) . ' '.getenv('emoji_separator').' ' . $popularity . ' (' . beautifyTime($track->duration_ms / 1000) . ')', array($subtitle, 'alt' => 'Play album ' . escapeQuery($track
                ->album
                ->name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($track->artists[0]
                ->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($track
                ->album
                ->name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($track->artists[0]
                ->name) . ' online',), $track_artwork_path, 'yes', '', '');

            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'kill_preview'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Stop the preview', 'This will stop the 30 seconds preview', './images/uncheck.png', 'yes', null, '');

            // pause track or episode
            if ($output_application == 'MOPIDY') {
                invokeMopidyMethod($w, 'core.playback.pause', array());
            }
            else if ($output_application == 'APPLESCRIPT') {
                exec("osascript -e 'tell application \"Spotify\" to pause'");
            }
            else {
                $device_id = getSpotifyConnectCurrentDeviceId($w);
                if ($device_id != '') {
                    pauseSpotifyConnect($w, $device_id);
                }
                else {
                    displayNotificationWithArtwork($w, 'No Spotify Connect device is available', './images/warning.png', 'Error!');
                    return;
                }
            }
            exec('curl -s ' . $track->preview_url . ' | /usr/local/bin/mpg123 - > /dev/null 2>&1 &');
        }
        else {
            $w->result(null, '', 'The track . ' . $track->name . ' does not have a preview', array('uri=' . $track_uri, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }
    }
    else if ($tmp[1] == 'episode') {
        $episode = getEpisode($w, $track_uri);
        if (isset($episode->audio_preview_url) && $episode->audio_preview_url != null) {

            $iso = new Matriphe\ISO639\ISO639;
            $array_languages = array();
            foreach ($episode->languages as $language) {
                if (strpos($language, '-') !== false) {
                    $language = strstr($language, '-', true);
                }
                $array_languages[] = $iso->languageByCode1($language);
            }
            $episode_artwork_path = getEpisodeArtwork($w, $episode->uri, false, false, false, $use_artworks);
            $w->result(null, serialize(array($episode->uri
            /*track_uri*/, $episode
                ->show->uri
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'play_episode'
            /* other_action */, ''
            /* artist_name */, escapeQuery($episode->name) /* track_name */, escapeQuery($episode
                ->show
                ->name) /* album_name */, $episode_artwork_path
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), '👁Previewing ' . $episode->name, array($episode->episode_type . 'Progress: ' . floatToCircles(intval($episode
                ->resume_point
                ->resume_position_ms) / intval($episode->duration_ms)) . ' Duration ' . beautifyTime($episode->duration_ms / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episode->release_date . ' '.getenv('emoji_separator').' Languages: ' . implode(',', $array_languages), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $episode_artwork_path, 'yes', null, '');

            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'kill_preview'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Stop the preview', 'This will stop the 30 seconds preview', './images/uncheck.png', 'yes', null, '');

            // pause track or episode
            if ($output_application == 'MOPIDY') {
                invokeMopidyMethod($w, 'core.playback.pause', array());
            }
            else if ($output_application == 'APPLESCRIPT') {
                exec("osascript -e 'tell application \"Spotify\" to pause'");
            }
            else {
                $device_id = getSpotifyConnectCurrentDeviceId($w);
                if ($device_id != '') {
                    pauseSpotifyConnect($w, $device_id);
                }
                else {
                    displayNotificationWithArtwork($w, 'No Spotify Connect device is available', './images/warning.png', 'Error!');
                    return;
                }
            }

            exec('curl -s ' . $episode->audio_preview_url . ' | /usr/local/bin/mpg123 - > /dev/null 2>&1 &');
        }
        else {
            $w->result(null, '', 'The episode . ' . $episode->name . ' does not have a preview', array('uri=' . $episode->uri, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }

    }
    else {
        $w->result(null, '', 'Only tracks and episodes can be previewed', array('uri=' . $track_uri, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }
}

