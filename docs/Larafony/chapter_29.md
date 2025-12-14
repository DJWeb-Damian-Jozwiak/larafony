# Chapter 29: WebSockets - Real-Time Communication

> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)

## Overview

This chapter implements a complete WebSocket system for Larafony, enabling real-time bidirectional communication between server and clients. The implementation follows a modular architecture with swappable engines, making it easy to switch between native PHP Fibers and ReactPHP depending on performance requirements.

The core WebSocket implementation uses PHP 8.5 Fibers for non-blocking I/O without external dependencies, while the optional `larafony/websocket-react` bridge package provides a high-performance ReactPHP-based engine for production environments requiring maximum scalability.

The implementation strictly follows RFC 6455 (WebSocket Protocol) with complete frame encoding/decoding, proper handshake handling, and support for all control frames (ping/pong/close). The architecture separates protocol logic from I/O handling, enabling different engines to share the same battle-tested WebSocket protocol implementation.

## Key Components

### Core Protocol Components

- **Frame** - Immutable value object representing a WebSocket frame with factory methods for text, binary, ping, pong, and close frames
- **Encoder** - RFC 6455 compliant frame encoder handling payload length variants (7-bit, 16-bit, 64-bit) and masking
- **Decoder** - Frame decoder with proper error handling for malformed frames and incomplete data
- **Opcode** - Enum defining WebSocket opcodes (CONTINUATION, TEXT, BINARY, CLOSE, PING, PONG) with `isControl()` helper
- **Handshake** - HTTP upgrade handshake handler with Sec-WebSocket-Accept key generation per RFC 6455

### Contracts (Interfaces)

- **EngineContract** - Abstraction for I/O engines, defining `listen()`, `run()`, `stop()`, and event handler registration
- **ConnectionContract** - Connection abstraction with `send()`, `close()`, `getId()`, and `getRemoteAddress()` methods
- **ServerContract** - High-level server interface with `on()` event registration, `broadcast()`, and connection management
- **MessageHandlerContract** - Optional handler interface for class-based message handling with lifecycle methods

### Core Implementation

- **Server** - Main WebSocket server orchestrating engine, connections, and event dispatching
- **Connection** - Native PHP socket-based connection implementation
- **EventDispatcher** - Simple event system for WebSocket events (open, message, close, error, custom events)
- **FiberEngine** - Native PHP 8.5 implementation using `stream_select()` and Fibers for concurrent connections

### Bridge Package (larafony/websocket-react)

- **ReactEngine** - High-performance engine using ReactPHP event loop and socket server
- **ReactConnection** - Adapter wrapping React's ConnectionInterface to implement ConnectionContract
- **ReactWebSocketServiceProvider** - Service provider registering ReactEngine as the default engine

## PSR Standards Implemented

The WebSocket system follows established patterns used throughout Larafony:

- **PSR-11**: Container integration via service providers for engine and server registration
- **PSR-4**: Autoloading with `Larafony\Framework\WebSockets` namespace structure

The implementation uses interfaces (`EngineContract`, `ConnectionContract`, `ServerContract`) to ensure loose coupling and enable easy swapping of engine implementations without changing application code.

## Architecture

### Engine Abstraction

The key architectural decision is separating protocol handling from I/O operations:

```
┌─────────────────────────────────────────────────────┐
│                 SHARED PROTOCOL LAYER               │
├─────────────────────────────────────────────────────┤
│  Frame, Encoder, Decoder, Opcode, Handshake         │
│  Server, EventDispatcher, Connection logic          │
├─────────────────────────────────────────────────────┤
│              ENGINE ABSTRACTION (EngineContract)    │
├──────────────────────┬──────────────────────────────┤
│   FiberEngine        │      ReactEngine             │
│   (Core - no deps)   │   (Bridge - react/*)         │
│                      │                              │
│   - stream_select()  │   - LoopInterface            │
│   - Fiber::suspend() │   - SocketServer             │
│   - ext-sockets      │   - react/socket             │
└──────────────────────┴──────────────────────────────┘
```

