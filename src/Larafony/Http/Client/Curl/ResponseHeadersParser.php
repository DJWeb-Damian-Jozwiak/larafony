<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Client\Curl;

use Larafony\Framework\Http\Helpers\Request\HeaderManager;

final class ResponseHeadersParser
{
    /**
     * Parse raw headers string into components.
     *
     * @return array{0: int, 1: string, 2: string|null, 3: HeaderManager}
     */
    public function parse(string $rawHeaders): array
    {
        $lines = explode("\r\n", trim($rawHeaders))
                |> (static fn (array $lines): array => array_filter($lines));
        $statusLine = array_first($lines);

        // Parse status line: "HTTP/1.1 200 OK"
        [$protocolVersion, $statusCode, $reasonPhrase] = new StatusLineParser()->parse($statusLine ?? '');

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
}
