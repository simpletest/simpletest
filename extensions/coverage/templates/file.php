<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php $title = $title ?? 'File Coverage'; ?>
  <title><?php print $title; ?></title>
    <style type="text/css">
        body { font-family: "Gill Sans MT", "Gill Sans", GillSans, Arial, Helvetica, sans-serif; }
        h1 { font-size: medium; }
        #code { border-spacing: 0; }
        .lineNo { color: #9CA3AF; }
        .code, .lineNo { white-space: pre; font-family: monospace; }
        .covered { color: #059669; }
        .missed { color: #B91C1C; }
        .dead { color: #1D4ED8; }
        .comment { color: #6B7280; }
    </style>
  </head>
  <body>
  <h1 id="title"><?php print $title; ?></h1>
    <table id="code">
      <tbody>
          <?php $lines = $lines ?? [];

  foreach ($lines as $lineNo => $line) { ?>
            <tr>
              <td><span class="lineNo"><?php print $lineNo; ?></span></td>
              <td><span class="<?php print $line['lineCoverage']; ?> code"><?php print \htmlentities($line['code']); ?></span></td>
            </tr>
        <?php } ?>
      </tbody>
    </table>
    <h2>Legend</h2>
    <dl>
      <dt><span class="missed">Missed</span></dt>
      <dd>lines code that <strong>were not</strong> excersized during program execution.</dd>
      <dt><span class="covered">Covered</span></dt>
      <dd>lines code <strong>were</strong> excersized during program execution.</dd>
      <dt><span class="comment">Comment/non executable</span></dt>
      <dd>Comment or non-executable line of code.</dd>
      <dt><span class="dead">Dead</span></dt>
      <dd>lines of code that according to xdebug could not be executed.
        This is counted as coverage code because in almost all cases it is code that runnable.</dd>
    </dl>
  </body>
</html>
