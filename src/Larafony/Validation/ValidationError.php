<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation;

readonly class ValidationError
{
    public function __construct(
        public string $field,
        public string $message
    ) {
    }
}
