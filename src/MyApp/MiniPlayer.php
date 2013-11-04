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
		echo "$msg";
		$json = json_decode($msg,true);
		if (json_last_error() === JSON_ERROR_NONE) 
		{
			if($json[0]=="update_library")
			{
				updateLibrary($json[1]);
		        foreach ($this->clients as $client) {
		                $client->send("UPDATE LIBRARY SUCCESS");
		        }		
			}
			else if($json[0]=="update_playlist")
			{
				updatePlaylist($json[1]);
		        foreach ($this->clients as $client) {
		                $client->send("UPDATE PLAYLIST SUCCESS");
		        }		
			}
			else
			{
		        foreach ($this->clients as $client) {
		                $client->send("ERROR UNKNOWN COMMAND");
		        }					
			}
		}
		else
		{
	        foreach ($this->clients as $client) {
	                $client->send("ERROR DECODING JSON");
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

