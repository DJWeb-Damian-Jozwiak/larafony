<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\TimeoutError;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class TimeoutErrorTest extends TestCase
{
    public function testCreatesTimeoutErrorWithCustomMessage(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $exception = TimeoutError::forTimeout($request, 30);

        $this->assertSame('Request timed out after 30 seconds', $exception->getMessage());
        $this->assertSame('GET', $exception->getMethod());
        $this->assertSame('https://example.com', $exception->getUri());
    }

    public function testStoresPreviousException(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $previous = new \RuntimeException('CURL timeout');
        $exception = TimeoutError::forTimeout($request, 30, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
