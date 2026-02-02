<?php

namespace Larafony\Framework\Tests\Session;

use Larafony\Framework\Config\Contracts\ConfigContract;
use Larafony\Framework\Encryption\KeyGenerator;
use Larafony\Framework\Storage\EnvFileHandler;
use Larafony\Framework\Storage\Session\Handlers\FileSessionHandler;
use Larafony\Framework\Storage\Session\SessionConfiguration;
use Larafony\Framework\Storage\Session\SessionManager;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\MockObject\Stub;
use SessionHandlerInterface;
use Larafony\Framework\Tests\TestCase;

class SessionManagerTest extends TestCase
{
    private SessionManager $manager;
    private SessionConfiguration $configuration;
    private ConfigContract&Stub $config;
    private SessionHandlerInterface $handler;
    private Application $app;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        Application::empty();
        $this->app = Application::instance();
        $this->config = $this->createStub(ConfigContract::class);
        $this->configuration = new SessionConfiguration();
        $this->handler = $this->createStub(SessionHandlerInterface::class);
        $this->tempDir = sys_get_temp_dir() . '/sessions_' . uniqid();
        $this->config
            ->method('get')
            ->willReturnCallback(fn(string $key) => match ($key) {
                'session.cookie_params' => [
                    'lifetime' => 7200,
                    'path' => null,
                    'domain' => null,
                    'secure' =>  isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ],
                'session.handler' => FileSessionHandler::class,
                'session.path' => $this->tempDir,
                'app.key' => new KeyGenerator()->generateKey(),
                default => null,
            });
        $this->app->set(ConfigContract::class, $this->config);

        mkdir($this->tempDir);
    }

    #[RunInSeparateProcess]
    public function testStartWhenSessionAlreadyActive(): void
    {
        session_start();

        $manager = SessionManager::create();
        $this->assertTrue($manager->start());

        // Sprawdzamy, że flaga started została ustawiona
        $reflection = new \ReflectionClass($manager);
        $startedProperty = $reflection->getProperty('started');
        $this->assertTrue($startedProperty->getValue($manager));
    }
    public function testCreate(): void
    {
        $manager = SessionManager::create();

        $this->assertInstanceOf(SessionManager::class, $manager);
        $this->assertTrue($manager->start());
    }

    public function testStartWhenNotStarted(): void
    {
        $manager = SessionManager::create();

        $this->assertTrue($manager->start());
        $this->assertTrue($manager->start()); // drugi raz powinno też zwrócić true
    }

    public function testStartWhenAlreadyStarted(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $this->assertTrue($manager->start());
    }

    public function testGetId(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $this->assertIsString($manager->getId());
    }

    public function testSetAndGet(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $manager->set('key', 'value');
        $this->assertEquals('value', $manager->get('key'));
        $this->assertNull($manager->get('nonexistent'));
        $this->assertEquals('default', $manager->get('nonexistent', 'default'));
    }



    public function testRemove(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $manager->set('key', 'value');
        $this->assertTrue($manager->has('key'));

        $manager->remove('key');
        $this->assertFalse($manager->has('key'));
    }

    public function testHas(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $this->assertFalse($manager->has('key'));
        $manager->set('key', 'value');
        $this->assertTrue($manager->has('key'));
    }

    public function testClear(): void
    {
        $manager = SessionManager::create();
        $manager->start();

        $manager->set('key1', 'value1');
        $manager->set('key2', 'value2');

        $manager->clear();
        $this->assertEmpty($manager->all());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        array_map('unlink', glob($this->tempDir . '/*'));
        rmdir($this->tempDir);
    }
}
