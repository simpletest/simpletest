<?php
    // $Id$
    
    define('MOCK_WILDCARD', '*');
    
    /**
     *    A list of parameters that can include wild cards.
     *    The parameters can be compared with wildcards
     *    being counted as matches.
     */
    class ParameterList {
        var $_parameters;
        var $_wildcard;
        
        /**
         *    Creates a parameter list with a possible wildcard.
         *    @param $parameters        Array of parameters including
         *                              those that are wildcarded.
         *                              If the value is not an array
         *                              then it is considered to match any.
         *    @param $wildcard          Any parameter matching this
         *                              will always match.
         *    @public
         */
        function ParameterList($parameters, $wildcard = MOCK_WILDCARD) {
            $this->_parameters = $parameters;
            $this->_wildcard = $wildcard;
        }
        
        /**
         *    Tests the internal parameters and wildcards against
         *    the test list of parameters.
         *    @param $parameters        Parameter list to test against.
         *    @return                   False if a parameter fails to match.
         *    @public
         */
        function isMatch($parameters) {
            if (!is_array($this->_parameters)) {
                return true;
            }
            if (count($this->_parameters) != count($parameters)) {
                return false;
            }
            for ($i = 0; $i < count($this->_parameters); $i++) {
                if ($this->_parameters[$i] === $this->_wildcard) {
                    continue;
                }
                if (count($parameters) <= $i) {
                    return false;
                }
                if (!($this->_parameters[$i] === $parameters[$i])) {
                    return false;
                }
            }
            return true;
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
            $this->_map[$place]["params"] = new ParameterList(
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
                if ($this->_map[$i]["params"]->isMatch($parameters)) {
                    return $this->_map[$i];
                }
            }
            return null;
        }
    }
    
    /**
     *    An empty collection of methods that can have their
     *    return values set and expectations made of the
     *    calls upon them. The mock will assert the
     *    expectations against it's attached test case.
     */
    class SimpleMock {
        var $_test;
        var $_wildcard;
        var $_returns;
        var $_return_sequence;
        var $_call_counts;
        var $_expected_counts;
        var $_max_counts;
        var $_expected_args;
        var $_args_sequence;
        
        /**
         *    Creates an empty return list and expectation list.
         *    All call counts are set to zero.
         *    @param $wildcard    Parameter matching wildcard.
         *    @param $test        Test case to test expectations in.
         *    @public
         */
        function SimpleMock(&$test, $wildcard) {
            $this->_test = &$test;
            $this->_wildcard = $wildcard;
            $this->clearHistory();
            $this->_returns = array();
            $this->_return_sequence = array();
            $this->_expected_counts = array();
            $this->_max_counts = array();
            $this->_expected_args = array();
            $this->_args_sequence = array();
        }
        
        /**
         *    Resets the call history for the mock. The tally
         *    will be counted from this point onwards.
         *    @public
         */
        function clearHistory() {
            $this->_call_counts = array();
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
                $this->_returns[$method] = new CallMap($this->_wildcard);
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
        function setReturnValueSequence($timing, $method, $value, $args = "") {
            $this->_dieOnNoMethod($method, "set return value sequence");
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap($this->_wildcard);
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
        function setReturnReference($method, &$reference, $args = "") {
            $this->_dieOnNoMethod($method, "set return reference");
            $method = strtolower($method);
            if (!isset($this->_returns[$method])) {
                $this->_returns[$method] = new CallMap($this->_wildcard);
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
        function setReturnReferenceSequence($timing, $method, &$reference, $args = "") {
            $this->_dieOnNoMethod($method, "set return reference sequence");
            $method = strtolower($method);
            if (!isset($this->_return_sequence[$method])) {
                $this->_return_sequence[$method] = array();
            }
            if (!isset($this->_return_sequence[$method][$timing])) {
                $this->_return_sequence[$method][$timing] = new CallMap($this->_wildcard);
            }
            $this->_return_sequence[$method][$timing]->addReference($args, $reference);
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
        function setExpectedArguments($method, $args = "") {
            $this->_dieOnNoMethod($method, "set expected arguments");
            $args = (is_array($args) ? $args : array());
            $this->_expected_args[strtolower($method)] = new ParameterList(
                    $args,
                    $this->_wildcard);
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
        function setExpectedArgumentsSequence($timing, $method, $args = "") {
            $this->_dieOnNoMethod($method, "set expected arguments sequence");
            $args = (is_array($args) ? $args : array());
            if (!isset($this->_sequence_args[$timing])) {
                $this->_sequence_args[$timing] = array();
            }
            $method = strtolower($method);
            $this->_sequence_args[$timing][$method] = new ParameterList(
                    $args,
                    $this->_wildcard);
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
        function setExpectedCallCount($method, $count) {
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
        function setMaximumCallCount($method, $count) {
            $this->_dieOnNoMethod($method, "set maximum call count");
            $this->_max_counts[strtolower($method)] = $count;
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
         *    Totals up the call counts and triggers a test
         *    assertion if a test is present for expected
         *    call counts.
         *    This method must be called explicitely for the call
         *    count assertions to be triggered.
         *    @return                True if tallies are correct.
         *    @public
         */
        function tally() {
            $all_correct = true;
            foreach ($this->_expected_counts as $method => $expected) {
                $is_correct = ($expected == ($count = $this->getCallCount($method)));
                $this->_assertTrue(
                        $is_correct,
                        "Expected call count for [$method] was [$expected], but got [$count]");
                $all_correct = $is_correct && $all_correct;
            }
            return $all_correct;
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
        function &_mockMethod($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            $this->_checkExpectations($method, $args, $step);
            return $this->_getReturn($method, $args, $step);
        }
        
        /**
         *    Adds one to the call count of a method.
         *    @param $method        Method called.
         *    @param $args          Arguments as an array.
         *    @private
         */
        function _addCall($method, $args) {
            if (!isset($this->_call_counts[$method])) {
                $this->_call_counts[$method] = 0;
            }
            $this->_call_counts[$method]++;
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
                    $this->_assertTrue(false, "Call count for [$method] is [$timing]");
                }
            }
            if (isset($this->_sequence_args[$timing][$method])) {
                $this->_assertTrue(
                        $this->_sequence_args[$timing][$method]->isMatch($args),
                        "Arguments for [$method] at [$timing] were [" . $this->_renderArguments($args) . "]");
            } elseif (isset($this->_expected_args[$method])) {
                $this->_assertTrue(
                        $this->_expected_args[$method]->isMatch($args),
                        "Arguments for [$method] were [" . $this->_renderArguments($args) . "]");
            }
        }
        
        /**
         *    Finds the return value matching the incoming
         *    arguments.
         *    @param $method      Method name.
         *    @param $args        Calling arguments.
         *    @param $step        Current position in the
         *                        call history.
         *    @returns            Stored return.
         *    @private
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
            return null;
        }
        
        /**
         *    Triggers an assertion on the held test case.
         *    Should be overridden when using another test
         *    framework other than the SimpleTest one if the
         *    assertion method has a different name.
         *    @param $assertion      True will pass.
         *    @param $message        Message that will go with
         *                           the test event.
         *    @protected
         */
        function _assertTrue($assertion, $message) {
            $this->_test->assertTrue($assertion, $message);
        }
        
        /**
         *    Triggers a PHP error if the method is not part
         *    of this object.
         *    @param $method        Name of method.
         *    @param $task          Description of task attempt.
         *    @protected
         */
        function _dieOnNoMethod($method, $task) {
            if (!method_exists($this, $method)) {
                trigger_error(
                        "Cannot $task as no $method in class " . get_class($this),
                        E_USER_ERROR);
            }
        }
        
        /**
         *    Renders the argument list as a string for
         *    messages.
         *    @param $args            Array of arguments.
         *    @private
         */
        function _renderArguments($args) {
            $arg_strings = array();
            foreach ($args as $arg) {
                if (is_bool($arg)) {
                    $arg_strings[] = "Boolean: " . ($arg ? "true" : "false");
                } elseif (is_string($arg)) {
                    $arg_strings[] = "String: $arg";
                } elseif (is_integer($arg)) {
                    $arg_strings[] = "Integer: $arg";
                } elseif (is_integer($arg)) {
                    $arg_strings[] = "Float: $arg";
                } elseif (is_array($arg)) {
                    $arg_strings[] = "Array: " . count($arg) . " items";
                } elseif (is_array($arg)) {
                    $arg_strings[] = "Resource: $arg";
                } elseif (is_object($arg)) {
                    $arg_strings[] = "Object: " . get_class($arg);
                }
            }
            return implode(", ", $arg_strings);
        }
    }
    
    /**
     *    Static methods only class for code generation.
     */
    class Mock {
        
        /**
         *    Factory for Mock classes.
         *    @abstract
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
         *    @static
         */
        function generate($class, $mock_class = "") {
            if (!class_exists($class)) {
                return false;
            }
            if ($mock_class == "") {
                $mock_class = "Mock" . $class;
            }
            if (class_exists($mock_class)) {
                return false;
            }
            return eval(Mock::_createClassCode($class, $mock_class) . " return true;");
        }
        
        /**
         *    The new mock class code in string form.
         *    @param $class            Class to clone.
         *    @param $mock_class       New class name.
         *    @static
         *    @private
         */
        function _createClassCode($class, $mock_class) {
            $mock_base = Mock::setMockBaseClass();
            $code = "class $mock_class extends $mock_base {\n";
            $code .= "    function $mock_class(&\$test, \$wildcard = MOCK_WILDCARD) {\n";
            $code .= "        \$this->$mock_base(\$test, \$wildcard);\n";
            $code .= "        \$args = func_get_args();\n";
            $code .= "        \$this->_mockMethod(\"$class\", \$args);\n";
            $code .= "    }\n";
            foreach (get_class_methods($class) as $method) {
                $code .= "    function &$method() {\n";
                $code .= "        \$args = func_get_args();\n";
                $code .= "        return \$this->_mockMethod(\"$method\", \$args);\n";
                $code .= "    }\n";
            }
            $code .= "}\n";
            return $code;
        }
        
        /**
         *    The base class name is setable here. This is the
         *    class that the new mock will inherited from.
         *    To modify the generated mocks simple extend the
         *    SimpleMock class above and set it's name
         *    with this method before any mocks are generated.
         *    @param $mock_base        Mock base class to use.
         *                             If empty then the existing
         *                             class will be unchanged.
         *    @return                  Current or new base class.
         *    @static
         */
        function setMockBaseClass($mock_base = "") {
            static $_mock_base = "SimpleMock";
            if ($mock_base != "") {
                $_mock_base = $mock_base;
            }
            return $_mock_base;
        }
    }
?>