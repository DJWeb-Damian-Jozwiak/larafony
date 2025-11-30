<?php

declare(strict_types=1);

namespace Larafony\Framework\Cache;

use DateInterval;
use DateTimeInterface;
use Larafony\Framework\Clock\ClockFactory;
use Psr\Cache\CacheItemInterface;

class CacheItem implements CacheItemInterface
{
    public private(set) bool $isHit = false;
    public private(set) ?\DateTimeInterface $expiry = null;
    private mixed $value = null;

    public function __construct(private readonly string $key)
    {
    }
    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function set(mixed $value): static
    {
        //mutable for performance reasons!
        $this->value = $value;
        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        if ($expiration === null) {
            $this->expiry = null;
            return $this;
        }
        // Convert to DateTimeImmutable if needed
        $this->expiry = \DateTimeImmutable::createFromInterface($expiration);
        return $this;
    }

    public function expiresAfter(\DateInterval|int|null $time): static
    {
        if ($time === null) {
            $this->expiry = null;
        } elseif ($time instanceof DateInterval) {
            $this->expiry = ClockFactory::now()->add($time);
        } else {
            $this->expiry = ClockFactory::now()->add(new DateInterval("PT{$time}S"));
        }
        return $this;
    }

    /**
     * Mark item as hit
     *
     * @param bool $hit
     *
     * @return static
     */
    public function withIsHit(bool $hit): static
    {
        $this->isHit = $hit;
        return $this;
    }
}
