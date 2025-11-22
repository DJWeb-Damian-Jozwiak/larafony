<?php

declare(strict_types=1);

namespace Larafony\Framework\Scheduler\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class Serialize
{
    public function __construct(public ?string $name = null)
    {
    }
}
