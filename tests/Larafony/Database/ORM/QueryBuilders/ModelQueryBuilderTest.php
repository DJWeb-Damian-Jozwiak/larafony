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
use PHPUnit\Framework\TestCase;

class ModelQueryBuilderTest extends TestCase
{
    private Model $model;
    private ModelQueryBuilder $queryBuilder;
    private QueryBuilder $mockBuilder;

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

        // Mock the underlying QueryBuilder
        $this->mockBuilder = $this->createMock(QueryBuilder::class);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($this->mockBuilder);
        DB::withManager($manager);

        $this->queryBuilder = new ModelQueryBuilder($this->model);

        // Inject mock builder using reflection
        $reflection = new \ReflectionClass($this->queryBuilder);
        $property = $reflection->getProperty('builder');
        $property->setValue($this->queryBuilder, $this->mockBuilder);
    }

    public function testWhereCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('where')
            ->with('name', '=', 'John');

        $result = $this->queryBuilder->where('name', '=', 'John');

        $this->assertSame($this->queryBuilder, $result);
    }

    public function testOrWhereCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('orWhere')
            ->with('email', '=', 'test@example.com');

        $this->queryBuilder->orWhere('email', '=', 'test@example.com');
    }

    public function testWhereInCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereIn')
            ->with('id', [1, 2, 3]);

        $this->queryBuilder->whereIn('id', [1, 2, 3]);
    }

    public function testWhereNotInCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereNotIn')
            ->with('status', ['deleted']);

        $this->queryBuilder->whereNotIn('status', ['deleted']);
    }

    public function testWhereNullCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereNull')
            ->with('deleted_at');

        $this->queryBuilder->whereNull('deleted_at');
    }

    public function testWhereNotNullCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereNotNull')
            ->with('email_verified_at');

        $this->queryBuilder->whereNotNull('email_verified_at');
    }

    public function testWhereBetweenCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereBetween')
            ->with('age', [18, 65]);

        $this->queryBuilder->whereBetween('age', [18, 65]);
    }

    public function testWhereLikeCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('whereLike')
            ->with('name', '%John%');

        $this->queryBuilder->whereLike('name', '%John%');
    }

    public function testWhereNestedCallsUnderlyingBuilder(): void
    {
        $callback = fn () => null;

        $this->mockBuilder->expects($this->once())
            ->method('whereNested')
            ->with($callback, 'and');

        $this->queryBuilder->whereNested($callback);
    }

    public function testJoinCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('join')
            ->with('posts', 'users.id', '=', 'posts.user_id', JoinType::INNER);

        $this->queryBuilder->join('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testLeftJoinCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('posts', 'users.id', '=', 'posts.user_id');

        $this->queryBuilder->leftJoin('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testRightJoinCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('rightJoin')
            ->with('posts', 'users.id', '=', 'posts.user_id');

        $this->queryBuilder->rightJoin('posts', 'users.id', '=', 'posts.user_id');
    }

    public function testOrderByCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('orderBy')
            ->with('created_at', OrderDirection::DESC);

        $this->queryBuilder->orderBy('created_at', OrderDirection::DESC);
    }

    public function testLatestCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('latest')
            ->with('created_at');

        $this->queryBuilder->latest();
    }

    public function testOldestCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('oldest')
            ->with('updated_at');

        $this->queryBuilder->oldest('updated_at');
    }

    public function testLimitCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('limit')
            ->with(10);

        $this->queryBuilder->limit(10);
    }

    public function testOffsetCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('offset')
            ->with(20);

        $this->queryBuilder->offset(20);
    }

    public function testSelectCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('select')
            ->with(['id', 'name']);

        $this->queryBuilder->select(['id', 'name']);
    }

    public function testGetHydratesResults(): void
    {
        $this->mockBuilder->method('get')->willReturn([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
        ]);

        $results = $this->queryBuilder->get();

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Model::class, $results);
        $this->assertSame('John', $results[0]->name);
        $this->assertSame('Jane', $results[1]->name);
    }

    public function testFirstReturnsHydratedModel(): void
    {
        $this->mockBuilder->method('first')->willReturn(['id' => 1, 'name' => 'John']);

        $result = $this->queryBuilder->first();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertSame('John', $result->name);
    }

    public function testFirstReturnsNullWhenNoResults(): void
    {
        $this->mockBuilder->method('first')->willReturn(null);

        $result = $this->queryBuilder->first();

        $this->assertNull($result);
    }

    public function testCountCallsUnderlyingBuilder(): void
    {
        $this->mockBuilder->expects($this->once())
            ->method('count')
            ->with('*')
            ->willReturn(42);

        $result = $this->queryBuilder->count();

        $this->assertSame(42, $result);
    }
}
