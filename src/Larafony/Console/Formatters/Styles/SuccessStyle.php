<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Formatters\Styles;

use Larafony\Framework\Console\Enums\ForegroundColor;
use Larafony\Framework\Console\Formatters\OutputFormatterStyle;

final readonly class SuccessStyle extends OutputFormatterStyle
{
    public function __construct()
    {
        parent::__construct(
            foregroundColor: ForegroundColor::GREEN
        );
    }
}
