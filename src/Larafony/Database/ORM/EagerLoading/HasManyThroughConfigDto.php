<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Model;

final readonly class HasManyThroughConfigDto
{
    /**
     * @param class-string<Model> $relatedClass
     * @param class-string<Model> $throughClass
     */
    public function __construct(
        public string $relatedClass,
        public string $throughClass,
        public string $firstKey,
        public string $secondKey,
        public string $localKey,
        public string $secondLocalKey,
    ) {
    }
}
