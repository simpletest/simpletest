<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	MockObjects
     *	@version	$Id$
     */

    /**
     * @ignore    originally defined in simple_test.php
     */
    if (! defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    /**#@+
     * include SimpleTest files
     */
    require_once(SIMPLE_TEST . 'expectation.php');
    require_once(SIMPLE_TEST . 'options.php');
    require_once(SIMPLE_TEST . 'dumper.php');
    /**#@-*/
    
    /**
     * character simpletest will substitute for any value
     */
    define('MOCK_WILDCARD', '*');
    
    /**
     *    A wildcard expectation always matches.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class WildcardExpectation extends SimpleExpectation {
        
        /**
         *    Chains constructor only.
         *    @access public
         */
        function WildcardExpectation() {
            $this->SimpleExpectation();
        }
        
        /**
         *    Tests the expectation. Always true.
         *    @param mixed $compare  Ignored.
         *    @return boolean        True.
         *    @access public
         */
        function test($compare) {
            return true;
        }
        
        /**
         *    Returns a human readable test message.
         *    @param mixed $compare      Comparison value.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($compare) {
            $dumper = &$this->_getDumper();
            return 'Wildcard always matches [' . $dumper->describeValue($compare) . ']';
        }
    }
    
    /**
     *    Parameter comparison assertion.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class ParametersExpectation extends SimpleExpectation {
        var $_expected;
        
        /**
         *    Sets the expected parameter list.
         *    @param array $parameters  Array of parameters including
         *                              those that are wildcarded.
         *                              If the value is not an array
         *                              then it is considered to match any.
         *    @param mixed $wildcard    Any parameter matching this
         *                              will always match.
         *    @access public
         */
        function ParametersExpectation($expected = false) {
            $this->SimpleExpectation();
            $this->_expected = $expected;
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param array $parameters     Comparison values.
         *    @return boolean              True if correct.
         *    @access public
         */
        function test($parameters) {
            if (!is_array($this->_expected)) {
                return true;
            }
            if (count($this->_expected) != count($parameters)) {
                return false;
            }
            for ($i = 0; $i < count($this->_expected); $i++) {
                if (! $this->_testParameter($parameters[$i], $this->_expected[$i])) {
                    return false;
                }
            }
            return true;
        }
        
        /**
         *    Tests an individual parameter.
         *    @param mixed $parameter    Value to test.
         *    @param mixed $expected     Comparison value.
         *    @return boolean            True if expectation
         *                               fulfilled.
         *    @access private
         */
        function _testParameter($parameter, $expected) {
            if (is_a($expected, 'SimpleExpectation')) {
                return $expected->test($parameter);
            }
            return ($expected === $parameter);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param array $comparison   Incoming parameter list.
         *    @return string             Description of success
         *                               or failure.
         *    @access public
         */
        function testMessage($parameters) {
            if ($this->test($parameters)) {
                return "Expectation of " . count($this->_expected) .
                        " arguments of [" . $this->_renderArguments($this->_expected) .
                        "] is correct";
            } else {
                return $this->_describeDifference($this->_expected, $parameters);
            }
        }
        
        /**
         *    Message to display if expectation differs from
         *    the parameters actually received.
         *    @param array $expected      Expected parameters as list.
         *    @param array $parameters    Actual parameters received.
         *    @return string              Description of difference.
         *    @access private
         */
        function _describeDifference($expected, $parameters) {
            if (count($expected) != count($parameters)) {
                return "Expected " . count($expected) .
                        " arguments of [" . $this->_renderArguments($expected) .
                        "] but got " . count($parameters) .
                        " arguments of [" . $this->_renderArguments($parameters) . "]";
            }
            $messages = array();
            for ($i = 0; $i < count($expected); $i++) {
                $comparison = $this->_coerceToExpectation($expected[$i]);
                if (! $comparison->test($parameters[$i])) {
                    $messages[] = "parameter " . ($i + 1) . " with [" .
                            $comparison->testMessage($parameters[$i]) . "]";
                }
            }
            return "Mock expectation differs at " . implode(" and ", $messages);
        }
        
        /**
         *    Creates an identical expectation if the
         *    object/value is not already some type
         *    of expectation.
         *    @param mixed $expected      Expected value.
         *    @return SimpleExpectation   Expectation object.
         *    @access private
         */
        function _coerceToExpectation($expected) {
            if (is_a($expected, 'SimpleExpectation')) {
                return $expected;
            }
            return new IdenticalExpectation($expected);
        }
        
        /**
         *    Renders the argument list as a string for
         *    messages.
         *    @param array $args    Incoming arguments.
         *    @return string        Simple description of type and value.
         *    @access private
         */
        function _renderArguments($args) {
            $descriptions = array();
            if (is_array($args)) {
                foreach ($args as $arg) {
                    $dumper = &new SimpleDumper();
                    $descriptions[] = $dumper->describeValue($arg);
                }
            }
            return implode(', ', $descriptions);
        }
    }
    
    /**
     *    Retrieves values and references by searching the
     *    parameter lists until a match is found.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class CallMap {
        var $_map;
        
        /**
         *    Creates an empty call map.
         *    @access public
         */
        function CallMap() {
            $this->_map = array();
        }
        
        /**
         *    Stashes a value against a method call.
         *    @param array $parameters    Arguments including wildcards.
         *    @param mixed $value         Value copied into the map.
         *    @access public
         */
        function addValue($parameters, $value) {
            $this->addReference($parameters, $value);
        }
        
        /**
         *    Stashes a reference against a method call.
         *    @param array $parameters    Array of arguments (including wildcards).
         *    @param mixed $reference     Array reference placed in the map.
         *    @access public
         */
        function addReference($parameters, &$reference) {
            $place = count($this->_map);
            $this->_map[$place] = array();
            $this->_map[$place]["params"] = new ParametersExpectation($parameters);
            $this->_map[$place]["content"] = &$reference;
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. Returned by reference.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return object              Object held in the first matching
         *                                slot, otherwise null.
         *    @access public
         */
        function &findFirstMatch($parameters) {
            $slot = $this->_findFirstSlot($parameters);
            if (!isset($slot)) {
                return null;
            }
            return $slot["content"];
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. True if successful.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return boolean             True if a match is present.
         *    @access public
         */
        function isMatch($parameters) {
            return ($this->_findFirstSlot($parameters) != null);
        }
        
        /**
         *    Searches the map for a matching item.
         *    @param array $parameters    Parameters to search by
         *                                without wildcards.
         *    @return array               Reference to slot or null.
         *    @access private
         */
        function &_findFirstSlot($parameters) {
            for ($i = 0; $i < count($this->_map); $i++) {
                if ($this->_map[$i]["params"]->test($parameters)) {
                    return $this->_map[$i];
                }
            }
            return null;
        }
    }
    
    /**
     *    An empty collection of methods that can have their
     *    return values set. Used for prototyping.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class SimpleStub {
        var $_wildcard;
        var $_is_strict;
        var $_returns;
        var $_return_sequence;
        var $_call_counts;
        
        /**
         *    Sets up the wildcard and everything else empty.
         *    @param mixed $wildcard     Parameter matching wildcard.
         *    @param boolean$is_strict   Enables method name checks.
         *    @access public
         */
        function SimpleStub($wildcard, $is_strict = true) {
            $this->_wildcard = $wildcard;
            $this->_is_strict = $is_strict;
            $this->_returns = array();
            $this->_return_sequence = array();
            $this->_call_counts = array();
        }
        
        /**
         *    Replaces wildcard matches with wildcard
         *    expectations in the argument list.
         *    @param array $args      Raw argument list.
         *    @return array           Argument list with
         *                            expectations.
         *    @access private
         */
        function _replaceWildcards($args) {
            if ($args === false) {
                return false;
            }
            for ($i = 0; $i < count($args); $i++) {
                if ($args[$i] === $this->_wildcard) {
                    $args[$i] = new WildcardExpectation();
                }
            }
            return $args;
        }

        /**
         *    @deprecated
         */
        function clearHistory() {
            $this->_call_counts = array();
        }
        
        /**
         *    Returns the expected value for the method name.
         *    @param string $method       Name of method to simulate.
         *    @param array $args          Arguments as an array.
         *    @return mixed               Stored return.
         *    @access private
         */
        function &_invoke($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            return $this->_getReturn($method, $args, $step);
        }
        
        /**
         *    Triggers a PHP error if the method is not part
         *    of this object.
         *    @param string $method        Name of method.
         *    @param string $task          Description of task attempt.
         *    @access protected
         */
        function _dieOnNoMethod($method, $task) {
            if ($this->_is_strict && !method_exists($this, $method)) {
                trigger_error(
                        "Cannot $task as no ${method}() in class " . get_class($this),
                        E_USER_ERROR);
            }
        }
        
        /**
         *    Adds one to the call count of a method.
         *    @param string $method        Method called.
         *    @param array $args           Arguments as an array.
         *    @access protected
         */
        function _addCall($method, $args) {
            if (!isset($this->_call_counts[$method])) {
                $this->_call_counts[$method] = 0;
            }
            $this->_call_counts[$method]++;
        }
        
        /**
         *    Fetches the call count of a method so far.
         *    @param string $method        Method name called.
         *    @return                      Number of calls so far.
         *    @access public
         */
        function getCallCount($method) {
            $this->_dieOnNoMethod($method, "get call count");
            $method = strtolower($method);
            if (!isset($this->_call_counts[$method])) {
                return 0;
            }
            return $this->_call_counts[$method];
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value for all calls to this method.
         *    @param string $method       Method name.
         *    @param mixed $value         Result of call passed by value.
         *    @param array $args          List of parameters to match
         *                                including wildcards.
         *    @access public
         */
        function setReturnValue($method, $value, $args = false) {
            $this->_dieOnNoMethod($method, "set return value");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (!isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap();
            }
            $this->_returns[$method]->addValue($args, $value);
        }
                
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param integer $timing   Number of calls in the future
         *                             to which the result applies. If
         *                             not set then all calls will return
         *                             the value.
         *    @param string $method    Method name.
         *    @param mixed $value      Result of call passed by value.
         *    @param array $args       List of parameters to match
         *                             including wildcards.
         *    @access public
         */
        function setReturnValueAt($timing, $method, $value, $args = false) {
            $this->_dieOnNoMethod($method, "set return value sequence");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap();
            }
            $this->_return_sequence[$method][$timing]->addValue($args, $value);
        }
         
        /**
         *    Sets a return for a parameter list that will
         *    be passed by reference for all calls.
         *    @param string $method       Method name.
         *    @param mixed $reference     Result of the call will be this object.
         *    @param array $args          List of parameters to match
         *                                including wildcards.
         *    @access public
         */
        function setReturnReference($method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (!isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap();
            }
            $this->_returns[$method]->addReference($args, $reference);
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param integer $timing    Number of calls in the future
         *                              to which the result applies. If
         *                              not set then all calls will return
         *                              the value.
         *    @param string $method     Method name.
         *    @param mixed $reference   Result of the call will be this object.
         *    @param array $args        List of parameters to match
         *                              including wildcards.
         *    @access public
         */
        function setReturnReferenceAt($timing, $method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference sequence");
            $args = $this->_replaceWildcards($args);
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap();
            }
            $this->_return_sequence[$method][$timing]->addReference($args, $reference);
        }
        
        /**
         *    Finds the return value matching the incoming
         *    arguments. If there is no matching value found
         *    then an error is triggered.
         *    @param string $method      Method name.
         *    @param array $args         Calling arguments.
         *    @param integer $step       Current position in the
         *                               call history.
         *    @return mixed              Stored return.
         *    @access protected
         */
        function &_getReturn($method, $args, $step) {
            if (isset($this->_return_sequence[$method][$step])) {
                if ($this->_return_sequence[$method][$step]->isMatch($args)) {
                    return $this->_return_sequence[$method][$step]->findFirstMatch($args);
                }
            }
            if (isset($this->_returns[$method])) {
                return $this->_returns[$method]->findFirstMatch($args);
            }
            $this->_warnOnNoReturn($method);
            return null;
        }
        
        /**
         *    What to do if there is no return value set. Does
         *    nothing for a stub.
         *    @param string $method      Method name.
         *    @access protected
         */
        function _warnOnNoReturn($method) {
        }
    }
    
    /**
     *    An empty collection of methods that can have their
     *    return values set and expectations made of the
     *    calls upon them. The mock will assert the
     *    expectations against it's attached test case in
     *    addition to the server stub behaviour.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class SimpleMock extends SimpleStub {
        var $_test;
        var $_expected_counts;
        var $_max_counts;
        var $_min_counts;
        var $_expected_args;
        var $_args_sequence;
        var $_require_return;
        
        /**
         *    Creates an empty return list and expectation list.
         *    All call counts are set to zero.
         *    @param SimpleTestCase $test    Test case to test expectations in.
         *    @param mixed $wildcard         Parameter matching wildcard.
         *    @param boolean $is_strict      Enables method name checks on
         *                                   expectations.
         *    @access public
         */
        function SimpleMock(&$test, $wildcard, $is_strict = true) {
            $this->SimpleStub($wildcard, $is_strict);
            $this->_test = &$test;
            $this->_expected_counts = array();
            $this->_max_counts = array();
            $this->_min_counts = array();
            $this->_expected_args = array();
            $this->_args_sequence = array();
            $this->_require_return = false;
        }
        
        /**
         *    Accessor for attached unit test so that when
         *    subclassed, new expectations can be added easily.
         *    @return SimpleTestCase      Unit test passed in constructor.
         *    @access public
         */
        function &getTest() {
            return $this->_test;
        }
        
        /**
         *    @deprecated
         */
        function requireReturn() {
            $this->_require_return = true;
        }
        
        /**
         *    Die if bad arguments array is passed
         *    @param	mixed    $args    The arguments value to be checked.
         *    @param 	string   $task    Description of task attempt.
         *    @return   boolean           Valid arguments
         *    @access	private
         */
        function _CheckArgumentsArray($args, $task) {
        	if (!is_array($args)) {
        		trigger_error(
        			"Cannot $task as \$args parameter is not an array",
        			E_USER_ERROR);
        	}
        }
        
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. All
         *    calls will be compared to these expectations
         *    regardless of when the call is made.
         *    @param string $method        Method call to test.
         *    @param array $args           Expected parameters for the call
         *                                 including wildcards.
         *    @access public
         */
        function expectArguments($method, $args) {
            $this->_dieOnNoMethod($method, "set expected arguments");
            $this->_CheckArgumentsArray($args, "set expected arguments");
            $args = $this->_replaceWildcards($args);
            $this->_expected_args[strtolower($method)] =
                    new ParametersExpectation($args);
        }
        
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. The
         *    expected call count will be adjusted if it
         *    is set too low to reach this call.
         *    @param integer $timing    Number of calls in the future at
         *                              which to test. Next call is 0.
         *    @param string $method     Method call to test.
         *    @param array $args        Expected parameters for the call
         *                              including wildcards.
         *    @access public
         */
        function expectArgumentsAt($timing, $method, $args) {
            $this->_dieOnNoMethod($method, "set expected arguments at time");
            $this->_CheckArgumentsArray($args, "set expected arguments");
            $args = $this->_replaceWildcards($args);
            if (!isset($this->_sequence_args[$timing])) {
                $this->_sequence_args[$timing] = array();
            }
            $method = strtolower($method);
            $this->_sequence_args[$timing][$method] =
                    new ParametersExpectation($args);
        }
        
        /**
         *    Sets an expectation for the number of times
         *    a method will be called. The tally method
         *    is used to check this.
         *    @param string $method        Method call to test.
         *    @param integer $count        Number of times it should
         *                                 have been called at tally.
         *    @access public
         */
        function expectCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set expected call count");
            $this->_expected_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Sets the number of times a method may be called
         *    before a test failure is triggered.
         *    @param string $method        Method call to test.
         *    @param integer $count        Most number of times it should
         *                                 have been called.
         *    @access public
         */
        function expectMaximumCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set maximum call count");
            $this->_max_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Sets the number of times to call a method to prevent
         *    a failure on the tally.
         *    @param string $method      Method call to test.
         *    @param integer $count      Least number of times it should
         *                               have been called.
         *    @access public
         */
        function expectMinimumCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set minimum call count");
            $this->_min_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Convenience method for barring a method
         *    call.
         *    @param string $method        Method call to ban.
         *    @access public
         */
        function expectNever($method) {
            $this->expectMaximumCallCount($method, 0);
        }
        
        /**
         *    Convenience method for a single method
         *    call.
         *    @param string $method     Method call to track.
         *    @param array $args        Expected argument list or
         *                              false for any arguments.
         *    @access public
         */
        function expectOnce($method, $args = false) {
            $this->expectCallCount($method, 1);
            if ($args !== false) {
                $this->expectArguments($method, $args);
            }
        }
        
        /**
         *    Convenience method for requiring a method
         *    call.
         *    @param string $method       Method call to track.
         *    @param array $args          Expected argument list or
         *                                false for any arguments.
         *    @access public
         */
        function expectAtLeastOnce($method, $args = false) {
            $this->expectMinimumCallCount($method, 1);
            if ($args !== false) {
                $this->expectArguments($method, $args);
            }
        }
        
        /**
         *    Totals up the call counts and triggers a test
         *    assertion if a test is present for expected
         *    call counts.
         *    This method must be called explicitly for the call
         *    count assertions to be triggered.
         *    @access public
         */
        function tally() {
            $this->_tally_call_counts();
            $this->_tally_minimum_call_counts();
        }
        
        /**
         *    Checks that the exact call counts match up.
         *    @access private
         */
        function _tally_call_counts() {
            foreach ($this->_expected_counts as $method => $expected) {
                $this->_assertTrue(
                        $expected == ($count = $this->getCallCount($method)),
                        "Expected call count for [$method] was [$expected] got [$count]",
                        $this->_test);
            }
        }
        
        /**
         *    Checks that the minimum call counts match up.
         *    @access private
         */
        function _tally_minimum_call_counts() {
            foreach ($this->_min_counts as $method => $minimum) {
                $this->_assertTrue(
                        $minimum <= ($count = $this->getCallCount($method)),
                        "Expected minimum call count for [$method] was [$minimum] got [$count]",
                        $this->_test);
            }
        }
        
        /**
         *    Returns the expected value for the method name
         *    and checks expectations. Will generate any
         *    test assertions as a result of expectations
         *    if there is a test present.
         *    @param string $method       Name of method to simulate.
         *    @param array $args          Arguments as an array.
         *    @return mixed               Stored return.
         *    @access private
         */
        function &_invoke($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            $this->_checkExpectations($method, $args, $step);
            return $this->_getReturn($method, $args, $step);
        }
        
        /**
         *    Tests the arguments against expectations.
         *    @param string $method        Method to check.
         *    @param array $args           Argument list to match.
         *    @param integer $timing       The position of this call
         *                                 in the call history.
         *    @access private
         */
        function _checkExpectations($method, $args, $timing) {
            if (isset($this->_max_counts[$method])) {
                if ($timing >= $this->_max_counts[$method]) {
                    $this->_assertTrue(
                            false,
                            "Call count for [$method] is [" . ($timing + 1) . "]",
                            $this->_test);
                }
            }
            if (isset($this->_sequence_args[$timing][$method])) {
                $this->_assertTrue(
                        $this->_sequence_args[$timing][$method]->test($args),
                        "Mock method [$method] at [$timing]->" . $this->_sequence_args[$timing][$method]->testMessage($args),
                        $this->_test);
            } elseif (isset($this->_expected_args[$method])) {
                $this->_assertTrue(
                        $this->_expected_args[$method]->test($args),
                        "Mock method [$method]->" . $this->_expected_args[$method]->testMessage($args),
                        $this->_test);
            }
        }
        
        /**
         *    @deprecated
         */
        function _warnOnNoReturn($method) {
            if ($this->_require_return) {
                trigger_error(
                        "No value set in mock class [" . get_class($this) . "] for method [$method]",
                        E_USER_NOTICE);
            }
        }
        
        /**
         *    Triggers an assertion on the held test case.
         *    Should be overridden when using another test
         *    framework other than the SimpleTest one if the
         *    assertion method has a different name.
         *    @param boolean $assertion     True will pass.
         *    @param string $message        Message that will go with
         *                                  the test event.
         *    @param SimpleTestCase $test   Unit test case to send
         *                                  assertion to.
         *    @access protected
         */
        function _assertTrue($assertion, $message , &$test) {
            $test->assertTrue($assertion, $message);
        }
    }
    
    /**
     *    Static methods only class for code generation of
     *    server stubs.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class Stub {
        
        /**
         *    Factory for server stub classes.
         */
        function Stub() {
            trigger_error("Stub factory methods are class only.");
        }
        
        /**
         *    Clones a class' interface and creates a stub version
         *    that can have return values set.
         *    @param string $class        Class to clone.
         *    @param string $stub_class   New class name. Default is
         *                                the old name with "Stub"
         *                                prepended.
         *    @param array $methods       Additional methods to add beyond
         *                                those in th cloned class. Use this
         *                                to emulate the dynamic addition of
         *                                methods in the cloned class or when
         *                                the class hasn't been written yet.
         *    @static
         *    @access public
         */
        function generate($class, $stub_class = false, $methods = false) {
            if (!class_exists($class)) {
                return false;
            }
            if (!$stub_class) {
                $stub_class = "Stub" . $class;
            }
            if (class_exists($stub_class)) {
                return false;
            }
            return eval(Stub::_createClassCode(
                    $class,
                    $stub_class,
                    $methods ? $methods : array()) . " return true;");
        }
        
        /**
         *    The new server stub class code in string form.
         *    @param string $class           Class to clone.
         *    @param string $mock_class      New class name.
         *    @param array $methods          Additional methods.
         *    @static
         *    @access private
         */
        function _createClassCode($class, $stub_class, $methods) {
            $stub_base = SimpleTestOptions::getStubBaseClass();
            $code = "class $stub_class extends $stub_base {\n";
            $code .= "    function $stub_class(\$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->$stub_base(\$wildcard);\n";
            $code .= "    }\n";
            $code .= Stub::_createHandlerCode($class, $stub_base, $methods);
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    Creates code within a class to generate replaced
         *    methods. All methods call the _invoke() handler
         *    with the method name and the arguments in an
         *    array.
         *    @param string $class     Class to clone.
         *    @param string $base      Base class with methods that
         *                             cannot be cloned.
         *    @param array $methods    Additional methods.
         *    @static
         *    @access private
         */
        function _createHandlerCode($class, $base, $methods) {
            $code = "";
            $methods = array_merge($methods, get_class_methods($class));
            foreach ($methods as $method) {
                if (in_array($method, get_class_methods($base))) {
                    continue;
                }
                $code .= "    function &$method() {\n";
                $code .= "        \$args = func_get_args();\n";
                $code .= "        return \$this->_invoke(\"$method\", \$args);\n";
                $code .= "    }\n";
            }
            return $code;
        }
        
        /**
         *    @deprecated
         */
        function setStubBaseClass($stub_base = false) {
            SimpleTestOptions::setStubBaseClass($stub_base);
        }
    }
    
    /**
     *    Static methods only class for code generation of
     *    mock objects.
	 *    @package SimpleTest
	 *    @subpackage MockObjects
     */
    class Mock {
        
        /**
         *    Factory for mock object classes.
         *    @access public
         */
        function Mock() {
            trigger_error("Mock factory methods are class only.");
        }
        
        /**
         *    Clones a class' interface and creates a mock version
         *    that can have return values and expectations set.
         *    @param string $class         Class to clone.
         *    @param string $mock_class    New class name. Default is
         *                                 the old name with "Mock"
         *                                 prepended.
         *    @param array $methods        Additional methods to add beyond
         *                                 those in th cloned class. Use this
         *                                 to emulate the dynamic addition of
         *                                 methods in the cloned class or when
         *                                 the class hasn't been written yet.
         *    @static
         *    @access public
         */
        function generate($class, $mock_class = false, $methods = false) {
            if (!class_exists($class)) {
                return false;
            }
            if (!$mock_class) {
                $mock_class = "Mock" . $class;
            }
            if (class_exists($mock_class)) {
                return false;
            }
            return eval(Mock::_createClassCode(
                    $class,
                    $mock_class,
                    $methods ? $methods : array()) . " return true;");
        }
        
        /**
         *    Generates a version of a class with selected
         *    methods mocked only. Inherits the old class
         *    and chains the mock methods of an aggregated
         *    mock object.
         *    @param string $class            Class to clone.
         *    @param string $mock_class       New class name.
         *    @param array $methods           Methods to be overridden
         *                                    with mock versions.
         *    @static
         *    @access public
         */
        function generatePartial($class, $mock_class, $methods) {
            if (!class_exists($class)) {
                return false;
            }
            if (class_exists($mock_class)) {
                return false;
            }
            return eval(Mock::_extendClassCode($class, $mock_class, $methods));
        }

        /**
         *    The new mock class code as a string.
         *    @param string $class           Class to clone.
         *    @param string $mock_class      New class name.
         *    @param array $methods          Additional methods.
         *    @return string                 Code for new mock class.
         *    @static
         *    @access private
         */
        function _createClassCode($class, $mock_class, $methods) {
            $mock_base = SimpleTestOptions::getMockBaseClass();
            $code = "class $mock_class extends $mock_base {\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->$mock_base(\$test, \$wildcard);\n";
            $code .= "    }\n";
            $code .= Stub::_createHandlerCode($class, $mock_base, $methods);
            $code .= "}\n";
            return $code;
        }

        /**
         *    The extension class code as a string. The class
         *    composites a mock object and chains mocked methods
         *    to it.
         *    @param string $class         Class to extend.
         *    @param string $mock_class    New class name.
         *    @param array  $methods       Additional methods.
         *    @return string               Code for a new class.
         *    @static
         *    @access private
         */
        function _extendClassCode($class, $mock_class, $methods) {
            $mock_base = SimpleTestOptions::getMockBaseClass();
            $code  = "class $mock_class extends $class {\n";
            $code .= "    var \$_mock;\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->_mock = &new $mock_base(\$test, \$wildcard, false);\n";
            $code .= "    }\n";
            $code .= Mock::_chainMockReturns();
            $code .= Mock::_chainMockExpectations();
            $code .= Mock::_overrideMethods($methods);
            $code .= SimpleTestOptions::getPartialMockCode();
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    Creates source code for chaining to the composited
         *    mock object.
         *    @return string           Code for mock set up.
         *    @access private
         */
        function _chainMockReturns() {
            $code = "    function setReturnValue(\$method, \$value, \$args = false) {\n";
            $code .= "        \$this->_mock->setReturnValue(\$method, \$value, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnValueAt(\$timing, \$method, \$value, \$args = false) {\n";
            $code .= "        \$this->_mock->setReturnValueAt(\$timing, \$method, \$value, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnReference(\$method, &\$ref, \$args = false) {\n";
            $code .= "        \$this->_mock->setReturnReference(\$method, \$ref, \$args);\n";
            $code .= "    }\n";
            $code .= "    function setReturnReferenceAt(\$timing, \$method, &\$ref, \$args = false) {\n";
            $code .= "        \$this->_mock->setReturnReferenceAt(\$timing, \$method, \$ref, \$args);\n";
            $code .= "    }\n";
            $code .= "    function getCallCount(\$method) {\n";
            $code .= "        \$this->_mock->getCallCount(\$method);\n";
            $code .= "    }\n";
            $code .= "    function clearHistory() {\n";
            $code .= "        \$this->_mock->clearHistory();\n";
            $code .= "    }\n";
            return $code;
        }
        
        /**
         *    Creates source code for chaining to an aggregated
         *    mock object.
         *    @return string                 Code for expectations.
         *    @access private
         */
        function _chainMockExpectations() {
            $code = "    function expectArguments(\$method, \$args = false) {\n";
            $code .= "        \$this->_mock->expectArguments(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectArgumentsAt(\$timing, \$method, \$args = false) {\n";
            $code .= "        \$this->_mock->expectArgumentsAt(\$timing, \$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectCallCount(\$method, \$count) {\n";
            $code .= "        \$this->_mock->expectCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectMaximumCallCount(\$method, \$count) {\n";
            $code .= "        \$this->_mock->expectMaximumCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectMinimumCallCount(\$method, \$count) {\n";
            $code .= "        \$this->_mock->expectMinimumCallCount(\$method, \$count);\n";
            $code .= "    }\n";
            $code .= "    function expectNever(\$method) {\n";
            $code .= "        \$this->_mock->expectNever(\$method);\n";
            $code .= "    }\n";
            $code .= "    function expectOnce(\$method, \$args = false) {\n";
            $code .= "        \$this->_mock->expectOnce(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function expectAtLeastOnce(\$method, \$args = false) {\n";
            $code .= "        \$this->_mock->expectAtLeastOnce(\$method, \$args);\n";
            $code .= "    }\n";
            $code .= "    function tally() {\n";
            $code .= "        \$this->_mock->tally();\n";
            $code .= "    }\n";
            return $code;
        }
        
        /**
         *    Creates source code to override a list of methods
         *    with mock versions.
         *    @param array $methods    Methods to be overridden
         *                             with mock versions.
         *    @return string           Code for overridden chains.
         *    @access private
         */
        function _overrideMethods($methods) {
            $code = "";
            foreach ($methods as $method) {
                $code .= "    function &$method() {\n";
                $code .= "        \$args = func_get_args();\n";
                $code .= "        return \$this->_mock->_invoke(\"$method\", \$args);\n";
                $code .= "    }\n";
            }
            return $code;
        }
        
        /**
         *    @deprecated
         */
        function setMockBaseClass($mock_base = false) {
            SimpleTestOptions::setMockBaseClass($mock_base);
        }
    }
?>