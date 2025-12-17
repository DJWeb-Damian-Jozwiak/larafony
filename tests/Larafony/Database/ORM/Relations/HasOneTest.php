<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\HasOne;
use PHPUnit\Framework\TestCase;

class HasOneTest extends TestCase
{
    private Model $parent;

    protected function setUp(): void
    {
        // Setup DB facade
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->parent = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $this->parent->id = 10;
    }

    public function testAddConstraintsAddsWhereClause(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('user_id', '=', 10);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'profiles';
            }
        };

        $relation = new HasOne($this->parent, $relatedClass::class, 'user_id', 'id');
        $relation->addConstraints();
    }

    public function testGetRelatedReturnsHydratedModel(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('first')->willReturn([
            'id' => 1,
            'bio' => 'Test bio',
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'profiles';
            }

            public ?string $bio {
                get => $this->bio;
                set {
                    $this->bio = $value;
                    $this->markPropertyAsChanged('bio');
                }
            }
        };

        $relation = new HasOne($this->parent, $relatedClass::class, 'user_id', 'id');
        $result = $relation->getRelated();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertSame('Test bio', $result->bio);
    }

    public function testGetRelatedReturnsNullWhenNoResult(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('first')->willReturn(null);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'profiles';
            }
        };

        $relation = new HasOne($this->parent, $relatedClass::class, 'user_id', 'id');
        $result = $relation->getRelated();

        $this->assertNull($result);
    }
}
