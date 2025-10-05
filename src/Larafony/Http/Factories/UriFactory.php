<?php

declare(strict_types=1);

namespace Larafony\Framework\Http\Factories;

use Larafony\Framework\Http\Helpers\Uri\Authority;
use Larafony\Framework\Http\Helpers\Uri\Query;
use Larafony\Framework\Http\Helpers\Uri\Scheme;
use Larafony\Framework\Http\UriManager;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriFactory implements UriFactoryInterface
{
    public function createUriFromGlobals(): UriInterface
    {
        $uri = sprintf(
            '%s://%s%s%s',
            Scheme::get(),
            new Authority()->get(),
            $_SERVER['REQUEST_URI'] ?? '/',
            Query::get(),
        );
        return new UriManager($uri);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new UriManager($uri);
    }
}
