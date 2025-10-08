<?php

declare(strict_types=1);

namespace Larafony\Framework\Web;

use Larafony\Framework\Container\Container;
use Larafony\Framework\Container\Contracts\ServiceProviderContract;
use Psr\Http\Message\ResponseInterface;

final class Application extends Container
{
    protected static ?self $instance = null;
    protected function __construct(public private(set) ?string $base_path = null)
    {
        parent::__construct();
        if ($this->base_path !== null) {
            $this->bind('base_path', $this->base_path);
        }
    }
    public static function instance(?string $base_path = null): Application
    {
        self::$instance ??= new self($base_path);
        return self::$instance;
    }

    /**
     * @param array<int, class-string<ServiceProviderContract>> $serviceProviders
     */
    public function withServiceProviders(array $serviceProviders): self
    {
        array_walk(
            $serviceProviders,
            fn (string $provider) => $this->get($provider)->register($this)->boot($this)
        );
        return $this;
    }

    /**
     * Emit PSR-7 response to the output buffer
     */
    public function emit(ResponseInterface $response): void
    {
        // Emit status line
        header(sprintf(
            'HTTP/%s %d %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // Emit headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Emit body
        echo $response->getBody();
    }
}
