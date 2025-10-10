<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Dto;

/**
 * Reprezentuje sparsowaną zmienną środowiskową
 */
final readonly class EnvironmentVariable
{
    public function __construct(
        public string $key,
        public string $value,
        public bool $isQuoted = false,
        public bool $isMultiline = false,
        public int $lineNumber = 0,
    ) {
    }

    /**
     * @return array<string, string|bool|int>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'is_quoted' => $this->isQuoted,
            'is_multiline' => $this->isMultiline,
            'line_number' => $this->lineNumber,
        ];
    }
}
