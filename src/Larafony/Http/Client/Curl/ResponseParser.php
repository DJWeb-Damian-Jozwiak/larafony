<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use CurlHandle;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Helpers\Request\HeaderManager;
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
        [$statusCode, $protocolVersion, $reasonPhrase, $headers] = $this->parseHeaders($headersRaw);

        // Create response
        return new Response(
            protocolVersion: $protocolVersion,
            headerManager: $headers,
            body: $this->streamFactory->createStream($bodyRaw),
            statusCode: $statusCode,
            reasonPhrase: $reasonPhrase,
        );
    }

    /**
     * Parse raw headers string into components.
     *
     * @return array{0: int, 1: string, 2: string|null, 3: HeaderManager}
     */
    private function parseHeaders(string $headersRaw): array
    {
        $lines = explode("\r\n", trim($headersRaw))
            |> (static fn (array $lines): array => array_filter($lines));
        $statusLine = array_first($lines);

        // Parse status line: "HTTP/1.1 200 OK"
        [$protocolVersion, $statusCode, $reasonPhrase] = $this->parseStatusLine($statusLine);

        // Parse headers (skip status line)
        $headerManager = new HeaderManager();
        foreach (array_slice($lines, 1) as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headerManager = $headerManager->withHeader(trim($parts[0]), trim($parts[1]));
            }
        }

        return [$statusCode, $protocolVersion, $reasonPhrase, $headerManager];
    }

    /**
     * Parse HTTP status line.
     *
     * @return array{0: string, 1: int, 2: string|null}
     */
    private function parseStatusLine(string $statusLine): array
    {
        $response = ['1.1', 500, 'Internal Server Error'];
        $matches = [];
        // Example: "HTTP/1.1 200 OK" or "HTTP/2 200 OK"
        // Note: HTTP/2 doesn't have a dot in version
        if (preg_match('/^HTTP\/(\d(?:\.\d)?)\s+(\d{3})\s*(.*)$/', $statusLine, $matches)) {
            $response = [
                $matches[1],                    // protocol version (1.1, 2, etc.)
                (int) $matches[2],              // status code
                $matches[3] !== '' ? $matches[3] : null,  // reason phrase
            ];
        }

        // Fallback
        return $response;
    }
}
