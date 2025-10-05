<?php

declare(strict_types=1);

namespace Larafony\Framework\Http;

use Psr\Http\Message\UriInterface;
use Uri\Rfc3986\Uri;

readonly class UriManager implements UriInterface
{
    private Uri $uri;

    public function __construct(string $uri)
    {
        $this->uri = new Uri($uri);
    }

    public function __toString(): string
    {
        return $this->uri->toString();
    }

    public function getScheme(): string
    {
        return $this->uri->getScheme() ?? 'http';
    }

    public function getUserInfo(): string
    {
        return $this->uri->getUserInfo() ?? '';
    }

    public function getHost(): string
    {
        return $this->uri->getHost() ?? '';
    }

    public function getPort(): ?int
    {
        return $this->uri->getPort() ?? null;
    }

    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    public function getQuery(): string
    {
        return $this->uri->getQuery() ?? '';
    }

    public function getFragment(): string
    {
        return $this->uri->getFragment() ?? '';
    }

    public function getAuthority(): string
    {
        return sprintf('%s@%s:%s', $this->getUserInfo(), $this->getHost(), $this->getPort());
    }

    public function withScheme(string $scheme): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withScheme($scheme)]);
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        $userInfo = $password ? sprintf('%s:%s', $user, $password) : $user;
        return clone($this, ['uri' => $this->uri->withUserInfo($userInfo)]);
    }

    public function withHost(string $host): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withHost($host)]);
    }

    public function withPort(?int $port): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withPort($port)]);
    }

    public function withPath(string $path): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withPath($path)]);
    }

    public function withQuery(string $query): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withQuery($query)]);
    }

    public function withFragment(string $fragment): UriInterface
    {
        return clone($this, ['uri' => $this->uri->withFragment($fragment)]);
    }
}
