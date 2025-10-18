<?php

declare(strict_types=1);

namespace Larafony\Framework\Log\Rotators;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Core\Helpers\Directory;
use Larafony\Framework\Log\Contracts\RotatorContract;

readonly class DailyRotator implements RotatorContract
{
    public function __construct(
        private int $maxDays = 7,
        private readonly ?string $pattern = null,
    ) {
    }

    public function shouldRotate(string $logPath): bool
    {
        if (! file_exists($logPath)) {
            return false;
        }
        $now = ClockFactory::now();
        $fromFile = new \DateTimeImmutable()->setTimestamp((int) filemtime($logPath));

        return $now->format('Y-m-d') !== $fromFile->format('Y-m-d');
    }

    public function rotate(string $logPath): string
    {
        $info = pathinfo($logPath);
        return sprintf(
            '%s/%s-%s.%s',
            $info['dirname'] ?? '',
            $info['filename'],
            ClockFactory::now()->format('Y-m-d'),
            $info['extension'] ?? '',
        );
    }

    public function cleanup(string $logPath): void
    {
        $pattern = $this->pattern ?? '/^\w+\-\d{4}\-\d{2}\-\d{2}\.\w+$/';
        $threshold = ClockFactory::now()->modify("-{$this->maxDays} days")->getTimestamp();

        $files = new Directory(dirname($logPath))->files
            |> (fn ($files) => array_filter($files, fn ($file) => $this->isValidLogFile($file)))
            |> (fn ($files) => array_filter($files, fn ($file) => $this->matchesPattern($pattern, $file)))
            |> (static fn ($files) => array_filter($files, static fn ($file) => $file->getMTime() < $threshold));

        foreach ($files as $file) {
            unlink($file->getPathname());
        }
    }

    private function isValidLogFile(\SplFileInfo $file): bool
    {
        $filename = $file->getFilename();
        return $filename !== '.' && $filename !== '..' && $file->isFile();
    }

    private function matchesPattern(string $pattern, \SplFileInfo $file): bool
    {
        return (bool) preg_match($pattern, $file->getFilename());
    }
}
