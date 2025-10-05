<?php

declare(strict_types=1);

namespace Larafony\Framework\Clock\Enums;

enum Timezone: string
{
    case UTC = 'UTC';
    case EUROPE_LONDON = 'Europe/London';
    case EUROPE_PARIS = 'Europe/Paris';
    case EUROPE_BERLIN = 'Europe/Berlin';
    case EUROPE_WARSAW = 'Europe/Warsaw';
    case EUROPE_MOSCOW = 'Europe/Moscow';
    case AMERICA_NEW_YORK = 'America/New_York';
    case AMERICA_CHICAGO = 'America/Chicago';
    case AMERICA_DENVER = 'America/Denver';
    case AMERICA_LOS_ANGELES = 'America/Los_Angeles';
    case AMERICA_SAO_PAULO = 'America/Sao_Paulo';
    case ASIA_TOKYO = 'Asia/Tokyo';
    case ASIA_SHANGHAI = 'Asia/Shanghai';
    case ASIA_HONG_KONG = 'Asia/Hong_Kong';
    case ASIA_SINGAPORE = 'Asia/Singapore';
    case ASIA_DUBAI = 'Asia/Dubai';
    case ASIA_KOLKATA = 'Asia/Kolkata';
    case AUSTRALIA_SYDNEY = 'Australia/Sydney';
    case AUSTRALIA_MELBOURNE = 'Australia/Melbourne';
    case PACIFIC_AUCKLAND = 'Pacific/Auckland';
}
