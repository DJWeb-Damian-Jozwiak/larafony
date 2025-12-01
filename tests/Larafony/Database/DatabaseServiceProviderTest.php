<?php

declare(strict_types=1);

namespace Larafony\Database;

use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Console\Application;
use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ServiceProviders\DatabaseServiceProvider;
use Larafony\Framework\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('integration')]
class DatabaseServiceProviderTest extends TestCase
{
    public function testDatabaseServiceProvider(): void
    {
        $basePath = dirname(__DIR__, 2) . '/public';
        $app = Application::instance($basePath);
        $app->withServiceProviders([
            ConfigServiceProvider::class,
            DatabaseServiceProvider::class
        ]);
        $manager = $app->get(DatabaseManager::class);
        $connection = $manager->connection();
        $this->assertInstanceOf(ConnectionContract::class, $connection);
    }
}