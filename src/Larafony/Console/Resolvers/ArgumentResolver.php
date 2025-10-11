<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Resolvers;

use Larafony\Framework\Console\Attributes\CommandArgument;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Input\Input;

final class ArgumentResolver
{
    /**
     * @var \ReflectionClass<Command> $reflectionClass
     */
    private \ReflectionClass $reflectionClass;

    public function __construct(private Command $command, private Input $input)
    {
        $this->reflectionClass = new \ReflectionClass($this->command);
    }
    public function resolveArguments(): void
    {
        $properties = $this->reflectionClass->getProperties();
        $index = 0;
        foreach ($properties as $property) {
            $attribute = $property->getAttributes(CommandArgument::class)[0] ?? null;
            if ($attribute === null) {
                continue;
            }
            $attribute = $attribute->newInstance();
            $name = $property->getName();
            $value = $this->input->getArgument($index);
            if ($value !== null || $property->getType()?->allowsNull()) {
                $property->setValue($this->command, $value);
            }
            $attribute->apply($property, $this->command);
            $index++;
        }
    }
}
