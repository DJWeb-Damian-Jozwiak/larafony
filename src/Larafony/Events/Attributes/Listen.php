<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class Listen
{
    /**
     * @param class-string|null $event Event class name. If null, will be inferred from method parameter type.
     * @param int $priority Listener priority. Higher values execute first.
     */
    public function __construct(
        public ?string $event = null,
        public int $priority = 0,
    ) {
    }
}
