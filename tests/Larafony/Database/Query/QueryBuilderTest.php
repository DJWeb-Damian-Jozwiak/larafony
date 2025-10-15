<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\Query;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Base\Query\Enums\OrderDirection;
use Larafony\Framework\Database\Drivers\MySQL\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{
    private ConnectionContract $connection;
    private QueryBuilder $builder;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(ConnectionContract::class);
        $this->builder = new QueryBuilder($this->connection);
    }

    public function testBuildsBasicSelectQuery(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['id', 'name', 'email'])
            ->toSql();

        $this->assertSame('SELECT id, name, email FROM users', $sql);
    }

    public function testBuildsSelectWithWhere(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ?', $sql);
    }

    public function testBuildsSelectWithMultipleWhere(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->where('age', '>', 18)
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ? and age > ?', $sql);
    }

    public function testBuildsSelectWithOrWhere(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->orWhere('verified', '=', true)
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ? or verified = ?', $sql);
    }

    public function testBuildsSelectWithNestedWhere(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->whereNested(function (QueryBuilder $q) {
                $q->where('age', '>', 18)
                  ->orWhere('verified', '=', true);
            }, 'and')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ? and (age > ? or verified = ?)', $sql);
    }

    public function testBuildsSelectWithWhereIn(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereIn('id', [1, 2, 3])
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE id IN (?, ?, ?)', $sql);
    }

    public function testBuildsSelectWithWhereNull(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereNull('deleted_at')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE deleted_at IS NULL', $sql);
    }

    public function testBuildsSelectWithWhereNotNull(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereNotNull('email_verified_at')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE email_verified_at IS NOT NULL', $sql);
    }

    public function testBuildsSelectWithWhereBetween(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereBetween('age', [18, 65])
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE age BETWEEN ? AND ?', $sql);
    }

    public function testBuildsSelectWithWhereLike(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereLike('name', '%John%')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE name LIKE ?', $sql);
    }

    public function testBuildsSelectWithJoin(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['users.*', 'profiles.bio'])
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->toSql();

        $this->assertSame('SELECT users.*, profiles.bio FROM users LEFT JOIN profiles ON users.id = profiles.user_id', $sql);
    }

    public function testBuildsSelectWithOrderBy(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->orderBy('created_at', OrderDirection::DESC)
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY `created_at` DESC', $sql);
    }

    public function testBuildsSelectWithLatest(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->latest()
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY `created_at` DESC', $sql);
    }

    public function testBuildsSelectWithLimitAndOffset(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->limit(10)
            ->offset(20)
            ->toSql();

        $this->assertSame('SELECT * FROM users LIMIT 10 OFFSET 20', $sql);
    }

    public function testBuildsComplexSelectQuery(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['users.id', 'users.name', 'profiles.bio'])
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->where('users.status', '=', 'active')
            ->whereNested(function (QueryBuilder $q) {
                $q->where('users.age', '>', 18)
                  ->orWhere('users.verified', '=', true);
            }, 'and')
            ->whereNotNull('users.email_verified_at')
            ->orderBy('users.created_at', OrderDirection::DESC)
            ->limit(10)
            ->offset(20)
            ->toSql();

        $expected = 'SELECT users.id, users.name, profiles.bio FROM users ' .
                    'LEFT JOIN profiles ON users.id = profiles.user_id ' .
                    'WHERE users.status = ? and (users.age > ? or users.verified = ?) and users.email_verified_at IS NOT NULL ' .
                    'ORDER BY `users.created_at` DESC LIMIT 10 OFFSET 20';

        $this->assertSame($expected, $sql);
    }

    public function testBuildsInsertQuery(): void
    {
        $builder = $this->builder->table('users');

        // Create a new QueryBuilder to access internal state for testing
        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['name' => 'John', 'email' => 'john@example.com'];

        $grammarProperty = $reflection->getProperty('grammar');
        $grammar = $grammarProperty->getValue($builder);

        $sql = $grammar->compileInsert($query);

        $this->assertSame('INSERT INTO users (name, email) VALUES (?, ?)', $sql);
    }

    public function testBuildsUpdateQuerySql(): void
    {
        $builder = $this->builder
            ->table('users')
            ->where('id', '=', 1);

        // Access internal state for testing SQL generation
        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['status' => 'inactive'];
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::UPDATE;

        $grammarProperty = $reflection->getProperty('grammar');
        $grammar = $grammarProperty->getValue($builder);

        $sql = $grammar->compileUpdate($query);

        $this->assertSame('UPDATE users SET status = ? WHERE id = ?', $sql);
    }

    public function testBuildsDeleteQuerySql(): void
    {
        $builder = $this->builder
            ->table('users')
            ->where('status', '=', 'deleted');

        // Access internal state for testing SQL generation
        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::DELETE;

        $grammarProperty = $reflection->getProperty('grammar');
        $grammar = $grammarProperty->getValue($builder);

        $sql = $grammar->compileDelete($query);

        $this->assertSame('DELETE FROM users WHERE status = ?', $sql);
    }

    public function testCollectsBindingsCorrectly(): void
    {
        $builder = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->where('age', '>', 18)
            ->whereIn('role', ['admin', 'editor']);

        // Access internal state for testing
        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);

        $bindings = $query->getBindings();

        $this->assertSame(['active', 18, 'admin', 'editor'], $bindings);
    }

    public function testWhereWithTwoArguments(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ?', $sql);
    }

    public function testWhereNotIn(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereNotIn('id', [1, 2, 3])
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE id NOT IN (?, ?, ?)', $sql);
    }

    public function testOrWhereWithClosure(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=','active')
            ->whereNested(function (QueryBuilder $q) {
                $q->where('role', '=','admin')
                  ->where('verified','=',true);
            }, 'or')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ? or (role = ? and verified = ?)', $sql);
    }

    public function testOrWhereWithTwoArguments(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=','active')
            ->orWhere('role','=', 'admin')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ? or role = ?', $sql);
    }

    public function testOldest(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->oldest('created_at')
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY `created_at` ASC', $sql);
    }

    public function testMultipleOrderBy(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->orderBy('name', OrderDirection::ASC)
            ->orderBy('created_at', OrderDirection::DESC)
            ->toSql();

        $this->assertSame('SELECT * FROM users ORDER BY `name` ASC, `created_at` DESC', $sql);
    }

    public function testRightJoin(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->rightJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->toSql();

        $this->assertSame('SELECT * FROM users RIGHT JOIN profiles ON users.id = profiles.user_id', $sql);
    }

    public function testInnerJoin(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->join('profiles', 'users.id', '=', 'profiles.user_id')
            ->toSql();

        $this->assertSame('SELECT * FROM users INNER JOIN profiles ON users.id = profiles.user_id', $sql);
    }

    public function testJoinWithClosure(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->leftJoin('profiles', function ($join) {
                $join->on('users.id', '=', 'profiles.user_id')
                     ->on('users.tenant_id', '=', 'profiles.tenant_id');
            })
            ->toSql();

        $this->assertSame('SELECT * FROM users LEFT JOIN profiles ON users.id = profiles.user_id and users.tenant_id = profiles.tenant_id', $sql);
    }

    public function testSelectWithVariadicArguments(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['id', 'name', 'email'])
            ->toSql();

        $this->assertSame('SELECT id, name, email FROM users', $sql);
    }

    public function testOffsetWithoutLimit(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->offset(10)
            ->toSql();

        $this->assertSame('SELECT * FROM users OFFSET 10', $sql);
    }

    public function testLimitOverwritesPreviousLimit(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->limit(10)
            ->limit(5)
            ->toSql();

        $this->assertSame('SELECT * FROM users LIMIT 5', $sql);
    }

    public function testOffsetOverwritesPreviousOffset(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->offset(20)
            ->offset(10)
            ->toSql();

        $this->assertSame('SELECT * FROM users OFFSET 10', $sql);
    }

    public function testEmptyNestedWhereIsIgnored(): void
    {
        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->whereNested(function (QueryBuilder $q) {
                // Empty closure
            }, 'and')
            ->toSql();

        $this->assertSame('SELECT * FROM users WHERE status = ?', $sql);
    }

    public function testUpdateBindingsIncludeWhereBindings(): void
    {
        $builder = $this->builder
            ->table('users')
            ->where('id', '=', 5)
            ->where('status', '=', 'active');

        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['name' => 'Updated'];
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::UPDATE;

        $bindings = $query->getBindings();

        // UPDATE bindings come first, then WHERE bindings
        $this->assertSame(['Updated', 5, 'active'], $bindings);
    }

    public function testToSqlWithInsertType(): void
    {
        $builder = $this->builder->table('users');

        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['name' => 'John'];
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::INSERT;

        $sql = $builder->toSql();

        $this->assertSame('INSERT INTO users (name) VALUES (?)', $sql);
    }

    public function testToSqlWithUpdateType(): void
    {
        $builder = $this->builder
            ->table('users')
            ->where('id', '=', 1);

        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['status' => 'inactive'];
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::UPDATE;

        $sql = $builder->toSql();

        $this->assertSame('UPDATE users SET status = ? WHERE id = ?', $sql);
    }

    public function testToSqlWithDeleteType(): void
    {
        $builder = $this->builder
            ->table('users')
            ->where('status', '=', 'deleted');

        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::DELETE;

        $sql = $builder->toSql();

        $this->assertSame('DELETE FROM users WHERE status = ?', $sql);
    }

    public function testGet(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM users WHERE status = ?', ['active'])
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->get();

        $this->assertCount(2, $result);
        $this->assertSame('John', $result[0]['name']);
    }

    public function testFirst(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([['id' => 1, 'name' => 'John']]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM users WHERE status = ? LIMIT 1', ['active'])
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=','active')
            ->first();

        $this->assertIsArray($result);
        $this->assertSame('John', $result['name']);
    }

    public function testFirstReturnsNullWhenNoResults(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=','inactive')
            ->first();

        $this->assertNull($result);
    }

    public function testCount(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([['aggregate' => '42']]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(*) as aggregate FROM users WHERE status = ? LIMIT 1', ['active'])
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->where('status', '=','active')
            ->count();

        $this->assertSame(42, $result);
    }

    public function testCountWithColumn(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn([['aggregate' => '10']]);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with('SELECT COUNT(id) as aggregate FROM users LIMIT 1', [])
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->count('id');

        $this->assertSame(10, $result);
    }

    public function testInsert(): void
    {
        $statement = $this->createMock(\PDOStatement::class);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with(
                'INSERT INTO users (name, email) VALUES (?, ?)',
                ['John Doe', 'john@example.com']
            )
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->insert(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertTrue($result);
    }

    public function testInsertGetId(): void
    {
        $statement = $this->createMock(\PDOStatement::class);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->willReturn($statement);

        $this->connection
            ->expects($this->once())
            ->method('getLastInsertId')
            ->willReturn('123');

        $result = $this->builder
            ->table('users')
            ->insertGetId(['name' => 'John Doe', 'email' => 'john@example.com']);

        $this->assertSame('123', $result);
    }

    public function testUpdate(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(3);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with(
                'UPDATE users SET status = ? WHERE role = ?',
                ['inactive', 'admin']
            )
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->where('role', '=','admin')
            ->update(['status' => 'inactive']);

        $this->assertSame(3, $result);
    }

    public function testDelete(): void
    {
        $statement = $this->createMock(\PDOStatement::class);
        $statement->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with(
                'DELETE FROM users WHERE status = ?',
                ['deleted']
            )
            ->willReturn($statement);

        $result = $this->builder
            ->table('users')
            ->where('status', '=','deleted')
            ->delete();

        $this->assertSame(5, $result);
    }

    public function testToRawSqlWithStringValues(): void
    {
        $this->connection
            ->expects($this->exactly(2))
            ->method('quote')
            ->willReturnCallback(fn ($value) => "'" . addslashes((string) $value) . "'");

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('name', '=', 'John Doe')
            ->where('email', '=', 'john@example.com')
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE name = 'John Doe' and email = 'john@example.com'", $sql);
    }

    public function testToRawSqlWithSqlInjectionAttempt(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('quote')
            ->willReturnCallback(fn ($value) => "'" . addslashes((string) $value) . "'");

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('name', '=', "Robert'; DROP TABLE users; --")
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE name = 'Robert\\'; DROP TABLE users; --'", $sql);
    }

    public function testToRawSqlWithMixedTypes(): void
    {
        $callCount = 0;
        $this->connection
            ->expects($this->exactly(3))
            ->method('quote')
            ->willReturnCallback(function ($value) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) { // 'John'
                    return "'John'";
                }
                if ($callCount === 2) { // 25
                    return '25';
                }
                // true -> 1
                return '1';
            });

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('name', '=', 'John')
            ->where('age', '>', 25)
            ->where('active', '=', true)
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE name = 'John' and age > 25 and active = 1", $sql);
    }

    public function testToRawSqlWithNull(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('quote')
            ->with(null)
            ->willReturn('NULL');

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('deleted_at', '=', null)
            ->toRawSql();

        $this->assertSame('SELECT * FROM users WHERE deleted_at = NULL', $sql);
    }

    public function testToRawSqlWithWhereIn(): void
    {
        $this->connection
            ->expects($this->exactly(3))
            ->method('quote')
            ->willReturnCallback(fn ($value) => "'" . addslashes((string) $value) . "'");

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->whereIn('status', ['active', 'pending', 'verified'])
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE status IN ('active', 'pending', 'verified')", $sql);
    }

    public function testToRawSqlWithComplexQuery(): void
    {
        $this->connection
            ->expects($this->exactly(3))
            ->method('quote')
            ->willReturnCallback(fn ($value) => "'" . addslashes((string) $value) . "'");

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('status', '=', 'active')
            ->whereNested(function (QueryBuilder $q) {
                $q->where('role', '=', 'admin')
                  ->orWhere('role', '=', 'moderator');
            }, 'and')
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE status = 'active' and (role = 'admin' or role = 'moderator')", $sql);
    }

    public function testToRawSqlWithUpdateQuery(): void
    {
        $callCount = 0;
        $this->connection
            ->expects($this->exactly(2))
            ->method('quote')
            ->willReturnCallback(function ($value) use (&$callCount) {
                $callCount++;
                if ($callCount === 1) { // 'Updated Name'
                    return "'Updated Name'";
                }
                // 5 (integer)
                return '5';
            });

        $builder = $this->builder
            ->table('users')
            ->where('id', '=', 5);

        $reflection = new \ReflectionClass($builder);
        $queryProperty = $reflection->getProperty('query');
        $query = $queryProperty->getValue($builder);
        $query->values = ['name' => 'Updated Name'];
        $query->type = \Larafony\Framework\Database\Base\Query\Enums\QueryType::UPDATE;

        $sql = $builder->toRawSql();

        $this->assertSame("UPDATE users SET name = 'Updated Name' WHERE id = 5", $sql);
    }

    public function testToRawSqlWithQuotesInString(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('quote')
            ->willReturnCallback(fn ($value) => "'" . addslashes((string) $value) . "'");

        $sql = $this->builder
            ->table('users')
            ->select(['*'])
            ->where('name', '=', "O'Reilly")
            ->toRawSql();

        $this->assertSame("SELECT * FROM users WHERE name = 'O\\'Reilly'", $sql);
    }
}
