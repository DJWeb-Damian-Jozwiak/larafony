<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Contracts\Clock;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\Attributes\CastUsing;
use Larafony\Framework\Database\ORM\Contracts\Castable;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    private Model $model;

    protected function setUp(): void
    {
        // Setup DB facade
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->model = new class extends Model {
            public string $table {
                get => 'users';
            }

            public ?string $name {
                get => $this->name;
                set {
                    $this->name = $value;
                    $this->markPropertyAsChanged('name');
                }
            }

            public ?string $email {
                get => $this->email;
                set {
                    $this->email = $value;
                    $this->markPropertyAsChanged('email');
                }
            }

            #[CastUsing(TestEnum::from(...))]
            public ?TestEnum $status {
                get => $this->status;
                set {
                    $this->status = $value;
                    $this->markPropertyAsChanged('status');
                }
            }

            #[CastUsing(ClockFactory::parse(...))]
            public ?Clock $created_at {
                get => $this->created_at;
                set {
                    $this->created_at = $value;
                    $this->markPropertyAsChanged('created_at');
                }
            }
        };
    }

    public function testConstructorInitializesComponents(): void
    {
        $this->assertInstanceOf(ModelQueryBuilder::class, $this->model->query_builder);
        $this->assertNotNull($this->model->observer);
    }

    public function testFillSetsPropertiesFromArray(): void
    {
        $this->model->fill([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertSame('John Doe', $this->model->name);
        $this->assertSame('john@example.com', $this->model->email);
    }

    public function testFillIgnoresNonExistentProperties(): void
    {
        $this->model->fill([
            'name' => 'John',
            'non_existent' => 'value',
        ]);

        $this->assertSame('John', $this->model->name);
        $this->assertFalse(property_exists($this->model, 'non_existent'));
    }

    public function testFillCastsAttributesUsingCastUsingAttribute(): void
    {
        $this->model->fill([
            'status' => 'active',
        ]);

        $this->assertInstanceOf(TestEnum::class, $this->model->status);
        $this->assertSame(TestEnum::Active, $this->model->status);
    }

    public function testMarkPropertyAsChangedNotifiesObserver(): void
    {
        $this->model->name = 'John';

        $changes = $this->model->observer->getChangedProperties();

        $this->assertArrayHasKey('name', $changes);
        $this->assertSame('John', $changes['name']);
    }

    public function testIsNewReturnsTrueForNewModel(): void
    {
        $this->assertTrue($this->model->is_new);
    }

    public function testIsNewReturnsFalseWhenIdIsSet(): void
    {
        $this->model->id = 1;

        $this->assertFalse($this->model->is_new);
    }

    public function testQueryReturnsModelQueryBuilder(): void
    {
        $builder = $this->model::query();

        $this->assertInstanceOf(ModelQueryBuilder::class, $builder);
    }

    public function testGetTableReturnsTableName(): void
    {
        $table = $this->model->table;

        $this->assertSame('users', $table);
    }

    public function testCastAttributeHandlesDatetime(): void
    {
        $this->model->fill(['created_at' => '2024-01-01 12:00:00']);

        $this->assertInstanceOf(Clock::class, $this->model->created_at);
        $this->assertSame('2024-01-01', $this->model->created_at->format('Y-m-d'));
    }

    public function testCastAttributeHandlesDatetimeImmutablePassthrough(): void
    {
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $this->model->fill(['created_at' => '2024-01-01 12:00:00']);

        $this->assertSame($date->format('Y-m-d H:i:s'), $this->model->created_at->format('Y-m-d H:i:s'));
    }

    public function testCastAttributeHandlesBackedEnum(): void
    {
        $this->model->fill(['status' => 'active']);

        $this->assertSame(TestEnum::Active, $this->model->status);
    }

    public function testJsonSerializeThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Direct serialization of models is not allowed');

        $this->model->jsonSerialize();
    }

    public function testIdPropertyTriggersMarkAsChanged(): void
    {
        $this->model->id = 123;

        $changes = $this->model->observer->getChangedProperties();

        $this->assertArrayHasKey('id', $changes);
        $this->assertSame(123, $changes['id']);
    }
}

enum TestEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
