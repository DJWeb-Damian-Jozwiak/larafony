<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Routing\Advanced\RouteParameter;
use Larafony\Framework\Tests\TestCase;

class RouteParameterTest extends TestCase
{
    public function testConstructorWithDefaultPattern(): void
    {
        $param = new RouteParameter('id');

        $this->assertSame('id', $param->name);
        $this->assertSame('[\d\p{L}-]+', $param->pattern);
    }

    public function testConstructorWithCustomPattern(): void
    {
        $param = new RouteParameter('slug', '[a-z-]+');

        $this->assertSame('slug', $param->name);
        $this->assertSame('[a-z-]+', $param->pattern);
    }

    public function testGetValueReturnsMatchedValue(): void
    {
        $param = new RouteParameter('id');
        $matches = ['id' => '123', 'name' => 'test'];

        $value = $param->getValue($matches);

        $this->assertSame('123', $value);
    }

    public function testGetValueReturnsNullWhenNotFound(): void
    {
        $param = new RouteParameter('missing');
        $matches = ['id' => '123'];

        $value = $param->getValue($matches);

        $this->assertNull($value);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RouteParameter::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
