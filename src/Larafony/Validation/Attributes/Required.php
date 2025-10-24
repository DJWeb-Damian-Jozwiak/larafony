<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Required extends ValidationAttribute
{
    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'This field is required';
    }

    public function validate(mixed $value): bool
    {
        return $value !== null;
    }
}
