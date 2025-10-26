# Chapter 18 - Publishing the Framework


> Part of the Larafony Framework - A modern PHP 8.5 framework built from scratch
>
> 📚 Full implementation details with tests available at [masterphp.eu](https://masterphp.eu)


## Overview

This chapter covers the final step in the framework development journey - publishing `larafony/core` as a standalone package and creating a separate application skeleton (`larafony/skeleton`) for developers to use.

## What Changed

### Package Restructuring

**Framework Package (`larafony/core`)**
- Renamed from `larafony/framework` to `larafony/core`
- Removed demo application from the repository
- Added `provide` section to `composer.json` declaring PSR implementation compatibility
- Framework now contains only core library code

**Application Skeleton (`larafony/skeleton`)**
- Extracted to a separate repository
- Contains application boilerplate (bootstrap, config, public, resources)
- Requires `larafony/core` as a dependency
- Provides ready-to-use demo application showing framework features

### Composer Metadata

Added `provide` section to declare PSR standard implementations:

```json
"provide": {
    "psr/container-implementation": "2.0",
    "psr/http-message-implementation": "2.0",
    "psr/http-factory-implementation": "1.1",
    "psr/http-server-handler-implementation": "1.0",
    "psr/http-server-middleware-implementation": "1.0",
    "psr/http-client-implementation": "1.0",
    "psr/log-implementation": "3.0",
    "psr/clock-implementation": "1.0"
}
```

This tells Composer that Larafony provides implementations of these PSR interfaces, allowing other packages to depend on `psr/*-implementation` instead of concrete packages.

### Final Bug Fixes

**Blade Template Engine**
- Fixed nested directive compilation (components with @if, @foreach inside)
- Corrected directive processing order (control flow before components)
- ComponentDirective now has access to full compiler for recursive compilation

**Base Controller**
- Added `redirect(string $url, int $status = 301)` helper method

## Repository Structure

### Before (Monorepo)
```
larafony/framework/
├── src/Larafony/          # Framework code
├── tests/Larafony/        # Framework tests
├── demo-app/              # Demo application
│   ├── src/
│   ├── config/
│   ├── public/
│   └── resources/
└── composer.json
```

### After (Split Repositories)
```
larafony/core/             # Framework package
├── src/Larafony/
├── tests/Larafony/
└── composer.json

larafony/skeleton/         # Application skeleton (separate repo)
├── src/
├── config/
├── public/
├── resources/
└── composer.json          # requires: larafony/core
```

## Publishing Checklist

### Framework Package (`larafony/core`)

1. ✅ Clean repository structure (no demo app)
2. ✅ Proper package name (`larafony/core`)
3. ✅ PSR implementation declarations (`provide`)
4. ✅ All tests passing
5. ✅ Code quality checks passing (95%+ on all metrics)
6. ✅ README with installation instructions
7. ✅ LICENSE file (MIT)
8. ✅ Comprehensive documentation

### Application Skeleton (`larafony/skeleton`)

1. ✅ Requires `larafony/core`
2. ✅ Working demo application (notes CRUD)
3. ✅ Example models, controllers, DTOs
4. ✅ Blade templates and components
5. ✅ Database migrations and seeders
6. ✅ Console commands
7. ✅ Configuration files
8. ✅ README with quick start guide

## Installation

### Creating New Project

```bash
composer create-project larafony/skeleton my-app
cd my-app
php bin/larafony migrate
php bin/larafony seed
php -S localhost:8000 -t public
```

### Using Core Package Only

```bash
composer require larafony/core
```

## Why Split the Repository?

### Benefits

1. **Clear Separation of Concerns**
   - Framework developers work on `larafony/core`
   - Application developers use `larafony/skeleton`

2. **Faster Framework Updates**
   - Users run `composer update larafony/core`
   - No need to sync demo app changes

3. **Cleaner Package**
   - Framework package is lean (no app boilerplate)
   - Application skeleton is complete (no framework internals)

4. **Better Versioning**
   - Framework and skeleton can have independent versions
   - Breaking changes in skeleton don't require framework version bump

5. **Professional Structure**
   - Matches industry standards (Laravel: laravel/framework + laravel/laravel)
   - Clear package purpose and boundaries

## Version Numbering

### Framework (`larafony/core`)
- `v0.9.0-beta` - Current release (Chapter 17 + fixes)
- `v1.0.0-rc1` - Release candidate (after final polish)
- `v1.0.0` - Stable release

### Skeleton (`larafony/skeleton`)
- Independent versioning
- Always requires compatible `larafony/core` version

## Next Steps

After publishing:

1. **Documentation website** - Full API documentation and guides
2. **Community** - GitHub issues, discussions, contributing guidelines
3. **CI/CD** - Automated testing and releases
4. **Packagist** - Publish to packagist.org
5. **Promotion** - Blog posts, social media, showcases

## Summary

Chapter 18 completes the framework development journey by:
- Restructuring the project into professional packages
- Declaring PSR standard implementations
- Fixing final bugs in Blade compilation
- Preparing for public release on Packagist

The framework is now ready for production use and community contributions.

---

## 🧭 Further Development Roadmap

The journey doesn't end here. The following chapters will expand Larafony into a complete, production-ready framework:

### 🎨 View Layer (SPA)
- **Chapter 19** - Inertia.js Middleware (Vue.js integration)
  - SPA without API complexity
  - Server-side routing with client-side rendering
  - Shared data and props

### 💥 Error Handling
- **Chapter 20** - Advanced Web Error Handling
  - Custom error pages
  - Exception handlers
  - Debug mode with stack traces

- **Chapter 21** - Advanced Console Error Handling
  - Formatted error output
  - Exit codes
  - Error recovery strategies

### 🔐 Security & Communication
- **Chapter 22** - Encrypted Cookies and Sessions
  - Secure session management
  - CSRF protection
  - Cookie encryption

- **Chapter 23** - Sending Emails
  - SMTP integration
  - Email templates
  - Queue support

- **Chapter 24** - Authorization System
  - Gates and policies
  - Role-based access control
  - Middleware guards

### ⚡ Performance & Async
- **Chapter 25** - Cache Optimization (PSR-6)
  - File-based caching
  - Redis adapter
  - Cache tags and invalidation

- **Chapter 26** - Event System (PSR-14 and alternatives)
  - Event dispatcher
  - Listeners and subscribers
  - Async event handling

- **Chapter 27** - Jobs and Queues
  - Background job processing
  - Queue workers
  - Failed job handling

- **Chapter 28** - Simple WebSockets (almost from scratch)
  - Real-time communication
  - WebSocket server
  - Broadcasting

- **Chapter 29** - MCP - A new way of communication
  - Modern communication patterns
  - API alternatives

### 🧭 Meta & Integration
- **Chapter 30** - Creating Larafony Installer
  - Project scaffolding
  - Interactive CLI setup
  - Template generation

- **Chapter 31** - Demo Project: (Very) Simple Web Store
  - Complete e-commerce example
  - Payment integration
  - Order management

- **Chapter 32** - Why Larafony - Comparing with Laravel, Symfony, CodeIgniter
  - Performance benchmarks
  - Architecture comparison
  - Use case analysis

### 🧩 The Philosophy of the Final Chapters

**This will be updated while following packages reach FULL PHP 8.5 support.**

Larafony's journey doesn't end with writing code — it ends with understanding.

The last chapters are not about adding features, but about **liberating the developer**.
They show that every component — clock, container, logger, cache, or view — is *optional*, replaceable, and interchangeable.

Each replacement (Carbon, Monolog, Laravel Container, Twig, etc.) isn't a "plugin", but a **lesson**:
> how professional PHP code achieves the same result through different abstractions.

By the time you reach the end, you won't just *use* a framework —
you'll **understand the architecture behind every framework**.

> "The best framework is the one you can replace piece by piece — because you understand it completely."

### ⚙️ Extending with Mature Libraries
- **Chapter 33** - View Bridges (Twig & Smarty)
  - Template engine adapters
  - Custom directive support
  - Performance comparison

- **Chapter 34** - Use Carbon instead of ClockFactory
  - DateTime manipulation
  - Timezone handling
  - Human-readable dates

- **Chapter 35** - Use Monolog
  - Advanced logging
  - Multiple handlers
  - Log rotation

- **Chapter 36** - Replace Container with Laravel Container
  - Service providers
  - Deferred loading
  - Contextual binding

---

🧠 **Larafony is not just a framework.**
It's an open architecture, a teaching tool, and a manifesto of modern PHP.

Every line of code exists to remind you that:
- elegance is a function of simplicity,
- performance is a side effect of clarity, and
- real mastery means knowing when to write less.

Welcome to the end of the framework —
and the beginning of **your own**.

---

📚 **Learn More:** This implementation is explained in detail with step-by-step tutorials, tests, and best practices at [masterphp.eu](https://masterphp.eu)
