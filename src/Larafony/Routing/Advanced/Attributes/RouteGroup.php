<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class RouteGroup
{
    public function __construct(public string $name)
    {
    }
}