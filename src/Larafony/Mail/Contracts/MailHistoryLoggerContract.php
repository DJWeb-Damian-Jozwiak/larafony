<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Contracts;

use Larafony\Framework\Mail\Message\Email;

/**
 * Contract for logging email history.
 */
interface MailHistoryLoggerContract
{
    /**
     * Log an email sending attempt.
     *
     * @param Email $email The email that was sent or failed
     * @param string $status The status (sent, failed, etc.)
     * @param string|null $error Optional error message if failed
     *
     * @return void
     */
    public function logMail(Email $email, string $status, ?string $error = null): void;
}
