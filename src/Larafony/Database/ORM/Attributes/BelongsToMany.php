<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

use Attribute;
use Larafony\Framework\Database\ORM\Model;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsToMany
{
    /**
     * @param class-string<Model> $related
     * @param string $pivot_table
     * @param string $foreign_pivot_key
     * @param string $related_pivot_key
     */
    public function __construct(
        public string $related,
        public string $pivot_table,
        public string $foreign_pivot_key,
        public string $related_pivot_key
    ) {
    }
}
