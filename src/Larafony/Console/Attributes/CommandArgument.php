<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Attributes;

use Attribute;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Input\ValueCaster;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CommandArgument
{
    public function __construct(
        public string $name,
        public string|int|float|null $value = null,
        public string $description = '',
    ) {
    }

    public function hasDefaultValue(\ReflectionProperty $property, Command $command): bool
    {
        if ($this->value !== null) {
            return true;
        }
        if (! $property->isInitialized($command)) {
            return false;
        }
        return true;
    }

    public function getDefaultValue(\ReflectionProperty $property, Command $command): mixed
    {
        return $this->value ?? $property->getValue($command);
    }

    public function apply(\ReflectionProperty $property, Command $command): void
    {
        if ($this->hasDefaultValue($property, $command)) {
            $property->setValue($command, $this->getDefaultValue($property, $command));
            return;
        }
        $value = $command->output->question('Enter value for argument ' . $this->name . ':')
                |> ValueCaster::cast(...);
        $property->setValue($command, $value);
    }
}
