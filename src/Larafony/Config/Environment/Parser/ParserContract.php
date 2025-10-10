<?php

declare(strict_types=1);

namespace Larafony\Framework\Config\Environment\Parser;

use Larafony\Framework\Config\Environment\Dto\ParserResult;

interface ParserContract
{
    /**
     * Parsuje zawartość pliku .env
     */
    public function parse(string $content): ParserResult;
}
