#!/bin/bash

# Docker helper script for Larafony Framework
# Usage: ./docker.sh [command]

set -e

# Detect if we need sudo for docker commands
DOCKER_CMD="docker"
DOCKER_COMPOSE_CMD="docker-compose"

if ! docker ps >/dev/null 2>&1; then
    if sudo docker ps >/dev/null 2>&1; then
        DOCKER_CMD="sudo docker"
        DOCKER_COMPOSE_CMD="sudo docker-compose"
    fi
fi

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

print_help() {
    echo -e "${BLUE}Larafony Framework Docker Helper${NC}"
    echo ""
    echo "Usage: ./docker.sh [command]"
    echo ""
    echo "Commands:"
    echo "  build         - Build Docker images"
    echo "  up            - Start all services"
    echo "  down          - Stop all services"
    echo "  test          - Run tests without coverage"
    echo "  test-coverage - Run tests with coverage"
    echo "  test-html     - Run tests with HTML coverage report"
    echo "  quality       - Run all quality checks (analyse + test + insights)"
    echo "  analyse       - Run PHPStan static analysis"
    echo "  insights      - Run PHP Insights"
    echo "  shell         - Open shell in app container"
    echo "  mysql         - Open MySQL shell"
    echo "  logs          - Show logs from all containers"
    echo "  clean         - Remove all containers, volumes and images"
    echo "  clean-orphans - Remove orphaned containers"
    echo "  restart       - Restart all services"
    echo ""
}

case "$1" in
    build)
        echo -e "${GREEN}Building Docker images...${NC}"
        $DOCKER_COMPOSE_CMD build
        ;;
    up)
        echo -e "${GREEN}Starting services...${NC}"
        $DOCKER_COMPOSE_CMD up -d mysql
        echo -e "${BLUE}Waiting for MySQL to be ready...${NC}"
        sleep 10
        ;;
    down)
        echo -e "${GREEN}Stopping services...${NC}"
        $DOCKER_COMPOSE_CMD down
        ;;
    test)
        echo -e "${GREEN}Running tests without coverage...${NC}"
        $DOCKER_COMPOSE_CMD run --rm app php vendor/bin/phpunit --no-coverage
        ;;
    test-coverage)
        echo -e "${GREEN}Running tests with coverage...${NC}"
        $DOCKER_COMPOSE_CMD run --rm test php vendor/bin/phpunit --coverage-text
        ;;
    test-html)
        echo -e "${GREEN}Running tests with HTML coverage report...${NC}"
        $DOCKER_COMPOSE_CMD run --rm test php vendor/bin/phpunit --coverage-html coverage
        echo -e "${BLUE}Coverage report generated in coverage/index.html${NC}"
        ;;
    quality)
        echo -e "${GREEN}Running all quality checks...${NC}"
        $DOCKER_COMPOSE_CMD run --rm quality
        ;;
    analyse)
        echo -e "${GREEN}Running PHPStan analysis...${NC}"
        $DOCKER_COMPOSE_CMD run --rm app php vendor/bin/phpstan analyse --memory-limit=512M
        ;;
    insights)
        echo -e "${GREEN}Running PHP Insights...${NC}"
        $DOCKER_COMPOSE_CMD run --rm app php vendor/bin/phpinsights
        ;;
    shell)
        echo -e "${GREEN}Opening shell in app container...${NC}"
        $DOCKER_COMPOSE_CMD run --rm app sh
        ;;
    mysql)
        echo -e "${GREEN}Opening MySQL shell...${NC}"
        $DOCKER_COMPOSE_CMD exec mysql mysql -u larafony -psecret larafony_test
        ;;
    logs)
        echo -e "${GREEN}Showing logs...${NC}"
        $DOCKER_COMPOSE_CMD logs -f
        ;;
    clean)
        echo -e "${RED}Removing all containers, volumes and images...${NC}"
        $DOCKER_COMPOSE_CMD down -v --rmi all
        ;;
    clean-orphans)
        echo -e "${GREEN}Removing orphaned containers...${NC}"
        $DOCKER_COMPOSE_CMD down --remove-orphans
        ;;
    restart)
        echo -e "${GREEN}Restarting services...${NC}"
        $DOCKER_COMPOSE_CMD down
        $DOCKER_COMPOSE_CMD up -d mysql
        echo -e "${BLUE}Waiting for MySQL to be ready...${NC}"
        sleep 10
        ;;
    *)
        print_help
        ;;
esac
