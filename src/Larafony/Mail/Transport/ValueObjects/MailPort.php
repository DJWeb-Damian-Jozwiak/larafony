<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport\ValueObjects;

/**
 * Value object representing SMTP port.
 */
final class MailPort
{
    public function __construct(
        public private(set) int $value
    ) {
    }

    public static function fromEncryption(?MailEncryption $encryption): self
    {
        $port = match ($encryption?->value) {
            'ssl' => 465,
            'tls' => 587,
            default => 25,
        };

        return new self($port);
    }

    public static function fromInt(?int $port, ?MailEncryption $encryption): self
    {
        if ($port !== null) {
            return new self($port);
        }

        return self::fromEncryption($encryption);
    }
}
