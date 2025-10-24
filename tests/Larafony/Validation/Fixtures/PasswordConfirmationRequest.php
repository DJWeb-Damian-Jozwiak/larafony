<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Fixtures;

use Larafony\Framework\Validation\Attributes\ValidWhen;

class PasswordConfirmationRequest
{
    public string $password = 'secret123';

    #[ValidWhen(self::matchesPassword(...), message: 'Passwords must match')]
    public string $password_confirmation = 'different';

    private static function matchesPassword(mixed $value, array $data): bool
    {
        return $value === $data['password'];
    }
}
