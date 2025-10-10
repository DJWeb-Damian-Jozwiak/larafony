<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Curl;

use Larafony\Framework\Http\Client\Curl\CurlHandleExecutor;
use Larafony\Framework\Http\Client\Exceptions\ConnectionError;
use Larafony\Framework\Http\Client\Exceptions\DnsError;
use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Client\Exceptions\TimeoutError;
use Larafony\Framework\Http\Client\Testing\FakeCurlWrapper;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CurlHandleExecutor using FakeCurlWrapper.
 *
 * This allows us to test all error paths without making real network calls.
 */
final class CurlHandleExecutorTest extends TestCase
{
    public function testThrowsTimeoutErrorOnCurlTimeout(): void
    {
        $fake = (new FakeCurlWrapper())->withError(28, 'Operation timed out');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(TimeoutError::class);
        $this->expectExceptionMessage('Request timed out after 0 seconds');

        $executor->execute($request);
    }

    public function testThrowsDnsErrorOnHostResolutionFailure(): void
    {
        $fake = (new FakeCurlWrapper())->withError(CURLE_COULDNT_RESOLVE_HOST, 'Could not resolve host');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://invalid-host-that-does-not-exist.com'));

        $this->expectException(DnsError::class);
        $this->expectExceptionMessage('Could not resolve host: invalid-host-that-does-not-exist.com');

        $executor->execute($request);
    }

    public function testThrowsConnectionErrorOnConnectionFailure(): void
    {
        $fake = (new FakeCurlWrapper())->withError(CURLE_COULDNT_CONNECT, 'Failed to connect');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(ConnectionError::class);
        $this->expectExceptionMessage('Could not connect to host: example.com');

        $executor->execute($request);
    }

    public function testThrowsConnectionErrorOnSslError(): void
    {
        $fake = (new FakeCurlWrapper())->withError(CURLE_SSL_CONNECT_ERROR, 'SSL connection error');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(ConnectionError::class);
        $this->expectExceptionMessage('SSL/TLS error: SSL connection error');

        $executor->execute($request);
    }

    public function testThrowsConnectionErrorOnSslCertProblem(): void
    {
        $fake = (new FakeCurlWrapper())->withError(CURLE_SSL_CERTPROBLEM, 'SSL certificate problem');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(ConnectionError::class);
        $this->expectExceptionMessage('SSL/TLS error: SSL certificate problem');

        $executor->execute($request);
    }

    public function testThrowsNetworkErrorForUnknownCurlError(): void
    {
        $fake = (new FakeCurlWrapper())->withError(999, 'Unknown error');
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(NetworkError::class);
        $this->expectExceptionMessage('CURL error (999): Unknown error');

        $executor->execute($request);
    }

    public function testThrowsNetworkErrorWhenCurlExecReturnsFalse(): void
    {
        $fake = (new FakeCurlWrapper())->withFailure();
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $this->expectException(NetworkError::class);
        $this->expectExceptionMessage('CURL execution failed');

        $executor->execute($request);
    }

    public function testSuccessfullyExecutesRequestWithFakeCurl(): void
    {
        $headers = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n";
        $body = "<html>Test</html>";
        $rawResponse = $headers . $body;
        $fake = (new FakeCurlWrapper())->withResponse($rawResponse, strlen($headers));
        $executor = new CurlHandleExecutor(curlWrapper: $fake);

        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $response = $executor->execute($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>Test</html>', (string) $response->getBody());
    }
}
