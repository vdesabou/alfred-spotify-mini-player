<?php

require './src/functions.php';
require_once './src/workflows.php';


function debug($argv) {
    $w = new Workflows('com.vdesabou.spotify.mini.player');

    // Read settings from JSON

    $settings = getSettings($w);

    $theme_color = $settings->theme_color;
    $artwork_folder_size = $settings->artwork_folder_size;

    $query = $argv[1];

    if (countCharacters($query) > 1) {
        if (startsWith($query, 'DB Exception')) {
            $w->result(null, '', 'DB Exception occurred: '.$query,array(
                        'Try to re-create library as explained below.',
                        'alt' => '',
                        'cmd' => '',
                        'shift' => '',
                        'fn' => '',
                        'ctrl' => '',
                    ), './images/warning.png', 'no', null, '');
            $w->result(null, '', '👇👇👇 You need to recreate your library by selecting option below 👇👇👇',array(
                'There was a breaking change and you need to telect Re-Create Library library below',
                'alt' => '',
                'cmd' => '',
                'shift' => '',
                'fn' => '',
                'ctrl' => '',
            ), './images/info.png', 'no', null, '');
            $w->result(uniqid(), serialize(array(
                '' /*track_uri*/,
                '' /* album_uri */,
                '' /* artist_uri */,
                '' /* playlist_uri */,
                '' /* spotify_command */,
                '' /* query */,
                '' /* other_settings*/,
                'create_library' /* other_action */,
                '' /* alfred_playlist_uri */,
                '' /* artist_name */,
                '' /* track_name */,
                '' /* album_name */,
                '' /* track_artwork_path */,
                '' /* artist_artwork_path */,
                '' /* album_artwork_path */,
                '' /* playlist_name */,
                '' /* playlist_artwork_path */,
                '',
                /* $alfred_playlist_name */
            )), 'Re-Create Library', "you can check progress by invoking the workflow again and use it while it's creating the library", './images/update.png', 'yes', null, '');
        } elseif (startsWith($query, 'AppleScript Exception')) {
            $w->result(null, 'help', 'AppleScript execution failed!',array(
                        'Message: '.$query,
                        'alt' => '',
                        'cmd' => '',
                        'shift' => '',
                        'fn' => '',
                        'ctrl' => '',
                    ), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(
                '' /*track_uri*/,
                '' /* album_uri */,
                '' /* artist_uri */,
                '' /* playlist_uri */,
                '' /* spotify_command */,
                '' /* query */,
                'Open▹'.'https://alfred-spotify-mini-player.com/known-issues/' /* other_settings*/,
                '' /* other_action */,
                '' /* artist_name */,
                '' /* track_name */,
                '' /* album_name */,
                '' /* track_artwork_path */,
                '' /* artist_artwork_path */,
                '' /* album_artwork_path */,
                '' /* playlist_name */,
                '', /* playlist_artwork_path */
            )), 'Maybe you have an issue with a broken Spotify version?', 'Go to the article to get more information', './images/website.png', 'yes', null, '');
        } elseif (startsWith($query, 'Mopidy Exception')) {
            $w->result(null, 'help', 'Mopidy execution failed!',array(
                        'Message: '.$query,
                        'alt' => '',
                        'cmd' => '',
                        'shift' => '',
                        'fn' => '',
                        'ctrl' => '',
                    ), './images/warning.png', 'no', null, '');
            $w->result(null, serialize(array(
                '' /*track_uri*/,
                '' /* album_uri */,
                '' /* artist_uri */,
                '' /* playlist_uri */,
                '' /* spotify_command */,
                '' /* query */,
                'Open▹'.'https://alfred-spotify-mini-player.com/known-issues/#mopidy/' /* other_settings*/,
                '' /* other_action */,
                '' /* artist_name */,
                '' /* track_name */,
                '' /* album_name */,
                '' /* track_artwork_path */,
                '' /* artist_artwork_path */,
                '' /* album_artwork_path */,
                '' /* playlist_name */,
                '', /* playlist_artwork_path */
            )), 'Is Mopidy correctly installed and running?', 'Go to the article to get more information', './images/website.png', 'yes', null, '');
        } elseif (startsWith($query, 'Refresh token revoked')) {
            $w->result(null, serialize(array(
                '' /*track_uri*/,
                '' /* album_uri */,
                '' /* artist_uri */,
                '' /* playlist_uri */,
                '' /* spotify_command */,
                '' /* query */,
                '' /* other_settings*/,
                'reset_oauth_settings' /* other_action */,
                '' /* artist_name */,
                '' /* track_name */,
                '' /* album_name */,
                '' /* track_artwork_path */,
                '' /* artist_artwork_path */,
                '' /* album_artwork_path */,
                '' /* playlist_name */,
                '', /* playlist_artwork_path */
                )), 'The workflow needs to re-authenticate, click to restart authentication', array(
                'Next time you invoke the workflow, you will have to re-authenticate',
                'alt' => '',
                'cmd' => '',
                'shift' => '',
                'fn' => '',
                'ctrl' => '',
            ), './images/warning.png', 'yes', null, '');
            echo $w->tojson();
            return;
        } else {
            $w->result(null, '', 'Exception occurred: '.$query,array(
                        'Use the Send an email to the author option below to send generated spot_mini_debug.zip',
                        'alt' => '',
                        'cmd' => '',
                        'shift' => '',
                        'fn' => '',
                        'ctrl' => '',
                    ), './images/warning.png', 'no', null, '');
        }
    }

    $w->result(null, serialize(array(
                            '' /*track_uri*/,
                            '' /* album_uri */,
                            '' /* artist_uri */,
                            '' /* playlist_uri */,
                            '' /* spotify_command */,
                            '' /* query */,
                            '' /* other_settings*/,
                            'spot_mini_debug' /* other_action */,
                            '' /* artist_name */,
                            '' /* track_name */,
                            '' /* album_name */,
                            '' /* track_artwork_path */,
                            '' /* artist_artwork_path */,
                            '' /* album_artwork_path */,
                            '' /* playlist_name */,
                            '', /* playlist_artwork_path */
    )), 'Send a debug email to the author with all required information for troubleshooting', 'This will open your default mail application (and copy content into clipboard)', './images/mail.png', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.$w->data() /* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Browse to App Support Folder', 'This will open the folder in Finder', 'fileicon:'.$w->data(), 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.$w->cache() /* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Browse to Workflow Cache Folder', 'This will open the folder in Finder', 'fileicon:'.$w->cache(), 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.exec('pwd') /* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Browse to Alfred Workflow folder', 'This will open the folder in Finder', 'fileicon:'.exec('pwd'), 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.$w->cache().'/action.log'/* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Open log file', 'This will open the log file', 'fileicon:'.$w->cache().'/action.log', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.$w->data().'/settings.db'/* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Open settings database file', 'This will open the db settings file', 'fileicon:'.$w->data().'/settings.db', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.exec('pwd').'/App/'.$theme_color.'/Spotify Mini Player.app' /* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Open Spotify Mini Player app', 'This will open the app (troubleshooting notifications issues)', 'fileicon:'.exec('pwd').'/App/'.$theme_color.'/Spotify Mini Player.app', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        'Open▹'.exec('pwd').'/terminal-notifier.app' /* other_settings*/,
        '' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Open terminal-notifier app', 'This will open the app (troubleshooting notifications issues)', 'fileicon:'.exec('pwd').'/terminal-notifier.app', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        '' /* other_settings*/,
        'reset_client_secret' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Reset your Client Secret', 'Use this if you reset your Client Secret; your data and settings will be kept', './images/settings.png', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        '' /* other_settings*/,
        'reset_oauth_settings' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Re-authenticate to Spotify', 'This will force re-authentication to Spotify; your data and settings will be kept', './images/settings.png', 'yes', null, '');

    $w->result(null, serialize(array(
        '' /*track_uri*/,
        '' /* album_uri */,
        '' /* artist_uri */,
        '' /* playlist_uri */,
        '' /* spotify_command */,
        '' /* query */,
        '' /* other_settings*/,
        'delete_artwork_folder' /* other_action */,
        '' /* alfred_playlist_uri */,
        '' /* artist_name */,
        '' /* track_name */,
        '' /* album_name */,
        '' /* track_artwork_path */,
        '' /* artist_artwork_path */,
        '' /* album_artwork_path */,
        '' /* playlist_name */,
        '' /* playlist_artwork_path */,
        '' /* $alfred_playlist_name */,
        '' /* now_playing_notifications */,
        '' /* is_alfred_playlist_active */,
        '' /* country_code*/,
        '', /* userid*/
    )), 'Delete artwork folder ' . '(current cached folder size is ' . $artwork_folder_size . ')' , 'This will erase existing album artwork and re-download them', './images/warning.png', 'yes', null, '');

    echo $w->tojson();
}

debug($argv);
