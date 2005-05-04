<?php
    // $Id$
    define('TEST', __FILE__);
    require_once(dirname(__FILE__) . '/../unit_tester.php');
    require_once(dirname(__FILE__) . '/../shell_tester.php');
    require_once(dirname(__FILE__) . '/../reporter.php');
    require_once(dirname(__FILE__) . '/../mock_objects.php');
    require_once(dirname(__FILE__) . '/unit_tests.php');
    
    // Uncomment and modify the following line if you are accessing
    // the net via a proxy server.
    //
    // SimpleTestOptions::useProxy('http://my-proxy', 'optional username', 'optional password');
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest('All tests for SimpleTest ' . SimpleTestOptions::getVersion());
            $this->addTestCase(new UnitTests());
            $this->addTestFile(dirname(__FILE__) . '/shell_test.php');
            $this->addTestFile(dirname(__FILE__) . '/live_test.php');
            $this->addTestFile(dirname(__FILE__) . '/acceptance_test.php');
            $this->addTestFile(dirname(__FILE__) . '/real_sites_test.php');
        }
    }

    $test = &new AllTests();
    if (SimpleReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>