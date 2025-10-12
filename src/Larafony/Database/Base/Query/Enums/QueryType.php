<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Enums;

enum QueryType: string
{
    case SELECT = 'SELECT';
    case INSERT = 'INSERT';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
}
