<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
// Local fallbacks for template variables to satisfy static analysis tools.
$title                  = $title ?? 'Coverage Report';
$totalPercentCoverage   = $totalPercentCoverage ?? 0;
$filesTouchedPercentage = $filesTouchedPercentage ?? 0;
$now                    = $now ?? (new DateTimeImmutable)->format(DateTime::ATOM);
$human_readable         = $human_readable ?? (new DateTimeImmutable($now))->format('F j, Y, g:i a');
$coverageByFile         = $coverageByFile ?? [];
$untouched              = $untouched ?? [];
?>
<title><?php print $title; ?></title>
<style type="text/css">
  /* --- Base --- */
  body {
    font-family: "Gill Sans MT", "Gill Sans", GillSans, Arial, Helvetica, sans-serif;
    margin: 1em;
    color: #222;
  }
  h1 { font-size: 1.2em; margin-bottom: 1em; }
  h2 { margin-top: 1em; }

  a { color: #2563eb; text-decoration: none; }
  a:hover, a:focus { text-decoration: underline; }

  /* --- Tables --- */
	table {
	margin: 1em;       /* add auto for center table */
	border-collapse: collapse;
	table-layout: auto;     /* allow columns to size to content */
	max-width: 800px;       /* limit overall width */
	}
  th, td {
    padding: 0.4em 0.6em;
    vertical-align: middle;
    word-wrap: break-word;
  }
  th:first-child, td:first-child { width: auto; min-width: 200px; }
  td.percentage { width: auto; min-width: 100px; display: flex; justify-content: flex-end; gap: 1em; }

  caption { border-bottom: thin solid #ccc; font-weight: bold; padding-bottom: 0.5em; text-align: left; }

  /* --- Coverage rows --- */
  tr.coverage-low td.percentage { background: #FDE2E1; }
  tr.coverage-medium td.percentage { background: #FFF5D1; }
  tr.coverage-high td.percentage { background: #D1F7D3; }

  #covered-files tbody tr:nth-child(odd) { background: #fafafa; }
  #covered-files tbody tr:hover { background: #f0f8ff; }

  /* --- Percentages --- */
  .percentCoverage,
  .totalPercentCoverage,
  .filesTouchedPercentage {
    display: inline-flex;
    justify-content: flex-end;
    width: 4ch; /* space for "100%" */
    font-weight: bold;
  }
  .digits {
    font-variant-numeric: tabular-nums;
    text-align: right;
  }

  /* --- Progress bars --- */
  .progress {
    flex: 0 0 8em;
    height: 0.8em;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }
  .progress-fill {
    height: 100%;
    width: 0%;
    border-radius: inherit;
    transition: width 0.4s ease;
    background: #ccc; /* fallback */
  }

  tr.coverage-low .progress-fill    { background: #d8534e; }
  tr.coverage-medium .progress-fill { background: #facc15; }
  tr.coverage-high .progress-fill   { background: #4ade80; }

  /* --- Legend --- */
  .legend-swatch {
    display: inline-block;
    width: 1em;
    height: 1em;
    margin-right: 0.5em;
    border: 1px solid #ccc;
    flex-shrink: 0;
  }
  .legend-low { background: #FDE2E1; }
  .legend-medium { background: #FFF5D1; }
  .legend-high { background: #D1F7D3; }

  ul { list-style: none; padding-left: 0; margin: 0; }
  ul li { display: flex; align-items: center; margin-bottom: 0.4em; }

  /* --- Glossary --- */
  dl { margin: 1em 0; }
  dl dt { font-weight: bold; margin-top: 0.8em; }
  dl dd { margin-left: 1.2em; margin-bottom: 0.4em; }

  /* --- Responsive --- */
  @media (max-width: 600px) {
    th:first-child, td:first-child { width: 50%; }
    td.percentage { width: 50%; }
    .progress { width: 6em; }
  }
</style>
</head>
<body>

<h1 id="title"><?php print $title; ?></h1>

<!-- Summary Table -->
<table>
  <caption>Summary</caption>
  <tbody>
    <tr>
      <td>Total Coverage (<a href="#total-coverage">?</a>) :</td>
      <td class="percentage">
  <span class="totalPercentCoverage"><span class="digits"><?php print \number_format($totalPercentCoverage, 0); ?></span>%</span>
      </td>
    </tr>
    <tr>
      <td>Total Files Covered (<a href="#total-files-covered">?</a>) :</td>
      <td class="percentage">
        <span class="filesTouchedPercentage"><span class="digits"><?php print \number_format($filesTouchedPercentage, 0); ?></span>%</span>
      </td>
    </tr>
    <tr>
      <td>Report Generation Date :</td>
      <td>
        <time datetime="<?php print \htmlspecialchars($now, \ENT_QUOTES, 'UTF-8'); ?>"><?php print $human_readable; ?></time>
      </td>
    </tr>
  </tbody>
</table>

<!-- Covered Files Table -->
<table id="covered-files">
  <caption>Coverage (<a href="#coverage">?</a>)</caption>
  <thead>
    <tr>
      <th>File</th>
      <th>Coverage</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($coverageByFile as $file => $coverage) {
        $pct = isset($coverage['percentage']) ? (int) \round($coverage['percentage']) : 0;

        if ($pct <= 50) {
            $coverageClass = 'coverage-low';
        } elseif ($pct <= 90) {
            $coverageClass = 'coverage-medium';
        } else {
            $coverageClass = 'coverage-high';
        }
        ?>
    <tr class="<?php print $coverageClass; ?>">
      <td><a class="fileReportLink" href="<?php print $coverage['fileReport']; ?>"><?php print $file; ?></a></td>
      <td class="percentage">
        <div class="progress" aria-hidden="true">
          <div class="progress-fill" style="width: <?php print \min(100, \max(0, $pct)); ?>%;"></div>
        </div>
        <span class="percentCoverage"><span class="digits"><?php print $pct; ?></span>%</span>
      </td>
    </tr>
    <?php } ?>
  </tbody>
</table>

<!-- Files Not Covered -->
<table>
  <caption>Files Not Covered (<a href="#untouched">?</a>)</caption>
  <tbody>
    <?php foreach ($untouched as $file) { ?>
    <tr>
      <td><span class="untouchedFile"><?php print $file; ?></span></td>
    </tr>
    <?php } ?>
  </tbody>
</table>

<!-- Glossary -->
<h2>Glossary</h2>
<dl>
  <dt><a name="total-coverage">Total Coverage</a></dt>
  <dd>Ratio of executed lines to total executable lines (excluding files with 0 coverage).</dd>
  <dt><a name="total-files-covered">Total Files Covered</a></dt>
  <dd>Ratio of tested files to total files (excluding untouched files).</dd>
  <dt><a name="coverage">Coverage</a></dt>
  <dd>Files parsed and loaded during tests. Coverage percentage is calculated as executed lines over total executable lines.</dd>
  <dt><a name="untouched">Files Not Covered</a></dt>
  <dd>Files not loaded by PHP during unit tests. Counted as 0% coverage but not reflected in Total Coverage.</dd>
</dl>

<!-- Legend -->
<h2>Legend</h2>
<p>The following color coding is used:</p>
<ul>
  <li><span class="legend-swatch legend-low" aria-hidden="true"></span>0–50% - Low Coverage</li>
  <li><span class="legend-swatch legend-medium" aria-hidden="true"></span>51–90% - Medium Coverage</li>
  <li><span class="legend-swatch legend-high" aria-hidden="true"></span>91–100% - High Coverage</li>
</ul>

<!-- Footer -->
<p>This code coverage report was generated by <a href="http://www.simpletest.org">SimpleTest</a>
<?php
$simpletest_version = \trim(\file_get_contents(\dirname(__DIR__, 3) . '/VERSION'));
$php_version        = \PHP_VERSION;
$xdebug_version     = \phpversion('xdebug');
\printf('%s running on PHP v%s with Xdebug v%s', $simpletest_version, $php_version, $xdebug_version);
?>
</p>

</body>
</html>
