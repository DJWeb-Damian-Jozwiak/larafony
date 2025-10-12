<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Enums;

enum LogicalOperator: string
{
    case AND = 'AND';
    case OR = 'OR';
}
