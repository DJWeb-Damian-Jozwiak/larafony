<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database;

use Larafony\Framework\Database\Base\Contracts\ConnectionContract;
use Larafony\Framework\Database\Drivers\MySQL\SchemaBuilder;
use Larafony\Framework\Tests\TestCase;
use PDOStatement;

class SchemaBuilderTest extends TestCase
{
    private ConnectionContract $connection;
    private SchemaBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(ConnectionContract::class);
        $this->builder = new SchemaBuilder($this->connection);
    }

    public function testCreateTableWithMultipleColumns(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->string('email');
            $table->integer('age')->nullable(true);
        });

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('id INT(11) NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('name VARCHAR(255) NOT NULL', $sql);
        $this->assertStringContainsString('email VARCHAR(255) NULL', $sql);
        $this->assertStringContainsString('age INT(11) NULL', $sql);
        $this->assertStringContainsString('PRIMARY KEY (id)', $sql);
    }

    public function testCreateTableWithPrimaryKey(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
        });

        $expected = 'CREATE TABLE users (id INT(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (id));';

        $this->assertEquals($expected, $sql);
    }

    public function testCreateTableWithTimestamps(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP', $sql);
        $this->assertStringContainsString('updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', $sql);
    }

    public function testCreateTableWithSoftDeletes(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('deleted_at TIMESTAMP NULL', $sql);
    }

    public function testCreateTableWithAllColumnTypes(): void
    {
        $sql = $this->builder->create('test_table', function ($table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description');
            $table->integer('count');
            $table->bigInteger('big_count');
            $table->date('birth_date');
            $table->datetime('created_datetime');
            $table->timestamp('created_timestamp');
        });

        $this->assertStringContainsString('CREATE TABLE test_table', $sql);
        $this->assertStringContainsString('name VARCHAR(100) NULL', $sql);
        $this->assertStringContainsString('description TEXT NULL', $sql);
        $this->assertStringContainsString('count INT(11) NULL', $sql);
        $this->assertStringContainsString('big_count MEDIUMINT(20) NULL', $sql);
        $this->assertStringContainsString('birth_date DATE NULL', $sql);
        $this->assertStringContainsString('created_datetime DATETIME NULL', $sql);
        $this->assertStringContainsString('created_timestamp TIMESTAMP NULL', $sql);
    }

    public function testCreateTableWithUniqueIndex(): void
    {
        $sql = $this->builder->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->unique('title');
        });

        $this->assertStringContainsString('CREATE TABLE posts', $sql);
        $this->assertStringContainsString('title VARCHAR(255) NULL', $sql);
        $this->assertStringContainsString('UNIQUE KEY posts_title_unique (title)', $sql);
    }

    public function testCreateTableWithDefaultValues(): void
    {
        $sql = $this->builder->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->integer('stock')->default(0);
        });

        $this->assertStringContainsString('CREATE TABLE products', $sql);
        $this->assertStringContainsString('stock INT(11) NULL DEFAULT 0', $sql);
    }

    public function testDropTable(): void
    {
        $sql = $this->builder->drop('users');

        $this->assertEquals('DROP TABLE users', $sql);
    }

    public function testDropIfExists(): void
    {
        $sql = $this->builder->dropIfExists('users');

        $this->assertEquals('DROP TABLE IF EXISTS users', $sql);
    }

    public function testExecuteMethod(): void
    {
        $sql = 'CREATE TABLE test (id INT)';

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($sql);

        $result = $this->builder->execute($sql);

        $this->assertTrue($result);
    }

    public function testCreateAndExecute(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('name');
        });

        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($sql);

        $this->builder->execute($sql);
    }

    public function testMultipleIndexes(): void
    {
        $sql = $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('username');
            $table->unique('email');
            $table->index('username');
        });

        $this->assertStringContainsString('CREATE TABLE users', $sql);
        $this->assertStringContainsString('UNIQUE KEY users_email_unique (email)', $sql);
        $this->assertStringContainsString('KEY users_username_index (username)', $sql);
    }
}
