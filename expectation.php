<?php
    // $Id$
    
    /**
     *    Assertion that can display failure information.
     *    Also includes various static helper methods.
     *    @abstract
     */
    class Expectation {
        
        /**
         *    Does nothing.
         */
        function Expectation() {
        }
        
        /**
         *    Tests the expectation. True if correct.
         *    @param $compare        Comparison value.
         *    @return                True if correct.
         *    @public
         *    @abstract
         */
        function test($compare) {
        }
        
        /**
         *    Returns a human readable test message.
         *    @param $compare      Comparison value.
         *    @return              String description of success
         *                         or failure.
         *    @public
         *    @abstract
         */
        function testMessage($compare) {
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
                return "String: " . Expectation::clipString($value, 40);
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
         *    @param $expectation_class   Test class to apply.
         *    @return                   Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second, $expectation_class) {
            if (!isset($first)) {
                return "as [" . Expectation::describeValue($first) .
                        "] does not match [" . Expectation::describeValue($second) . "]";
            } elseif (is_bool($first)) {
                return "as [" . Expectation::describeValue($first) .
                        "] does not match [" . Expectation::describeValue($second) . "]";
            } elseif (is_string($first)) {
                $position = Expectation::_stringDiffersAt($first, $second);
                return "at character $position with [" .
                        Expectation::clipString($first, 100, $position) . "] and [" .
                        Expectation::clipString($second, 100, $position) . "]";
            } elseif (is_integer($first)) {
                return Expectation::_describeIntegerDifference(
                        $first,
                        $second,
                        $expectation_class);
            } elseif (is_float($first)) {
                return Expectation::_describeFloatDifference(
                        $first,
                        $second,
                        $expectation_class);
            } elseif (is_array($first)) {
                return Expectation::_describeArrayDifference(
                        $first,
                        $second,
                        $expectation_class);
            } elseif (is_object($first)) {
                return Expectation::_describeObjectDifference(
                        $first,
                        $second,
                        $expectation_class);
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
         *    difference between two integers.
         *    @param $first             First number.
         *    @param $second            Number to compare with.
         *    @param $expectation_class   Test to apply.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeIntegerDifference($first, $second, $expectation_class) {
            return "because [" . Expectation::describeValue($first) ."] differs from [" .
                    Expectation::describeValue($second) . "] by " . abs($first - $second);
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two floating point numbers.
         *    @param $first             First float.
         *    @param $second            Float to compare with.
         *    @param $expectation_class   Test to apply.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeFloatDifference($first, $second, $expectation_class) {
            return "because [" . Expectation::describeValue($first) ."] differs from [" .
                    Expectation::describeValue($second) . "]";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two arrays.
         *    @param $first             First array.
         *    @param $second            Array to compare with.
         *    @param $expectation_class   Test to apply.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeArrayDifference($first, $second, $expectation_class) {
            if (array_keys($first) != array_keys($second)) {
                return "as keys [" .
                        implode(", ", array_keys($first)) . "] do not match  [" .
                        implode(", ", array_keys($second)) . "]";
            }
            foreach (array_keys($first) as $key) {
                $test = &new $expectation_class($first[$key]);
                if (!$test->test($second[$key])) {
                    return "with member [$key] " . Expectation::describeDifference(
                            $first[$key],
                            $second[$key],
                            $expectation_class);
                }
            }
            return "";
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two objects.
         *    @param $first             First object.
         *    @param $second            Object to compare with.
         *    @param $expectation_class   Test to apply.
         *    @return                   Descriptive string.
         *    @private
         *    @static
         */
        function _describeObjectDifference($first, $second, $expectation_class) {
            return Expectation::_describeArrayDifference(
                    get_object_vars($first),
                    get_object_vars($second),
                    $expectation_class);
        }
    }
    
    /**
     *    Test for equality.
     */
    class EqualExpectation extends Expectation {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function EqualExpectation($value) {
            $this->Expectation();
            $this->_value = $value;
        }
        
        /**
         *    Tests the expectation. True if it matches the
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
                return "Equal expectation [" . $this->describeValue($this->_value) . "]";
            } else {
                return "Equal expectation fails " .
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
    class NotEqualExpectation extends EqualExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function NotEqualExpectation($value) {
            $this->EqualExpectation($value);
        }
        
        /**
         *    Tests the expectation. True if it differs from the
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
                return "Not equal expectation passes " .
                        $this->describeDifference($this->_get_value(), $compare, get_class($this));
            } else {
                return "Not equal expectation fails [" . $this->describeValue($this->_get_value()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for identity.
     */
    class IdenticalExpectation extends EqualExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function IdenticalExpectation($value) {
            $this->EqualExpectation($value);
        }
        
        /**
         *    Tests the expectation. True if it exactly
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
                return "Identical expectation [" . $this->describeValue($this->_value) . "]";
            } else {
                return "Identical expectation [" . $this->describeValue($this->_value) .
                        "] fails with [" .
                        $this->describeValue($compare) . "] " .
                        $this->describeDifference($this->_value, $compare, get_class($this));
            }
        }
        
        /**
         *    Creates a human readable description of the
         *    difference between two variables.
         *    @param $first             First variable.
         *    @param $second            Value to compare with.
         *    @param $expectation_class Test class to apply.
         *    @return                   Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second, $expectation_class) {
            if (gettype($first) != gettype($second)) {
                return "by type";
            }
            return parent::describeDifference($first, $second, $expectation_class);
        }
    }
    
    /**
     *    Test for non-identity.
     */
    class NotIdenticalExpectation extends IdenticalExpectation {
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function NotIdenticalExpectation($value) {
            $this->IdenticalExpectation($value);
        }
        
        /**
         *    Tests the expectation. True if it differs from the
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
                return "Not identical expectation fails " .
                        $this->describeDifference($this->_get_value(), $compare, get_class($this));
            } else {
                return "Not identical expectation [" . $this->describeValue($this->_get_value()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for a pattern using Perl regex rules.
     */
    class WantedPatternExpectation extends Expectation {
        var $_pattern;
        
        /**
         *    Sets the value to compare against.
         *    @param $pattern        Pattern to search for.
         *    @public
         */
        function WantedPatternExpectation($pattern) {
            $this->Expectation();
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
         *    Tests the expectation. True if the Perl regex
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
                    Expectation::clipString($subject, 40) . "] as [" .
                    $matches[0] . "] in region [" .
                    Expectation::clipString($subject, 40, $position) . "]";
        }
    }
    
    /**
     *    Fail if a pattern is detected within the
     *    comparison.
     */
    class UnwantedPatternExpectation extends WantedPatternExpectation {
        
        /**
         *    Sets the reject pattern
         *    @param $pattern        Pattern to search for.
         *    @public
         */
        function UnwantedPatternExpectation($pattern) {
            $this->WantedPatternExpectation($pattern);
        }
        
        /**
         *    Tests the expectation. False if the Perl regex
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