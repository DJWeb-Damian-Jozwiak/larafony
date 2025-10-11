<?php

declare(strict_types=1);

namespace Larafony\Framework\Console;

final class CommandCache
{
    /**
     * @var array<string, class-string<Command>> $commands
     */
    public private(set) array $commands = [];

    /**
     * @var array<string, class-string<Command>> $commands
     */
    public function withCommands(array $commands): self
    {
        $this->commands = $commands;
        return $this;
    }

    public function load(string $cacheFile): bool
    {
        if (! file_exists($cacheFile)) {
            return false;
        }

        $cached = require $cacheFile;

        if (! is_array($cached)) {
            return false;
        }

        $cached = array_filter(
            $cached,
            static fn (string $command) => class_exists($command) && is_subclass_of($command, Command::class),
        );

        $this->commands = $cached;

        return true;
    }

    public function save(string $cacheFile): void
    {
        $cacheDir = dirname($cacheFile);

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $export = var_export($this->commands, true);

        file_put_contents(
            $cacheFile,
            "<?php\n\nreturn {$export};\n",
        );
    }
}
