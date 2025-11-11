<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Container\ServiceProvider;
use Larafony\Framework\Log\LoggerFactory;
use Psr\Log\LoggerInterface;

class LogServiceProvider extends ServiceProvider
{
    public function boot(ContainerContract $container): void
    {
        parent::boot($container);
        $logger = LoggerFactory::create();
        $container->set(LoggerInterface::class, $logger);
    }
}