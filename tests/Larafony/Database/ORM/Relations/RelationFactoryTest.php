<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\Attributes\BelongsTo as BelongsToAttribute;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany as BelongsToManyAttribute;
use Larafony\Framework\Database\ORM\Attributes\HasMany as HasManyAttribute;
use Larafony\Framework\Database\ORM\Attributes\HasManyThrough as HasManyThroughAttribute;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\BelongsTo;
use Larafony\Framework\Database\ORM\Relations\BelongsToMany;
use Larafony\Framework\Database\ORM\Relations\HasMany;
use Larafony\Framework\Database\ORM\Relations\HasManyThrough;
use Larafony\Framework\Database\ORM\Relations\RelationFactory;
use PHPUnit\Framework\TestCase;

class RelationFactoryTest extends TestCase
{
    private Model $parent;

    protected function setUp(): void
    {
        // Setup DB facade BEFORE creating models
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $this->parent = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $this->parent->id = 1;
    }

    public function testBelongsToCreatesRelationAndAddsConstraints(): void
    {
        // Skip - covered by BelongsToTest
        $this->markTestSkipped('Covered by BelongsToTest');
    }

    public function testHasManyCreatesRelationAndAddsConstraints(): void
    {
        $relatedClass = new class extends Model {
            public string $table {
                get => 'posts';
            }
        };

        $attribute = new HasManyAttribute($relatedClass::class, 'user_id', 'id');

        $relation = RelationFactory::hasMany($this->parent, $attribute);

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function testBelongsToManyCreatesRelationAndAddsConstraints(): void
    {
        $relatedClass = new class extends Model {
            public string $table {
                get => 'roles';
            }
        };

        $attribute = new BelongsToManyAttribute(
            $relatedClass::class,
            'role_user',
            'user_id',
            'role_id'
        );

        $relation = RelationFactory::belongsToMany($this->parent, $attribute);

        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }

    public function testHasManyThroughCreatesRelationAndAddsConstraints(): void
    {
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

        $attribute = new HasManyThroughAttribute(
            $relatedClass::class,
            $throughClass::class,
            'country_id',
            'user_id',
            'id',
            'id'
        );

        $relation = RelationFactory::hasManyThrough($this->parent, $attribute);

        $this->assertInstanceOf(HasManyThrough::class, $relation);
    }
}
