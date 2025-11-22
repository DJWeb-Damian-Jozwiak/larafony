<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use DateTimeImmutable;
use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\ORM\Model;

class Job extends Model
{
    public protected(set) bool $use_uuid = true;
    public string $payload {
        get => $this->payload;
        set {
            $this->payload = $value;
            $this->markPropertyAsChanged('payload');
        }
    }

    public ?string $queue {
        get => $this->queue;
        set {
            $this->queue = $value;
            $this->markPropertyAsChanged('queue');
        }
    }

    public int $attempts {
        get => $this->attempts;
        set {
            $this->attempts = $value;
            $this->markPropertyAsChanged('attempts');
        }
    }

    public ?DateTimeImmutable $reserved_at {
        get => $this->reserved_at;
        set {
            $this->reserved_at = $value;
            $this->markPropertyAsChanged('reserved_at');
        }
    }

    public Clock $available_at {
        get => $this->available_at;
        set {
            $this->available_at = $value;
            $this->markPropertyAsChanged('available_at');
        }
    }

    public Clock $created_at {
        get => $this->created_at;
        set {
            $this->created_at = $value;
            $this->markPropertyAsChanged('created_at');
        }
    }

    /**
     * @var array<string, string>
     */
    public array $casts = [
        'attempts' => 'int',
        'reserved_at' => 'datetime',
        'available_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
