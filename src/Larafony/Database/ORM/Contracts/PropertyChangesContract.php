<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

interface PropertyChangesContract
{
    public function markPropertyAsChanged(string $property_name): void;
}
