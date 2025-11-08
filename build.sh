#!/bin/bash

################################################################################
# PHP 8.5 Redis & Memcached Extension Builder
#
# This script builds PHP extensions from source for PHP 8.5 RC
# Required because official builds don't exist yet for RC versions
#
# Prerequisites:
#   - PHP 8.5 installed (php8.5 binary available)
#   - Build tools: gcc, make, autoconf, pkg-config
#   - Development libraries: libmemcached-dev, zlib1g-dev
#
# Usage:
#   chmod +x build.sh
#   ./build.sh
#
# Or build specific extension:
#   ./build.sh redis
#   ./build.sh memcached
################################################################################

# Note: We don't use 'set -e' here because we want to show friendly error messages
# instead of just exiting. Each critical command is checked individually.

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PHP_VERSION="8.5"
PHP_BIN="php8.5"
PHPIZE_BIN="phpize8.5"
PHP_CONFIG_BIN="php-config8.5"

# Extension versions
REDIS_VERSION="6.3.0"
MEMCACHED_VERSION="3.4.0"

# Build directory
BUILD_DIR="/tmp/php-extensions-build"
LOG_DIR="${BUILD_DIR}/logs"

################################################################################
# Helper Functions
################################################################################

print_header() {
    echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}════════════════════════════════════════════════════════════════${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "Command '$1' not found"
        return 1
    fi
    return 0
}

check_prerequisites() {
    print_header "Checking Prerequisites"

    local missing=0

    # Check PHP 8.5
    if ! check_command "$PHP_BIN"; then
        print_error "PHP 8.5 not found. Please install PHP 8.5 first."
        missing=1
    else
        local php_version=$($PHP_BIN -r "echo PHP_VERSION;")
        print_success "PHP found: $php_version"
    fi

    # Check phpize
    if ! check_command "$PHPIZE_BIN"; then
        print_error "phpize not found. Install php8.5-dev package."
        missing=1
    else
        print_success "phpize found"
    fi

    # Check php-config
    if ! check_command "$PHP_CONFIG_BIN"; then
        print_error "php-config not found. Install php8.5-dev package."
        missing=1
    else
        print_success "php-config found"
    fi

    # Check build tools
    for tool in gcc make autoconf pkg-config; do
        if ! check_command "$tool"; then
            print_error "$tool not found"
            missing=1
        else
            print_success "$tool found"
        fi
    done

    # Check for development libraries
    print_info "Checking development libraries..."

    if ! pkg-config --exists libmemcached; then
        print_warning "libmemcached-dev not found. Memcached extension will be skipped."
        print_info "Install with: sudo apt-get install libmemcached-dev"
    else
        print_success "libmemcached-dev found"
    fi

    if [ $missing -eq 1 ]; then
        print_error "Missing prerequisites. Please install required packages:"
        echo ""
        echo "  sudo apt-get update"
        echo "  sudo apt-get install -y php8.5-dev build-essential autoconf pkg-config libmemcached-dev zlib1g-dev"
        echo ""
        exit 1
    fi

    echo ""
}

create_build_directory() {
    print_info "Creating build directory: $BUILD_DIR"
    mkdir -p "$BUILD_DIR"
    mkdir -p "$LOG_DIR"
    cd "$BUILD_DIR"
    print_success "Build directory ready"
    echo ""
}

