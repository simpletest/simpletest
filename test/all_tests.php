<?php

require_once dirname(__FILE__) . '/../autorun.php';

class AllTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct('All tests for SimpleTest ' . SimpleTest::getVersion());
        $this->addFile(dirname(__FILE__) . '/unit_tests.php');
        $this->addFile(dirname(__FILE__) . '/shell_test.php');
        $this->addFile(dirname(__FILE__) . '/live_test.php');
        
        // The acceptance tests "examples" are served via PHP's built-in webserver,
        // which is available from PHP5.4 on.
        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            
            $this->addFile(dirname(__FILE__) . '/acceptance_test.php');
        }
    }
}
