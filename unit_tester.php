<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    require_once(SIMPLE_TEST . 'errors.php');
    require_once(SIMPLE_TEST . 'dumper.php');
    
    if (! function_exists('is_a')) {
        function is_a( $object, $className ) {
            return ((strtolower($className) == get_class($object))
                    or (is_subclass_of($object, $className)));
        }
    }

    /**
     *    Standard unit test class for day to day testing
     *    of PHP code XP style. Adds some useful standard
     *    assertions.
     */
    class UnitTestCase extends SimpleTestCase {
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param $label            Name of test case. Will use
         *                             the class name if none specified.
         *    @public
         */
        function UnitTestCase($label = false) {
            if (!$label) {
                $label = get_class($this);
            }
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Will be true if the value is null.
         *    @param $value          Supposedly null value.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNull($value, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($value) . "] should be null");
            $this->assertTrue(!isset($value), $message);
        }
        
        /**
         *    Will be true if the value is set.
         *    @param $value          Supposedly set value.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNotNull($value, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($value) . "] should not be null");
            $this->assertTrue(isset($value), $message);
        }
        
        /**
         *    Type and class test. Will pass if class
         *    matches the type name or is a subclass or
         *    if not an object, but the type is correct.
         *    @param $object        Object to test.
         *    @param $type          Type name as string.
         *    @public
         */
        function assertIsA($object, $type, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($object) . "] should be type [$type]");
            if (is_object($object)) {
                $this->assertTrue(is_a($object, $type), $message);
            } else {
                $this->assertTrue(
                        strtolower(gettype($object)) == strtolower($type),
                        $message);
            }
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value only. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         *    @public
         */
        function assertEqual($first, $second, $message = "%s") {
            $this->assertExpectation(
                    new EqualExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    a different value. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNotEqual($first, $second, $message = "%s") {
            $this->assertExpectation(
                    new NotEqualExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value and same type. Otherwise a fail.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         *    @public
         */
        function assertIdentical($first, $second, $message = "%s") {
            $this->assertExpectation(
                    new IdenticalExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the different value or different type.
         *    @param $first          Value to compare.
         *    @param $second         Value to compare.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNotIdentical($first, $second, $message = "%s") {
            $this->assertExpectation(
                    new NotIdenticalExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to the same object. Fail otherwise.
         *    @param $first          Object reference to check.
         *    @param $second         Hopefully the same object.
         *    @param $message        Message to display.
         *    @public
         */
        function assertReference(&$first, &$second, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($first) .
                            "] and [" . $dumper->describeValue($second) .
                            "] should reference the same object");
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
         *    @public
         */
        function assertCopy(&$first, &$second, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($first) .
                            "] and [" . $dumper->describeValue($second) .
                            "] should not be the same object");
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
         *    @public
         */
        function assertWantedPattern($pattern, $subject, $message = "%s") {
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    $subject,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in subject. Fail if found.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $subject        String to search in.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNoUnwantedPattern($pattern, $subject, $message = "%s") {
            $this->assertExpectation(
                    new UnwantedPatternExpectation($pattern),
                    $subject,
                    $message);
        }
        
        /**
         *    Confirms that no errors have occoured so
         *    far in the test method.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNoErrors($message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            $this->assertTrue(
                    $queue->isEmpty(),
                    sprintf($message, "Should be no errors"));
        }
        
        /**
         *    Confirms that an error has occoured and
         *    optionally that the error text matches.
         *    @param $expected   Expected error text or
         *                       false for no check.
         *    @param $message    Message to display.
         *    @public
         */
        function assertError($expected = false, $message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            if ($queue->isEmpty()) {
                $this->fail(sprintf($message, "Expected error not found"));
                return;
            }
            list($severity, $content, $file, $line, $globals) = $queue->extract();
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            $this->assertTrue(
                    !$expected || ($expected == $content),
                    "Expected [$expected] in PHP error [$content] severity [$severity] in [$file] line [$line]");
        }
    }
?>