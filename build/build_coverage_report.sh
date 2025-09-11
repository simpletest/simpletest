#!/bin/bash

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR/.."

echo "Building code coverage report..."
php ./extensions/coverage/bin/php-coverage-open.php --exclude='.*/tests/.*' --exclude='.*sqlite.php$' --exclude='.*unit_tests.php$'

export XDEBUG_MODE=${XDEBUG_MODE:-coverage}

echo "Running All tests..."
php -d auto_prepend_file=./extensions/coverage/autocoverage.php -f tests/unit_tests.php

echo "Running All extension tests.."
php -d auto_prepend_file=./extensions/coverage/autocoverage.php -f extensions/coverage/tests/test.php

echo "Finalizing code coverage report..."
php ./extensions/coverage/bin/php-coverage-close.php

echo "Generating code coverage report..."
php ./extensions/coverage/bin/php-coverage-report.php
