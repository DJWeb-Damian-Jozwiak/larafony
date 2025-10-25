# Chapter 17: Blade-Inspired Template Engine with Components

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter introduces a complete, Blade-inspired templating system that brings modern view composition to Larafony. The implementation leverages PHP 8.5's property hooks (`protected(set)`, `private(set)`) to create immutable view objects with controlled mutability, providing Laravel's developer experience while maintaining strict PSR-7 compliance.

The view system compiles Blade-like templates to PHP with intelligent caching, supports template inheritance through layouts and sections, and implements reusable components with slots. Unlike traditional template engines that return strings, Larafony's View class extends PSR-7 Response, allowing views to be returned directly from controllers while maintaining full HTTP message compatibility.

The architecture uses the Strategy pattern for template engines (BladeAdapter, future TwigAdapter), the Template Method pattern for directives, and property hooks for clean immutability. All templates are compiled once and cached with file modification time checks, ensuring zero overhead after the initial compilation.

## Key Components

### Core View Classes

- **ViewManager** - Factory for creating View instances with fluent `make()` and `withRenderer()` methods using PHP 8.5 `protected(set)` property hooks
- **View** - PSR-7 Response extension with `render()` method, implements ViewContract, uses `protected(set)` for immutable data array
- **TemplateCompiler** - Compiles Blade syntax to PHP using directive chain and `private(set)` for directives array
- **TemplateLoader** - Loads template content from filesystem with dot notation support (e.g., 'components.Layout' → 'components/Layout.blade.php')
- **Component** - Abstract base class for reusable components with automatic property extraction via Reflection and slot support

### Template Engine Adapters

- **BladeAdapter** - Main Blade-compatible rendering engine with caching, layout inheritance, and 15+ built-in directives
- **BaseAdapter** - Abstract base providing common adapter functionality for future engines (Twig, Plates, etc.)

### Directives (15 total)

**Control Flow Directives:**
- `IfDirective` - Compiles `@if`, `@elseif`, `@else`, `@endif`
- `UnlessDirective` - Compiles `@unless`, `@endunless` (inverse if)
- `SwitchDirective` - Compiles `@switch`, `@case`, `@break`, `@default`, `@endswitch`
- `IssetDirective` - Compiles `@isset`, `@endisset`
- `EmptyDirective` - Compiles `@empty`, `@endempty`

**Loop Directives:**
- `ForeachDirective` - Compiles `@foreach`, `@endforeach`
- `ForDirective` - Compiles `@for`, `@endfor`
- `WhileDirective` - Compiles `@while`, `@endwhile`
- `DoWhileDirective` - Compiles `@do`, `@dowhile`, `@enddo`

**Layout & Component Directives:**
- `ExtendDirective` - Compiles `@extend('layout')` for template inheritance
- `SectionDirective` - Compiles `@section('name')`, `@endsection` for content blocks
- `YieldDirective` - Compiles `@yield('section')` for rendering sections in layouts
- `ComponentDirective` - Compiles `<x-component>` tags to PHP component instantiation
- `SlotDirective` - Compiles `<x-slot:name>` for named component slots

**Asset Management:**
- `StackDirective` - Compiles `@push('stack')`, `@endpush`, `@stack('name')` for asset stacking (scripts, styles)

### Helper Classes

- **AssetManager** - Manages asset stacks for scripts and styles with `push()` and `render()` methods
- **File** (Storage) - File system utilities including `isCached()` for template cache validation and `create()` for cache writing

### Contracts

- **ViewContract** - Interface for View objects with `render()`, `with()`, and property access
- **RendererContract** - Interface for template engines (BladeAdapter, etc.)
- **ComponentContract** - Interface for component rendering
- **DirectiveContract** - Interface for directive compilers with `compile(string): string` method
- **AssetManagerContract** - Interface for asset stack management

### Integration

- **Controller** (Web) - Base controller with `render()` method using ViewManager and `withRenderer()` for custom engines
- **ViewServiceProvider** - Registers ViewManager and BladeAdapter in DI container

## PSR Standards Implemented

