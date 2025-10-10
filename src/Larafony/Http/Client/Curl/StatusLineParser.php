<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

final class StatusLineParser
{
    /**
     * Parse HTTP status line.
     *
     * @return array{0: string, 1: int, 2: string|null}
     */
    public function parse(string $statusLine): array
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
