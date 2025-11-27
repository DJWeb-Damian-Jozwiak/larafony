<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Storage\Session\Handlers\DatabaseSessionHandler;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Storage\Session\SessionConfiguration;
use Larafony\Framework\Storage\Session\SessionManager;
use Larafony\Framework\Storage\Session\SessionSecurity;
use Larafony\Framework\Web\Config;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * @return array<int|string, class-string>
     */
    public function providers(): array
    {
        return [SessionConfiguration::class => SessionConfiguration::class];
    }
    public function boot(ContainerContract $container): void
    {
        parent::boot($container);
        /** @var SessionConfiguration $config */
        $config = $container->get(SessionConfiguration::class);
        $path = Config::get('session.path');
        $security = new SessionSecurity();
        $config->registerHandler(new FileSessionHandler($path, $security));
        $config->registerHandler(new DatabaseSessionHandler($security));
        $manager = new SessionManager($config);
        $manager->start();
        $container->set(SessionManager::class, $manager);
    }
}
