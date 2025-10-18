<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Migrations;

use Larafony\Framework\Core\Helpers\Directory;
use RuntimeException;

readonly class MigrationResolver
{
    public function __construct(private string $migrationPath)
    {
    }

    /**
     * @return array<int, string>
     */
    public function getMigrationFiles(): array
    {
        $files = new Directory($this->migrationPath)->files
            |> (fn ($files) => array_filter($files, $this->isMigrationFile(...)))
            |> (static fn ($files) => array_map(static fn (\SplFileInfo $file) => $file->getPathname(), $files));
        sort($files);
        return $files;
    }

    public function resolve(string $file): Migration
    {
        $path = $this->migrationPath . DIRECTORY_SEPARATOR . $file . '.php';
        $class = require_once $path;
        $migration = new $class($file);
        if (! $migration instanceof Migration) {
            throw new RuntimeException('Migration class must extend Migration');
        }
        return $migration->withName($file);
    }

    private function isMigrationFile(\SplFileObject $file): bool
    {
        $name = $file->getFilename();
        return (bool) preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_\w+$/', $name);
    }
}
