<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'dumper.php');
    
    /**
     *    Assertion that can display failure information.
     *    Also includes various helper methods.
     *    @abstract
     */
    class SimpleExpectation extends SimpleDumper {
        
        /**
         *    Does nothing.
         */
        function SimpleExpectation() {
            $this->SimpleDumper();
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
    }
    
    /**
     *    Test for equality.
     */
    class EqualExpectation extends SimpleExpectation {
        var $_value;
        
        /**
         *    Sets the value to compare against.
         *    @param $value        Test value to match.
         *    @public
         */
        function EqualExpectation($value) {
            $this->SimpleExpectation();
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
         *    @return                   Descriptive string.
         *    @public
         *    @static
         */
        function describeDifference($first, $second) {
            if (gettype($first) != gettype($second)) {
                return "by type";
            }
            return parent::describeDifference($first, $second);
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
                return "Not identical expectation passes " .
                        $this->describeDifference($this->_get_value(), $compare, get_class($this));
            } else {
                return "Not identical expectation [" . $this->describeValue($this->_get_value()) . "] matches";
            }
        }
    }
    
    /**
     *    Test for a pattern using Perl regex rules.
     */
    class WantedPatternExpectation extends SimpleExpectation {
        var $_pattern;
        
        /**
         *    Sets the value to compare against.
         *    @param $pattern        Pattern to search for.
         *    @public
         */
        function WantedPatternExpectation($pattern) {
            $this->SimpleExpectation();
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
                    $this->clipString($subject, 40) . "] as [" .
                    $matches[0] . "] in region [" .
                    $this->clipString($subject, 40, $position) . "]";
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