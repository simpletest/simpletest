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
         *    @param $compare    Comparison value.
         *    @return            String description of success
         *                       or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Equal [" . $this->describeValue($this->_value) . "]";
            } else {
                return "[" . $this->describeValue($this->_value) .
                        "] differs from [" .
                        $this->describeValue($compare) . "] at " .
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
                return "Float: $arg";
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
         *    @param $first    First variable.
         *    @param $second   Value to compare with.
         *    @return          Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second) {
            if (!isset($first)) {
                return "null value";
            } elseif (is_bool($first)) {
                return "Boolean: " . ($var ? "true" : "false");
            } elseif (is_string($first)) {
                return "character " . $this->_stringDiffersAt($first, $second);
            } elseif (is_integer($first)) {
                return "Integer: $var";
            } elseif (is_float($first)) {
                return "Float: $arg";
            } elseif (is_array($first)) {
                return "Array: " . count($var) . " items";
            } elseif (is_resource($first)) {
                return "Resource: $var";
            } elseif (is_object($first)) {
                return "Object: of " . get_class($var);
            }
            return "no value difference";
        }
        
        /**
         *    Find the first character position that differs
         *    in two strings by binary chop.
         *    @param $first        First string.
         *    @param $second       String to compare with.
         *    @return              Integer position.
         *    @private
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
                $step = $this->_halve($step);
                if (strncmp($first, $second, $position + $step) == 0) {
                    $position += $step;
                }
            }
            return $position;
        }
        
        /**
         *    Halves a number rounding up.
         *    @param $value    Item to halve.
         *    @return          Half size integer rounded up.
         *    @private
         */
        function _halve($value) {
            return (integer)(($value + 1)/2);
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