<?php
    // $Id$
    
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', './');
    }
    require_once(SIMPLE_TEST . 'errors.php');
    require_once(SIMPLE_TEST . 'runner.php');
    require_once(SIMPLE_TEST . 'assertion.php');
    
    /**
     *    Interface used by the test displays and group tests.
     */
    class RunnableTest {
        var $_reporter;
        var $_label;
        
        /**
         *    Sets up the test name and starts with no attached
         *    displays.
         *    @param $label        Name of test.
         *    @public
         */
        function RunnableTest($label) {
            $this->_label = $label;
            $this->_reporter = false;
        }
        
        /**
         *    Accessor for the test name for subclasses.
         *    @return            Name of the test.
         *    @public
         */
        function getLabel() {
            return $this->_label;
        }
        
        /**
         *    Runs the top level test for this class. The
         *    parameter will soon cease to be optional.
         *    @param $reporter        Target of test results.
         *    @public
         */
        function run($reporter = false) {
            if (!$reporter) {
                $reporter = &$this->_reporter;
            }
            $this->accept(new TestRunner($reporter));
        }
        
        /**
         *    Accepts a runner, either a dummy or a real one.
         *    @param $runner        Test runner.
         *    @public
         *    @abstract
         */
        function accept(&$runner) {
        }
        
        /**
         *    Accessor for the number of subtests.
         *    @return            Number of test cases.
         *    @public
         */
        function getSize() {
            return 1;
        }
        
        /**
         *    @deprecated
         */
        function attachObserver(&$reporter) {
            $this->_reporter = &$reporter;
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
         *    @public
         */
        function SimpleTestCase($label = false) {
            if (!$label) {
                $label = get_class($this);
            }
            $this->RunnableTest($label);
            $this->_current_runner = false;
        }
        
        /**
         *    Uses reflection to run every method within itself
         *    starting with the string "test".
         *    @param $runner    Current test runner.
         *    @public
         */
        function accept(&$runner) {
            $runner->handleCaseStart($this->getLabel());
            $methods = get_class_methods(get_class($this));
            foreach ($methods as $method) {
                if (strtolower(substr($method, 0, 4)) != "test") {
                    continue;
                }
                if (is_a($this, strtolower($method))) {
                    continue;
                }
                $runner->handleMethodStart($method);
                $this->invoke($runner, $method);
                $runner->handleMethodEnd($method);
            }
            $runner->handleCaseEnd($this->getLabel());
        }
        
        /**
         *    Invokes a test method.
         *    @param $runner    Current test runner.
         *    @public
         */
        function invoke(&$runner, $method) {
            $this->_current_runner = &$runner;
            $this->setUp();
            $this->$method();
            $this->tearDown();
        }
        
        /**
         *    Sets up unit test wide variables at the start
         *    of each test method. To be overridden in
         *    actual user test cases.
         *    @public
         */
        function setUp() {
        }
        
        /**
         *    Clears the data set in the setUp() method call.
         *    To be overridden by the user in actual user test cases.
         *    @public
         */
        function tearDown() {
        }
        
        /**
         *    Sends a pass event with a message.
         *    @param $message        Message to send.
         *    @public
         */
        function pass($message = "Pass") {
            $this->_current_runner->handlePass($message);
        }
        
        /**
         *    Sends a fail event with a message.
         *    @param $message        Message to send.
         *    @public
         */
        function fail($message = "Fail") {
            $this->_current_runner->handleFail($message);
        }
        
        /**
         *    Runs an assertion directly, for extending the
         *    tests with new assertion classes.
         *    @param $assertion    Assertion subclass.
         *    @param $test_value   Value to compare.
         *    @param $message      Message to display.
         *    @public
         */
        function assertAssertion(&$assertion, $test_value, $message) {
            $this->assertTrue(
                    $assertion->test($test_value),
                    sprintf($message, $assertion->testMessage($test_value)));
        }
        
        /**
         *    Called from within the test methods to register
         *    passes and failures.
         *    @param $result            Boolean, true on pass.
         *    @param $message           Message to display describing
         *                              the test state.
         *    @public
         */
        function assertTrue($result, $message = "True assertion failed.") {
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
         *    @public
         */
        function assertFalse($boolean, $message = "False assertion") {
            $this->assertTrue(!$boolean, $message);
        }
        
        /**
         *    Sends a formatted dump of a variable to the
         *    test suite for those emergency debugging
         *    situations.
         *    @param $variable       Variable to display.
         *    @param $message        Message to display.
         *    @public
         */
        function dump($variable, $message = false) {
            ob_start();
            print_r($variable);
            $formatted = ob_get_contents();
            ob_end_clean();
            if ($message) {
                $formatted = $message . "\n" . $formatted;
            }
            $this->_current_runner->handleFormattedMessage($formatted);
        }
        
        /**
         *    Dispatches a text message straight to the
         *    test suite. Useful for status bar displays.
         *    @param $message        Message to show.
         *    @public
         */
        function sendMessage($message) {
            $this->_current_runner->handleMessage($message);
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
         *    @public
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
         *    @public
         */
        function addTestCase(&$test_case) {
            $test_case->attachObserver($this);
            $this->_test_cases[] = &$test_case;
        }
        
        /**
         *    Builds a group test from a library of test cases.
         *    The new group is composed into this one.
         *    @param $test_file        File name of library with
         *                             test case classes.
         *    @public
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
                if (in_array($class, GroupTest::ignore())) {
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
         *    @private
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
         *    Invokes accept() on all of the held test cases.
         *    @param $runner    Current test runner.
         *    @public
         */
        function accept(&$runner) {
            $runner->handleGroupStart($this->getLabel(), $this->getSize());
            for ($i = 0; $i < count($this->_test_cases); $i++) {
                $this->_test_cases[$i]->accept($runner);
            }
            $runner->handleGroupEnd($this->getLabel());
        }
        
        /**
         *    Number of contained test cases.
         *    @return         Total count of cases in the group.
         *    @public
         */
        function getSize() {
            $count = 0;
            foreach ($this->_test_cases as $case) {
                $count += $case->getSize();
            }
            return $count;
        }
        
        /**
         *    Maintains a static ignore list so that a
         *    directive can be sent to the group test
         *    that a test class should not be included
         *    during a file scan. Used for hiding test
         *    generic case extensions from tests.
         *    @param $class        Add a class to ignore.
         *    @static
         */
        function ignore($class = false) {
            static $_classes;
            if (!isset($_classes)) {
                $_classes = array();
            }
            if ($class) {
                $_classes[] = strtolower($class);
            }
            return $_classes;
        }
    }
?>
