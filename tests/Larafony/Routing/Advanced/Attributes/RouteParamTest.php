<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Routing\Advanced\Attributes;

use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;
use Larafony\Framework\Tests\TestCase;

class RouteParamTest extends TestCase
{
    public function testConstructorWithNameOnly(): void
    {
        $param = new RouteParam('id');

        $this->assertSame('id', $param->name);
        $this->assertNull($param->default);
        $this->assertSame('', $param->bind);
    }

    public function testConstructorWithDefault(): void
    {
        $param = new RouteParam('page', 1);

        $this->assertSame('page', $param->name);
        $this->assertSame(1, $param->default);
    }

    public function testConstructorWithBind(): void
    {
        $param = new RouteParam('user', bind: 'App\Models\User');

        $this->assertSame('user', $param->name);
        $this->assertSame('App\Models\User', $param->bind);
    }

    public function testConstructorWithAllParameters(): void
    {
        $param = new RouteParam('status', 'active', 'App\Models\Status');

        $this->assertSame('status', $param->name);
        $this->assertSame('active', $param->default);
        $this->assertSame('App\Models\Status', $param->bind);
    }

    public function testDefaultCanBeString(): void
    {
        $param = new RouteParam('sort', 'created_at');

        $this->assertSame('created_at', $param->default);
    }

    public function testDefaultCanBeInt(): void
    {
        $param = new RouteParam('page', 1);

        $this->assertSame(1, $param->default);
    }

    public function testDefaultCanBeFloat(): void
    {
        $param = new RouteParam('rating', 4.5);

        $this->assertSame(4.5, $param->default);
    }

    public function testDefaultCanBeObject(): void
    {
        $obj = new \stdClass();
        $param = new RouteParam('data', $obj);

        $this->assertSame($obj, $param->default);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(RouteParam::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testAttributeTargetsMethodAndIsRepeatable(): void
    {
        $reflection = new \ReflectionClass(RouteParam::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
        $attribute = $attributes[0]->newInstance();
        $expectedFlags = \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE;
        $this->assertSame($expectedFlags, $attribute->flags);
    }
}
