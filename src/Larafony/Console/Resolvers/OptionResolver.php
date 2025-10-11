<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Resolvers;

use Larafony\Framework\Console\Attributes\CommandOption;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Input\Input;

final class OptionResolver
{
    /**
     * @var \ReflectionClass<Command> $reflectionClass
     */
    private \ReflectionClass $reflectionClass;

    public function __construct(private Command $command, private Input $input)
    {
        $this->reflectionClass = new \ReflectionClass($this->command);
    }

    public function resolveOptions(): void
    {
        $properties = $this->reflectionClass->getProperties();
        foreach ($properties as $property) {
            $attribute = $property->getAttributes(CommandOption::class)[0] ?? null;
            if ($attribute === null) {
                continue;
            }
            $attribute = $attribute->newInstance();
            $name = $property->getName();
            $value = $this->input->getOption($name);
            if ($value !== null || $property->getType()?->allowsNull()) {
                $property->setValue($this->command, $value);
                return;
            }
            $attribute->apply($property, $this->command);
        }
    }
}
