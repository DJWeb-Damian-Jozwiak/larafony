<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Factories;

use Larafony\Framework\Http\Factories\UriFactory;
use PHPUnit\Framework\TestCase;

final class UriFactoryTest extends TestCase
{
    public function testCreateUriFromString(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://example.com/path?query=value#fragment');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('/path', $uri->getPath());
        $this->assertSame('query=value', $uri->getQuery());
        $this->assertSame('fragment', $uri->getFragment());
    }

    public function testCreateUriWithPort(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://localhost:8080/api');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('localhost', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/api', $uri->getPath());
    }

    public function testCreateUriWithUserInfo(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://user:pass@example.com');

        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
    }

    public function testWithSchemeCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com');
        $newUri = $uri->withScheme('https');

        $this->assertNotSame($uri, $newUri);
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('https', $newUri->getScheme());
    }

    public function testWithHostCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com');
        $newUri = $uri->withHost('newhost.com');

        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('newhost.com', $newUri->getHost());
    }

    public function testWithPortCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com:8080');
        $newUri = $uri->withPort(9000);

        $this->assertSame(8080, $uri->getPort());
        $this->assertSame(9000, $newUri->getPort());
    }

    public function testWithPathCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com/old');
        $newUri = $uri->withPath('/new/path');

        $this->assertSame('/old', $uri->getPath());
        $this->assertSame('/new/path', $newUri->getPath());
    }

    public function testWithQueryCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com?old=value');
        $newUri = $uri->withQuery('new=data');

        $this->assertSame('old=value', $uri->getQuery());
        $this->assertSame('new=data', $newUri->getQuery());
    }

    public function testWithFragmentCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com#old');
        $newUri = $uri->withFragment('new');

        $this->assertSame('old', $uri->getFragment());
        $this->assertSame('new', $newUri->getFragment());
    }

    public function testWithUserInfoCreatesNewInstance(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com');
        $newUri = $uri->withUserInfo('username', 'password');

        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('username:password', $newUri->getUserInfo());
    }

    public function testWithUserInfoWithoutPassword(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com');
        $newUri = $uri->withUserInfo('username');

        $this->assertSame('username', $newUri->getUserInfo());
    }

    public function testToStringReturnsFullUri(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://user:pass@example.com:8080/path?query=value#fragment');

        $string = (string) $uri;
        $this->assertStringContainsString('https', $string);
        $this->assertStringContainsString('example.com', $string);
    }

    public function testGetAuthorityReturnsFormattedAuthority(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('https://user:pass@example.com:8080');

        $authority = $uri->getAuthority();
        $this->assertStringContainsString('user:pass', $authority);
        $this->assertStringContainsString('example.com', $authority);
        $this->assertStringContainsString('8080', $authority);
    }

    public function testDefaultSchemeIsHttp(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('//example.com');

        $this->assertSame('http', $uri->getScheme());
    }

    public function testEmptyComponentsReturnEmptyStrings(): void
    {
        $factory = new UriFactory();
        $uri = $factory->createUri('http://example.com');

        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }

    public function testSuperGlobalsSchemesAreSupported(): void
    {
        // Test HTTPS detection
        $_SERVER['HTTPS'] = 'on';
        $scheme = \Larafony\Framework\Http\Helpers\Uri\Scheme::get();
        $this->assertSame('https', $scheme);

        // Test HTTPS = off falls back to http
        $_SERVER['HTTPS'] = 'off';
        $scheme = \Larafony\Framework\Http\Helpers\Uri\Scheme::get();
        $this->assertSame('http', $scheme);

        // Test HTTP_X_FORWARDED_PROTO header
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $scheme = \Larafony\Framework\Http\Helpers\Uri\Scheme::get();
        $this->assertSame('https', $scheme);

        // Test default to http
        unset($_SERVER['HTTPS'], $_SERVER['HTTP_X_FORWARDED_PROTO']);
        $scheme = \Larafony\Framework\Http\Helpers\Uri\Scheme::get();
        $this->assertSame('http', $scheme);
    }
}
