# Chapter 30: MCP Integration - AI-Powered Development

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter implements Model Context Protocol (MCP) integration for Larafony, enabling AI assistants like Claude to interact with your application through a standardized protocol. MCP is Anthropic's open specification for connecting AI models with external tools, data sources, and services.

The implementation consists of two parts:

1. **Core MCP Integration** (`larafony/core`) - Foundation layer providing server factory, cache adapter, session storage, and example tools/prompts/resources
2. **MCP Assistant Bridge** (`larafony/mcp-assistant`) - AI-powered development assistant for learning the framework and scaffolding code

Larafony's MCP integration uses the official PHP MCP SDK (`mcp/sdk`), developed collaboratively by Anthropic, Symfony, and the PHP Foundation. This SDK provides attribute-based tool, resource, and prompt registration, making integration seamless with Larafony's existing attribute-first philosophy.

## Key Concepts

### What is MCP?

Model Context Protocol (MCP) defines three core primitives for AI interaction:

- **Tools** - Functions that AI can invoke to perform actions (e.g., create files, query database)
- **Resources** - Data sources AI can read (e.g., documentation, configuration, project structure)
- **Prompts** - Pre-built prompt templates for common tasks (e.g., code review, debugging help)

### Why MCP for Larafony?

1. **Standardized Integration** - One protocol for all AI assistants (Claude, GPT, etc.)
2. **Attribute-Based** - Tools/resources/prompts defined via PHP 8 attributes (natural fit for Larafony)
3. **PSR Compliant** - Uses PSR-11 container, PSR-3 logger, PSR-16 cache
4. **Auto-Discovery** - Automatically discovers tools in configured directories

## Core Components

### PSR-16 SimpleCache Adapter

**Location:** `src/Larafony/MCP/SimpleCache/SimpleCacheAdapter.php`

MCP SDK requires PSR-16 (SimpleCache) for discovery caching and session storage. Larafony uses PSR-6, so we provide an adapter:

```php
final class SimpleCacheAdapter implements CacheInterface
{
    public function __construct(
        private readonly Cache $cache,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        return $this->cache->put($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->cache->forget($key);
    }

    // ... other PSR-16 methods
}
```

### MCP Server Factory

**Location:** `src/Larafony/MCP/McpServerFactory.php`

Factory for creating MCP servers with full Larafony integration:

```php
final class McpServerFactory implements McpServerFactoryContract
{
    public function __construct(
        private readonly ContainerContract $container,
        private readonly ?EventDispatcherInterface $eventDispatcher = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?CacheInterface $discoveryCache = null,
    ) {}

    public function create(
        string $name,
        string $version,
        ?string $instructions = null,
        ?string $discoveryPath = null,
    ): Server {
        $builder = Server::builder()
            ->setServerInfo($name, $version)
            ->setContainer($this->container)
            ->setLogger($this->logger ?? new NullLogger());

        if ($this->eventDispatcher !== null) {
            $builder->setEventDispatcher($this->eventDispatcher);
        }

        if ($instructions !== null) {
            $builder->setInstructions($instructions);
        }

        if ($discoveryPath !== null) {
            $builder->setDiscovery(
                basePath: $discoveryPath,
                scanDirs: ['src', '.'],
                cache: $this->resolveDiscoveryCache(),
            );
        }

        return $builder->build();
    }
}
```

**Key Features:**
- **Container Integration** - Tools resolved via Larafony's PSR-11 container
- **Event Dispatching** - Optional PSR-14 event dispatcher for lifecycle events
- **Discovery Caching** - Uses Larafony Cache via SimpleCacheAdapter
- **Auto-Discovery** - Scans configured directories for `#[McpTool]`, `#[McpResource]`, `#[McpPrompt]`

### Session Store

**Location:** `src/Larafony/MCP/Session/CacheSessionStore.php`

MCP sessions stored in Larafony's cache system:

```php
final class CacheSessionStore implements SessionStoreInterface
{
    private const PREFIX = 'mcp_session:';
    private const TTL = 3600; // 1 hour

    public function __construct(
        private readonly CacheInterface $cache,
    ) {}

    public function get(string $sessionId): ?Session
    {
        $data = $this->cache->get(self::PREFIX . $sessionId);
        return $data ? unserialize($data) : null;
    }

    public function save(Session $session): void
    {
        $this->cache->set(
            self::PREFIX . $session->getId(),
            serialize($session),
            self::TTL
        );
    }

    public function delete(string $sessionId): void
    {
        $this->cache->delete(self::PREFIX . $sessionId);
    }
}
```

### Service Provider

**Location:** `src/Larafony/MCP/ServiceProviders/McpServiceProvider.php`

