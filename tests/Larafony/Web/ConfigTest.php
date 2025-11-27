<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Web;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Config\Environment\Exception\EnvironmentError;
use Larafony\Framework\Config\ServiceProviders\ConfigServiceProvider;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Web\Config;

class ConfigTest extends TestCase
{
    public function testConfig(): void
    {
        $basePath = dirname(__DIR__, 2) . '/public';
        $app = Application::instance($basePath);
        $app->withServiceProviders([ConfigServiceProvider::class]);
        $config = $app->get(ConfigContract::class);
        $config->loadConfig();
        Config::set('test', 'test');
        $this->assertSame('Larafony', $config->get('app.name'));
        $this->assertSame('test', $config->get('test'));
        $this->assertSame('Larafony', Config::get('app.name'));
    }

    public function testConfigWithInvalidPath(): void
    {
        $basePath = dirname(__DIR__, 2) . '/not-exists';
        $app = Application::instance($basePath);
        $this->expectException(EnvironmentError::class);
        $app->withServiceProviders([ConfigServiceProvider::class]);
        $config = $app->get(ConfigContract::class);
        $config->loadConfig();
        $this->assertNull($config->get('app.name'));
    }
}