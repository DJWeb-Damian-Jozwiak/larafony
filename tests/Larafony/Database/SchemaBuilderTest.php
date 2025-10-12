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

    public function testCreateTableExecutesSql(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('CREATE TABLE users'));

        $this->builder->create('users', function ($table) {
            $table->id();
            $table->string('name');
        });
    }

    public function testCreateTableWithPrimaryKey(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('CREATE TABLE users'));

        $this->builder->create('users', function ($table) {
            $table->id();
        });
    }

    public function testCreateTableWithTimestamps(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('CREATE TABLE users'));

        $this->builder->create('users', function ($table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function testCreateTableWithSoftDeletes(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('CREATE TABLE users'));

        $this->builder->create('users', function ($table) {
            $table->id();
            $table->softDeletes();
        });
    }

    public function testDropTableExecutesSql(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DROP TABLE users'));

        $this->builder->drop('users');
    }

    public function testDropIfExistsExecutesSql(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('DROP TABLE users'));

        $this->builder->dropIfExists('users');
    }
}
