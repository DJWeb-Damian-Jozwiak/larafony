<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length extends ValidationAttribute
{
    public function __construct(
        private readonly int $min,
        private readonly int $max,
        ?string $message = null
    ) {
        $this->message = $message ?? "Field must be between {$min} and {$max} characters";
    }

    public function validate(mixed $value): bool
    {
        return new MinLength($this->min)->validate($value) &&
            new MaxLength($this->max)->validate($value);
    }
}
