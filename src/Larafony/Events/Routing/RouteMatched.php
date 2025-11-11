<?php

declare(strict_types=1);

namespace Larafony\Framework\Events\Routing;

use Larafony\Framework\Database\ORM\Model;
use Larafony\Framework\Routing\Advanced\Route;

final readonly class RouteMatched
{
    public array $parameters;
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public Route $route,
        array $parameters,
    ) {
        $result = [];
        foreach ($parameters as $key => $value) {
            if($value instanceof Model) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }
        $this->parameters = $result;
    }
}
