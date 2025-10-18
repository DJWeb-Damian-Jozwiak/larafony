<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class HasManyTest extends TestCase
{
    private Model $parent;

    protected function setUp(): void
    {
        $this->parent = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $this->parent->id = 10;

        // Setup DB facade
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);
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
                get => 'posts';
            }
        };

        $relation = new HasMany($this->parent, $relatedClass::class, 'user_id', 'id');
        $relation->addConstraints();
    }

    public function testGetRelatedReturnsArrayOfHydratedModels(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 1, 'title' => 'Post 1'],
            ['id' => 2, 'title' => 'Post 2'],
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
            public string $table {
                get => 'posts';
            }

            public ?string $title {
                get => $this->title;
                set {
                    $this->title = $value;
                    $this->markPropertyAsChanged('title');
                }
            }
        };

        $relation = new HasMany($this->parent, $relatedClass::class, 'user_id', 'id');
        $results = $relation->getRelated();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(Model::class, $results);
        $this->assertSame('Post 1', $results[0]->title);
        $this->assertSame('Post 2', $results[1]->title);
    }
}
