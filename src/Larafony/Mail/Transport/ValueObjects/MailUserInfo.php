<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport\ValueObjects;

/**
 * Value object representing SMTP authentication credentials.
 */
final class MailUserInfo
{
    public bool $hasCredentials {
        get => $this->username !== null;
    }
    public function __construct(
        public private(set) ?string $username = null,
        public private(set) ?string $password = null
    ) {
    }

    public static function fromString(string $userInfo): self
    {
        if (! str_contains($userInfo, ':')) {
            return new self($userInfo, null);
        }

        [$username, $password] = explode(':', $userInfo, 2);
        return new self($username, $password);
    }
}
