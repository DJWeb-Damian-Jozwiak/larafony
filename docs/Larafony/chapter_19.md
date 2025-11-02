# Chapter 19: Inertia.js Integration

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter introduces Inertia.js integration into the Larafony framework, enabling developers to build modern single-page applications (SPAs) using server-side routing and controllers while maintaining the benefits of client-side rendering with Vue, React, or Svelte. Unlike traditional SPAs that require separate API endpoints and client-side routing, Inertia.js acts as a bridge that allows you to build SPAs using classic server-side patterns.

The implementation includes a complete Inertia.js server-side adapter with support for shared props, lazy evaluation, partial reloads, and proper redirect handling. Additionally, we've integrated Vite for modern asset bundling, providing both development and production build support with automatic manifest parsing.

The architecture follows PSR standards for HTTP messages and middleware, ensuring seamless integration with the existing framework while maintaining the PSR-first philosophy that defines Larafony.

## Key Components

### Core Inertia Classes

- **Inertia** - Main service for rendering Inertia.js responses with shared props, lazy evaluation, and partial reload support (src/Larafony/View/Inertia/Inertia.php:11)
- **ResponseFactory** - Handles response creation logic, determining whether to return JSON (for XHR requests) or HTML (for initial page loads), with external redirect detection (src/Larafony/View/Inertia/ResponseFactory.php:13)
- **InertiaMiddleware** - PSR-15 middleware for setting up shared data and handling redirect status codes for PUT/PATCH/DELETE requests (src/Larafony/Http/Middleware/InertiaMiddleware.php:20)

### Asset Management

- **Vite** - Asset bundler integration that handles both development (hot reload) and production (manifest-based) asset loading (src/Larafony/View/Inertia/Vite.php:11)
- **ViteDirective** - Blade directive (`@vite`) for rendering Vite asset tags in templates (src/Larafony/View/Directives/ViteDirective.php:7)

### Framework Enhancements

- **Route** - Enhanced with automatic route parameter injection into request attributes (src/Larafony/Routing/Advanced/Route.php:102)
- **Controller** - Added `inertia()` helper method for convenient Inertia.js responses from controllers (src/Larafony/Web/Controller.php:70)

## PSR Standards Implemented

This implementation maintains strict PSR compliance throughout:

- **PSR-7**: HTTP Messages - All request and response handling uses PSR-7 interfaces (ServerRequestInterface, ResponseInterface)
- **PSR-15**: HTTP Middleware - InertiaMiddleware implements the standard MiddlewareInterface for request/response processing
- **PSR-11**: Container - Dependency injection through ContainerContract for all services

The Inertia.js integration demonstrates how modern frontend patterns can be implemented while maintaining PSR standards compliance.

## New Features

### Inertia.js Protocol Implementation

- **Dual Response Mode**: Automatically detects request type (initial visit vs. XHR) and returns HTML or JSON accordingly
- **Shared Props**: Global data sharing across all Inertia responses with lazy evaluation support
- **Partial Reloads**: Optimized data transfer by only sending requested props via `X-Inertia-Partial-Data` header
- **External Redirect Handling**: 409 Conflict responses for external redirects during XHR requests
- **Version Tracking**: Asset version management for cache invalidation
- **Root View Customization**: Configurable root template for Inertia page wrapper

### Vite Integration

- **Development Mode**: Automatic connection to Vite dev server (localhost:5173) with hot module replacement
- **Production Mode**: Manifest-based asset loading with proper CSS and import handling
- **Blade Directive**: Simple `@vite(['resources/js/app.js'])` syntax for asset inclusion

### Enhanced Routing

- **Automatic Parameter Injection**: Route parameters are automatically added to PSR-7 request attributes, accessible via `$request->getAttribute('id')`

## Usage Examples

### Basic Inertia Response

```php
<?php

namespace App\Controllers;

use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;

class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->inertia('Home/Index', [
            'title' => 'Welcome to Larafony',
            'user' => ['name' => 'John Doe', 'email' => 'john@example.com'],
        ]);
    }
}
```

### Shared Props in Middleware

