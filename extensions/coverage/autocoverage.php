<?php declare(strict_types=1);

/**
 * Include this in any file to start coverage,
 * coverage will automatically end when process dies.
 */
require_once __DIR__ . '/coverage.php';

if (CodeCoverage::isCoverageOn()) {
    $coverage = CodeCoverage::getInstance();
    $coverage->startCoverage();
    \register_shutdown_function('stop_coverage');
}

function stop_coverage(): void
{
    # hack until i can think of a way to run tests first and w/o exiting
    $autorun = \function_exists('run_local_tests');

    if ($autorun) {
        $result = run_local_tests();
    }
    CodeCoverage::getInstance()->stopCoverage();

    if ($autorun) {
        exit($result ? 0 : 1);
    }
}
