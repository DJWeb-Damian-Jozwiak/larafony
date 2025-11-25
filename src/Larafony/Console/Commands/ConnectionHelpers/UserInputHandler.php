<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Commands\ConnectionHelpers;

use Larafony\Framework\Console\Contracts\OutputContract;

class UserInputHandler
{
    public function handle(string $key, OutputContract $output, string $default): int|string
    {
        $prompt = "Enter {$key}" . ($default !== '' ? " [{$default}]" : '') . ': ';
        if ($key === 'password') {
            $value = $output->secret($prompt);
            // Use default if empty
            if ($value === '') {
                $value = $default;
            }
        } else {
            $value = $output->question($prompt, (string) $default);
        }
        return $key === 'port' ? intval($value) : $value;
    }
}