Registers MCP factory and example tools/prompts/resources:

```php
class McpServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [
            McpServerFactoryContract::class => McpServerFactory::class,

            // Example tools
            TimeTool::class => TimeTool::class,
            DatabaseTool::class => DatabaseTool::class,

            // Example resources
            ConfigResource::class => ConfigResource::class,

            // Example prompts
            CodeReviewPrompt::class => CodeReviewPrompt::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        parent::boot($container);

        $container->set(
            McpServerFactoryContract::class,
            new McpServerFactory(
                container: $container,
                eventDispatcher: $container->has(EventDispatcherInterface::class)
                    ? $container->get(EventDispatcherInterface::class)
                    : null,
                logger: $container->has(LoggerInterface::class)
                    ? $container->get(LoggerInterface::class)
                    : null,
            )
        );
    }
}
```

## Console Commands

### mcp:start

**Location:** `src/Larafony/MCP/Console/McpStartCommand.php`

Starts an MCP server using STDIO transport (for Claude Desktop integration):

```php
#[AsCommand(name: 'mcp:start')]
class McpStartCommand extends Command
{
    public function run(): int
    {
        $name = Config::get('mcp.name', Config::get('app.name', 'Larafony MCP Server'));
        $version = Config::get('mcp.version', '1.0.0');
        $instructions = Config::get('mcp.instructions');
        $discoveryPath = Config::get('mcp.discovery.path');

        $this->output->info("Starting MCP server: {$name} v{$version}");

        $factory = $this->container->get(McpServerFactoryContract::class);

        $server = $factory->create(
            name: $name,
            version: $version,
            instructions: $instructions,
            discoveryPath: $discoveryPath,
        );

        $transport = new StdioTransport();
        $server->run($transport);

        return 0;
    }
}
```

**Usage:**
```bash
php bin/larafony mcp:start
```

## Creating Custom Tools

### Attribute-Based Registration

Tools are registered via `#[McpTool]` attribute with `#[Schema]` for parameters:

```php
<?php

namespace App\MCP\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\TextContent;

class UserTool
{
    #[McpTool(
        name: 'create_user',
        description: 'Create a new user in the system',
    )]
    public function createUser(
        #[Schema(description: 'User email address', type: 'string')]
        string $email,
        #[Schema(description: 'User full name', type: 'string')]
        string $name,
        #[Schema(description: 'User role', type: 'string', enum: ['admin', 'user', 'guest'])]
        string $role = 'user',
    ): TextContent {
        // Create user logic
        $user = User::create([
            'email' => $email,
            'name' => $name,
            'role' => $role,
        ]);

        return new TextContent("User created with ID: {$user->id}");
    }

    #[McpTool(
        name: 'find_user',
        description: 'Find a user by email',
    )]
    public function findUser(
        #[Schema(description: 'Email to search for', type: 'string')]
        string $email,
    ): TextContent {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return new TextContent("No user found with email: {$email}");
        }

        return new TextContent(json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], JSON_PRETTY_PRINT));
    }
}
```

### Built-in Example: TimeTool

```php
class TimeTool
{
    #[McpTool(
        name: 'get_current_time',
        description: 'Get the current date and time in specified timezone',
    )]
    public function getCurrentTime(
        #[Schema(description: 'Timezone (e.g., UTC, Europe/Warsaw)', type: 'string')]
        string $timezone = 'UTC',
        #[Schema(description: 'Date format (PHP date format)', type: 'string')]
        string $format = 'Y-m-d H:i:s',
    ): TextContent {
        try {
            $tz = new \DateTimeZone($timezone);
            $now = new \DateTimeImmutable('now', $tz);
            return new TextContent($now->format($format));
        } catch (\Exception $e) {
            return new TextContent("Error: Invalid timezone '{$timezone}'");
        }
    }
}
```

## Creating Custom Resources

Resources expose read-only data to AI assistants:

```php
<?php

namespace App\MCP\Resources;

use Mcp\Capability\Attribute\McpResource;
use Mcp\Schema\Content\TextContent;

class ApiDocsResource
{
    #[McpResource(
        uri: 'docs://api',
        name: 'API Documentation',
        description: 'REST API endpoints and usage',
        mimeType: 'text/markdown',
    )]
    public function apiDocs(): TextContent
    {
        return new TextContent(<<<'MD'
# API Endpoints

## Users
- GET /api/users - List all users
- POST /api/users - Create new user
- GET /api/users/{id} - Get user by ID
- PUT /api/users/{id} - Update user
- DELETE /api/users/{id} - Delete user

## Authentication
All endpoints require Bearer token in Authorization header.
MD);
    }
}
```

### Built-in Example: ConfigResource

