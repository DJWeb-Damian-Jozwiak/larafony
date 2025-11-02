<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Contracts;

use Larafony\Framework\Mail\Message\Email;

/**
 * Contract for email transport implementations.
 *
 * This contract allows easy swapping of transport drivers
 * (native SMTP, Symfony Mailer, AWS SES, etc.)
 */
interface TransportContract
{
    /**
     * Send an email message.
     *
     * @param Email $message The email message to send
     *
     * @return void
     *
     * @throws \Larafony\Framework\Mail\Exceptions\TransportError
     */
    public function send(Email $message): void;
}
