<?php

declare(strict_types=1);

namespace Larafony\Framework\DBAL\Models\Entities;

use Larafony\Framework\Database\ORM\Model;

class DatabaseLog extends Model
{
    public string $table {
        get => 'database_logs';
    }

    public string $level {
        get => $this->level;
        set {
            $this->level = $value;
            $this->markPropertyAsChanged('level');
        }
    }

    public string $message {
        get => $this->message;
        set {
            $this->message = $value;
            $this->markPropertyAsChanged('message');
        }
    }

    /**
     * @var array<int|string, mixed>
     */

    public array $context {
        get => $this->context;
        set {
            $this->context = $value;
            $this->markPropertyAsChanged('context');
        }
    }

    /**
     * @var ?array<int|string, mixed>
     */
    public ?array $metadata {
        get => $this->metadata;
        set {
            $this->metadata = $value;
            $this->markPropertyAsChanged('metadata');
        }
    }

    /**
     * @var array<string, string>
     */
    public array $casts = [
        'metadata' => 'array',
        'context' => 'array',
    ];
}
