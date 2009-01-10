<?php

require_once dirname(__FILE__) .'/coverage_writer.php';

class SimpleCoverageWriter implements CoverageWriter {

    function writeSummary($out, $variables) {
        extract($variables);
        $now = date("F j, Y, g:i a");
        ob_start();
        include dirname(__FILE__) . '/templates/index.php';
        $contents = ob_get_contents();
        fwrite ($out, $contents);
        ob_end_clean();
    }

    function writeByFile($out, $variables) {
        extract($variables);
        ob_start();
        include dirname(__FILE__) . '/templates/file.php';
        $contents = ob_get_contents();
        fwrite ($out, $contents);
        ob_end_clean();
    }
}
?>