This design allows:
- Zero-dependency core implementation for simple use cases
- Drop-in ReactPHP engine for high-scale production
- Future engines (Swoole, Amp, etc.) without protocol changes

## Implementation Details

### Frame - WebSocket Frame Structure

**Location:** `src/Larafony/WebSockets/Protocol/Frame.php`

Immutable value object with convenient factory methods:

```php
final readonly class Frame
{
    public function __construct(
        public bool $fin,
        public Opcode $opcode,
        public bool $mask,
        public int $payloadLength,
        public ?string $maskingKey,
        public string $payload,
    ) {}

    public static function text(string $payload, bool $mask = false): self;
    public static function binary(string $payload, bool $mask = false): self;
    public static function ping(string $payload = ''): self;
    public static function pong(string $payload = ''): self;
    public static function close(int $code = 1000, string $reason = ''): self;
}
```

### Encoder - RFC 6455 Frame Encoding

**Location:** `src/Larafony/WebSockets/Protocol/Encoder.php`

Handles all payload length variants per RFC 6455 section 5.2:

```php
final class Encoder
{
    public static function encode(Frame $frame): string
    {
        $frameHead = [];

        // First byte: FIN bit + opcode
        $frameHead[0] = ($frame->fin ? 128 : 0) | $frame->opcode->value;

        // Second byte: mask bit + payload length
        $payloadLength = strlen($frame->payload);

        if ($payloadLength <= 125) {
            $frameHead[1] = ($frame->mask ? 128 : 0) | $payloadLength;
        } elseif ($payloadLength <= 65535) {
            $frameHead[1] = ($frame->mask ? 128 : 0) | 126;
            // 16-bit extended length
        } else {
            $frameHead[1] = ($frame->mask ? 128 : 0) | 127;
            // 64-bit extended length
        }

        // ... masking and payload
    }

    public static function applyMask(string $payload, string $maskingKey): string;
}
```

### FiberEngine - Native PHP Implementation

**Location:** `src/Larafony/WebSockets/Engine/FiberEngine.php`

Uses PHP 8.5 Fibers for non-blocking concurrent connection handling:

```php
final class FiberEngine implements EngineContract
{
    private ?Socket $serverSocket = null;
    private SplObjectStorage $sockets;    // Socket -> Connection
    private SplObjectStorage $fibers;     // Connection -> Fiber

    public function listen(string $host, int $port): void
    {
        $this->serverSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->serverSocket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_nonblock($this->serverSocket);
        socket_bind($this->serverSocket, $host, $port);
        socket_listen($this->serverSocket);
    }

    public function run(): void
    {
        $this->running = true;
        while ($this->running) {
            $this->tick();
        }
    }

    private function tick(): void
    {
        // Use stream_select for non-blocking I/O
        $read = [$this->serverSocket, ...array_keys($this->sockets)];
        socket_select($read, $write, $except, 0, 100000);

        foreach ($read as $socket) {
            if ($socket === $this->serverSocket) {
                $this->acceptConnection();
            } else {
                $this->handleSocketData($socket);
            }
        }

        $this->processFibers(); // Resume suspended fibers
    }
}
```

### Server - High-Level WebSocket Server

**Location:** `src/Larafony/WebSockets/Server.php`

Orchestrates engine, handles protocol, and dispatches events:

```php
final class Server implements ServerContract
{
    private SplObjectStorage $connections;
    private EventDispatcher $eventDispatcher;

    public function __construct(
        private readonly EngineContract $engine,
        private readonly string $host = '0.0.0.0',
        private readonly int $port = 8080,
    ) {
        $this->setupEngineHandlers();
    }

    public function on(string $event, callable $callback): void
    {
        $this->eventDispatcher->addListener($event, $callback);
    }

    public function broadcast(string $message, ?Closure $filter = null): void
    {
        foreach ($this->connections as $connection) {
            if ($filter === null || $filter($connection)) {
                $connection->send($message);
            }
        }
    }

    private function handleWebSocketFrame(ConnectionContract $conn, string $data): void
    {
        $frame = Decoder::decode($data);

        match ($frame->opcode) {
            Opcode::TEXT, Opcode::BINARY => $this->handleMessage($conn, $frame),
            Opcode::PING => $conn->send(Frame::pong($frame->payload)),
            Opcode::CLOSE => $this->handleCloseFrame($conn, $frame),
            default => null,
        };
    }
}
```

