# Docker Setup for Larafony Framework

This document describes how to use Docker for development and testing of the Larafony Framework.

## Prerequisites

- Docker (v20.10 or higher)
- Docker Compose (v2.0 or higher)

## Quick Start

You can use either the `docker.sh` shell script or `make` commands (both provide the same functionality).

### 1. Build Docker Images

```bash
./docker.sh build
# or
make build
```

This will build three Docker images:
- `larafony-framework:dev` - Development image with all dependencies
- `larafony-framework:test` - Test image with PCOV (built from source) for coverage
- `larafony-framework:quality` - Quality checks image

**Note:** PCOV is built from source for PHP 8.5 compatibility. It's significantly faster than Xdebug for code coverage.

### 2. Start Services

```bash
./docker.sh up
# or
make up
```

This starts the MySQL database service in the background.

### 3. Run Tests

```bash
# Quick test script (recommended)
./docker-test.sh              # Run tests without coverage
./docker-test.sh --coverage   # Run tests with HTML coverage report
./docker-test.sh --text       # Run tests with text coverage output

# Or use docker.sh / make
./docker.sh test              # Run tests without coverage
./docker.sh test-coverage     # Run tests with text coverage
./docker.sh test-html         # Run tests with HTML coverage report

# Or use make
make test                     # Run tests without coverage
make test-coverage            # Run tests with text coverage
make test-html                # Run tests with HTML coverage report
```

**Note:** The `docker-test.sh` script is recommended as it automatically:
- Cleans up old containers
- Rebuilds images if needed
- Starts MySQL and waits for it to be ready
- Runs tests with the specified coverage option

## Available Commands

You can use either `./docker.sh [command]` or `make [command]`. Both provide the same functionality:

### Build & Start
- `./docker.sh build` - Build Docker images
- `./docker.sh up` - Start all services
- `./docker.sh down` - Stop all services
- `./docker.sh restart` - Restart all services

### Testing
- `./docker.sh test` - Run PHPUnit tests without coverage
- `./docker.sh test-coverage` - Run tests with text coverage output
- `./docker.sh test-html` - Generate HTML coverage report in `coverage/`

### Code Quality
- `./docker.sh quality` - Run all quality checks (PHPStan + Tests + PHP Insights)
- `./docker.sh analyse` - Run PHPStan static analysis
- `./docker.sh insights` - Run PHP Insights code quality analysis

### Development
- `./docker.sh shell` - Open shell in the app container
- `./docker.sh mysql` - Open MySQL shell
- `./docker.sh logs` - Show logs from all containers

### Cleanup
- `./docker.sh clean` - Remove all containers, volumes, and images

## Docker Compose Services

### app
The main application container for running tests without coverage.

**Usage:**
```bash
docker-compose run --rm app php vendor/bin/phpunit --no-coverage
```

### test
Test container with Xdebug enabled for code coverage.

**Usage:**
```bash
docker-compose run --rm test php vendor/bin/phpunit
```

### quality
Container for running all quality checks.

**Usage:**
```bash
docker-compose run --rm quality
```

### mysql
MySQL 8.0 database for testing.

**Connection details:**
- Host: `mysql` (inside Docker network) or `localhost:33306` (from host)
- Database: `larafony_test`
- Username: `larafony`
- Password: `secret`
- Root password: `root`

## Manual Docker Commands

If you prefer to use Docker Compose directly:

```bash
# Build images
docker-compose build

# Start MySQL
docker-compose up -d mysql

# Run tests
docker-compose run --rm app php vendor/bin/phpunit --no-coverage

# Run tests with coverage
docker-compose run --rm test php vendor/bin/phpunit

# Run quality checks
docker-compose run --rm quality

# Stop all services
docker-compose down

# Remove all data
docker-compose down -v
```

## Volumes

The setup uses the following Docker volumes:

- `vendor` - Composer dependencies (named volume, persisted for faster rebuilds)
- `cache` - PHPUnit cache directory (named volume)
- `mysql_data` - MySQL database files (named volume)

**Note:** Coverage reports are stored directly on the host filesystem in the `coverage/` directory (bind mount), not in a Docker volume. This allows easy access from your browser.

## Environment Variables

The following environment variables are configured in `docker-compose.yml`:

```yaml
PHP_VERSION=8.5
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=larafony_test
DB_USERNAME=larafony
DB_PASSWORD=secret
```

You can override these by creating a `.env` file or modifying `docker-compose.yml`.

## Code Coverage with PCOV

The test container uses **PCOV** instead of Xdebug for code coverage. PCOV is:

- **Faster**: 2-3x faster than Xdebug for coverage collection
- **Built from source**: Compiled specifically for PHP 8.5 compatibility
- **Optimized**: Configured to only scan the `src/` directory
- **Lightweight**: No debugging overhead, pure coverage driver

### PCOV Configuration

PCOV is configured in the Dockerfile with:

```ini
pcov.enabled=1
pcov.directory=/app/src
pcov.exclude="~vendor~"
```

### Why PCOV?

For PHP 8.5, PECL packages may not be available yet, so PCOV is built from the official GitHub repository during the Docker build process. This ensures:

1. Full PHP 8.5 compatibility
2. Latest PCOV features
3. Optimal performance for test coverage
4. No external dependencies

## Troubleshooting

### Tests Failing Due to Database Connection

If tests fail with database connection errors:

1. Ensure MySQL is running:
   ```bash
   docker-compose ps
   ```

2. Check MySQL health:
   ```bash
   docker-compose exec mysql mysqladmin ping -u root -proot
   ```

3. Wait for MySQL to be ready (usually takes 5-10 seconds after starting)

### Permission Issues

If you encounter permission issues with volumes:

```bash
# Fix permissions on host
chmod -R 777 .phpunit.cache coverage

# Or rebuild without volumes
docker-compose down -v
docker-compose build --no-cache
```

### Out of Memory

If PHPStan runs out of memory:

```bash
# Increase memory limit
docker-compose run --rm app php -d memory_limit=1G vendor/bin/phpstan analyse
```

### Clean Start

To completely reset the Docker environment:

```bash
./docker.sh clean
./docker.sh build
./docker.sh up
```

## CI/CD Integration

For CI/CD pipelines, you can use these commands:

```bash
# Build and test
docker-compose build
docker-compose run --rm app php vendor/bin/phpunit --no-coverage

# Full quality check
docker-compose run --rm quality
```

Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build Docker images
        run: docker-compose build
      - name: Run tests
        run: docker-compose run --rm app php vendor/bin/phpunit --no-coverage
      - name: Run quality checks
        run: docker-compose run --rm quality
```

## Development Workflow

Recommended workflow for development with Docker:

1. Start services:
   ```bash
   ./docker.sh up
   ```

2. Make your changes to the code

3. Run tests:
   ```bash
   ./docker.sh test
   ```

4. Check code quality:
   ```bash
   ./docker.sh analyse
   ```

5. Before committing, run full quality check:
   ```bash
   ./docker.sh quality
   ```

6. Stop services when done:
   ```bash
   ./docker.sh down
   ```

## Notes

- The Dockerfile uses multi-stage builds to optimize image size
- PHP 8.5 CLI Alpine image is used for minimal footprint
- Xdebug is only installed in the test stage for coverage
- All containers run as the current user to avoid permission issues
- Composer dependencies are cached in a named volume for faster rebuilds
