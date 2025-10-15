<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\Base\Contracts;

interface MigrationContract
{
    public function up(): void;

    public function down(): void;
}
