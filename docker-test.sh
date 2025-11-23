#!/bin/bash

# Quick test script for Docker setup
# This script will clean orphans, start MySQL, wait for it, and run tests
#
# Usage:
#   ./docker-test.sh              - Run tests without coverage
#   ./docker-test.sh --coverage   - Run tests with HTML coverage report
#   ./docker-test.sh --text       - Run tests with text coverage output

set -e

# Parse arguments
COVERAGE_MODE="none"
if [[ "$1" == "--coverage" ]]; then
    COVERAGE_MODE="html"
elif [[ "$1" == "--text" ]]; then
    COVERAGE_MODE="text"
fi

# Detect if we need sudo for docker commands
DOCKER_COMPOSE_CMD="docker-compose"
if ! docker ps >/dev/null 2>&1; then
    if sudo docker ps >/dev/null 2>&1; then
        DOCKER_COMPOSE_CMD="sudo docker-compose"
    fi
fi

echo "🧹 Stopping existing containers and cleaning up..."
$DOCKER_COMPOSE_CMD down --remove-orphans 2>/dev/null || true

echo "🗑️  Removing old volumes (if exist)..."
$DOCKER_COMPOSE_CMD down -v 2>/dev/null || true

echo "📁 Ensuring coverage directory exists on host..."
mkdir -p coverage
chmod 777 coverage

echo "🔨 Building Docker images (if needed)..."
$DOCKER_COMPOSE_CMD build test mysql

echo "🚀 Starting fresh MySQL service..."
$DOCKER_COMPOSE_CMD up -d mysql

echo "⏳ Waiting for MySQL to be ready..."
for i in {1..30}; do
    if $DOCKER_COMPOSE_CMD exec -T mysql mysqladmin ping -h localhost -u root -proot >/dev/null 2>&1; then
        echo "✅ MySQL is ready!"
        break
    fi
    echo "   Attempt $i/30..."
    sleep 2
done

echo ""
if [[ "$COVERAGE_MODE" == "html" ]]; then
    echo "🧪 Running tests with HTML coverage report..."
    $DOCKER_COMPOSE_CMD run --rm test php vendor/bin/phpunit --coverage-html coverage
    echo ""
    echo "📊 Coverage report generated!"
    echo "   📂 Location: $(pwd)/coverage/index.html"
    echo "   🌐 Open in browser: file://$(pwd)/coverage/index.html"
    if command -v xdg-open &> /dev/null; then
        echo ""
        read -p "   Open in browser now? (y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            xdg-open "$(pwd)/coverage/index.html" 2>/dev/null || echo "   ⚠️  Could not open browser automatically"
        fi
    fi
elif [[ "$COVERAGE_MODE" == "text" ]]; then
    echo "🧪 Running tests with text coverage output..."
    $DOCKER_COMPOSE_CMD run --rm test php vendor/bin/phpunit --coverage-text
else
    echo "🧪 Running tests without coverage..."
    $DOCKER_COMPOSE_CMD run --rm test php vendor/bin/phpunit --no-coverage
fi

echo ""
echo "✅ All done!"
