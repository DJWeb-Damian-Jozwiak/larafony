<?php

declare(strict_types=1);

namespace Larafony\Framework\Mail;

/**
 * Represents the email envelope (headers and recipients).
 */
final class Envelope
{
    /**
     * @var array<int, Address>
     */
    public private(set) array $to = [];

    /**
     * @var array<int, Address>
     */
    public private(set) array $cc = [];

    /**
     * @var array<int, Address>
     */
    public private(set) array $bcc = [];

    public function __construct(
        public readonly Address $from,
        public readonly string $subject,
        public readonly ?Address $replyTo = null
    ) {
    }

    public function addTo(Address $address): self
    {
        $this->to[] = $address;
        return $this;
    }

    public function addCc(Address $address): self
    {
        $this->cc[] = $address;
        return $this;
    }

    public function addBcc(Address $address): self
    {
        $this->bcc[] = $address;
        return $this;
    }
}
