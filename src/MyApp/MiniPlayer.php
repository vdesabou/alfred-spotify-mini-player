<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

include_once './src/functions.php';

class MiniPlayer implements MessageComponentInterface {
	protected $clients;

	public function __construct() {
		$this->clients = new \SplObjectStorage;
	}

	public function onOpen(ConnectionInterface $conn) {
		// Store the new connection to send messages to later
		$this->clients->attach($conn);

		// echo "New connection! ({$conn->resourceId})\n";
	}

	public function onMessage(ConnectionInterface $from, $msg) {
		$numRecv = count($this->clients) - 1;


		/*
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
*/

		$tmp = explode('▹', $msg);
		$command=$tmp[0];
		$json=$tmp[1];
		if ($command=="update_library") {
			updateLibrary($json);
			foreach ($this->clients as $client) {
				$client->send("UPDATE LIBRARY SUCCESS");
			}
		}
		else if ($command=="update_playlist") {
				updatePlaylist($json);
				foreach ($this->clients as $client) {
					$client->send("UPDATE PLAYLIST SUCCESS");
				}
			}
		else if ($command=="update_playlist_list") {
				updatePlaylistList($json);
				foreach ($this->clients as $client) {
					$client->send("UPDATE PLAYLIST LIST SUCCESS");
				}
			}
		else if ($command=="current_track_get_artist") {
				//try to decode it
				$artist = json_decode($json, true);
				if (json_last_error() === JSON_ERROR_NONE) {

					if (count($artist) > 0) {
						$artist_uri = $artist['artist_uri'];
						$artist_name = $artist['artist_name'];

						exec("osascript -e 'tell application \"Alfred 2\" to search \"spot_mini Online▹$artist_uri@$artist_name\"'");
					} else {
						foreach ($this->clients as $client) {
							$client->send("CURRENT TRACK GET ARTIST FAIL");
						}
						displayNotification("Error: cannot get artist for current track");
					}

				}else {
					foreach ($this->clients as $client) {
						$client->send("CURRENT TRACK GET ARTIST FAIL");
					}
					displayNotification("Error: cannot get artist for current track");
				}

				foreach ($this->clients as $client) {
					$client->send("CURRENT TRACK GET ARTIST SUCCESS");
				}
			}
		else if ($command=="star") {
				//try to decode it

				$track = json_decode($tmp[2], true);
				if (json_last_error() === JSON_ERROR_NONE) {

					if (count($track) > 0) {
						$track_uri = $track['uri'];
						$track_name = $track['name'];

						displayNotificationForStarredTrack($track_name, $track_uri);

						updatePlaylist($json);

					} else {
						foreach ($this->clients as $client) {
							$client->send("STAR FAIL");
						}
						displayNotification("Error: cannot get current track");
						removeUpdateLibraryInProgressFile();
					}

				}else {
					foreach ($this->clients as $client) {
						$client->send("STAR FAIL");
					}
					displayNotification("Error: cannot get current track");
					removeUpdateLibraryInProgressFile();
				}

				foreach ($this->clients as $client) {
					$client->send("STAR SUCCESS");
				}
			}
		else if ($command=="unstar") {
				//try to decode it
				$track = json_decode($tmp[2], true);
				if (json_last_error() === JSON_ERROR_NONE) {

					if (count($track) > 0) {
						$track_uri = $track['uri'];
						$track_name = $track['name'];

						displayNotificationForUnstarredTrack($track_name, $track_uri);

						updatePlaylist($json);

					} else {
						foreach ($this->clients as $client) {
							$client->send("UNSTAR FAIL");
						}
						displayNotification("Error: cannot get current track");
						removeUpdateLibraryInProgressFile();
					}

				}else {
					foreach ($this->clients as $client) {
						$client->send("UNSTAR FAIL");
					}
					displayNotification("Error: cannot get current track");
					removeUpdateLibraryInProgressFile();
				}

				foreach ($this->clients as $client) {
					$client->send("UNSTAR SUCCESS");
				}
			}
		else {
			foreach ($this->clients as $client) {
				$client->send("ERROR UNKNOWN COMMAND");
			}
		}
	}

	public function onClose(ConnectionInterface $conn) {
		// The connection is closed, remove it, as we can no longer send it messages
		$this->clients->detach($conn);

		// echo "Connection {$conn->resourceId} has disconnected\n";

		$conn->close();

		exit(0);
	}

	public function onError(ConnectionInterface $conn, \Exception $e) {
		echo "An error has occurred: {$e->getMessage()}\n";

		$conn->close();

		exit(0);
	}
}
