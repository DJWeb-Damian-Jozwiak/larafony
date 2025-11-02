<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport\ValueObjects;

use Larafony\Framework\Mail\Transport\ValueObjects\MailEncryption;
use PHPUnit\Framework\TestCase;

class MailEncryptionTest extends TestCase
{
    public function testCanCreateSslEncryption(): void
    {
        $encryption = MailEncryption::ssl();

        $this->assertSame('ssl', $encryption->value);
        $this->assertTrue($encryption->isSsl);
        $this->assertFalse($encryption->isTls);
        $this->assertFalse($encryption->isNone);
    }

    public function testCanCreateTlsEncryption(): void
    {
        $encryption = MailEncryption::tls();

        $this->assertSame('tls', $encryption->value);
        $this->assertTrue($encryption->isTls);
        $this->assertFalse($encryption->isSsl);
        $this->assertFalse($encryption->isNone);
    }

    public function testCanCreateNoneEncryption(): void
    {
        $encryption = MailEncryption::none();

        $this->assertSame('none', $encryption->value);
        $this->assertTrue($encryption->isNone);
        $this->assertFalse($encryption->isSsl);
        $this->assertFalse($encryption->isTls);
    }

    public function testFromSchemeWithSmtps(): void
    {
        $encryption = MailEncryption::fromScheme('smtps');

        $this->assertNotNull($encryption);
        $this->assertTrue($encryption->isSsl);
    }

    public function testFromSchemeWithSmtpTls(): void
    {
        $encryption = MailEncryption::fromScheme('smtp+tls');

        $this->assertNotNull($encryption);
        $this->assertTrue($encryption->isTls);
    }

    public function testFromSchemeWithSmtpStarttls(): void
    {
        $encryption = MailEncryption::fromScheme('smtp+starttls');

        $this->assertNotNull($encryption);
        $this->assertTrue($encryption->isTls);
    }

    public function testFromSchemeWithPlainSmtpReturnsNull(): void
    {
        $encryption = MailEncryption::fromScheme('smtp');

        $this->assertNull($encryption);
    }
}
