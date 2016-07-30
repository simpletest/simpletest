<?php

require_once __DIR__ . '/../autorun.php';

class AllTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct('All tests for SimpleTest ' . SimpleTest::getVersion());
        $this->addFile(__DIR__ . '/unit_tests.php');
        $this->addFile(__DIR__ . '/shell_test.php');
        $this->addFile(__DIR__ . '/live_test.php');
        // The acceptance tests "examples" are served via PHP's built-in webserver (5.4+)
        $this->addFile(__DIR__ . '/acceptance_test.php');
    }
}
