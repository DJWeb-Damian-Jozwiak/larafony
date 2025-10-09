<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Container;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Web\Application;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = Application::instance();
    }

    public function testBindings(): void
    {
        $this->container->bind('const', 1);
        $this->assertEquals(1, $this->container->getBinding('const'));
    }

    public function testAutowireSimpleClass(): void
    {
        $instance = $this->container->get(Database::class);
        $this->assertInstanceOf(Database::class, $instance);
    }

    public function testAutowireWithDependencies(): void
    {
        $instance = $this->container->get(UserRepository::class);
        $this->assertInstanceOf(UserRepository::class, $instance);
        $this->assertInstanceOf(Database::class, $instance->database);
    }

    public function testSetAndGet(): void
    {
        $this->container->set('key', 'value');
        $this->assertTrue($this->container->has('key'));
        $this->assertEquals('value', $this->container->get('key'));
    }

    public function testHasReturnsFalseForNonExistent(): void
    {
        $this->assertFalse($this->container->has('nonexistent'));
    }

    public function testRegisterByClassName()
    {
        $this->container->set(Database::class, Database::class);
        $db = $this->container->get(Database::class);
        $this->assertInstanceOf(Database::class, $db);
    }
}

// Test helpers
class Database
{
}

class UserRepository
{
    public function __construct(
        public readonly Database $database,
    ) {
    }
}