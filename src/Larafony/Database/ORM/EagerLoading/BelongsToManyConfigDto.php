<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\EagerLoading;

use Larafony\Framework\Database\ORM\Model;

final readonly class BelongsToManyConfigDto
{
    /**
     * @param class-string<Model> $relatedClass
     */
    public function __construct(
        public string $relatedClass,
        public string $pivotTable,
        public string $foreignPivotKey,
        public string $relatedPivotKey,
    ) {
    }
}
