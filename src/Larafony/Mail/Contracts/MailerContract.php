<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Contracts;

use Larafony\Framework\Mail\Mailable;

/**
 * Contract for the Mailer facade.
 *
 * This is the main API for sending emails in the application.
 */
interface MailerContract
{
    /**
     * Send a mailable email.
     *
     * @param Mailable $mailable The mailable to send
     *
     * @return void
     */
    public function send(Mailable $mailable): void;
}
