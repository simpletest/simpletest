<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'expectation.php');
    require_once(SIMPLE_TEST . 'options.php');
    
    define('MOCK_WILDCARD', '*');
    
    /**
     *    Parameter comparison assertion.
     */
    class ParametersExpectation extends SimpleExpectation {
        var $_expected;
        var $_wildcard;
        
        /**
         *    Sets the expected parameter list.
         *    @param $parameters        Array of parameters including
         *                              those that are wildcarded.
         *                              If the value is not an array
         *                              then it is considered to match any.
         *    @param $wildcard          Any parameter matching this
         *                              will always match.
         *    @public
         */
        function ParametersExpectation($expected = false, $wildcard = MOCK_WILDCARD) {
            $this->SimpleExpectation();
            $this->_expected = $expected;
            $this->_wildcard = $wildcard;
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param $parameters     Comparison values.
         *    @return                True if correct.
         *    @public
         */
        function test($parameters) {
            if (!is_array($this->_expected)) {
                return true;
            }
            if (count($this->_expected) != count($parameters)) {
                return false;
            }
            for ($i = 0; $i < count($this->_expected); $i++) {
                if ($this->_expected[$i] === $this->_wildcard) {
                    continue;
                }
                if (!($this->_expected[$i] === $parameters[$i])) {
                    return false;
                }
            }
            return true;
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $comparison   Incoming parameter list.
         *    @return              String description of success
         *                         or failure.
         *    @public
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
         *    @param $expected      Expected parameters as list.
         *    @param $parameters    Actual parameters received.
         *    @return               String description of difference.
         *    @private
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
                $comparison = new IdenticalExpectation($expected[$i]);
                if (! $comparison->test($parameters[$i])) {
                    $messages[] = "parameter " . ($i + 1) . " with [" .
                            $comparison->testMessage($parameters[$i]) . "]";
                }
            }
            return "Mock expectation differs at " . implode(" and ", $messages);
        }
        
        /**
         *    Renders the argument list as a string for
         *    messages.
         *    @param $args            Array of arguments.
         *    @private
         */
        function _renderArguments($args) {
            $descriptions = array();
            foreach ($args as $arg) {
                $descriptions[] = SimpleExpectation::describeValue($arg);
            }
            return implode(', ', $descriptions);
        }
    }
    
    /**
     *    Retrieves values and references by searching the
     *    parameter lists until a match is found.
     */
    class CallMap {
        var $_map;
        var $_wildcard;
        
        /**
         *    Creates an empty call map.
         *    @param $wildcard        Wildcard value for matching.
         *    @public
         */
        function CallMap($wildcard) {
            $this->_map = array();
            $this->_wildcard = $wildcard;
        }
        
        /**
         *    Stashes a value against a method call.
         *    @param $parameters    Array of arguments (including wildcards).
         *    @param $value         Value copied into the map.
         *    @public
         */
        function addValue($parameters, $value) {
            $this->addReference($parameters, $value);
        }
        
        /**
         *    Stashes a reference against a method call.
         *    @param $parameters    Array of arguments (including wildcards).
         *    @param $reference     Array reference placed in the map.
         *    @public
         */
        function addReference($parameters, &$reference) {
            $place = count($this->_map);
            $this->_map[$place] = array();
            $this->_map[$place]["params"] = new ParametersExpectation(
                    $parameters,
                    $this->_wildcard);
            $this->_map[$place]["content"] = &$reference;
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. Returned by reference.
         *    @param $parameters    Array of parameters to search by
         *                          without wildcards.
         *    @return               Object held in the first matching
         *                          slot, otherwise null.
         *    @public
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
         *    @param $parameters    Array of parameters to search by
         *                          without wildcards.
         *    @return               True if a match is present.
         *    @public
         */
        function isMatch($parameters) {
            return ($this->_findFirstSlot($parameters) != null);
        }
        
        /**
         *    Searches the map for a matching item.
         *    @param $parameters    Array of parameters to search by
         *                          without wildcards.
         *    @return               Reference to slot or null.
         *    @private
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
     */
    class SimpleStub {
        var $_wildcard;
        var $_is_strict;
        var $_returns;
        var $_return_sequence;
        var $_call_counts;
        
        /**
         *    Sets up the wildcard and everything else empty.
         *    @param $wildcard    Parameter matching wildcard.
         *    @param $is_strict   Enables method name checks.
         *    @public
         */
        function SimpleStub($wildcard, $is_strict = true) {
            $this->_wildcard = $wildcard;
            $this->_is_strict = $is_strict;
            $this->_returns = array();
            $this->_return_sequence = array();
            $this->clearHistory();
        }
        
        /**
         *    Resets the call count history for the stub.
         *    Sequences of returns will start from 0 again.
         *    @public
         */
        function clearHistory() {
            $this->_call_counts = array();
        }
        
        /**
         *    Accessor for wildcard.
         *    @return        Wildcard object or string.
         *    @protected
         */
        function _getWildcard() {
            return $this->_wildcard;
        }
        
        /**
         *    Returns the expected value for the method name.
         *    @param $method        Name of method to simulate.
         *    @param $args          Arguments as an array.
         *    @return               Stored return.
         *    @private
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
         *    @param $method        Name of method.
         *    @param $task          Description of task attempt.
         *    @protected
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
         *    @param $method        Method called.
         *    @param $args          Arguments as an array.
         *    @protected
         */
        function _addCall($method, $args) {
            if (!isset($this->_call_counts[$method])) {
                $this->_call_counts[$method] = 0;
            }
            $this->_call_counts[$method]++;
        }
        
        /**
         *    Fetches the call count of a method so far.
         *    @param $method        Method name called.
         *    @return               Number of calls so far.
         *    @public
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
         *    @param $method        Method name.
         *    @param $value         Result of call passed by value.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         *    @public
         */
        function setReturnValue($method, $value, $args = "") {
            $this->_dieOnNoMethod($method, "set return value");
            $method = strtolower($method);
            if (!isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap($this->_getWildcard());
            }
            $this->_returns[$method]->addValue($args, $value);
        }
                
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param $timing        Number of calls in the future
         *                          to which the result applies. If
         *                          not set then all calls will return
         *                          the value.
         *    @param $method        Method name.
         *    @param $value         Result of call passed by value.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         *    @public
         */
        function setReturnValueAt($timing, $method, $value, $args = false) {
            $this->_dieOnNoMethod($method, "set return value sequence");
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap($this->_getWildcard());
            }
            $this->_return_sequence[$method][$timing]->addValue($args, $value);
        }
         
        /**
         *    Sets a return for a parameter list that will
         *    be passed by reference for all calls.
         *    @param $method        Method name.
         *    @param $reference     Result of the call will be this object.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         *    @public
         */
        function setReturnReference($method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference");
            $method = strtolower($method);
            if (!isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap($this->_getWildcard());
            }
            $this->_returns[$method]->addReference($args, $reference);
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value only when the required call count
         *    is reached.
         *    @param $timing        Number of calls in the future
         *                          to which the result applies. If
         *                          not set then all calls will return
         *                          the value.
         *    @param $method        Method name.
         *    @param $reference     Result of the call will be this object.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         *    @public
         */
        function setReturnReferenceAt($timing, $method, &$reference, $args = false) {
            $this->_dieOnNoMethod($method, "set return reference sequence");
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap($this->_getWildcard());
            }
            $this->_return_sequence[$method][$timing]->addReference($args, $reference);
        }
        
        /**
         *    Finds the return value matching the incoming
         *    arguments. If there is no matching value found
         *    then an error is triggered.
         *    @param $method      Method name.
         *    @param $args        Calling arguments.
         *    @param $step        Current position in the
         *                        call history.
         *    @returns            Stored return.
         *    @protected
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
         *    @param $method      Method name.
         *    @protected
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
         *    @param $wildcard    Parameter matching wildcard.
         *    @param $test        Test case to test expectations in.
         *    @param $is_strict   Enables method name checks on
         *                        expectations.
         *    @public
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
         *    @return          Unit test passed in constructor.
         *    @public
         */
        function &getTest() {
            return $this->_test;
        }
        
        /**
         *    Sets the mock to require a return value to be set or
         *    it issues a warning.
         *    @public
         */
        function requireReturn() {
            $this->_require_return = true;
        }
         
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. All
         *    calls will be compared to these expectations
         *    regardless of when the call is made.
         *    @param $method        Method call to test.
         *    @param $args          Expected parameters for the call
         *                          including wildcards.
         *    @public
         */
        function expectArguments($method, $args = false) {
            $this->_dieOnNoMethod($method, "set expected arguments");
            $args = (is_array($args) ? $args : array());
            $this->_expected_args[strtolower($method)] = new ParametersExpectation(
                    $args,
                    $this->_getWildcard());
        }
        
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. The
         *    expected call count will be adjusted if it
         *    is set too low to reach this call.
         *    @param $timing        Number of calls in the future at
         *                          which to test. Next call is 0.
         *    @param $method        Method call to test.
         *    @param $args          Expected parameters for the call
         *                          including wildcards.
         *    @public
         */
        function expectArgumentsAt($timing, $method, $args = false) {
            $this->_dieOnNoMethod($method, "set expected arguments at time");
            $args = (is_array($args) ? $args : array());
            if (!isset($this->_sequence_args[$timing])) {
                $this->_sequence_args[$timing] = array();
            }
            $method = strtolower($method);
            $this->_sequence_args[$timing][$method] = new ParametersExpectation(
                    $args,
                    $this->_getWildcard());
        }
        
        /**
         *    Sets an expectation for the number of times
         *    a method will be called. The tally method
         *    is used to check this.
         *    @param $method        Method call to test.
         *    @param $count         Number of times it should
         *                          have been called at tally.
         *    @public
         */
        function expectCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set expected call count");
            $this->_expected_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Sets the number of times a method may be called
         *    before a test failure is triggered.
         *    @param $method        Method call to test.
         *    @param $count         Most number of times it should
         *                          have been called.
         *    @public
         */
        function expectMaximumCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set maximum call count");
            $this->_max_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Sets the number of times to call a method to prevent
         *    a failure on the tally.
         *    @param $method        Method call to test.
         *    @param $count         Least number of times it should
         *                          have been called.
         *    @public
         */
        function expectMinimumCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set minimum call count");
            $this->_min_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Convenience method for barring a method
         *    call.
         *    @param $method        Method call to ban.
         *    @public
         */
        function expectNever($method) {
            $this->expectMaximumCallCount($method, 0);
        }
        
        /**
         *    Convenience method for a single method
         *    call.
         *    @param $method        Method call to track.
         *    @param $args          Expected argument list or
         *                          false for any arguments.
         *    @public
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
         *    @param $method        Method call to track.
         *    @param $args          Expected argument list or
         *                          false for any arguments.
         *    @public
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
         *    @public
         */
        function tally() {
            $this->_tally_call_counts();
            $this->_tally_minimum_call_counts();
        }
        
        /**
         *    Checks that the exact call counts match up.
         *    @private
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
         *    @private
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
         *    @param $method        Name of method to simulate.
         *    @param $args          Arguments as an array.
         *    @return               Stored return.
         *    @private
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
         *    @param $method            Method to check.
         *    @param $args              Argument list to match.
         *    @param $timing            The position of this call
         *                              in the call history.
         *    @private
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
         *    What to do if there is no return value set.
         *    @protected
         *    @param $method      Method name.
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
         *    @param $assertion      True will pass.
         *    @param $message        Message that will go with
         *                           the test event.
         *    @param $test           Unit test case to send
         *                           assertion to.
         *    @protected
         */
        function _assertTrue($assertion, $message , &$test) {
            $test->assertTrue($assertion, $message);
        }
    }
    
    /**
     *    Static methods only class for code generation of
     *    server stubs.
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
         *    @param $class            Class to clone.
         *    @param $stub_class       New class name. Default is
         *                             the old name with "Stub"
         *                             prepended.
         *    @param $methods          Additional methods to add beyond
         *                             those in th cloned class. Use this
         *                             to emulate the dynamic addition of
         *                             methods in the cloned class or when
         *                             the class hasn't been written yet.
         *    @static
         *    @public
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
         *    @param $class            Class to clone.
         *    @param $mock_class       New class name.
         *    @param $methods          Additional methods.
         *    @static
         *    @private
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
         *    @param $class     Class to clone.
         *    @param $base      Base class with methods that
         *                      cannot be cloned.
         *    @param $methods   Additional methods.
         *    @static
         *    @private
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
     */
    class Mock {
        
        /**
         *    Factory for mock object classes.
         */
        function Mock() {
            trigger_error("Mock factory methods are class only.");
        }
        
        /**
         *    Clones a class' interface and creates a mock version
         *    that can have return values and expectations set.
         *    @param $class            Class to clone.
         *    @param $mock_class       New class name. Default is
         *                             the old name with "Mock"
         *                             prepended.
         *    @param $methods          Additional methods to add beyond
         *                             those in th cloned class. Use this
         *                             to emulate the dynamic addition of
         *                             methods in the cloned class or when
         *                             the class hasn't been written yet.
         *    @static
         *    @public
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
         *    @param $class            Class to clone.
         *    @param $mock_class       New class name.
         *    @param $methods          Methods to be overridden
         *                             with mock versions.
         *    @static
         *    @public
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
         *    @param $class            Class to clone.
         *    @param $mock_class       New class name.
         *    @param $methods          Additional methods.
         *    @return                  Code as a string.
         *    @static
         *    @private
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
         *    The extension class code as astring.
         *    @param $class            Class to extends.
         *    @param $mock_class       New class name.
         *    @param $methods          Additional methods.
         *    @return                  Code as a string.
         *    @static
         *    @private
         */
        function _extendClassCode($class, $mock_class, $methods) {
            $mock_base = SimpleTestOptions::getMockBaseClass();
            $code  = "class $mock_class extends $class {\n";
            $code .= "    var \$_mock;\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->_mock = &new $mock_base(&\$test, \$wildcard, false);\n";
            $code .= "    }\n";
            $code .= Mock::_chainMockReturns();
            $code .= Mock::_chainMockExpectations();
            $code .= Mock::_overrideMethods($methods);
            $code .= SimpleTestOptions::getPartialMockCode();
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    Creates source code for chaining to an aggregated
         *    mock object.
         *    @return                  Code as a string.
         *    @private
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
         *    @return                  Code as a string.
         *    @private
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
         *    @param $methods          Methods to be overridden
         *                             with mock versions.
         *    @return                  Code as a string.
         *    @private
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