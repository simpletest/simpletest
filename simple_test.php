<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */
    
    /**#@+
     * Includes SimpleTest files and defined the root constant
     * for dependent libraries.
     */
    require_once(dirname(__FILE__) . '/errors.php');
    require_once(dirname(__FILE__) . '/options.php');
    require_once(dirname(__FILE__) . '/scorer.php');
    require_once(dirname(__FILE__) . '/expectation.php');
    require_once(dirname(__FILE__) . '/dumper.php');
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', dirname(__FILE__) . '/');
    }
    /**#@-*/

    /**
     *    The standard runner. Will run every method starting
     *    with test as well as the setUp() and tearDown()
     *    before and after each test method. Basically the
     *    Mediator pattern.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleRunner {
        var $_test_case;
        var $_scorer;
        
        /**
         *    Takes in the test case and reporter to mediate between.
         *    @param SimpleTestCase $test_case  Test case to run.
         *    @param SimpleScorer $scorer       Reporter to receive events.
         */
        function SimpleRunner(&$test_case, &$scorer) {
            $this->_test_case = &$test_case;
            $this->_scorer = &$scorer;
        }
        
        /**
         *    Accessor for test case being run.
         *    @return SimpleTestCase    Test case.
         *    @access protected
         */
        function &_getTestCase() {
            return $this->_test_case;
        }
        
        /**
         *    Runs the test methods in the test case.
         *    @param SimpleTest $test_case    Test case to run test on.
         *    @param string $method           Name of test method.
         *    @access public
         */
        function run() {
            $methods = get_class_methods(get_class($this->_test_case));
            foreach ($methods as $method) {
                if (! $this->_isTest($method)) {
                    continue;
                }
                if ($this->_isConstructor($method)) {
                    continue;
                }
                $this->_scorer->paintMethodStart($method);
                $this->_scorer->invoke($this, $method);
                $this->_scorer->paintMethodEnd($method);
            }
        }
        
        /**
         *    Tests to see if the method is the constructor and
         *    so should be ignored.
         *    @param string $method        Method name to try.
         *    @return boolean              True if constructor.
         *    @access protected
         */
        function _isConstructor($method) {
            return SimpleTestCompatibility::isA(
                    $this->_test_case,
                    strtolower($method));
        }
        
        /**
         *    Tests to see if the method is a test that should
         *    be run. Currently any method that starts with 'test'
         *    is a candidate.
         *    @param string $method        Method name to try.
         *    @return boolean              True if test method.
         *    @access protected
         */
        function _isTest($method) {
            return strtolower(substr($method, 0, 4)) == 'test';
        }
        
        /**
         *    Invokes a test method and buffered with setUp()
         *    and tearDown() calls.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            $this->_test_case->before();
            $this->_test_case->setUp();
            $this->_test_case->$method();
            $this->_test_case->tearDown();
            $this->_test_case->after();
        }

        /**
         *    Paints the start of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodStart($test_name) {
            $this->_scorer->paintMethodStart($test_name);
        }
        
        /**
         *    Paints the end of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodEnd($test_name) {
            $this->_scorer->paintMethodEnd($test_name);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintPass($message) {
            $this->_scorer->paintPass($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintFail($message) {
            $this->_scorer->paintFail($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message    Text of error formatted by
         *                              the test case.
         *    @access public
         */
        function paintError($message) {
            $this->_scorer->paintError($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param Exception $exception     Object thrown.
         *    @access public
         */
        function paintException($exception) {
            $this->_scorer->paintException($exception);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintMessage($message) {
            $this->_scorer->paintMessage($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintFormattedMessage($message) {
            $this->_scorer->paintFormattedMessage($message);
        }
        
        /**
         *    Chains to the wrapped reporter.
         *    @param string $type        Event type as text.
         *    @param mixed $payload      Message or object.
         *    @return boolean            Should return false if this
         *                               type of signal should fail the
         *                               test suite.
         *    @access public
         */
        function paintSignal($type, &$payload) {
            $this->_scorer->paintSignal($type, $payload);
        }
    }

    /**
     *    Extension that traps errors into an error queue.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleErrorTrappingRunner extends SimpleRunner {
        
        /**
         *    Takes in the test case and reporter to mediate between.
         *    @param SimpleTestCase $test_case  Test case to run.
         *    @param SimpleScorer $scorer       Reporter to receive events.
         */
        function SimpleErrorTrappingRunner(&$test_case, &$scorer) {
            $this->SimpleRunner($test_case, $scorer);
        }
        
        /**
         *    Invokes a test method and dispatches any
         *    untrapped errors. Called back from
         *    the visiting runner.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function invoke($method) {
            set_error_handler('simpleTestErrorHandler');
            parent::invoke($method);
            $queue = &SimpleErrorQueue::instance();
            while (list($severity, $message, $file, $line, $globals) = $queue->extract()) {
                $test_case = &$this->_getTestCase();
                $test_case->error($severity, $message, $file, $line, $globals);
            }
            restore_error_handler();
        }
    }
    
    /**
     *    Basic test case. This is the smallest unit of a test
     *    suite. It searches for
     *    all methods that start with the the string "test" and
     *    runs them. Working test cases extend this class.
	 *    @package		SimpleTest
	 *    @subpackage	UnitTester
     */
    class SimpleTestCase {
        var $_label;
        var $_runner;
        
        /**
         *    Sets up the test with no display.
         *    @param string $label    If no test name is given then
         *                            the class name is used.
         *    @access public
         */
        function SimpleTestCase($label = false) {
            $this->_label = $label ? $label : get_class($this);
            $this->_runner = false;
        }
        
        /**
         *    Accessor for the test name for subclasses.
         *    @return string           Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }
        
        /**
         *    Can modify the incoming reporter so as to run
         *    the tests differently. This version simply
         *    passes it straight through.
         *    @param SimpleReporter $reporter    Incoming observer.
         *    @return SimpleReporter
         *    @access protected
         */
        function &_createRunner(&$reporter) {
            return new SimpleErrorTrappingRunner($this, $reporter);
        }
        
        /**
         *    Uses reflection to run every method within itself
         *    starting with the string "test".
         *    @param SimpleReporter $reporter    Current test reporter.
         *    @access public
         */
        function run(&$reporter) {
            $reporter->paintCaseStart($this->getLabel());
            $this->_runner = &$this->_createRunner($reporter);
            $this->_runner->run();
            $reporter->paintCaseEnd($this->getLabel());
            return $reporter->getStatus();
        }
        
        /**
         *    Runs test case specific code before the user setUp().
         *    @access protected
         */
        function before() {
        }
          
        /**
         *    Runs test case specific code after the user tearDown().
         *    @access protected
         */
        function after() {
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
         *    @param string $message        Message to send.
         *    @access public
         */
        function pass($message = "Pass") {
            $this->_runner->paintPass($message . $this->getAssertionLine(' at line [%d]'));
        }
        
        /**
         *    Sends a fail event with a message.
         *    @param string $message        Message to send.
         *    @access public
         */
        function fail($message = "Fail") {
            $this->_runner->paintFail($message . $this->getAssertionLine(' at line [%d]'));
        }
        
        /**
         *    Formats a PHP error and dispatches it to the
         *    runner.
         *    @param integer $severity  PHP error code.
         *    @param string $message    Text of error.
         *    @param string $file       File error occoured in.
         *    @param integer $line      Line number of error.
         *    @param hash $globals      PHP super global arrays.
         *    @access public
         */
        function error($severity, $message, $file, $line, $globals) {
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            $this->_runner->paintError(
                    "Unexpected PHP error [$message] severity [$severity] in [$file] line [$line]");
        }
        
        /**
         *    Sends a user defined event to the test runner.
         *    This is for small scale extension where
         *    both the test case and either the runner or
         *    display are subclassed.
         *    @param string $type       Type of event.
         *    @param mixed $payload     Object or message to deliver.
         *    @access public
         */
        function signal($type, &$payload) {
            $this->_runner->paintSignal($type, $payload);
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
         *    @param SimpleExpectation $expectation  Expectation subclass.
         *    @param mixed $test_value               Value to compare.
         *    @param string $message                 Message to display.
         *    @return boolean                        True on pass
         *    @access public
         */
        function assertExpectation(&$expectation, $test_value, $message = '%s') {
            return $this->assertTrue(
                    $expectation->test($test_value),
                    sprintf($message, $expectation->testMessage($test_value)));
        }
        
        /**
         *    Called from within the test methods to register
         *    passes and failures.
         *    @param boolean $result    Pass on true.
         *    @param string $message    Message to display describing
         *                              the test state.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertTrue($result, $message = false) {
            if (! $message) {
                $message = 'True assertion got ' . ($result ? 'True' : 'False');
            }
            if ($result) {
                $this->pass($message);
                return true;
            } else {
                $this->fail($message);
                return false;
            }
        }
        
        /**
         *    Will be true on false and vice versa. False
         *    is the PHP definition of false, so that null,
         *    empty strings, zero and an empty array all count
         *    as false.
         *    @param boolean $result    Pass on false.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertFalse($result, $message = false) {
            if (! $message) {
                $message = 'False assertion got ' . ($result ? 'True' : 'False');
            }
            return ! $this->assertTrue(! $result, $message);
        }
        
        /**
         *    Uses a stack trace to find the line of an assertion.
         *    @param string $format    String formatting.
         *    @param array $stack      Stack frames top most first. Only
         *                             needed if not using the PHP
         *                             backtrace function.
         *    @return string           Line number of first assert*
         *                             method embedded in format string.
         *    @access public
         */
        function getAssertionLine($format = '%d', $stack = false) {
            if ($stack === false) {
                $stack = SimpleTestCompatibility::getStackTrace();
            }
            return SimpleDumper::getFormattedAssertionLine($stack, $format);
        }
        
        /**
         *    Sends a formatted dump of a variable to the
         *    test suite for those emergency debugging
         *    situations.
         *    @param mixed $variable    Variable to display.
         *    @param string $message    Message to display.
         *    @return mixed             The original variable.
         *    @access public
         */
        function dump($variable, $message = false) {
            $formatted = SimpleDumper::dump($variable);
            if ($message) {
                $formatted = $message . "\n" . $formatted;
            }
            $this->_runner->paintFormattedMessage($formatted);
            return $variable;
        }
        
        /**
         *    Dispatches a text message straight to the
         *    test suite. Useful for status bar displays.
         *    @param string $message        Message to show.
         *    @access public
         */
        function sendMessage($message) {
            $this->_runner->PaintMessage($message);
        }
        
        /**
         *    Accessor for the number of subtests.
         *    @return integer           Number of test cases.
         *    @access public
         */
        function getSize() {
            return 1;
        }
    }
    
    /**
     *    This is a composite test class for combining
     *    test cases and other RunnableTest classes into
     *    a group test.
	 *    @package		SimpleTest
	 *    @subpackage	UnitTester
     */
    class GroupTest {
        var $_label;
        var $_test_cases;
        
        /**
         *    Sets the name of the test suite.
         *    @param string $label    Name sent at the start and end
         *                            of the test.
         *    @access public
         */
        function GroupTest($label) {
            $this->_label = $label;
            $this->_test_cases = array();
        }
        
        /**
         *    Accessor for the test name for subclasses.
         *    @return string           Name of the test.
         *    @access public
         */
        function getLabel() {
            return $this->_label;
        }
        
        /**
         *    Adds a test into the suite.
         *    @param SimpleTestCase $test_case  Suite or individual test
         *                                      case implementing the
         *                                      runnable test interface.
         *    @access public
         */
        function addTestCase(&$test_case) {
            $this->_test_cases[] = &$test_case;
        }
        
        /**
         *    Builds a group test from a library of test cases.
         *    The new group is composed into this one.
         *    @param string $test_file    File name of library with
         *                                test case classes.
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
                if (! $this->_isTestCase($class)) {
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
         *    @param string $class            Class name.
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
         *    @param SimpleReporter $reporter    Current test reporter.
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
         *    @return integer     Total count of cases in the group.
         *    @access public
         */
        function getSize() {
            $count = 0;
            foreach ($this->_test_cases as $case) {
                $count += $case->getSize();
            }
            return $count;
        }
    }
?>