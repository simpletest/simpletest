<?php

interface CoverageWriter
{
    public function writeSummary($out, $variables);

    public function writeByFile($out, $variables);
}
