<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Transport;

use Larafony\Framework\Mail\Assert\CommandLengthIsValid;

/**
 * Represents an SMTP command.
 */
final readonly class SmtpCommand implements \Stringable
{
    private function __construct(
        public private(set) string $value
    ) {
        CommandLengthIsValid::assert($value);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public static function ehlo(string $hostname = 'localhost'): self
    {
        return new self("EHLO {$hostname}");
    }

    public static function authLogin(): self
    {
        return new self('AUTH LOGIN');
    }

    public static function username(string $username): self
    {
        return new self(base64_encode($username));
    }

    public static function password(string $password): self
    {
        return new self(base64_encode($password));
    }

    public static function mailFrom(string $email): self
    {
        return new self("MAIL FROM:<{$email}>");
    }

    public static function rcptTo(string $email): self
    {
        return new self("RCPT TO:<{$email}>");
    }

    public static function data(): self
    {
        return new self('DATA');
    }

    public static function dataEnd(): self
    {
        return new self('.');
    }

    public static function quit(): self
    {
        return new self('QUIT');
    }

    public function toString(): string
    {
        return $this->value . "\r\n";
    }
}
