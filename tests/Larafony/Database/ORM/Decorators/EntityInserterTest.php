<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Decorators;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Decorators\EntityInserter;
use Larafony\Framework\Database\ORM\Model;
use PHPUnit\Framework\TestCase;

class EntityInserterTest extends TestCase
{
    private Model $model;
    private EntityInserter $inserter;

    protected function setUp(): void
    {
        // Setup DB facade before creating model
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $manager = $this->createMock(DatabaseManager::class);
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
        };

        $this->inserter = new EntityInserter($this->model);

        // Setup DB facade with mock
        $manager = $this->createMock(DatabaseManager::class);
        DB::withManager($manager);
    }

    public function testInsertCallsQueryBuilderWithChangedProperties(): void
    {
        // Set up model with changes
        $this->model->name = 'John Doe';
        $this->model->observer->markPropertyAsChanged('email', 'john@example.com');

        // Mock QueryBuilder
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('insertGetId')
            ->with([
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])
            ->willReturn('42');

        // Mock DB::table() to return our mocked QueryBuilder
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects($this->once())
            ->method('table')
            ->with('users')
            ->willReturn($queryBuilder);

        DB::withManager($manager);

        $result = $this->inserter->insert();

        $this->assertSame('42', $result);
    }
}
