<?php

declare(strict_types=1);

namespace Larafony\Framework\ErrorHandler\Renderers\Partials;

use Larafony\Framework\Console\Contracts\OutputContract;

readonly class ConsoleEnvironmentRenderer
{
    public function __construct(
        private OutputContract $output
    ) {
    }

    public function render(): void
    {
        $this->output->writeln('');
        $this->output->writeln('Environment:');
        $this->renderEnvironmentDetails();
    }

    private function renderEnvironmentDetails(): void
    {
        $details = [
            'PHP Version' => PHP_VERSION,
            'Interface' => PHP_SAPI,
            'Server' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
            'Time' => date('Y-m-d H:i:s'),
        ];

        if (function_exists('memory_get_peak_usage')) {
            $details['Peak Memory'] = sprintf(
                '%.2f MB',
                memory_get_peak_usage(true) / 1024 / 1024
            );
        }

        foreach ($details as $key => $value) {
            $this->output->writeln(sprintf('%s: %s', $key, $value));
        }
    }
}