```php
class ConfigResource
{
    #[McpResource(
        uri: 'config://app',
        name: 'Application Config',
        description: 'Application configuration (app.name, app.env, etc.)',
        mimeType: 'application/json',
    )]
    public function appConfig(): TextContent
    {
        return new TextContent(json_encode([
            'name' => Config::get('app.name'),
            'env' => Config::get('app.env'),
            'debug' => Config::get('app.debug'),
            'url' => Config::get('app.url'),
            'timezone' => Config::get('app.timezone'),
        ], JSON_PRETTY_PRINT));
    }
}
```

## Creating Custom Prompts

Prompts are pre-built templates for common AI tasks:

```php
<?php

namespace App\MCP\Prompts;

use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

class TestWriterPrompt
{
    /**
     * @return array<int, PromptMessage>
     */
    #[McpPrompt(
        name: 'write_tests',
        description: 'Generate PHPUnit tests for a class',
    )]
    public function writeTests(
        #[Schema(description: 'The class code to test', type: 'string')]
        string $code,
        #[Schema(description: 'Test style', type: 'string', enum: ['unit', 'feature', 'both'])]
        string $style = 'unit',
    ): array {
        return [
            new PromptMessage(
                role: Role::User,
                content: new TextContent(<<<PROMPT
You are a PHP testing expert. Generate comprehensive {$style} tests for the following class.

Use PHPUnit 12+ syntax with attributes:
- test prefix
- #[DataProvider] for data providers
- Descriptive method names

Follow Larafony testing conventions:
- Use strict types
- Test edge cases
- Mock dependencies appropriately

Code to test:

```php
{$code}
```
PROMPT),
            ),
        ];
    }
}
```

### Built-in Example: CodeReviewPrompt

```php
class CodeReviewPrompt
{
    /**
     * @return array<int, PromptMessage>
     */
    #[McpPrompt(
        name: 'code_review',
        description: 'Generate a code review prompt for PHP/Larafony code',
    )]
    public function codeReview(
        #[Schema(description: 'The code to review', type: 'string')]
        string $code,
        #[Schema(description: 'Focus areas (security, performance, style)', type: 'string')]
        string $focus = 'all',
    ): array {
        $systemPrompt = match ($focus) {
            'security' => 'Focus on security vulnerabilities: SQL injection, XSS, CSRF.',
            'performance' => 'Focus on performance: N+1 queries, memory usage, caching.',
            'style' => 'Focus on code style: PSR-12, naming conventions, documentation.',
            default => 'Review for security, performance, maintainability, and Larafony conventions.',
        };

        return [
            new PromptMessage(
                role: Role::User,
                content: new TextContent("You are a code review assistant. {$systemPrompt}"),
            ),
            new PromptMessage(
                role: Role::User,
                content: new TextContent("Review this code:\n\n```php\n{$code}\n```"),
            ),
        ];
    }
}
```

## Configuration

### config/mcp.php

```php
<?php

return [
    // Server identification
    'name' => env('MCP_SERVER_NAME', env('APP_NAME', 'Larafony MCP Server')),
    'version' => env('MCP_VERSION', '1.0.0'),

    // Instructions for AI model (optional)
    'instructions' => <<<'TEXT'
This is a Larafony PHP 8.5 application.
Use attribute-based routing (#[Route]) and ORM relationships (#[BelongsTo], #[HasMany]).
Views use component-based Blade (<x-Layout>).
TEXT,

    // Auto-discovery configuration
    'discovery' => [
        'path' => base_path(),
        'dirs' => ['src/MCP'],
    ],
];
```

### Claude Desktop Integration

Add to `~/.config/claude/claude_desktop_config.json` (Linux) or `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS):

```json
{
  "mcpServers": {
    "larafony-app": {
      "command": "php",
      "args": ["/path/to/your/app/bin/larafony", "mcp:start"],
      "env": {}
    }
  }
}
```

## Bridge Package: larafony/mcp-assistant

The `larafony/mcp-assistant` bridge package provides AI-powered development tools for learning Larafony and scaffolding code. It's similar to Laravel Boost but designed specifically for Larafony conventions.

### Installation

```bash
composer require larafony/mcp-assistant
```

### What It Provides

#### Scaffolding Tools

| Tool | Description |
|------|-------------|
| `make_controller` | Generate a controller with #[Route] attributes |
| `make_model` | Generate an ORM model with property hooks |
| `make_view` | Generate a Blade view with component layout |
| `make_job` | Generate a queueable job with #[Serialize] |
| `make_migration` | Generate a database migration |
| `make_command` | Generate a console command with #[AsCommand] |

#### Resources

| URI | Description |
|-----|-------------|
| `project://structure` | Project directory structure overview |
| `project://controllers` | List of controllers with routes |
| `project://models` | List of ORM models |
| `docs://routing` | Routing documentation |
| `docs://models` | ORM models documentation |
| `docs://views` | Blade views documentation |
| `docs://controllers` | Controllers documentation |

