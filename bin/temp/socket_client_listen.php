<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

echo "Читаем данные из очереди:\n\n";
//for ($i = 1; $i <= 10; $i++) {
fetchFronQueue();
//}

function fetchFronQueue(): void
{
    echo "<h2>Соединение TCP/IP</h2>\n";

    $address = '127.0.0.2';
    $port = 10001;

    /* Создаём сокет TCP/IP. */
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        echo "Не удалось выполнить socket_create(): причина: " . socket_strerror(socket_last_error()) . "\n";
    } else {
        echo "OK.\n";
    }

    echo "Пытаемся соединиться с '$address' на порту '$port'...";
    $result = socket_connect($socket, $address, $port);
    if ($result === false) {
        echo "Не удалось выполнить socket_connect().\nПричина: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    } else {
        echo "OK.\n";
    }
    while (true) {
        $value = 'getFromQueue {"name" : "send"}';
        $sent = socket_write($socket, $value, strlen($value));
        echo "socket_write\n";
        if ($sent === false) {
            break;
        }
        $out = '';
        echo "ожидаем ответа на запрос\n";
        socket_recv($socket, $out, 2048, MSG_WAITALL);
        //        while ($out = socket_read($socket, 2048)) {
//            echo $out . PHP_EOL;
//        }
            echo $out . PHP_EOL;
    }

    echo "Все получили, закрываем сокет..." . PHP_EOL;
    socket_close($socket);

    echo "OK.\n\n";

}

