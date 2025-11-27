<?php

declare(strict_types=1);

namespace Larafony\Framework\Validation\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StartsWith extends ValidationAttribute
{
    /**
     * @var array<int, string>
     */
    private readonly array $prefixes;

    /**
     * @param array<int, string> $prefixes
     * @param string|null $message
     */
    public function __construct(
        array $prefixes,
        ?string $message = null
    ) {
        $this->prefixes = array_filter($prefixes, is_string(...));
        $this->message = $message ?? 'Invalid prefix';
    }

    public function validate(mixed $value): bool
    {
        $value ??= '';
        return array_any(
            $this->prefixes,
            static fn (string $prefix) => str_starts_with($value, $prefix)
        );
    }
}
