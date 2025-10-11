<?php

declare(strict_types=1);

//see https://github.com/php-parallel-lint/PHP-Console-Color/blob/master/src/ConsoleColor.php
//see https://en.wikipedia.org/wiki/ANSI_escape_code

namespace Larafony\Framework\Console\Enums;

enum ForegroundColor: string
{
    case DEFAULT = '39';
    case BLACK = '30';
    case RED = '31';
    case GREEN = '32';
    case YELLOW = '33';
    case BLUE = '34';
    case MAGENTA = '35';
    case CYAN = '36';
    case LIGHT_GRAY = '37';
    case DARK_GRAY = '90';
    case LIGHT_RED = '91';
    case LIGHT_GREEN = '92';
    case LIGHT_YELLOW = '93';
    case LIGHT_BLUE = '94';
    case LIGHT_MAGENTA = '95';
    case LIGHT_CYAN = '96';
    case WHITE = '97';
}
