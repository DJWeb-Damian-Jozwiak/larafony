<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

/**
 * Validates that field matches its confirmation field.
 *
 * By default, looks for field named "{field}_confirmation".
 * For example, "password" looks for "password_confirmation".
 *
 * Examples:
 *
 * // Standard confirmation (looks for "password_confirmation")
 * #[Confirmed]
 * public string $password;
 *
 * // Custom confirmation field name
 * #[Confirmed('password_repeat')]
 * public string $password;
 *
 * // Email confirmation
 * #[Confirmed]
 * public string $email;  // Looks for "email_confirmation"
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Confirmed extends ValidationAttribute
{
    private readonly string $confirmationField;

    /**
     * @param string|null $confirmationField Name of confirmation field (null = auto-detect)
     * @param string|null $message Custom validation message
     */
    public function __construct(
        ?string $confirmationField = null,
        ?string $message = null
    ) {
        $this->confirmationField = $confirmationField ?? '';
        $this->message = $message ?? 'Confirmation does not match';
    }

    public function validate(mixed $value): bool
    {
        $confirmField = $this->confirmationField !== '' ? $this->confirmationField : "{$this->fieldName}_confirmation";

        // Check if confirmation field exists and matches
        if (! isset($this->data[$confirmField])) {
            return false;
        }

        return $value === $this->data[$confirmField];
    }
}
