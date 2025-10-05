<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

final readonly class RequestFactory implements RequestFactoryInterface
{
    public function __construct(
        private UriFactory $uriFactory = new UriFactory(),
        private StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    public function createRequest(string $method, $uri): RequestInterface
    {
        $uriInstance = $uri instanceof UriInterface
            ? $uri
            : $this->uriFactory->createUri((string) $uri);

        return new Request(
            method: strtoupper($method),
            uri: $uriInstance,
            body: $this->streamFactory->createStream(),
        );
    }
}
