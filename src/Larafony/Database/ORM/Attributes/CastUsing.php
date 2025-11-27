<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class CastUsing
{
    /**
     * @param callable(mixed): mixed $callback First-class callable for casting the value
     */
    public function __construct(
        public mixed $callback
    ) {
    }
}
