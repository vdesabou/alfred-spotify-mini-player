<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

include_once('./functions.php');

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
		$tmp = explode('⇾', $msg);
		$command=$tmp[0];
		$json=$tmp[1];
		if($command=="update_library")
		{
			updateLibrary($json);
	        foreach ($this->clients as $client) {
	                $client->send("UPDATE LIBRARY SUCCESS");
	        }		
		}
		else if($command=="update_playlist")
		{
			updatePlaylist($json);
	        foreach ($this->clients as $client) {
	                $client->send("UPDATE PLAYLIST SUCCESS");
	        }		
		}
		else if($command=="update_playlist_list")
		{
			updatePlaylistList($json);
	        foreach ($this->clients as $client) {
	                $client->send("UPDATE PLAYLIST LIST SUCCESS");
	        }		
		}
		else
		{
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

