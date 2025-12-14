<?php

declare(strict_types=1);

namespace Larafony\Framework\WebSockets\Protocol;

final class Handshake
{
    private const string GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    /**
     * @return array<string, string>|null
     */
    public static function parseRequest(string $request): ?array
    {
        if (! str_contains($request, "\r\n\r\n")) {
            return null;
        }

        $lines = preg_split("/\r\n/", $request);

        if ($lines === false) {
            return null;
        }

        $headers = [];

        foreach ($lines as $line) {
            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    public static function createAcceptKey(string $secWebSocketKey): string
    {
        return base64_encode(
            pack('H*', sha1($secWebSocketKey . self::GUID))
        );
    }

    public static function createResponse(string $secWebSocketKey): string
    {
        $acceptKey = self::createAcceptKey($secWebSocketKey);

        return sprintf(
            '%s%s%s%s',
            "HTTP/1.1 101 Switching Protocols\r\n",
            "Upgrade: websocket\r\n",
            "Connection: Upgrade\r\n",
            "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n"
        );
    }

    public static function createErrorResponse(int $code, string $message): string
    {
        return "HTTP/{$code} {$message}\r\n\r\n";
    }

    /**
     * @param array<string, string> $headers
     */
    public static function isValidUpgradeRequest(array $headers): bool
    {
        return isset($headers['Sec-WebSocket-Key'])
            && isset($headers['Upgrade'])
            && strtolower($headers['Upgrade']) === 'websocket';
    }
}
