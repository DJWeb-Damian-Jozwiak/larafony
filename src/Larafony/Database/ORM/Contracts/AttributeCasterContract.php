<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

interface AttributeCasterContract
{
    /**
     * Cast a property value based on its type or CastUsing attribute
     *
     * @param mixed $value The raw value to cast
     * @param string $property_name The name of the property being cast
     * @param object $model The model instance containing the property
     *
     * @return mixed The casted value
     */
    public function cast(mixed $value, string $property_name, object $model): mixed;
}
