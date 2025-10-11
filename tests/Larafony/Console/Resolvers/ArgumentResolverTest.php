<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Resolvers;

use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Input\Input;
use Larafony\Framework\Console\Resolvers\ArgumentResolver;
use PHPUnit\Framework\TestCase;

final class ArgumentResolverTest extends TestCase
{
    public function testResolvesArgumentsFromInput(): void
    {
        $command = new class ($this->createMock(OutputContract::class)) extends Command {
            #[CommandArgument(name: 'name')]
            public string $name;

            public function run(): int
            {
                return 0;
            }
        };

        $input = new Input('test', ['Alice']);
        $resolver = new ArgumentResolver($command, $input);

        $resolver->resolveArguments();

        $this->assertSame('Alice', $command->name);
    }

    public function testResolvesMultipleArguments(): void
    {
        $command = new class ($this->createMock(OutputContract::class)) extends Command {
            #[CommandArgument(name: 'first')]
            public string $first;

            #[CommandArgument(name: 'second')]
            public string $second;

            public function run(): int
            {
                return 0;
            }
        };

        $input = new Input('test', ['Alice', 'Bob']);
        $resolver = new ArgumentResolver($command, $input);

        $resolver->resolveArguments();

        $this->assertSame('Alice', $command->first);
        $this->assertSame('Bob', $command->second);
    }

    public function testSkipsPropertiesWithoutAttribute(): void
    {
        $command = new class ($this->createMock(OutputContract::class)) extends Command {
            #[CommandArgument(name: 'name')]
            public string $name;

            public string $noAttribute = 'default';

            public function run(): int
            {
                return 0;
            }
        };

        $input = new Input('test', ['Alice']);
        $resolver = new ArgumentResolver($command, $input);

        $resolver->resolveArguments();

        $this->assertSame('Alice', $command->name);
        $this->assertSame('default', $command->noAttribute);
    }

    public function testHandlesNullableArguments(): void
    {
        $command = new class ($this->createMock(OutputContract::class)) extends Command {
            #[CommandArgument(name: 'optional')]
            public ?string $optional = null;

            public function run(): int
            {
                return 0;
            }
        };

        $input = new Input('test');
        $resolver = new ArgumentResolver($command, $input);

        $resolver->resolveArguments();

        // Attribute's apply() sets empty string when no value provided
        $this->assertSame('', $command->optional);
    }
}
