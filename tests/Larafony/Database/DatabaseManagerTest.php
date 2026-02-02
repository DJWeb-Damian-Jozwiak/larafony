<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Drivers\MySQL\QueryBuilder;
use PHPUnit\Framework\TestCase;

class DatabaseManagerTest extends TestCase
{
    private DatabaseManager $manager;
    private ConnectionContract $mockConnection;

    protected function setUp(): void
    {
        $this->mockConnection = $this->createStub(ConnectionContract::class);
        // connect() returns void, don't specify willReturn

        // Create a testable DatabaseManager with mocked connection
        $this->manager = new class ([
            'mysql' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'test',
                'username' => 'root',
                'password' => '',
            ],
        ]) extends DatabaseManager {
            public ?ConnectionContract $testConnection = null;

            protected function makeConnection(string $name): ConnectionContract
            {
                // Return test connection if set, otherwise create real one
                if ($this->testConnection !== null) {
                    return $this->testConnection;
                }
                return parent::makeConnection($name);
            }
        };

        $this->manager->testConnection = $this->mockConnection;
    }

    public function testTableReturnsQueryBuilder(): void
    {
        $queryBuilder = $this->manager->table('users');

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    public function testTableBuildsCorrectQuery(): void
    {
        $sql = $this->manager
            ->table('users')
            ->select(['id', 'name'])
            ->where('status', '=', 'active')
            ->toSql();

        $this->assertSame('SELECT id, name FROM users WHERE status = ?', $sql);
    }

    public function testTableWithDifferentConnection(): void
    {
        $mockConnection2 = $this->createStub(ConnectionContract::class);

        $manager = new class ([
            'mysql' => ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'db1'],
            'mysql2' => ['driver' => 'mysql', 'host' => 'localhost', 'database' => 'db2'],
        ]) extends DatabaseManager {
            public ?ConnectionContract $testConnection = null;
            public ?ConnectionContract $testConnection2 = null;

            protected function makeConnection(string $name): ConnectionContract
            {
                if ($name === 'mysql2' && $this->testConnection2 !== null) {
                    return $this->testConnection2;
                }
                if ($this->testConnection !== null) {
                    return $this->testConnection;
                }
                return parent::makeConnection($name);
            }
        };

        $manager->testConnection = $this->mockConnection;
        $manager->testConnection2 = $mockConnection2;

        $queryBuilder = $manager->table('users', 'mysql2');

        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    public function testTableCachesQueryBuilder(): void
    {
        $builder1 = $this->manager->table('users');
        $sql1 = $builder1->toSql();

        $builder2 = $this->manager->table('posts');
        $sql2 = $builder2->toSql();

        // Check that different tables produce different SQL
        $this->assertSame('SELECT * FROM users', $sql1);
        $this->assertSame('SELECT * FROM posts', $sql2);

        // Both calls return QueryBuilder instances
        $this->assertInstanceOf(QueryBuilder::class, $builder1);
        $this->assertInstanceOf(QueryBuilder::class, $builder2);
    }

    public function testFluentQueryChaining(): void
    {
        $sql = $this->manager
            ->table('users')
            ->select(['users.id', 'users.name', 'profiles.bio'])
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->where('users.status',  '=', 'active')
            ->whereNested(function ($q) {
                $q->where('users.age', '>', 18)
                  ->orWhere('users.verified', '=', 1);
            }, 'and')
            ->orderBy('users.created_at', OrderDirection::DESC)
            ->limit(10)
            ->offset(20)
            ->toSql();

        $expected = 'SELECT users.id, users.name, profiles.bio FROM users ' .
                    'LEFT JOIN profiles ON users.id = profiles.user_id ' .
                    'WHERE users.status = ? and (users.age > ? or users.verified = ?) ' .
                    'ORDER BY `users.created_at` DESC LIMIT 10 OFFSET 20';

        $this->assertSame($expected, $sql);
    }

    public function testMultipleTablesCreatesDifferentQueryBuilders(): void
    {
        // Each call to table() creates a NEW QueryBuilder instance
        $builder1 = $this->manager->table('users');
        $builder2 = $this->manager->table('posts');

        // They should be different instances
        $this->assertNotSame($builder1, $builder2);

        // But both are QueryBuilder instances
        $this->assertInstanceOf(QueryBuilder::class, $builder1);
        $this->assertInstanceOf(QueryBuilder::class, $builder2);

        // And they maintain their own state
        $this->assertSame('SELECT * FROM users', $builder1->toSql());
        $this->assertSame('SELECT * FROM posts', $builder2->toSql());
    }

    public function testQueryBuildersDoNotShareState(): void
    {
        // Create two builders for different tables
        $users = $this->manager->table('users')->where('status', '=', 'active');
        $posts = $this->manager->table('posts')->where('published', '=', true);

        // Each should have its own WHERE conditions
        $usersSql = $users->toSql();
        $postsSql = $posts->toSql();

        $this->assertStringContainsString('users', $usersSql);
        $this->assertStringContainsString('status', $usersSql);

        $this->assertStringContainsString('posts', $postsSql);
        $this->assertStringContainsString('published', $postsSql);

        // Users query should NOT contain posts table
        $this->assertStringNotContainsString('posts', $usersSql);
        // Posts query should NOT contain users table
        $this->assertStringNotContainsString('users', $postsSql);
    }

    public function testSchemaBuilderStillWorks(): void
    {
        $schema = $this->manager->schema();

        $this->assertInstanceOf(\Larafony\Framework\Database\Drivers\MySQL\SchemaBuilder::class, $schema);
    }

    public function testConnectionStillWorks(): void
    {
        $connection = $this->manager->connection();

        $this->assertInstanceOf(ConnectionContract::class, $connection);
        $this->assertSame($this->mockConnection, $connection);
    }
}
