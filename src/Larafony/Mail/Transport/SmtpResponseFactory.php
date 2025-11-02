<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Mail\Exceptions\TransportError;

/**
 * Factory for creating SMTP responses from server output.
 *
 * SMTP Protocol Response Format (RFC 5321):
 * ==========================================
 *
 * SMTP responses consist of a 3-digit status code followed by optional text.
 * Responses can span multiple lines, with each line starting with the same code.
 *
 * Single-line response:
 * ---------------------
 * 250 OK
 * └─┬─┘ └┬┘
 *   │    └─── Message text
 *   └──────── 3-digit code followed by SPACE
 *
 * Multi-line response:
 * --------------------
 * 250-mail.example.com
 * 250-SIZE 52428800
 * 250-8BITMIME
 * 250 HELP
 * └─┬─┘ └┬┘
 *   │    └─── Last line: SPACE after code indicates end
 *   └──────── Middle lines: HYPHEN after code indicates continuation
 *
 * Response Code Categories:
 * -------------------------
 * 2xx - Success (e.g., 250 OK, 220 Ready)
 * 3xx - Intermediate (e.g., 354 Start mail input)
 * 4xx - Transient failure (e.g., 421 Service not available)
 * 5xx - Permanent failure (e.g., 550 Mailbox unavailable)
 *
 * Multi-line Detection Logic:
 * ---------------------------
 * We read lines until we find one where character at index 3 is a SPACE.
 * - Index 0-2: Three-digit code (e.g., "250")
 * - Index 3: Either '-' (more lines follow) or ' ' (last line)
 * - Index 4+: Message text
 *
 * Examples:
 * ---------
 * "250 OK"              → Single line (space at index 3)
 * "250-HELP\n250 OK"    → Multi-line (hyphen at index 3, then space)
 * "354 Start input"     → Single line waiting for data
 */
final class SmtpResponseFactory
{
    /**
     * Read and parse SMTP response from connection.
     *
     * Reads lines from the SMTP connection until it encounters a line where
     * the character at position 3 is a space (indicating the final line).
     *
     * @param SmtpConnection $connection Active SMTP connection
     *
     * @return SmtpResponse Parsed SMTP response
     *
     * @throws TransportError If connection is not established or reading fails
     */
    public static function readFromConnection(SmtpConnection $connection): SmtpResponse
    {
        $response = '';

        while ($line = $connection->readLine()) {
            $response .= $line;

            // Check if this is the last line (space after code instead of hyphen)
            // Example: "250 OK" (last) vs "250-HELP" (continuation)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return SmtpResponse::fromString(trim($response));
    }
}
