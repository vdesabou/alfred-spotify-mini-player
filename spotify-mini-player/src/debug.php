<?php

require './src/functions.php';
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

$query = $argv[1];

if (mb_strlen($query) > 1) {
    if (startsWith($query, 'DB Exception')) {
        $w->result(null, '', 'DB Exception occurred: ' . $query, 'Try to re-create library as explained below.', './images/warning.png', 'no', null, '');
        $w->result(uniqid(), '', 'There is a problem with the library, try to re-create it.', 'Select Re-Create Library library below', './images/warning.png', 'no', null, '');
        $w->result(uniqid(), serialize(array(
            '' /*track_uri*/ ,
            '' /* album_uri */ ,
            '' /* artist_uri */ ,
            '' /* playlist_uri */ ,
            '' /* spotify_command */ ,
            '' /* query */ ,
            '' /* other_settings*/ ,
            'update_library' /* other_action */ ,
            '' /* alfred_playlist_uri */ ,
            '' /* artist_name */ ,
            '' /* track_name */ ,
            '' /* album_name */ ,
            '' /* track_artwork_path */ ,
            '' /* artist_artwork_path */ ,
            '' /* album_artwork_path */ ,
            '' /* playlist_name */ ,
            '' /* playlist_artwork_path */ ,
            ''
            /* $alfred_playlist_name */
        )), "Re-Create Library", "when done you'll receive a notification. you can check progress by invoking the workflow again", './images/update.png', 'yes', null, '');
    } elseif (startsWith($query, 'AppleScript Exception')) {
        $w->result(null, 'help', "AppleScript execution failed!", "Message: " . $query, './images/warning.png', 'no', null, '');
        $w->result(null, serialize(array(
            '' /*track_uri*/ ,
            '' /* album_uri */ ,
            '' /* artist_uri */ ,
            '' /* playlist_uri */ ,
            '' /* spotify_command */ ,
            '' /* query */ ,
            'Open▹' . 'http://alfred-spotify-mini-player.com/blog/issue-with-latest-spotify-update/' /* other_settings*/ ,
            '' /* other_action */ ,
            '' /* artist_name */ ,
            '' /* track_name */ ,
            '' /* album_name */ ,
            '' /* track_artwork_path */ ,
            '' /* artist_artwork_path */ ,
            '' /* album_artwork_path */ ,
            '' /* playlist_name */ ,
            '' /* playlist_artwork_path */
        )), 'Maybe you have an issue with a Broken Spotify version?', "Go to the article to get more information", './images/website.png', 'yes', null, '');
    } elseif (startsWith($query, 'Mopidy Exception')) {
        $w->result(null, 'help', "Mopidy execution failed!", "Message: " . $query, './images/warning.png', 'no', null, '');
        $w->result(null, serialize(array(
            '' /*track_uri*/ ,
            '' /* album_uri */ ,
            '' /* artist_uri */ ,
            '' /* playlist_uri */ ,
            '' /* spotify_command */ ,
            '' /* query */ ,
            'Open▹' . 'http://alfred-spotify-mini-player.com/articles/mopidy/' /* other_settings*/ ,
            '' /* other_action */ ,
            '' /* artist_name */ ,
            '' /* track_name */ ,
            '' /* album_name */ ,
            '' /* track_artwork_path */ ,
            '' /* artist_artwork_path */ ,
            '' /* album_artwork_path */ ,
            '' /* playlist_name */ ,
            '' /* playlist_artwork_path */
        )), 'Is Mopidy correctly installed and running?', "Go to the article to get more information", './images/website.png', 'yes', null, '');
    } else {
        $w->result(null, '', 'Exception occurred: ' . $query, 'Use the Send an email to the author option below to send generated spot_mini_debug.tgz', './images/warning.png', 'no', null, '');
    }
}

exec("mkdir -p ~/Downloads/spot_mini_debug");

$output = "DEBUG: ";

//
// check for library update in progress
if (file_exists($w->data() . "/update_library_in_progress")) {
    $w->result('', '', "Library update in progress", "", 'fileicon:' . $w->data() . '/update_library_in_progress', 'no', null, '');
    $output = $output . "Library update in progress: " . "the file" . $w->data() . "/update_library_in_progress is present\n";
}

