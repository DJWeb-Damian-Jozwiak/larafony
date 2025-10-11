<?php

declare(strict_types=1);

namespace Larafony\Framework\Console\Input;

class Input
{
    /**
     * @var array<string|int, string> $options
     */
    public private(set) array $options {
        get => $this->options;
        set {
            $this->options = [];
            foreach ($value as $option) {
                $option = str_replace('--', '', $option);
                $this->options[$option] = $option;
            }
        }
    }
    /**
     * @param array<int, string> $arguments
     * @param array<int, string> $options
     */
    public function __construct(
        public string $command,
        public readonly array $arguments = [],
        array $options = []
    ) {
        $this->options = $options;
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function getArgument(int $index): string|int|float|bool|null
    {
        return $this->arguments[$index] ?? null;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function getOption(string $name): string|int|float|bool|null
    {
        return $this->options[$name] ?? null;
    }
}
