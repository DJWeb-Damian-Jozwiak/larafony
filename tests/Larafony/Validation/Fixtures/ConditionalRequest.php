<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Validation\Fixtures;

use Larafony\Framework\Validation\Attributes\RequiredWhen;

class ConditionalRequest
{
    public string $account_type = 'business';

    #[RequiredWhen(self::isBusinessAccount(...))]
    public ?string $company_name = null;

    private static function isBusinessAccount(array $data): bool
    {
        return $data['account_type'] === 'business';
    }
}
