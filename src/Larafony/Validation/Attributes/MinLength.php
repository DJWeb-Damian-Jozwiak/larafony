<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength extends ValidationAttribute
{
    public function __construct(
        public int $minLength,
        ?string $message = null
    ) {
        $this->message = $message ?? "The field must be at least {$this->minLength} characters long.";
    }

    public function validate(mixed $value): bool
    {
        $value ??= '';
        return strlen($value) >= $this->minLength;
    }
}
