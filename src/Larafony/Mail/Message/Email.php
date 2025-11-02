<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail\Message;

use Larafony\Framework\Mail\Address;

/**
 * Represents an email message ready to be sent.
 */
final class Email
{
    /**
     * @var array<int, string>
     */
    public array $headers {
        get {
            $headers = [];
            if ($this->to !== []) {
                $headers[] = 'To: ' . implode(', ', $this->to);
            }

            if ($this->cc !== []) {
                $headers[] = 'Cc: ' . implode(', ', $this->cc);
            }

            if ($this->replyTo !== null) {
                $headers[] = 'Reply-To: ' . $this->replyTo;
            }
            return $headers;
        }
    }
    /**
     * @param array<int, Address> $to
     * @param array<int, Address> $cc
     * @param array<int, Address> $bcc
     */
    public function __construct(
        public private(set) ?Address $from = null,
        public private(set) array $to = [],
        public private(set) array $cc = [],
        public private(set) array $bcc = [],
        public private(set) ?Address $replyTo = null,
        public private(set) ?string $subject = null,
        public private(set) ?string $htmlBody = null,
        public private(set) ?string $textBody = null,
    ) {
    }

    public function from(Address $address): self
    {
        return clone($this, ['from' => $address]);
    }

    public function to(Address $address): self
    {
        return clone($this, ['to' => [...$this->to, $address]]);
    }

    public function cc(Address $address): self
    {
        return clone($this, ['cc' => [...$this->cc, $address]]);
    }

    public function bcc(Address $address): self
    {
        return clone($this, ['bcc' => [...$this->bcc, $address]]);
    }

    public function replyTo(Address $address): self
    {
        return clone($this, ['replyTo' => $address]);
    }

    public function subject(string $subject): self
    {
        return clone($this, ['subject' => $subject]);
    }

    public function html(string $html): self
    {
        return clone($this, ['htmlBody' => $html]);
    }

    public function text(string $text): self
    {
        return clone($this, ['textBody' => $text]);
    }
}
