<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Migrations;

use Larafony\Framework\Database\Base\Migrations\MigrationRepository as BaseMigrationRepository;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\Drivers\MySQL\QueryBuilder as MySQLQueryBuilder;

class MigrationRepository extends BaseMigrationRepository
{
    protected function queryBuilder(): QueryBuilder
    {
        return new MySQLQueryBuilder($this->connection);
    }
}
