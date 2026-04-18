#!/usr/bin/env bash
#
# TYPO3 Blog Extension — test-suite dispatcher.
#
# Single entry point that mirrors CI locally. Dispatches to composer
# scripts so developers don't have to remember which suite uses which
# configuration file.
#
# Usage:
#   Build/Scripts/runTests.sh -s <suite> [-p <php_version>]
#
#   -s  Suite to run (default: all).
#       Supported: lint, cgl, phpstan, unit, coverage, functional, all
#   -p  PHP binary version (default: current `php`). Example: 8.3 -> runs
#       /opt/homebrew/opt/php@8.3/bin/php (macOS/Homebrew) or php8.3.
#   -h  Help.
#
# Exit code: non-zero on first failing step.
set -euo pipefail

SUITE="all"
PHP_VERSION=""

while getopts "s:p:h" opt; do
    case "${opt}" in
        s) SUITE="${OPTARG}" ;;
        p) PHP_VERSION="${OPTARG}" ;;
        h)
            sed -n '3,18p' "$0" | sed 's/^#\s\{0,1\}//'
            exit 0
            ;;
        *)
            echo "Unknown option. Run '$0 -h' for usage." >&2
            exit 2
            ;;
    esac
done

resolve_php() {
    local version="$1"
    if [[ -z "$version" ]]; then
        command -v php
        return
    fi

    # macOS / Homebrew
    if [[ -x "/opt/homebrew/opt/php@${version}/bin/php" ]]; then
        echo "/opt/homebrew/opt/php@${version}/bin/php"
        return
    fi

    # Debian / Ubuntu packaging
    if command -v "php${version}" >/dev/null 2>&1; then
        command -v "php${version}"
        return
    fi

    echo "Could not find PHP ${version}. Install it or run without -p." >&2
    exit 3
}

PHP_BIN="$(resolve_php "${PHP_VERSION}")"
export PATH="$(dirname "${PHP_BIN}"):${PATH}"

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "${REPO_ROOT}"

run() {
    echo
    echo ">>> $*"
    "$@"
}

case "${SUITE}" in
    lint)
        run composer test:php:lint
        ;;
    cgl)
        run composer cgl
        ;;
    phpstan)
        run composer phpstan
        ;;
    unit)
        run composer test:php:unit
        ;;
    coverage)
        run composer test:php:coverage
        ;;
    functional)
        run composer test:php:functional
        ;;
    all|"")
        run composer test
        ;;
    *)
        echo "Unknown suite '${SUITE}'. Run '$0 -h' for a list." >&2
        exit 2
        ;;
esac
