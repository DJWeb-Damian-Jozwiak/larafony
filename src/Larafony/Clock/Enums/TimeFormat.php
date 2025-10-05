<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock\Enums;

enum TimeFormat: string
{
    case ATOM = 'Y-m-d\TH:i:sP';
    case COOKIE = 'l, d-M-Y H:i:s T';
    case ISO8601 = 'Y-m-d\TH:i:sO';
    case ISO8601_EXPANDED = 'Y-m-d\TH:i:s.vP';
    case RFC822 = 'D, d M y H:i:s O';
    case RFC850 = 'l, d-M-y H:i:s T';
    case RFC7231 = 'D, d M Y H:i:s \G\M\T';
    case RSS = 'D, d M Y H:i:s O';

    // Common formats
    case DATE = 'Y-m-d';
    case TIME = 'H:i:s';
    case DATETIME = 'Y-m-d H:i:s';
    case DATE_SHORT = 'd/m/Y';
    case DATE_LONG = 'l, F j, Y';
    case TIME_12H = 'g:i A';
    case TIME_24H = 'H:i';
    case POSTGRES = 'Y-m-d H:i:s.uP';
}
