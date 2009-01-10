<?php
# 
# Close code coverage data collection, next step is to generate report
#
require_once(dirname(__FILE__) . '/../coverage.php');
$cc = CodeCoverage::getInstance();
$cc->readSettings();
$cc->writeUntouched();
?>