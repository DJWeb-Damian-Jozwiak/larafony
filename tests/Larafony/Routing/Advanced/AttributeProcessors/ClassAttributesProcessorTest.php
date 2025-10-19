<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\AttributeProcessors;

use Larafony\Framework\Routing\Advanced\AttributeProcessors\ClassAttributesProcessor;
use Larafony\Framework\Routing\Advanced\Attributes\Middleware;
use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;
use Larafony\Framework\Tests\Routing\Advanced\Decorators\TestMiddleware;
use Larafony\Framework\Tests\TestCase;
use ReflectionClass;

class ClassAttributesProcessorTest extends TestCase
{
    public function testProcessesRouteGroupAttribute(): void
    {
        $reflection = new ReflectionClass(TestControllerWithGroup::class);
        $processor = new ClassAttributesProcessor($reflection);

        $this->assertInstanceOf(RouteGroup::class, $processor->routeGroup);
        $this->assertSame('/api', $processor->routeGroup->name);
    }

    public function testProcessesMiddlewareAttribute(): void
    {
        $reflection = new ReflectionClass(TestControllerWithMiddleware::class);
        $processor = new ClassAttributesProcessor($reflection);

        $this->assertInstanceOf(Middleware::class, $processor->middleware);
    }

    public function testProcessesBothAttributes(): void
    {
        $reflection = new ReflectionClass(TestControllerWithBoth::class);
        $processor = new ClassAttributesProcessor($reflection);

        $this->assertInstanceOf(RouteGroup::class, $processor->routeGroup);
        $this->assertInstanceOf(Middleware::class, $processor->middleware);
    }

    public function testHandlesNoAttributes(): void
    {
        $reflection = new ReflectionClass(TestControllerWithoutAttributes::class);
        $processor = new ClassAttributesProcessor($reflection);

        $this->assertNull($processor->routeGroup);
        $this->assertNull($processor->middleware);
    }

    public function testTakesFirstRouteGroupOnly(): void
    {
        $reflection = new ReflectionClass(TestControllerWithGroup::class);
        $processor = new ClassAttributesProcessor($reflection);

        $this->assertInstanceOf(RouteGroup::class, $processor->routeGroup);
        $this->assertSame('/api', $processor->routeGroup->name);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(ClassAttributesProcessor::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}

#[RouteGroup('/api')]
class TestControllerWithGroup
{
}

#[Middleware(beforeGlobal: [TestMiddleware::class])]
class TestControllerWithMiddleware
{
}

#[RouteGroup('/api')]
#[Middleware(beforeGlobal: [TestMiddleware::class])]
class TestControllerWithBoth
{
}

class TestControllerWithoutAttributes
{
}
