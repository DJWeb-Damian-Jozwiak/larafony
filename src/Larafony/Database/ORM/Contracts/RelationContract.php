<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\ORM\Model;

interface RelationContract
{
    public function addConstraints(): void;

    /**
     * @return array<int, Model>|Model
     */
    public function getRelated(): array|Model;

    public function createQueryBuilder(): QueryBuilder;
}
