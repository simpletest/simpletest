<?php

interface CoverageWriter {

    function writeSummary($out, $variables);

    function writeByFile($out, $variables);
}
?>