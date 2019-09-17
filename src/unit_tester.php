<?php

require_once __DIR__.'/test_case.php';
require_once __DIR__.'/dumper.php';

/**
 * Standard unit test class for day to day testing of PHP code XP style.
 * Adds some useful standard assertions.
 */
class UnitTestCase extends SimpleTestCase
{
    /**
     * Creates an empty test case.
     * Should be subclassed with test methods for a functional test case.
     *
     * @param string $label Name of test case. Will use the class name if none specified.
     */
    public function __construct($label = false)
    {
        if (!$label) {
            $label = get_class($this);
        }
        parent::__construct($label);
    }

    /**
     * Called from within the test methods to register passes and failures.
     *
     * @param bool   $result  pass on true
     * @param string $message message to display describing the test state
     *
     * @return bool True on pass
     */
    public function assertTrue($result, $message = '%s')
    {
        return $this->assert(new TrueExpectation(), $result, $message);
    }

    /**
     * Will be true on false and vice versa.
     * False is the PHP definition of false, so that null,
     * empty strings, zero and an empty array all count as false.
     *
     * @param bool   $result  pass on false
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertFalse($result, $message = '%s')
    {
        return $this->assert(new FalseExpectation(), $result, $message);
    }

    /**
     * Will be true if the value is null.
     *
     * @param null   $value   supposedly null value
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertNull($value, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            '['.$dumper->describeValue($value).'] should be null'
        );

        return $this->assertTrue(!isset($value), $message);
    }

    /**
     * Will be true if the value is set.
     *
     * @param mixed  $value   supposedly set value
     * @param string $message message to display
     *
     * @return bool true on pass
     */
    public function assertNotNull($value, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            '['.$dumper->describeValue($value).'] should not be null'
        );

