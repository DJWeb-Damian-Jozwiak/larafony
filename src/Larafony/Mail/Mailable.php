<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

use Larafony\Framework\Mail\Message\Email;

/**
 * Abstract class for building mailable emails.
 */
abstract class Mailable
{
    /**
     * Get the email envelope (from, to, subject, etc.).
     */
    abstract public function envelope(): Envelope;

    /**
     * Get the email content (view and data).
     */
    abstract public function content(): Content;

    /**
     * Build the email message.
     *
     * Note: We use foreach loops instead of array_walk() because Envelope properties
     * use asymmetric visibility (private(set)). According to RFC Asymmetric Visibility v2,
     * obtaining a reference to a property follows set visibility, not get visibility.
     * Since array_walk() requires a reference to iterate, it would fail with:
     * "Cannot indirectly modify private(set) property"
     *
     * @see https://wiki.php.net/rfc/asymmetric-visibility-v2
     */
    public function build(): Email
    {
        $envelope = $this->envelope();
        $content = $this->content();

        $email = (new Email())
            ->from($envelope->from)
            ->subject($envelope->subject)
            ->html($content->render());

        if ($envelope->replyTo !== null) {
            $email = $email->replyTo($envelope->replyTo);
        }

        // Cannot use array_walk() with private(set) properties
        // See RFC: references follow set visibility, not get visibility
        foreach ($envelope->to as $address) {
            $email = $email->to($address);
        }

        foreach ($envelope->cc as $address) {
            $email = $email->cc($address);
        }

        foreach ($envelope->bcc as $address) {
            $email = $email->bcc($address);
        }

        return $email;
    }
}
