<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    originally defined in simple_test.php
     */
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
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
    class UnitTestCase extends SimpleTestCase {
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param string $label     Name of test case. Will use
         *                             the class name if none specified.
         *    @access public
         */
        function UnitTestCase($label = false) {
            if (! $label) {
                $label = get_class($this);
            }
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Will be true if the value is null.
         *    @param null $value       Supposedly null value.
         *    @param string $message   Message to display.
         *    @access public
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
         *    @param mixed $value           Supposedly set value.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param mixed $object         Object to test.
         *    @param string $type          Type name as string.
         *    @param string $message       Message to display.
         *    @access public
         */
        function assertIsA($object, $type, $message = "%s") {
            $this->assertExpectation(
                    new IsAExpectation($type),
                    $object,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value only. Otherwise a fail.
         *    @param mixed $first          Value to compare.
         *    @param mixed $second         Value to compare.
         *    @param string $message       Message to display.
         *    @access public
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
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param mixed $first           Object reference to check.
         *    @param mixed $second          Hopefully the same object.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param mixed $first           Object reference to check.
         *    @param mixed $second          Hopefully not the same object.
         *    @param string $message        Message to display.
         *    @access public
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
         *    @param string $pattern Perl regex to look for including
         *                           the regex delimiters.
         *    @param string $subject String to search in.
         *    @param string $message Message to display.
         *    @access public
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
         *    @param string $pattern Perl regex to look for including
         *                           the regex delimiters.
         *    @param string $subject String to search in.
         *    @param string $message Message to display.
         *    @access public
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
         *    @param string $message        Message to display.
         *    @access public
         */
        function assertNoErrors($message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            $this->assertTrue(
                    $queue->isEmpty(),
                    sprintf($message, "Should be no errors"));
        }
        
        /**
         *    Confirms that an error has occoured and
         *    optionally that the error text matches exactly.
         *    @param string $expected   Expected error text or
         *                              false for no check.
         *    @param string $message    Message to display.
         *    @access public
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
        
        /**
         *    Confirms that an error has occoured and
         *    that the error text matches a Perl regular
         *    expression.
         *    @param string $expected   Perl regular expresion to
         *                              match against.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertErrorPattern($pattern, $message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            if ($queue->isEmpty()) {
                $this->fail(sprintf($message, "Expected error not found"));
                return;
            }
            list($severity, $content, $file, $line, $globals) = $queue->extract();
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            $this->assertTrue(
                    (boolean)preg_match($pattern, $content),
                    "Expected pattern match [$pattern] in PHP error [$content] severity [$severity] in [$file] line [$line]");
        }
    }
?>