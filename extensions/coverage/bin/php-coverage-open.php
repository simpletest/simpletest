<?php declare(strict_types=1);

/**
 * Initialize code coverage data collection, next step is to run your tests
 * with ini setting auto_prepend_file=autocoverage.php ...
 */

# optional arguments:
#  --include=<some filepath regexp>      these files should be included coverage report
#  --exclude=<come filepath regexp>      these files should not be included in coverage report
#  --maxdepth=2                          when considering which file were not touched, scan directories
#
# Example:
# php-coverage-open.php --include='.*\.php$' --include='.*\.inc$' --exclude='.*/tests/.*'

// include coverage files

require_once __DIR__ . '/../coverage_utils.php';

require_once __DIR__ . '/../coverage.php';

$cc      = new CodeCoverage;
$cc->log = 'coverage.sqlite';

// Load existing settings if present so we don't overwrite user customizations
if (\file_exists($cc->settingsFile)) {
    $cc->readSettings();
}

$args = CoverageUtils::parseArguments($_SERVER['argv'], true);

// CLI-provided includes/excludes override or merge with existing settings
$cliIncludes = $args['include[]'] ?? null;
$cliExcludes = $args['exclude[]'] ?? null;

// Default include pattern if nothing provided
if ($cliIncludes !== null) {
    $cc->includes = $cliIncludes;
} elseif (empty($cc->includes)) {
    $cc->includes = ['.*\.php$'];
}

// Ensure common folders are excluded and merge with existing excludes
$defaultExcludes = [
    # folders
    '.*\/build\/.*',
    '.*\/coverage-report\/.*',
    '.*\/docs\/.*',
    '.*\/vendor\/.*',
    # files
    '.*sqlite.php$',
    '.*unit_tests.php$',
];

if ($cliExcludes !== null) {
    $merged = \array_merge($cc->excludes ?? [], $cliExcludes);
} else {
    $merged = $cc->excludes ?? [];
}

// Add defaults if missing
foreach ($defaultExcludes as $pattern) {
    if (!\in_array($pattern, $merged, true)) {
        $merged[] = $pattern;
    }
}

$cc->excludes = $merged;

$cc->maxDirectoryDepth = (int) ($args['maxdepth'] ?? '1');
$cc->resetLog();
$cc->writeSettings();
