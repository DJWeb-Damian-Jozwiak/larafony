<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class CastUsing
{
    /**
     * @param callable(mixed): mixed $callback First-class callable for casting the value
     * @param callable(mixed): mixed|null $castBack  second-class callable for casting the value back
     */
    public function __construct(
        public mixed $callback,
        public mixed $castBack = null
    ) {
    }
}
