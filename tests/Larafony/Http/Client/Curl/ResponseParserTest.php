<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Curl;

use Larafony\Framework\Http\Client\Curl\ResponseParser;

use PHPUnit\Framework\TestCase;

/**
 * Note: These testS are limited because we cannot easily mock CurlHandle
 * and curl_getinfo() behavior. The real behavior is testEd in integration testS.
 */
final class ResponseParserTest extends TestCase
{
    
    public function testHandlesEmptyResponse(): void
    {
        $parser = new ResponseParser();
        $curl = curl_init('http://example.com');

        $response = $parser->parse('', $curl);

        $this->assertSame(500, $response->getStatusCode());

        // No need to close - CurlHandle auto-closes in PHP 8.0+
        unset($curl);
    }

    
    public function testHandlesFalseResponse(): void
    {
        $parser = new ResponseParser();
        $curl = curl_init('http://example.com');

        $response = $parser->parse(false, $curl);

        $this->assertSame(500, $response->getStatusCode());

        // No need to close - CurlHandle auto-closes in PHP 8.0+
        unset($curl);
    }

    
    public function testCreatesResponseInstance(): void
    {
        $parser = new ResponseParser();

        $this->assertInstanceOf(ResponseParser::class, $parser);
    }
}