### WebSocketServiceProvider - DI Registration

**Location:** `src/Larafony/WebSockets/ServiceProviders/WebSocketServiceProvider.php`

```php
class WebSocketServiceProvider extends ServiceProvider
{
    public function providers(): array
    {
        return [
            EngineContract::class => FiberEngine::class,
        ];
    }

    public function boot(ContainerContract $container): void
    {
        $config = $container->get(ConfigContract::class);

        $host = $config->get('websocket.host', '0.0.0.0');
        $port = (int) $config->get('websocket.port', 8080);

        $engine = $container->get(EngineContract::class);
        $server = new Server($engine, $host, $port);

        $this->registerDefaultHandlers($server);

        $container->set(ServerContract::class, $server);
    }

    protected function registerDefaultHandlers(ServerContract $server): void
    {
        // Override in subclass to register application handlers
    }
}
```

## Console Commands

### websocket:start

**Location:** `src/Larafony/Console/Commands/WebSocketStart.php`

Starts the WebSocket server with optional host/port override:

```php
#[AsCommand(name: 'websocket:start')]
class WebSocketStart extends Command
{
    #[CommandOption(name: 'host', description: 'The host to bind to')]
    protected ?string $host = null;

    #[CommandOption(name: 'port', description: 'The port to listen on')]
    protected ?int $port = null;

    public function run(): int
    {
        $server = $this->container->get(ServerContract::class);

        $this->output->info("Starting WebSocket server...");
        $this->registerLoggingHandlers($server);

        $server->run();
        return 0;
    }
}
```

Usage:
```bash
# Default (from config)
php bin/larafony websocket:start

# Custom host/port
php bin/larafony websocket:start --host=127.0.0.1 --port=9000
```

## Bridge Package: larafony/websocket-react

For high-performance production environments, the `larafony/websocket-react` bridge provides a ReactPHP-based engine.

### Installation

```bash
composer require larafony/websocket-react
```

### ReactEngine Implementation

```php
final class ReactEngine implements EngineContract
{
    private LoopInterface $loop;
    private ?SocketServer $server = null;
    private SplObjectStorage $connections;

    public function __construct(?LoopInterface $loop = null)
    {
        $this->loop = $loop ?? Loop::get();
    }

    public function listen(string $host, int $port): void
    {
        $this->server = new SocketServer("{$host}:{$port}", [], $this->loop);

        $this->server->on('connection', function (ConnectionInterface $conn) {
            $this->handleConnection($conn);
        });
    }

    public function run(): void
    {
        $this->loop->run();
    }
}
```

### Usage

Simply register `ReactWebSocketServiceProvider` instead of the core provider:

```php
// In your application bootstrap
$providers = [
    // ...
    ReactWebSocketServiceProvider::class, // Instead of WebSocketServiceProvider
];
```

## Usage Examples

### Basic Echo Server

```php
use Larafony\Framework\WebSockets\Engine\FiberEngine;
use Larafony\Framework\WebSockets\Server;

$server = new Server(new FiberEngine(), '0.0.0.0', 8080);

$server->on('open', function ($data, $connection) {
    echo "Client connected: {$connection->getId()}\n";
});

$server->on('message', function ($payload, $connection) {
    $connection->send("Echo: {$payload}");
});

$server->on('close', function ($data, $connection) {
    echo "Client disconnected: {$connection->getId()}\n";
});

$server->run();
```

### Chat Room with Broadcast

