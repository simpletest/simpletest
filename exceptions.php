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
    require_once(dirname(__FILE__) . '/invoker.php');
    require_once(dirname(__FILE__) . '/expectation.php');

    /**
     *    Extension that traps exceptions and turns them into
     *    an error message. PHP5 only.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleExceptionTrappingInvoker extends SimpleInvokerDecorator {

        /**
         *    Stores the invoker to be wrapped.
         *    @param SimpleInvoker $invoker   Test method runner.
         */
        function SimpleExceptionTrappingInvoker($invoker) {
            $this->SimpleInvokerDecorator($invoker);
        }

        /**
         *    Invokes a test method whilst trapping expected
         *    exceptions. Any left over unthrown exceptions
         *    are then reported as failures.
         *    @param string $method    Test method to call.
         */
        function invoke($method) {
            $queue = SimpleExceptionQueue::instance();
            $queue->clear();
            try {
                parent::invoke($method);
            } catch (Exception $exception) {
                $test_case = $this->getTestCase();
                if (! $queue->isExpected($test_case, $exception)) {
                    $test_case->exception($exception);
                }
                $test_case->pass('Swallowed exception [' .
                        get_class($exception) . ']');
                $queue->clear();
            }
        }
    }

    /**
     *    Tests exceptions either by type or the exact
     *    exception. This could be improved to accept
     *    a pattern expectation to test the error
     *    message, but that will have to come later.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class ExceptionExpectation extends SimpleExpectation {
        private $expected;

        /**
         *    Sets up the conditions to test against.
         *    If the expected value is a string, then
         *    it will act as a test of the class name.
         *    An exception as the comparison will
         *    trigger an identical match. Writing this
         *    down now makes it look doubly dumb. I hope
         *    come up with a better scheme later.
         *    @param mixed $expected   A class name or an actual
         *                             exception to compare with.
         *    @param string $message   Message to display.
         */
        function __construct($expected, $message = '%s') {
            $this->expected = $expected;
            parent::__construct($message);
        }

        /**
         *    Carry out the test.
         *    @param Exception $compare    Value to check.
         *    @return boolean              True if matched.
         */
        function test($compare) {
            if (is_string($this->expected)) {
                return ($compare instanceof $this->expected);
            }
            if (get_class($compare) != get_class($this->expected)) {
                return false;
            }
            return $compare->getMessage() == $this->expected->getMessage();
        }

        /**
         *    Create the message to display describing the test.
         *    @param Exception $compare     Exception to match.
         *    @return string                Final message.
         */
        function testMessage($compare) {
            if (is_string($this->expected)) {
                return "Exception [" . $this->describeException($compare) .
                        "] should be type [" . $this->expected . "]";
            }
            return "Exception [" . $this->describeException($compare) .
                    "] should match [" .
                    $this->dumpException($this->expected) . "]";
        }

        /**
         *    Summary of an Exception object.
         *    @param Exception $compare     Exception to describe.
         *    @return string                Text description.
         */
        protected function describeException($exception) {
            return get_class($exception) . ": " . $exception->getMessage();
        }
    }

    /**
     *    Stores expected exceptions for when they
     *    get thrown. Saves the irritating try...catch
     *    block.
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
    class SimpleExceptionQueue {
        private $queue;
        static $instance = false;

        /**
         *    Clears down the queue ready for action.
         */
        function __construct() {
            $this->clear();
        }

        /**
         *    Sets up an expectation of an exception.
         *    This has the effect of intercepting an
         *    exception that matches.
         *    @param SimpleExpectation $expected    Expected exception to match.
         *    @param string $message                Message to display.
         *    @access public
         */
        function expectException($expected = false, $message = '%s') {
            if ($expected === false) {
                $expected = new AnythingExpectation();
            }
            if (! SimpleExpectation::isExpectation($expected)) {
                $expected = new ExceptionExpectation($expected);
            }
            array_push(
                    $this->queue,
                    array($expected, $message));
        }

        /**
         *    Compares the expected exception with any
         *    in the queue. Issues a pass or fail and
         *    returns the state of the test.
         *    @param SimpleTestCase $test    Test case to send messages to.
         *    @param Exception $exception    Exception to compare.
         *    @return boolean                False on no match.
         */
        function isExpected($test, $exception) {
            if (count($this->queue) == 0) {
                return false;
            }
            list($expectation, $message) = array_shift($this->queue);
            return $test->assert($expectation, $exception, $message);
        }

        /**
         *    Discards the contents of the error queue.
         */
        function clear() {
            $this->queue = array();
        }

        /**
         *    Singleton pending a test context object.
         *    @return SimpleExceptionQueue    Global instance.
         */
        static function instance() {
            if (! self::$instance) {
                self::$instance = new SimpleExceptionQueue();
            }
            return self::$instance;
        }
    }
?>