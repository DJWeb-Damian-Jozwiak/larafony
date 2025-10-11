<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class AsCommand
{
    public function __construct(
        public string $name
    ) {
    }
}
