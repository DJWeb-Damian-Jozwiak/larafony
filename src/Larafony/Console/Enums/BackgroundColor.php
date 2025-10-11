<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Enums;

//see https://github.com/php-parallel-lint/PHP-Console-Color/blob/master/src/ConsoleColor.php
//see https://en.wikipedia.org/wiki/ANSI_escape_code

enum BackgroundColor: string
{
    case DEFAULT = '49';
    case BLACK = '40';
    case RED = '41';
    case GREEN = '42';
    case YELLOW = '43';
    case BLUE = '44';
    case MAGENTA = '45';
    case CYAN = '46';
    case LIGHT_GRAY = '47';
    case DARK_GRAY = '100';
    case LIGHT_RED = '101';
    case LIGHT_GREEN = '102';
    case LIGHT_YELLOW = '103';
    case LIGHT_BLUE = '104';
    case LIGHT_MAGENTA = '105';
    case LIGHT_CYAN = '106';
    case WHITE = '107';
}