- **PSR-7**: HTTP Messages - View extends Response, maintaining full PSR-7 compatibility for template rendering
- **PSR-11**: Container - ViewServiceProvider registers views in DI container

## New Syntax

### Blade Directives

**Echo Statements:**
```blade
{{ $variable }}              {{-- Escaped output (htmlspecialchars) --}}
{!! $rawHtml !!}             {{-- Raw output (unescaped) --}}
{{-- Comment --}}            {{-- Blade comments (compiled to PHP comments) --}}
```

**Control Structures:**
```blade
@if($condition)
@elseif($other)
@else
@endif

@unless($condition)
@endunless

@isset($variable)
@endisset

@empty($array)
@endempty

@switch($value)
    @case(1)
        @break
    @case(2)
        @break
    @default
@endswitch
```

**Loops:**
```blade
@foreach($items as $item)
@endforeach

@for($i = 0; $i < 10; $i++)
@endfor

@while($condition)
@endwhile

@do
@dowhile($condition)
@enddo
```

**Layouts & Sections:**
```blade
@extend('layouts.app')

@section('content')
    Content here
@endsection

@yield('content')
```

**Components:**
```blade
<x-layout title="Page Title">
    <x-alert>
        Alert content
    </x-alert>
</x-layout>

<x-card>
    <x-slot:header>
        Header content
    </x-slot:header>

    Default slot content
</x-card>
```

**Asset Stacks:**
```blade
@push('scripts')
    <script src="/app.js"></script>
@endpush

@stack('scripts')
```

## Usage Examples

### Basic Example - Controller Rendering

```php
<?php

use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HomeController extends Controller
{
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        // Renders resources/views/home.blade.php
        return $this->render('home', [
            'title' => 'Welcome',
            'user' => $request->getAttribute('user'),
        ]);
    }
}
```

### Advanced Example - Template with Layout

