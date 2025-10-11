<?php

declare(strict_types=1);

namespace Larafony\Framework\Core\Helpers;

final class Directory
{
    public private(set) string $directory {
        get => $this->directory;
        set {
            if (! is_dir($value)) {
                throw new \InvalidArgumentException(sprintf('Directory %s does not exist', $value));
            }
            if (! is_readable($value)) {
                throw new \InvalidArgumentException(sprintf('Directory %s is not readable', $value));
            }
            $this->directory = $value;
        }
    }

    /**
     * @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator
     */
    public \RecursiveIteratorIterator $iterator {
        get => new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
    }

    /**
     * @var array<int, \SplFileInfo> $files
     */
    public array $files {
        get {
            $files = [];
            foreach ($this->iterator as $file) {
                $files[] = $file;
            }
            return $files;
        }
    }

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }
}
