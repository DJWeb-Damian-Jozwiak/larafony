<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class ArrayCaster extends CastUsing
{
    public function __construct()
    {
        parent::__construct(self::toArray(...), self::castBack(...));
    }

    /**
     * @return array<int|string, mixed>
     */
    public static function toArray(string $value): array
    {
        if (str_contains($value, ',')) {
            return explode(',', $value);
        }
        return json_decode($value, true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<int|string, mixed> $value
     */
    public static function castBack(array $value): string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
