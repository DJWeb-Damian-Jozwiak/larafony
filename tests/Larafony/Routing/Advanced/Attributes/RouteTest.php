<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Attributes;

use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Tests\TestCase;

class RouteTest extends TestCase
{
    public function testConstructorWithDefaultMethod(): void
    {
        $route = new Route('/users');

        $this->assertSame('/users', $route->path);
        $this->assertSame(['GET'], $route->methods);
    }

    public function testConstructorWithSingleMethod(): void
    {
        $route = new Route('/users', 'POST');

        $this->assertSame('/users', $route->path);
        $this->assertSame(['POST'], $route->methods);
    }

    public function testConstructorWithMultipleMethods(): void
    {
        $route = new Route('/users', ['GET', 'POST']);

        $this->assertSame('/users', $route->path);
        $this->assertSame(['GET', 'POST'], $route->methods);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(Route::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testAttributeTargetsMethod(): void
    {
        $reflection = new \ReflectionClass(Route::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_METHOD, $attribute->flags);
    }
}
