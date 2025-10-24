<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;
use Closure;

/**
 * Conditional required validation using PHP 8.5 closures in attributes.
 *
 * The field is required when the closure returns true.
 *
 * Examples:
 *
 * // Simple field check
 * #[RequiredWhen(fn(array $data) => $data['type'] === 'email')]
 * public ?string $email;
 *
 * // Multiple conditions
 * #[RequiredWhen(fn(array $data) => $data['is_company'] && $data['country'] === 'PL')]
 * public ?string $nip;
 *
 * // Using first-class callable (PHP 8.5)
 * #[RequiredWhen(self::isBusinessAccount(...))]
 * public ?string $taxId;
 *
 * private static function isBusinessAccount(array $data): bool
 * {
 *     return $data['account_type'] === 'business';
 * }
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class RequiredWhen extends ValidationAttribute
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
        $isRequired = ($this->condition)($this->data);

        // If not required, validation passes regardless of value
        if (! $isRequired) {
            return true;
        }

        // If required, value must not be null
        return $value !== null;
    }
}
