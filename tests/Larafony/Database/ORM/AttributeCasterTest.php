<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM;

use Larafony\Framework\Database\ORM\Attributes\CastUsing;
use Larafony\Framework\Database\ORM\Casters\AttributeCaster;
use PHPUnit\Framework\TestCase;

class AttributeCasterTest extends TestCase
{
    private AttributeCaster $caster;

    protected function setUp(): void
    {
        $this->caster = new AttributeCaster();
    }

    public function testReturnsNullWhenValueIsNull(): void
    {
        $model = new class {
            #[CastUsing(strtoupper(...))]
            public mixed $name;
        };

        $result = $this->caster->cast(null, 'name', $model);

        $this->assertNull($result);
    }

    public function testCastsUsingAttributeWithFirstClassCallable(): void
    {
        $model = new class {
            #[CastUsing(strtoupper(...))]
            public mixed $name;
        };

        $result = $this->caster->cast('john', 'name', $model);

        $this->assertSame('JOHN', $result);
    }

    public function testCastsUsingStaticMethod(): void
    {
        $model = new class {
            #[CastUsing(self::toInt(...))]
            public mixed $age;

            public static function toInt(mixed $value): int
            {
                return (int) $value;
            }
        };

        $result = $this->caster->cast('25', 'age', $model);

        $this->assertSame(25, $result);
    }

    public function testReturnsValueUnchangedWhenNoCastUsingAttribute(): void
    {
        $model = new class {
            public mixed $plain_value;
        };

        $result = $this->caster->cast('test', 'plain_value', $model);

        $this->assertSame('test', $result);
    }

    public function testCastsWithComplexCallable(): void
    {
        $model = new class {
            #[CastUsing(self::parseDate(...))]
            public mixed $created_at;

            public static function parseDate(string $value): array
            {
                return ['parsed' => $value, 'timestamp' => strtotime($value)];
            }
        };

        $result = $this->caster->cast('2024-12-25', 'created_at', $model);

        $this->assertIsArray($result);
        $this->assertSame('2024-12-25', $result['parsed']);
        $this->assertIsInt($result['timestamp']);
    }

    public function testMultiplePropertiesWithDifferentCasters(): void
    {
        $model = new class {
            #[CastUsing(strtoupper(...))]
            public mixed $name;

            #[CastUsing(intval(...))]
            public mixed $age;

            #[CastUsing(boolval(...))]
            public mixed $active;
        };

        $this->assertSame('TEST', $this->caster->cast('test', 'name', $model));
        $this->assertSame(30, $this->caster->cast('30', 'age', $model));
        $this->assertTrue($this->caster->cast(1, 'active', $model));
    }
}
