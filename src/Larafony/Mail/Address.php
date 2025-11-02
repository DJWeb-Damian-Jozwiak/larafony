<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

/**
 * Represents an email address with optional name.
 */
final readonly class Address implements \Stringable
{
    public function __construct(
        public string $email,
        public ?string $name = null
    ) {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        if ($this->name === null) {
            return $this->email;
        }

        return sprintf('"%s" <%s>', $this->name, $this->email);
    }
}
