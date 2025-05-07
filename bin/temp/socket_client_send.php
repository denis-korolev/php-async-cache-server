<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";


$data = range(1, 100);
foreach ($data as $index => $value) {
//    sendData((string)$value);
}


//function sendData(string $data)
//{
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

    echo "Пытаемся соединиться с '$address' на порту '$port'..." . PHP_EOL;
    $result = socket_connect($socket, $address, $port);
    if ($result === false) {
        echo "Не удалось выполнить socket_connect().\nПричина: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    } else {
        echo "OK.\n" . PHP_EOL;
    }


    echo "Сообщаем, что мы отправляем в очередь данные" . PHP_EOL;

$data = range(1, 100);
foreach ($data as $index => $value) {
//    sendData((string)$value);
    $value = 'addToQueue {"name" : "send", "payload" : "'.$value.'"}';
    socket_write($socket, $value, strlen($value));
    echo "Отправляем данные в очередь" . PHP_EOL;
}

    echo "Читаем данные из очереди:" . PHP_EOL;

    while ($out = socket_read($socket, 2048)) {
        echo $out . PHP_EOL;
    }

    echo "Все данные ушли, закрываем сокет..." . PHP_EOL;
//    socket_close($socket);
//}


echo "OK.\n\n";