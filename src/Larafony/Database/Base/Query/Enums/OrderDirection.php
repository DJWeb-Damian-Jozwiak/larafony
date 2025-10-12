<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Query\Enums;

enum OrderDirection: string
{
    case ASC = 'ASC';
    case DESC = 'DESC';
}
