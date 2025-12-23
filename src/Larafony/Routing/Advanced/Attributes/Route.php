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
     * @param string|null $name Named route for URL generation
     */
    public function __construct(
        public string $path,
        string|array $methods = ['GET'],
        public ?string $name = null,
    ) {
        $this->methods = is_string($methods) ? [$methods] : $methods;
    }
}