if (!file_exists($w->data() . "/settings.json")) {
    $output = $output . "The file " . $w->data() . "/settings.json is not present\n";
} else {
    copy($w->data() . "/settings.json", $w->home() . "/Downloads/spot_mini_debug/settings.json");
}

copyDirectory($w->cache(), $w->home() . "/Downloads/spot_mini_debug/cache");

if (!file_exists($w->data() . "/fetch_artworks.db")) {
    $output = $output . "The file " . $w->data() . "/fetch_artworks.db is not present\n";
} else {
    copy($w->data() . "/fetch_artworks.db", $w->home() . "/Downloads/spot_mini_debug/fetch_artworks.db");
}

if (!file_exists($w->data() . "/library.db")) {
    $output = $output . "The file " . $w->data() . "/library.db is not present\n";
} else {
    copy($w->data() . "/library.db", $w->home() . "/Downloads/spot_mini_debug/library.db");
}

if (!file_exists($w->data() . "/library_new.db")) {
    $output = $output . "The file " . $w->data() . "/library_new.db is not present\n";
} else {
    copy($w->data() . "/library_new.db", $w->home() . "/Downloads/spot_mini_debug/library_new.db");
}

if (!file_exists($w->data() . "/library_old.db")) {
    $output = $output . "The file " . $w->data() . "/library_old.db is not present\n";
} else {
    copy($w->data() . "/library_old.db", $w->home() . "/Downloads/spot_mini_debug/library_old.db");
}


if (!file_exists($w->data() . "/history.json")) {
    $output = $output . "The file " . $w->data() . "/history.json is not present\n";
} else {
    copy($w->data() . "/history.json", $w->home() . "/Downloads/spot_mini_debug/history.json");
}

if (!file_exists($w->data() . "/playqueue.json")) {
    $output = $output . "The file " . $w->data() . "/playqueue.json is not present\n";
} else {
    copy($w->data() . "/playqueue.json", $w->home() . "/Downloads/spot_mini_debug/playqueue.json");
}

if (!file_exists(exec('pwd') . "/packal/package.xml")) {
    $output = $output . "The file " . exec('pwd') . "/packal/package.xml is not present\n";
} else {
    copy(exec('pwd') . "/packal/package.xml", $w->home() . "/Downloads/spot_mini_debug/package.xml");
}

//
// Read settings from JSON
//
$settings                  = getSettings($w);
$use_mopidy                = $settings->use_mopidy;

$output = $output . exec("uname -a");
$output = $output . "\n";
$output = $output . exec("sw_vers -productVersion");
$output = $output . "\n";
$output = $output . exec("sysctl hw.memsize");
$output = $output . "\n";
if(! $use_mopidy) {
	$output = $output . exec("osascript -e 'tell application \"Spotify\" to version'");
} else {
	$output = $output . "Mopidy version is " . invokeMopidyMethod($w, "core.get_version", array(), false);
}
$output = $output . "\n";

file_put_contents($w->home() . "/Downloads/spot_mini_debug/debug.log", $output);

exec("cd ~/Downloads;tar cfz spot_mini_debug.tgz spot_mini_debug");

$w->result(null, serialize(array(
    '' /*track_uri*/ ,
    '' /* album_uri */ ,
    '' /* artist_uri */ ,
    '' /* playlist_uri */ ,
    '' /* spotify_command */ ,
    '' /* query */ ,
    'Reveal▹' . $w->home() . '/Downloads/spot_mini_debug.tgz' /* other_settings*/ ,
    '' /* other_action */ ,
    '' /* alfred_playlist_uri */ ,
    '' /* artist_name */ ,
    '' /* track_name */ ,
    '' /* album_name */ ,
    '' /* track_artwork_path */ ,
    '' /* artist_artwork_path */ ,
    '' /* album_artwork_path */ ,
    '' /* playlist_name */ ,
    '' /* playlist_artwork_path */ ,
    '' /* $alfred_playlist_name */ ,
    '' /* now_playing_notifications */ ,
    '' /* is_alfred_playlist_active */ ,
    '' /* country_code*/ ,
    ''/* userid*/
)), 'Browse to generated tgz file', "This will reveal the tgz file in Finder", 'fileicon:' . $w->home() . '/Downloads/spot_mini_debug.tgz', 'yes', null, '');