```php
$server->on('message', function ($payload, $connection, $dispatcher) use ($server) {
    $data = json_decode($payload, true);

    if ($data['type'] === 'chat') {
        // Broadcast to all except sender
        $server->broadcast(
            json_encode(['type' => 'chat', 'message' => $data['message']]),
            fn($conn) => $conn->getId() !== $connection->getId()
        );
    }
});
```

### Custom Event Handling

```php
// Client sends: {"event": "user_typing", "data": {"userId": 123}}

$server->on('user_typing', function ($data, $connection) use ($server) {
    $server->broadcast(
        json_encode(['event' => 'typing_indicator', 'userId' => $data['userId']]),
        fn($conn) => $conn->getId() !== $connection->getId()
    );
});
```

### Integration with AI (OpenAI Example)

```php
class ChatAIProvider extends WebSocketServiceProvider
{
    protected function registerDefaultHandlers(ServerContract $server): void
    {
        $server->on('chat_message', new ChatMessageListener(
            $this->container->get(ConfigContract::class)
        ));
    }
}

class ChatMessageListener
{
    public function __invoke($data, ConnectionContract $connection): void
    {
        $message = $data['message'] ?? '';
        $response = $this->callOpenAI($message);

        $connection->send(Frame::text(json_encode([
            'event' => 'ai_response',
            'data' => $response,
        ])));
    }
}
```

## Configuration

### config/websocket.php

```php
return [
    'host' => env('WEBSOCKET_HOST', '0.0.0.0'),
    'port' => (int) env('WEBSOCKET_PORT', 8080),
];
```

### .env

```env
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8080
```

## Performance Comparison

| Aspect | FiberEngine (Core) | ReactEngine (Bridge) |
|--------|-------------------|---------------------|
| Dependencies | None (ext-sockets) | react/event-loop, react/socket |
| Event Loop | stream_select() | libuv/libev |
| Concurrency | PHP Fibers | Callbacks + Promises |
| Memory | Lower baseline | Higher with many connections |
| Throughput | Good (~1000 conn) | Excellent (~10000+ conn) |
| Best For | Simple apps, learning | Production, high-scale |

## Testing

The WebSocket system includes comprehensive tests:

```bash
# Core tests (70 tests)
composer test -- tests/Larafony/WebSockets

# Bridge tests
cd bridges/larafony-ws && composer test
```

Test coverage includes:
- Protocol encoding/decoding (Frame, Encoder, Decoder)
- Handshake validation and response generation
- Connection lifecycle management
- Event dispatching
- Engine abstraction

## Practical Example: AI Chat in demo-app 🤖

A complete demonstration of WebSocket integration with AI, showing the full flow from Vue frontend through WebSocket to backend calling OpenAI API.

### Controller (Inertia)

**Location:** `demo-app/src/Controllers/ChatAIController.php`

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Larafony\Framework\Http\Controllers\Controller;
use Larafony\Framework\Contracts\Config\ConfigContract;

