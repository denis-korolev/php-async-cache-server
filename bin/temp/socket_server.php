<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

/* Позволяет скрипту ожидать соединения бесконечно. */
set_time_limit(0);

/* Включает скрытое очищение вывода так, что мы видим данные
 * как только они появляются. */
ob_implicit_flush();

$address = '127.0.0.2';
$port = 10001;

use Amp\Socket;
use function Amp\async;

$server = Socket\listen($address.':'.$port);

echo 'Listening for new connections on ' . $server->getAddress() . ' ...' . PHP_EOL;


$queue = new SplQueue();

$data = range(1, 100);
foreach ($data as $index => $value) {
    $queue->push((string)$value);
}


    while ($socket = $server->accept()) {
        async(function () use ($socket, $queue) {
            $address = $socket->getRemoteAddress();
            echo "Accepted connection from {$address}." . PHP_EOL;
            $data = $socket->read();
            echo "Получили данные {$data}" . PHP_EOL;

            // ищем возможные команды для действия
            // todo маршрутизатор
            $match = preg_match_all('/(\w+)\s({.+})/m', $data, $matches, PREG_SET_ORDER, 0);
            if($match !== 0) {
                $action = $matches[0][1];
                $body = json_decode($matches[0][2], true);
                $queueName = $body['name']??'';
                $payload = $body['payload']??'';

                if ($action === 'addToQueue') {
                    echo "Добавление в очередь" . PHP_EOL;
                    $queue->enqueue($payload);
                    echo "Количество записей в очереди: ".$queue->count(). PHP_EOL;
                    $socket->end();
                }

                if ($action === 'getFromQueue' && $queue->count() > 0) {
                    echo "Достаем из очереди" . PHP_EOL;

                    $response = [];
                    // prefetch
                    for ($i = 1; $i <= 10; $i++) {
                        $response[] = $queue->pop();
                    }
                    $value = json_encode($response);

                    echo "Количество записей в очереди: ".$queue->count() . PHP_EOL;
                    echo "Значение в очереди получили: ".$value. PHP_EOL;
                    $socket->write($value);
                }
            }

        });
    }

