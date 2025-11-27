<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Casters;

use Larafony\Framework\Database\ORM\Attributes\CastUsing;
use Larafony\Framework\Database\ORM\Contracts\AttributeCasterContract;
use ReflectionProperty;

class AttributeCaster implements AttributeCasterContract
{
    public function cast(mixed $value, string $property_name, object $model): mixed
    {
        if ($value === null) {
            return null;
        }

        $reflection = new ReflectionProperty($model, $property_name);

        $cast_using = $reflection->getAttributes(CastUsing::class)[0] ?? null;
        if ($cast_using !== null) {
            /** @var CastUsing $cast_attribute */
            $cast_attribute = $cast_using->newInstance();
            return ($cast_attribute->callback)($value);
        }

        return $value;
    }
}
