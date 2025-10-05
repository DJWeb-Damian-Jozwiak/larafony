<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final readonly class QueryParamsManager
{
    /**
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        public array $queryParams = [],
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQueryParams(array $query): self
    {
        return clone($this, ['queryParams' => $query]);
    }

    public function has(string $key): bool
    {
        return isset($this->queryParams[$key]);
    }
}
