<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Enums;

enum JoinType: string
{
    case INNER = 'INNER';
    case LEFT = 'LEFT';
    case RIGHT = 'RIGHT';
    case CROSS = 'CROSS';
}
