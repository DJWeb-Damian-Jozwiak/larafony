<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\BadRequestError;
use Larafony\Framework\Http\Client\Exceptions\ClientError;
use Larafony\Framework\Http\Client\Exceptions\HttpError;
use Larafony\Framework\Http\Client\Exceptions\InternalServerError;
use Larafony\Framework\Http\Client\Exceptions\NotFoundError;
use Larafony\Framework\Http\Client\Exceptions\ServerError;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

final class HttpErrorTest extends TestCase
{
    public function testStoresRequestAndResponseMetadata(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 404, reasonPhrase: 'Not Found');
        $exception = HttpError::fromResponse($request, $response);

        $this->assertSame('GET', $exception->getMethod());
        $this->assertSame('https://example.com', $exception->getUri());
        $this->assertSame(404, $exception->getStatusCode());
        $this->assertSame('Not Found', $exception->getReasonPhrase());
    }

    public function testCreatesClientErrorFor4xxStatus(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 404);

        $exception = HttpError::fromResponse($request, $response);

        $this->assertInstanceOf(ClientError::class, $exception);
        $this->assertInstanceOf(NotFoundError::class, $exception);
    }

    public function testCreatesServerErrorFor5xxStatus(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 500);

        $exception = HttpError::fromResponse($request, $response);

        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertInstanceOf(InternalServerError::class, $exception);
    }

    public function testCreatesSpecificClientErrors(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $badRequest = HttpError::fromResponse($request, new Response(statusCode: 400));
        $this->assertInstanceOf(BadRequestError::class, $badRequest);

        $notFound = HttpError::fromResponse($request, new Response(statusCode: 404));
        $this->assertInstanceOf(NotFoundError::class, $notFound);
    }

    public function testCreatesGenericClientErrorForUnknown4xx(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 418); // I'm a teapot

        $exception = HttpError::fromResponse($request, $response);

        $this->assertInstanceOf(ClientError::class, $exception);
        $this->assertNotInstanceOf(BadRequestError::class, $exception);
        $this->assertNotInstanceOf(NotFoundError::class, $exception);
    }

    public function testCreatesGenericServerErrorForUnknown5xx(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 599);

        $exception = HttpError::fromResponse($request, $response);

        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertNotInstanceOf(InternalServerError::class, $exception);
    }

    public function testClientErrorFromResponseCreatesDefaultInstance(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 429); // Too Many Requests - not in specific map

        $exception = ClientError::fromResponse($request, $response);

        $this->assertInstanceOf(ClientError::class, $exception);
        $this->assertStringContainsString('429', $exception->getMessage());
    }

    public function testServerErrorFromResponseCreatesDefaultInstance(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: 504); // Gateway Timeout - not in specific map

        $exception = ServerError::fromResponse($request, $response);

        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertStringContainsString('504', $exception->getMessage());
    }
}
