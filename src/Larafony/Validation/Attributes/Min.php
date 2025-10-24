<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min extends ValidationAttribute
{
    public function __construct(
        private readonly int $min,
        ?string $message = null
    ) {
        $this->message = $message ?? "Value must be at least {$min}";
    }

    public function validate(mixed $value): bool
    {
        return (int) $value >= $this->min;
    }
}