**resources/views/layouts/app.blade.php:**
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Larafony' }}</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 50px auto; }
        .card { background: #f4f4f4; padding: 20px; border-radius: 8px; }
    </style>
    @stack('styles')
</head>
<body>
    <header>
        <h1>{{ $title }}</h1>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <p>&copy; 2025 Larafony Framework</p>
    </footer>

    @stack('scripts')
</body>
</html>
```

**resources/views/posts/show.blade.php:**
```blade
@extend('layouts.app')

@section('content')
    <article>
        <h2>{{ $post->title }}</h2>
        <p>{{ $post->content }}</p>

        @if($post->published)
            <span>Published at {{ $post->published_at }}</span>
        @else
            <span>Draft</span>
        @endif

        @unless(empty($post->tags))
            <div class="tags">
                @foreach($post->tags as $tag)
                    <span class="tag">{{ $tag }}</span>
                @endforeach
            </div>
        @endunless
    </article>
@endsection

@push('scripts')
    <script src="/post-interactions.js"></script>
@endpush
```

### Component Example

**app/View/Components/Alert.php:**
```php
<?php

namespace App\View\Components;

use Larafony\Framework\View\Component;

class Alert extends Component
{
    public function __construct(
        public readonly string $type = 'info',
        public readonly bool $dismissible = false,
    ) {}

    protected function getView(): string
    {
        return 'components.alert';
    }
}
```

**resources/views/components/alert.blade.php:**
```blade
<div class="alert alert-{{ $type }} {{ $dismissible ? 'alert-dismissible' : '' }}">
    @if($dismissible)
        <button type="button" class="close">&times;</button>
    @endif

    {!! $slot !!}

    @isset($slots['footer'])
        <div class="alert-footer">
            {!! $slots['footer'] !!}
        </div>
    @endisset
</div>
```

**Usage in views:**
```blade
<x-alert type="warning" :dismissible="true">
    <strong>Warning!</strong> Check this out.

    <x-slot:footer>
        <a href="/learn-more">Learn More</a>
    </x-slot:footer>
</x-alert>
```

### Custom Renderer Example

```php
<?php

use Larafony\Framework\View\Engines\BladeAdapter;
use Larafony\Framework\Web\Controller;

class CustomController extends Controller
{
    public function __construct(ContainerContract $container)
    {
        parent::__construct($container);

        // Use custom renderer with different template paths
        $customRenderer = new BladeAdapter(
            template_path: '/custom/templates',
            cache_path: '/custom/cache',
            componentNamespace: '\\Custom\\Components'
        );

        $this->withRenderer($customRenderer);
    }
}
```

## Comparison with Other Frameworks

| Feature | Larafony | Laravel Blade | Symfony Twig |
|---------|----------|---------------|--------------|
| **Syntax** | Blade-inspired (`@if`, `{{ }}`) | Blade directives | Twig syntax (`{% if %}`, `{{ }}`) |
| **Compilation** | Compiles to PHP, file-based cache | Compiles to PHP | Compiles to PHP classes |
| **Template Extension** | `.blade.php` | `.blade.php` | `.html.twig` |
| **PSR-7 Integration** | ✅ View extends PSR-7 Response | ❌ Returns strings/Illuminate Response | ❌ Returns strings |
| **Property Hooks** | ✅ PHP 8.5 `protected(set)`, `private(set)` | ❌ Not available (PHP 8.1+) | ❌ Not available |
| **Components** | Class-based with reflection | Class-based or anonymous | Twig Components (experimental) |
| **Slots** | Named and default slots | Named and default slots | Blocks (similar concept) |
| **Template Inheritance** | `@extend`, `@section`, `@yield` | `@extends`, `@section`, `@yield` | `{% extends %}`, `{% block %}` |
| **Caching Strategy** | File mtime-based validation | File mtime-based | Timestamp + environment-based |
| **Asset Stacks** | `@push`, `@stack` | `@push`, `@stack` | Custom implementation needed |
| **Escaping** | Auto-escape with `{{ }}` | Auto-escape with `{{ }}` | Auto-escape with `{{ }}` |
| **Raw Output** | `{!! !!}` | `{!! !!}` | `{{ var|raw }}` |
| **Control Structures** | 15 directives (if, foreach, switch, etc.) | 20+ directives | Twig tags (if, for, etc.) |
| **Loops** | foreach, for, while, do-while | foreach, for, while, forelse | for, filter |
| **Comments** | `{{-- --}}` | `{{-- --}}` | `{# #}` |
| **Dot Notation** | ✅ `components.Layout` | ✅ `components.layout` | ❌ `/` separator |
| **Framework Coupling** | PSR-only, framework-agnostic | Laravel-specific | Framework-agnostic |
| **Complexity** | ≤5 with Strategy + Template Method | Medium | Higher (more features) |
| **Performance** | Zero overhead after compilation | Zero overhead after compilation | Slight overhead, better caching |

**Key Differences:**

1. **PSR-7 First-Class Citizen**: Larafony's View extends PSR-7 Response, allowing `return $this->render()` to return a proper HTTP message. Laravel Blade returns strings that are wrapped in framework-specific responses.

2. **PHP 8.5 Property Hooks**: Uses `protected(set)` and `private(set)` for controlled immutability in ViewManager, View, and TemplateCompiler. Not possible in Laravel/Symfony (PHP 8.1+).

3. **Simpler Component System**: Components use Reflection to auto-extract public properties, eliminating manual property declarations in templates. Laravel uses similar approach but within framework context.

4. **Strategy Pattern for Engines**: BladeAdapter implements RendererContract, making it trivial to swap engines (future TwigAdapter, PlatesAdapter) via `withRenderer()`. Laravel has similar but more tightly coupled.

5. **Minimal Dependencies**: No framework lock-in - only PSR contracts. Symfony's Twig is framework-agnostic but has more dependencies for full feature set.

6. **Template Compilation**: Both Larafony and Laravel compile to plain PHP for performance. Twig compiles to PHP classes with more overhead but better cross-platform caching.

7. **Developer Experience**: Borrowed Laravel's beloved Blade syntax for familiarity while maintaining architectural purity and PSR compliance.

8. **File-Based Caching**: Uses `File::isCached()` with modification time checks (similar to Laravel). Twig uses timestamps with environment awareness.

## Real World Integration

This chapter's features are demonstrated in the demo application with a complete view-based homepage replacing the previous JSON responses.

### Demo Application Changes

The demo app showcases the full templating system with:
- Layout component with slots
- Reusable UI components (InfoCard, Alert)
- Controller integration
- Blade syntax in practice

### File Structure
```
demo-app/
├── resources/
│   └── views/
│       ├── home.blade.php                 # Main homepage view
│       ├── components/
│       │   ├── Layout.blade.php           # Layout template with slots
│       │   ├── InfoCard.blade.php         # Card component
│       │   └── Alert.blade.php            # Alert component
│       └── errors/
│           └── 404.blade.php              # Error page
├── src/
│   ├── Http/Controllers/
│   │   └── DemoController.php             # Updated to use render()
│   └── View/Components/
│       ├── Layout.php                      # Layout component class
│       ├── InfoCard.php                    # InfoCard component class
│       └── Alert.php                       # Alert component class
├── storage/
│   └── framework/
│       └── views/                          # Compiled template cache
├── config/
│   └── views.php                           # View configuration
└── bootstrap/
    └── web_app.php                         # Registers ViewServiceProvider
```

### Implementation Example

**File: `demo-app/src/Http/Controllers/DemoController.php`**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Larafony\Framework\Clock\ClockFactory;
use Larafony\Framework\Clock\Enums\TimeFormat;
use Larafony\Framework\Clock\Enums\Timezone;
use Larafony\Framework\Web\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DemoController extends Controller
{
    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $currentTime = ClockFactory::timezone(Timezone::EUROPE_WARSAW)
            ->format(TimeFormat::DATETIME);

        // Controller::render() uses ViewManager to create a View
        // View extends PSR-7 Response - fully compatible with routing
        return $this->render('home', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'protocol' => $request->getProtocolVersion(),
            'currentTime' => $currentTime,
        ]);
    }
}
```

**What's happening here:**
1. **PSR-7 Compliance**: `render()` returns `ResponseInterface` - View extends Response
2. **ViewManager Integration**: Controller uses DI-injected ViewManager (from ViewServiceProvider)
3. **Template Resolution**: String 'home' resolves to `resources/views/home.blade.php`
4. **Data Passing**: Array data becomes template variables via `extract()`

**File: `demo-app/resources/views/home.blade.php`**

```blade
{{-- Component syntax: <x-{name}> resolves to App\View\Components\{Name} --}}
<x-layout title="Larafony Framework Demo">
    <h1>Larafony Framework Demo</h1>

    {{-- Using InfoCard component with default slot --}}
    <x-info-card>
        <h2>PSR-7/17 Implementation Active</h2>
        {{-- {{ }} = Auto-escaped output (htmlspecialchars) --}}
        <p><strong>Request Method:</strong> {{ $method }}</p>
        <p><strong>Request URI:</strong> {{ $uri }}</p>
        <p><strong>Protocol:</strong> HTTP/{{ $protocol }}</p>
        <p><strong>Current Time:</strong> {{ $currentTime }}</p>
    </x-info-card>

    {{-- Alert component demonstrating reusable UI elements --}}
    <x-alert>
        <p>Error Handler is active. Try these endpoints:</p>
    </x-alert>

    <ul>
        <li><a href="/info">📊 View Request Info (JSON)</a></li>
        <li><a href="/error">⚠️ Trigger E_WARNING</a></li>
        <li><a href="/exception">💥 Trigger Exception</a></li>
        <li><a href="/fatal">☠️ Trigger Fatal Error</a></li>
    </ul>
