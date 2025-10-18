<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

use Attribute;
use Larafony\Framework\Database\ORM\Model;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class BelongsTo
{
    /**
     * @param class-string<Model> $related
     * @param string $foreign_key
     * @param string $local_key
     */
    public function __construct(
        public string $related,
        public string $foreign_key,
        public string $local_key
    ) {
    }
}
