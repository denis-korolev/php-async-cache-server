<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

/* Позволяет скрипту ожидать соединения бесконечно. */
set_time_limit(0);

/* Включает скрытое очищение вывода так, что мы видим данные
 * как только они появляются. */
ob_implicit_flush();


//author:zhxia
if (!extension_loaded('sockets')) {
    die('the sockets extension is not loaded!');
}
const PORT = 10001;

$address = '127.0.0.2';

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die('socket create error!');
#By setting this option, port reuse
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, $address, PORT);
socket_listen($socket);
#Use non-blocking mode
socket_set_nonblock($socket);
echo 'listen on port ' . PORT . '...' . PHP_EOL;
$clients = array($socket);
while (TRUE) {
    $read = $clients;
    $write = $except = array();
    //Through the select system call, detect whether the state of the socket has changed
    if (socket_select($read, $write, $except, 0) < 1) {
        continue;
    }
    //Check if there is a client to connect
    if (in_array($socket, $read)) {
        $clients[] = $newsocket = socket_accept($socket);
        socket_write($newsocket, "welcome!\nthere are " . (count($clients) - 1) . " client here\n");
        socket_getpeername($newsocket, $ip);
        echo "new client connected:$ip\n";
        $key = array_search($socket, $read);
        unset($read[$key]);
    }

    foreach ($read as $read_socket) {
        $data = @socket_read($read_socket, 1024, PHP_NORMAL_READ);
        if ($data === false) {
            //If the data is not retrieved, the client has been disconnected
            $key = array_search($read_socket, $clients);
            unset($clients[$key]);
            echo "client disconnectd.\n";
            continue;
        }
        $data = trim($data);
        if (!empty($data)) {
            foreach ($clients as $write_socket) {
                //Exclude the server and itself, and then send the data to all other clients
                if ($write_socket == $socket || $write_socket == $read_socket) {
                    continue;
                }
                socket_write($write_socket, "$data\n");
            }
        }
    }
}
socket_close($socket);