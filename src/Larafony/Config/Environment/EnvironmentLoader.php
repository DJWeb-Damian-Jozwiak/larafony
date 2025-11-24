<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment;

use Larafony\Framework\Config\Environment\Dto\ParserResult;
use Larafony\Framework\Config\Environment\Exception\EnvironmentError;
use Larafony\Framework\Config\Environment\Parser\DotenvParser;
use Larafony\Framework\Config\Environment\Parser\ParserContract;

/**
 * Facade do ładowania zmiennych środowiskowych
 */
class EnvironmentLoader
{
    public function __construct(
        private readonly ParserContract $parser = new DotenvParser()
    ) {
    }

    /**
     * Ładuje plik .env i ustawia zmienne w $_ENV i $_SERVER
     */
    public function load(string $path): ParserResult
    {
        if (! file_exists($path)) {
            throw new EnvironmentError("Environment file not found: {$path}");
        }

        if (! is_readable($path)) {
            throw new EnvironmentError("Environment file not readable: {$path}");
        }

        $content = file_get_contents($path);
        if ($content === false) {
            //@codeCoverageIgnoreStart
            $content = '';
            //@codeCoverageIgnoreEnd
        }
        $result = $this->parser->parse($content);

        $this->withEnvironmentVariables($result);

        return $result;
    }

    /**
     * Parsuje zawartość bez ładowania do $_ENV
     */
    public function parseContent(string $content): ParserResult
    {
        return $this->parser->parse($content);
    }

    /**
     * Ustawia zmienne w środowisku
     */
    private function withEnvironmentVariables(ParserResult $result): void
    {
        $env_keys = array_keys($_ENV);
        $variables = array_filter($result->variables, static fn ($variable) => ! in_array($variable->key, $env_keys));
        foreach ($variables as $variable) {
            $_ENV[$variable->key] = $variable->value;
            $_SERVER[$variable->key] = $variable->value;
            putenv("{$variable->key}={$variable->value}");
        }
    }
}
