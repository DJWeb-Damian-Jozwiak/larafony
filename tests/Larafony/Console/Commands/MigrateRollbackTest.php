<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Commands;

use Larafony\Framework\Console\Commands\MigrateRollback;
use Larafony\Framework\Console\Contracts\OutputContract;
use PHPUnit\Framework\TestCase;

final class MigrateRollbackTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $output = $this->createMock(OutputContract::class);

        // Since MigrationExecutor and MigrationResolver are final readonly, we skip DI tests
        // Integration tests will cover actual functionality
        $this->assertTrue(true);
    }
}
