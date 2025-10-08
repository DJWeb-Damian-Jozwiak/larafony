<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\ServiceProviders;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Http\Factories\RequestFactory;
use Larafony\Framework\Http\Factories\ResponseFactory;
use Larafony\Framework\Http\Factories\ServerRequestFactory;
use Larafony\Framework\Http\Factories\StreamFactory;
use Larafony\Framework\Http\Factories\UploadedFileFactory;
use Larafony\Framework\Http\Factories\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class HttpServiceProvider extends \Larafony\Framework\Container\ServiceProvider
{
    /**
     * @var array<string, class-string> $providers
     */
    public array $providers {
        get => [
            RequestFactoryInterface::class => RequestFactory::class,
            ResponseFactoryInterface::class => ResponseFactory::class,
            ServerRequestFactoryInterface::class => ServerRequestFactory::class,
            StreamFactoryInterface::class => StreamFactory::class,
            UploadedFileFactoryInterface::class => UploadedFileFactory::class,
            UriFactoryInterface::class => UriFactory::class,
        ];
    }

    public function register(ContainerContract $container): self
    {
        parent::register($container);
        $container->set(
            ServerRequestInterface::class,
            $container->get(ServerRequestFactoryInterface::class)->createServerRequestFromGlobals()
        );
        return $this;
    }
}
