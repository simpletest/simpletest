<?php
    // $Id$
    
    /**
     *    Assertion that can display failure information.
     *    @abstract
     */
    class Assertion {
        
        /**
         *    Does nothing.
         */
        function SimpleAssertion() {
        }
        
        /**
         *    Tests the assertion. True if correct.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @param $test_class   Test class to apply.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare, $test_class = "equalityassertion") {
            if ($this->test($compare)) {
                return "$test_class [" . $this->describeValue($this->_value) . "]";
            } else {
                return "$test_class [" . $this->describeValue($this->_value) .
                        "] fails with [" .
                        $this->describeValue($compare) . "]" .
                        $this->describeDifference($this->_value, $compare);
            }
        }

        /**
         *    Renders a variable in a shorter for than print_r().
         *    @param $var        Variable to render as a string.
         *    @return            Human readable string form.
         *    @public
         *    @static
         */
        function describeValue($var) {
            if (!isset($var)) {
                return "NULL";
            } elseif (is_bool($var)) {
                return "Boolean: " . ($var ? "true" : "false");
            } elseif (is_string($var)) {
                return "String: $var";
            } elseif (is_integer($var)) {
                return "Integer: $var";
            } elseif (is_float($var)) {
                return "Float: $var";
            } elseif (is_array($var)) {
                return "Array: " . count($var) . " items";
            } elseif (is_resource($var)) {
                return "Resource: $var";
            } elseif (is_object($var)) {
                return "Object: of " . get_class($var);
            }
            return "Unknown";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two variables.
         *    @param $first        First variable.
         *    @param $second       Value to compare with.
         *    @param $test_class   Test class to apply.
         *    @return              Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second, $test_class = "equalityassertion") {
            if (is_string($first)) {
                return " at character " . Assertion::_stringDiffersAt($first, $second);
            } elseif (is_integer($first)) {
                return " by " . abs($first - $second);
            } elseif (is_array($first)) {
                return " key " .
                        Assertion::_describeArrayDifference($first, $second, $test_class);
            } elseif (is_object($first)) {
                return "";
            }
            return "";
        }
        
        /**
         *    Find the first character position that differs
         *    in two strings by binary chop.
         *    @param $first        First string.
         *    @param $second       String to compare with.
         *    @return              Integer position.
         *    @private
         *    @static
         */
        function _stringDiffersAt($first, $second) {
            if (!$first || !$second) {
                return 0;
            }
            if (strlen($first) < strlen($second)) {
                list($first, $second) = array($second, $first);
            }
            $position = 0;
            $step = strlen($first);
            while ($step > 1) {
                $step = (integer)(($step + 1)/2);
                if (strncmp($first, $second, $position + $step) == 0) {
                    $position += $step;
                }
            }
            return $position;
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param $first        First array.
         *    @param $second       Array to compare with.
         *    @param $test_class   Test to apply.
         *    @return              Descriptive string.
         *    @private
         *    @static
         */
        function _describeArrayDifference($first, $second, $test_class) {
            $keys = array_merge(array_keys($first), array_keys($second));
            sort($keys);
            foreach ($keys as $key) {
                if (!isset($first[$key])) {
                    return "$key does not exist in first array";
                }
                if (!isset($second[$key])) {
                    return "$key does not exist in second array";
                }
                $test = &new $test_class($first[$key]);
                if (!$test->test($second[$key])) {
                    return "$key" . Assertion::describeDifference(
                            $first[$key],
                            $second[$key],
                            $test_class);
                }
            }
            return "";
        }
    }
    
    /**
     *    Test for equality.
     */
    class EqualityAssertion extends Assertion {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function EqualityAssertion($value) {
            $this->_value = $value;
        }
        
        /**
         *    Tests the assertion. True if it matches the
         *    held value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return ($this->_value == $compare);
        }
    }
?>