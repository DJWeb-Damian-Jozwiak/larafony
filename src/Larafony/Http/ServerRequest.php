<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Helpers\Request\AttributesManager;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Helpers\Request\ParsedBodyManager;
use Larafony\Framework\Http\Helpers\Request\QueryParamsManager;
use Larafony\Framework\Http\Helpers\Request\UploadedFilesManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, string> $cookieParams
     */
    public function __construct(
        string $method = 'GET',
        ?UriInterface $uri = null,
        string $protocolVersion = '1.1',
        HeaderManager $headerManager = new HeaderManager(),
        ?StreamInterface $body = null,
        protected array $serverParams = [],
        protected array $cookieParams = [],
        protected QueryParamsManager $queryParamsManager = new QueryParamsManager(),
        protected UploadedFilesManager $uploadedFilesManager = new UploadedFilesManager(),
        protected ParsedBodyManager $parsedBodyManager = new ParsedBodyManager(),
        protected AttributesManager $attributesManager = new AttributesManager(),
    ) {
        parent::__construct($method, $uri, $protocolVersion, $headerManager, $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @return array<string, string>
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @param array<string, string> $cookies
     */
    public function withCookieParams(array $cookies): static
    {
        return clone($this, ['cookieParams' => $cookies]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueryParams(): array
    {
        return $this->queryParamsManager->queryParams;
    }

    /**
     * @param array<string, mixed> $query
     */
    public function withQueryParams(array $query): static
    {
        return clone($this, ['queryParamsManager' => $this->queryParamsManager->withQueryParams($query)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFilesManager->uploadedFiles;
    }

    /**
     * @param array<string, mixed> $uploadedFiles
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        return clone($this, ['uploadedFilesManager' => $this->uploadedFilesManager->withUploadedFiles($uploadedFiles)]);
    }

    /**
     * @return array<string, mixed>|object|null
     */
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBodyManager->parsedBody;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function withParsedBody($data): static
    {
        return clone($this, ['parsedBodyManager' => $this->parsedBodyManager->withParsedBody($data)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributesManager->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributesManager->getAttribute($name, $default);
    }

    public function withAttribute(string $name, mixed $value): static
    {
        return clone($this, ['attributesManager' => $this->attributesManager->withAttribute($name, $value)]);
    }

    public function withoutAttribute(string $name): static
    {
        return clone($this, ['attributesManager' => $this->attributesManager->withoutAttribute($name)]);
    }

    // Convenience methods

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->getQueryParams()[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        $body = $this->getParsedBody();
        return is_array($body) ? ($body[$key] ?? $default) : $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key) ?? $this->query($key) ?? $default;
    }

    public function has(string $key): bool
    {
        return $this->queryParamsManager->has($key) || $this->parsedBodyManager->has($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $body = $this->getParsedBody();
        return [...$this->getQueryParams(), ...(is_array($body) ? $body : [])];
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }
}
