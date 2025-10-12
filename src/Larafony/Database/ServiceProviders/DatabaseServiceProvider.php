<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Web\Config;

class DatabaseServiceProvider extends ServiceProvider
{
    public array $providers = [];

    public function register(ContainerContract $container): self
    {
        // Create DatabaseManager instance
        $config = Config::get('database.connections', []);
        $defaultConnection = Config::get('database.default', 'mysql');

        $manager = new DatabaseManager($config);
        $manager->setDefaultConnection($defaultConnection);

        // Register in container
        $container->set(DatabaseManager::class, $manager);

        // Set Schema facade manager
        Schema::setManager($manager);

        // Register schema builder
        $container->set('db.schema', $manager->schema());

        return $this;
    }
}
