#!/bin/bash

cd ..

php ./extensions/coverage/bin/php-coverage-open.php --exclude='.*/tests/.*' --exclude='.*sqlite.php$' --exclude='.*unit_tests.php$'

# run all tests
php -d auto_prepend_file=./extensions/coverage/autocoverage.php -f tests/unit_tests.php
php -d auto_prepend_file=./extensions/coverage/autocoverage.php -f extensions/coverage/tests/test.php

php ./extensions/coverage/bin/php-coverage-close.php
php ./extensions/coverage/bin/php-coverage-report.php
