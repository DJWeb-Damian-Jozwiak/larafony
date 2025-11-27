<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail;

use Larafony\Framework\Mail\Address;
use Larafony\Framework\Mail\Content;
use Larafony\Framework\Mail\Envelope;
use Larafony\Framework\Mail\Mailable;
use Larafony\Framework\Mail\MailerFactory;
use Larafony\Framework\Mail\Transport\SmtpConnection;
use PHPUnit\Framework\TestCase;

class MailerIntegrationTest extends TestCase
{
    private function isMailHogRunning(): bool
    {
        try {
            //connection from docker-compose
            $connection = SmtpConnection::create('mailhog', 1025, 1);
            $connection->close();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function testSendEmailThroughMailHog(): void
    {
        if (!$this->isMailHogRunning()) {
            $this->markTestSkipped('MailHog is not running on localhost:1025');
        }

        $mailer = MailerFactory::createMailHogMailer(host: 'mailhog');

        $mailable = new class extends Mailable {
            public function envelope(): Envelope
            {
                return (new Envelope(
                    from: new Address('sender@larafony.test', 'Larafony Framework'),
                    subject: 'Test Email from Larafony Mail Component'
                ))
                    ->addTo(new Address('recipient@larafony.test', 'Test Recipient'))
                    ->addCc(new Address('cc@larafony.test'))
                    ->addBcc(new Address('bcc@larafony.test'));
            }

            public function content(): Content
            {
                // Return Content with overridden render method
                return new class extends Content {
                    public function __construct()
                    {
                        // Don't call parent constructor to avoid ViewManager dependency
                    }

                    public function render(): string
                    {
                        return '<html><body><h1>Test Email</h1><p>This is a test email from Larafony Framework!</p></body></html>';
                    }
                };
            }
        };

        // Should not throw exception
        $mailer->send($mailable);

        $this->assertTrue(true, 'Email sent successfully to MailHog');
    }
}
