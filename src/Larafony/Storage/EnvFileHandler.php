<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage;

use Larafony\Framework\Web\Application;

final class EnvFileHandler
{
    private const ENV_FILE = '.env';

    public function update(string $key, string $value): void
    {
        $path = $this->getEnvPath();
        File::ensureFileExists($path);
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException("Unable to read file: {$path}");
        }
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
