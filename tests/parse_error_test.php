<?php

require_once '../src/unit_tester.php';
require_once '../src/reporter.php';

$test = new TestSuite('This should fail');
$test->addFile('test_with_parse_error.php');
$test->run(new HtmlReporter());
