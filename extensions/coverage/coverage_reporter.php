<?php declare(strict_types=1);

require_once __DIR__ . '/coverage_calculator.php';

require_once __DIR__ . '/coverage_utils.php';

require_once __DIR__ . '/coverage_writer.php';

/**
 * Take aggregated coverage data and generate reports from it.
 */
class CoverageReporter
{
    public $coverage;
    public $untouched;
    public $reportDir;
    public $title = 'Coverage';
    public $writer;
    public $calculator;
    public $summaryFile;

    public function __construct()
    {
        $this->writer     = new CoverageWriter;
        $this->calculator = new CoverageCalculator;

        $this->summaryFile = $this->reportDir . '/index.html';
    }

    public function generate(): void
    {
        print 'Generating Code Coverage Report';

        CoverageUtils::mkdir($this->reportDir);

        $this->generateSummaryReport();

        foreach ($this->coverage as $file => $cov) {
            $this->generateCoverageByFile($file, $cov);
        }

        print "Report generated: {$this->summaryFile}\n";
    }

    public function generateSummaryReport(): void
    {
        $variables          = $this->calculator->variables($this->coverage, $this->untouched);
        $variables['title'] = $this->title;

        $this->writer->writeSummaryReport($this->summaryFile, $variables);
    }

    public function generateCoverageByFile($file, $cov): void
    {
        $reportFile = $this->reportDir . '/' . CoverageUtils::reportFilename($file);

        $variables          = $this->calculator->coverageByFileVariables($file, $cov);
        $variables['title'] = $this->title . ' - ' . $file;

        $this->writer->writeFileReport($reportFile, $variables);
    }
}
