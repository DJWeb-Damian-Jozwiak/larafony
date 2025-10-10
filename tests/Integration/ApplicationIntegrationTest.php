<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Integration;

use Larafony\Framework\Http\Client\HttpClientFactory;
use Larafony\Framework\Http\Request;
use Larafony\Framework\Tests\TestCase;
use Larafony\Framework\Web\Config;
use Psr\Http\Client\ClientInterface;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Web\Application;
use Psr\Http\Message\RequestFactoryInterface;

class ApplicationIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        restore_error_handler();
        restore_exception_handler();
    }
    public function testApplicationRespondsWwith200(): void
    {
        $app = require __DIR__ . '/../../demo-app/bootstrap/web_app.php';
        $this->assertInstanceOf(Application::class, $app);
        $factory = $app->get(RequestFactoryInterface::class);
        $url = Config::get('app.url');
        $response = HttpClientFactory::instance()->sendRequest($factory->createRequest('GET', $url));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testApplicationConfigIsLoaded(): void
    {
        // Load application from bootstrap
        $app = require __DIR__ . '/../../demo-app/bootstrap/web_app.php';

        // Verify config is loaded
        $config = $app->get(ConfigContract::class);
        $config->loadConfig();

        $this->assertTrue($config->has('app'));
        $this->assertTrue($config->has('app.name'));
        $this->assertTrue($config->has('app.url'));
    }

    public function testEnvironmentVariablesAreLoaded(): void
    {
        // Load application from bootstrap
        $app = require __DIR__ . '/../../demo-app/bootstrap/web_app.php';

        $config = $app->get(ConfigContract::class);
        $config->loadConfig();

        // Verify .env was loaded
        $this->assertArrayHasKey('APP_NAME', $_ENV);
        $this->assertArrayHasKey('APP_URL', $_ENV);

        // Verify config uses env values
        $this->assertEquals($_ENV['APP_NAME'], $config->get('app.name'));
        $this->assertEquals($_ENV['APP_URL'], $config->get('app.url'));
    }
}
