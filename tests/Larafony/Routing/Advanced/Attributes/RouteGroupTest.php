<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Attributes;

use Larafony\Framework\Routing\Advanced\Attributes\RouteGroup;
use Larafony\Framework\Tests\TestCase;

class RouteGroupTest extends TestCase
{
    public function testConstructorSetsName(): void
    {
        $group = new RouteGroup('/api');

        $this->assertSame('/api', $group->name);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RouteGroup::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testAttributeTargetsClass(): void
    {
        $reflection = new \ReflectionClass(RouteGroup::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
        $attribute = $attributes[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_CLASS, $attribute->flags);
    }
}
