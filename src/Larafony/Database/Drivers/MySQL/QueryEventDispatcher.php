<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Events\Database\QueryExecuted;
use Larafony\Framework\Events\Database\StackFrameDto;
use Larafony\Framework\View\BladeSourceMapper;
use Psr\EventDispatcher\EventDispatcherInterface;

final class QueryEventDispatcher
{
    public function __construct(
        private readonly ContainerContract $container,
        private readonly string $projectRoot,
    ) {
    }

    /**
     * @param array<int, mixed> $params
     */
    public function dispatch(string $sql, array $params, float $time, callable $quoteCallback): void
    {
        $eventDispatcher = $this->getEventDispatcher();
        if ($eventDispatcher === null) {
            return;
        }

        $rawSql = $this->buildRawSql($sql, $params, $quoteCallback);
        $backtrace = $this->buildBacktrace();

        $eventDispatcher->dispatch(
            new QueryExecuted(
                $sql,
                $rawSql,
                $time,
                'default',
                $backtrace,
            ),
        );
    }

    /**
     * @return array{compiledFile: ?string, compiledLine: ?int, file: ?string, line: ?int}
     */
    public function handleCached(?string $file, ?int $line, ?string $compiledFile, ?int $compiledLine): array
    {
        if ($file !== null && $line !== null && str_contains($file, '/cache/blade/')) {
            $compiledFile = $file;
            $compiledLine = $line;

            $resolved = BladeSourceMapper::resolve($file, $line);
            if ($resolved !== null) {
                $file = $resolved['file'];
                $line = $resolved['line'];
            }
        }
        return [
            'compiledFile' => $compiledFile,
            'compiledLine' => $compiledLine,
            'file' => $file,
            'line' => $line,
        ];
    }

    private function getEventDispatcher(): ?EventDispatcherInterface
    {
        if (! $this->container->has(EventDispatcherInterface::class)) {
            return null;
        }

        return $this->container->get(EventDispatcherInterface::class);
    }

    /**
     * @param array<int, mixed> $params
     */
    private function buildRawSql(string $sql, array $params, callable $quoteCallback): string
    {
        $rawSql = $sql;
        foreach ($params as $param) {
            $value = $quoteCallback($param);
            $rawSql = (string) preg_replace('/\?/', $value, $rawSql, 1);
        }

        return $rawSql;
    }

    /**
     * @return array<int, StackFrameDto>
     */
    private function buildBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        return array_map(
            fn (array $frame) => $this->formatBacktraceFrame($frame),
            $trace,
        );
    }

    /**
     * @param array{file?: string, line?: int, class?: string, function?: string} $frame
     */
    private function formatBacktraceFrame(array $frame): StackFrameDto
    {
        $file = $frame['file'] ?? null;
        $line = $frame['line'] ?? null;

        $cached = $this->handleCached($file, $line, null, null);

        $file = $cached['file'];
        if ($file !== null && str_starts_with($file, $this->projectRoot)) {
            $file = substr($file, strlen($this->projectRoot));
        }

        return new StackFrameDto(
            file: $file,
            line: $cached['line'],
            class: $frame['class'] ?? null,
            function: $frame['function'] ?? null,
            compiledFile: $cached['compiledFile'],
            compiledLine: $cached['compiledLine'],
        );
    }
}
