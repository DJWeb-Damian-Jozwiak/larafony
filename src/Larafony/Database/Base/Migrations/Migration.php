<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Migrations;

use Larafony\Framework\Database\Base\Contracts\MigrationContract;

abstract class Migration implements MigrationContract
{
    public protected(set) string $name;

    public function __construct()
    {
    }

    public function withName(string $name): static
    {
        return clone($this, ['name' => $name]);
    }
}
