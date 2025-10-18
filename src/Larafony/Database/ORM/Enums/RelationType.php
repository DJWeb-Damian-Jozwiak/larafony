<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Enums;

enum RelationType: string
{
    case belongsTo = 'belongsTo';
    case hasMany = 'hasMany';
    case belongsToMany = 'belongsToMany';
    case hasManyThrough = 'hasManyThrough';
}
