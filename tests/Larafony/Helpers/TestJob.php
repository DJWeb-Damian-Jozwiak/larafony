<?php

namespace Larafony\Framework\Tests\Helpers;

use Larafony\Framework\Scheduler\Attributes\Serialize;
use Larafony\Framework\Scheduler\Job;

class TestJob extends Job
{
    public function __construct(
        #[Serialize]
        public protected(set) string $id,
        #[Serialize('custom_title')]
        public protected(set) string $title,
        #[Serialize]
        public protected(set) string $name = 'John Doe',
        #[Serialize('custom_email')]
        public protected(set) string $email = 'john@example.com',
        #[Serialize]
        public protected(set) int $age = 30
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
    }

    public function handleException(\Throwable $e): void
    {
    }
}
