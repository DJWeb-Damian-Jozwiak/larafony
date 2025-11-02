<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\DBAL\Models\Entities\MailLog;
use Larafony\Framework\Mail\Contracts\MailHistoryLoggerContract;
use Larafony\Framework\Mail\Message\Email;

/**
 * Mail history logger with database persistence.
 */
readonly class MailHistoryLogger implements MailHistoryLoggerContract
{
    public function logMail(Email $email, string $status, ?string $error = null): void
    {
        $log = new MailLog();

        $log->from_email = $email->from->email ?? '';
        $log->from_name = $email->from->name ?? null;
        $log->subject = $email->subject ?? '';

        if ($email->to !== []) {
            $log->to_email = $email->to[0]->email;
            $log->to_name = $email->to[0]->name;
        } else {
            $log->to_email = '';
        }

        $log->cc_email = $this->formatAddresses($email->cc);
        $log->bcc_email = $this->formatAddresses($email->bcc);

        if ($email->replyTo !== null) {
            $log->reply_to_email = $email->replyTo->email;
        }

        $log->status = $status;
        $log->error = $error;

        $log->save();
    }

    /**
     * @param array<int, \Larafony\Framework\Mail\Address> $addresses
     */
    private function formatAddresses(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        return implode(',', array_map(
            static fn ($address) => $address->email,
            $addresses
        ));
    }
}
