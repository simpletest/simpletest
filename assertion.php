<?php
    // $Id$
    
    /**
     *    Assertion that can display failure information.
     *    Also includes various static helper methods.
     *    @abstract
     */
    class Assertion {
        
        /**
         *    Does nothing.
         */
        function Assertion() {
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
         *    Renders a variable in a shorter form than print_r().
         *    @param $value      Variable to render as a string.
         *    @return            Human readable string form.
         *    @public
         *    @static
         */
        function describeValue($value) {
            if (!isset($value)) {
                return "NULL";
            } elseif (is_bool($value)) {
                return "Boolean: " . ($value ? "true" : "false");
            } elseif (is_string($value)) {
                return "String: " . Assertion::clipString($value, 40);
            } elseif (is_integer($value)) {
                return "Integer: $value";
            } elseif (is_float($value)) {
                return "Float: $value";
            } elseif (is_array($value)) {
                return "Array: " . count($value) . " items";
            } elseif (is_resource($value)) {
                return "Resource: $value";
            } elseif (is_object($value)) {
                return "Object: of " . get_class($value);
            }
            return "Unknown";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two variables.
         *    @param $first             First variable.
         *    @param $second            Value to compare with.
         *    @param $assertion_class   Test class to apply.
         *    @return                   Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second, $assertion_class) {
            if (gettype($first) != gettype($second)) {
                return "by type";
            }
            if (is_string($first)) {
                $position = Assertion::_stringDiffersAt($first, $second);
                return "at character $position with [" .
                        Assertion::clipString($first, 40, $position) . "] and [" .
                        Assertion::clipString($second, 40, $position) . "]";
            } elseif (is_integer($first)) {
                return "by " . abs($first - $second);
            } elseif (is_array($first)) {
                return Assertion::_describeArrayDifference(
                        $first,
                        $second,
                        $assertion_class);
            } elseif (is_object($first)) {
                return "";
            }
            return "by value";
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
         *    Clips a string to a maximum length.
         *    @param $value        String to truncate.
         *    @param $size         Minimum string size to show.
         *    @param $position     Centre of string section.
         *    @return              Shortened version.
         *    @public
         *    @static
         */
        function clipString($value, $size, $position = 0) {
            $length = strlen($value);
            if ($length <= $size) {
                return $value;
            }
            $position = min($position, $length);
            $start = ($size/2 > $position ? 0 : $position - $size/2);
            if ($start + $size > $length) {
                $start = $length - $size;
            }
            $value = substr($value, $start, $size);
            return ($start > 0 ? "..." : "") . $value . ($start + $size < $length ? "..." : "");
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param $first             First array.
         *    @param $second            Array to compare with.
         *    @param $assertion_class   Test to apply.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeArrayDifference($first, $second, $assertion_class) {
            if (array_keys($first) != array_keys($second)) {
                return "keys [" .
                        implode(", ", array_keys($first)) . "] do not match  [" .
                        implode(", ", array_keys($second)) . "]";
            }
            foreach (array_keys($first) as $key) {
                $test = &new $assertion_class($first[$key]);
                if (!$test->test($second[$key])) {
                    return "key [$key] " . Assertion::describeDifference(
                            $first[$key],
                            $second[$key],
                            $assertion_class);
                }
            }
            return "";
        }
    }
    
    /**
     *    Test for equality.
     */
    class EqualAssertion extends Assertion {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function EqualAssertion($value) {
            $this->Assertion();
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
            return (($this->_value == $compare) && ($compare == $this->_value));
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Equal assertion [" . $this->describeValue($this->_value) . "]";
            } else {
                return "Equal assertion [" . $this->describeValue($this->_value) .
                        "] fails with [" .
                        $this->describeValue($compare) . "] " .
                        $this->describeDifference($this->_value, $compare, get_class($this));
            }
        }
        
        /**
         *    Accessor for comparison value.
         *    @return        Held value to compare with.
         *    @protected
         */
        function _get_value() {
            return $this->_value;
        }
    }
    
    /**
     *    Test for inequality.
     */
    class NotEqualAssertion extends EqualAssertion {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function NotEqualAssertion($value) {
            $this->EqualAssertion($value);
        }
        
        /**
         *    Tests the assertion. True if it differs from the
         *    held value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return !parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Not equal assertion differs " .
                        $this->describeDifference($this->_get_value(), $compare, get_class($this));
            } else {
                return "Not equal assertion [" . $this->describeValue($this->_get_value()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for identity.
     */
    class IdenticalAssertion extends EqualAssertion {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function IdenticalAssertion($value) {
            $this->EqualAssertion($value);
        }
        
        /**
         *    Tests the assertion. True if it exactly
         *    matches the held value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return ($this->_get_value() === $compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Identical assertion [" . $this->describeValue($this->_value) . "]";
            } else {
                return "Identical assertion [" . $this->describeValue($this->_value) .
                        "] fails with [" .
                        $this->describeValue($compare) . "] " .
                        $this->describeDifference($this->_value, $compare, get_class($this));
            }
        }
    }
    
    /**
     *    Test for non-identity.
     */
    class NotIdenticalAssertion extends IdenticalAssertion {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function NotIdenticalAssertion($value) {
            $this->IdenticalAssertion($value);
        }
        
        /**
         *    Tests the assertion. True if it differs from the
         *    held value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return !parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Not identical assertion differs " .
                        $this->describeDifference($this->_get_value(), $compare, get_class($this));
            } else {
                return "Not identical assertion [" . $this->describeValue($this->_get_value()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for a pattern using Perl regex rules.
     */
    class WantedPatternAssertion extends Assertion {
        var $_pattern;
        
        /**
         *    Sets the value to compare against.
         *    @param $pattern        Pattern to search for.
         *    @public
         */
        function WantedPatternAssertion($pattern) {
            $this->Assertion();
            $this->_pattern = $pattern;
        }
        
        /**
         *    Accessor for the pattern.
         *    @return        Perl regex as string.
         *    @protected
         */
        function _get_pattern() {
            return $this->_pattern;
        }
        
        /**
         *    Tests the assertion. True if the Perl regex
         *    matches the comparison value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return (boolean)preg_match($this->_get_pattern(), $compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return $this->_decribePatternMatch($this->_get_pattern(), $compare);
            } else {
                return "Pattern [" . $this->_get_pattern() . "] not detected in string [$compare]";
            }
        }
        
        /**
         *    Describes a pattern match including the string
         *    found and it's position.
         */
        function _decribePatternMatch($pattern, $subject) {
            preg_match($pattern, $subject, $matches);
            $position = strpos($subject, $matches[0]);
            return "Pattern [$pattern] detected at [$position] in string [" .
                    Assertion::clipString($subject, 40) . "] as [" .
                    $matches[0] . "] in region [" .
                    Assertion::clipString($subject, 40, $position) . "]";
        }
    }
    
    /**
     *    Fail if a pattern is detected within the
     *    comparison.
     */
    class UnwantedPatternAssertion extends WantedPatternAssertion {
        
        /**
         *    Sets the reject pattern
         *    @param $pattern        Pattern to search for.
         *    @public
         */
        function UnwantedPatternAssertion($pattern) {
            $this->WantedPatternAssertion($pattern);
        }
        
        /**
         *    Tests the assertion. False if the Perl regex
         *    matches the comparison value.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         */
        function test($compare) {
            return !parent::test($compare);
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         */
        function testMessage($compare) {
            if ($this->test($compare)) {
                return "Pattern [" . $this->_get_pattern() . "] not detected in string [$compare]";
            } else {
                return $this->_decribePatternMatch($this->_get_pattern(), $compare);
            }
         }
    }
?>