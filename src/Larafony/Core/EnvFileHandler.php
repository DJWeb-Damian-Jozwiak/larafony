<?php

declare(strict_types=1);

namespace Larafony\Framework\Core;

use Larafony\Framework\Console\Application;
use Larafony\Framework\Storage\File;

final class EnvFileHandler
{
    private const ENV_FILE = '.env';

    public function update(string $key, string|int $value): void
    {
        $path = $this->getEnvPath();
        File::ensureFileExists($path);
        $content = file_get_contents($path);
        $content = trim($content);
        $content = preg_replace('/^' . $key . '=.*$/m', '', $content);
        $content .= "\n" . $key . '=' . $value;
        file_put_contents($path, $content);
    }

    private function getEnvPath(): string
    {
        $app = Application::instance();
        return $app->base_path . DIRECTORY_SEPARATOR . self::ENV_FILE;
    }
}
