<?php declare(strict_types=1);

require_once __DIR__ . '/../src/unit_tester.php';

require_once __DIR__ . '/../src/reporter.php';

$test = new TestSuite('This should fail');
$test->addFile('test_with_parse_error.php');
$test->run(new HtmlReporter);
