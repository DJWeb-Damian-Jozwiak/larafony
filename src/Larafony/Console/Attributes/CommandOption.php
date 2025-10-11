<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Attributes;

use Attribute;
use Larafony\Framework\Console\Command;
use Larafony\Framework\Console\Input\ValueCaster;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CommandOption
{
    public bool $isRequired {
        get => str_starts_with($this->name, '?');
    }
    public function __construct(
        public string $name,
        public ?string $value = null,
        public string $description = '',
    ) {
    }

    public function hasDefaultValue(\ReflectionProperty $property, Command $command): bool
    {
        if ($this->value !== null) {
            return true;
        }
        $value = $property->getValue($command);
        return $value !== null && $property->getName() === $this->name;
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
        if (! $this->isRequired) {
            return;
        }
        $value = $command->output->question('Enter value for option ' . $this->name . ': ')
                |> ValueCaster::cast(...);
        $property->setValue($command, $value);
    }
}
