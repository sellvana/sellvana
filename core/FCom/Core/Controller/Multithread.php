<?php

class FCom_Core_Controller_Multithread extends FCom_Core_Controller_Abstract
{
    public function action_index()
    {
        error_reporting(E_ALL);

        /* Permitir al script esperar para conexiones. */
        set_time_limit(0);

        /* Activar el volcado de salida implícito, así veremos lo que estamo obteniendo
        * mientras llega. */
        ob_implicit_flush();

        $address = '127.0.0.1';
        $port    = 10000;

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->BDebug->log("socket_create() failed, reason: " . socket_strerror(socket_last_error()));
        }

        if (socket_bind($sock, $address, $port) === false) {
            $this->BDebug->log("socket_bind() failed, reason: " . socket_strerror(socket_last_error($sock)));
        }

        if (socket_listen($sock, 5) === false) {
            $this->BDebug->log("socket_listen() failed, reason: " . socket_strerror(socket_last_error($sock)));
        }

        //clients array
        $clients = array();

        do {
            $read   = array();
            $read[] = $sock;

            $read = array_merge($read, $clients);

            // Set up a blocking call to socket_select
            if (socket_select($read, $write = NULL, $except = NULL, $tv_sec = 5) < 1) {
                //    SocketServer::debug("Problem blocking socket_select?");
                continue;
            }

            // Handle new Connections
            if (in_array($sock, $read)) {
                if (($msgsock = socket_accept($sock)) === false) {
                    $this->BDebug->log("socket_accept() failed, reason: " . socket_strerror(socket_last_error($sock)));
                    break;
                }
                $clients[] = $msgsock;
                $key       = array_keys($clients, $msgsock);
                $msg = "\n" . $this->BUtil->toJson(['ok' => true, 'client_num' => $key[0]]) . "\n";
                socket_write($msgsock, $msg, strlen($msg));
            }

            // Handle Input
            foreach ($clients as $key => $client) { // for each client
                if (in_array($client, $read)) {
                    $buf = socket_read($client, 2048, PHP_NORMAL_READ);
                    if (false === $buf) {
                        $this->BDebug->log("socket_read() failed, reason: " . socket_strerror(socket_last_error($client)));
                        break 2;
                    }
                    if (!$buf = trim($buf)) {
                        continue;
                    }
                    if ($buf == 'quit') {
                        unset($clients[$key]);
                        socket_close($client);
                        break;
                    }
                    if ($buf == 'shutdown') {
                        socket_close($client);
                        break 2;
                    }
                    $talkback = "Cliente {$key}: Usted dijo '$buf'.\n";
                    socket_write($client, $talkback, strlen($talkback));
                    echo "$buf\n";
                }

            }
        } while (true);

        socket_close($sock);
    }
}