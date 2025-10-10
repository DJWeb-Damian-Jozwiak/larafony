<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Dto;

/**
 * Reprezentuje sparsowaną linię (może być komentarzem, pustą linią lub zmienną)
 */
final class ParsedLine
{
    public bool $isVariable {
        get => $this->type === LineType::Variable;
    }

    public bool $isComment {
        get => $this->type === LineType::Comment;
    }

    public bool $isEmpty {
        get => $this->type === LineType::Empty;
    }
    public function __construct(
        public readonly string $raw,
        public readonly LineType $type,
        public readonly ?EnvironmentVariable $variable = null,
        public readonly int $lineNumber = 0,
    ) {
    }
}
