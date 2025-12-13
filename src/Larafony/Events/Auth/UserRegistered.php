<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Auth;

use Larafony\Framework\Database\ORM\Entities\User;

final readonly class UserRegistered
{
    public function __construct(
        public User $user,
    ) {
    }
}
