<?php declare(strict_types=1);

/**
 * Generate a code coverage report.
 */
# optional arguments:
#  --reportDir=some/directory    the default is ./coverage-report
#  --title='My Coverage Report'  title the main page of your report

// include coverage files
require_once __DIR__ . '/../coverage_utils.php';

require_once __DIR__ . '/../coverage.php';

require_once __DIR__ . '/../coverage_reporter.php';

$cc                = CodeCoverage::getInstance();
$handler           = new CoverageDataHandler($cc->log);
$report            = new CoverageReporter;
$args              = CoverageUtils::parseArguments($_SERVER['argv']);
$report->reportDir = CoverageUtils::issetOrDefault($args['reportDir'], 'coverage-report');
$report->title     = CoverageUtils::issetOrDefault($args['title'], 'Simpletest Coverage');
$report->coverage  = $handler->read();
$report->untouched = $handler->readUntouchedFiles();
$report->generate();
