<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Helpers;

use ArrayObject;
use Larafony\Framework\Container\Contracts\ArrayContract;

/**
 * @extends ArrayObject<string|int, mixed>
 */
class DotContainer extends ArrayObject implements ArrayContract
{
    private readonly ArrayGet $arrayGet;
    private readonly ArraySet $arraySet;

    /**
     * @param array<int|string, mixed> $array
     */
    public function __construct(array $array = [])
    {
        parent::__construct($array);
        $this->arrayGet = new ArrayGet($this);
        $this->arraySet = new ArraySet($this);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->arrayGet->get($key, $default);
    }

    public function set(string $key, mixed $default = null): void
    {
        $this->arraySet->set($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->arrayGet->get($key) !== null;
    }
}
