<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ServiceProviders;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Web\Config;

class DatabaseServiceProvider extends ServiceProvider
{
    public array $providers = [];

    public function boot(ContainerContract $container): void
    {
        $configBase = $container->get(ConfigContract::class);
        // Create DatabaseManager instance
        $config = $configBase->get('database.connections', []);
        $defaultConnection = $configBase->get('database.default', 'mysql');

        $manager = new DatabaseManager((array)$config)->defaultConnection($defaultConnection);

        // Register in container
        $container->set(DatabaseManager::class, $manager);

        // Set Schema facade manager
        Schema::withManager($manager);

        // Register schema builder
        $container->set('db.schema', $manager->schema());
    }
}