build_redis() {
    print_header "Building PHP Redis Extension"

    local log_file="${LOG_DIR}/redis-build.log"

    # Download source
    print_info "Downloading phpredis ${REDIS_VERSION}..."

    # Always work in build directory
    cd "$BUILD_DIR"

    # Check if already extracted
    local ext_dir=$(find "$BUILD_DIR" -maxdepth 1 -type d -name "phpredis-${REDIS_VERSION}*" | head -1)

    if [ -z "$ext_dir" ]; then
        wget -q "https://github.com/phpredis/phpredis/archive/${REDIS_VERSION}.tar.gz" -O phpredis.tar.gz >> "$log_file" 2>&1
        tar -xzf phpredis.tar.gz >> "$log_file" 2>&1
        rm phpredis.tar.gz

        # Find the extracted directory name
        ext_dir=$(find "$BUILD_DIR" -maxdepth 1 -type d -name "phpredis-${REDIS_VERSION}*" | head -1)

        if [ -z "$ext_dir" ]; then
            print_error "Failed to extract redis source"
            return 1
        fi

        print_success "Source downloaded and extracted to: $(basename "$ext_dir")"
    else
        print_info "Source already exists at: $(basename "$ext_dir")"
    fi

    cd "$ext_dir"

    # phpize
    print_info "Running phpize..."
    if ! $PHPIZE_BIN >> "$log_file" 2>&1; then
        print_error "phpize failed!"
        print_info "Check log: $log_file"
        return 1
    fi
    print_success "phpize completed"

    # Configure
    print_info "Configuring..."
    if ! ./configure --with-php-config=$PHP_CONFIG_BIN >> "$log_file" 2>&1; then
        print_error "Configuration failed!"
        print_info "Full error log: $log_file"
        echo ""
        echo -e "${YELLOW}Last 10 lines of error:${NC}"
        tail -10 "$log_file"
        echo ""
        return 1
    fi
    print_success "Configuration completed"

    # Make
    print_info "Compiling (this may take a while)..."
    if ! make -j$(nproc) >> "$log_file" 2>&1; then
        print_error "Compilation failed!"
        print_info "Check log: $log_file"
        echo ""
        echo -e "${YELLOW}Last 20 lines of error:${NC}"
        tail -20 "$log_file"
        echo ""
        return 1
    fi
    print_success "Compilation completed"

    # Install
    print_info "Installing extension..."
    if ! sudo make install >> "$log_file" 2>&1; then
        print_error "Installation failed!"
        print_info "Check log: $log_file"
        return 1
    fi
    print_success "Redis extension installed"

    # Enable extension
    enable_extension "redis"

    echo ""
}

build_memcached() {
    print_header "Building PHP Memcached Extension"

    # Check if libmemcached is available
    if ! pkg-config --exists libmemcached; then
        print_warning "libmemcached-dev not found. Skipping memcached extension."
        print_info "Install with: sudo apt-get install libmemcached-dev"
        echo ""
        return
    fi

    local log_file="${LOG_DIR}/memcached-build.log"

    # Download source
    print_info "Downloading php-memcached ${MEMCACHED_VERSION}..."

    # Always work in build directory
    cd "$BUILD_DIR"

    # Check if already extracted
    local ext_dir=$(find "$BUILD_DIR" -maxdepth 1 -type d -name "php-memcached-${MEMCACHED_VERSION}*" | head -1)

    if [ -z "$ext_dir" ]; then
        wget -q "https://github.com/php-memcached-dev/php-memcached/archive/v${MEMCACHED_VERSION}.tar.gz" -O memcached.tar.gz >> "$log_file" 2>&1
        tar -xzf memcached.tar.gz >> "$log_file" 2>&1
        rm memcached.tar.gz

        # Find the extracted directory name
        ext_dir=$(find "$BUILD_DIR" -maxdepth 1 -type d -name "php-memcached-${MEMCACHED_VERSION}*" | head -1)

        if [ -z "$ext_dir" ]; then
            print_error "Failed to extract memcached source"
            return 1
        fi

        print_success "Source downloaded and extracted to: $(basename "$ext_dir")"
    else
        print_info "Source already exists at: $(basename "$ext_dir")"
    fi

    cd "$ext_dir"

    # phpize
    print_info "Running phpize..."
    if ! $PHPIZE_BIN >> "$log_file" 2>&1; then
        print_error "phpize failed!"
        print_info "Check log: $log_file"
        return 1
    fi
    print_success "phpize completed"

    # Configure
    print_info "Configuring..."
    if ! ./configure --with-php-config=$PHP_CONFIG_BIN --enable-memcached >> "$log_file" 2>&1; then
        print_error "Configuration failed!"
        echo ""

        # Check for common errors
        if grep -q "zlib" "$log_file"; then
            print_error "Missing ZLIB development library"
            echo ""
            echo -e "${YELLOW}Please install zlib1g-dev:${NC}"
            echo -e "  ${BLUE}sudo apt-get install -y zlib1g-dev${NC}"
            echo ""
        fi

        if grep -q "libmemcached" "$log_file"; then
            print_error "Missing libmemcached development library"
            echo ""
            echo -e "${YELLOW}Please install libmemcached-dev:${NC}"
            echo -e "  ${BLUE}sudo apt-get install -y libmemcached-dev${NC}"
            echo ""
        fi

        print_info "Full error log: $log_file"
        echo ""
        echo -e "${YELLOW}Last 10 lines of error:${NC}"
        tail -10 "$log_file"
        echo ""
        return 1
    fi
    print_success "Configuration completed"

    # Make
    print_info "Compiling (this may take a while)..."
    if ! make -j$(nproc) >> "$log_file" 2>&1; then
        print_error "Compilation failed!"
        print_info "Check log: $log_file"
        echo ""
        echo -e "${YELLOW}Last 20 lines of error:${NC}"
        tail -20 "$log_file"
        echo ""
        return 1
    fi
    print_success "Compilation completed"

    # Install
    print_info "Installing extension..."
    if ! sudo make install >> "$log_file" 2>&1; then
        print_error "Installation failed!"
        print_info "Check log: $log_file"
        return 1
    fi
    print_success "Memcached extension installed"

    # Enable extension
    enable_extension "memcached"

    echo ""
}

