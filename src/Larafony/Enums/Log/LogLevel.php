<?php

declare(strict_types=1);

namespace Larafony\Framework\Enums\Log;

use Psr\Log\LogLevel as PsrLogLevelConstants;

enum LogLevel: string
{
    case EMERGENCY = PsrLogLevelConstants::EMERGENCY;
    case ALERT = PsrLogLevelConstants::ALERT;
    case CRITICAL = PsrLogLevelConstants::CRITICAL;
    case ERROR = PsrLogLevelConstants::ERROR;
    case WARNING = PsrLogLevelConstants::WARNING;
    case NOTICE = PsrLogLevelConstants::NOTICE;
    case INFO = PsrLogLevelConstants::INFO;
    case DEBUG = PsrLogLevelConstants::DEBUG;
}
