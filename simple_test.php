<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'observer.php');
    
    /**
     *    Interface used by the test displays and group tests.
     */
    class RunnableTest extends TestObservable {
        var $_label;
        
        /**
         *    Sets up the test name and starts with no attached
         *    displays.
         *    @param $label        Name of test.
         */
        function RunnableTest($label) {
            $this->TestObservable();
            $this->_label = $label;
        }
        
        /**
         *    Accessor for the test name for subclasses.
         *    @return            Name of the test.
         */
        function getLabel() {
            return $this->_label;
        }
        
        /**
         *    Abstract. Runs the top level test for this class.
         */
        function run() {
        }
    }

    /**
     *    Basic test case. This is the smallest unit of a test
     *    suite. It searches for
     *    all methods that start with the the string "test" and
     *    runs them. Working test cases extend this class.
     */
    class TestCase extends RunnableTest {
        
        /**
         *    Sets up the test with no display.
         *    @param $label        If no test name is given then
         *                         the class name is used.
         */
        function TestCase($label = "") {
            if ($label == "") {
                $label = get_class($this);
            }
            $this->RunnableTest($label);
        }
        
        /**
         *    Uses reflection to run every method within itself
         *    starting with the string "test".
         */
        function run() {
            $this->notify(new TestStart($this->getLabel()));
            $methods = get_class_methods(get_class($this));
            sort($methods);
            foreach ($methods as $method) {
                if (strtolower(substr($method, 0, 4)) != "test") {
                    continue;
                }
                if (is_a($this, strtolower($method))) {
                    continue;
                }
                $this->notify(new TestStart($method));
                $this->setUp();
                $this->$method();
                $this->tearDown();
                $this->notify(new TestEnd($method));
            }
            $this->notify(new TestEnd($this->getLabel()));
        }
        
        /**
         *    Called from within the test methods to register
         *    passes and failures.
         *    @param $result            Boolean, true on pass.
         *    @param $message           Message to display describing
         *                              the test state.
         */
        function assertTrue($result, $message = "True assertion failed.") {
            $this->notify(new TestResult($result, $message));
        }
        
        /**
         *    Sets up unit test wide variables at the start
         *    of each test method. To be overridden in
         *    actual test cases.
         */
        function setUp() {
        }
        
        /**
         *    Clears the data set in the setUp() method call.
         */
        function tearDown() {
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
         */
        function GroupTest($label) {
            $this->RunnableTest($label);
        }
        
        /**
         *    Adds a test into the suite.
         *    @param $test_case        Suite or individual test
         *                             case implementing the
         *                             runnable test interface.
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
         */
        function addTestFile($test_file) {
            $existing_classes = get_declared_classes();
            require($test_file);
            $group = new GroupTest($test_file);
            foreach (get_declared_classes() as $class) {
                if (in_array($class, $existing_classes)) {
                    continue;
                }
                if (!$this->_is_test_case($class)) {
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
         */
        function _is_test_case($class) {
            while ($class = get_parent_class($class)) {
                if (strtolower($class) == "testcase") {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Invokes run() on all of the held tests.
         */
        function run() {
            $this->notify(new TestStart($this->getLabel()));
            for ($i = 0; $i < count($this->_test_cases); $i++) {
                $this->_test_cases[$i]->run();
            }
            $this->notify(new TestEnd($this->getLabel()));
        }
    }
    
    /**
     *    Recipient of generated test messages that can display
     *    page footers and headers. Also keeps track of the
     *    test nesting. This is the main base class on which
     *    to build the finished test (page based) displays.
     */
    class TestDisplay extends TestReporter {
        var $_test_stack;
        var $_passes;
        var $_fails;
        
        /**
         *    Starts the display with no results in.
         */
        function TestDisplay() {
            $this->TestReporter();
            $this->_test_stack = array();
            $this->_passes = 0;
            $this->_fails = 0;
        }
        
        /**
         *    Paints the start of a test. Will also paint
         *    the page header and footer if this is the
         *    first test.
         *    @param $test_name        Name of test that is starting.
         */
        function paintStart($test_name) {
            if (count($this->_test_stack) == 0) {
                $this->paintHeader($test_name);
            }
            $this->_test_stack[] = $test_name;
        }
        
        /**
         *    Paints the end of a test. Will paint the page
         *    footer if the stack of tests has unwound.
         *    @param $test_name        Name of test that is ending.
         */
        function paintEnd($test_name) {
            array_pop($this->_test_stack);
            if (count($this->_test_stack) == 0) {
                $this->paintFooter($test_name);
            }
        }
        
        /**
         *    Increments the pass count.
         *    @param $message        Message is ignored.
         */
        function paintPass($message) {
            $this->_passes++;
        }
        
        /**
         *    Increments the fail count.
         *    @param $message        Message is ignored.
         */
        function paintFail($message) {
            $this->_fails++;
        }
        
        /**
         *    Paints the test document header. Abstract.
         *    @param $test_name        First test top level
         *                             to start.
         */
        function paintHeader($test_name) {
        }
        
        /**
         *    Paints the test document footer. Abstract.
         *    @param $test_name        The top level test.
         */
        function paintFooter($test_name) {
        }
        
        /**
         *    Accessor for internal test stack. For
         *    subclasses that need to see the whole test
         *    history for display purposes.
         *    @return      List of methods in nesting order.
         */
        function getTestList() {
            return $this->_test_stack;
        }
        
        /**
         *    Accessor for the number of passes so far.
         *    @return        Number of passes.
         */
        function getPassCount() {
            return $this->_passes;
        }
        
        /**
         *    Accessor for the number of fails so far.
         *    @return        Number of fails.
         */
        function getFailCount() {
            return $this->_fails;
        }
    }
?>
