<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Relations;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\Attributes\BelongsTo;
use Larafony\Framework\Database\ORM\Attributes\BelongsToMany;
use Larafony\Framework\Database\ORM\Attributes\HasMany;
use Larafony\Framework\Database\ORM\Attributes\HasManyThrough;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Database\ORM\Relations\RelationDecorator;
use PHPUnit\Framework\TestCase;

class RelationDecoratorTest extends TestCase
{
    protected function setUp(): void
    {
        // Setup DB facade with mock
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 1, 'title' => 'Test Post'],
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);
    }

    public function testGetRelationInitializesAndReturnsRelation(): void
    {
        // Create a concrete test model class with HasMany attribute
        $model = new class extends Model {
            public string $table {
                get => 'users';
            }

            #[HasMany(RelationDecoratorTestPost::class, 'user_id', 'id')]
            public ?array $posts {
                get => $this->relations->getRelation('posts');
            }
        };

        $model->id = 1;

        // Mock QueryBuilder to return posts data
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 10, 'title' => 'Post 1', 'user_id' => 1],
            ['id' => 11, 'title' => 'Post 2', 'user_id' => 1],
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $decorator = new RelationDecorator($model);
        $result = $decorator->getRelation('posts');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Model::class, $result[0]);
    }

    public function testGetRelationCachesResults(): void
    {
        // Create a concrete test model class with BelongsTo attribute
        $model = new class extends Model {
            public string $table {
                get => 'posts';
            }

            public int|string $user_id {
                get => $this->user_id;
                set {
                    $this->user_id = $value;
                    $this->markPropertyAsChanged('user_id');
                }
            }

            #[BelongsTo(RelationDecoratorTestUser::class, 'user_id', 'id')]
            public ?Model $user {
                get => $this->relations->getRelation('user');
            }
        };

        $model->id = 1;
        $model->user_id = 5;

        // Mock QueryBuilder - should only be called once due to caching
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('get')
            ->willReturn([
                ['id' => 5, 'name' => 'John Doe']
            ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $decorator = new RelationDecorator($model);

        // First call - should query database
        $result1 = $decorator->getRelation('user');
        $this->assertInstanceOf(Model::class, $result1);

        // Second call - should return cached result
        $result2 = $decorator->getRelation('user');
        $this->assertSame($result1, $result2);
    }

    public function testGetRelationInitializesBelongsToManyRelation(): void
    {
        // Create a model with BelongsToMany attribute
        $model = new class extends Model {
            public string $table {
                get => 'users';
            }

            #[BelongsToMany(RelationDecoratorTestRole::class, 'role_user', 'user_id', 'role_id')]
            public ?array $roles {
                get => $this->relations->getRelation('roles');
            }
        };

        $model->id = 1;

        // Mock QueryBuilder for BelongsToMany (uses join)
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Editor'],
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $decorator = new RelationDecorator($model);
        $result = $decorator->getRelation('roles');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Model::class, $result[0]);
    }

    public function testGetRelationInitializesHasManyThroughRelation(): void
    {
        // Create a model with HasManyThrough attribute
        $model = new class extends Model {
            public string $table {
                get => 'countries';
            }

            #[HasManyThrough(
                RelationDecoratorTestPost::class,
                RelationDecoratorTestUser::class,
                'country_id',
                'user_id',
                'id',
                'id'
            )]
            public ?array $posts {
                get => $this->relations->getRelation('posts');
            }
        };

        $model->id = 1;

        // Mock QueryBuilder for HasManyThrough (uses join)
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('join')->willReturnSelf();
        $queryBuilder->method('get')->willReturn([
            ['id' => 1, 'title' => 'Post 1', 'user_id' => 1],
            ['id' => 2, 'title' => 'Post 2', 'user_id' => 2],
        ]);

        $manager = $this->createMock(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $decorator = new RelationDecorator($model);
        $result = $decorator->getRelation('posts');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Model::class, $result[0]);
    }

    public function testGetRelationThrowsExceptionWhenNotFound(): void
    {
        $model = new class extends Model {
            public string $table {
                get => 'users';
            }
        };

        $decorator = new RelationDecorator($model);

        $this->expectException(\ReflectionException::class);

        $decorator->getRelation('non_existent');
    }
}

// Helper test models for RelationDecorator tests
class RelationDecoratorTestPost extends Model
{
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

    public int|string $user_id {
        get => $this->user_id;
        set {
            $this->user_id = $value;
            $this->markPropertyAsChanged('user_id');
        }
    }
}

class RelationDecoratorTestUser extends Model
{
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
}

class RelationDecoratorTestRole extends Model
{
    public string $table {
        get => 'roles';
    }

    public ?string $name {
        get => $this->name;
        set {
            $this->name = $value;
            $this->markPropertyAsChanged('name');
        }
    }
}
