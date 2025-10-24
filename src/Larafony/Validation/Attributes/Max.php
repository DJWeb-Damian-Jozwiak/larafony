<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max extends ValidationAttribute
{
    public function __construct(
        private readonly int $max,
        ?string $message = null
    ) {
        $this->message = $message ?? "Value must be at most {$max}";
    }

    public function validate(mixed $value): bool
    {
        return (int) $value <= $this->max;
    }
}
