<?php
/**
 * firstDelimiterPlaylists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterPlaylists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $fuzzy_search = $settings->fuzzy_search;
    $max_results = $settings->max_results;

    // Search playlists
    $theplaylist = $words[1];
    try {
        if (countCharacters($theplaylist) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                $results = getExternalResults($w, 'playlists', array('uri','name','nb_tracks','author','username','playlist_artwork_path','ownedbyuser','nb_playable_tracks','duration_playlist','collaborative','public','nb_times_played'), 'order by nb_times_played desc');
            } else {
                $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public,nb_times_played from playlists order by nb_times_played desc';
                $stmt = $db->prepare($getPlaylists);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
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

    $noresult = true;
    if ($query == 'Playlist▹Artist radio') {
        foreach ($results as $playlist) {
            $noresult = false;
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
            if (startswith($playlist[1], 'Artist radio for')) {
                $w->result(null, '', getenv('emoji_playlist') . ' ' . $playlist[1], array($public_status . ' playlist by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' ' . $playlist[11] . ' times played', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $playlist[5], 'no', null, 'Playlist▹' . $playlist[0] . '▹');
            }
        }
    }
    elseif ($query == 'Playlist▹Song radio') {
        foreach ($results as $playlist) {
            $noresult = false;
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
            if (startswith($playlist[1], 'Song radio for')) {
                $w->result(null, '', getenv('emoji_playlist') . ' ' . $playlist[1], array($public_status . ' playlist by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' ' . $playlist[11] . ' times played', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $playlist[5], 'no', null, 'Playlist▹' . $playlist[0] . '▹');
            }
        }
    }
    else {
        $savedPlaylists = array();
        $nb_artist_radio_playlist = 0;
        $nb_song_radio_playlist = 0;
        foreach ($results as $playlist) {
            if (startswith($playlist[1], 'Artist radio for')) {
                ++$nb_artist_radio_playlist;
                continue;
            }

            if (startswith($playlist[1], 'Song radio for')) {
                ++$nb_song_radio_playlist;
                continue;
            }

            $savedPlaylists[] = $playlist;
        }

        if (countCharacters($theplaylist) < 2) {
            if ($nb_artist_radio_playlist > 0) {
                $w->result(null, '', 'Browse your artist radio playlists (' . $nb_artist_radio_playlist . ' playlists)', array('Display all your artist radio playlists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/radio_artist.png', 'no', null, 'Playlist▹Artist radio');
            }
            if ($nb_song_radio_playlist > 0) {
                $w->result(null, '', 'Browse your song radio playlists (' . $nb_song_radio_playlist . ' playlists)', array('Display all your song radio playlists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/radio_song.png', 'no', null, 'Playlist▹Song radio');
            }
            $w->result(null, '', 'Featured Playlists', array('Browse the current featured playlists', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹');
        }

        foreach ($savedPlaylists as $playlist) {
            $noresult = false;
            $added = ' ';
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
            $w->result(null, '', getenv('emoji_playlist') . $added . $playlist[1], array($public_status . ' playlist by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8] . ' '.getenv('emoji_separator').' ' . $playlist[11] . ' times played', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $playlist[5], 'no', null, 'Playlist▹' . $playlist[0] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }

    $w->result(null, serialize(array(''
    /*track_uri*/, ''
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, ''
    /* other_settings*/, 'reset_playlist_number_times_played'
    /* other_action */, ''
    /* artist_name */, ''
    /* track_name */, ''
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, ''
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), 'Reset number of times played for playlists', 'This will reset playlists all times played counters to 0', './images/settings.png', 'yes', '');
}

/**
 * firstDelimiterArtists function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterArtists($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $all_playlists = $settings->all_playlists;
    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $fuzzy_search = $settings->fuzzy_search;
    $max_results = $settings->max_results;

    // Search artists
    $artist = $words[1];

    try {
        if (countCharacters($artist) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                if ($all_playlists == false) {
                    $results = getExternalResults($w, 'followed_artists', array('name','artist_artwork_path','uri'), 'group by name' . ' limit ' . $max_results);
                }
                else {
                    $results = getExternalResults($w, 'tracks', array('artist_name','artist_artwork_path','artist_uri'), 'group by artist_name' . ' limit ' . $max_results);
                }
            } else {
                if ($all_playlists == false) {
                    $getArtists = 'select name,artist_artwork_path,uri from followed_artists group by name' . ' limit ' . $max_results;
                }
                else {
                    $getArtists = 'select artist_name,artist_artwork_path,artist_uri from tracks group by artist_name' . ' limit ' . $max_results;
                }
                $stmt = $db->prepare($getArtists);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $results = getFuzzySearchResults($w, $update_in_progress, $artist, 'followed_artists', array('name','artist_artwork_path','uri'), $max_results, '1', '');
                }
                else {
                    $results = getFuzzySearchResults($w, $update_in_progress, $artist, 'tracks', array('artist_name','artist_artwork_path','artist_uri'), $max_results, '1', '');
                }
            } else {
                // Search artists
                if ($all_playlists == false) {
                    $getArtists = 'select name,artist_artwork_path,uri from followed_artists where name_deburr like :query limit ' . $max_results;
                }
                else {
                    $getArtists = 'select artist_name,artist_artwork_path,artist_uri from tracks where artist_name_deburr like :query limit ' . $max_results;
                }
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

    $noresult = true;
    foreach ($results as $artists) {
        $noresult = false;
        $nb_artist_tracks = getNumberOfTracksForArtist($update_in_progress, $w, $db, $artists[0]);
        if (checkIfResultAlreadyThere($w->results(), getenv('emoji_artist').' ' . $artists[0] . ' (' . $nb_artist_tracks . ' tracks)') == false) {
            $uri = $artists[2];
            // in case of local track, pass track uri instead
            if ($uri == '') {
                $uri = $artists[3];
            }

            $w->result(null, '', getenv('emoji_artist').' ' . $artists[0] . ' (' . $nb_artist_tracks . ' tracks)', array('Browse this artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artists[1], 'no', null, 'Artist▹' . $uri . '∙' . $artists[0] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        if ($output_application != 'MOPIDY') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, base64_encode('artist:' . $artist) /* spotify_command */, ''
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
            )), 'Search for artist ' . $artist . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
        }
    }
}

/**
 * firstDelimiterShows function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterShows($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $fuzzy_search = $settings->fuzzy_search;

    // Search shows
    $show = $words[1];

    try {
        if (countCharacters($show) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                $results = getExternalResults($w, 'shows', array('uri','name','description','media_type','show_artwork_path','explicit','added_at','languages','nb_times_played','is_externally_hosted', 'nb_episodes'), 'group by name' . ' limit ' . $max_results);
            } else {
                $getShows = 'select * from shows group by name' . ' limit ' . $max_results;
                $stmt = $db->prepare($getShows);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                $results = getFuzzySearchResults($w, $update_in_progress, $show, 'shows', array('uri','name','description','media_type','show_artwork_path','explicit','added_at','languages','nb_times_played','is_externally_hosted', 'nb_episodes'), $max_results, '2', '');
            } else {
                $getShows = 'select * from shows where name_deburr like :query limit ' . $max_results;
                $stmt = $db->prepare($getShows);
                $stmt->bindValue(':query', '%' . deburr($show) . '%');
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
    }
    catch(PDOException $e) {
        handleDbIssuePdoXml($e);

        exit;
    }

    // display all shows
    $noresult = true;
    foreach ($results as $show) {
        $noresult = false;
        if (checkIfResultAlreadyThere($w->results(), getenv('emoji_show').' ' . $show[1] . ' (' . $show[10] . ' episodes)') == false) {
            $w->result(null, '', getenv('emoji_show').' ' . $show[1] . ' (' . $show[10] . ' episodes)', array('Browse this show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $show[4], 'no', null, 'Show▹' . $show[0] . '∙' . $show[1] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        if ($output_application != 'MOPIDY') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, base64_encode('show:' . $show) /* spotify_command */, ''
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
            )), 'Search for show ' . $show . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/spotify.png', 'yes', null, '');
        }
    }
}

/**
 * firstDelimiterAlbums function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterAlbums($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $all_playlists = $settings->all_playlists;
    $max_results = $settings->max_results;
    $output_application = $settings->output_application;
    $fuzzy_search = $settings->fuzzy_search;

    // New Releases menu
    $w->result(null, '', 'New Releases', array('Browse new album releases', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹');

    // Search albums
    $album = $words[1];
    try {
        if (countCharacters($album) < 2) {
            if($update_in_progress && file_exists($w->data() . '/create_library')) {
                if ($all_playlists == false) {
                    $results = getExternalResults($w, 'tracks', array('album_name','album_artwork_path','artist_name','album_uri','album_type'), 'group by album_name order by max(added_at) desc limit ' . $max_results, 'where yourmusic_album=1');
                }
                else {
                    $results = getExternalResults($w, 'tracks', array('album_name','album_artwork_path','artist_name','album_uri','album_type'), 'group by album_name order by max(added_at) desc limit ' . $max_results);
                }
            } else {
                if ($all_playlists == false) {
                    $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic_album=1' . ' group by album_name order by max(added_at) desc limit ' . $max_results;
                }
                else {
                    $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks group by album_name order by max(added_at) desc limit ' . $max_results;
                }
                $stmt = $db->prepare($getTracks);
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
        }
        else {
            if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
                if ($all_playlists == false) {
                    $where_clause = 'where yourmusic_album=1';
                }
                else {
                    $where_clause = '';
                }
                $results = getFuzzySearchResults($w, $update_in_progress,  $album, 'tracks', array('album_name','album_artwork_path','artist_name','album_uri','album_type'), $max_results, '1,3', $where_clause);
            } else {
                if ($all_playlists == false) {
                    $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where yourmusic_album=1 and and album_name != "" and (album_name_deburr like :album_name or artist_name_deburr like :album_name) group by album_name order by max(added_at) desc limit ' . $max_results;
                }
                else {
                    $getTracks = 'select album_name,album_artwork_path,artist_name,album_uri,album_type from tracks where (album_name_deburr like :album_name or artist_name_deburr like :album_name) group by album_name order by max(added_at) desc limit ' . $max_results;
                }
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
        $nb_album_tracks = getNumberOfTracksForAlbum($update_in_progress, $w, $db, $track[3]);
        if (checkIfResultAlreadyThere($w->results(), $track[0] . ' (' . $nb_album_tracks . ' tracks)'. ' by '.$track[2]) == false) {
            $w->result(null, '',  $track[0] . ' (' . $nb_album_tracks . ' tracks)'. ' by '.$track[2], array($track[2] . ' by ' . $track[2], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track[1], 'no', null, 'Album▹' . $track[3] . '∙' . $track[0] . '▹');
        }
    }

    if ($noresult) {
        $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        if ($output_application != 'MOPIDY') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, base64_encode('album:' . $album) /* spotify_command */, ''
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
            )), 'Search for album ' . $album . ' in Spotify', array('This will start a new search in Spotify', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => ''), './images/spotify.png', 'yes', null, '');
        }
    }
}

