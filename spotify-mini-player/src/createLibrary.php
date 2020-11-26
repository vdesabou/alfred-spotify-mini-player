<?php
require_once './src/workflows.php';
require_once './src/functions.php';
require_once './vendor/autoload.php';

/**
 * createLibrary function.
 *
 * @param mixed $w
 */
function createLibrary($w) {
    touch($w->data() . '/update_library_in_progress');
    $w->write('InitLibrary▹' . 0 . '▹' . 0 . '▹' . time() . '▹' . 'starting', 'update_library_in_progress');

    putenv('LANG=fr_FR.UTF-8');
    ini_set('memory_limit', '512M');
    if (file_exists($w->data() . '/library.db')) {
        rename($w->data() . '/library.db', $w->data() . '/library_old.db');
    }
    deleteTheFile($w,$w->data() . '/library_new.db');
    $dbfile = $w->data() . '/library_new.db';
    touch($dbfile);

    try {
        $db = new PDO("sqlite:$dbfile", '', '', array(
            PDO::ATTR_PERSISTENT => true,
        ));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->query('PRAGMA synchronous = OFF');
        $db->query('PRAGMA journal_mode = OFF');
        $db->query('PRAGMA temp_store = MEMORY');
        $db->query('PRAGMA count_changes = OFF');
        $db->query('PRAGMA PAGE_SIZE = 4096');
        $db->query('PRAGMA default_cache_size=700000');
        $db->query('PRAGMA cache_size=700000');
        $db->query('PRAGMA compile_options');
        // Problems with search on russian language #210
        // thanks to https://blog.amartynov.ru/php-sqlite-case-insensitive-like-utf8/
        $db->sqliteCreateFunction('like', "lexa_ci_utf8_like", 2);
    }
    catch(PDOException $e) {
        logMsg($w,'Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $db = null;

        return false;
    }

    if (file_exists($w->data() . '/fetch_artworks.db')) {
        deleteTheFile($w,$w->data() . '/fetch_artworks.db');
    }
    if (file_exists($w->data() . '/library_old.db')) {
        deleteTheFile($w,$w->data() . '/library_old.db');
    }
    rename($w->data() . '/library_new.db', $w->data() . '/library.db');

    try {
        $db->exec('create table tracks (yourmusic boolean, popularity int, uri text, album_uri text, artist_uri text, track_name text, album_name text, artist_name text, album_type text, track_artwork_path text, artist_artwork_path text, album_artwork_path text, playlist_name text, playlist_uri text, playable boolean, added_at text, duration text, nb_times_played int, local_track boolean, yourmusic_album boolean, track_name_deburr text, album_name_deburr text, artist_name_deburr text)');
        $db->exec('CREATE INDEX IndexPlaylistUri ON tracks (playlist_uri)');
        $db->exec('CREATE INDEX IndexArtistName ON tracks (artist_name)');
        $db->exec('CREATE INDEX IndexAlbumName ON tracks (album_name)');
        $db->exec('create table counters (id int PRIMARY KEY, all_tracks int, yourmusic_tracks int, all_artists int, yourmusic_artists int, all_albums int, yourmusic_albums int, playlists int, shows int, episodes int)');
        $db->exec('create table playlists (uri text PRIMARY KEY NOT NULL, name text, nb_tracks int, author text, username text, playlist_artwork_path text, ownedbyuser boolean, nb_playable_tracks int, duration_playlist text, nb_times_played int, collaborative boolean, public boolean, name_deburr text)');

        $db->exec('create table followed_artists (uri text PRIMARY KEY NOT NULL, name text, artist_artwork_path text, name_deburr text)');

        $db->exec('create table shows (uri text PRIMARY KEY NOT NULL, name text, description text, media_type text, show_artwork_path text, explicit boolean, added_at text, languages text, nb_times_played int, is_externally_hosted boolean, nb_episodes int, name_deburr text)');

        $db->exec('create table episodes (uri text PRIMARY KEY NOT NULL, name text, show_uri text, show_name text, description text, episode_artwork_path text, is_playable boolean, languages text, nb_times_played int, is_externally_hosted boolean, duration_ms int, explicit boolean, release_date text, release_date_precision text, audio_preview_url text, fully_played boolean, resume_position_ms int, name_deburr text, show_name_deburr text)');
    }
    catch(PDOException $e) {
        logMsg($w,'Error(createLibrary): (exception ' . jTraceEx($e) . ')');
        handleDbIssuePdoEcho($db, $w);
        $db = null;

        return false;
    }

    updateCounters($w, $db);

    deleteTheFile($w,$w->data() . '/update_library_in_progress');
    touch($w->data() . '/create_library');

    // in case of new user, force creation of links and current_user.json
    getCurrentUser($w);

    exec("osascript -e 'tell application id \"".getAlfredName()."\" to run trigger \"refresh_library\" in workflow \"com.vdesabou.spotify.mini.player\" with argument \"\"'");
    sleep(2);
    exec("osascript -e 'tell application id \"".getAlfredName()."\" to search \"".getenv('c_spot_mini')." \"'");
}