#### Prompts

| Prompt | Description |
|--------|-------------|
| `learn_larafony` | Guided learning for framework topics |
| `build_feature` | Step-by-step feature building guidance |
| `debug_error` | Debug application errors |
| `debug_route` | Debug routing issues |
| `debug_model` | Debug ORM/database issues |
| `debug_view` | Debug Blade view issues |

### Usage Example

With Claude Desktop configured, you can interact naturally:

> "Create a UserController with CRUD routes"

Claude will use `make_controller` tool:
```
Created controller: src/Controllers/UserController.php
With routes:
  GET /users - index
  GET /users/{user} - show
  POST /users - store
  PUT /users/{user} - update
  DELETE /users/{user} - destroy
```

> "Show me how routing works in Larafony"

Claude reads `docs://routing` resource and explains:
```
Larafony uses attribute-based routing. Define routes directly on controller methods:

#[Route('/users', 'GET')]
public function index(): ResponseInterface

Route model binding is automatic when you type-hint models...
```

> "Help me debug: Route /api/users returns 404"

Claude uses `debug_route` prompt and provides structured debugging guidance.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      AI Assistant (Claude)                   │
├─────────────────────────────────────────────────────────────┤
│                     MCP Protocol (STDIO)                     │
├─────────────────────────────────────────────────────────────┤
│                      MCP SDK (mcp/sdk)                       │
│   ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐     │
│   │ #[McpTool]  │  │#[McpResource│  │  #[McpPrompt]   │     │
│   │ Attributes  │  │  Attributes │  │   Attributes    │     │
│   └─────────────┘  └─────────────┘  └─────────────────┘     │
├─────────────────────────────────────────────────────────────┤
│                   Larafony MCP Integration                   │
│   ┌─────────────────────────────────────────────────────┐   │
│   │              McpServerFactory                        │   │
│   │  - Container (PSR-11)                                │   │
│   │  - Logger (PSR-3)                                    │   │
│   │  - EventDispatcher (PSR-14)                          │   │
│   │  - Cache → SimpleCacheAdapter (PSR-6 → PSR-16)       │   │
│   └─────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│                      Larafony Core                           │
│   Container │ Cache │ Events │ Console │ ORM │ Config       │
└─────────────────────────────────────────────────────────────┘
```

## Comparison with Other Frameworks

| Feature | Larafony MCP | Laravel Boost | Symfony MCP |
|---------|--------------|---------------|-------------|
| **SDK** | Official mcp/sdk | Custom implementation | N/A (no official) |
| **Registration** | Attributes (#[McpTool]) | Config files | N/A |
| **Container** | PSR-11 (native) | Laravel Container | N/A |
| **Cache** | PSR-6 → PSR-16 adapter | Laravel Cache | N/A |
| **Discovery** | Auto-discovery via SDK | Manual registration | N/A |
| **Transport** | STDIO (Claude Desktop) | HTTP + STDIO | N/A |

**Key Advantages:**

1. **Official SDK** - Uses the official PHP MCP SDK developed by Anthropic/Symfony/PHP Foundation
2. **Attribute-First** - Natural fit with Larafony's attribute-based philosophy
3. **PSR Compliant** - Bridges PSR-6 to PSR-16 for SDK compatibility
4. **Zero Configuration** - Auto-discovery finds tools/resources/prompts automatically
5. **Bridge Package** - Separate `mcp-assistant` package for AI development tools

## Testing

```bash
# Run MCP core tests
composer test -- tests/Larafony/MCP

# Run MCP assistant bridge tests
cd bridges/larafony-mcp-assistant && composer test
```

Test coverage includes:
- SimpleCacheAdapter PSR-16 compliance
- McpServerFactory creation with various configurations
- CacheSessionStore session management
- Tool/resource/prompt attribute registration
- Console command execution

## Summary

This chapter delivered complete MCP integration for Larafony:

1. **Core Integration** - Server factory, cache adapter, session store with full PSR compliance
2. **Attribute-Based** - Tools, resources, and prompts via PHP 8 attributes
3. **Auto-Discovery** - Automatic registration of MCP capabilities
4. **Bridge Package** - AI assistant for learning and scaffolding
5. **Claude Desktop** - Ready for Claude Desktop integration via STDIO

The architecture demonstrates Larafony's commitment to standards (PSR-16 adapter), modern PHP (attributes), and practical AI integration for improved developer experience.

---

Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)
