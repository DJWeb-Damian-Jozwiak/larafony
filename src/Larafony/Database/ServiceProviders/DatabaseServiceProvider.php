<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Database\Base\Migrations\MigrationRepository as BaseMigrationRepository;
use Larafony\Framework\Database\Base\Migrations\MigrationResolver;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Drivers\MySQL\Migrations\MigrationRepository;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\Schema;

class DatabaseServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);
        $configBase = $container->get(ConfigContract::class);
        // Create DatabaseManager instance
        $config = $configBase->get('database.connections', []);
        $defaultConnection = $configBase->get('database.default', 'mysql');

        $manager = new DatabaseManager((array) $config, $container)->defaultConnection($defaultConnection);

        // Register in container
        $container->set(DatabaseManager::class, $manager);

        // Register default connection as ConnectionContract
        // This allows automatic resolution of ConnectionContract dependencies
        $container->set(
            \Larafony\Framework\Database\Base\Contracts\ConnectionContract::class,
            $manager->connection()
        );

        $migrationPath = $configBase->get('database.migrations.path', 'database/migrations');
        $migrationPath = $container->getBinding('base_path') . '/' . $migrationPath;

        $container->set(MigrationResolver::class, new MigrationResolver($migrationPath));

        // Register MigrationRepository implementation
        $container->set(
            BaseMigrationRepository::class,
            new MigrationRepository($manager->connection())
        );

        // Set Schema facade manager
        Schema::withManager($manager);

        // Set DB (ORM) facade manager
        DB::withManager($manager);

        // Register schema builder
        $container->set('db.schema', $manager->schema());
    }
}
