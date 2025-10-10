<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client;

use Larafony\Framework\Http\Client\CurlHttpClient;
use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Client\MockHttpClient;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Http\Response;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

final class HttpClientFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        HttpClientFactory::reset();
    }

    protected function tearDown(): void
    {
        HttpClientFactory::reset();
    }

    
    public function testReturnsCurlClientByDefault(): void
    {
        $client = HttpClientFactory::instance();

        $this->assertInstanceOf(CurlHttpClient::class, $client);
    }

    
    public function testReturnsSameInstanceOnMultipleCalls(): void
    {
        $client1 = HttpClientFactory::instance();
        $client2 = HttpClientFactory::instance();

        $this->assertSame($client1, $client2);
    }

    
    public function testCanSetCustomInstance(): void
    {
        $streamFactory = new StreamFactory();
        $customResponse = new Response(body: $streamFactory->createStream('Custom'), statusCode: 201);
        $mockClient = HttpClientFactory::fake(fn () => $customResponse);

        HttpClientFactory::withInstance($mockClient);

        $this->assertSame($mockClient, HttpClientFactory::instance());
    }

    
    public function testCanResetToDefault(): void
    {
        HttpClientFactory::fake(fn () => new Response(statusCode: 200));
        $this->assertInstanceOf(MockHttpClient::class, HttpClientFactory::instance());

        HttpClientFactory::reset();

        $this->assertInstanceOf(CurlHttpClient::class, HttpClientFactory::instance());
    }

    
    public function testCanFakeWithCallback(): void
    {
        $streamFactory = new StreamFactory();
        $expectedResponse = new Response(body: $streamFactory->createStream('Mocked'), statusCode: 200);

        $mockClient = HttpClientFactory::fake(fn () => $expectedResponse);

        $this->assertInstanceOf(MockHttpClient::class, $mockClient);
        $this->assertInstanceOf(MockHttpClient::class, HttpClientFactory::instance());

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = HttpClientFactory::sendRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

    
    public function testCanSendRequestThroughFactory(): void
    {
        $streamFactory = new StreamFactory();
        HttpClientFactory::fake(fn () => new Response(
            body: $streamFactory->createStream('Factory response'),
            statusCode: 200
        ));

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = HttpClientFactory::sendRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Factory response', (string) $response->getBody());
    }


    public function testFakeReturnsMockClientForAssertions(): void
    {
        $mockClient = HttpClientFactory::fake(fn () => new Response(statusCode: 200));

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        HttpClientFactory::sendRequest($request);

        // Can assert on the mock client
        $this->assertTrue($mockClient->hasRequests());
        $this->assertSame($request, $mockClient->getLastRequest());
    }

    public function testCanFakeWithCustomHandler(): void
    {
        $handler = new \Larafony\Framework\Http\Client\Mock\CallbackMockHandler(
            fn () => new Response(statusCode: 201)
        );

        $mockClient = HttpClientFactory::fakeWithHandler($handler);

        $this->assertInstanceOf(MockHttpClient::class, $mockClient);
        $this->assertSame($mockClient, HttpClientFactory::instance());

        $uriFactory = new UriFactory();
        $request = new Request('POST', $uriFactory->createUri('https://example.com'));
        $response = HttpClientFactory::sendRequest($request);

        $this->assertSame(201, $response->getStatusCode());
    }
}
