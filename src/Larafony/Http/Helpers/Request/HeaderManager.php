<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Helpers\Request;

final readonly class HeaderManager
{
    /**
     * @var array<string, array<int, string>> $normalizedHeaders
     */
    private array $normalizedHeaders;

    /**
     * @param array<string, string|array<int, string>> $headers
     */
    public function __construct(array $headers = [])
    {
        $this->normalizedHeaders = $this->normalizeHeaders($headers);
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->normalizedHeaders;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->normalizedHeaders[strtolower($name)]);
    }

    /**
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->normalizedHeaders[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * @param string|array<int, string> $value
     */
    public function withHeader(string $name, string|array $value): self
    {
        $headers = $this->normalizedHeaders;
        $headers[strtolower($name)] = is_array($value) ? $value : [$value];

        return new self($headers);
    }

    /**
     * @param string|array<int, string> $value
     */
    public function withAddedHeader(string $name, string|array $value): self
    {
        $headers = $this->normalizedHeaders;
        $key = strtolower($name);
        $newValues = is_array($value) ? $value : [$value];

        $headers[$key] = [...($headers[$key] ?? []), ...$newValues];

        return new self($headers);
    }

    public function withoutHeader(string $name): self
    {
        $headers = $this->normalizedHeaders;
        unset($headers[strtolower($name)]);

        return new self($headers);
    }

    /**
     * @param array<string, string|array<int, string>> $headers
     *
     * @return array<string, array<string>>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_array($value) ? $value : [$value];
        }

        return $normalized;
    }
}