$w->result(null, serialize(array(
    '' /*track_uri*/ ,
    '' /* album_uri */ ,
    '' /* artist_uri */ ,
    '' /* playlist_uri */ ,
    '' /* spotify_command */ ,
    '' /* query */ ,
    'Open▹' . 'mailto:alfred.spotify.mini.player@gmail.com' /* other_settings*/ ,
    '' /* other_action */ ,
    '' /* alfred_playlist_uri */ ,
    '' /* artist_name */ ,
    '' /* track_name */ ,
    '' /* album_name */ ,
    '' /* track_artwork_path */ ,
    '' /* artist_artwork_path */ ,
    '' /* album_artwork_path */ ,
    '' /* playlist_name */ ,
    '' /* playlist_artwork_path */ ,
    '' /* $alfred_playlist_name */ ,
    '' /* now_playing_notifications */ ,
    '' /* is_alfred_playlist_active */ ,
    '' /* country_code*/ ,
    ''/* userid*/
)), 'Send an email to the author', "This will open your default mail application, please attach the generated spot_mini_debug.tgz file.", './images/mail.png', 'yes', null, '');

$w->result(null, '', 'Quick access to workflow folders:', '', './images/info.png', 'no', null, '');

$w->result(null, serialize(array(
    '' /*track_uri*/ ,
    '' /* album_uri */ ,
    '' /* artist_uri */ ,
    '' /* playlist_uri */ ,
    '' /* spotify_command */ ,
    '' /* query */ ,
    'Open▹' . $w->data() /* other_settings*/ ,
    '' /* other_action */ ,
    '' /* alfred_playlist_uri */ ,
    '' /* artist_name */ ,
    '' /* track_name */ ,
    '' /* album_name */ ,
    '' /* track_artwork_path */ ,
    '' /* artist_artwork_path */ ,
    '' /* album_artwork_path */ ,
    '' /* playlist_name */ ,
    '' /* playlist_artwork_path */ ,
    '' /* $alfred_playlist_name */ ,
    '' /* now_playing_notifications */ ,
    '' /* is_alfred_playlist_active */ ,
    '' /* country_code*/ ,
    ''/* userid*/
)), 'Browse to App Support Folder', "This will open the folder in Finder", 'fileicon:' . $w->data(), 'yes', null, '');

$w->result(null, serialize(array(
    '' /*track_uri*/ ,
    '' /* album_uri */ ,
    '' /* artist_uri */ ,
    '' /* playlist_uri */ ,
    '' /* spotify_command */ ,
    '' /* query */ ,
    'Open▹' . $w->cache() /* other_settings*/ ,
    '' /* other_action */ ,
    '' /* alfred_playlist_uri */ ,
    '' /* artist_name */ ,
    '' /* track_name */ ,
    '' /* album_name */ ,
    '' /* track_artwork_path */ ,
    '' /* artist_artwork_path */ ,
    '' /* album_artwork_path */ ,
    '' /* playlist_name */ ,
    '' /* playlist_artwork_path */ ,
    '' /* $alfred_playlist_name */ ,
    '' /* now_playing_notifications */ ,
    '' /* is_alfred_playlist_active */ ,
    '' /* country_code*/ ,
    ''/* userid*/
)), 'Browse to Workflow Cache Folder', "This will open the folder in Finder", 'fileicon:' . $w->cache(), 'yes', null, '');

$w->result(null, serialize(array(
    '' /*track_uri*/ ,
    '' /* album_uri */ ,
    '' /* artist_uri */ ,
    '' /* playlist_uri */ ,
    '' /* spotify_command */ ,
    '' /* query */ ,
    'Open▹' . exec('pwd') /* other_settings*/ ,
    '' /* other_action */ ,
    '' /* alfred_playlist_uri */ ,
    '' /* artist_name */ ,
    '' /* track_name */ ,
    '' /* album_name */ ,
    '' /* track_artwork_path */ ,
    '' /* artist_artwork_path */ ,
    '' /* album_artwork_path */ ,
    '' /* playlist_name */ ,
    '' /* playlist_artwork_path */ ,
    '' /* $alfred_playlist_name */ ,
    '' /* now_playing_notifications */ ,
    '' /* is_alfred_playlist_active */ ,
    '' /* country_code*/ ,
    ''/* userid*/
)), 'Browse to Alfred workflow folder', "This will open the folder in Finder", 'fileicon:' . exec('pwd'), 'yes', null, '');


echo $w->toxml();

?>