</x-layout>
```

**What's happening here:**
1. **Component Resolution**: `<x-layout>` → `ComponentDirective` → finds `App\View\Components\Layout`
2. **Slot Content**: Everything between `<x-layout>` tags becomes the `$slot` variable
3. **Auto-Escaping**: `{{ $method }}` compiles to `<?php echo htmlspecialchars($method, ENT_QUOTES, 'UTF-8'); ?>`
4. **Nested Components**: Components can contain other components (`<x-info-card>` inside `<x-layout>`)

**File: `demo-app/src/View/Components/Layout.php`**

```php
<?php

declare(strict_types=1);

namespace App\View\Components;

use Larafony\Framework\View\Component;

class Layout extends Component
{
    // Constructor parameters become public properties
    // These are automatically extracted to template variables
    public function __construct(
        public readonly string $title = 'Larafony Framework',
    ) {}

    // Specifies which template file to render
    // Dot notation: 'components.Layout' → 'components/Layout.blade.php'
    protected function getView(): string
    {
        return 'components.Layout';
    }
}
```

**What's happening here:**
1. **Property Extraction**: `Component::render()` uses Reflection to extract public properties (`$title`)
2. **Template Variables**: Public properties + `$slot` + `$slots` array merged into template data
3. **Inheritance**: Extends abstract `Component` which provides slot management and rendering logic
4. **Immutability**: `readonly` properties ensure component state can't be mutated after construction

**File: `demo-app/resources/views/components/Layout.blade.php`**

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Component property available as variable --}}
    <title>{{ $title ?? 'Larafony Demo' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
    </style>
</head>
<body>
    {{-- {!! !!} = Raw output (unescaped) for HTML content --}}
    {{-- $slot contains all content between <x-layout> tags --}}
    {!! $slot !!}
</body>
</html>
```

