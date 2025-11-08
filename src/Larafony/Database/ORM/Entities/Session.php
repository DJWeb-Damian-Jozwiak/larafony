<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Entities;

use Larafony\Framework\Database\ORM\Model;

class Session extends Model
{
    public string $payload {
        get => $this->payload;
        set {
            $this->payload = $value;
            $this->markPropertyAsChanged('payload');
        }
    }

    public int $last_activity {
        get => $this->last_activity;
        set {
            $this->last_activity = $value;
            $this->markPropertyAsChanged('last_activity');
        }
    }

    public ?string $user_ip {
        get => $this->user_ip;
        set {
            $this->user_ip = $value;
            $this->markPropertyAsChanged('user_ip');
        }
    }

    public ?string $user_agent {
        get => $this->user_agent;
        set {
            $this->user_agent = $value;
            $this->markPropertyAsChanged('user_agent');
        }
    }

    public ?int $user_id {
        get => $this->user_id;
        set {
            $this->user_id = $value;
            $this->markPropertyAsChanged('user_id');
        }
    }

    public string $primaryKey {
        get => 'id';
    }

    public bool $incrementing = false;
}
