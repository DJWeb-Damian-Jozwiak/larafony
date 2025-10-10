<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Mock;

use Larafony\Framework\Http\Client\Contracts\MockHandler;
use Larafony\Framework\Http\Client\Mock\CallbackMockHandler;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Http\Response;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

final class CallbackMockHandlerTest extends TestCase
{
    
    public function testImplementsMockHandlerInterface(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));

        $this->assertInstanceOf(MockHandler::class, $handler);
    }

    
    public function testCallsCallbackWithRequest(): void
    {
        $capturedRequest = null;
        $handler = new CallbackMockHandler(function (RequestInterface $request) use (&$capturedRequest) {
            $capturedRequest = $request;
            return new Response(statusCode: 200);
        });

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $handler->handle($request);

        $this->assertSame($request, $capturedRequest);
    }

    
    public function testReturnsResponseFromCallback(): void
    {
        $streamFactory = new StreamFactory();
        $expectedResponse = new Response(
            body: $streamFactory->createStream('Created'),
            statusCode: 201,
        );
        $handler = new CallbackMockHandler(fn () => $expectedResponse);

        $uriFactory = new UriFactory();
        $request = new Request('POST', $uriFactory->createUri('https://example.com'));
        $response = $handler->handle($request);

        $this->assertSame($expectedResponse, $response);
    }

    
    public function testCanReturnDifferentResponsesBasedOnMethod(): void
    {
        $streamFactory = new StreamFactory();
        $handler = new CallbackMockHandler(function (RequestInterface $request) use ($streamFactory) {
            return match ($request->getMethod()) {
                'GET' => new Response(body: $streamFactory->createStream('GET response'), statusCode: 200),
                'POST' => new Response(body: $streamFactory->createStream('POST response'), statusCode: 201),
                default => new Response(statusCode: 405),
            };
        });

        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com');
        $getRequest = new Request('GET', $uri);
        $postRequest = new Request('POST', $uri);
        $deleteRequest = new Request('DELETE', $uri);

        $getResponse = $handler->handle($getRequest);
        $postResponse = $handler->handle($postRequest);
        $deleteResponse = $handler->handle($deleteRequest);

        $this->assertSame(200, $getResponse->getStatusCode());
        $this->assertSame('GET response', (string) $getResponse->getBody());

        $this->assertSame(201, $postResponse->getStatusCode());
        $this->assertSame('POST response', (string) $postResponse->getBody());

        $this->assertSame(405, $deleteResponse->getStatusCode());
    }

    
    public function testCanReturnDifferentResponsesBasedOnUrl(): void
    {
        $streamFactory = new StreamFactory();
        $handler = new CallbackMockHandler(function (RequestInterface $request) use ($streamFactory) {
            $uri = (string) $request->getUri();
            return match (true) {
                str_contains($uri, '/users') => new Response(body: $streamFactory->createStream('["user1", "user2"]'), statusCode: 200),
                str_contains($uri, '/posts') => new Response(body: $streamFactory->createStream('["post1", "post2"]'), statusCode: 200),
                default => new Response(statusCode: 404),
            };
        });

        $uriFactory = new UriFactory();
        $usersRequest = new Request('GET', $uriFactory->createUri('https://api.example.com/users'));
        $postsRequest = new Request('GET', $uriFactory->createUri('https://api.example.com/posts'));
        $notFoundRequest = new Request('GET', $uriFactory->createUri('https://api.example.com/invalid'));

        $usersResponse = $handler->handle($usersRequest);
        $postsResponse = $handler->handle($postsRequest);
        $notFoundResponse = $handler->handle($notFoundRequest);

        $this->assertSame('["user1", "user2"]', (string) $usersResponse->getBody());
        $this->assertSame('["post1", "post2"]', (string) $postsResponse->getBody());
        $this->assertSame(404, $notFoundResponse->getStatusCode());
    }

    
    public function testAlwaysHasResponses(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));

        $this->assertTrue($handler->hasResponses());

        // Even after handling requests
        $uriFactory = new UriFactory();
        $handler->handle(new Request('GET', $uriFactory->createUri('https://example.com')));
        $this->assertTrue($handler->hasResponses());
    }


    public function testResetIsANoop(): void
    {
        $callCount = 0;
        $handler = new CallbackMockHandler(function () use (&$callCount) {
            $callCount++;
            return new Response(statusCode: 200);
        });

        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com');
        $handler->handle(new Request('GET', $uri));
        $this->assertSame(1, $callCount);

        $handler->reset();

        $handler->handle(new Request('GET', $uri));
        $this->assertSame(2, $callCount);
    }
}
