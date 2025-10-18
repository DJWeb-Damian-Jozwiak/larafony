<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

interface NotifyPropertyChangesContract
{
    public function markPropertyAsChanged(string $propertyName, mixed $value): void;

    /**
     * @return array<string, int|string|float|null>
     */
    public function getChangedProperties(): array;
}
