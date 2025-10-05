<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Tests\Factories;

use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Factories\UriFactory;
use PHPUnit\Framework\TestCase;

final class RequestFactoryTest extends TestCase
{
    public function testCreateRequestWithStringUri(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com/path');

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://example.com/path', (string) $request->getUri());
    }

    public function testCreateRequestWithUriInterface(): void
    {
        $factory = new RequestFactory();
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('https://example.com/test');

        $request = $factory->createRequest('POST', $uri);

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($uri, $request->getUri());
    }

    public function testCreateRequestNormalizesMethodToUppercase(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('post', 'https://example.com');

        $this->assertSame('POST', $request->getMethod());
    }

    public function testGetRequestTargetReturnsPath(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com/api/users');

        $this->assertSame('/api/users', $request->getRequestTarget());
    }

    public function testGetRequestTargetIncludesQuery(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com/search?q=test');

        $this->assertSame('/search?q=test', $request->getRequestTarget());
    }

    public function testWithMethodCreatesNewInstance(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com');
        $newRequest = $request->withMethod('POST');

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('POST', $newRequest->getMethod());
    }

    public function testWithMethodNormalizesToUppercase(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com');
        $newRequest = $request->withMethod('delete');

        $this->assertSame('DELETE', $newRequest->getMethod());
    }

    public function testWithUriCreatesNewInstance(): void
    {
        $factory = new RequestFactory();
        $uriFactory = new UriFactory();

        $request = $factory->createRequest('GET', 'https://example.com');
        $newUri = $uriFactory->createUri('https://newhost.com/path');
        $newRequest = $request->withUri($newUri);

        $this->assertNotSame($request, $newRequest);
        $this->assertSame('example.com', $request->getUri()->getHost());
        $this->assertSame('newhost.com', $newRequest->getUri()->getHost());
    }

    public function testWithUriUpdatesHostHeader(): void
    {
        $factory = new RequestFactory();
        $uriFactory = new UriFactory();

        $request = $factory->createRequest('GET', 'https://example.com');
        $newUri = $uriFactory->createUri('https://newhost.com');
        $newRequest = $request->withUri($newUri);

        $this->assertTrue($newRequest->hasHeader('Host'));
        $this->assertSame(['newhost.com'], $newRequest->getHeader('Host'));
    }

    public function testWithUriPreservesHostWhenRequested(): void
    {
        $factory = new RequestFactory();
        $uriFactory = new UriFactory();

        $request = $factory->createRequest('GET', 'https://example.com');
        $request = $request->withHeader('Host', 'original.com');

        $newUri = $uriFactory->createUri('https://newhost.com');
        $newRequest = $request->withUri($newUri, true);

        $this->assertSame(['original.com'], $newRequest->getHeader('Host'));
    }

    public function testWithRequestTargetUpdatesPath(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com/old');
        $newRequest = $request->withRequestTarget('/new/path');

        $this->assertSame('/old', $request->getRequestTarget());
        $this->assertSame('/new/path', $newRequest->getRequestTarget());
    }

    public function testRequestInheritsFromMessage(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com');

        $withHeader = $request->withHeader('X-Custom', 'value');
        $this->assertTrue($withHeader->hasHeader('X-Custom'));
        $this->assertSame(['value'], $withHeader->getHeader('X-Custom'));
    }

    public function testRequestSupportsProtocolVersion(): void
    {
        $factory = new RequestFactory();
        $request = $factory->createRequest('GET', 'https://example.com');

        $this->assertSame('1.1', $request->getProtocolVersion());

        $withVersion = $request->withProtocolVersion('2.0');
        $this->assertSame('2.0', $withVersion->getProtocolVersion());
    }

    public function testRequestSupportsBody(): void
    {
        $factory = new RequestFactory();
        $streamFactory = new \Larafony\Framework\Http\Factories\StreamFactory();
        $request = $factory->createRequest('POST', 'https://example.com');

        $this->assertSame('', (string) $request->getBody());

        $withBody = $request->withBody($streamFactory->createStream('request body'));
        $this->assertSame('request body', (string) $withBody->getBody());
    }

    public function testMultipleMethodTypes(): void
    {
        $factory = new RequestFactory();

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

        foreach ($methods as $method) {
            $request = $factory->createRequest($method, 'https://example.com');
            $this->assertSame($method, $request->getMethod());
        }
    }
}
