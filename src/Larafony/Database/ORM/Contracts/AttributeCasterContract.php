<?php

declare(strict_types=1);

namespace Larafony\Framework\Database\ORM\Contracts;

interface AttributeCasterContract
{
    /**
     * Cast a property value based on its type or CastUsing attribute (for reading from DB)
     *
     * @param mixed $value The raw value to cast
     * @param string $property_name The name of the property being cast
     * @param object $model The model instance containing the property
     *
     * @return mixed The casted value
     */
    public function cast(mixed $value, string $property_name, object $model): mixed;

    /**
     * Cast a property value back to its database representation (for writing to DB)
     *
     * @param mixed $value The value to cast back
     * @param string $property_name The name of the property being cast
     * @param object $model The model instance containing the property
     *
     * @return mixed The value ready for database storage
     */
    public function castBack(mixed $value, string $property_name, object $model): mixed;
}
