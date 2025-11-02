<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport\ValueObjects;

use Larafony\Framework\Mail\Transport\ValueObjects\MailEncryption;
use Larafony\Framework\Mail\Transport\ValueObjects\MailPort;
use PHPUnit\Framework\TestCase;

class MailPortTest extends TestCase
{
    public function testCanCreatePortWithValue(): void
    {
        $port = new MailPort(587);

        $this->assertSame(587, $port->value);
    }

    public function testFromEncryptionWithSsl(): void
    {
        $port = MailPort::fromEncryption(MailEncryption::ssl());

        $this->assertSame(465, $port->value);
    }

    public function testFromEncryptionWithTls(): void
    {
        $port = MailPort::fromEncryption(MailEncryption::tls());

        $this->assertSame(587, $port->value);
    }

    public function testFromEncryptionWithNull(): void
    {
        $port = MailPort::fromEncryption(null);

        $this->assertSame(25, $port->value);
    }

    public function testFromIntWithExplicitPort(): void
    {
        $port = MailPort::fromInt(2525, null);

        $this->assertSame(2525, $port->value);
    }

    public function testFromIntWithNullUsesEncryptionDefault(): void
    {
        $port = MailPort::fromInt(null, MailEncryption::ssl());

        $this->assertSame(465, $port->value);
    }

    public function testFromIntWithNullAndNoEncryptionUsesDefault(): void
    {
        $port = MailPort::fromInt(null, null);

        $this->assertSame(25, $port->value);
    }
}
