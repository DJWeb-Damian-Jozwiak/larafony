<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MaxLength extends ValidationAttribute
{
    public function __construct(
        public int $maxLength,
        ?string $message = null
    ) {
        $this->message = $message ?? "The field must be at most {$this->maxLength} characters long.";
    }

    public function validate(mixed $value): bool
    {
        $value ??= '';
        return strlen($value) <= $this->maxLength;
    }
}
