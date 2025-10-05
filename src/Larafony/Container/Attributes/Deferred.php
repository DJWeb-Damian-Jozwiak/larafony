<?php

declare(strict_types=1);

namespace Larafony\Framework\Container\Attributes;

use Attribute;

/**
 * Marks a service provider as deferred.
 *
 * Deferred providers are not registered immediately but only when their services are requested.
 * This improves application boot time by lazy-loading services.
 * All deferred services are automatically cached as singletons.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Deferred
{
    /**
     * @param array<class-string> $provides List of service identifiers this provider offers
     */
    public function __construct(
        public array $provides = [],
    ) {
    }
}
