<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final readonly class ParsedBodyManager
{
    /**
     * @param ?array<string, mixed> $parsedBody
     */
    public function __construct(
        public ?array $parsedBody = null,
    ) {
    }

    /**
     * @param ?array<string, mixed> $data
     */
    public function withParsedBody(?array $data): self
    {
        return clone($this, ['parsedBody' => $data]);
    }

    public function has(string $key): bool
    {
        return isset($this->parsedBody[$key]);
    }
}
