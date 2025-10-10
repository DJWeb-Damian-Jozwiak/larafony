<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Curl;

use Larafony\Framework\Http\Client\Curl\CurlOptionsBuilder;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UriFactory;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
use Larafony\Framework\Http\Request;
use PHPUnit\Framework\TestCase;

final class CurlOptionsBuilderTest extends TestCase
{
    public function testBuildsBasicOptionsForGetRequest(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com/api'));

        $options = $builder->build($request);

        $this->assertSame('https://example.com/api', $options[CURLOPT_URL]);
        $this->assertSame('GET', $options[CURLOPT_CUSTOMREQUEST]);
        $this->assertTrue($options[CURLOPT_RETURNTRANSFER]);
        $this->assertTrue($options[CURLOPT_HEADER]);
    }

    public function testBuildsOptionsWithHeaders(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $headerManager = new HeaderManager()
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Custom', 'value');

        $request = new Request(
            method: 'POST',
            uri: $uriFactory->createUri('https://example.com/api'),
            headerManager: $headerManager,
        );

        $options = $builder->build($request);

        $this->assertArrayHasKey(CURLOPT_HTTPHEADER, $options);
        $this->assertContains('Content-Type: application/json', $options[CURLOPT_HTTPHEADER]);
        $this->assertContains('X-Custom: value', $options[CURLOPT_HTTPHEADER]);
    }

    public function testBuildsOptionsWithBody(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $streamFactory = new StreamFactory();

        $request = new Request(
            method: 'POST',
            uri: $uriFactory->createUri('https://example.com/api'),
            body: $streamFactory->createStream('{"name":"John"}'),
        );

        $options = $builder->build($request);

        $this->assertSame('{"name":"John"}', $options[CURLOPT_POSTFIELDS]);
    }

    public function testSetsSecurityOptions(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $options = $builder->build($request);

        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testSetsRedirectOptions(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $options = $builder->build($request);

        $this->assertTrue($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertSame(10, $options[CURLOPT_MAXREDIRS]);
    }

    public function testSetsTimeoutOptions(): void
    {
        $builder = new CurlOptionsBuilder();
        $uriFactory = new UriFactory();
        $request = new Request('GET', $uriFactory->createUri('https://example.com'));

        $options = $builder->build($request);

        $this->assertSame(30, $options[CURLOPT_TIMEOUT]);
        $this->assertSame(10, $options[CURLOPT_CONNECTTIMEOUT]);
    }
}
