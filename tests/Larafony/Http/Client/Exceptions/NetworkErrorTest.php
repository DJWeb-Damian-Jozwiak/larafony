<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\NetworkExceptionInterface;

final class NetworkErrorTest extends TestCase
{
    public function testImplementsPsr18NetworkExceptionInterface(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = NetworkError::fromRequest('Network failure', $request);

        $this->assertInstanceOf(NetworkExceptionInterface::class, $exception);
    }

    public function testStoresRequestMetadata(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = NetworkError::fromRequest('Network failure', $request);

        $this->assertSame('GET', $exception->getMethod());
        $this->assertSame('https://example.com', $exception->getUri());
    }

    public function testThrowsWhenGettingRequestObject(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = NetworkError::fromRequest('Network failure', $request);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Request object is not available');
        $exception->getRequest();
    }
}
