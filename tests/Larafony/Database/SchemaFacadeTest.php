<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database;

use Larafony\Framework\Database\DatabaseManager;
use Larafony\Framework\Database\Drivers\MySQL\SchemaBuilder;
use Larafony\Framework\Database\Schema;
use Larafony\Framework\Tests\TestCase;

class SchemaFacadeTest extends TestCase
{
    private DatabaseManager $manager;
    private SchemaBuilder $schemaBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(DatabaseManager::class);
        $this->schemaBuilder = $this->createMock(SchemaBuilder::class);

        $this->manager
            ->method('schema')
            ->willReturn($this->schemaBuilder);

        Schema::withManager($this->manager);
    }

    public function testCreateDelegatesToSchemaBuilder(): void
    {
        $this->schemaBuilder
            ->expects($this->once())
            ->method('create')
            ->with('users', $this->isInstanceOf(\Closure::class));

        $sql = Schema::create('users', function ($table) {
            $table->id();
        });
        Schema::execute($sql);
    }

    public function testTableDelegatesToSchemaBuilder(): void
    {
        $this->schemaBuilder
            ->expects($this->once())
            ->method('table')
            ->with('users', $this->isInstanceOf(\Closure::class));

        $sql = Schema::table('users', function ($table) {
            $table->string('new_column');
        });

        Schema::execute($sql);
    }

    public function testDropDelegatesToSchemaBuilder(): void
    {
        $this->schemaBuilder
            ->expects($this->once())
            ->method('drop')
            ->with('users');

        $sql = Schema::drop('users');

        Schema::execute($sql);
    }

    public function testDropIfExistsDelegatesToSchemaBuilder(): void
    {
        $this->schemaBuilder
            ->expects($this->once())
            ->method('dropIfExists')
            ->with('users');

        $sql = Schema::dropIfExists('users');

        Schema::execute($sql);
    }

    public function testGetColumnListingDelegatesToSchemaBuilder(): void
    {
        $this->schemaBuilder
            ->expects($this->once())
            ->method('getColumnListing')
            ->with('users')
            ->willReturn(['id', 'name', 'email']);

        $result = Schema::getColumnListing('users');

        $this->assertEquals(['id', 'name', 'email'], $result);
    }

    public function testThrowsExceptionWhenManagerNotSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database manager not set');

        // Create new Schema instance without manager
        $reflection = new \ReflectionClass(Schema::class);
        $property = $reflection->getProperty('manager');
        $property->setValue(null, null);

        $sql = Schema::create('users', function ($table) {
            $table->id();
        });

        Schema::execute($sql);
    }
}
