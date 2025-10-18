<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

use Attribute;
use Larafony\Framework\Database\ORM\Model;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class HasManyThrough
{
    /**
     * @param class-string<Model> $related
     * @param class-string<Model> $through
     * @param string $first_key
     * @param string $second_key
     * @param string $local_key
     * @param string $second_local_key
     */
    public function __construct(
        public string $related,
        public string $through,
        public string $first_key,
        public string $second_key,
        public string $local_key,
        public string $second_local_key
    ) {
    }
}
