<?php

declare(strict_types=1);

namespace Larafony\Framework\Exceptions\Validation;

use Larafony\Framework\Validation\ValidationError;

/**
 * Exception thrown when validation fails (HTTP 422 Unprocessable Entity).
 */
class ValidationFailed extends \RuntimeException
{
    /**
     * @param array<int, ValidationError> $errors
     */
    public function __construct(
        public readonly array $errors,
        string $message = 'Validation failed',
        int $code = 422
    ) {
        parent::__construct($message, $code);
    }

    /**
     * Get validation errors as an associative array.
     *
     * @return array<string, array<int, string>>
     */
    public function getErrorsArray(): array
    {
        $result = [];
        foreach ($this->errors as $error) {
            if (! isset($result[$error->field])) {
                $result[$error->field] = [];
            }
            $result[$error->field][] = $error->message;
        }
        return $result;
    }
}
