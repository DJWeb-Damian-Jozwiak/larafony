<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    public function __construct(
        protected string $method = 'GET',
        protected ?UriInterface $uri = null,
        string $protocolVersion = '1.1',
        HeaderManager $headerManager = new HeaderManager(),
        ?StreamInterface $body = null,
    ) {
        parent::__construct($protocolVersion, $headerManager, $body);
    }

    public function getRequestTarget(): string
    {
        $target = $this->uri?->getPath() ?? '/';
        $query = $this->uri?->getQuery() ?? '';

        return $query !== '' ? $target . '?' . $query : $target;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        return clone($this, ['uri' => $this->getUri()->withPath($requestTarget)]);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        return clone($this, ['method' => strtoupper($method)]);
    }

    public function getUri(): UriInterface
    {
        return $this->uri ?? throw new \RuntimeException('URI is not set');
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $params = ['uri' => $uri];
        if (! $preserveHost && $uri->getHost() !== '') {
            $params['headerManager'] = $this->headerManager->withHeader('Host', $uri->getHost());
        }
        return clone($this, $params);
    }
}