enable_extension() {
    local extension=$1
    local ini_dir="/etc/php/8.5/mods-available"
    local ini_file="${ini_dir}/${extension}.ini"

    print_info "Enabling ${extension} extension..."

    # Create mods-available directory if it doesn't exist
    if [ ! -d "$ini_dir" ]; then
        sudo mkdir -p "$ini_dir"
    fi

    # Create .ini file
    echo "extension=${extension}.so" | sudo tee "$ini_file" > /dev/null

    # Enable for CLI
    if [ -d "/etc/php/8.5/cli/conf.d" ]; then
        sudo ln -sf "$ini_file" "/etc/php/8.5/cli/conf.d/20-${extension}.ini" 2>/dev/null || true
    fi

    # Enable for FPM if exists
    if [ -d "/etc/php/8.5/fpm/conf.d" ]; then
        sudo ln -sf "$ini_file" "/etc/php/8.5/fpm/conf.d/20-${extension}.ini" 2>/dev/null || true
    fi

    print_success "${extension} extension enabled"
}

verify_installation() {
    print_header "Verifying Installation"

    # Check Redis
    if $PHP_BIN -m | grep -q "^redis$"; then
        print_success "Redis extension loaded successfully"
        local redis_version=$($PHP_BIN -r "echo phpversion('redis');")
        print_info "Redis version: $redis_version"
    else
        print_warning "Redis extension not loaded"
    fi

    # Check Memcached
    if $PHP_BIN -m | grep -q "^memcached$"; then
        print_success "Memcached extension loaded successfully"
        local memcached_version=$($PHP_BIN -r "echo phpversion('memcached');")
        print_info "Memcached version: $memcached_version"
    else
        print_warning "Memcached extension not loaded"
    fi

    echo ""
}

show_summary() {
    print_header "Build Summary"

    echo "Extensions built and installed:"
    echo ""

    $PHP_BIN -m | grep -E "^(redis|memcached)$" | while read ext; do
        local version=$($PHP_BIN -r "echo phpversion('$ext');")
        echo -e "  ${GREEN}✓${NC} $ext (v$version)"
    done

    echo ""
    echo "Build logs available at: $LOG_DIR"
    echo ""

    print_success "All done! 🎉"
    echo ""
}

cleanup() {
    print_info "Cleaning up build files..."
    cd /
    # Uncomment to remove build directory after successful build
    # rm -rf "$BUILD_DIR"
    print_info "Build files kept in: $BUILD_DIR"
}

################################################################################
# Main Script
################################################################################

main() {
    clear

    print_header "PHP 8.5 Extension Builder for Larafony Framework"
    echo ""
    echo "This script will build and install:"
    echo "  - phpredis v${REDIS_VERSION}"
    echo "  - php-memcached v${MEMCACHED_VERSION}"
    echo ""
    echo "For PHP ${PHP_VERSION} (Release Candidate)"
    echo ""

    # Check if specific extension requested
    local build_redis=1
    local build_memcached=1

    if [ "$1" = "redis" ]; then
        build_memcached=0
        print_info "Building Redis extension only"
        echo ""
    elif [ "$1" = "memcached" ]; then
        build_redis=0
        print_info "Building Memcached extension only"
        echo ""
    fi

    # Run checks and build
    check_prerequisites
    create_build_directory

    if [ $build_redis -eq 1 ]; then
        build_redis
    fi

    if [ $build_memcached -eq 1 ]; then
        build_memcached
    fi

    verify_installation
    show_summary

    # Cleanup if requested
    if [ "$2" = "--cleanup" ]; then
        cleanup
    fi
}

# Run main function
main "$@"
