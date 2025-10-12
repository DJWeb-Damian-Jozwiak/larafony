<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Enums;

enum AlterTableType
{
    case ADD;
    case DROP;
    case MODIFY;
}
