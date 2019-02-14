<?php 

namespace RaspberryConduit\Listen; 

use Volatile;
use pocketmine\Worker; 

class Listen extends Worker {

    private function setup_socket() {
        $address = 'localhost';
        $port = 4711;

        #if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
        #   echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        #}
        #socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
        #if (socket_bind($sock, $address, $port) === false) {
        #if (socket_bind($sock,  INADDR_ANY, $port) === false) {
        #    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        #}

        #if (socket_listen($sock, 5) === false) {
        #    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        #}

        if (($sock = socket_create_listen($port, 5)) === false) {
            echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
        }
        return $sock;
    }

    private function close_socket() {
        socket_close($this->sock);
    }

    public function __construct($conduit){
        $this->stop = False;
        $this->conduit = $conduit;
        $this->sock = $this->setup_socket();
    }

    public function run() {
        $this->registerClassLoader();
        
        $clients = array();
        $master_socket = $this->sock;

        while($this->stop === False) { #mainloop
            $read   = array();
            $write  = NULL;
            $except = NULL;

            if (!in_array($master_socket, $read)) {
                $read[] = $master_socket;
            } # Add the master socket if not in read

            foreach($clients as $client){
                $read[] = $client;
            } #monitor all clients

            $ready = socket_select($read, $write, $except, 0.1); # nonblocking
            #
            if ($ready == 0){
                # nothing to read processing conduit commands
                $processed_items = array();
                foreach ($this->conduit as $key => $data) {

                    if ($this->conduit[$key]['result'] !== NULL) {
                        if ($this->conduit[$key]['result'] != "NA") { #NA = no answer expected
                            $talkback = $this->conduit[$key]['result'] . "\n";
                            socket_write($data["sender"], $talkback, strlen($talkback));
                        }
                        $processed_items[] = $key;
                    } 
                    
                }
                foreach ($processed_items as $key) {
                    unset($this->conduit[$key]);
                }

                sleep(0.001);
                continue;
            }

            if (in_array($master_socket, $read)){
                $newsock = socket_accept($master_socket);
                $clients[] = $newsock;
                $key = array_search($master_socket, $read);
                unset($read[$key]);
            }

            foreach($read as $readable){
                $input = socket_read($readable, 8192);

                // Zero length string meaning disconnected
                if ($input == null) {
                    $key = array_search($readable, $clients);
                    unset($clients[$key]);
                }

                $buf = trim($input);
                $pieces = explode("\n", $buf);

                if ($input) { # handle the received message
                    foreach($pieces as $piece) {
                       $job = uniqid();
                        $this->conduit[$job] = array("sender" => $readable,
                            "command" =>$piece
                        );
                    }
                }
            }
        } #mainloop
            #echo "RaspberryConduit thread exiting.\n";
    }

    public function stop() {
        $this->stop = True;
        $this->close_socket();
    }
}

