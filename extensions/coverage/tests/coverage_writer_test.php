<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

class CoverageWriterTest extends UnitTestCase
{
    public static function getAttribute($element, $attribute)
    {
        return $element->attributes()[$attribute];
    }

    protected function setUp(): void
    {
        require_once __DIR__ . '/../coverage_writer.php';

        require_once __DIR__ . '/../coverage_calculator.php';
    }

    public function skip(): void
    {
        $this->skipIf(
            !\extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".',
        );
    }

    public function testGenerateSummaryReport(): void
    {
        $writer             = new CoverageWriter;
        $coverage           = ['file' => [0, 1]];
        $untouched          = ['missed-file'];
        $calc               = new CoverageCalculator;
        $variables          = $calc->variables($coverage, $untouched);
        $variables['title'] = 'Coverage Summary';
        $reportFile         = __DIR__ . '/summaryReport.html';

        $contents = $writer->writeSummaryReport($reportFile, $variables);

        $dom = new DOMDocument;
        @$dom->loadHTML($contents);
        $xpath = new DOMXPath($dom);

        $totalPercentCoverage = $xpath->query("//span[@class='totalPercentCoverage']");
        $this->assertEqual('50%', $totalPercentCoverage->item(0)->textContent);

        $fileLinks = $xpath->query("//a[@class='fileReportLink']");
        $this->assertEqual('file.html', $fileLinks->item(0)->getAttribute('href'));
        $this->assertEqual('file', $fileLinks->item(0)->textContent);

        $untouchedFile = $xpath->query("//span[@class='untouchedFile']");
        $this->assertEqual('missed-file', $untouchedFile->item(0)->textContent);

        \unlink($reportFile);
    }

    public function testGenerateCoverageByFile(): void
    {
        $writer             = new CoverageWriter;
        $cov                = [3 => 1, 4 => -2]; // 2 comments, 1 code, 1 dead (1-based indexes)
        $coverageSampleFile = __DIR__ . '/sample/code.php';
        $calc               = new CoverageCalculator;
        $variables          = $calc->coverageByFileVariables($coverageSampleFile, $cov);
        $variables['title'] = 'File Coverage';
        $reportFile         = __DIR__ . '/sampleFileReport.html';

        $contents = $writer->writeFileReport($reportFile, $variables);

        $dom = new DOMDocument;
        @$dom->loadHTML($contents);
        $xpath = new DOMXPath($dom);

        $cells = $xpath->query("//table[@id='code']/tbody/tr/td/span");

        $this->assertEqual('comment code', $cells->item(1)->getAttribute('class'));
        $this->assertEqual('comment code', $cells->item(3)->getAttribute('class'));
        $this->assertEqual('covered code', $cells->item(5)->getAttribute('class'));
        $this->assertEqual('dead code', $cells->item(7)->getAttribute('class'));

        \unlink($reportFile);
    }
}
