<?php

declare(strict_types=1);

namespace PhpCache;

final class MemoryHelper
{
    /**
     * @var int
     */
    private static $profileMemory = 1;

    public static function realUsageMiBi(): float
    {
        return self::byteToMiBi(memory_get_usage(true), 3);
    }

    public static function usageMiBi(): float
    {
        return self::byteToMiBi(memory_get_usage(), 3);
    }

    public static function peakRealUsageMiBi(): float
    {
        return self::byteToMiBi(memory_get_peak_usage(true), 3);
    }

    /**
     * Сохранение метки использования памяти.
     */
    public static function startProfile(): void
    {
        self::$profileMemory = memory_get_usage();
    }

    /**
     * Возвразщает долю изменения памяти от момента startProfile.
     *
     * @return float
     */
    public static function stopProfile(): float
    {
        $current = memory_get_usage();

        if (0 === self::$profileMemory) {
            return 1;
        }

        return $current / self::$profileMemory;
    }

    /**
     * Ковертиврование байт в МБ с округлением.
     *
     * @param int      $bytes
     * @param null|int $round
     *
     * @return float
     */
    private static function byteToMiBi(int $bytes, ?int $round = 0): float
    {
        $miBi = $bytes / 1024 / 1024;

        return null === $round ? $miBi : round($miBi, $round);
    }
}
