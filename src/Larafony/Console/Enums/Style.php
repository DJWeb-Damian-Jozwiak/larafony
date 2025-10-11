<?php

declare(strict_types=1);

//see https://github.com/php-parallel-lint/PHP-Console-Color/blob/master/src/ConsoleColor.php
//see https://en.wikipedia.org/wiki/ANSI_escape_code

namespace Larafony\Framework\Console\Enums;

enum Style: string
{
    case DEFAULT = '0';
    case BOLD = '1';
    case DARK = '2';
    case ITALIC = '3';
    case UNDERLINE = '4';
    case BLINK = '5';
    case REVERSE = '7';
    case CONCEALED = '8';
}
