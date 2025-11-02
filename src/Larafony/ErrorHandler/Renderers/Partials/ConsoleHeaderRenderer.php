<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials;

use Larafony\Framework\Console\Contracts\OutputContract;
use ReflectionClass;
use Throwable;

readonly class ConsoleHeaderRenderer
{
    private const LINE_LENGTH = 80;

    public function __construct(
        private OutputContract $output
    ) {
    }

    public function render(Throwable $exception): void
    {
        $class = new ReflectionClass($exception)->getShortName();

        $this->output->error(str_repeat('=', self::LINE_LENGTH));
        $this->output->error($class);
        $this->output->error(str_repeat('=', self::LINE_LENGTH));
        $this->output->writeln('');
        $this->output->writeln("<danger>{$exception->getMessage()}</danger>");
        $this->output->writeln('');
    }
}
