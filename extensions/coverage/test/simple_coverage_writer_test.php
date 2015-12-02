<?php

require_once dirname(__FILE__) . '/../../../autorun.php';

class SimpleCoverageWriterTest extends UnitTestCase
{
    public function skip()
    {
        $this->skipIf(
            !extension_loaded('sqlite3'),
            'The Coverage extension requires the PHP extension "php_sqlite3".'
        );
    }

    public function setUp()
    {
        require_once dirname(__FILE__) . '/../simple_coverage_writer.php';
        require_once dirname(__FILE__) . '/../coverage_calculator.php';
    }

    public function testGenerateSummaryReport()
    {
        $writer             = new SimpleCoverageWriter();
        $coverage           = array('file' => array(0, 1));
        $untouched          = array('missed-file');
        $calc               = new CoverageCalculator();
        $variables          = $calc->variables($coverage, $untouched);
        $variables['title'] = 'coverage';
        $out                = fopen('php://memory', 'w');
        $writer->writeSummary($out, $variables);
        $dom = self::getDom($out);

        $totalPercentCoverage = $dom->xpath("//span[@class='totalPercentCoverage']");
        $this->assertEqual('50%', (string) $totalPercentCoverage[0]);

        $fileLinks    = $dom->xpath("//a[@class='byFileReportLink']");
        $fileLinkAttr = $fileLinks[0]->attributes();
        $this->assertEqual('file.html', $fileLinkAttr['href']);
        $this->assertEqual('file', (string) ($fileLinks[0]));

        $untouchedFile = $dom->xpath("//span[@class='untouchedFile']");
        $this->assertEqual('missed-file', (string) $untouchedFile[0]);
    }

    public function testGenerateCoverageByFile()
    {
        $writer             = new SimpleCoverageWriter();
        $cov                = array(3 => 1, 4 => -2); // 2 comments, 1 code, 1 dead  (1-based indexes)
        $out                = fopen('php://memory', 'w');
        $file               = dirname(__FILE__) . '/sample/code.php';
        $calc               = new CoverageCalculator();
        $variables          = $calc->coverageByFileVariables($file, $cov);
        $variables['title'] = 'coverage';
        $writer->writeByFile($out, $variables);
        $dom = self::getDom($out);

        $cells = $dom->xpath("//table[@id='code']/tbody/tr/td/span");
        $this->assertEqual('comment code', self::getAttribute($cells[1], 'class'));
        $this->assertEqual('comment code', self::getAttribute($cells[3], 'class'));
        $this->assertEqual('covered code', self::getAttribute($cells[5], 'class'));
        $this->assertEqual('dead code', self::getAttribute($cells[7], 'class'));
    }

    public static function getAttribute($element, $attribute)
    {
        $a = $element->attributes();

        return $a[$attribute];
    }

    public static function getDom($stream)
    {
        rewind($stream);
        $doc = new DOMDocument();
        $doc->loadHTML(stream_get_contents($stream));

        return new SimpleXMLElement($doc->saveHTML());
    }
}
