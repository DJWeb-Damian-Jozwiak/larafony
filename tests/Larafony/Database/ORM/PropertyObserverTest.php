<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\PropertyObserver;
use PHPUnit\Framework\TestCase;

class PropertyObserverTest extends TestCase
{
    private PropertyObserver $observer;
    private Model $model;

    protected function setUp(): void
    {
        // Setup DB facade
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->model = new class extends Model {
            public string $table {
                get => 'test_table';
            }

            public ?string $name {
                get => $this->name;
                set {
                    $this->name = $value;
                    $this->markPropertyAsChanged('name');
                }
            }
        };

        $this->observer = $this->model->observer;
    }

    public function testMarkPropertyAsChangedStoresValue(): void
    {
        $this->observer->markPropertyAsChanged('name', 'John');

        $changes = $this->observer->getChangedProperties();

        $this->assertArrayHasKey('name', $changes);
        $this->assertSame('John', $changes['name']);
    }

    public function testIsNewReturnsTrueWhenNoPrimaryKeySet(): void
    {
        $this->assertTrue($this->observer->is_new);
    }

    public function testIsNewReturnsFalseWhenPrimaryKeySet(): void
    {
        $this->observer->markPropertyAsChanged('id', 1);

        $this->assertFalse($this->observer->is_new);
    }

    public function testToStringConvertsStringableToString(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'test';
            }
        };

        $result = $this->observer->toString($stringable);

        $this->assertSame('test', $result);
    }

    public function testToStringConvertsArrayToJson(): void
    {
        $array = ['key' => 'value'];

        $result = $this->observer->toString($array);

        $this->assertSame('{"key":"value"}', $result);
    }

    public function testToStringConvertsObjectToJson(): void
    {
        $object = (object) ['key' => 'value'];

        $result = $this->observer->toString($object);

        $this->assertSame('{"key":"value"}', $result);
    }

    public function testToStringReturnsScalarAsIs(): void
    {
        $this->assertSame('test', $this->observer->toString('test'));
        $this->assertSame(123, $this->observer->toString(123));
        $this->assertSame(12.34, $this->observer->toString(12.34));
        $this->assertNull($this->observer->toString(null));
    }

    public function testGetChangedPropertiesReturnsAllChanges(): void
    {
        $this->observer->markPropertyAsChanged('name', 'John');
        $this->observer->markPropertyAsChanged('email', 'john@example.com');

        $changes = $this->observer->getChangedProperties();

        $this->assertCount(2, $changes);
        $this->assertSame('John', $changes['name']);
        $this->assertSame('john@example.com', $changes['email']);
    }
}