        return $this->assertTrue(isset($value), $message);
    }

    /**
     * Type and class test.
     * Will pass if class matches the type name or is a subclass or
     * if not an object, but the type is correct.
     *
     * @param mixed  $object  object to test
     * @param string $type    type name as string
     * @param string $message message to display
     *
     * @return bool true on pass
     */
    public function assertIsA($object, $type, $message = '%s')
    {
        return $this->assert(new IsAExpectation($type), $object, $message);
    }

    /**
     * Type and class mismatch test.
     * Will pass if class name or underling type does not match the one specified.
     *
     * @param mixed  $object  object to test
     * @param string $type    type name as string
     * @param string $message message to display
     *
     * @return bool true on pass
     */
    public function assertNotA($object, $type, $message = '%s')
    {
        return $this->assert(new NotAExpectation($type), $object, $message);
    }

    /**
     * Will trigger a pass if the two parameters have the same value only. Otherwise a fail.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertEqual($first, $second, $message = '%s')
    {
        return $this->assert(new EqualExpectation($first), $second, $message);
    }

    /**
     * Will trigger a pass if the two parameters have a different value.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, otherwise a fail
     */
    public function assertNotEqual($first, $second, $message = '%s')
    {
        return $this->assert(new NotEqualExpectation($first), $second, $message);
    }

    /**
     * Will trigger a pass if the if the first parameter is near enough to the second by the margin.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param mixed  $margin  fuzziness of match
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertWithinMargin($first, $second, $margin, $message = '%s')
    {
        return $this->assert(new WithinMarginExpectation($first, $margin), $second, $message);
    }

    /**
     * Will trigger a pass if the two parameters differ by more than the margin.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param mixed  $margin  fuzziness of match
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertOutsideMargin($first, $second, $margin, $message = '%s')
    {
        return $this->assert(new OutsideMarginExpectation($first, $margin), $second, $message);
    }

    /**
     * Will trigger a pass if the two parameters have the same value and same type.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, otherwise a fail
     */
    public function assertIdentical($first, $second, $message = '%s')
    {
        return $this->assert(new IdenticalExpectation($first), $second, $message);
    }

    /**
     * Will trigger a pass if the two parameters have the different value or different type.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool True on pass
     */
    public function assertNotIdentical($first, $second, $message = '%s')
    {
        return $this->assert(new NotIdenticalExpectation($first), $second, $message);
    }

    /**
     * Will trigger a pass if both parameters refer to the same object or value.
     * This will cause problems testing objects under E_STRICT.
     *
     * @todo Replace with expectation.
     *
     * @param mixed  $first   reference to check
     * @param mixed  $second  hopefully the same variable
     * @param string $message message to display
     *
     * @return bool true on pass, otherwise fail
     */
    public function assertReference(&$first, &$second, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $args = '['.$dumper->describeValue($first).'] '
            .'and ['.$dumper->describeValue($second).'] '
            .'should reference the same object';
        $isReference = SimpleTestCompatibility::isReference($first, $second);

        return $this->assertTrue($isReference, sprintf($message, $args));
    }

    /**
     * Will trigger a pass if both parameters refer to the same object.
     * This has the same semantics at the PHPUnit assertSame.
     * That is, if values are passed in it has roughly the same affect as assertIdentical.
     *
     * @todo Replace with expectation.
     *
     * @param mixed  $first   object reference to check
     * @param mixed  $second  hopefully the same object
     * @param string $message message to display
     *
     * @return bool true on pass, Fail otherwise
     */
    public function assertSame($first, $second, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            '['.$dumper->describeValue($first).
                        '] and ['.$dumper->describeValue($second).
                        '] should reference the same object'
        );

        return $this->assertTrue($first === $second, $message);
    }

    /**
     * Will trigger a pass if both parameters refer to different objects.
     * The objects have to be identical though.
     *
     * @param mixed  $first   object reference to check
     * @param mixed  $second  hopefully not the same object
     * @param string $message message to display
     *
     * @return bool true on pass, fail otherwise
     */
    public function assertClone($first, $second, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            '['.$dumper->describeValue($first).'] and ['.
            $dumper->describeValue($second).'] should not be the same object'
        );
        $identical = new IdenticalExpectation($first);

        return $this->assertTrue($identical->test($second) && !($first === $second), $message);
    }

    /**
     * Will trigger a pass if both parameters refer to different variables.
     * The objects have to be identical references though.
     * This will fail under E_STRICT with objects. Use assertClone() for this.
     *
     * @param mixed  $first   object reference to check
     * @param mixed  $second  hopefully not the same object
     * @param string $message message to display
     *
     * @return bool true on pass, Fail otherwise
     */
    public function assertCopy(&$first, &$second, $message = '%s')
    {
        $dumper = new SimpleDumper();
        $message = sprintf(
            $message,
            '['.$dumper->describeValue($first).
                        '] and ['.$dumper->describeValue($second).
                        '] should not be the same object'
        );

        return $this->assertFalse(
            SimpleTestCompatibility::isReference($first, $second),
            $message
        );
    }

    /**
     * Will trigger a pass if the Perl regex pattern is found in the subject.
     *
     * @param string $pattern perl regex to look for including the regex delimiters
     * @param string $subject string to search in
     * @param string $message message to display
     *
     * @return bool true on pass, Fail otherwise
     */
    public function assertPattern($pattern, $subject, $message = '%s')
    {
        return $this->assert(new PatternExpectation($pattern), $subject, $message);
    }

    /**
     * Will trigger a pass if the perl regex pattern is not present in subject.
     *
     * @param string $pattern perl regex to look for including the regex delimiters
     * @param string $subject string to search in
     * @param string $message message to display
     *
     * @return bool true on pass, Fail if found
     */
    public function assertNoPattern($pattern, $subject, $message = '%s')
    {
        return $this->assert(new NoPatternExpectation($pattern), $subject, $message);
    }

    /**
     * Prepares for an error. If the error mismatches it passes through, otherwise it is swallowed.
     * Any left over errors trigger failures.
     *
     * @param simpleExpectation/string $expected The error to match
     * @param string                   $message  message on failure
     */
    public function expectError($expected = false, $message = '%s')
    {
        $queue = SimpleTest::getContext()->get('SimpleErrorQueue');
        $queue->expectError($this->forceExpectation($expected), $message);
    }

    /**
     * Prepares for an exception. If the error mismatches it passes through, otherwise it is
     * swallowed. Any left over errors trigger failures.
     *
     * @param simpleExpectation/Exception $expected The error to match
     * @param string                      $message  message on failure
     */
    public function expectException($expected = false, $message = '%s')
    {
        $queue = SimpleTest::getContext()->get('SimpleExceptionTrap');
        $line = $this->getAssertionLine();
        $queue->expectException($expected, $message.$line);
    }

    /**
     * Tells SimpleTest to ignore an upcoming exception as not relevant to the current test.
     * It doesn't affect the test, whether thrown or not.
     *
     * @param simpleExpectation/Exception $ignored The error to ignore
     */
    public function ignoreException($ignored = false)
    {
        SimpleTest::getContext()->get('SimpleExceptionTrap')->ignoreException($ignored);
    }

    /**
     * Creates an equality expectation if the object/value is not already some type of expectation.
     *
     * @param mixed $expected expected value
     *
     * @return SimpleExpectation expectation object
     */
    protected function forceExpectation($expected)
    {
        if (false === $expected) {
            return new TrueExpectation();
        }
        if (is_a($expected, 'SimpleExpectation')) {
            return $expected;
        }

        return new EqualExpectation(
            is_string($expected) ? str_replace('%', '%%', $expected) : $expected
        );
    }
}
