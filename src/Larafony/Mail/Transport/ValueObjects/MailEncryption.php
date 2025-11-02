<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport\ValueObjects;

/**
 * Value object representing SMTP encryption type.
 */
final class MailEncryption
{
    public bool $isSsl {
        get => $this->value === 'ssl';
    }

    public bool $isTls {
        get => $this->value === 'tls';
    }

    public bool $isNone {
        get => $this->value === 'none';
    }
    private function __construct(
        public private(set) string $value
    ) {
    }

    public static function ssl(): self
    {
        return new self('ssl');
    }

    public static function tls(): self
    {
        return new self('tls');
    }

    public static function none(): self
    {
        return new self('none');
    }

    public static function fromScheme(string $scheme): ?self
    {
        return match ($scheme) {
            'smtps' => self::ssl(),
            'smtp+tls', 'smtp+starttls' => self::tls(),
            default => null,
        };
    }
}
