<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany;
use PHPUnit\Framework\TestCase;

class BelongsToManyTest extends TestCase
{
    private Model $parent;

    protected function setUp(): void
    {
        // Setup DB facade BEFORE creating model
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->parent = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $this->parent->id = 15;
    }

    public function testAddConstraintsAddsJoinAndWhereClause(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('join')
            ->with(
                'role_user',
                'role_user.role_id',
                '=',
                'roles.id',
                JoinType::INNER
            )
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('role_user.user_id', '=', 15);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->addConstraints();
    }

    public function testAttachInsertsRecordsIntoPivotTable(): void
    {
        $pivotQueryBuilder = $this->createMock(QueryBuilder::class);
        $pivotQueryBuilder->expects($this->exactly(3))
            ->method('insert')
            ->willReturnCallback(function ($values) {
                static $callCount = 0;
                $callCount++;

                $expected = match ($callCount) {
                    1 => ['user_id' => 15, 'role_id' => 1],
                    2 => ['user_id' => 15, 'role_id' => 2],
                    3 => ['user_id' => 15, 'role_id' => 3],
                };

                $this->assertSame($expected, $values);
                return true;
            });

        $rolesQueryBuilder = $this->createStub(QueryBuilder::class);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturnCallback(
            fn($table) => match ($table) {
                'role_user' => $pivotQueryBuilder,
                'roles' => $rolesQueryBuilder,
                default => $this->createStub(QueryBuilder::class),
            }
        );
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->attach([1, 2, 3]);
    }

    public function testAttachDoesNothingForEmptyArray(): void
    {
        $rolesQueryBuilder = $this->createStub(QueryBuilder::class);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturnCallback(
            fn($table) => match ($table) {
                'roles' => $rolesQueryBuilder,
                default => $this->fail("Unexpected table: $table"),
            }
        );
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->attach([]);

        // No exception = success
        $this->assertTrue(true);
    }

    public function testDetachRemovesSpecificRecordsFromPivotTable(): void
    {
        $pivotQueryBuilder = $this->createMock(QueryBuilder::class);
        $pivotQueryBuilder->expects($this->once())
            ->method('where')
            ->with('user_id', '=', 15)
            ->willReturnSelf();
        $pivotQueryBuilder->expects($this->once())
            ->method('whereIn')
            ->with('role_id', [2, 3])
            ->willReturnSelf();
        $pivotQueryBuilder->expects($this->once())
            ->method('delete');

        $rolesQueryBuilder = $this->createStub(QueryBuilder::class);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturnCallback(
            fn($table) => match ($table) {
                'role_user' => $pivotQueryBuilder,
                'roles' => $rolesQueryBuilder,
                default => $this->createStub(QueryBuilder::class),
            }
        );
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->detach([2, 3]);
    }

    public function testDetachRemovesAllRecordsWhenNoIdsProvided(): void
    {
        $pivotQueryBuilder = $this->createMock(QueryBuilder::class);
        $pivotQueryBuilder->expects($this->once())
            ->method('where')
            ->with('user_id', '=', 15)
            ->willReturnSelf();
        $pivotQueryBuilder->expects($this->never())
            ->method('whereIn');
        $pivotQueryBuilder->expects($this->once())
            ->method('delete');

        $rolesQueryBuilder = $this->createStub(QueryBuilder::class);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturnCallback(
            fn($table) => match ($table) {
                'role_user' => $pivotQueryBuilder,
                'roles' => $rolesQueryBuilder,
                default => $this->createStub(QueryBuilder::class),
            }
        );
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->detach();
    }

    public function testSyncDetachesAllThenAttachesNew(): void
    {
        $pivotQueryBuilder = $this->createMock(QueryBuilder::class);
        $pivotQueryBuilder->method('where')->willReturnSelf();
        $pivotQueryBuilder->expects($this->once())->method('delete');
        $pivotQueryBuilder->expects($this->exactly(3))
            ->method('insert')
            ->willReturnCallback(function ($values) {
                static $callCount = 0;
                $callCount++;

                $expected = match ($callCount) {
                    1 => ['user_id' => 15, 'role_id' => 1],
                    2 => ['user_id' => 15, 'role_id' => 3],
                    3 => ['user_id' => 15, 'role_id' => 4],
                };

                $this->assertSame($expected, $values);
                return true;
            });

        $rolesQueryBuilder = $this->createStub(QueryBuilder::class);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturnCallback(
            fn($table) => match ($table) {
                'role_user' => $pivotQueryBuilder,
                'roles' => $rolesQueryBuilder,
                default => $this->createStub(QueryBuilder::class),
            }
        );
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $relation = new BelongsToMany(
            $this->parent,
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation->sync([1, 3, 4]);
    }
}
