<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced;

use Larafony\Framework\Routing\Advanced\RouteBinding;
use Larafony\Framework\Tests\TestCase;

class RouteBindingTest extends TestCase
{
    public function testConstructorWithDefaultFindMethod(): void
    {
        $binding = new RouteBinding('App\Models\User');

        $this->assertSame('App\Models\User', $binding->modelClass);
        $this->assertSame('findForRoute', $binding->findMethod);
    }

    public function testConstructorWithCustomFindMethod(): void
    {
        $binding = new RouteBinding('App\Models\Post', 'findBySlug');

        $this->assertSame('App\Models\Post', $binding->modelClass);
        $this->assertSame('findBySlug', $binding->findMethod);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RouteBinding::class);

        $this->assertTrue($reflection->isReadOnly());
    }
}
