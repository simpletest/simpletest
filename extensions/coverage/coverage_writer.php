<?php declare(strict_types=1);

/**
 * CoverageWriter class.
 */
class CoverageWriter
{
    public function writeSummaryReport($file, $data)
    {
        $summaryTemplateContents = function ($file, $data)
        {
            // Provide defaults for template variables to ensure they are defined.
            $defaults = [
                'coverageByFile'         => [],
                'totalPercentCoverage'   => 0,
                'totalLinesOfCode'       => 0,
                'totalLinesOfCoverage'   => 0,
                'filesTouchedPercentage' => 0,
                'untouched'              => [],
                'title'                  => 'Coverage Report',
            ];

            $data = \array_merge($defaults, $data);
            // Ensure coverageByFile is explicitly defined for static analysis
            $coverageByFile = $data['coverageByFile'] ?? [];
            \extract($data);

            \asort($coverageByFile);

            $now            = (new DateTimeImmutable)->format(DateTime::ATOM);
            $human_readable = (new DateTimeImmutable($now))->format('F j, Y, g:i a');

            \ob_start();

            include __DIR__ . '/templates/index.php';

            return \ob_get_clean();
        };

        $contents = $summaryTemplateContents($file, $data);

        \file_put_contents($file, $contents);

        return $contents;
    }

    public function writeFileReport($file, $data)
    {
        $fileTemplateContents = function ($file, $data)
        {
            $defaults = [
                'title' => 'File Coverage',
                'lines' => [],
            ];

            $data = \array_merge($defaults, $data);
            \extract($data);
            \ob_start();

            include __DIR__ . '/templates/file.php';

            return \ob_get_clean();
        };

        $contents = $fileTemplateContents($file, $data);

        \file_put_contents($file, $contents);

        return $contents;
    }
}
