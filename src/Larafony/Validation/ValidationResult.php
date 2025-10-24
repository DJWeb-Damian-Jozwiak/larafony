<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation;

class ValidationResult
{
    /**
     * @var array<int, ValidationError>
     */
    public private(set) array $errors;

    public function __construct()
    {
        $this->errors = [];
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[] = new ValidationError($field, $message);
    }

    public function hasErrors(): bool
    {
        return (bool) ($this->errors);
    }

    public function isValid(): bool
    {
        return ! $this->hasErrors();
    }
}
