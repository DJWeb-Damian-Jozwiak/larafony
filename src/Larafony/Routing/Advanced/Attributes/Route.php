<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
readonly class Route
{
    /**
     * @var array<int, string>
     */
    public array $methods;

    /**
     * @param string $path
     * @param string|array<int, string> $methods
     */
    public function __construct(
        public string $path,
        string|array $methods = ['GET'],
    ) {
        $this->methods = is_string($methods) ? [$methods] : $methods;
    }
}
