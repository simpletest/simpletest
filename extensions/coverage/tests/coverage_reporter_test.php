<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

class CoverageReporterTest extends UnitTestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../coverage_reporter.php';
        new CoverageReporter;
    }

    public function skip(): void
    {
        $this->skipIf(
            !\extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".',
        );
    }
}
