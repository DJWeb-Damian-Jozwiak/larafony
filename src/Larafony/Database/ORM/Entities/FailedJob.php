<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use DateTimeImmutable;
use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\ORM\Model;

class FailedJob extends Model
{
    public protected(set) bool $use_uuid = true;
    //table name generated automatically by ORM

    public string $connection {
        get => $this->connection;
        set {
            $this->connection = $value;
            $this->markPropertyAsChanged('connection');
        }
    }

    public string $queue {
        get => $this->queue;
        set {
            $this->queue = $value;
            $this->markPropertyAsChanged('queue');
        }
    }

    public string $payload {
        get => $this->payload;
        set {
            $this->payload = $value;
            $this->markPropertyAsChanged('payload');
        }
    }

    public string $exception {
        get => $this->exception;
        set {
            $this->exception = $value;
            $this->markPropertyAsChanged('exception');
        }
    }

    public Clock $failed_at {
        get => $this->failed_at;
        set {
            $this->failed_at = $value;
            $this->markPropertyAsChanged('failed_at');
        }
    }

    /**
     * @var array<string, string>
     */
    public array $casts = [
        'failed_at' => 'datetime',
    ];
}
