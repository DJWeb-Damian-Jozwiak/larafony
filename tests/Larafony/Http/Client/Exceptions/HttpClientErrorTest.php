<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\HttpClientError;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

final class HttpClientErrorTest extends TestCase
{
    public function testImplementsPsr18ClientExceptionInterface(): void
    {
        $exception = new HttpClientError('Test error');

        $this->assertInstanceOf(ClientExceptionInterface::class, $exception);
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new HttpClientError('Test error');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testStoresMessageAndCode(): void
    {
        $exception = new HttpClientError('Test error', 123);

        $this->assertSame('Test error', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
    }

    public function testStoresPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new HttpClientError('Test error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
