<?php
    // $Id$
    
    /**
     *    Can recieve test events and display them. Display
     *    is achieved by making display methods available
     *    and visiting the incoming event. Abstract.
     */
    class TestReporter {
        
        /**
         *    Does nothing.
         *    @access public
         *    @abstract
         */
        function TestReporter() {
        }
        
        /**
         *    Hook for invoking the actual test method.
         *    @param SimpleTest $test_case    The test case method to call.
         *    @param string $method           Method name to invoke.
         *    @access public
         */
        function invoke(&$test_case, $method) {
            $test_case->invoke($this, $method);
        }
        
        /**
         *    Accessor for current status. Will be false
         *    if there have been any failures or exceptions.
         *    Used for command line tools.
         *    @return boolean        True if no failures.
         *    @access public
         *    @abstract
         */
        function getStatus() {
        }
        
        /**
         *    Paints the start of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodStart($test_name) {
        }
        
        /**
         *    Paints the end of a test method.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintMethodEnd($test_name) {
        }
         
        /**
         *    Paints the start of a test case.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintCaseStart($test_name) {
        }
        
        /**
         *    Paints the end of a test case.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintCaseEnd($test_name) {
        }
       
        /**
         *    Paints the start of a group test.
         *    @param string $test_name     Name of test or other label.
         *    @param integer $size         Number of test cases starting.
         *    @access public
         */
        function paintGroupStart($test_name, $size) {
        }
        
        /**
         *    Paints the end of a group test.
         *    @param string $test_name     Name of test or other label.
         *    @access public
         */
        function paintGroupEnd($test_name) {
        }
        
        /**
         *    Paints a pass. This will often output nothing.
         *    @param string $message        Passing message.
         *    @access public
         */
        function paintPass($message) {
        }
        
        /**
         *    Paints a failure.
         *    @param string $message        Failure message from test.
         *    @access public
         */
        function paintFail($message) {
        }
        
        /**
         *    Deals with PHP 4 throwing an error.
         *    @param string $message    Text of error formatted by
         *                              the test case.
         *    @access public
         */
        function paintError($message) {
            $this->paintException($message);
        }
        
        /**
         *    Deals with PHP 5 throwing an exception
         *    This isn't really implemented yet.
         *    @param Exception $exception     Object thrown.
         *    @access public
         */
        function paintException($exception) {
            $this->_status = false;
        }
        
        /**
         *    Paints a simple supplementary message.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintMessage($message) {
        }
        
        /**
         *    Paints a formatted ASCII message such as a
         *    variable dump.
         *    @param string $message        Text to display.
         *    @access public
         */
        function paintFormattedMessage($message) {
        }
        
        /**
         *    By default just ignores user generated
         *    events.
         *    @param string $type        Event type as text.
         *    @param mixed $payload      Message or object.
         *    @return boolean            Should return false if this
         *                               type of signal should fail the
         *                               test suite.
         *    @access public
         */
        function paintSignal($type, &$payload) {
            return true;
        }
    }
    
    /**
     *    Recipient of generated test messages that can display
     *    page footers and headers. Also keeps track of the
     *    test nesting. This is the main base class on which
     *    to build the finished test (page based) displays.
     */
    class SimplePageReporter extends TestReporter {
        var $_test_stack;
        var $_passes;
        var $_fails;
        var $_exceptions;
        var $_size;
        var $_progress;
        
        /**
         *    Starts the display with no results in.
         *    @access public
         */
        function SimplePageReporter() {
            $this->TestReporter();
            $this->_test_stack = array();
            $this->_passes = 0;
            $this->_fails = 0;
            $this->_exceptions = 0;
            $this->_size = null;
            $this->_progress = 0;
        }
        
        /**
         *    Paints the start of a group test. Will also paint
         *    the page header and footer if this is the
         *    first test. Will stash the size if the first
         *    start.
         *    @param string $test_name   Name of test that is starting.
         *    @param integer $size       Number of test cases starting.
         *    @access public
         */
        function paintGroupStart($test_name, $size) {
            if (!isset($this->_size)) {
                $this->_size = $size;
            }
            if (count($this->_test_stack) == 0) {
                $this->paintHeader($test_name);
            }
            $this->_test_stack[] = $test_name;
        }
        
        /**
         *    Paints the end of a group test. Will paint the page
         *    footer if the stack of tests has unwound.
         *    @param string $test_name   Name of test that is ending.
         *    @param integer $progress   Number of test cases ending.
         *    @access public
         */
        function paintGroupEnd($test_name) {
            array_pop($this->_test_stack);
            if (count($this->_test_stack) == 0) {
                $this->paintFooter($test_name);
            }
        }
        
        /**
         *    Paints the start of a test case. Will also paint
         *    the page header and footer if this is the
         *    first test. Will stash the size if the first
         *    start.
         *    @param string $test_name   Name of test that is starting.
         *    @access public
         */
        function paintCaseStart($test_name) {
            if (! isset($this->_size)) {
                $this->_size = 1;
            }
            if (count($this->_test_stack) == 0) {
                $this->paintHeader($test_name);
            }
            $this->_test_stack[] = $test_name;
        }
        
        /**
         *    Paints the end of a test case. Will paint the page
         *    footer if the stack of tests has unwound.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintCaseEnd($test_name) {
            $this->_progress++;
            array_pop($this->_test_stack);
            if (count($this->_test_stack) == 0) {
                $this->paintFooter($test_name);
            }
        }
        
        /**
         *    Paints the start of a test method.
         *    @param string $test_name   Name of test that is starting.
         *    @access public
         */
        function paintMethodStart($test_name) {
            $this->_test_stack[] = $test_name;
        }
        
        /**
         *    Paints the end of a test method. Will paint the page
         *    footer if the stack of tests has unwound.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintMethodEnd($test_name) {
            array_pop($this->_test_stack);
        }
        
        /**
         *    Increments the pass count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintPass($message) {
            $this->_passes++;
        }
        
        /**
         *    Increments the fail count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintFail($message) {
            $this->_fails++;
        }
        
        /**
         *    Paints a PHP error or exception.
         *    @param string $message        Message is ignored.
         *    @access public
         *    @abstract
         */
        function paintException($message) {
            $this->_exceptions++;
        }
        
        /**
         *    Paints the test document header.
         *    @param string $test_name     First test top level
         *                                 to start.
         *    @access public
         *    @abstract
         */
        function paintHeader($test_name) {
        }
        
        /**
         *    Paints the test document footer.
         *    @param string $test_name        The top level test.
         *    @access public
         *    @abstract
         */
        function paintFooter($test_name) {
        }
        
        /**
         *    Accessor for internal test stack. For
         *    subclasses that need to see the whole test
         *    history for display purposes.
         *    @return array     List of methods in nesting order.
         *    @access public
         */
        function getTestList() {
            return $this->_test_stack;
        }
        
        /**
         *    Accessor for the number of passes so far.
         *    @return integer       Number of passes.
         *    @access public
         */
        function getPassCount() {
            return $this->_passes;
        }
        
        /**
         *    Accessor for the number of fails so far.
         *    @return integer       Number of fails.
         *    @access public
         */
        function getFailCount() {
            return $this->_fails;
        }
        
        /**
         *    Accessor for the number of untrapped errors
         *    so far.
         *    @return integer       Number of exceptions.
         *    @access public
         */
        function getExceptionCount() {
            return $this->_exceptions;
        }
        
        /**
         *    Accessor for current status. Will be false
         *    if there have been any failures or exceptions.
         *    Used for command line tools.
         *    @return boolean        True if no failures.
         *    @access public
         */
        function getStatus() {
            if ($this->_exceptions + $this->_fails > 0) {
                return false;
            }
            return true;
        }
        
        /**
         *    Accessor for total test size in number
         *    of test cases. Null until the first
         *    test is started.
         *    @return integer   Total number of cases at start.
         *    @access public
         */
        function getTestCaseCount() {
            return $this->_size;
        }
        
        /**
         *    Accessor for the number of test cases
         *    completed so far.
         *    @return integer   Number of ended cases.
         *    @access public
         */
        function getTestCaseProgress() {
            return $this->_progress;
        }
    }
    
    /**
     *    @deprecated
     */
    class TestDisplay extends SimplePageReporter {
        function TestDisplay() {
            $this->SimplePageReporter();
        }
    }
?>