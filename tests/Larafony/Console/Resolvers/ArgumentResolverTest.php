<?php

declare(strict_types=1);

namespace Larafony\Framework\Tests\Larafony\Console\Resolvers;

use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Contracts\OutputContract;
use Larafony\Framework\Console\Input\Input;
use Larafony\Framework\Console\Resolvers\ArgumentResolver;
use Larafony\Framework\Container\Contracts\ContainerContract;
use PHPUnit\Framework\TestCase;

final class ArgumentResolverTest extends TestCase
{
    public function testResolvesArgumentsFromInput(): void
    {
        $container = $this->createStub(ContainerContract::class);
        $command = new class ($this->createStub(OutputContract::class), $container) extends Command {
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
        $container = $this->createStub(ContainerContract::class);
        $command = new class ($this->createStub(OutputContract::class), $container) extends Command {
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
        $container = $this->createStub(ContainerContract::class);
        $command = new class ($this->createStub(OutputContract::class), $container) extends Command {
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
        $container = $this->createStub(ContainerContract::class);
        $command = new class ($this->createStub(OutputContract::class), $container) extends Command {
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

        $this->assertSame(null, $command->optional);
    }
}
