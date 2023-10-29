<?php

declare(strict_types=1);

require dirname(__DIR__) . "/../vendor/autoload.php";

use PhpCache\MemoryHelper;

$array = array_map(
    function () {
        return bin2hex(random_bytes(8));
    },
    array_fill(0, 10000, null)
);

$uri = 'http://localhost:80/';

$start = microtime(true);

$reqs = [];
foreach ($array as $key => $value) {
    $s = microtime(true);

    $ch = curl_init($uri . $key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $value);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $html = curl_exec($ch);
    curl_close($ch);

    if (curl_errno($ch) !== 0) {
        throw new Exception(sprintf('Curl error: (%s) %s', curl_errno($ch), curl_error($ch)), curl_errno($ch));
    }

    $reqs[] = microtime(true) - $s;
}
$message = 'Закончил работу ' . PHP_EOL .
    sprintf('Время работы %.4F с.', microtime(true) - $start);

echo $message . PHP_EOL;

$message = 'Real Memory using: ' . MemoryHelper::realUsageMiBi() . ' MiBi' . PHP_EOL .
    'Now  Memory using: ' . MemoryHelper::usageMiBi() . ' MiBi';
echo $message . PHP_EOL;

$message = sprintf('Среднее время запроса %.4F с.', array_sum($reqs) / count($reqs));
echo $message . PHP_EOL;
