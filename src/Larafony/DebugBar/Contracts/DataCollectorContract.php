<?php

declare(strict_types=1);

namespace Larafony\Framework\DebugBar\Contracts;

interface DataCollectorContract
{
    /**
     * @return array<string, mixed>
     */
    public function collect(): array;

    public function getName(): string;
}
