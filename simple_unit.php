<?php
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
         */
        function assertNull($value) {
            $this->assertTrue(!isset($value), "[" . gettype($value) . ": $value] should be null");
        }
        
        /**
         *    Will be true if the value is set.
         *    @param $value          Supposedly set value.
         */
        function assertNotNull($value) {
            $this->assertTrue(isset($value), "[" . gettype($value) . ": $value] should be not be null");
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value only. Otherwise a fail.
         *    @param $first            Value to compare.
         *    @param $second           Value to compare.
         */
        function assertEqual($first, $second) {
            $this->assertTrue(
                    $first == $second,
                    "[" . gettype($first) . ": $first] should be equal to [" . gettype($second) . ": $second]");
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value and same type. Otherwise a fail.
         *    @param $first            Value to compare.
         *    @param $second           Value to compare.
         */
        function assertIdentical($first, $second) {
            $this->assertTrue(
                    $first === $second,
                    "[" . gettype($first) . ": $first] should be identical to [" . gettype($second) . ": $second]");
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to the same object.
         *    @param $first            Object reference to check.
         *    @param $second           Hopefully the same object.
         */
        function assertReference(&$first, &$second) {
            $temp = $first;
            $first = uniqid("test");
            $is_ref = ($first === $second);
            $first = $temp;
            $this->assertTrue($is_ref, "Is reference test for [$first] and [$second]");
        }
        
        /**
         *    Will trigger a pass if the Perl regex pattern
         *    is found in the subject. Fail otherwise.
         *    @param $subject        String to search in.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         */
        function assertWantedPattern($subject, $pattern) {
            $this->assertTrue(
                    (boolean)preg_match($pattern, $subject),
                    "Expecting [$pattern] in [$subject]");
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in subject. Fail if found.
         *    @param $subject        String to search in.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         */
        function assertNoUnwantedPattern($subject, $pattern) {
            $this->assertTrue(
                    !preg_match($pattern, $subject),
                    "Not expecting [$pattern] in [$subject]");
        }
    }
?>