<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class RouteParam
{
    public function __construct(
        public string $name,
        public string|int|float|object|null $default = null,
        public ?string $bind = null,
    )
    {
    }
}