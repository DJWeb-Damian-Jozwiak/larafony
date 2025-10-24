<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Contracts;

use Larafony\Framework\Validation\ValidationResult;

interface Validator
{
    /**
     * Validate the given object and return validation result.
     *
     * @param object $request The object to validate
     *
     * @return ValidationResult The validation result containing any errors
     */
    public function validate(object $request): ValidationResult;
}
