<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console;

use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\CommandRegistry;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Exceptions\CommandNotFoundError;
use PHPUnit\Framework\TestCase;

final class CommandRegistryTest extends TestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new CommandRegistry();
    }

    public function testRegistersCommand(): void
    {
        $this->registry->register('test', TestCommand::class);

        $this->assertTrue($this->registry->has('test'));
    }

    public function testGetsRegisteredCommand(): void
    {
        $this->registry->register('test', TestCommand::class);

        $commandClass = $this->registry->get('test');

        $this->assertSame(TestCommand::class, $commandClass);
    }

    public function testThrowsExceptionWhenCommandNotFound(): void
    {
        $this->expectException(CommandNotFoundError::class);
        $this->expectExceptionMessage("Command 'nonexistent' not found.");

        $this->registry->get('nonexistent');
    }

    public function testHasReturnsFalseForNonExistentCommand(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function testReturnsAllRegisteredCommands(): void
    {
        $this->registry->register('test1', TestCommand::class);
        $this->registry->register('test2', TestCommand::class);

        $commands = $this->registry->all();

        $this->assertCount(2, $commands);
        $this->assertArrayHasKey('test1', $commands);
        $this->assertArrayHasKey('test2', $commands);
    }

    public function testReturnsEmptyArrayWhenNoCommandsRegistered(): void
    {
        $commands = $this->registry->all();

        $this->assertEmpty($commands);
    }
}

class TestCommand extends Command
{
    public function run(): int
    {
        return 0;
    }
}
