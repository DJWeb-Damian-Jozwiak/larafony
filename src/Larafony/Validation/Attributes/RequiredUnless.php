<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;
use Closure;

/**
 * Conditional required validation - field required UNLESS condition is true.
 *
 * The field is required when the closure returns false.
 *
 * Examples:
 *
 * // Email required unless has phone
 * #[RequiredUnless(fn(array $data) => isset($data['phone']))]
 * public ?string $email;
 *
 * // Password required unless using OAuth
 * #[RequiredUnless(fn(array $data) => $data['auth_method'] === 'oauth')]
 * public ?string $password;
 *
 * // Using first-class callable (PHP 8.5)
 * #[RequiredUnless(self::hasAlternativeContact(...))]
 * public ?string $phone;
 *
 * private static function hasAlternativeContact(array $data): bool
 * {
 *     return !empty($data['email']) || !empty($data['telegram']);
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RequiredUnless extends ValidationAttribute
{
    private readonly Closure $condition;

    /**
     * @param Closure $condition Closure that receives all form data and returns bool
     * @param string|null $message Custom validation message
     */
    public function __construct(
        Closure $condition,
        ?string $message = null
    ) {
        $this->condition = $condition;
        $this->message = $message ?? 'This field is required';
    }

    public function validate(mixed $value): bool
    {
        $skipRequired = ($this->condition)($this->data);

        // If condition is true, validation passes regardless of value
        if ($skipRequired) {
            return true;
        }

        // If condition is false, value must not be null
        return $value !== null;
    }
}
