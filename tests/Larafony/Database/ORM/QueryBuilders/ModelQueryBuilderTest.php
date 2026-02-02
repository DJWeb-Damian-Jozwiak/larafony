<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\QueryBuilders;

use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\QueryBuilders\ModelQueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ModelQueryBuilderTest extends TestCase
{
    private Model $model;

    protected function setUp(): void
    {
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
    }

    private function createQueryBuilderWithMock(): array
    {
        $mockBuilder = $this->createMock(QueryBuilder::class);
        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($mockBuilder);
        DB::withManager($manager);

        $queryBuilder = new ModelQueryBuilder($this->model);

        $reflection = new \ReflectionClass($queryBuilder);
        $property = $reflection->getProperty('builder');
        $property->setValue($queryBuilder, $mockBuilder);

        return [$queryBuilder, $mockBuilder];
    }

    public function testWhereCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('where')
            ->with('name', '=', 'John');

        $result = $queryBuilder->where('name', '=', 'John');

        $this->assertSame($queryBuilder, $result);
    }

    public function testOrWhereCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('orWhere')
            ->with('email', '=', 'test@example.com');

        $queryBuilder->orWhere('email', '=', 'test@example.com');
    }

    public function testWhereInCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereIn')
            ->with('id', [1, 2, 3]);

        $queryBuilder->whereIn('id', [1, 2, 3]);
    }

    public function testWhereNotInCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereNotIn')
            ->with('status', ['deleted']);

        $queryBuilder->whereNotIn('status', ['deleted']);
    }

    public function testWhereNullCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereNull')
            ->with('deleted_at');

        $queryBuilder->whereNull('deleted_at');
    }

    public function testWhereNotNullCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereNotNull')
            ->with('email_verified_at');

        $queryBuilder->whereNotNull('email_verified_at');
    }

    public function testWhereBetweenCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereBetween')
            ->with('age', [18, 65]);

        $queryBuilder->whereBetween('age', [18, 65]);
    }

    public function testWhereLikeCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('whereLike')
            ->with('name', '%John%');

        $queryBuilder->whereLike('name', '%John%');
    }

    public function testWhereNestedCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $callback = fn () => null;

        $mockBuilder->expects($this->once())
            ->method('whereNested')
            ->with($callback, 'and');

        $queryBuilder->whereNested($callback);
    }

    public function testJoinCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('join')
            ->with('posts', 'users.id', '=', 'posts.user_id', JoinType::INNER);

        $queryBuilder->join('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testLeftJoinCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('posts', 'users.id', '=', 'posts.user_id');

        $queryBuilder->leftJoin('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testRightJoinCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('rightJoin')
            ->with('posts', 'users.id', '=', 'posts.user_id');

        $queryBuilder->rightJoin('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testOrderByCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('orderBy')
            ->with('created_at', OrderDirection::DESC);

        $queryBuilder->orderBy('created_at', OrderDirection::DESC);
    }

    public function testLatestCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('latest')
            ->with('created_at');

        $queryBuilder->latest();
    }

    public function testOldestCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('oldest')
            ->with('updated_at');

        $queryBuilder->oldest('updated_at');
    }

    public function testLimitCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('limit')
            ->with(10);

        $queryBuilder->limit(10);
    }

    public function testOffsetCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('offset')
            ->with(20);

        $queryBuilder->offset(20);
    }

    public function testSelectCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('select')
            ->with(['id', 'name']);

        $queryBuilder->select(['id', 'name']);
    }

    public function testGetHydratesResults(): void
    {
        $stubBuilder = $this->createStub(QueryBuilder::class);
        $stubBuilder->method('get')->willReturn([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($stubBuilder);
        DB::withManager($manager);

        $queryBuilder = new ModelQueryBuilder($this->model);
        $reflection = new \ReflectionClass($queryBuilder);
        $property = $reflection->getProperty('builder');
        $property->setValue($queryBuilder, $stubBuilder);

        $results = $queryBuilder->get();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Model::class, $results);
        $this->assertSame('John', $results[0]->name);
        $this->assertSame('Jane', $results[1]->name);
    }

    public function testFirstReturnsHydratedModel(): void
    {
        $stubBuilder = $this->createStub(QueryBuilder::class);
        $stubBuilder->method('first')->willReturn(['id' => 1, 'name' => 'John']);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($stubBuilder);
        DB::withManager($manager);

        $queryBuilder = new ModelQueryBuilder($this->model);
        $reflection = new \ReflectionClass($queryBuilder);
        $property = $reflection->getProperty('builder');
        $property->setValue($queryBuilder, $stubBuilder);

        $result = $queryBuilder->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertSame('John', $result->name);
    }

    public function testFirstReturnsNullWhenNoResults(): void
    {
        $stubBuilder = $this->createStub(QueryBuilder::class);
        $stubBuilder->method('first')->willReturn(null);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($stubBuilder);
        DB::withManager($manager);

        $queryBuilder = new ModelQueryBuilder($this->model);
        $reflection = new \ReflectionClass($queryBuilder);
        $property = $reflection->getProperty('builder');
        $property->setValue($queryBuilder, $stubBuilder);

        $result = $queryBuilder->first();

        $this->assertNull($result);
    }

    public function testCountCallsUnderlyingBuilder(): void
    {
        [$queryBuilder, $mockBuilder] = $this->createQueryBuilderWithMock();

        $mockBuilder->expects($this->once())
            ->method('count')
            ->with('*')
            ->willReturn(42);

        $result = $queryBuilder->count();

        $this->assertSame(42, $result);
    }
}
