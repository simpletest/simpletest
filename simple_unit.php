<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    
    /**
     *    Standard unit test class for day to day testing
     *    of PHP code XP style. Adds some useful standard
     *    assertions.
     */
    class UnitTestCase extends TestCase {
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param $label            Name of test case. Will use
         *                             the class name if none specified.
         */
        function UnitTestCase($label = "") {
            if ($label == "") {
                $label = get_class($this);
            }
            $this->TestCase($label);
        }
        
        /**
         *    Will be true on false and vice versa.
         *    @param $boolean        Supposedly false value.
         *    @param $message        Message to display.
         */
        function assertFalse($boolean, $message = "False assertion") {
            $this->assertTrue(!$boolean, $message);
        }
        
        /**
         *    Will be true if the value is null.
         *    @param $value          Supposedly null value.
         *    @param $message        Message to display.
         */
        function assertNull($value, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($value) . ": $value] should be null";
            }
            $this->assertTrue(!isset($value), $message);
        }
        
        /**
         *    Will be true if the value is set.
         *    @param $value          Supposedly set value.
         *    @param $message        Message to display.
         */
        function assertNotNull($value, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($value) . ": $value] should be not be null";
            }
            $this->assertTrue(isset($value), $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value only. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         */
        function assertEqual($first, $second, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($first) . ": $first] should be equal to [" . gettype($second) . ": $second]";
            }
            $this->assertTrue($first == $second, $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    a different value. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         */
        function assertNotEqual($first, $second, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($first) . ": $first] should not be equal to [" . gettype($second) . ": $second]";
            }
            $this->assertTrue($first != $second, $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value and same type. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         */
        function assertIdentical($first, $second, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($first) . ": $first] should be identical to [" . gettype($second) . ": $second]";
            }
            $this->assertTrue($first === $second, $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the different value or different type.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         */
        function assertNotIdentical($first, $second, $message = "") {
            if ($message == "") {
                $message = "[" . gettype($first) . ": $first] should not be identical to [" . gettype($second) . ": $second]";
            }
            $this->assertTrue($first !== $second, $message);
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to the same object. Fail otherwise.
         *    @param $first          Object reference to check.
         *    @param $second         Hopefully the same object.
         *    @param $message        Message to display.
         */
        function assertReference(&$first, &$second, $message = "") {
            if ($message == "") {
                $message = "[$first] and [$second] should reference the same object";
            }
            $temp = $first;
            $first = uniqid("test");
            $is_ref = ($first === $second);
            $first = $temp;
            $this->assertTrue($is_ref, $message);
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to different objects. Fail otherwise.
         *    @param $first          Object reference to check.
         *    @param $second         Hopefully not the same object.
         *    @param $message        Message to display.
         */
        function assertCopy(&$first, &$second, $message = "") {
            if ($message == "") {
                $message = "[$first] and [$second] should not be the same object";
            }
            $temp = $first;
            $first = uniqid("test");
            $is_ref = ($first === $second);
            $first = $temp;
            $this->assertFalse($is_ref, $message);
        }
        
        /**
         *    Will trigger a pass if the Perl regex pattern
         *    is found in the subject. Fail otherwise.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $subject        String to search in.
         *    @param $message        Message to display.
         */
        function assertWantedPattern($pattern, $subject, $message = "") {
            if ($message == "") {
                $message = "Expecting [$pattern] in [$subject]";
            }
            $this->assertTrue((boolean)preg_match($pattern, $subject), $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in subject. Fail if found.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $subject        String to search in.
         *    @param $message        Message to display.
         */
        function assertNoUnwantedPattern($pattern, $subject, $message = "") {
            if ($message == "") {
                $message = "Not expecting [$pattern] in [$subject]";
            }
            $this->assertTrue(!preg_match($pattern, $subject), $message);
        }
    }
?>