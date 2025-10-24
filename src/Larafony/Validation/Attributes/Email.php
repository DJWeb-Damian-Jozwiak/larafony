<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email extends ValidationAttribute
{
    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'Invalid email format';
    }

    public function validate(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
