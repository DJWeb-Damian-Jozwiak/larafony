<?php

declare(strict_types=1);

namespace Larafony\Framework\Log;

use Larafony\Framework\Exceptions\Log\LoggerError;
use Larafony\Framework\Log\Formatters\JsonFormatter;
use Larafony\Framework\Log\Formatters\TextFormatter;
use Larafony\Framework\Log\Formatters\XmlFormatter;
use Larafony\Framework\Log\Handlers\DatabaseHandler;
use Larafony\Framework\Log\Handlers\FileHandler;
use Larafony\Framework\Log\Rotators\DailyRotator;
use Larafony\Framework\Web\Config;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public static function create(): LoggerInterface
    {
        $config = Config::get('logging');
        $handlers = [];
        foreach ($config['channels'] as $settings) {
            $handlers[] = match ($settings['handler']) {
                'database' => new DatabaseHandler(),
                'file' => new FileHandler(
                    logPath: $settings['path'],
                    formatter: match ($settings['formatter']) {
                        'json' => new JsonFormatter(),
                        'xml' => new XmlFormatter(),
                        default => new TextFormatter()
                    },
                    rotator: new DailyRotator(
                        maxDays: $settings['max_days'] ?? 7
                    )
                ),
                default => throw new LoggerError("Unknown handler type: {$settings['handler']}")
            };
        }

        return new Logger($handlers);
    }
}
