<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail\Transport;

use Larafony\Framework\Mail\Transport\SmtpConfig;
use PHPUnit\Framework\TestCase;

class SmtpConfigTest extends TestCase
{
    public function testFromDsnWithBasicSmtp(): void
    {
        $config = SmtpConfig::fromDsn('smtp://localhost:1025');

        $this->assertSame('localhost', $config->host);
        $this->assertSame(1025, $config->port->value);
        $this->assertNull($config->userInfo->username);
        $this->assertNull($config->userInfo->password);
        $this->assertNull($config->encryption);
    }

    public function testFromDsnWithAuthentication(): void
    {
        $config = SmtpConfig::fromDsn('smtp://user:pass@smtp.example.com:587');

        $this->assertSame('smtp.example.com', $config->host);
        $this->assertSame(587, $config->port->value);
        $this->assertSame('user', $config->userInfo->username);
        $this->assertSame('pass', $config->userInfo->password);
    }

    public function testFromDsnWithSslScheme(): void
    {
        $config = SmtpConfig::fromDsn('smtps://smtp.example.com');

        $this->assertNotNull($config->encryption);
        $this->assertTrue($config->encryption->isSsl);
        $this->assertSame(465, $config->port->value);
    }

    public function testFromDsnWithTlsScheme(): void
    {
        $config = SmtpConfig::fromDsn('smtp+tls://smtp.example.com');

        $this->assertNotNull($config->encryption);
        $this->assertTrue($config->encryption->isTls);
        $this->assertSame(587, $config->port->value);
    }

    public function testFromDsnWithStarttlsScheme(): void
    {
        $config = SmtpConfig::fromDsn('smtp+starttls://smtp.example.com');

        $this->assertNotNull($config->encryption);
        $this->assertTrue($config->encryption->isTls);
    }

    public function testFromDsnWithoutPortUsesDefault(): void
    {
        $config = SmtpConfig::fromDsn('smtp://localhost');

        $this->assertSame(25, $config->port->value);
    }
}