**What's happening here:**
1. **Raw Output**: `{!! $slot !!}` compiles to `<?php echo $slot; ?>` (no escaping for HTML)
2. **Null Coalescing**: `{{ $title ?? 'Larafony Demo' }}` provides default value
3. **Component Data**: `$title` comes from `Layout::__construct()` parameter
4. **Slot System**: Default slot contains everything from parent template between component tags

**File: `demo-app/resources/views/components/InfoCard.blade.php`**

```blade
<div style="background: #ecf0f1; padding: 20px; border-radius: 8px; margin: 20px 0;">
    {!! $slot !!}
</div>
```

**What's happening here:**
1. **Simple Component**: No component class needed - `ComponentDirective` creates anonymous component
2. **Slot Rendering**: Displays content passed between `<x-info-card>` tags
3. **Inline Styles**: Simple styling for demo purposes (production would use CSS files with `@stack`)

### Running the Demo

```bash
cd demo-app
php -S localhost:8000 -t public
```

Open browser to `http://localhost:8000`

**Expected output:**
```
A styled webpage with:
- "Larafony Framework Demo" heading
- Info card showing:
  - PSR-7/17 Implementation Active
  - Request Method: GET
  - Request URI: http://localhost:8000/
  - Protocol: HTTP/1.1
  - Current Time: 2025-10-25 14:30:45
- Alert box with error handler message
- Links to other demo endpoints
```

**View the compiled template:**
```bash
cat demo-app/storage/framework/views/<hash>.php
```

You'll see the compiled PHP code showing:
- `@extend` → PHP includes
- `{{ }}` → `htmlspecialchars()` calls
- Components → Instantiated classes
- Raw template logic converted to efficient PHP

### Key Takeaways

1. **PSR-7 Integration**: Views are first-class HTTP responses, not just strings. This maintains architectural consistency throughout the framework.

2. **Component-Based UI**: Reusable components (`Layout`, `InfoCard`, `Alert`) promote DRY principles and maintainability.

3. **Blade Familiarity**: Developers familiar with Laravel Blade can immediately use Larafony's templating with zero learning curve.

4. **Zero Configuration**: No template registration needed - component resolution via namespace convention (`<x-layout>` → `App\View\Components\Layout`).

5. **Performance**: Templates compile once to PHP and cache based on file modification time. Production deployments see zero compilation overhead.

6. **Separation of Concerns**: Controllers focus on business logic, templates handle presentation, components encapsulate reusable UI patterns.

7. **Property Hooks Showcase**: `View::$data` uses `protected(set)` - publicly readable, privately writable - demonstrating PHP 8.5 property hooks in real application.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
