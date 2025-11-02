<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail;

use Larafony\Framework\Mail\Mailer;
use Larafony\Framework\Mail\MailerFactory;
use PHPUnit\Framework\TestCase;

class MailerFactoryTest extends TestCase
{
    public function testFromDsnCreatesMailer(): void
    {
        $mailer = MailerFactory::fromDsn('smtp://localhost:1025');

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateSmtpMailerWithBasicConfig(): void
    {
        $mailer = MailerFactory::createSmtpMailer('localhost', 1025);

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateSmtpMailerWithAuthentication(): void
    {
        $mailer = MailerFactory::createSmtpMailer(
            'smtp.example.com',
            587,
            'user',
            'pass'
        );

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateSmtpMailerWithSslEncryption(): void
    {
        $mailer = MailerFactory::createSmtpMailer(
            'smtp.example.com',
            465,
            'user',
            'pass',
            'ssl'
        );

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateSmtpMailerWithTlsEncryption(): void
    {
        $mailer = MailerFactory::createSmtpMailer(
            'smtp.example.com',
            587,
            'user',
            'pass',
            'tls'
        );

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateMailHogMailerWithDefaults(): void
    {
        $mailer = MailerFactory::createMailHogMailer();

        $this->assertInstanceOf(Mailer::class, $mailer);
    }

    public function testCreateMailHogMailerWithCustomHostAndPort(): void
    {
        $mailer = MailerFactory::createMailHogMailer('mailhog.local', 2025);

        $this->assertInstanceOf(Mailer::class, $mailer);
    }
}
