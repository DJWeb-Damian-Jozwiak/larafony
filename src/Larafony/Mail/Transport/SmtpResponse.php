<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Mail\Exceptions\TransportError;

/**
 * Represents an SMTP response.
 */
final class SmtpResponse
{
    public bool $isSuccess {
        get => $this->code >= 200 && $this->code < 400;
    }

    public bool $isError {
        get => $this->code >= 400;
    }
    public function __construct(
        public private(set) int $code,
        public private(set) string $message
    ) {
    }

    public static function fromString(string $response): self
    {
        $code = (int) substr($response, 0, 3);
        $message = trim(substr($response, 4));

        return new self($code, $message);
    }

    public function assertSuccess(): void
    {
        if ($this->isError) {
            throw new TransportError("SMTP Error [{$this->code}]: {$this->message}");
        }
    }
}
