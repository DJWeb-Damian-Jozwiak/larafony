<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Schema\IndexDefinitions\PrimaryIndex;
use Larafony\Framework\Tests\TestCase;

class PrimaryIndexTest extends TestCase
{
    public function testCreatesPrimaryIndex(): void
    {
        $index = new PrimaryIndex('users', 'id');

        $this->assertEquals('users', $index->tableName);
        $this->assertEquals('id', $index->columns);
    }

    public function testGeneratesSqlDefinition(): void
    {
        $index = new PrimaryIndex('posts', 'id');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('ALTER TABLE posts', $sql);
        $this->assertStringContainsString('ADD PRIMARY KEY', $sql);
        $this->assertStringContainsString('id', $sql);
    }

    public function testSupportsCompositeKey(): void
    {
        $index = new PrimaryIndex('user_roles', 'user_id, role_id');
        $sql = $index->getSqlDefinition();

        $this->assertStringContainsString('ALTER TABLE user_roles', $sql);
        $this->assertStringContainsString('ADD PRIMARY KEY', $sql);
        $this->assertStringContainsString('user_id, role_id', $sql);
    }
}
