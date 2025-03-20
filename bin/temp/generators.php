<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket\InternetAddress;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

require dirname(__DIR__) . "/vendor/autoload.php";

function count_to_ten(): Generator
{
    $s = yield 1;
    echo $s;
    yield 2;
    yield from [3, 4];
    yield from new ArrayIterator([5, 6]);

//    while (true) {
//        $string = yield;
//        echo $string;
//    }
}

 $generator = count_to_ten();
while ($generator->valid()) {
    $s = $generator->current();
    echo $s.PHP_EOL;

    $sss = $generator->send('dddd');
    echo $sss.PHP_EOL;

    $generator->next();
}
$s = $generator->getReturn();
echo $s.PHP_EOL;
