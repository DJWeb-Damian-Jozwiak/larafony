<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\Enums\JoinType;
use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough;
use PHPUnit\Framework\TestCase;

class HasManyThroughTest extends TestCase
{
    private Model $parent;

    protected function setUp(): void
    {
        $this->parent = new class extends Model {
            public string $table {
                get => 'countries';
            }
        };

        $this->parent->id = 1;

        // Setup DB facade
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);
    }

    public function testAddConstraintsAddsJoinAndWhereClause(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('join')
            ->with(
                'users',
                'users.id',
                '=',
                'posts.user_id',
                JoinType::INNER
            )
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('users.country_id', '=', 1);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $throughClass = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $relatedClass = new class extends Model {
            public string $table {
                get => 'posts';
            }
        };

        $relation = new HasManyThrough(
            $this->parent,
            $relatedClass::class,
            $throughClass::class,
            'country_id',
            'user_id',
            'id',
            'id'
        );

        $relation->addConstraints();
    }
}
