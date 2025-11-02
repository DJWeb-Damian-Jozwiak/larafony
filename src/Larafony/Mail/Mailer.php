<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\Mail\Contracts\MailerContract;
use Larafony\Framework\Mail\Contracts\MailHistoryLoggerContract;
use Larafony\Framework\Mail\Contracts\TransportContract;

/**
 * Main mailer facade.
 */
final readonly class Mailer implements MailerContract
{
    public function __construct(
        private TransportContract $transport,
        private ?MailHistoryLoggerContract $logger = null
    ) {
    }

    public function send(Mailable $mailable): void
    {
        $email = $mailable->build();

        try {
            $this->transport->send($email);
            $this->logger?->logMail($email, 'sent');
        } catch (\Throwable $e) {
            $this->logger?->logMail($email, 'failed', $e->getMessage());
            throw $e;
        }
    }
}
