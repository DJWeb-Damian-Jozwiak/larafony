<?php

declare(strict_types=1);

namespace Larafony\Framework\Routing\Advanced\Enums;

enum CommonRouteRegex: string
{
    case DIGITS = '\d+';
    case UUID = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';
    case SLUG = '[a-z0-9]+(?:-[a-z0-9]+)*';
    case ALPHA = '[a-zA-Z]+';
    case ALPHA_LOWER = '[a-z]+';
    case ALPHA_UPPER = '[A-Z]+';
    case ALPHA_DASH = '[a-zA-Z-]+';
    case ALPHA_NUM = '[a-zA-Z0-9]+';
    case ISO_DATE = '\d{4}-\d{2}-\d{2}';
    case ISO_DATETIME = '\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}';
    case EMAIL = '[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}';
    case USERNAME = '[a-zA-Z0-9_-]{3,20}';
    case HEX_COLOR = '[0-9a-fA-F]{6}';
    case IP_V4 = '\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}';
    case COUNTRY_CODE = '[A-Z]{2}';
    case LOCALE = '[a-z]{2}(?:_[A-Z]{2})?';
    case YEAR = '\d{4}';
    case MONTH = '(?:0[1-9]|1[0-2])';
    case DAY = '(?:0[1-9]|[12][0-9]|3[01])';
    case CURRENCY = '[A-Z]{3}';
    case PHONE = '\+?[1-9]\d{1,14}';
    case SEMVER = '\d+\.\d+\.\d+';
    case MD5 = '[a-f0-9]{32}';
    case SHA1 = '[a-f0-9]{40}';
    case SHA256 = '[a-f0-9]{64}';
}
