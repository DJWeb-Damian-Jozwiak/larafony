<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Http\Client\Config;

use Larafony\Framework\Http\Client\Config\HttpClientConfig;
use PHPUnit\Framework\TestCase;

final class HttpClientConfigTest extends TestCase
{
    public function testHasSensibleDefaults(): void
    {
        $config = new HttpClientConfig();

        $this->assertSame(30, $config->timeout);
        $this->assertSame(10, $config->connectTimeout);
        $this->assertTrue($config->followRedirects);
        $this->assertSame(10, $config->maxRedirects);
        $this->assertTrue($config->verifyPeer);
        $this->assertTrue($config->verifyHost);
        $this->assertNull($config->proxy);
    }

    public function testCanCreateWithCustomTimeout(): void
    {
        $config = HttpClientConfig::withTimeout(60);

        $this->assertSame(60, $config->timeout);
    }

    public function testCanCreateInsecureConfig(): void
    {
        $config = HttpClientConfig::insecure();

        $this->assertFalse($config->verifyPeer);
        $this->assertFalse($config->verifyHost);
    }

    public function testCanCreateWithProxy(): void
    {
        $config = HttpClientConfig::withProxy('proxy.local:8080', 'user:pass');

        $this->assertSame('proxy.local:8080', $config->proxy);
        $this->assertSame('user:pass', $config->proxyAuth);
    }

    public function testCanCreateConfigWithoutRedirects(): void
    {
        $config = HttpClientConfig::noRedirects();

        $this->assertFalse($config->followRedirects);
    }

    public function testCanCreateHttp11Only(): void
    {
        $config = HttpClientConfig::http11();

        $this->assertSame(CURL_HTTP_VERSION_1_1, $config->httpVersion);
    }

    public function testConvertsToCurlOptions(): void
    {
        $config = new HttpClientConfig(
            timeout: 45,
            connectTimeout: 15,
            followRedirects: false,
        );

        $options = $config->toCurlOptions();

        $this->assertSame(45, $options[CURLOPT_TIMEOUT]);
        $this->assertSame(15, $options[CURLOPT_CONNECTTIMEOUT]);
        $this->assertFalse($options[CURLOPT_FOLLOWLOCATION]);
        $this->assertTrue($options[CURLOPT_RETURNTRANSFER]);
        $this->assertTrue($options[CURLOPT_HEADER]);
    }

    public function testIncludesProxyInCurlOptions(): void
    {
        $config = new HttpClientConfig(
            proxy: 'proxy.example.com:3128',
            proxyAuth: 'username:password',
        );

        $options = $config->toCurlOptions();

        $this->assertSame('proxy.example.com:3128', $options[CURLOPT_PROXY]);
        $this->assertSame('username:password', $options[CURLOPT_PROXYUSERPWD]);
    }

    public function testVerifyHostConvertsCorrectly(): void
    {
        $verifyConfig = new HttpClientConfig(verifyHost: true);
        $noVerifyConfig = new HttpClientConfig(verifyHost: false);

        $verifyOptions = $verifyConfig->toCurlOptions();
        $noVerifyOptions = $noVerifyConfig->toCurlOptions();

        $this->assertSame(2, $verifyOptions[CURLOPT_SSL_VERIFYHOST]);
        $this->assertSame(0, $noVerifyOptions[CURLOPT_SSL_VERIFYHOST]);
    }
}
