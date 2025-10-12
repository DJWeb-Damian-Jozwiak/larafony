<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Exceptions;

use Larafony\Framework\Http\Client\Exceptions\BadGatewayError;
use Larafony\Framework\Http\Client\Exceptions\ClientError;
use Larafony\Framework\Http\Client\Exceptions\ConnectionError;
use Larafony\Framework\Http\Client\Exceptions\DnsError;
use Larafony\Framework\Http\Client\Exceptions\ForbiddenError;
use Larafony\Framework\Http\Client\Exceptions\InvalidRequestError;
use Larafony\Framework\Http\Client\Exceptions\NetworkError;
use Larafony\Framework\Http\Client\Exceptions\RequestError;
use Larafony\Framework\Http\Client\Exceptions\ServerError;
use Larafony\Framework\Http\Client\Exceptions\ServiceUnavailableError;
use Larafony\Framework\Http\Client\Exceptions\TooManyRedirectsError;
use Larafony\Framework\Http\Client\Exceptions\UnauthorizedError;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SpecificExceptionsTest extends TestCase
{
    /**
     * @param class-string $expectedClass
     * @param class-string $parentClass
     */
    #[DataProvider('httpResponseExceptionProvider')]
    public function testHttpResponseBasedExceptions(
        string $factoryMethod,
        int $statusCode,
        string $expectedMessage,
        string $expectedClass,
        string $parentClass,
    ): void {
        $uriFactory = new UriFactory();
        $request = new Request('POST', $uriFactory->createUri('https://api.example.com/users'));
        $response = new Response(statusCode: $statusCode, reasonPhrase: 'Test Reason');

        /** @var ClientError|ServerError $exception */
        $exception = $expectedClass::$factoryMethod($request, $response);

        $this->assertInstanceOf($expectedClass, $exception);
        $this->assertInstanceOf($parentClass, $exception);
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame('POST', $exception->getMethod());
        $this->assertSame('https://api.example.com/users', $exception->getUri());
        $this->assertSame($statusCode, $exception->getStatusCode());
        $this->assertSame('Test Reason', $exception->getReasonPhrase());
        $this->assertSame($statusCode, $exception->getCode());
    }

    /**
     * @return array<string, array{factoryMethod: string, statusCode: int, expectedMessage: string, expectedClass: class-string, parentClass: class-string}>
     */
    public static function httpResponseExceptionProvider(): array
    {
        return [
            'UnauthorizedError' => [
                'factoryMethod' => 'fromResponse',
                'statusCode' => 401,
                'expectedMessage' => '401 Unauthorized',
                'expectedClass' => UnauthorizedError::class,
                'parentClass' => ClientError::class,
            ],
            'ForbiddenError' => [
                'factoryMethod' => 'fromResponse',
                'statusCode' => 403,
                'expectedMessage' => '403 Forbidden',
                'expectedClass' => ForbiddenError::class,
                'parentClass' => ClientError::class,
            ],
            'BadGatewayError' => [
                'factoryMethod' => 'fromResponse',
                'statusCode' => 502,
                'expectedMessage' => '502 Bad Gateway',
                'expectedClass' => BadGatewayError::class,
                'parentClass' => ServerError::class,
            ],
            'ServiceUnavailableError' => [
                'factoryMethod' => 'fromResponse',
                'statusCode' => 503,
                'expectedMessage' => '503 Service Unavailable',
                'expectedClass' => ServiceUnavailableError::class,
                'parentClass' => ServerError::class,
            ],
        ];
    }

    /**
     * @param class-string $expectedClass
     * @param class-string $parentClass
     */
    #[DataProvider('networkExceptionProvider')]
    public function testNetworkBasedExceptions(
        string $factoryMethod,
        array $factoryArgs,
        string $expectedMessagePattern,
        string $expectedClass,
        string $parentClass,
    ): void {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com/api'));

        /** @var NetworkError|RequestError $exception */
        $exception = $expectedClass::$factoryMethod($request, ...$factoryArgs);

        $this->assertInstanceOf($expectedClass, $exception);
        $this->assertInstanceOf($parentClass, $exception);
        $this->assertMatchesRegularExpression($expectedMessagePattern, $exception->getMessage());
        $this->assertSame('GET', $exception->getMethod());
        $this->assertSame('https://example.com/api', $exception->getUri());
    }

    /**
     * @return array<string, array{factoryMethod: string, factoryArgs: array<mixed>, expectedMessagePattern: string, expectedClass: class-string, parentClass: class-string}>
     */
    public static function networkExceptionProvider(): array
    {
        return [
            'DnsError' => [
                'factoryMethod' => 'couldNotResolve',
                'factoryArgs' => ['example.com'],
                'expectedMessagePattern' => '/Could not resolve host: example\.com/',
                'expectedClass' => DnsError::class,
                'parentClass' => NetworkError::class,
            ],
            'ConnectionError - couldNotConnect' => [
                'factoryMethod' => 'couldNotConnect',
                'factoryArgs' => ['example.com'],
                'expectedMessagePattern' => '/Could not connect to host: example\.com/',
                'expectedClass' => ConnectionError::class,
                'parentClass' => NetworkError::class,
            ],
            'ConnectionError - sslError' => [
                'factoryMethod' => 'sslError',
                'factoryArgs' => ['Certificate verification failed'],
                'expectedMessagePattern' => '/SSL\/TLS error: Certificate verification failed/',
                'expectedClass' => ConnectionError::class,
                'parentClass' => NetworkError::class,
            ],
            'InvalidRequestError - malformedUrl' => [
                'factoryMethod' => 'malformedUrl',
                'factoryArgs' => ['htp://invalid'],
                'expectedMessagePattern' => '/Malformed URL: htp:\/\/invalid/',
                'expectedClass' => InvalidRequestError::class,
                'parentClass' => RequestError::class,
            ],
            'InvalidRequestError - invalidMethod' => [
                'factoryMethod' => 'invalidMethod',
                'factoryArgs' => ['INVALID'],
                'expectedMessagePattern' => '/Invalid HTTP method: INVALID/',
                'expectedClass' => InvalidRequestError::class,
                'parentClass' => RequestError::class,
            ],
            'TooManyRedirectsError' => [
                'factoryMethod' => 'forTooManyRedirects',
                'factoryArgs' => [10, null],
                'expectedMessagePattern' => '/Too many redirects encountered \(max: 10\)/',
                'expectedClass' => TooManyRedirectsError::class,
                'parentClass' => RequestError::class,
            ],
        ];
    }

    public function testTooManyRedirectsErrorWithPreviousException(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $previous = new \RuntimeException('Redirect loop detected');

        $exception = TooManyRedirectsError::forTooManyRedirects($request, 5, $previous);

        $this->assertInstanceOf(TooManyRedirectsError::class, $exception);
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertStringContainsString('max: 5', $exception->getMessage());
    }

    public function testConnectionErrorInheritanceChain(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $exception = ConnectionError::couldNotConnect($request, 'example.com');

        $this->assertInstanceOf(ConnectionError::class, $exception);
        $this->assertInstanceOf(NetworkError::class, $exception);
        $this->assertInstanceOf(\Psr\Http\Client\NetworkExceptionInterface::class, $exception);
    }

    public function testDnsErrorInheritanceChain(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $exception = DnsError::couldNotResolve($request, 'example.com');

        $this->assertInstanceOf(DnsError::class, $exception);
        $this->assertInstanceOf(NetworkError::class, $exception);
        $this->assertInstanceOf(\Psr\Http\Client\NetworkExceptionInterface::class, $exception);
    }

    public function testInvalidRequestErrorInheritanceChain(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $exception = InvalidRequestError::malformedUrl($request, 'invalid');

        $this->assertInstanceOf(InvalidRequestError::class, $exception);
        $this->assertInstanceOf(RequestError::class, $exception);
        $this->assertInstanceOf(\Psr\Http\Client\RequestExceptionInterface::class, $exception);
    }

    public function testTooManyRedirectsErrorInheritanceChain(): void
    {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $exception = TooManyRedirectsError::forTooManyRedirects($request, 10);

        $this->assertInstanceOf(TooManyRedirectsError::class, $exception);
        $this->assertInstanceOf(RequestError::class, $exception);
        $this->assertInstanceOf(\Psr\Http\Client\RequestExceptionInterface::class, $exception);
    }

    /**
     * @param class-string $expectedSpecificClass
     */
    #[DataProvider('clientErrorFactoryProvider')]
    public function testClientErrorFromResponseCreatesSpecificExceptions(
        int $statusCode,
        string $expectedSpecificClass,
    ): void {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: $statusCode);

        $exception = ClientError::fromResponse($request, $response);

        $this->assertInstanceOf($expectedSpecificClass, $exception);
        $this->assertInstanceOf(ClientError::class, $exception);
        $this->assertSame($statusCode, $exception->getStatusCode());
    }

    /**
     * @return array<string, array{statusCode: int, expectedSpecificClass: class-string}>
     */
    public static function clientErrorFactoryProvider(): array
    {
        return [
            '401 creates UnauthorizedError' => [
                'statusCode' => 401,
                'expectedSpecificClass' => UnauthorizedError::class,
            ],
            '403 creates ForbiddenError' => [
                'statusCode' => 403,
                'expectedSpecificClass' => ForbiddenError::class,
            ],
        ];
    }

    /**
     * @param class-string $expectedSpecificClass
     */
    #[DataProvider('serverErrorFactoryProvider')]
    public function testServerErrorFromResponseCreatesSpecificExceptions(
        int $statusCode,
        string $expectedSpecificClass,
    ): void {
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));
        $response = new Response(statusCode: $statusCode);

        $exception = ServerError::fromResponse($request, $response);

        $this->assertInstanceOf($expectedSpecificClass, $exception);
        $this->assertInstanceOf(ServerError::class, $exception);
        $this->assertSame($statusCode, $exception->getStatusCode());
    }

    /**
     * @return array<string, array{statusCode: int, expectedSpecificClass: class-string}>
     */
    public static function serverErrorFactoryProvider(): array
    {
        return [
            '502 creates BadGatewayError' => [
                'statusCode' => 502,
                'expectedSpecificClass' => BadGatewayError::class,
            ],
            '503 creates ServiceUnavailableError' => [
                'statusCode' => 503,
                'expectedSpecificClass' => ServiceUnavailableError::class,
            ],
        ];
    }
}
