<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php print $title; ?></title>
    <style type="text/css">
        body { font-family: "Gill Sans MT", "Gill Sans", GillSans, Arial, Helvetica, sans-serif; }
        h1 { font-size: medium; }
        td.percentage { text-align: right; display: flex; align-items: center; justify-content: flex-end; gap: .5em; }
        tr.coverage-low td.percentage { background-color: #FDE2E1; color: #222222; }
        tr.coverage-low td.percentage .percentCoverage { color: #222222; font-weight: bold; }
        tr.coverage-medium td.percentage { background-color: #FFF5D1; color: #222222; }
        tr.coverage-medium td.percentage .percentCoverage { color: #222222; font-weight: bold; }
        tr.coverage-high td.percentage { background-color: #D1F7D3; color: #222222; }
        tr.coverage-high td.percentage .percentCoverage { color: #222222; font-weight: bold; }
        .fileReportLink { color: #2563eb; text-decoration: none; }
        .fileReportLink:hover, .fileReportLink:focus { text-decoration: underline; }
        .progress { display: inline-block; width: 8em; height: 0.6em; background: rgba(0,0,0,0.06); border-radius: 999px; overflow: hidden; }
        .progress-fill { display: block; height: 100%; width: 0%; background: rgba(0,0,0,0.15); transition: width 250ms ease; }
        tr.coverage-low td.percentage .progress-fill { background: rgba(216, 83, 78, 0.9); }
        tr.coverage-medium td.percentage .progress-fill { background: rgba(250, 204, 21, 0.9); }
        tr.coverage-high td.percentage .progress-fill { background: rgba(74, 222, 128, 0.9); }
        .legend-swatch { display: inline-block; width: 1em; height: 1em; margin-right: .5em; vertical-align: middle; border: 1px solid #ccc; }
        .legend-low { background-color: #FDE2E1; }
        .legend-medium { background-color: #FFF5D1; }
        .legend-high { background-color: #D1F7D3; }
        caption { border-bottom: thin solid; font-weight: bolder; }
        dt { font-weight: bolder; }
        table { margin: 1em; }
    </style>
  </head>
  <body>
    <h1 id="title"><?php print $title; ?></h1>
    <table>
      <caption>Summary</caption>
      <tbody>
        <tr>
          <td>Total Coverage (<a href="#total-coverage">?</a>) :</td>
          <td class="percentage"><span class="totalPercentCoverage"><?php print \number_format($totalPercentCoverage, 0); ?>%</span></td>
        </tr>
        <tr>
          <td>Total Files Covered (<a href="#total-files-covered">?</a>) :</td>
          <td class="percentage"><span class="filesTouchedPercentage"><?php print \number_format($filesTouchedPercentage, 0); ?>%</span></td>
        </tr>
        <tr>
          <td>Report Generation Date :</td>
          <td>
            <time datetime="<?php print \htmlspecialchars($now, \ENT_QUOTES, 'UTF-8'); ?>"><?php print $human_readable; ?></time>
          </td>
        </tr>
      </tbody>
    </table>
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
          // determine coverage class: low (0-50), medium (51-90), high (91-100)
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
          <span class="percentCoverage"><?php print \number_format($coverage['percentage'], 0); ?>%</span>
          <span class="progress" aria-hidden="true">
            <span class="progress-fill" style="width: <?php print \number_format(\min(100, \max(0, (int) \round($coverage['percentage']))), 0); ?>%;"></span>
          </span>
        </td>
      </tr>
    <?php } ?>
      </tbody>
    </table>
    <table>
      <caption>Files Not Covered (<a href="#untouched">?</a>)</caption>
      <tbody>
          <?php foreach ($untouched as $key => $file) { ?>
            <tr>
              <td><span class="untouchedFile"><?php print $file; ?></span></td>
            </tr>
        <?php } ?>
      </tbody>
    </table>

    <h2>Glossary</h2>
    <dl>
      <dt><a name="total-coverage">Total Coverage</a></dt>
      <dd>Ratio of all the lines of executable code that were executed to the
        lines of code that were not executed. This does not include the files
        that were not covered at all.</dd>
      <dt><a name="total-files-covered">Total Files Covered</a></dt>
      <dd>This is the ratio of the number of files tested, to the number of
        files not tested at all.</dd>
      <dt><a name="coverage">Coverage</a></dt>
      <dd>These files were parsed and loaded by the php interpreter while
        running the tests. Percentage is determined by the ratio of number of
        lines of code executed to the number of possible executable lines of
        code. "dead" lines of code, or code that could not be executed
        according to xdebug, are counted as covered because in almost all cases
        it is the end of a logical loop.</dd>
      <dt><a name="untouched">Files Not Covered</a></dt>
      <dd>These files were not loaded by the php interpreter at anytime
        during a unit test. You could consider these files having 0% coverage,
        but because it is difficult to determine the total coverage unless you
        could count the lines for executable code, this is not reflected in the
        Total Coverage calculation.</dd>
    </dl>

    <h2>Legend</h2>
    <dl><p>The following color coding is used:</p>
    <ul>
       <li><span class="legend-swatch legend-low" aria-hidden="true"></span>0 to 50% - Low Coverage</li>
       <li><span class="legend-swatch legend-medium" aria-hidden="true"></span>51 to 90% - Medium Coverage</li>
       <li><span class="legend-swatch legend-high" aria-hidden="true"></span>91 to 100% - High Coverage</li>
    </ul>
    </dl>

  <p>This code coverage report was generated by <a href="http://www.simpletest.org">SimpleTest</a>
  <?php
  $simpletest_version = \trim(\file_get_contents(\dirname(__DIR__, 3) . '/VERSION'));
    $php_version      = \PHP_VERSION;
    $xdebug_version   = \phpversion('xdebug');
    print \sprintf('%s running on PHP v%s with Xdebug v%s', $simpletest_version, $php_version, $xdebug_version);
    ?>
  </p>

  </body>
</html>
