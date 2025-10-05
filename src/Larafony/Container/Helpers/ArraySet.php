<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Helpers;

use ArrayObject;

final class ArraySet
{
    public function __construct(
        private DotContainer $container
    ) {
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $data = &$this->container;

        foreach ($keys as $segment) {
            if (! isset($data[$segment])) {
                $data[$segment] = new ArrayObject();
            }

            $data = &$data[$segment];
        }

        $data = $value;
    }
}