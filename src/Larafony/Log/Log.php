<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

use Larafony\Framework\Web\Application;
use Psr\Log\LoggerInterface;

final class Log
{
    private static ?LoggerInterface $logger = null;

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::logger()->emergency($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        self::logger()->alert($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::logger()->critical($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::logger()->error($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::logger()->warning($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        self::logger()->notice($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::logger()->info($message, $context);
    }

    /**
     * @param string $message
     * @param array<int|string, mixed> $context
     *
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        self::logger()->debug($message, $context);
    }

    private static function logger(): LoggerInterface
    {
        return self::$logger ??= Application::instance()->get(LoggerInterface::class);
    }
}
