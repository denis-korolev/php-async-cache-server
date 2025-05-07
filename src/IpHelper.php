<?php

namespace PhpCache;

class IpHelper
{
    public static function getIP(): string
    {
        return gethostbyname(gethostname());
    }

    public static function getPort(): int
    {
        $opts = getopt('p:', ['port:']);

        $port =  $opts['p'] ?? ($opts['port'] ?? null);
        if ($port === null) {
            throw new \Exception('Не передан обязательный параметр --port');
        }

        return (int)$port;
    }
}
