<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client;

use Larafony\Framework\Http\Client\Exceptions\HttpClientError;
use Larafony\Framework\Http\Client\Mock\CallbackMockHandler;
use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Http\Response;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

final class MockHttpClientTest extends TestCase
{
    
    public function testImplementsPsr18ClientInterface(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));
        $client = new MockHttpClient($handler);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    
    public function testReturnsResponseFromHandler(): void
    {
        $expectedResponse = new Response(statusCode: 201);
        $handler = new CallbackMockHandler(fn () => $expectedResponse);
        $client = new MockHttpClient($handler);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = $client->sendRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

    
    public function testRecordsRequestHistory(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));
        $client = new MockHttpClient($handler);

        $uriFactory = new UriFactory();
        $request1 = new Request('GET', $uriFactory->createUri('https://example.com/1'));
        $request2 = new Request('POST', $uriFactory->createUri('https://example.com/2'));

        $client->sendRequest($request1);
        $client->sendRequest($request2);

        $this->assertCount(2, $client->getRequestHistory());
        $this->assertSame($request1, $client->getRequestHistory()[0]);
        $this->assertSame($request2, $client->getRequestHistory()[1]);
    }

    
    public function testReturnsLastRequest(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));
        $client = new MockHttpClient($handler);

        $uriFactory = new UriFactory();
        $request1 = new Request('GET', $uriFactory->createUri('https://example.com/1'));
        $request2 = new Request('POST', $uriFactory->createUri('https://example.com/2'));

        $client->sendRequest($request1);
        $client->sendRequest($request2);

        $this->assertSame($request2, $client->getLastRequest());
    }

    
    public function testChecksIfHasRequests(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));
        $client = new MockHttpClient($handler);

        $this->assertFalse($client->hasRequests());

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $client->sendRequest($request);

        $this->assertTrue($client->hasRequests());
    }

    
    public function testCanResetHistory(): void
    {
        $handler = new CallbackMockHandler(fn () => new Response(statusCode: 200));
        $client = new MockHttpClient($handler);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $client->sendRequest($request);

        $this->assertTrue($client->hasRequests());

        $client->resetHistory();

        $this->assertFalse($client->hasRequests());
        $this->assertNull($client->getLastRequest());
    }

    
    public function testThrowsIfHandlerHasNoResponses(): void
    {
        // Create a handler that returns false for hasResponses()
        $handler = new class implements \Larafony\Framework\Http\Client\Contracts\MockHandler {
            public function handle(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                throw new \RuntimeException('Should not be called');
            }

            public function hasResponses(): bool
            {
                return false;
            }

            public function reset(): void
            {
            }
        };

        $client = new MockHttpClient($handler);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(HttpClientError::class);
        $this->expectExceptionMessage('Mock handler has no more responses available');

        $client->sendRequest($request);
    }
}