/**
 * firstDelimiterFeaturedPlaylist function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterFeaturedPlaylist($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[1];

    $country_code = $settings->country_code;

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName($country_code)), strtolower($search)) !== false) {
        $w->result(null, '', getCountryName($country_code), array('Browse the current featured playlists in ' . getCountryName($country_code), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹' . $country_code . '▹');
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('US')), strtolower($search)) !== false) {
        if ($country_code != 'US') {
            $w->result(null, '', getCountryName('US'), array('Browse the current featured playlists in ' . getCountryName('US'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹US▹');
        }
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('GB')), strtolower($search)) !== false) {
        if ($country_code != 'GB') {
            $w->result(null, '', getCountryName('GB'), array('Browse the current featured playlists in ' . getCountryName('GB'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹GB▹');
        }
    }

    if (countCharacters($search) < 2 || strpos(strtolower('Choose Another country'), strtolower($search)) !== false) {
        $w->result(null, '', 'Choose Another country', array('Browse the current featured playlists in another country of your choice', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/star.png', 'no', null, 'Featured Playlist▹Choose a Country▹');
    }
}

/**
 * firstDelimiterSearchOnline function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterSearchOnline($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $kind = $words[0];
    $search = $words[1];

    $max_results = $settings->max_results;
    $country_code = $settings->country_code;
    $use_artworks = $settings->use_artworks;
    $search_order = $settings->search_order;

    // Search online
    $the_query = $words[1] . '*';

    if (countCharacters($the_query) < 2) {
        if ($kind == 'Search Online') {
            $w->result(null, 'help', 'Search for playlists, artists, albums, shows, episodes or tracks online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');

            $w->result(null, null, 'Search for playlists only', array('This will search for playlists online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/playlists.png', 'no', null, 'Search Playlists Online▹');

            $w->result(null, null, 'Search for tracks only', array('This will search for tracks online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/tracks.png', 'no', null, 'Search Tracks Online▹');

            $w->result(null, null, 'Search for artists only', array('This will search for artists online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/artists.png', 'no', null, 'Search Artists Online▹');

            $w->result(null, null, 'Search for shows only', array('This will search for shows online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/shows.png', 'no', null, 'Search Shows Online▹');

            $w->result(null, null, 'Search for show episodes only', array('This will search for show episodes online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/episodes.png', 'no', null, 'Search Episodes Online▹');

            $w->result(null, null, 'Search for albums only', array('This will search for albums online, i.e not in your library', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/albums.png', 'no', null, 'Search Albums Online▹');
        }
        elseif ($kind == 'Search Playlists Online') {
            $w->result(null, 'help', 'Search playlists online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        elseif ($kind == 'Search Artists Online') {
            $w->result(null, 'help', 'Search artists online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        elseif ($kind == 'Search Tracks Online') {
            $w->result(null, 'help', 'Search tracks online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        elseif ($kind == 'Search Shows Online') {
            $w->result(null, 'help', 'Search shows online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        elseif ($kind == 'Search Episodes Online') {
            $w->result(null, 'help', 'Search show episodes online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
        elseif ($kind == 'Search Albums Online') {
            $w->result(null, 'help', 'Search albums online, i.e not in your library', array('Begin typing at least 3 characters to start search online. This is using slow Spotify API be patient.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
        }
    }
    else {
        $search_playlists = false;
        $search_artists = false;
        $search_albums = false;
        $search_tracks = false;
        $search_shows = false;
        $search_episodes = false;

        if ($kind == 'Search Online') {
            $search_playlists = true;
            $search_artists = true;
            $search_albums = true;
            $search_tracks = true;
            $search_shows = true;
            $search_episodes = true;
            $search_playlists_limit = 8;
            $search_artists_limit = 5;
            $search_albums_limit = 5;
            $search_tracks_limit = 20;
            $search_shows_limit = 5;
            $search_episodes_limit = 10;
        }
        elseif ($kind == 'Search Playlists Online') {
            $search_playlists = true;
            $search_playlists_limit = ($max_results <= 50) ? $max_results : 50;
        }
        elseif ($kind == 'Search Artists Online') {
            $search_artists = true;
            $search_artists_limit = ($max_results <= 50) ? $max_results : 50;
        }
        elseif ($kind == 'Search Albums Online') {
            $search_albums = true;
            $search_albums_limit = ($max_results <= 50) ? $max_results : 50;
        }
        elseif ($kind == 'Search Shows Online') {
            $search_shows = true;
            $search_shows_limit = ($max_results <= 50) ? $max_results : 50;
        }
        elseif ($kind == 'Search Episodes Online') {
            $search_episodes = true;
            $search_episodes_limit = ($max_results <= 50) ? $max_results : 50;
        }
        elseif ($kind == 'Search Tracks Online') {
            $search_tracks = true;
            $search_tracks_limit = ($max_results <= 50) ? $max_results : 50;
        }

        $noresult = true;

        $search_categories = explode('▹', $search_order);

        foreach ($search_categories as $search_category) {

            if ($search_category == 'artist') {

                if ($search_artists == true) {
                    // Search Artists
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = 'artist:' . $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'artist', $search_artists_limit, false);

                    foreach ($results as $artist) {
                        if (checkIfResultAlreadyThere($w->results(), getenv('emoji_artist').' ' . escapeQuery($artist->name)) == false) {
                            $noresult = false;
                            $w->result(null, '', getenv('emoji_artist').' ' . escapeQuery($artist->name), 'Browse this artist', getArtistArtwork($w, $artist->uri, $artist->name, false, false, false, $use_artworks), 'no', null, 'Online▹' . $artist->uri . '@' . escapeQuery($artist->name) . '▹');
                        }
                    }
                }
            }

            if ($search_category == 'show') {

                if ($search_shows == true) {
                    // Search shows
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'show', $search_shows_limit, false);
                    foreach ($results as $show) {
                        if (checkIfResultAlreadyThere($w->results(), getenv('emoji_show').' ' . escapeQuery($show->name)) == false) {
                            $noresult = false;
                            $w->result(null, '', getenv('emoji_show').' ' . escapeQuery($show->name), 'Browse this show', getShowArtwork($w, $show->uri, false, false, false, $use_artworks), 'no', null, 'Online▹' . $show->uri . '@' . escapeQuery($show->name) . '▹');
                        }
                    }
                }
            }

            if ($search_category == 'episode') {

                if ($search_episodes == true) {
                    // Search show episodes
                    $iso = new Matriphe\ISO639\ISO639;
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'episode', $search_episodes_limit, false);
                    foreach ($results as $episode) {
                        if (checkIfResultAlreadyThere($w->results(), $episode->name) == false) {
                            $noresult = false;
                            if (countCharacters($search) < 2 || strpos(strtolower($episode->name), strtolower($search)) !== false) {
                                $episode_artwork_path = getEpisodeArtwork($w, $episode->uri, false, false, false, $use_artworks);

                                $array_languages = array();
                                foreach ($episode->languages as $language) {
                                    if (strpos($language, '-') !== false) {
                                        $language = strstr($language, '-', true);
                                    }
                                    $array_languages[] = $iso->languageByCode1($language);
                                }
                                $w->result(null, serialize(array($episode->uri
                                /*track_uri*/, ''
                                /* album_uri */, ''
                                /* artist_uri */, ''
                                /* playlist_uri */, ''
                                /* spotify_command */, ''
                                /* query */, ''
                                /* other_settings*/, 'play_episode_simplified'
                                /* other_action */, ''
                                /* artist_name */, escapeQuery($episode->name) /* track_name */, ''
                                /* album_name */, $episode_artwork_path
                                /* track_artwork_path */, ''
                                /* artist_artwork_path */, ''
                                /* album_artwork_path */, ''
                                /* playlist_name */, '', /* playlist_artwork_path */
                                )), $episode->name, array($episode->episode_type . 'Progress: ' . floatToCircles(intval($episode
                                    ->resume_point
                                    ->resume_position_ms) / intval($episode->duration_ms)) . ' Duration ' . beautifyTime($episode->duration_ms / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episode->release_date . ' '.getenv('emoji_separator').' Languages: ' . implode(',', $array_languages), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $episode_artwork_path, 'yes', null, '');
                            }
                        }
                    }
                }
            }

            if ($search_category == 'album') {

                if ($search_albums == true) {
                    // Search Albums
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = 'album:' . $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'album', $search_albums_limit, false);

                    try {
                        $api = getSpotifyWebAPI($w);
                    }
                    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                        echo $w->tojson();
                        exit;
                    }

                    foreach ($results as $album) {
                        if (checkIfResultAlreadyThere($w->results(), escapeQuery($album->name)) == false) {
                            $noresult = false;

                            try {
                                $full_album = $api->getAlbum($album->id);
                            }
                            catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                                $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                                echo $w->tojson();
                                exit;
                            }
                            $w->result(null, '', escapeQuery($album->name) . ' (' . $full_album
                                ->tracks->total . ' tracks)', $album->album_type . ' by ' . escapeQuery($full_album->artists[0]
                                ->name), getTrackOrAlbumArtwork($w, $album->uri, false, false, false, $use_artworks), 'no', null, 'Online▹' . $full_album->artists[0]->uri . '@' . escapeQuery($full_album->artists[0]
                                ->name) . '@' . $album->uri . '@' . escapeQuery($album->name) . '▹');
                        }
                    }
                }
            }

            if ($search_category == 'playlist') {

                if ($search_playlists == true) {
                    // Search Playlists
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'playlist', $search_playlists_limit, false);

                    foreach ($results as $playlist) {
                        $noresult = false;
                        $w->result(null, '', getenv('emoji_playlist') . escapeQuery($playlist->name), 'by ' . $playlist
                            ->owner->id . ' '.getenv('emoji_separator').' ' . $playlist
                            ->tracks->total . ' tracks', getPlaylistArtwork($w, $playlist->uri, false, false, $use_artworks), 'no', null, 'Online Playlist▹' . $playlist->uri . '∙' . base64_encode($playlist->name) . '▹');
                    }
                }
            }

            if ($search_category == 'track') {
                if ($search_tracks == true) {
                    // Search Tracks
                    // call to web api, if it fails,
                    // it displays an error in main window
                    $query = 'track:' . $the_query;
                    $results = searchWebApi($w, $country_code, $query, 'track', $search_tracks_limit, false);
                    foreach ($results as $track) {
                        $noresult = false;
                        $track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);

                        $artists = $track->artists;
                        $artist = $artists[0];
                        $album = $track->album;

                        $w->result(null, serialize(array($track->uri
                        /*track_uri*/, $album->uri
                        /* album_uri */, $artist->uri
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'play_track_in_album_context'
                        /* other_action */, escapeQuery($artist->name) /* artist_name */, escapeQuery($track->name) /* track_name */, escapeQuery($album->name) /* album_name */, $track_artwork
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . escapeQuery($album->name), 'alt' => 'Play album ' . escapeQuery($album->name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album->name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online',), $track_artwork, 'yes', null, '');
                    }
                }
            }
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no result for this search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }
}

/**
 * firstDelimiterNewReleases function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterNewReleases($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[1];

    $country_code = $settings->country_code;

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName($country_code)), strtolower($search)) !== false) {
        $w->result(null, '', getCountryName($country_code), array('Browse the new album releases in ' . getCountryName($country_code), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹' . $country_code . '▹');
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('US')), strtolower($search)) !== false) {
        if ($country_code != 'US') {
            $w->result(null, '', getCountryName('US'), array('Browse the new album releases in ' . getCountryName('US'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹US▹');
        }
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('GB')), strtolower($search)) !== false) {
        if ($country_code != 'GB') {
            $w->result(null, '', getCountryName('GB'), array('Browse the new album releases in ' . getCountryName('GB'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹GB▹');
        }
    }

    if (countCharacters($search) < 2 || strpos(strtolower('Choose Another country'), strtolower($search)) !== false) {
        $w->result(null, '', 'Choose Another country', array('Browse the new album releases in another country of your choice', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/new_releases.png', 'no', null, 'New Releases▹Choose a Country▹');
    }
}

/**
 * firstDelimiterCurrentTrack function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterCurrentTrack($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $input = $words[1];

    $all_playlists = $settings->all_playlists;
    $radio_number_tracks = $settings->radio_number_tracks;
    $max_results = $settings->max_results;
    $country_code = $settings->country_code;
    $is_public_playlists = $settings->is_public_playlists;
    $output_application = $settings->output_application;
    $is_display_rating = $settings->is_display_rating;
    $use_artworks = $settings->use_artworks;
    $always_display_lyrics_in_browser = $settings->always_display_lyrics_in_browser;

    $results = getCurrentTrackinfo($w, $output_application);

    if (is_array($results) && count($results) > 0) {
        $isEpisode = false;
        $href = explode(':', $results[4]);
        $added = '';
        $shared_url = '';
        if ($href[1] == 'local') {
            $added = getenv('emoji_local_track').' ';
        }
        else if ($href[1] == 'episode') {
            $shared_url .= ' https://open.spotify.com/episode/';
            $shared_url .= $href[2];
        }
        else {
            $shared_url .= ' https://open.spotify.com/track/';
            $shared_url .= $href[2];
        }
        $subtitle = '⌥ (play album) ⌘ (play artist) ctrl (lookup online)';
        $subtitle = "$subtitle fn (add track to ...) ⇧ (add album to ...)";
        if (countCharacters($input) < 2) {
            $popularity = '';
            if ($is_display_rating) {
                $popularity = floatToStars($results[6] / 100);
            }
            if ($results[3] == 'playing') {
                if ($href[1] == 'episode') {
                    if ($output_application == 'CONNECT') {
                        $episode = getEpisode($w, $results[4]);
                        $iso = new Matriphe\ISO639\ISO639;
                        $array_languages = array();
                        foreach ($episode->languages as $language) {
                            if (strpos($language, '-') !== false) {
                                $language = strstr($language, '-', true);
                            }
                            $array_languages[] = $iso->languageByCode1($language);
                        }
                        $clipboard_current_track_episode_text = getenv('clipboard_current_track_episode_text');
                        $clipboard_current_track_episode_text  = str_replace('{episode_name}', escapeQuery($episode->name), $clipboard_current_track_episode_text);
                        $clipboard_current_track_episode_text  = str_replace('{url}', $shared_url, $clipboard_current_track_episode_text);
                        $w->result(null, serialize(array($episode->uri
                        /*track_uri*/, $episode->uri
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'pause'
                        /* other_action */, ''
                        /* artist_name */, escapeQuery($episode->name) /* track_name */, '' /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $episode->name, array($episode->episode_type . 'Progress: ' . floatToCircles(intval($episode
                            ->resume_point
                            ->resume_position_ms) / intval($episode->duration_ms)) . ' Duration ' . beautifyTime($episode->duration_ms / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episode->release_date . ' '.getenv('emoji_separator').' Languages: ' . implode(',', $array_languages), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_episode_text, 'largetype' => escapeQuery($episode->name)), '');

                    }
                    else {
                        $clipboard_current_track_episode_text = getenv('clipboard_current_track_episode_text');
                        $clipboard_current_track_episode_text  = str_replace('{episode_name}', escapeQuery($results[0]), $clipboard_current_track_episode_text);
                        $clipboard_current_track_episode_text  = str_replace('{url}', $shared_url, $clipboard_current_track_episode_text);
                        $isEpisode = true;
                        $w->result(null, serialize(array($results[4] /*track_uri*/, ''
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'pause'
                        /* other_action */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $added . escapeQuery($results[0]), array(escapeQuery($results[1]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[2]) . ' '.getenv('emoji_separator').' ' . ' (' . beautifyTime($results[5] / 1000) . ')', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_episode_text, 'largetype' => escapeQuery($results[0])), '');
                    }
                }
                else {
                    $clipboard_current_track_track_text = getenv('clipboard_current_track_track_text');
                    $clipboard_current_track_track_text  = str_replace('{track_name}', escapeQuery($results[0]), $clipboard_current_track_track_text);
                    $clipboard_current_track_track_text  = str_replace('{artist_name}', escapeQuery($results[1]), $clipboard_current_track_track_text);
                    $clipboard_current_track_track_text  = str_replace('{url}', $shared_url, $clipboard_current_track_track_text);
                    $liked = 'emoji_not_liked';
                    if(isTrackInYourMusic($w,$results[4])) {
                        $liked = 'emoji_liked';
                    }
                    $w->result(null, serialize(array($results[4] /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'pause'
                    /* other_action */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), $added . escapeQuery(getenv($liked).' ' . $results[0]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[1]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[2]) . ' '.getenv('emoji_separator').' ' . $popularity . ' (' . beautifyTime($results[5] / 1000) . ')', array($subtitle, 'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ...', 'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_track_text, 'largetype' => escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]),), '');
                }
            }
            else {
                if ($href[1] == 'episode') {
                    if ($output_application == 'CONNECT') {

                        $episode = getEpisode($w, $results[4]);
                        $iso = new Matriphe\ISO639\ISO639;
                        $array_languages = array();
                        foreach ($episode->languages as $language) {
                            if (strpos($language, '-') !== false) {
                                $language = strstr($language, '-', true);
                            }
                            $array_languages[] = $iso->languageByCode1($language);
                        }
                        $clipboard_current_track_episode_text = getenv('clipboard_current_track_episode_text');
                        $clipboard_current_track_episode_text  = str_replace('{episode_name}', escapeQuery($episode->name), $clipboard_current_track_episode_text);
                        $clipboard_current_track_episode_text  = str_replace('{url}', $shared_url, $clipboard_current_track_episode_text);
                        $w->result(null, serialize(array($episode->uri
                        /*track_uri*/, $episode->uri
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'play'
                        /* other_action */, ''
                        /* artist_name */, escapeQuery($episode->name) /* track_name */, '' /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $episode->name, array($episode->episode_type . 'Progress: ' . floatToCircles(intval($episode
                            ->resume_point
                            ->resume_position_ms) / intval($episode->duration_ms)) . ' Duration ' . beautifyTime($episode->duration_ms / 1000) . ' '.getenv('emoji_separator').' Release date: ' . $episode->release_date . ' '.getenv('emoji_separator').' Languages: ' . implode(',', $array_languages), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_episode_text, 'largetype' => escapeQuery($episode->name)), '');

                    }
                    else {
                        $clipboard_current_track_episode_text = getenv('clipboard_current_track_episode_text');
                        $clipboard_current_track_episode_text  = str_replace('{episode_name}', escapeQuery($results[0]), $clipboard_current_track_episode_text);
                        $clipboard_current_track_episode_text  = str_replace('{url}', $shared_url, $clipboard_current_track_episode_text);
                        $isEpisode = true;
                        $w->result(null, serialize(array($results[4] /*track_uri*/, ''
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'play'
                        /* other_action */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $added . escapeQuery($results[0]), array(escapeQuery($results[1]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[2]) . ' '.getenv('emoji_separator').' ' . ' (' . beautifyTime($results[5] / 1000) . ')', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_episode_text, 'largetype' => escapeQuery($results[0])), '');
                    }
                }
                else {
                    $clipboard_current_track_track_text = getenv('clipboard_current_track_track_text');
                    $clipboard_current_track_track_text  = str_replace('{track_name}', escapeQuery($results[0]), $clipboard_current_track_track_text);
                    $clipboard_current_track_track_text  = str_replace('{artist_name}', escapeQuery($results[1]), $clipboard_current_track_track_text);
                    $clipboard_current_track_track_text  = str_replace('{url}', $shared_url, $clipboard_current_track_track_text);
                    $liked = 'emoji_not_liked';
                    if(isTrackInYourMusic($w,$results[4])) {
                        $liked = 'emoji_liked';
                    }
                    $w->result(null, serialize(array($results[4] /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'play'
                    /* other_action */, escapeQuery($results[1]) /* artist_name */, escapeQuery($results[0]) /* track_name */, escapeQuery($results[2]) /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), $added . escapeQuery(getenv($liked).' ' . $results[0]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[1]) . ' '.getenv('emoji_separator').' ' . escapeQuery($results[2]) . ' '.getenv('emoji_separator').' ' . $popularity . ' (' . beautifyTime($results[5] / 1000) . ')', array($subtitle, 'alt' => 'Play album ' . escapeQuery($results[2]) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($results[1]) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($results[0]) . ' to ...', 'shift' => 'Add album ' . escapeQuery($results[2]) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($results[1]) . ' online',), ($results[3] == 'playing') ? './images/pause.png' : './images/play.png', 'yes', array('copy' => $clipboard_current_track_track_text, 'largetype' => escapeQuery($results[0]) . ' by ' . escapeQuery($results[1]),), '');
                }
            }
        }

        if ($output_application == 'CONNECT') {
            $context_type = '';
            if (countCharacters($input) < 2) {
                try {
                    $api = getSpotifyWebAPI($w);

                    $playback_info = $api->getMyCurrentPlaybackInfo(array('market' => $country_code, 'additional_types' => 'track,episode',));

                    $currently_playing_type = $playback_info->currently_playing_type;

                    if ($currently_playing_type == 'episode') {
                        $isEpisode = true;
                    }

                    $is_playing = $playback_info->is_playing;
                    $popularity = $playback_info
                        ->item->popularity;
                    $progress_ms = $playback_info->progress_ms;

                    // device
                    $device_name = $playback_info
                        ->device->name;
                    $device_type = $playback_info
                        ->device->type;

                    $shuffle_state = "inactive";
                    if ($playback_info->shuffle_state) {
                        $shuffle_state = "active";
                    }

                    if ($playback_info->context != null) {
                        $context_type = $playback_info
                            ->context->type;
                        if ($context_type == 'playlist') {
                            $playlist_uri = $playback_info
                                ->context->uri;
                            $context = 'playlist ' . getPlaylistName($w, $playlist_uri) . ' ';
                        }
                        else if ($context_type == 'album') {
                            $album_uri = $playback_info
                                ->context->uri;
                            $context = 'album ' . getAlbumName($w, $album_uri) . ' ';
                        }
                        else if ($context_type == 'episode') {
                            $episode_uri = $playback_info
                                ->context->uri;
                            $context = 'episode ' . getEpisodeName($w, $episode_uri) . ' ';
                        }
                        else if ($context_type == 'artist') {
                            $artist_uri = $playback_info
                                ->context->uri;
                            $context = 'artist ' . getArtistName($w, $artist_uri) . ' ';
                        }
                    }
                    $repeat_state = "Repeat is <inactive>";
                    if ($playback_info->repeat_state == 'track') {
                        $repeat_state = "Repeat track is <active>";
                    }
                    else if ($playback_info->repeat_state == 'context') {
                        $repeat_state = "Repeat " . $context_type . " is <active>";
                    }

                    if ($device_name != '') {
                        $w->result(null, 'help', 'Playing ' . $context . 'on ' . $device_type . ' ' . $device_name, array('Progress: ' . floatToCircles($progress_ms / $results[5]) . ' Shuffle is <' . $shuffle_state . '> ' . $repeat_state, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'no', null, '');
                    }
                }
                catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
                    if ($e->getMessage() == 'Permissions missing') {
                        $w->result(null, serialize(array(''
                        /*track_uri*/, ''
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, ''
                        /* other_settings*/, 'reset_oauth_settings'
                        /* other_action */, ''
                        /* artist_name */, ''
                        /* track_name */, ''
                        /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), 'The workflow needs more priviledges to do this, click to restart authentication', array('Next time you invoke the workflow, you will have to re-authenticate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'yes', null, '');
                    }
                    else {
                        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                    }
                    echo $w->tojson();
                    exit;
                }
            }
        }

        if ($isEpisode) {
            $show_uri = getShowFromEpisode($w, $results[4]);
            $w->result(null, '', getenv('emoji_show').' ' . escapeQuery($results[2]) . ' (' . getNumberOfEpisodesForShow($w, $show_uri, $country_code) . ' episodes)', array('Browse this show', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), getEpisodeArtwork($w, $results[4], false, false, false, $use_artworks), 'no', null, 'Show▹' . $show_uri . '∙' . escapeQuery($results[2]) . '▹');
            echo $w->tojson();
            exit;
        }

        $getTracks = 'select artist_name,artist_uri from tracks where artist_name_deburr=:artist_name limit ' . 1;

        try {
            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':artist_name', deburr(escapeQuery($results[1])));
            $stmt->execute();
            $dbresults = $stmt->fetchAll();
        }
        catch(PDOException $e) {
            handleDbIssuePdoXml($e);

            return;
        }

        if (countCharacters($input) < 2 || strpos(strtolower('browse artist'), strtolower($input)) !== false) {
            // check if artist is in library
            $noresult = true;
            foreach ($dbresults as $track) {
                if ($track[1] != '') {
                    $artist_uri = $track[1];
                    $noresult = false;
                }
            }
            if ($noresult == false) {

                $href = explode(':', $artist_uri);
                $shared_url .= ' https://open.spotify.com/artist/';
                $shared_url .= $href[2];

                $w->result(null, '', getenv('emoji_artist').' ' . escapeQuery($results[1]), 'Browse this artist', getArtistArtwork($w, $artist_uri, $results[1], false, false, false, $use_artworks), 'no', array(false, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '', 'copy' => '#NowPlaying artist ' . escapeQuery($results[1]) . ' ' . $shared_url, 'largetype' => escapeQuery($results[1]),), 'Artist▹' . $artist_uri . '∙' . escapeQuery($results[1]) . '▹');

            }
            else {
                // artist is not in library
                $artist_uri = getArtistUriFromTrack($w, $results[4]);

                $href = explode(':', $artist_uri);
                $shared_url .= ' https://open.spotify.com/artist/';
                $shared_url .= $href[2];

                $w->result(null, '', getenv('emoji_artist').' ' . escapeQuery($results[1]), 'Browse this artist', getArtistArtwork($w, $artist_uri /* empty artist_uri */, $results[1], false, false, false, $use_artworks), 'no', array(false, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '', 'copy' => '#NowPlaying artist ' . escapeQuery($results[1]) . ' ' . $shared_url, 'largetype' => escapeQuery($results[1]),), 'Artist▹' . $results[4] . '∙' . escapeQuery($results[1]) . '▹');
            }
        }

        if (countCharacters($input) < 2 || strpos(strtolower('play album'), strtolower($input)) !== false) {

            $album_uri = getAlbumUriFromTrack($w, $results[4]);

            $shared_url = '';
            if ($album_uri != false) {
                $href = explode(':', $album_uri);
                $shared_url .= ' https://open.spotify.com/album/';
                $shared_url .= $href[2];
            }

            // use track uri here
            $album_artwork_path = getTrackOrAlbumArtwork($w, $results[4], false, false, false, $use_artworks);
            $w->result(null, serialize(array($results[4] /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'playalbum'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, escapeQuery($results[2]) /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, $album_artwork_path
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), getenv('emoji_album').' ' . escapeQuery($results[2]), 'Play album', $album_artwork_path, 'yes', array('copy' => '#NowPlaying album ' . escapeQuery($results[2]) . ' ' . $shared_url, 'largetype' => escapeQuery($results[2]),), '');
        }

        // use track uri here
        if (countCharacters($input) < 2 || strpos(strtolower('query lookup online'), strtolower($input)) !== false) {
            $w->result(null, '', getenv('emoji_album').' ' . escapeQuery($results[2]), array(getenv('emoji_online').' Query all tracks from this album online..', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/online_album.png', 'no', null, 'Online▹' . $results[4] . '@' . escapeQuery($results[1]) . '@' . $results[4] . '@' . escapeQuery($results[2]) . '▹');
        }

        if (countCharacters($input) < 2 || strpos(strtolower('get lyrics'), strtolower($input)) !== false) {
            if ($always_display_lyrics_in_browser == false) {
                $w->result(null, '', 'Get Lyrics for track ' . escapeQuery($results[0]), array('This will fetch lyrics online and display them in Alfred', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'no', null, 'Lyrics▹' . $results[4] . '∙' . escapeQuery($results[1]) . '∙' . escapeQuery($results[0]));
            }
            else {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'lyrics'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Get Lyrics for track ' . escapeQuery($results[0]), array('This will display them in default browser', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'yes', null, '');
            }
        }

        if ($update_in_progress == false) {
            if (countCharacters($input) < 2 || strpos(strtolower('add'), strtolower($input)) !== false) {
                $w->result(null, '', 'Add track ' . escapeQuery($results[0]) . ' to...', array('This will add current track to Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/add.png', 'no', null, 'Add▹' . $results[4] . '∙' . escapeQuery($results[0]) . '▹');
            }

            if (countCharacters($input) < 2 || strpos(strtolower('remove'), strtolower($input)) !== false) {
                $w->result(null, '', 'Remove track ' . escapeQuery($results[0]) . ' from...', array('This will remove current track from Your Music or a playlist you will choose in next step', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/remove.png', 'no', null, 'Remove▹' . $results[4] . '∙' . escapeQuery($results[0]) . '▹');
            }

            $privacy_status = 'private';
            if ($is_public_playlists) {
                $privacy_status = 'public';
            }
            if (countCharacters($input) < 2 || strpos(strtolower('song radio'), strtolower($input)) !== false) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'current_track_radio'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Create a Song Radio Playlist based on ' . escapeQuery($results[0]), array('This will create a ' . $privacy_status . ' song radio playlist with ' . $radio_number_tracks . ' tracks for the current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/radio_song.png', 'yes', null, '');
            }
        }

        if (countCharacters($input) < 2 || strpos(strtolower('share'), strtolower($input)) !== false) {

            $osx_version = exec('sw_vers -productVersion');
            if (version_compare($osx_version, '10,14', '<')) {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'share'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'Share current track using Mac OS X Sharing ', array('This will open the Mac OS X Sharing for the current track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/share.png', 'yes', null, '');
            }
        }

        if (countCharacters($input) < 2 || strpos(strtolower('web search'), strtolower($input)) !== false) {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'web_search'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Do a web search for current track or artist on Youtube, Facebook, etc.. ', array('You will be prompted to choose the web service you want to use', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/youtube.png', 'yes', null, '');
        }

        if (countCharacters($input) < 2) {
            if ($all_playlists == true) {
                $getTracks = 'select playlist_uri from tracks where uri=:uri limit ' . $max_results;
                try {
                    $stmtgetTracks = $db->prepare($getTracks);
                    $stmtgetTracks->bindValue(':uri', $results[4]);
                    $stmtgetTracks->execute();
                }
                catch(PDOException $e) {
                    handleDbIssuePdoXml($e);

                    return;
                }

                while ($track = $stmtgetTracks->fetch()) {
                    if ($track[0] == '') {
                        // The track is in Your Music
                        $w->result(null, '', 'In "Your Music"', array('The track is in Your Music', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/yourmusic.png', 'no', null, 'Your Music▹Tracks▹' . escapeQuery($results[0]));
                    }
                    else {
                        $getPlaylists = 'select uri,name,nb_tracks,author,username,playlist_artwork_path,ownedbyuser,nb_playable_tracks,duration_playlist,collaborative,public from playlists where uri=:uri';

                        try {
                            $stmtGetPlaylists = $db->prepare($getPlaylists);
                            $stmtGetPlaylists->bindValue(':uri', $track[0]);
                            $playlists = $stmtGetPlaylists->execute();
                        }
                        catch(PDOException $e) {
                            handleDbIssuePdoXml($e);

                            return;
                        }

                        while ($playlist = $stmtGetPlaylists->fetch()) {
                            $added = ' ';
                            if (startswith($playlist[1], 'Artist radio for')) {
                                $added = getenv('emoji_radio').' ';
                            }
                            if (checkIfResultAlreadyThere($w->results(), getenv('emoji_playlist') . $added . 'In playlist ' . $playlist[1]) == false) {
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
                                $w->result(null, '', getenv('emoji_playlist') . $added . 'In playlist ' . $playlist[1], array($public_status . ' playlist by ' . $playlist[3] . ' '.getenv('emoji_separator').' ' . $playlist[7] . ' tracks '.getenv('emoji_separator').' ' . $playlist[8], 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $playlist[5], 'no', null, 'Playlist▹' . $playlist[0] . '▹');
                            }
                        }
                    }
                }
            }
        }
    }
    else {
        $w->result(null, 'help', 'There is no track currently playing', array('Launch a track and come back here', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
    }
}

/**
 * firstDelimiterSpotifyConnect function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterSpotifyConnect($w, $query, $settings, $db, $update_in_progress) {

    $settings = getSettings($w);

    $preferred_spotify_connect_device = $settings->preferred_spotify_connect_device;

    $retry = true;
    $nb_retry = 0;
    while ($retry) {
        try {
            $api = getSpotifyWebAPI($w);

            $noresult = true;

            $savedDevices = array();
            $devices = $api->getMyDevices();
            $retry = false;
            if (isset($devices->devices)) {
                foreach ($devices->devices as $device) {
                    if ($device->is_active) {
                        array_unshift($savedDevices, $device);
                    }
                    else {
                        $savedDevices[] = $device;
                    }
                    $noresult = false;
                }
            }

            if (!$noresult) {
                $w->result(null, '', 'Select one of your Spotify Connect devices', array('Select one of your Spotify Connect devices below as your listening device', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'no', null, '');

                foreach ($savedDevices as $device) {
                    $added = '';
                    if ($device->name == $preferred_spotify_connect_device) {
                        $added .= '🌟';
                    }
                    if ($device->is_active) {
                        $added .= getenv('emoji_playing').' ';
                    }
                    if ($device->type == 'Computer') {
                        $icon = './images/computer.png';
                    }
                    else if ($device->type == 'Smartphone') {
                        $icon = './images/smartphone.png';
                    }
                    else {
                        $icon = './images/speaker.png';
                    }
                    $volume = '';
                    if (isset($device->volume_percent)) {
                        $volume = '- volume: ' . floatToSquares($device->volume_percent / 100, 5);
                    }
                    if ($device->is_restricted) {
                        $w->result(null, 'help', $added . $device->type . ' ' . $device->name . ' cannot be controlled', array('⚠ This device cannot be controlled by Spotify WEB API', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'no', null, '');
                    }
                    else {
                        if (!$device->is_active) {
                            $connect_switch_string = getenv('connect_switch_string');
                            $connect_switch_string  = str_replace('{device_type}', $device->type, $connect_switch_string);
                            $connect_switch_string  = str_replace('{device_name}', $device->name, $connect_switch_string);
                            $connect_switch_string  = str_replace('{volume}', $volume, $connect_switch_string);
                            $w->result(null, serialize(array(''
                            /*track_uri*/, ''
                            /* album_uri */, ''
                            /* artist_uri */, ''
                            /* playlist_uri */, ''
                            /* spotify_command */, ''
                            /* query */, 'CHANGE_DEVICE▹' . $device->id /* other_settings*/, ''
                            /* other_action */, ''
                            /* artist_name */, ''
                            /* track_name */, ''
                            /* album_name */, ''
                            /* track_artwork_path */, ''
                            /* artist_artwork_path */, ''
                            /* album_artwork_path */, ''
                            /* playlist_name */, '', /* playlist_artwork_path */
                            )), $added . $connect_switch_string, array('Type enter to validate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'yes', null, '');
                        }
                        else {
                            $w->result(null, 'help', $added . ' ' . $device->type . ' ' . $device->name . ' is currently active ' . $volume, array('This device is the currently active device', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'no', null, '');
                        }
                    }
                }
                $w->result(null, '', 'Select your preferred Spotify Connect device', array('It will be used by default if there is no other active device', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/settings.png', 'no', null, 'Spotify Connect Preferred Device▹');
            }
            else {
                $w->result(null, 'help', 'There was no Spotify Connect device found!', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                if (isSpotifyAppInstalled()) {
                    $w->result('SpotifyMiniPlayer_' . 'open_spotify_app', serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'open_spotify_app'
                    /* other_action */, ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Open Spotify application', 'This will open Spotify', './images/spotify.png', 'yes', null, '');
                }
            }
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->getMessage() == 'Permissions missing') {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'reset_oauth_settings'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'The workflow needs more privilages to do this, click to restart authentication', array('Next time you invoke the workflow, you will have to re-authenticate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'yes', null, '');
                echo $w->tojson();
                exit;
            }
            else {
                if ($e->getCode() == 404) {
                    $retry = false;
                    $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                    echo $w->tojson();
                    exit;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 504) {
                    // retry
                    if ($nb_retry > 3) {
                        $retry = false;
                        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

                        echo $w->tojson();
                        exit;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    $retry = false;
                    $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                    echo $w->tojson();
                    exit;
                }
            }
        }
    }
}


/**
 * firstDelimiterSpotifyConnectPreferredDevice function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterSpotifyConnectPreferredDevice($w, $query, $settings, $db, $update_in_progress) {

    $settings = getSettings($w);

    $preferred_spotify_connect_device = $settings->preferred_spotify_connect_device;

    $retry = true;
    $nb_retry = 0;
    while ($retry) {
        try {
            $api = getSpotifyWebAPI($w);

            $noresult = true;

            $savedDevices = array();
            $devices = $api->getMyDevices();
            $retry = false;
            if (isset($devices->devices)) {
                foreach ($devices->devices as $device) {
                    if ($device->is_active) {
                        array_unshift($savedDevices, $device);
                    }
                    else {
                        $savedDevices[] = $device;
                    }
                    $noresult = false;
                }
            }

            if (!$noresult) {
                $w->result(null, '', 'Select one of your Spotify Connect devices as preferred device', array('Select one of your Spotify Connect devices below as your preferred device', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/connect.png', 'no', null, '');

                foreach ($savedDevices as $device) {
                    if ($device->type == 'Computer') {
                        $icon = './images/computer.png';
                    }
                    else if ($device->type == 'Smartphone') {
                        $icon = './images/smartphone.png';
                    }
                    else {
                        $icon = './images/speaker.png';
                    }
                    if ($device->is_restricted) {
                        $w->result(null, 'help', $device->type . ' ' . $device->name . ' cannot be controlled', array('⚠ This device cannot be controlled by Spotify WEB API', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'no', null, '');
                    } elseif ($device->name == $preferred_spotify_connect_device) {
                        $w->result(null, 'help', $device->type . ' ' . $device->name . ' is already your preferred device 🌟', array('Choose another one in the list', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'no', null, '');
                    }
                    else {
                        $w->result(null, serialize(array(''
                        /*track_uri*/, ''
                        /* album_uri */, ''
                        /* artist_uri */, ''
                        /* playlist_uri */, ''
                        /* spotify_command */, ''
                        /* query */, 'CHANGE_PREFERRED_DEVICE▹' . $device->name /* other_settings*/, ''
                        /* other_action */, ''
                        /* artist_name */, ''
                        /* track_name */, ''
                        /* album_name */, ''
                        /* track_artwork_path */, ''
                        /* artist_artwork_path */, ''
                        /* album_artwork_path */, ''
                        /* playlist_name */, '', /* playlist_artwork_path */
                        )), $device->type . ' ' . $device->name, array('Type enter to validate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $icon, 'yes', null, '');
                    }
                }

            }
            else {
                $w->result(null, 'help', 'There was no Spotify Connect device found!', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                if (isSpotifyAppInstalled()) {
                    $w->result('SpotifyMiniPlayer_' . 'open_spotify_app', serialize(array(''
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'open_spotify_app'
                    /* other_action */, ''
                    /* artist_name */, ''
                    /* track_name */, ''
                    /* album_name */, ''
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, ''
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), 'Open Spotify application', 'This will open Spotify', './images/spotify.png', 'yes', null, '');
                }
            }
        }
        catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
            if ($e->getMessage() == 'Permissions missing') {
                $w->result(null, serialize(array(''
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'reset_oauth_settings'
                /* other_action */, ''
                /* artist_name */, ''
                /* track_name */, ''
                /* album_name */, ''
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), 'The workflow needs more privilages to do this, click to restart authentication', array('Next time you invoke the workflow, you will have to re-authenticate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'yes', null, '');
                echo $w->tojson();
                exit;
            }
            else {
                if ($e->getCode() == 404) {
                    $retry = false;
                    $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                    echo $w->tojson();
                    exit;
                }
                else if ($e->getCode() == 500 || $e->getCode() == 502 || $e->getCode() == 503 || $e->getCode() == 202 || $e->getCode() == 400 || $e->getCode() == 504) {
                    // retry
                    if ($nb_retry > 3) {
                        $retry = false;
                        $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');

                        echo $w->tojson();
                        exit;
                    }
                    ++$nb_retry;
                    sleep(5);
                }
                else {
                    $retry = false;
                    $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
                    echo $w->tojson();
                    exit;
                }
            }
        }
    }

}
/**
 * firstDelimiterYourMusic function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterYourMusic($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $max_results = $settings->max_results;
    $fuzzy_search = $settings->fuzzy_search;

    $thequery = $words[1];

    if (countCharacters($thequery) < 2) {
        if($update_in_progress && file_exists($w->data() . '/create_library')) {
            $results = getExternalResults($w, 'counters', array('all_tracks','yourmusic_tracks','all_artists','yourmusic_artists','all_albums','yourmusic_albums','playlists','shows','episodes'), '', 'where id=0');
        } else {
            $getCounters = 'select all_tracks,yourmusic_tracks,all_artists,yourmusic_artists,all_albums,yourmusic_albums,playlists,shows,episodes from counters where id=0';
            try {
                $stmt = $db->prepare($getCounters);

                $stmt->execute();
                $results = $stmt->fetchAll();
            }
            catch(PDOException $e) {
                handleDbIssuePdoXml($e);

                exit;
            }
        }
        $counters = $results[0];

        $yourmusic_tracks = $counters[1];
        $yourmusic_artists = $counters[3];
        $yourmusic_albums = $counters[5];

        $w->result(null, '', 'Liked songs', array('Browse your ' . $yourmusic_tracks . ' tracks in Your Music', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/tracks.png', 'no', null, 'Your Music▹Tracks▹');
        $w->result(null, '', 'Albums', array('Browse your ' . $yourmusic_albums . ' albums in Your Music', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/albums.png', 'no', null, 'Your Music▹Albums▹');
        $w->result(null, '', 'Artists', array('Browse your ' . $yourmusic_artists . ' artists in Your Music', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/artists.png', 'no', null, 'Your Music▹Artists▹');
    }
    else {

        // Search artists
        if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
            $results = getFuzzySearchResults($w, $update_in_progress, $query, 'followed_artists', array('name','artist_artwork_path','uri'), $max_results, '1', '');
        } else {
            // Search artists
            $getArtists = 'select name,artist_artwork_path,uri from followed_artists where name_deburr like :name limit ' . $max_results;
            $stmt = $db->prepare($getArtists);
            $stmt->bindValue(':name', '%' . deburr($thequery) . '%');
            try {
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
            catch(PDOException $e) {
                handleDbIssuePdoXml($e);

                return;
            }
        }

        $noresult = true;
        foreach ($results as $artists) {
            if (checkIfResultAlreadyThere($w->results(), getenv('emoji_artist').' ' . $artists[0]) == false) {
                $noresult = false;
                $w->result(null, '', getenv('emoji_artist').' ' . $artists[0], array('Browse this artist', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $artists[1], 'no', null, 'Artist▹' . $artists[2] . '∙' . $artists[0] . '▹');
            }
        }

        // Search everything
        if($fuzzy_search || ($update_in_progress && file_exists($w->data() . '/create_library'))) {
            $results = getFuzzySearchResults($w, $update_in_progress, $thequery, 'tracks', array('yourmusic', 'popularity', 'uri', 'album_uri', 'artist_uri', 'track_name', 'album_name', 'artist_name', 'album_type', 'track_artwork_path', 'artist_artwork_path', 'album_artwork_path', 'playlist_name', 'playlist_uri', 'playable', 'added_at', 'duration', 'nb_times_played', 'local_track'), $max_results, '6..8', 'where yourmusic=1');
        } else {
            $getTracks = 'select yourmusic, popularity, uri, album_uri, artist_uri, track_name, album_name, artist_name, album_type, track_artwork_path, artist_artwork_path, album_artwork_path, playlist_name, playlist_uri, playable, added_at, duration, nb_times_played, local_track from tracks where yourmusic=1 and (artist_name_deburr like :query or album_name_deburr like :query or track_name_deburr like :query)' . ' limit ' . $max_results;
            $stmt = $db->prepare($getTracks);
            $stmt->bindValue(':query', '%' . deburr($thequery) . '%');
            try {
                $stmt->execute();
                $results = $stmt->fetchAll();
            }
            catch(PDOException $e) {
                handleDbIssuePdoXml($e);

                return;
            }
        }

        foreach ($results as $track) {
            $noresult = false;
            $subtitle = $track[6];

            if (checkIfResultAlreadyThere($w->results(), $track[7] . ' '.getenv('emoji_separator').' ' . $track[5]) == false) {
                $w->result(null, serialize(array($track[2] /*track_uri*/, $track[3] /* album_uri */, $track[4] /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, ''
                /* other_action */, $track[7] /* artist_name */, $track[5] /* track_name */, $track[6] /* album_name */, $track[9] /* track_artwork_path */, $track[10] /* artist_artwork_path */, $track[11] /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], $arrayresult = array($track[16] . ' '.getenv('emoji_separator').' ' . $subtitle . getPlaylistsForTrack($db, $track[2]), 'alt' => 'Play album ' . $track[6] . ' in Spotify', 'cmd' => 'Play artist ' . $track[7] . ' in Spotify', 'fn' => 'Add track ' . $track[5] . ' to ...', 'shift' => 'Add album ' . $track[6] . ' to ...', 'ctrl' => 'Search artist ' . $track[7] . ' online',), $track[9], 'yes', array('copy' => $track[7] . ' '.getenv('emoji_separator').' ' . $track[5], 'largetype' => $track[7] . ' '.getenv('emoji_separator').' ' . $track[5],), '');
            }
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no result for your search', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }
}

/**
 * firstDelimiterLyrics function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterLyrics($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);

    $use_artworks = $settings->use_artworks;

    if (substr_count($query, '∙') == 2) {

        // Search Lyrics
        $tmp = $words[1];
        $words = explode('∙', $tmp);
        $track_uri = $words[0];
        $artist_name = $words[1];
        $track_name = $words[2];

        list($lyrics_url, $lyrics) = getLyrics($w, $artist_name, $track_name);
        stathat_ez_count('AlfredSpotifyMiniPlayer', 'lyrics', 1);

        if ($lyrics_url != false) {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . $lyrics_url /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'See lyrics for ' . $track_name . ' by ' . $artist_name . ' online', 'This will open your default browser', './images/lyrics.png', 'yes', null, '');

            $track_artwork = getTrackOrAlbumArtwork($w, $track_uri, false, false, false, $use_artworks);

            $wrapped = wordwrap($lyrics, 70, "\n", false);
            $lyrics_sentances = explode("\n", $wrapped);

            for ($i = 0;$i < count($lyrics_sentances);++$i) {
                $w->result(null, '', $lyrics_sentances[$i], array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track_artwork, 'no', null, '');
            }
        }
        else {
            $w->result(null, 'help', 'No lyrics found!', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            echo $w->tojson();
            exit;
        }
    }
}

/**
 * firstDelimiterSettings function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterSettings($w, $query, $settings, $db, $update_in_progress) {
    $all_playlists = $settings->all_playlists;
    $is_alfred_playlist_active = $settings->is_alfred_playlist_active;
    $radio_number_tracks = $settings->radio_number_tracks;
    $now_playing_notifications = $settings->now_playing_notifications;
    $max_results = $settings->max_results;
    $last_check_update_time = $settings->last_check_update_time;
    $userid = $settings->userid;
    $is_public_playlists = $settings->is_public_playlists;
    $quick_mode = $settings->quick_mode;
    $output_application = $settings->output_application;
    $mopidy_server = $settings->mopidy_server;
    $mopidy_port = $settings->mopidy_port;
    $is_display_rating = $settings->is_display_rating;
    $volume_percent = $settings->volume_percent;
    $is_autoplay_playlist = $settings->is_autoplay_playlist;
    $use_growl = $settings->use_growl;
    $use_artworks = $settings->use_artworks;
    $use_facebook = $settings->use_facebook;
    $always_display_lyrics_in_browser = $settings->always_display_lyrics_in_browser;
    $automatic_refresh_library_interval = $settings->automatic_refresh_library_interval;
    $fuzzy_search = $settings->fuzzy_search;
    $artwork_folder_size = $settings->artwork_folder_size;
    $podcasts_enabled = $settings->podcasts_enabled;

    if ($update_in_progress == false) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'refresh_library'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Refresh your library', array('Do this when your library has changed (outside the scope of this workflow)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/update.png', 'yes', null, '');
    }

    $w->result(null, '', 'Switch Spotify user (currently ' . $userid . ')', array('Switch to another Spotify user', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), getUserArtwork($w, $userid), 'no', null, 'Settings▹Users▹');

    if ($is_alfred_playlist_active == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_alfred_playlist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Control Your Music', array('You will control Your Music (if disabled, you control Alfred Playlist)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/yourmusic.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_alfred_playlist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Control Alfred Playlist', array('You will control the Alfred Playlist (if disabled, you control Your Music)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/alfred_playlist.png', 'yes', null, '');
    }

    if ($all_playlists == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_all_playlist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Set Search Scope to Your Music only', array('Select to search only in "Your Music"', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search_scope_yourmusic_only.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_all_playlist'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Unset Search Scope to Your Music only', array('Select to search in your complete library ("Your Music" and all Playlists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search.png', 'yes', null, '');
    }
    $w->result(null, '', 'Configure Max Number of Results (currently ' . $max_results . ')', array('Number of results displayed (it does not apply to the list of your playlists)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/results_numbers.png', 'no', null, 'Settings▹MaxResults▹');
    $w->result(null, '', 'Configure Number of Radio tracks (currently ' . $radio_number_tracks . ')', array('Number of tracks when creating a Radio Playlist.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/radio_numbers.png', 'no', null, 'Settings▹RadioTracks▹');
    if($automatic_refresh_library_interval == 0) {
        $automatic_refresh_library_interval = 'disabled';
    } else {
        $automatic_refresh_library_interval = 'every '.$automatic_refresh_library_interval.' minutes';
    }
    $w->result(null, '', 'Configure Automatic Refresh of Library (currently ' . $automatic_refresh_library_interval . ')', array('Setup automatic refresh of your library in background)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/update.png', 'no', null, 'Settings▹AutomaticRefreshLibrary▹');
    $w->result(null, '', 'Configure Volume Percent (currently ' . $volume_percent . '%)', array('The percentage of volume which is increased or decreased.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/volume_up.png', 'no', null, 'Settings▹VolumePercentage▹');

    $w->result(null, '', 'Select the output: Spotify Connect or Spotify Desktop', array('Spotify Connect is for premium users only', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/speaker.png', 'no', null, 'Settings▹Output▹');

    if ($output_application == 'MOPIDY') {
        $w->result(null, '', 'Configure Mopidy server (currently ' . $mopidy_server . ')', array('Server name/ip where Mopidy server is running', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/mopidy_server.png', 'no', null, 'Settings▹MopidyServer▹');
        $w->result(null, '', 'Configure Mopidy port (currently ' . $mopidy_port . ')', array('TCP port where Mopidy server is running', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/mopidy_port.png', 'no', null, 'Settings▹MopidyPort▹');
    }

    if ($fuzzy_search == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_fuzzy_search'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Fuzzy Search '.getenv('emoji_fuzzy'), array('Use Regular Search', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_fuzzy_search'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Fuzzy Search '.getenv('emoji_fuzzy'), array('Enable Fuzzy Search', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search.png', 'yes', null, '');
    }

    if ($now_playing_notifications == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_now_playing_notifications'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Now Playing notifications', array('Do not display notifications for current playing track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_now_playing.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_now_playing_notifications'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Now Playing notifications', array('Display notifications for current playing track', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_now_playing.png', 'yes', null, '');
    }

    if ($quick_mode == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_quick_mode'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Quick Mode', array('Do not launch directly tracks/album/artists/playlists in main search', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_quick_mode.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_quick_mode'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Quick Mode', array('Launch directly tracks/album/artists/playlists in main search', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_quick_mode.png', 'yes', null, '');
    }

    if ($use_artworks == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_artworks'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Artworks (current cached folder size is ' . $artwork_folder_size . ')', array('All existing artworks will be deleted and workflow will only show default artworks (library will be re-created)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_artworks.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_artworks'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Artworks', array('Use Artworks for playlists, tracks, etc..(library will be re-created)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_artworks.png', 'yes', null, '');
    }

    if ($podcasts_enabled == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_podcasts'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Shows (podcasts)', array('Do not display shows', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/shows.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_podcasts'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Shows (podcasts)', array('Display shows', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/shows.png', 'yes', null, '');
    }

    if ($is_display_rating == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_display_rating'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Track Rating', array('Do not display track rating with stars in Current Track menu and notifications', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_display_rating.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_display_rating'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Track Rating', array('Display track rating with stars in Current Track menu and notifications', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_display_rating.png', 'yes', null, '');
    }

    if ($is_autoplay_playlist == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_autoplay'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Playlist Autoplay', array('Do not autoplay playlists (radios, similar playlists and complete collection) when they are created', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_autoplay.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_autoplay'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Playlist Autoplay', array('Autoplay playlists (radios, similar playlists and complete collection) when they are created', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_autoplay.png', 'yes', null, '');
    }

    if ($use_growl == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_use_growl'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Disable Growl', array('Use Notification Center instead of Growl', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_use_growl.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_use_growl'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Enable Growl', array('Use Growl instead of Notification Center', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_use_growl.png', 'yes', null, '');
    }

    if ($always_display_lyrics_in_browser == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_always_display_lyrics_in_browser'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Display lyrics in Alfred', array('Lyrics will be displayed in Alfred', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_always_display_lyrics_in_browser'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Display lyrics in Browser', array('Lyrics will be displayed in default browser', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/lyrics.png', 'yes', null, '');
    }

    $osx_version = exec('sw_vers -productVersion');
    if (version_compare($osx_version, '10,14', '<')) {
        if ($use_facebook == true) {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'use_twitter'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Use Twitter for sharing', array('Use Twitter instead of Facebook', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/twitter.png', 'yes', null, '');
        }
        else {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'use_facebook'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Enable Facebook for sharing', array('Use Facebook instead of Twitter', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/facebook.png', 'yes', null, '');
        }
    }

    if ($update_in_progress == false) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'create_library'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Re-Create your library from scratch', array('Do this when refresh library is not working as you would expect', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/recreate.png', 'yes', null, '');
    }

    if ($is_public_playlists == true) {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'disable_public_playlists'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Automatically make new playlists private', array('If disabled, the workflow will mark new playlists (created or followed) as private', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/disable_public_playlists.png', 'yes', null, '');
    }
    else {
        $w->result(null, serialize(array(''
        /*track_uri*/, ''
        /* album_uri */, ''
        /* artist_uri */, ''
        /* playlist_uri */, ''
        /* spotify_command */, ''
        /* query */, ''
        /* other_settings*/, 'enable_public_playlists'
        /* other_action */, ''
        /* artist_name */, ''
        /* track_name */, ''
        /* album_name */, ''
        /* track_artwork_path */, ''
        /* artist_artwork_path */, ''
        /* album_artwork_path */, ''
        /* playlist_name */, '', /* playlist_artwork_path */
        )), 'Automatically make new playlists public', array('If enabled, the workflow will mark new playlists (created or followed) as public', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/enable_public_playlists.png', 'yes', null, '');
    }

    $w->result(null, serialize(array(''
    /*track_uri*/, ''
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, ''
    /* other_settings*/, 'change_theme_color'
    /* other_action */, ''
    /* artist_name */, ''
    /* track_name */, ''
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, ''
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), 'Change theme color', array('All existing icons will be replaced by chosen color icons', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/change_theme_color.png', 'yes', null, '');

    $w->result(null, serialize(array(''
    /*track_uri*/, ''
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, ''
    /* other_settings*/, 'change_search_order'
    /* other_action */, ''
    /* artist_name */, ''
    /* track_name */, ''
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, ''
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), 'Change search order results', array('Choose order of search results between playlist, artist, track, album, show and episode', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/search.png', 'yes', null, '');

    $w->result(null, '', 'Check for workflow update', array('Last checked: ' . beautifyTime(time() - $last_check_update_time, true) . ' ago (note this is automatically done otherwise once per day)', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/check_update.png', 'no', null, 'Check for update...' . '▹');

    $w->result(null, serialize(array(''
    /*track_uri*/, ''
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/articles/customization/' /* other_settings*/, ''
    /* other_action */, ''
    /* artist_name */, ''
    /* track_name */, ''
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, ''
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), 'Missing a setting ? There are others described on the website', 'Find out all possible additional settings on the website', './images/website.png', 'yes', null, '');

    $w->result(null, serialize(array(''
    /*track_uri*/, ''
    /* album_uri */, ''
    /* artist_uri */, ''
    /* playlist_uri */, ''
    /* spotify_command */, ''
    /* query */, ''
    /* other_settings*/, 'open_debug_tools'
    /* other_action */, ''
    /* artist_name */, ''
    /* track_name */, ''
    /* album_name */, ''
    /* track_artwork_path */, ''
    /* artist_artwork_path */, ''
    /* album_artwork_path */, ''
    /* playlist_name */, '', /* playlist_artwork_path */
    )), 'Open debug tools', array('This is how you can access information required for further troubleshooting', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/debug.png', 'yes', null, '');
}

/**
 * firstDelimiterCheckForUpdate function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterCheckForUpdate($w, $query, $settings, $db, $update_in_progress) {
    $check_results = checkForUpdate($w, 0);
    if ($check_results != null && is_array($check_results)) {
        if($check_results[0] != '') {
            $w->result(null, '', 'New version ' . $check_results[0] . ' is available !', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . $check_results[1] /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Click to open and install the new version', 'This will open the new version of the Spotify Mini Player workflow', './images/alfred-workflow-icon.png', 'yes', null, '');
        }
    }
    elseif ($check_results == null) {
        $w->result(null, '', 'No update available', array('You are good to go!', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
    }
    else {
        $w->result(null, '', 'Error happened : ' . $check_results, array('The check for workflow update could not be done', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        echo $w->tojson();
        exit;
    }
    echo $w->tojson();
    exit;
}

/**
 * firstDelimiterPlayQueue function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterPlayQueue($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[1];

    $output_application = $settings->output_application;
    $use_artworks = $settings->use_artworks;

    if ($output_application == 'MOPIDY') {
        $playqueue = $w->read('playqueue.json');
        if ($playqueue == false) {
            $w->result(null, 'help', array('There is no track in the play queue', 'Make sure to always use the workflow to launch tracks, playlists etc..Internet connectivity is also required', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Learn more about Play Queue', 'Find out all information about Play Queue on alfred-spotify-mini-player.com', './images/website.png', 'yes', null, '');
            echo $w->tojson();
            exit;
        }
        $tl_tracks = invokeMopidyMethod($w, 'core.tracklist.get_tl_tracks', array());
        $current_tl_track = invokeMopidyMethod($w, 'core.playback.get_current_tl_track', array());

        $isShuffleEnabled = invokeMopidyMethod($w, 'core.tracklist.get_random', array());
        if ($isShuffleEnabled) {
            $w->result(null, 'help', 'Shuffle is enabled', array('The order of tracks presented below is not relevant', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
        $noresult = true;
        $firstTime = true;
        $nb_tracks = 0;
        $track_name = '';
        $album_name = '';
        $playlist_name = '';
        $current_track_found = false;
        $current_track_index = 0;
        foreach ($tl_tracks as $tl_track) {
            ++$current_track_index;
            if ($current_track_found == false && $tl_track->tlid == $current_tl_track->tlid) {
                $current_track_found = true;
            }
            if ($current_track_found == false && $tl_track->tlid != $current_tl_track->tlid) {
                continue;
            }
            if ($firstTime == true) {
                $added = getenv('emoji_playing').' ';
                if ($playqueue->type == 'playlist') {
                    $playlist_name = $playqueue->name;
                }
                elseif ($playqueue->type == 'album') {
                    $album_name = $playqueue->name;
                }
                elseif ($playqueue->type == 'track') {
                    $track_name = $playqueue->name;
                }
                if (countCharacters($search) < 2) {
                    $w->result(null, 'help', 'Playing from: ' . $playqueue->type . ' ' . $playqueue->name, array('Track ' . $current_track_index . ' on ' . count($tl_tracks) . ' tracks queued', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/play_queue.png', 'no', null, '');
                }
            }
            $firstTime = false;
            $max_tracks_displayed = 150;
            if ($nb_tracks >= $max_tracks_displayed) {
                if (countCharacters($search) < 2) {
                    $w->result(null, 'help', '[...] ' . (count($tl_tracks) - $max_tracks_displayed) . ' additional tracks are in the queue', array('A maximum of ' . $max_tracks_displayed . ' tracks is displayed.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
                }
                break;
            }
            $track_name = '';
            if (isset($tl_track
                ->track
                ->name)) {
                $track_name = $tl_track
                    ->track->name;
            }
            $artist_name = '';
            if (isset($tl_track
                ->track
                ->artists[0]
                ->name)) {
                $artist_name = $tl_track
                    ->track
                    ->artists[0]->name;
            }
            $album_name = '';
            if (isset($tl_track
                ->track
                ->album
                ->name)) {
                $album_name = $tl_track
                    ->track
                    ->album->name;
            }
            $duration = 'na';
            if (isset($tl_track
                ->track
                ->length)) {
                $duration = beautifyTime($tl_track
                    ->track->length / 1000);
            }
            $track_artwork = getTrackOrAlbumArtwork($w, $tl_track
                ->track->uri, false, false, false, $use_artworks);

            if (strpos($track_name, '[unplayable]') !== false) {
                $track_name = str_replace('[unplayable]', '', $track_name);
                if (countCharacters($search) < 2 || strpos(strtolower($artist_name), strtolower($search)) !== false || strpos(strtolower($track_name), strtolower($search)) !== false || strpos(strtolower($album_name), strtolower($search)) !== false) {
                    $w->result(null, '', getenv('emoji_not_playable').' ' . escapeQuery($artist_name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track_name), array($duration . ' '.getenv('emoji_separator').' ' . $album_name, 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), $track_artwork, 'no', null, '');
                }
            }
            else {
                if (countCharacters($search) < 2 || strpos(strtolower($artist_name), strtolower($search)) !== false || strpos(strtolower($track_name), strtolower($search)) !== false || strpos(strtolower($album_name), strtolower($search)) !== false) {
                    $w->result(null, serialize(array($tl_track
                        ->track->uri
                    /*track_uri*/, ''
                    /* album_uri */, ''
                    /* artist_uri */, ''
                    /* playlist_uri */, ''
                    /* spotify_command */, ''
                    /* query */, ''
                    /* other_settings*/, 'play_track_from_play_queue'
                    /* other_action */, escapeQuery($artist_name) /* artist_name */, escapeQuery($track_name) /* track_name */, escapeQuery($album_name) /* album_name */, $track_artwork
                    /* track_artwork_path */, ''
                    /* artist_artwork_path */, ''
                    /* album_artwork_path */, $playlist_name
                    /* playlist_name */, '', /* playlist_artwork_path */
                    )), $added . escapeQuery($artist_name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track_name), array($duration . ' '.getenv('emoji_separator').' ' . escapeQuery($album_name), 'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track_name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online',), $track_artwork, 'yes', null, '');
                }
            }
            $noresult = false;
            $added = '';
            $nb_tracks += 1;
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no track in the play queue from Mopidy', array('Make sure to always use the workflow to launch tracks, playlists etc..Internet connectivity is also required', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Learn more about Play Queue', 'Find out all information about Play Queue on alfred-spotify-mini-player.com', './images/website.png', 'yes', null, '');
            echo $w->tojson();
            exit;
        }
    }
    else if ($output_application == 'APPLESCRIPT' || $output_application == 'CONNECT') {
        $playqueue = $w->read('playqueue.json');
        if ($playqueue == false) {
            $w->result(null, 'help', 'There is no track in the play queue', array('Make sure to always use the workflow to launch tracks, playlists etc..Internet connectivity is also required', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Learn more about Play Queue', 'Find out all information about Play Queue on alfred-spotify-mini-player.com', './images/website.png', 'yes', null, '');
            echo $w->tojson();
            exit;
        }
        if (isShuffleActive(false) == 'true') {
            if (countCharacters($search) < 2) {
                $w->result(null, 'help', 'Shuffle is enabled', array('The order of tracks presented below is not relevant', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            }
        }
        $noresult = true;
        $nb_tracks = 0;
        $track_name = '';
        $album_name = '';
        $playlist_name = '';
        for ($i = $playqueue->current_track_index;$i < count($playqueue->tracks);++$i) {
            $track = $playqueue->tracks[$i];
            if ($noresult == true) {
                $added = getenv('emoji_playing').' ';
                if ($playqueue->type == 'playlist') {
                    $playlist_name = $playqueue->name;
                }
                elseif ($playqueue->type == 'album') {
                    $album_name = $playqueue->name;
                }
                elseif ($playqueue->type == 'track') {
                    $track_name = $playqueue->name;
                }
                if (countCharacters($search) < 2) {
                    $w->result(null, 'help', 'Playing from: ' . $playqueue->type . ' ' . $playqueue->name, array('Track ' . ($playqueue->current_track_index + 1) . ' on ' . count($playqueue->tracks) . ' tracks queued', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/play_queue.png', 'no', null, '');
                }
            }
            $max_tracks_displayed = 150;
            if ($nb_tracks >= $max_tracks_displayed) {
                if (countCharacters($search) < 2) {
                    $w->result(null, 'help', '[...] ' . (count($playqueue->tracks) - $max_tracks_displayed) . ' additional tracks are in the queue', array('A maximum of ' . $max_tracks_displayed . ' tracks is displayed.', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/info.png', 'no', null, '');
                }
                break;
            }
            $track_name = '';
            if (isset($track->name)) {
                $track_name = $track->name;
            }
            $artist_name = '';
            if (isset($track->artists[0]
                ->name)) {
                $artist_name = $track->artists[0]->name;
            }
            $album_name = '';
            if (isset($track
                ->album
                ->name)) {
                $album_name = $track
                    ->album->name;
            }
            $duration = 'na';
            if (isset($track->duration_ms)) {
                $duration = beautifyTime($track->duration_ms / 1000);
            }
            if (isset($track->duration)) {
                $duration = $track->duration;
            }
            $track_artwork = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
            if (countCharacters($search) < 2 || strpos(strtolower($artist_name), strtolower($search)) !== false || strpos(strtolower($track_name), strtolower($search)) !== false || strpos(strtolower($album_name), strtolower($search)) !== false) {
                $w->result(null, serialize(array($track->uri
                /*track_uri*/, ''
                /* album_uri */, ''
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, 'play_track_from_play_queue'
                /* other_action */, escapeQuery($artist_name) /* artist_name */, escapeQuery($track_name) /* track_name */, escapeQuery($album_name) /* album_name */, $track_artwork
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, $playlist_name
                /* playlist_name */, '', /* playlist_artwork_path */
                )), $added . escapeQuery($artist_name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track_name), array($duration . ' '.getenv('emoji_separator').' ' . escapeQuery($album_name), 'alt' => 'Play album ' . escapeQuery($album_name) . ' in Spotify', 'cmd' => 'Play artist ' . escapeQuery($artist_name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => 'Add album ' . escapeQuery($album_name) . ' to ...', 'ctrl' => 'Search artist ' . escapeQuery($artist_name) . ' online',), $track_artwork, 'yes', null, '');
            }
            $noresult = false;
            $added = '';
            $nb_tracks += 1;
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no track in the play queue', array('Make sure to always use the workflow to launch tracks, playlists etc..Internet connectivity is also required', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, 'Open▹' . 'http://alfred-spotify-mini-player.com/articles/play-queue/' /* other_settings*/, ''
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'Learn more about Play Queue', 'Find out all information about Play Queue on alfred-spotify-mini-player.com', './images/website.png', 'yes', null, '');
            echo $w->tojson();
            exit;
        }
    }
}

/**
 * firstDelimiterBrowse function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterBrowse($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[1];

    $country_code = $settings->country_code;

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName($country_code)), strtolower($search)) !== false) {
        $w->result(null, '', getCountryName($country_code), array('Browse the Spotify categories in ' . getCountryName($country_code), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹' . $country_code . '▹');
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('US')), strtolower($search)) !== false) {
        if ($country_code != 'US') {
            $w->result(null, '', getCountryName('US'), array('Browse the Spotify categories in ' . getCountryName('US'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹US▹');
        }
    }

    if (countCharacters($search) < 2 || strpos(strtolower(getCountryName('GB')), strtolower($search)) !== false) {
        if ($country_code != 'GB') {
            $w->result(null, '', getCountryName('GB'), array('Browse the Spotify categories in ' . getCountryName('GB'), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹GB▹');
        }
    }
    if (countCharacters($search) < 2 || strpos(strtolower('Choose Another country'), strtolower($search)) !== false) {
        $w->result(null, '', 'Choose Another country', array('Browse the Spotify categories in another country of your choice', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/browse.png', 'no', null, 'Browse▹Choose a Country▹');
    }
}

/**
 * firstDelimiterYourTops function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterYourTops($w, $query, $settings, $db, $update_in_progress) {
    $w->result(null, '', 'Get your top artists (last 4 weeks)', array('Get your top artists for last 4 weeks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹short_term');

    $w->result(null, '', 'Get your top artists (last 6 months)', array('Get your top artists for last 6 months', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹medium_term');

    $w->result(null, '', 'Get your top artists (all time)', array('Get your top artists for all time', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_artists.png', 'no', null, 'Your Tops▹Artists▹long_term');

    $w->result(null, '', 'Get your top tracks (last 4 weeks)', array('Get your top tracks for last 4 weeks', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹short_term');

    $w->result(null, '', 'Get your top tracks (last 6 months)', array('Get your top tracks for last 6 months', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹medium_term');

    $w->result(null, '', 'Get your top tracks (all time)', array('Get your top tracks for all time', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/your_tops_tracks.png', 'no', null, 'Your Tops▹Tracks▹long_term');
}

/**
 * firstDelimiterYourRecentTracks function.
 *
 * @param mixed $w
 * @param mixed $query
 * @param mixed $settings
 * @param mixed $db
 * @param mixed $update_in_progress
 */
function firstDelimiterYourRecentTracks($w, $query, $settings, $db, $update_in_progress) {
    $words = explode('▹', $query);
    $search = $words[1];

    $max_results = $settings->max_results;
    $use_artworks = $settings->use_artworks;

    try {
        $api = getSpotifyWebAPI($w);

        $recentTracks = $api->getMyRecentTracks(array('limit' => ($max_results <= 50) ? $max_results : 50,));

        $noresult = true;
        $items = $recentTracks->items;

        foreach ($items as $item) {

            $track = $item->track;
            $noresult = false;
            $artists = $track->artists;
            $artist = $artists[0];

            if (countCharacters($search) < 2 || strpos(strtolower($track->name), strtolower($search)) !== false || strpos(strtolower($artist->name), strtolower($search)) !== false) {
                $track_artwork_path = getTrackOrAlbumArtwork($w, $track->uri, false, false, false, $use_artworks);
                $w->result(null, serialize(array($track->uri
                /*track_uri*/, ''
                /* album_uri */, $artist->uri
                /* artist_uri */, ''
                /* playlist_uri */, ''
                /* spotify_command */, ''
                /* query */, ''
                /* other_settings*/, ''
                /* other_action */, escapeQuery($artist->name) /* artist_name */, escapeQuery($track->name) /* track_name */, ''
                /* album_name */, $track_artwork_path
                /* track_artwork_path */, ''
                /* artist_artwork_path */, ''
                /* album_artwork_path */, ''
                /* playlist_name */, '', /* playlist_artwork_path */
                )), escapeQuery($artist->name) . ' '.getenv('emoji_separator').' ' . escapeQuery($track->name), array(beautifyTime($track->duration_ms / 1000) . ' '.getenv('emoji_separator').' ' . time2str($item->played_at), 'alt' => '', 'cmd' => 'Play artist ' . escapeQuery($artist->name) . ' in Spotify', 'fn' => 'Add track ' . escapeQuery($track->name) . ' to ...', 'shift' => '', 'ctrl' => 'Search artist ' . escapeQuery($artist->name) . ' online',), $track_artwork_path, 'yes', null, '');
            }
        }

        if ($noresult) {
            $w->result(null, 'help', 'There is no result for your recent tracks', array('', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
    }
    catch(SpotifyWebAPI\SpotifyWebAPIException $e) {
        if ($e->getMessage() == 'Insufficient client scope') {
            $w->result(null, serialize(array(''
            /*track_uri*/, ''
            /* album_uri */, ''
            /* artist_uri */, ''
            /* playlist_uri */, ''
            /* spotify_command */, ''
            /* query */, ''
            /* other_settings*/, 'reset_oauth_settings'
            /* other_action */, ''
            /* artist_name */, ''
            /* track_name */, ''
            /* album_name */, ''
            /* track_artwork_path */, ''
            /* artist_artwork_path */, ''
            /* album_artwork_path */, ''
            /* playlist_name */, '', /* playlist_artwork_path */
            )), 'The workflow needs more privilages to do this, click to restart authentication', array('Next time you invoke the workflow, you will have to re-authenticate', 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'yes', null, '');
        }
        else {
            $w->result(null, 'help', 'Exception occurred', array('' . $e->getMessage(), 'alt' => '', 'cmd' => '', 'shift' => '', 'fn' => '', 'ctrl' => '',), './images/warning.png', 'no', null, '');
        }
        echo $w->tojson();
        exit;
    }
}
