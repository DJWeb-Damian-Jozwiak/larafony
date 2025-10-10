<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\RequestError;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\RequestExceptionInterface;

final class RequestErrorTest extends TestCase
{
    public function testImplementsPsr18RequestExceptionInterface(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = RequestError::fromRequest('Request failed', $request);

        $this->assertInstanceOf(RequestExceptionInterface::class, $exception);
    }

    public function testStoresRequestMetadata(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = RequestError::fromRequest('Request failed', $request);

        $this->assertSame('GET', $exception->getMethod());
        $this->assertSame('https://example.com', $exception->getUri());
    }

    public function testStoresMessageCodeAndPrevious(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $previous = new \RuntimeException('Previous error');
        $exception = RequestError::fromRequest('Request failed', $request, 456, $previous);

        $this->assertSame('Request failed', $exception->getMessage());
        $this->assertSame(456, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testThrowsWhenGettingRequestObject(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = RequestError::fromRequest('Request failed', $request);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Request object is not available');
        $exception->getRequest();
    }
}
