<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use CurlHandle;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Parses raw CURL response into PSR-7 Response object.
 *
 * Handles:
 * - Separating headers from body
 * - Parsing HTTP status code and reason phrase
 * - Parsing response headers
 * - Creating PSR-7 Response with proper protocol version
 */
final class ResponseParser
{
    public function __construct(
        private readonly StreamFactory $streamFactory = new StreamFactory(),
    ) {
    }

    /**
     * Parse raw CURL response into PSR-7 Response.
     *
     * @param string|bool $rawResponse Raw response from curl_exec()
     * @param CurlHandle $curl Active CURL handle
     *
     * @return ResponseInterface
     */
    public function parse(string|bool $rawResponse, CurlHandle $curl, ?int $headerSize = null): ResponseInterface
    {
        if ($rawResponse === false || $rawResponse === '' || $rawResponse === true) {
            return new Response(statusCode: 500);
        }

        // Get header size to separate headers from body
        if ($headerSize === null) {
            /** @var int $headerSize */
            $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        }

        $headersRaw = substr($rawResponse, 0, $headerSize);
        $bodyRaw = substr($rawResponse, $headerSize);

        // Parse status line and headers
        [$statusCode, $protocolVersion, $reasonPhrase, $headers] = new ResponseHeadersParser()->parse($headersRaw);

        // Create response
        return new Response(
            protocolVersion: $protocolVersion,
            headerManager: $headers,
            body: $this->streamFactory->createStream($bodyRaw),
            statusCode: $statusCode,
            reasonPhrase: $reasonPhrase,
        );
    }
}
