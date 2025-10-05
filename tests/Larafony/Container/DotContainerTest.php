<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Container;

use ArrayObject;
use Larafony\Framework\Container\Helpers\DotContainer;
use PHPUnit\Framework\TestCase;

final class DotContainerTest extends TestCase
{
    public function testSetAndGetWithDotNotation(): void
    {
        $container = new DotContainer();

        $container->set('foo.bar.baz', 'value');
        $this->assertEquals('value', $container->get('foo.bar.baz'));

        $container->set('foo.bar.qux', 123);
        $this->assertEquals(123, $container->get('foo.bar.qux'));
    }

    public function testSetArray(): void
    {
        $container = new DotContainer();
        $container->set('test', ['foo' => 'bar', 'baz' => 'qux']);
        $this->assertEquals('bar', $container->get('test.foo'));
        $this->assertEquals('qux', $container->get('test.baz'));
    }

    public function testGetWithDefaultValue(): void
    {
        $container = new DotContainer();

        $this->assertEquals('default', $container->get('non.existent.key', 'default'));
    }

    public function testNestedArrayObjectIsCreated(): void
    {
        $container = new DotContainer();

        $container->set('foo.bar.baz', 'value');
        $this->assertInstanceOf(ArrayObject::class, $container['foo']);
        $this->assertInstanceOf(ArrayObject::class, $container['foo']['bar']);
    }
}