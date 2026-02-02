<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class BelongsToTest extends TestCase
{
    private Model $parent;
    private BelongsTo $relation;

    protected function setUp(): void
    {
        $this->parent = new class extends Model {
            public string $table {
                get => 'posts';
            }

            public ?int $user_id {
                get => $this->user_id;
                set {
                    $this->user_id = $value;
                    $this->markPropertyAsChanged('user_id');
                }
            }
        };

        $this->parent->user_id = 5;

        $relatedClass = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        // Setup DB facade
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->relation = new BelongsTo(
            $this->parent,
            $relatedClass::class,
            'user_id',
            'id'
        );
    }

    public function testAddConstraintsAddsWhereClause(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('id', '=', 5);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relation = new BelongsTo($this->parent, get_class($this->parent), 'user_id', 'id');
        $relation->addConstraints();
    }

    public function testGetRelatedReturnsHydratedModel(): void
    {
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 5, 'name' => 'John Doe'],
        ]);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $relatedClass = new class extends Model {
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

        $relation = new BelongsTo($this->parent, $relatedClass::class, 'user_id', 'id');
        $result = $relation->getRelated();

        $this->assertInstanceOf(Model::class, $result);
        $this->assertSame('John Doe', $result->name);
    }
}
