<?php
    define('MOCK_WILDCARD', '*');
    
    /**
     *    A list of parameters that can include wild cards.
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
     *    Retrieves values and references by parameter lists
     *    and method name.
     */
    class CallMap {
        var $_map;
        var $_wildcard;
        
        /**
         *    Creates an empty call map.
         *    @param $wildcard        Wildcard value for matching.
         */
        function CallMap($wildcard) {
            $this->_map = array();
            $this->_wildcard = $wildcard;
        }
        
        /**
         *    Stashes a value against a method call.
         *    @param $method        Method name (reduced to lowercase).
         *    @param $parameters    Array of arguments (including wildcards).
         *    @param $value         Value copied into the map.
         */
        function addValue($method, $parameters, $value) {
            $this->addReference($method, $parameters, $value);
        }
        
        /**
         *    Stashes a reference against a method call.
         *    @param $method        Method name (reduced to lowercase).
         *    @param $parameters    Array of arguments (including wildcards).
         *    @param $reference     Array reference placed in the map.
         */
        function addReference($method, $parameters, &$reference) {
            if (!in_array($method, array_keys($this->_map))) {
                $this->_map[$method] = array();
            }
            $place = count($this->_map[$method]);
            $this->_map[$method][$place] = array();
            $this->_map[$method][$place]["params"] = new ParameterList(
                    $parameters,
                    $this->_wildcard);
            $this->_map[$method][$place]["content"] = &$reference;
        }
        
        /**
         *    Searches the call list for a matching parameter
         *    set. Returned by reference.
         *    @param $method        Method name (case insensitive).
         *    @param $parameters    Array of parameters to search by
         *                          without wildcards.
         *    @return               Object held in the first matching
         *                          slot, otherwise null.
         */
        function &findFirstMatch($method, $parameters) {
            if (!in_array($method, array_keys($this->_map))) {
                return null;
            }
            for ($i = 0; $i < count($this->_map[$method]); $i++) {
                if ($this->_map[$method][$i]["params"]->isMatch($parameters)) {
                    return $this->_map[$method][$i]["content"];
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
         *    @param $wildcard    Parameter matching wildcard.
         *    @param $test        Test case to test expectations in.
         */
        function SimpleMock(&$test, $wildcard) {
            $this->_test = &$test;
            $this->_wildcard = $wildcard;
            $this->clearHistory();
            $this->_returns = new CallMap($this->_wildcard);
            $this->_return_sequence = array();
            $this->_expected_counts = array();
            $this->_max_counts = array();
            $this->_expected_args = array();
            $this->_args_sequence = array();
        }
        
        /**
         *    Resets the call history for the mock. The tally
         *    will be counted from this point onwards.
         */
        function clearHistory() {
            $this->_call_counts = array();
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value.
         *    @param $method        Method name.
         *    @param $value         Result of call passed by value.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         */
        function setReturnValue($method, $value, $args = "") {
            $this->_returns->addValue(strtolower($method), $args, $value);
        }
                
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value when the required call count
         *    is reached.
         *    @param $timing        Number of calls in the future
         *                          to which the result applies. If
         *                          not set then all calls will return
         *                          the value.
         *    @param $method        Method name.
         *    @param $value         Result of call passed by value.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         */
        function setReturnValueSequence($timing, $method, $value, $args = "") {
            if (!isset($this->_return_sequence[$timing])) {
                $this->_return_sequence[$timing] = new CallMap($this->_wildcard);
            }
            $this->_return_sequence[$timing]->addValue(
                    strtolower($method),
                    $args,
                    $value);
        }
         
        /**
         *    Sets a return for a parameter list that will
         *    be passed by reference.
         *    @param $method        Method name.
         *    @param $reference     Result of the call will be this object.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         */
        function setReturnReference($method, &$reference, $args = "") {
            $this->_returns->addReference(strtolower($method), $args, $reference);
        }
        
        /**
         *    Sets a return for a parameter list that will
         *    be passed by value when the required call count
         *    is reached.
         *    @param $timing        Number of calls in the future
         *                          to which the result applies. If
         *                          not set then all calls will return
         *                          the value.
         *    @param $method        Method name.
         *    @param $reference     Result of the call will be this object.
         *    @param $args          List of parameters to match
         *                          including wildcards.
         */
        function setReturnReferenceSequence($timing, $method, &$reference, $args = "") {
            if (!isset($this->_return_sequence[$timing])) {
                $this->_return_sequence[$timing] = new CallMap($this->_wildcard);
            }
            $this->_return_sequence[$timing]->addReference(
                    strtolower($method),
                    $args,
                    $reference);
        }
         
        /**
         *    Sets up an expected call with a set of
         *    expected parameters in that call. All
         *    calls will be compared to these expectations
         *    regardless of when the call is made.
         *    @param $method        Method call to test.
         *    @param $args          Expected parameters for the call
         *                          including wildcards.
         */
        function setExpectedArguments($method, $args = "") {
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
         */
        function setExpectedArgumentsSequence($timing, $method, $args = "") {
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
         */
        function setExpectedCallCount($method, $count) {
            $this->_expected_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Sets the number of times a method may be called
         *    before a test failure is triggered.
         *    @param $method        Method call to test.
         *    @param $count         Most number of times it should
         *                          have been called.
         */
        function setMaximumCallCount($method, $count) {
            $this->_max_counts[strtolower($method)] = $count;
        }
        
        /**
         *    Fetches the call count of a method so far.
         *    @param $method        Method name called.
         *    @return               Number of calls so far.
         */
        function getCallCount($method) {
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
         *    @return                True if tallies are correct.
         */
        function tally() {
            $all_correct = true;
            foreach ($this->_expected_counts as $method => $expected) {
                $is_same = ($expected == ($count = $this->getCallCount($method)));
                $this->_assertTrue(
                        $is_same,
                        "Expected call count for [$method] was [$expected], but got [$count]");
                $all_correct = $is_same && $all_correct;
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
         */
        function &_mockMethod($method, $args) {
            $method = strtolower($method);
            $step = $this->getCallCount($method);
            $this->_addCall($method, $args);
            if (isset($this->_return_sequence[$step])) {
                return $this->_return_sequence[$step]->findFirstMatch($method, $args);
            }
            return $this->_returns->findFirstMatch($method, $args);
        }
        
        /**
         *    Adds one to the call count of a method.
         *    Will also validate the arguments against the
         *    previously set expectations passing the
         *    events to the held test case.
         *    @param $method        Method called.
         *    @param $args          Arguments as an array.
         */
        function _addCall($method, $args) {
            if (!isset($this->_call_counts[$method])) {
                $this->_call_counts[$method] = 0;
            }
            $this->_checkExpectations($method, $args, $this->_call_counts[$method]);
            $this->_call_counts[$method]++;
        }
        
        /**
         *    Tests the arguments against expectations.
         *    @param $method            Method to check.
         *    @param $args              Argument list to match.
         *    @param $timing            The position of this call
         *                              in the call history.
         */
        function _checkExpectations($method, $args, $timing) {
            if (isset($this->_max_counts[$method])) {
                if ($timing >= $this->_max_counts[$method]) {
                    $this->_test->assertTrue(false, "Call count for [$method] is [$timing]");
                }
            }
            if (isset($this->_sequence_args[$timing][$method])) {
                $this->_assertTrue(
                        $this->_sequence_args[$timing][$method]->isMatch($args),
                        "Arguments for [$method] at [$timing] were [" . implode(", ", $args) . "]");
            } elseif (isset($this->_expected_args[$method])) {
                $this->_assertTrue(
                        $this->_expected_args[$method]->isMatch($args),
                        "Arguments for [$method] were [" . implode(", ", $args) . "]");
            }
        }
        
        /**
         *    Triggers an assertion on the held test case.
         *    Should be overridden when using another test
         *    framework other than the SimpleTest one.
         *    @param $assertion      True will pass.
         *    @param $message        Message that will go with
         *                           the test event.
         */
        function _assertTrue($assertion, $message) {
            $this->_test->assertTrue($assertion, $message);
        }
    }
    
    /**
     *    Static methods only class for code generation.
     */
    class Mock {
        
        /**
         *    Factory for Mock classes.
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
         *    The base class name is settable here.
         *    @param $mock_base        Mock base class to use.
         *                             If empty then the existing
         *                             class will be unchanged.
         *    @return                  Current or new base class.
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