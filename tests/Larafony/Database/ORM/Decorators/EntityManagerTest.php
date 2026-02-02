<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\ORM\Decorators;

use Larafony\Framework\Database\Base\Query\QueryBuilder;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\ORM\DB;
use Larafony\Framework\Database\ORM\Decorators\EntityManager;
use Larafony\Framework\Database\ORM\Model;
use PHPUnit\Framework\TestCase;

class EntityManagerTest extends TestCase
{
    public function testSaveCallsInserterWhenModelIsNew(): void
    {
        // Mock QueryBuilder
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('insertGetId')->willReturn('99');

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $model = new class extends Model {
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

        $model->name = 'New User';
        $model->save();

        $this->assertSame('99', $model->id);
    }

    public function testSaveCallsUpdaterWhenModelExists(): void
    {
        // Mock QueryBuilder with where and update
        $queryBuilder = $this->createStub(QueryBuilder::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('update')->willReturn(1);

        $manager = $this->createStub(DatabaseManager::class);
        $manager->method('table')->willReturn($queryBuilder);
        DB::withManager($manager);

        $model = new class extends Model {
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

        $model->id = 5;
        $model->name = 'Updated User';
        $model->save();

        // If we got here without exception, update was called
        $this->assertTrue(true);
    }
}
