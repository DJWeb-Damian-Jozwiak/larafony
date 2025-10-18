<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

interface Castable
{
    public static function from(string $value): static;
}
