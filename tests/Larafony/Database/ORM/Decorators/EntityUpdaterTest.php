<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Decorators;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Decorators\EntityUpdater;
use Larafony\Framework\Database\ORM\Model;
use PHPUnit\Framework\TestCase;

class EntityUpdaterTest extends TestCase
{
    private Model $model;
    private EntityUpdater $updater;

    protected function setUp(): void
    {
        // Setup DB facade before creating model
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
        };

        $this->updater = new EntityUpdater($this->model);
    }

    public function testUpdateCallsQueryBuilderWithChangedPropertiesAndWhereClause(): void
    {
        // Set up model with ID and changes
        $this->model->id = 10;
        $this->model->name = 'Jane Doe';

        // Mock QueryBuilder with fluent interface
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('id', '=', 10)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('update')
            ->with($this->callback(function ($values) {
                return isset($values['name']) && $values['name'] === 'Jane Doe';
            }))
            ->willReturn(1);

        // Mock DB::table()
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects($this->once())
            ->method('table')
            ->with('users')
            ->willReturn($queryBuilder);

        DB::withManager($manager);

        $this->updater->update();
    }
}
