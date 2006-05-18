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
            try {
                parent::invoke($method);
            } catch (Exception $exception) {
                $test_case = $this->getTestCase();
                $test_case->exception($exception);
            }
        }
    }

    class ExceptionExpectation extends SimpleExpectation {
        private $expected;

        function __construct($expected, $message = '%s') {
            $this->expected = $expected;
            parent::__construct($message);
        }

        function test($compare) {
            if (is_string($this->expected)) {
                return ($compare instanceof $this->expected);
            }
            if (get_class($compare) != get_class($this->expected)) {
                return false;
            }
            return $compare->getMessage() == $this->expected->getMessage();
        }

        function testMessage($compare) {
            if (is_string($this->expected)) {
                return "Exception [" . $this->dumpException($compare) .
                        "] should be type [" . $this->expected . "]";
            }
            return "Exception [" . $this->dumpException($compare) .
                    "] should match [" .
                    $this->dumpException($this->expected) . "]";
        }

        protected function dumpException($exception) {
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
    class SimpleExpectedExceptionQueue {
        private $queue;

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
        function expectException($expected, $message = '%s') {
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
    }
?>