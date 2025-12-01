<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Drivers\MySQL;

use Larafony\Framework\Container\Contracts\ContainerContract;
use Larafony\Framework\Events\Database\QueryExecuted;
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
     * @param mixed $file
     * @param mixed $line
     * @param mixed $compiledFile
     * @param mixed $compiledLine
     *
     * @return array<int, string>
     *
     * @throws \JsonException
     */
    public function handleCached(mixed $file, mixed $line, mixed $compiledFile, mixed $compiledLine): array
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
        return [$compiledFile, $compiledLine, $file, $line];
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
            $rawSql = preg_replace('/\?/', $value, $rawSql, 1);
        }

        return $rawSql;
    }

    /**
     * @return array<int, array{file: ?string, line: ?int, class: ?string, function: ?string, compiled_file: ?string, compiled_line: ?int}>
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
     *
     * @return array{file: ?string, line: ?int, class: ?string, function: ?string, compiled_file: ?string, compiled_line: ?int}
     */
    private function formatBacktraceFrame(array $frame): array
    {
        $file = $frame['file'] ?? null;
        $line = $frame['line'] ?? null;
        $compiledFile = null;
        $compiledLine = null;

        [$compiledFile, $compiledLine, $file, $line] = $this->handleCached($file, $line, $compiledFile, $compiledLine);

        if ($file !== null && str_starts_with($file, $this->projectRoot)) {
            $file = substr($file, strlen($this->projectRoot));
        }

        return [
            'file' => $file,
            'line' => $line,
            'class' => $frame['class'] ?? null,
            'function' => $frame['function'] ?? null,
            'compiled_file' => $compiledFile,
            'compiled_line' => $compiledLine,
        ];
    }
}
