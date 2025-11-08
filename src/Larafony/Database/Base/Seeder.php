<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base;

abstract class Seeder
{
    abstract public function run(): void;

    /**
     * @param class-string<Seeder> $seeder
     */
    protected function call(string $seeder): void
    {
        $instance = new $seeder();
        $instance->run();
    }
}
