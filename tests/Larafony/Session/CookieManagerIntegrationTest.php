<?php

namespace Larafony\Framework\Tests\Session;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Encryption\EncryptionService;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Storage\CookieManager;
use Larafony\Framework\Storage\CookieOptions;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Storage\Session\SessionConfiguration;
use Larafony\Framework\Web\Application;
use Larafony\Framework\Tests\TestCase;

class CookieManagerIntegrationTest extends TestCase
{
    private Application $app;
    private CookieManager $manager;
    private array $originalCookie;
    private array $originalServer;

    public function testSetAndGetCookie(): void
    {
        $name = 'test_cookie';
        $value = ['key' => 'value'];

        $this->manager->set($name, $value);

        $encryptedValue = $_COOKIE[$name] ?? null;

        $this->assertNotNull($encryptedValue);

        $retrievedValue = $this->manager->get($name);
        $this->assertEquals($value, $retrievedValue);
    }

    public function testGetDefaultValueWhenCookieNotExists(): void
    {
        $default = 'default_value';
        $value = $this->manager->get('non_existent_cookie', $default);

        $this->assertEquals($default, $value);
    }

    public function testRemoveCookie(): void
    {
        $name = 'cookie_to_remove';
        $value = 'some_value';

        $this->manager->set($name, $value);
        $this->assertArrayHasKey($name, $_COOKIE);

        $this->manager->remove($name);

        $this->assertEquals('', $_COOKIE[$name] ?? '');
    }

    public function testGetAllCookies(): void
    {
        $cookies = [
            'cookie1' => 'value1',
            'cookie2' => ['nested' => 'value2'],
            'cookie3' => 42
        ];

        foreach ($cookies as $name => $value) {
            $this->manager->set($name, $value);
        }

        $_COOKIE = array_combine(
            array_keys($cookies),
            array_map(
                fn($value) => new EncryptionService()->encrypt($value),
                $cookies
            )
        );

        $allCookies = $this->manager->all();

        $this->assertEquals($cookies, $allCookies);
    }

    public function testEncryptionAndDecryption(): void
    {
        $name = 'encrypted_cookie';
        $sensitiveData = ['password' => 'secret123', 'api_key' => 'xyz789'];

        $this->manager->set($name, $sensitiveData);

        $encryptedValue = $_COOKIE[$name] ?? null;
        $this->assertNotNull($encryptedValue);
        $this->assertNotEquals(json_encode($sensitiveData), $encryptedValue);

        $decryptedValue = $this->manager->get($name);
        $this->assertEquals($sensitiveData, $decryptedValue);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Application::empty();
        $this->app = Application::instance();
        $config = $this->createStub(ConfigContract::class);
        $encryptionKey = new KeyGenerator()->generateKey();
        $config
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'app.key' => $encryptionKey,
                default => null,
            });
        $this->app->set(ConfigContract::class, $config);
        $this->originalCookie = $_COOKIE;
        $this->originalServer = $_SERVER;

        $_COOKIE = [];
        $_SERVER['HTTPS'] = 'on';
        $this->manager = new CookieManager();
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookie;
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }
}
