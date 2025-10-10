<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Dto;

enum LineType: string
{
    case Variable = 'variable';
    case Comment = 'comment';
    case Empty = 'empty';
}
