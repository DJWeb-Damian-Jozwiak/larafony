<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Helpers;

use ArrayObject;

final readonly class ArrayGet
{
    public function __construct(
        private DotContainer $container
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $data = $this->container->getArrayCopy();
        $data = new ArrayObject($data);

        foreach ($keys as $segment) {
            if ($data->offsetExists($segment)) {
                $value = $data[$segment];
                $value = is_array($value) ? new ArrayObject($value) : $value;
                $data = $value;
            } else {
                return $default;
            }
        }

        return $data;
    }
}