```php
<?php

namespace App\Middleware;

use Larafony\Framework\Http\Middleware\InertiaMiddleware as BaseInertiaMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class CustomInertiaMiddleware extends BaseInertiaMiddleware
{
    /**
     * Share data globally with all Inertia responses
     */
    protected function getSharedData(ServerRequestInterface $request): array
    {
        return [
            'auth' => [
                'user' => $this->getAuthenticatedUser($request),
            ],
            'flash' => fn() => $this->getFlashMessages(), // Lazy evaluation
            'errors' => fn() => $this->getValidationErrors(),
        ];
    }

    private function getAuthenticatedUser(ServerRequestInterface $request): ?array
    {
        // Your authentication logic
        return null;
    }

    private function getFlashMessages(): array
    {
        // Your flash message logic
        return [];
    }

    private function getValidationErrors(): array
    {
        // Your validation error logic
        return [];
    }
}
```

### Root View Template

```blade
<!-- resources/views/inertia.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Larafony App</title>

    @vite(['resources/js/app.js', 'resources/css/app.css'])

    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
```

### Vue Component Example

```vue
<!-- resources/js/Pages/Home/Index.vue -->
<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  title: String,
  user: Object,
})
</script>

<template>
  <div>
    <h1>{{ title }}</h1>
    <p>Welcome, {{ user.name }}</p>
  </div>
</template>
```

### Route with Inertia

```php
<?php

namespace App\Controllers;

use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DashboardController extends Controller
{
    // GET request renders initial HTML with @inertia directive
    // XHR requests return JSON with component and props
    #[Route(path: '/dashboard', methods: ['GET'])]
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->inertia('Dashboard/Index', [
            'stats' => $this->getDashboardStats(),
        ]);
    }

    private function getDashboardStats(): array
    {
        return ['users' => 100, 'posts' => 500];
    }
}
```

### Accessing Route Parameters

```php
<?php

namespace App\Controllers;

use App\Models\User;use Larafony\Framework\Routing\Advanced\Attributes\Route;
use Larafony\Framework\Routing\Advanced\Attributes\RouteParam;use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends Controller
{
    // Route parameters automatically injected into request attributes
    #[Route(path: '/users/<user:\d+>', methods: ['GET'])]
    #[RouteParam(name: 'user', bind: User::class)]
    public function show(ServerRequestInterface $request, User $user): ResponseInterface
    {
        return $this->inertia('Users/Show', [
            'user' => $user,
        ]);
    }
}
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel | Symfony |
|---------|----------|---------|---------|
| Inertia.js Integration | Built-in with PSR-15 middleware | Official adapter package (`inertiajs/inertia-laravel`) | Community bundles (rompetomp, mercuryseries) |
| Asset Bundling | Vite with `@vite` directive | Laravel Mix/Vite with `@vite` helper | Symfony Encore (Webpack) |
| PSR Compliance | Full PSR-7/PSR-15 for all HTTP | Partial (Illuminate HTTP) | Full PSR support via HttpFoundation bridge |
| Middleware Approach | PSR-15 MiddlewareInterface | Laravel middleware (Illuminate) | Symfony event subscribers |
| Shared Props | Middleware with lazy evaluation | HandleInertiaRequests middleware | Bundle-specific middleware |
| Response Creation | Automatic HTML/JSON detection | Automatic via `inertia()` helper | Bundle-specific services |
| Route Parameters | Auto-injected to PSR-7 attributes | Auto-injected to method params | Auto-injected to controller params |
| Configuration | Attribute-based + code | Config files + service providers | Bundle configuration YAML |

**Key Differences:**

- **PSR-First Architecture**: Larafony implements Inertia.js using strict PSR-7 and PSR-15 standards, while Laravel uses its proprietary Illuminate HTTP components. Symfony bundles use PSR standards but require additional bridge components.

- **Built-in Integration**: Unlike Laravel and Symfony which require external packages, Larafony includes Inertia.js support as a first-class framework feature, demonstrating the framework's commitment to modern SPA development.

- **Lazy Evaluation**: All three frameworks support lazy prop evaluation via closures, but Larafony's implementation is built directly on PSR-15 middleware patterns rather than framework-specific abstractions.

- **Vite Integration**: Larafony and modern Laravel both use Vite for asset bundling with similar directive syntax. Symfony traditionally uses Webpack Encore, though newer bundles support Vite via community packages.

- **Attribute-Based Design**: Larafony continues its attribute-first philosophy while Laravel and Symfony rely more heavily on configuration files and service provider bootstrapping.

- **Minimal Dependencies**: Larafony's implementation uses only PSR packages, while Laravel and Symfony adapters require framework-specific dependencies and often additional packages for full functionality.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
