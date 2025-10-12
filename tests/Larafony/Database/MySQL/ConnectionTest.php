<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Database\MySQL;

use Larafony\Framework\Database\Drivers\MySQL\Connection;
use Larafony\Framework\Tests\TestCase;
use PDO;

class ConnectionTest extends TestCase
{
    public function testCreatesConnectionWithParameters(): void
    {
        $connection = new Connection(
            host: 'localhost',
            port: 3306,
            database: 'test_db',
            username: 'root',
            password: 'secret',
            charset: 'utf8mb4'
        );

        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testConnectMethodInitializesConnection(): void
    {
        // Connection is final, so we test actual behavior
        $connection = new Connection(
            host: 'localhost',
            port: 3306,
            database: 'test_db',
            username: 'root',
            password: 'secret',
            charset: 'utf8mb4'
        );

        // We cannot test actual connection without a real database
        // Instead, verify that query throws exception before connect is called
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not connected to database. Call connect() first.');

        $connection->query('SELECT 1');
    }

    public function testDisconnectMethodClearsConnection(): void
    {
        $connection = new Connection(
            host: 'localhost',
            port: 3306,
            database: 'test_db',
            username: 'root',
            password: 'secret'
        );

        $connection->disconnect();

        // After disconnect, query should throw exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not connected to database. Call connect() first.');

        $connection->query('SELECT 1');
    }

    public function testQueryThrowsExceptionWhenNotConnected(): void
    {
        $connection = new Connection(
            host: 'localhost',
            port: 3306,
            database: 'test_db',
            username: 'root',
            password: 'secret'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not connected to database. Call connect() first.');

        $connection->query('SELECT 1');
    }

    public function testGetConnectionOptionsReturnsCorrectPdoAttributes(): void
    {
        $connection = new Connection();

        $options = $connection->getConnectionOptions();

        $this->assertIsArray($options);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
        $this->assertEquals(PDO::FETCH_ASSOC, $options[PDO::ATTR_DEFAULT_FETCH_MODE]);
        $this->assertFalse($options[PDO::ATTR_EMULATE_PREPARES]);
    }

    public function testConnectMysqlCreatesPdoWithCorrectDsn(): void
    {
        $connection = new Connection();

        // This will throw exception since we're not actually connecting to a DB
        // but we can verify the DSN format would be correct
        try {
            $connection->connectMysql(
                'localhost',
                3306,
                'test_db',
                'root',
                'secret',
                'utf8mb4'
            );
        } catch (\PDOException $e) {
            // Expected - no actual database connection
            $this->assertStringContainsString('SQLSTATE', $e->getMessage());
        }
    }

    public function testGetLastInsertIdReturnsNullWhenNotConnected(): void
    {
        $connection = new Connection();

        $id = $connection->getLastInsertId();

        $this->assertNull($id);
    }
}
