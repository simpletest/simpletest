<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'errors.php');
    require_once(SIMPLE_TEST . 'options.php');
    require_once(SIMPLE_TEST . 'runner.php');
    require_once(SIMPLE_TEST . 'expectation.php');
    
    /**
     *    Interface used by the test displays and group tests.
     */
    class RunnableTest {
        var $_label;
        
        /**
         *    Sets up the test name and starts with no attached
         *    displays.
         *    @param $label        Name of test.
         *    @access public
         */
        function RunnableTest($label) {
            $this->_label = $label;
        }
        
        /**
         *    Accessor for the test name for subclasses.
         *    @return            Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }
        
        /**
         *    Runs the top level test for this class. The
         *    parameter will soon cease to be optional.
         *    @param $reporter        Target of test results.
         *    @returns Boolean        True if no failures.
         *    @access public
         *    @abstract
         */
        function run(&$reporter) {
        }
        
        /**
         *    Accessor for the number of subtests.
         *    @return            Number of test cases.
         *    @access public
         */
        function getSize() {
            return 1;
        }
    }

    /**
     *    Basic test case. This is the smallest unit of a test
     *    suite. It searches for
     *    all methods that start with the the string "test" and
     *    runs them. Working test cases extend this class.
     */
    class SimpleTestCase extends RunnableTest {
        var $_current_runner;
        
        /**
         *    Sets up the test with no display.
         *    @param $label        If no test name is given then
         *                         the class name is used.
         *    @access public
         */
        function SimpleTestCase($label = false) {
            if (! $label) {
                $label = get_class($this);
            }
            $this->RunnableTest($label);
            $this->_current_runner = false;
        }
        
        /**
         *    Uses reflection to run every method within itself
         *    starting with the string "test".
         *    @param $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintCaseStart($this->getLabel());
            $methods = get_class_methods(get_class($this));
            foreach ($methods as $method) {
                if (strtolower(substr($method, 0, 4)) != "test") {
                    continue;
                }
                if (is_a($this, strtolower($method))) {
                    continue;
                }
                $reporter->paintMethodStart($method);
                $reporter->invoke($this, $method);
                $reporter->paintMethodEnd($method);
            }
            $reporter->paintCaseEnd($this->getLabel());
            return $reporter->getStatus();
        }
        
        /**
         *    Invokes a test method and dispatches any
         *    untrapped errors. Called back from
         *    the visiting runner.
         *    @param $reporter    Current test reporter.
         *    @param $method    Test method to call.
         *    @access public
         */
        function invoke(&$reporter, $method) {
            set_error_handler('simpleTestErrorHandler');
            $this->_current_runner = &$reporter;
            $this->setUp();
            $this->$method();
            $this->tearDown();
            $queue = &SimpleErrorQueue::instance();
            while (list($severity, $message, $file, $line, $globals) = $queue->extract()) {
                $this->error($severity, $message, $file, $line, $globals);
            }
            restore_error_handler();
        }
        
        /**
         *    Sets up unit test wide variables at the start
         *    of each test method. To be overridden in
         *    actual user test cases.
         *    @access public
         */
        function setUp() {
        }
        
        /**
         *    Clears the data set in the setUp() method call.
         *    To be overridden by the user in actual user test cases.
         *    @access public
         */
        function tearDown() {
        }
        
        /**
         *    Sends a pass event with a message.
         *    @param $message        Message to send.
         *    @access public
         */
        function pass($message = "Pass") {
            $this->_current_runner->paintPass($message);
        }
        
        /**
         *    Sends a fail event with a message.
         *    @param $message        Message to send.
         *    @access public
         */
        function fail($message = "Fail") {
            $this->_current_runner->paintFail($message);
        }
        
        /**
         *    Formats a PHP error and dispatches it to the
         *    runner.
         *    @param $severity   PHP error code.
         *    @param $message    Text of error.
         *    @param $file       File error occoured in.
         *    @param $line       Line number of error.
         *    @param $globals    Hash of PHP super global arrays.
         *    @access public
         */
        function error($severity, $message, $file, $line, $globals) {
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            $this->_current_runner->paintError(
                    "Unexpected PHP error [$message] severity [$severity] in [$file] line [$line]");
        }
        
        /**
         *    Sends a user defined event to the test runner.
         *    This is for small scale extension where
         *    both the test case and either the runner or
         *    display are subclassed.
         *    @param $type        Type of event as string.
         *    @param $payload     Object or message to deliver.
         *    @access public
         */
        function signal($type, &$payload) {
            $this->_current_runner->paintSignal($type, $payload);
        }
        
        /**
         *    Cancels any outstanding errors.
         *    @access public
         */
        function swallowErrors() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }
        
        /**
         *    Runs an expectation directly, for extending the
         *    tests with new expectation classes.
         *    @param $expectation  Expectation subclass.
         *    @param $test_value   Value to compare.
         *    @param $message      Message to display.
         *    @access public
         */
        function assertExpectation(&$expectation, $test_value, $message) {
            $this->assertTrue(
                    $expectation->test($test_value),
                    sprintf($message, $expectation->testMessage($test_value)));
        }
        
        /**
         *    Called from within the test methods to register
         *    passes and failures.
         *    @param $result            Boolean, true on pass.
         *    @param $message           Message to display describing
         *                              the test state.
         *    @access public
         */
        function assertTrue($result, $message = "True expectation failed.") {
            if ($result) {
                $this->pass($message);
            } else {
                $this->fail($message);
            }
        }
        
        /**
         *    Will be true on false and vice versa. False
         *    is the PHP definition of false, so that null,
         *    empty strings, zero and an empty array all count
         *    as false.
         *    @param $boolean        Supposedly false value.
         *    @param $message        Message to display.
         *    @access public
         */
        function assertFalse($boolean, $message = "False expectation") {
            $this->assertTrue(! $boolean, $message);
        }
        
        /**
         *    Sends a formatted dump of a variable to the
         *    test suite for those emergency debugging
         *    situations.
         *    @param $variable       Variable to display.
         *    @param $message        Message to display.
         *    @access public
         */
        function dump($variable, $message = false) {
            ob_start();
            print_r($variable);
            $formatted = ob_get_contents();
            ob_end_clean();
            if ($message) {
                $formatted = $message . "\n" . $formatted;
            }
            $this->_current_runner->paintFormattedMessage($formatted);
        }
        
        /**
         *    Dispatches a text message straight to the
         *    test suite. Useful for status bar displays.
         *    @param $message        Message to show.
         *    @access public
         */
        function sendMessage($message) {
            $this->_current_runner->PaintMessage($message);
        }
    }
    
    /**
     *    This is a composite test class for combining
     *    test cases and other RunnableTest classes into
     *    a group test.
     */
    class GroupTest extends RunnableTest {
        var $_test_cases;
        
        /**
         *    Sets the name of the test suite.
         *    @param $label       Name sent at the start and end
         *                        of the test.
         *    @access public
         */
        function GroupTest($label) {
            $this->RunnableTest($label);
            $this->_test_cases = array();
        }
        
        /**
         *    Adds a test into the suite.
         *    @param $test_case        Suite or individual test
         *                             case implementing the
         *                             runnable test interface.
         *    @access public
         */
        function addTestCase(&$test_case) {
            $this->_test_cases[] = &$test_case;
        }
        
        /**
         *    Builds a group test from a library of test cases.
         *    The new group is composed into this one.
         *    @param $test_file        File name of library with
         *                             test case classes.
         *    @access public
         */
        function addTestFile($test_file) {
            $existing_classes = get_declared_classes();
            require($test_file);
            $group = new GroupTest($test_file);
            foreach (get_declared_classes() as $class) {
                if (in_array($class, $existing_classes)) {
                    continue;
                }
                if (!$this->_isTestCase($class)) {
                    continue;
                }
                if (SimpleTestOptions::isIgnored($class)) {
                    continue;
                }
                $group->addTestCase(new $class());
            }
            $this->addTestCase($group);
        }
        
        /**
         *    Test to see if a class is derived from the
         *    TestCase class.
         *    @param $class            Class name.
         *    @access private
         */
        function _isTestCase($class) {
            while ($class = get_parent_class($class)) {
                if (strtolower($class) == "simpletestcase") {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Invokes run() on all of the held test cases.
         *    @param $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintGroupStart($this->getLabel(), $this->getSize());
            for ($i = 0; $i < count($this->_test_cases); $i++) {
                $this->_test_cases[$i]->run($reporter);
            }
            $reporter->paintGroupEnd($this->getLabel());
            return $reporter->getStatus();
        }
        
        /**
         *    Number of contained test cases.
         *    @return         Total count of cases in the group.
         *    @access public
         */
        function getSize() {
            $count = 0;
            foreach ($this->_test_cases as $case) {
                $count += $case->getSize();
            }
            return $count;
        }
        
        /**
         *    @deprecated
         */
        function ignore($class = false) {
            SimpleTestOptions::ignore($class);
        }
    }
?>