class ChatAIController extends Controller
{
    public function index(ConfigContract $config): \Inertia\Response
    {
        return inertia('Chat/Index', [
            'wsHost' => $config->get('websocket.host', 'localhost'),
            'wsPort' => $config->get('websocket.port', 8080),
        ]);
    }
}
```

### Message Listener

**Location:** `demo-app/src/Listeners/ChatMessageListener.php`

Receives WebSocket messages, calls OpenAI API via PSR-18 HTTP Client, and sends back responses:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Larafony\Framework\Contracts\Config\ConfigContract;
use Larafony\Framework\WebSockets\Contracts\ConnectionContract;
use Larafony\Framework\WebSockets\Protocol\Frame;
use Larafony\Framework\Http\Client\CurlHttpClient;

class ChatMessageListener
{
    public function __construct(
        private readonly ConfigContract $config,
    ) {}

    public function __invoke(array $data, ConnectionContract $connection): void
    {
        $message = $data['message'] ?? '';

        if (empty($message)) {
            return;
        }

        $response = $this->callOpenAI($message);

        $connection->send(Frame::text(json_encode([
            'event' => 'ai_response',
            'data' => [
                'message' => $response,
                'timestamp' => time(),
            ],
        ])));
    }

    private function callOpenAI(string $message): string
    {
        $client = new CurlHttpClient();
        $apiKey = $this->config->get('openai.api_key');

        $request = new \Larafony\Framework\Http\Request(
            'POST',
            new \Larafony\Framework\Http\Uri('https://api.openai.com/v1/chat/completions'),
            ['Content-Type' => 'application/json', 'Authorization' => "Bearer {$apiKey}"],
            json_encode([
                'model' => $this->config->get('openai.model', 'gpt-4'),
                'messages' => [
                    ['role' => 'user', 'content' => $message],
                ],
            ])
        );

        $response = $client->sendRequest($request);
        $body = json_decode((string) $response->getBody(), true);

        return $body['choices'][0]['message']['content'] ?? 'Error processing request';
    }
}
```

### ServiceProvider

**Location:** `demo-app/src/Providers/ChatWebSocketProvider.php`

Extends WebSocketServiceProvider to register the chat_message event handler:

```php
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\ChatMessageListener;
use Larafony\Framework\WebSockets\Contracts\ServerContract;
use Larafony\Framework\WebSockets\ServiceProviders\WebSocketServiceProvider;

class ChatWebSocketProvider extends WebSocketServiceProvider
{
    protected function registerDefaultHandlers(ServerContract $server): void
    {
        $listener = $this->container->get(ChatMessageListener::class);

        $server->on('chat_message', $listener);

        $server->on('open', fn($data, $conn) =>
            $conn->send(json_encode(['event' => 'welcome', 'data' => 'Connected to AI Chat']))
        );
    }
}
```

### Vue Component

**Location:** `demo-app/resources/js/Pages/Chat/Index.vue`

```vue
<template>
  <div class="chat-container">
    <div class="messages" ref="messagesContainer">
      <div v-for="(msg, index) in messages" :key="index"
           :class="['message', msg.type]">
        <div class="content">{{ msg.text }}</div>
        <div class="timestamp">{{ formatTime(msg.timestamp) }}</div>
      </div>
    </div>

    <form @submit.prevent="sendMessage" class="input-form">
      <input v-model="newMessage"
             placeholder="Ask AI something..."
             :disabled="!connected || loading" />
      <button type="submit" :disabled="!connected || loading || !newMessage.trim()">
        {{ loading ? 'Thinking...' : 'Send' }}
      </button>
    </form>

    <div class="status" :class="{ connected }">
      {{ connected ? '🟢 Connected' : '🔴 Disconnected' }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';

const props = defineProps(['wsHost', 'wsPort']);

const messages = ref([]);
const newMessage = ref('');
const connected = ref(false);
const loading = ref(false);
let ws = null;

onMounted(() => {
  connectWebSocket();
});

onUnmounted(() => {
  ws?.close();
});

function connectWebSocket() {
  ws = new WebSocket(`ws://${props.wsHost}:${props.wsPort}`);

  ws.onopen = () => {
    connected.value = true;
  };

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data);

    if (data.event === 'ai_response') {
      loading.value = false;
      messages.value.push({
        type: 'ai',
        text: data.data.message,
        timestamp: data.data.timestamp,
      });
      scrollToBottom();
    }
  };

  ws.onclose = () => {
    connected.value = false;
    setTimeout(connectWebSocket, 3000);
  };
}

function sendMessage() {
  if (!newMessage.value.trim() || !connected.value) return;

  messages.value.push({
    type: 'user',
    text: newMessage.value,
    timestamp: Date.now() / 1000,
  });

  ws.send(JSON.stringify({
    event: 'chat_message',
    data: { message: newMessage.value },
  }));

  loading.value = true;
  newMessage.value = '';
  scrollToBottom();
}

