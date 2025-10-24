<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;
use Closure;

/**
 * Custom validation logic using closures in attributes (PHP 8.5).
 *
 * Allows complex validation rules with access to both value and all form data.
 *
 * Examples:
 *
 * // Password confirmation
 * #[ValidWhen(
 *     fn(mixed $value, array $data) => $value === $data['password'],
 *     message: 'Password confirmation does not match'
 * )]
 * public string $password_confirmation;
 *
 * // Age validation with context
 * #[ValidWhen(
 *     fn(mixed $value, array $data) => $value >= ($data['country'] === 'US' ? 21 : 18),
 *     message: 'You must meet the minimum age requirement'
 * )]
 * public int $age;
 *
 * // Using first-class callable (PHP 8.5)
 * #[ValidWhen(self::isStrongPassword(...), message: 'Password is too weak')]
 * public string $password;
 *
 * private static function isStrongPassword(mixed $value, array $data): bool
 * {
 *     return strlen($value) >= 8
 *         && preg_match('/[A-Z]/', $value)
 *         && preg_match('/[0-9]/', $value)
 *         && preg_match('/[^A-Za-z0-9]/', $value);
 * }
 *
 * // Date range validation
 * #[ValidWhen(
 *     fn($end, $data) => strtotime($end) > strtotime($data['start_date']),
 *     message: 'End date must be after start date'
 * )]
 * public string $end_date;
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ValidWhen extends ValidationAttribute
{
    private readonly Closure $validator;

    /**
     * @param Closure $validator Closure(mixed $value, array $data): bool
     * @param string $message Validation error message
     */
    public function __construct(
        Closure $validator,
        string $message = 'Validation failed'
    ) {
        $this->validator = $validator;
        $this->message = $message;
    }

    public function validate(mixed $value): bool
    {
        return ($this->validator)($value, $this->data);
    }
}
