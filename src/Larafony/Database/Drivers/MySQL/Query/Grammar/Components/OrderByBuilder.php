<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL\Query\Grammar\Components;

use Larafony\Framework\Database\Base\Query\Clauses\OrderByClause;

/**
 * ORDER BY clause builder - builds ORDER BY part of SQL
 * Grammar partial component
 */
class OrderByBuilder
{
    /**
     * @param array<int, OrderByClause> $orders
     */
    public function build(array $orders): string
    {
        if (! $orders) {
            return '';
        }

        $sql = [];
        foreach ($orders as $order) {
            $sql[] = $order->getSqlDefinition();
        }

        return 'ORDER BY ' . implode(', ', $sql);
    }
}
