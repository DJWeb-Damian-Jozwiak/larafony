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
     * @param array<int, string> $suffixes
     * @param string|null $message
     */
    public function __construct(
        array $suffixes,
        ?string $message = null
    ) {
        $this->prefixes = array_filter($suffixes, is_string(...));
        $this->message = $message ?? 'Invalid suffix';
    }

    public function validate(mixed $value): bool
    {
        $value ??= '';
        return array_filter($this->prefixes, static fn ($suffix) => str_starts_with($value, $suffix)) !== [];
    }
}
