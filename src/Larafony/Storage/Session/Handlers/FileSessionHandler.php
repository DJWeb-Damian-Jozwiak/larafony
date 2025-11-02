<?php

declare(strict_types=1);

namespace Larafony\Framework\Storage\Session\Handlers;

use Larafony\Framework\Storage\Directory;
use Larafony\Framework\Storage\File;
use Larafony\Framework\Storage\Session\SessionSecurity;

final readonly class FileSessionHandler implements \SessionHandlerInterface
{
    public function __construct(private string $savePath, private SessionSecurity $security)
    {
        Directory::ensureDirectoryExists($this->savePath);
        Directory::ensureDirectoryIsWritable($this->savePath);
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $file = $this->getFilePath($id);
        if (! file_exists($file)) {
            return '';
        }
        File::ensureFileIsReadable($file);
        $encrypted = file_get_contents($file);
        if ($encrypted === false) {
            return '';
        }
        return $this->security->decrypt($encrypted);
    }

    public function write(string $id, string $data): bool
    {
        $file = $this->getFilePath($id);
        $encrypted = $this->security->encrypt($data);

        return file_put_contents($file, $encrypted) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = $this->getFilePath($id);
        File::unlink($file);

        return true;
    }

    public function gc(int $max_lifetime): int
    {
        $files = glob($this->savePath . '/sess_*');
        if ($files === false) {
            return 0;
        }
        $files = array_filter($files, static fn (string $file) => filemtime($file) + $max_lifetime < time());
        array_walk($files, static fn (string $file) => unlink($file));
        return count($files);
    }

    private function getFilePath(string $id): string
    {
        return $this->savePath . '/sess_' . $id;
    }
}
