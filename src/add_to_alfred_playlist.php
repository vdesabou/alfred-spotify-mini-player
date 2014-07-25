<?php


// Load and use David Ferguson's Workflows.php class
require_once './src/workflows.php';
$w = new Workflows('com.vdesabou.spotify.mini.player');

//require('./src/functions.php');
require './src/action.php';

// get info on current song
$command_output = exec("./track_info.sh 2>&1");

if (substr_count($command_output, '▹') > 0) {
	$results = explode('▹', $command_output);

	//
	// Read settings from DB
	//
	$getSettings = 'select alfred_playlist_uri,alfred_playlist_name,theme from settings';
	$dbfile = $w->data() . '/settings.db';
	exec("sqlite3 -separator '	' \"$dbfile\" \"$getSettings\" 2>&1", $settings, $returnValue);

	if ($returnValue != 0) {
		displayNotification("Error: Alfred Playlist is not set");
		return;
	}


	foreach ($settings as $setting):

		$setting = explode("	", $setting);

	$alfred_playlist_uri = $setting[0];
	$alfred_playlist_name = $setting[1];
	$theme = $setting[2];
	endforeach;

	if ($alfred_playlist_uri == "" || $alfred_playlist_name == "") {
		displayNotification("Error: Alfred Playlist is not set");
		return;
	}

	exec("osascript -e 'tell application \"Spotify\" to open location \"spotify:app:miniplayer:addtoalfredplaylist:$results[4]:$alfred_playlist_uri\"'");

	displayNotificationWithArtwork('' . $results[0] . ' by ' . $results[1] . '
added to ' . $alfred_playlist_name, getTrackOrAlbumArtwork($w, $theme, $results[4], true));

	// update alfred playlist
	refreshPlaylist($w, $alfred_playlist_uri);
}
else {
	displayNotification("Error: No track is playing");
}

?>