<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Mail;

use Larafony\Framework\Mail\Address;
use Larafony\Framework\Mail\Envelope;
use PHPUnit\Framework\TestCase;

class EnvelopeTest extends TestCase
{
    public function testCanCreateBasicEnvelope(): void
    {
        $from = new Address('sender@example.com', 'Sender');
        $envelope = new Envelope($from, 'Test Subject');

        $this->assertSame($from, $envelope->from);
        $this->assertSame('Test Subject', $envelope->subject);
        $this->assertNull($envelope->replyTo);
        $this->assertEmpty($envelope->to);
        $this->assertEmpty($envelope->cc);
        $this->assertEmpty($envelope->bcc);
    }

    public function testCanCreateEnvelopeWithReplyTo(): void
    {
        $from = new Address('sender@example.com');
        $replyTo = new Address('reply@example.com');
        $envelope = new Envelope($from, 'Test', $replyTo);

        $this->assertSame($replyTo, $envelope->replyTo);
    }

    public function testCanAddToRecipients(): void
    {
        $envelope = new Envelope(
            new Address('sender@example.com'),
            'Test'
        );

        $to1 = new Address('recipient1@example.com');
        $to2 = new Address('recipient2@example.com');

        $envelope->addTo($to1);
        $envelope->addTo($to2);

        $this->assertCount(2, $envelope->to);
        $this->assertSame($to1, $envelope->to[0]);
        $this->assertSame($to2, $envelope->to[1]);
    }

    public function testCanAddCcRecipients(): void
    {
        $envelope = new Envelope(
            new Address('sender@example.com'),
            'Test'
        );

        $cc = new Address('cc@example.com');
        $envelope->addCc($cc);

        $this->assertCount(1, $envelope->cc);
        $this->assertSame($cc, $envelope->cc[0]);
    }

    public function testCanAddBccRecipients(): void
    {
        $envelope = new Envelope(
            new Address('sender@example.com'),
            'Test'
        );

        $bcc = new Address('bcc@example.com');
        $envelope->addBcc($bcc);

        $this->assertCount(1, $envelope->bcc);
        $this->assertSame($bcc, $envelope->bcc[0]);
    }

    public function testAddToReturnsEnvelopeForChaining(): void
    {
        $envelope = new Envelope(
            new Address('sender@example.com'),
            'Test'
        );

        $result = $envelope
            ->addTo(new Address('to@example.com'))
            ->addCc(new Address('cc@example.com'))
            ->addBcc(new Address('bcc@example.com'));

        $this->assertSame($envelope, $result);
        $this->assertCount(1, $envelope->to);
        $this->assertCount(1, $envelope->cc);
        $this->assertCount(1, $envelope->bcc);
    }
}
