<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Request\AttributesManager;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Helpers\Request\HeadersParser;
use Larafony\Framework\Http\Helpers\Request\ParsedBodyManager;
use Larafony\Framework\Http\Helpers\Request\ParsedBodyParser;
use Larafony\Framework\Http\Helpers\Request\QueryParamsManager;
use Larafony\Framework\Http\Helpers\Request\UploadedFilesManager;
use Larafony\Framework\Http\Helpers\Request\UploadedFilesParser;
use Larafony\Framework\Http\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

final readonly class ServerRequestFactory implements ServerRequestFactoryInterface
{
    public function __construct(
        private UriFactory $uriFactory = new UriFactory(),
        private StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    /**
     * @param array<string, mixed> $serverParams
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $uriInstance = $uri instanceof UriInterface
            ? $uri
            : $this->uriFactory->createUri((string) $uri);

        return new ServerRequest(
            method: strtoupper($method),
            uri: $uriInstance,
            body: $this->streamFactory->createStream(),
            serverParams: $serverParams,
        );
    }

    /**
     * @throws \JsonException
     */
    public function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $uri = $this->uriFactory->createUriFromGlobals();

        return new ServerRequest(
            method: $_SERVER['REQUEST_METHOD'] ?? 'GET',
            uri: $uri,
            protocolVersion: $this->getProtocolVersion(),
            headerManager: new HeaderManager(HeadersParser::parseFromGlobals()),
            body: $this->streamFactory->createStreamFromFile('php://input', 'r'),
            serverParams: $_SERVER,
            cookieParams: $_COOKIE,
            queryParamsManager: new QueryParamsManager($_GET),
            uploadedFilesManager: new UploadedFilesManager(UploadedFilesParser::parseFromGlobals()),
            parsedBodyManager: new ParsedBodyManager(ParsedBodyParser::parseFromGlobals()),
            attributesManager: new AttributesManager(),
        );
    }

    private function getProtocolVersion(): string
    {
        $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';

        return str_starts_with($protocol, 'HTTP/')
            ? substr($protocol, 5)
            : '1.1';
    }
}
