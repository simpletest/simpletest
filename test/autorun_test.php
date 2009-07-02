<?php
require_once(dirname(__FILE__) . '/../autorun.php');
require_once(dirname(__FILE__) . '/support/test1.php');

class TestOfAutorun extends UnitTestCase {
    function test_load_if_included() {
        $tests = new TestSuite();
        $tests->addFile(dirname(__FILE__) . '/support/test1.php');
        $this->assertEqual($tests->getSize(), 1);
    }

    function test_exit_status_one_if_tests_fail() {
        exec('php ' . dirname(__FILE__) . '/support/failing_test.php', $output, $exit_status);
        $this->assertEqual($exit_status, 1);
    }

    function test_exit_status_zero_if_tests_fail() {
        exec('php ' . dirname(__FILE__) . '/support/passing_test.php', $output, $exit_status);
        $this->assertEqual($exit_status, 0);
    }
}

?>