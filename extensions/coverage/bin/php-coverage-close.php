<?php declare(strict_types=1);

/**
 * Close code coverage data collection, next step is to generate report.
 */
require_once __DIR__ . '/../coverage.php';

$cc = CodeCoverage::getInstance();
$cc->writeUntouched();