function scrollToBottom() {
  nextTick(() => {
    const container = document.querySelector('.messages');
    container.scrollTop = container.scrollHeight;
  });
}

function formatTime(timestamp) {
  return new Date(timestamp * 1000).toLocaleTimeString();
}
</script>
```

### Data Flow

1. User types message in Vue component
2. Vue sends JSON via WebSocket: `{"event": "chat_message", "data": {"message": "..."}}`
3. Server dispatches `chat_message` event to ChatMessageListener
4. Listener calls OpenAI API via PSR-18 CurlHttpClient
5. AI response is sent back via WebSocket
6. Vue receives and displays response in real-time

The entire flow works without page reload, with instant response thanks to persistent WebSocket connection.

## Framework Comparison 🔥

How does Larafony's WebSocket implementation compare to other PHP frameworks?

### Laravel Reverb

Laravel introduced Reverb in 2024 as its first-party WebSocket solution:

| Aspect | Laravel Reverb | Larafony |
|--------|---------------|----------|
| Dependencies | Ratchet, Redis (for scaling) | Zero (core), ReactPHP (optional bridge) |
| Protocol | Pusher protocol | Native RFC 6455 |
| Architecture | Separate Reverb server process | Integrated into framework |
| Scaling | Requires Redis pub/sub | Built-in broadcast, optional React for scale |
| Learning Curve | Pusher concepts, channels, events | Simple `on('event', callback)` API |
| External Services | Often paired with Pusher/Soketi | Fully self-contained |

**Larafony advantage:** No external services, no Pusher protocol abstraction, no Redis requirement. Just pure WebSockets with a clean, minimal API.

### Symfony

Symfony does not include a built-in WebSocket solution:

| Aspect | Symfony | Larafony |
|--------|---------|----------|
| Native Support | ❌ None | ✅ Full RFC 6455 |
| Recommended Solution | Mercure (SSE, not WebSockets) or third-party Ratchet | Native FiberEngine or ReactEngine |
| Protocol | Mercure uses Server-Sent Events | True bidirectional WebSockets |
| Integration | Manual setup required | ServiceProvider, console command included |
| Real-time | One-way (SSE) or external package | True bidirectional |

**Larafony advantage:** First-class WebSocket support built from scratch, not delegated to external projects or limited to Server-Sent Events.

### Why Larafony WebSockets Stand Out

1. **Zero Dependencies** - Core implementation uses only PHP 8.5 Fibers and ext-sockets. No composer packages required for basic functionality.

2. **RFC 6455 From Scratch** - Complete protocol implementation (Frame, Encoder, Decoder, Handshake) that you can learn from and extend.

3. **Swappable Engines** - Start with FiberEngine for development, switch to ReactEngine for production - same API, same handlers.

4. **No External Services** - Unlike Laravel's common Pusher/Redis setup, Larafony WebSockets are fully self-contained.

5. **Simple Mental Model** - No channels, no presence, no Pusher protocol. Just connections, events, and broadcasts.

6. **Educational Value** - Every line of code is readable and follows the RFC specification. Perfect for learning how WebSockets actually work.

```php
// That's it. No Redis, no Pusher, no external services.
$server = new Server(new FiberEngine(), '0.0.0.0', 8080);
$server->on('message', fn($data, $conn) => $conn->send("Echo: $data"));
$server->run();
```

## Summary

This chapter delivered a complete, production-ready WebSocket implementation:

1. **RFC 6455 Compliant** - Full protocol support with proper framing and handshakes
2. **Zero Dependencies** - Core implementation uses only PHP 8.5 features
3. **Swappable Engines** - Easy upgrade path from Fibers to ReactPHP
4. **Service Provider Pattern** - Customizable handlers through inheritance
5. **Console Command** - Simple `websocket:start` for running servers
6. **Bridge Package** - Optional `larafony/websocket-react` for production scale

The architecture demonstrates Larafony's commitment to clean abstractions and PSR-style interfaces while maintaining practical usability for real-world applications.
