<?php declare(strict_types=1);

require_once __DIR__ . '/test_case.php';

require_once __DIR__ . '/dumper.php';

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
            $label = static::class;
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
        return $this->assert(new TrueExpectation, $result, $message);
    }

    /**
     * Tests whether a value evaluates to false.
     *
     * In PHP, the following values are considered false:
     * - false itself
     * - null
     * - 0 (integer)
     * - 0.0 (float)
     * - "" (empty string)
     * - "0" (string containing zero)
     * - [] (empty array)
     *
     * @see https://www.php.net/manual/en/language.types.boolean.php#language.types.boolean.casting
     *
     * @param mixed  $result  the value to check (passes if it evaluates to false)
     * @param string $message message to display on failure
     *
     * @return bool true if the assertion passes, false otherwise
     */
    public function assertFalse($result, $message = '%s')
    {
        return $this->assert(new FalseExpectation, $result, $message);
    }

    /**
     * Assert that the given callable throws an exception of the expected type.
     *
     * assertThrows catches the exception, verifies its class,
     * and returns the exception so that further assertions can be made.
     *
     * @param callable    $fn            The function to execute
     * @param null|string $expectedClass Expected exception class (optional)
     * @param string      $message       Optional message to display if expectation fails
     *
     * @return null|Throwable The caught exception, or null on failure
     */
    public function assertThrows(callable $fn, ?string $expectedClass = null, string $message = ''): ?Throwable
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if ($expectedClass !== null && !($e instanceof $expectedClass)) {
                // Caller can assert on null to record a failure if desired.
                return null;
            }

            // Success: return the caught exception for further assertions by the caller.
            return $e;
        }

        // If we get here, no exception was thrown
        return null;
    }

    /**
     * Assert that the given callable throws an exception of the exact expected type.
     *
     * Unlike assertThrows (which uses instanceof), this requires the thrown
     * exception's concrete class to match the expected class exactly.
     *
     * @param callable $fn            The function to execute
     * @param string   $expectedClass Expected exception class (exact match)
     * @param string   $message       Optional message to display if expectation fails
     *
     * @return null|Throwable The caught exception on exact match, or null otherwise
     */
    public function assertThrowsExactly(callable $fn, string $expectedClass, string $message = ''): ?Throwable
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if ($e::class !== $expectedClass) {
                return null;
            }

            return $e;
        }

        return null;
    }

    /**
     * Assert that the given callable does not throw any exception.
     *
     * @param callable $fn      The function to execute
     * @param string   $message Optional message to display on failure
     *
     * @return bool True on pass (no exception), false on failure
     */
    public function assertDoesNotThrow(callable $fn, $message = '%s')
    {
        try {
            $fn();
        } catch (Throwable $e) {
            $msg = $message !== '%s'
                ? $message
                : \sprintf('Unexpected exception [%s] thrown: %s', $e::class, $e->getMessage());

            return $this->fail($msg);
        }

        return $this->assertTrue(true, $message !== '%s' ? $message : 'No exception thrown');
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
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] should  be null';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        $message = \sprintf($message, $msg_tpl);

        return $this->assertTrue($value === null, $message);
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
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] should not be null';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        $message = \sprintf($message, $msg_tpl);

        return $this->assertTrue($value !== null, $message);
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
     * Assert that the given object is an instance of the specified class.
     *
     * @param string $class   fully-qualified class name expected
     * @param mixed  $object  object to check
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertInstanceOf(string $class, $object, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not an instance of [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($object), $dumper->describeValue($class));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($object instanceof $class, $message);
    }

    /**
     * Assert that the given object has the specified property (declared on the class).
     *
     * Uses property_exists() to check for declared properties. This will return true
     * for declared properties even if their value is null, and it does not consult
     * magic __get implementations.
     *
     * @param string $property property name to look for
     * @param object $object   object to check
     * @param string $message  optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertObjectHasProperty(string $property, object $object, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not have property [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($object), $dumper->describeValue($property));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\property_exists($object, $property), $message);
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
     * Will trigger a pass if the two parameters have the same value only.
     * This is for testing hand extracted text, etc.
     *
     * Convenience alias for assertEqual()!
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, Otherwise a fail
     */
    public function assertEquals($first, $second, $message = '%s')
    {
        return $this->assert(
            new EqualExpectation($first),
            $second,
            $message,
        );
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
     * Will trigger a pass if the two parameters have a different value.
     *
     * Convenience alias for assertNotEqual().
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, otherwise a fail
     */
    public function assertNotEquals($first, $second, $message = '%s')
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
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] and [%s] should reference the same object';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($first), $dumper->describeValue($second));

        $message = \sprintf($message, $msg_tpl);

        $isReference = SimpleTestCompatibility::isReference($first, $second);

        return $this->assertTrue($isReference, $message);
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
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] and [%s] should reference the same object';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($first), $dumper->describeValue($second));

        $message = \sprintf($message, $msg_tpl);

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
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] and [%s] should not be the same object';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($first), $dumper->describeValue($second));

        $message = \sprintf($message, $msg_tpl);

        $identical = new IdenticalExpectation($first);

        return $this->assertTrue($identical->test($second) && $first !== $second, $message);
    }

    /**
     * Will trigger a pass if both parameters refer to different variables.
     * The objects have to be identical references though.
     * Use assertClone() for this.
     *
     * @param mixed  $first   object reference to check
     * @param mixed  $second  hopefully not the same object
     * @param string $message message to display
     *
     * @return bool true on pass, Fail otherwise
     */
    public function assertCopy(&$first, &$second, $message = '%s')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] and [%s] should reference the same object';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($first), $dumper->describeValue($second));

        $message = \sprintf($message, $msg_tpl);

        return $this->assertFalse(
            SimpleTestCompatibility::isReference($first, $second),
            $message,
        );
    }

    /**
     * Will trigger a pass if the regex pattern is found in the subject.
     *
     * @param string $pattern regex to look for including the regex delimiters
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
     * Will trigger a pass if the regex pattern is not present in subject.
     *
     * @param string $pattern regex to look for including the regex delimiters
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
     * Assert that the given substring is present inside the target string.
     *
     * @param string $substring    substring to look for
     * @param string $targetString string to search in
     * @param string $message      optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertStringContainsString(string $substring, string $targetString, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not contain [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($targetString), $dumper->describeValue($substring));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\str_contains($targetString, $substring), $message);
    }

    /**
     * Assert that the given target string starts with the provided prefix.
     *
     * @param string $prefix       prefix expected at the start of the target string
     * @param string $targetString string to check
     * @param string $message      optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertStringStartsWith(string $prefix, string $targetString, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not start with [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($targetString), $dumper->describeValue($prefix));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\str_starts_with($targetString, $prefix), $message);
    }

    /**
     * Assert that the given target string ends with the provided suffix.
     *
     * @param string $suffix       suffix expected at the end of the target string
     * @param string $targetString string to check
     * @param string $message      optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertStringEndsWith(string $suffix, string $targetString, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not end with [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($targetString), $dumper->describeValue($suffix));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        if ($suffix === '') {
            // Every string ends with an empty suffix
            return $this->assertTrue(true, $message);
        }

        $len      = \strlen($suffix);
        $endsWith = \substr($targetString, -$len) === $suffix;

        return $this->assertTrue($endsWith, $message);
    }

    /**
     * Assert that the given array has the provided key.
     *
     * @param int|string $key     key expected to exist in the array
     * @param array      $array   array to check
     * @param string     $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertArrayHasKey(int|string $key, array $array, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not have key [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($array), $dumper->describeValue($key));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\array_key_exists($key, $array), $message);
    }

    /**
     * Assert that the given array does NOT have the provided key.
     *
     * @param int|string $key     key expected to NOT exist in the array
     * @param array      $array   array to check
     * @param string     $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertArrayNotHasKey(int|string $key, array $array, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] has key [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($array), $dumper->describeValue($key));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(!\array_key_exists($key, $array), $message);
    }

    /**
     * Assert that the given collection (array or Traversable) contains the element.
     *
     * @param mixed             $element    element to look for
     * @param array|Traversable $collection array or Traversable to search
     * @param string            $message    optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertContains(mixed $element, array|Traversable $collection, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not contain [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($collection), $dumper->describeValue($element));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        $found = false;

        if (\is_array($collection)) {
            $found = \in_array($element, $collection, true);
        } else {
            foreach ($collection as $item) {
                if ($item === $element) {
                    $found = true;

                    break;
                }
            }
        }

        return $this->assertTrue($found, $message);
    }

    /**
     * Assert that the given collection (array or Traversable) does NOT contain the element.
     *
     * @param mixed             $element    element to look for
     * @param array|Traversable $collection array or Traversable to search
     * @param string            $message    optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertNotContains(mixed $element, array|Traversable $collection, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] contains [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($collection), $dumper->describeValue($element));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        $found = false;

        if (\is_array($collection)) {
            $found = \in_array($element, $collection, true);
        } else {
            foreach ($collection as $item) {
                if ($item === $element) {
                    $found = true;

                    break;
                }
            }
        }

        return $this->assertTrue(!$found, $message);
    }

    /**
     * Assert that the given array or Countable has the expected count.
     *
     * @param int             $expectedCount expected number of elements
     * @param array|Countable $value         array or Countable to count
     * @param string          $message       optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertCount(int $expectedCount, array|Countable $value, string $message = '')
    {
        $dumper = new SimpleDumper;

        $actual = \count($value);

        $msg_tpl = '[%s] expected count [%d], actual [%d]';

        return $this->assertTrue($actual === $expectedCount, $message);
    }

    /**
     * Assert that $actual is greater than $expected.
     *
     * @param mixed  $expected expected threshold
     * @param mixed  $actual   actual value to test
     * @param string $message  optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertGreaterThan($expected, $actual, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not greater than [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($actual), $dumper->describeValue($expected));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($actual > $expected, $message);
    }

    /**
     * Assert that $actual is greater than or equal to $expected.
     */
    public function assertGreaterThanOrEqual($expected, $actual, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not greater than or equal to [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($actual), $dumper->describeValue($expected));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($actual >= $expected, $message);
    }

    /**
     * Assert that $actual is less than $expected.
     */
    public function assertLessThan($expected, $actual, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not less than [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($actual), $dumper->describeValue($expected));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($actual < $expected, $message);
    }

    /**
     * Assert that $actual is less than or equal to $expected.
     */
    public function assertLessThanOrEqual($expected, $actual, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not less than or equal to [%s]';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($actual), $dumper->describeValue($expected));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($actual <= $expected, $message);
    }

    /**
     * Assert that two floating point numbers are equal to within the given delta.
     *
     * @param float  $expected expected value
     * @param float  $actual   actual value to test
     * @param float  $delta    allowed difference
     * @param string $message  optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertEqualsWithDelta(float $expected, float $actual, float $delta, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not equal to [%s] within delta [%s]';
        $msg_tpl = \sprintf(
            $msg_tpl,
            $dumper->describeValue($actual),
            $dumper->describeValue($expected),
            $dumper->describeValue($delta),
        );

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        $diff = \abs($actual - $expected);

        return $this->assertTrue($diff <= $delta, $message);
    }

    /**
     * Assert that executing the given callable takes longer than the specified number of seconds.
     *
     * @param callable $fn      callable to execute
     * @param float    $seconds threshold in seconds
     * @param string   $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertTimeout(callable $fn, float $seconds, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = 'Execution time [%s]s did not exceed expected timeout [%s]s';

        $start = \microtime(true);
        $fn();
        $end = \microtime(true);

        $elapsed = $end - $start;

        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($elapsed), $dumper->describeValue($seconds));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($elapsed > $seconds, $message);
    }

    /**
     * Assert that executing the given callable does NOT take longer than the specified seconds.
     *
     * @param callable $fn      callable to execute
     * @param float    $seconds maximum allowed duration in seconds
     * @param string   $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertDoesNotTimeout(callable $fn, float $seconds, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = 'Execution time [%s]s exceeded maximum allowed [%s]s';

        $start = \microtime(true);
        $fn();
        $end = \microtime(true);

        $elapsed = $end - $start;

        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($elapsed), $dumper->describeValue($seconds));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue($elapsed <= $seconds, $message);
    }

    /**
     * Assert that the given path points to an existing file.
     *
     * @param string $path    path to the file
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertFileExists(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not exist or is not a file';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_file($path), $message);
    }

    /**
     * Assert that the given path points to an existing directory.
     *
     * @param string $path    path to the directory
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertDirExists(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] does not exist or is not a directory';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_dir($path), $message);
    }

    /**
     * Assert that the given path is readable.
     *
     * @param string $path    file or directory path
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsReadable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not readable';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_readable($path), $message);
    }

    /**
     * Assert that the given path is writable.
     *
     * @param string $path    file or directory path
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsWritable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not writable';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_writable($path), $message);
    }

    /**
     * Assert that the given path points to an existing file that is readable.
     *
     * @param string $path    path to the file
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertFileIsReadable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not a readable file';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_file($path) && \is_readable($path), $message);
    }

    /**
     * Assert that the given path points to a directory that is readable.
     *
     * @param string $path    path to the directory
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertDirIsReadable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not a readable directory';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_dir($path) && \is_readable($path), $message);
    }

    /**
     * Assert that the given path points to a file that is writable.
     *
     * @param string $path    path to the file
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertFileIsWritable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not a writable file';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_file($path) && \is_writable($path), $message);
    }

    /**
     * Assert that the given path points to a directory that is writable.
     *
     * @param string $path    path to the directory
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertDirIsWritable(string $path, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not a writable directory';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($path));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_dir($path) && \is_writable($path), $message);
    }

    /**
     * Assert that the given float value is NaN (not a number).
     *
     * @param float  $value   value to test
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsNaN(float $value, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not NaN';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_nan($value), $message);
    }

    /**
     * Assert that the given float value is infinite (positive or negative).
     *
     * @param float  $value   value to test
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsInfinite(float $value, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not infinite';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_infinite($value), $message);
    }

    /**
     * Assert that the given float value is positive infinity.
     *
     * @param float  $value   value to test
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsPositiveInfinity(float $value, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not positive infinity';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_infinite($value) && $value === \INF, $message);
    }

    /**
     * Assert that the given float value is negative infinity.
     *
     * @param float  $value   value to test
     * @param string $message optional message (can contain a single %s placeholder)
     *
     * @return bool true on pass, false otherwise
     */
    public function assertIsNegativeInfinity(float $value, string $message = '')
    {
        $dumper = new SimpleDumper;

        $msg_tpl = '[%s] is not negative infinity';
        $msg_tpl = \sprintf($msg_tpl, $dumper->describeValue($value));

        if ($message !== '') {
            $message = \sprintf($message, $msg_tpl);
        } else {
            $message = $msg_tpl;
        }

        return $this->assertTrue(\is_infinite($value) && $value === -\INF, $message);
    }

    /**
     * Prepares for an error. If the error mismatches it passes through, otherwise it is swallowed.
     * Any left over errors trigger failures.
     *
     * @param mixed  $expected The error to match
     * @param string $message  message on failure
     */
    public function expectError($expected = false, $message = '%s'): void
    {
        $queue = SimpleTest::getContext()->get('SimpleErrorQueue');
        $queue->expectError($this->forceExpectation($expected), $message);
    }

    /**
     * Prepares for an exception. If the error mismatches it passes through, otherwise it is
     * swallowed. Any left over errors trigger failures.
     *
     * Note: if you want to catch the exception and continue the test (for further
     * assertions), use assertThrows() which returns the caught exception.
     *
     * @param mixed  $expected The error to match
     * @param string $message  message on failure
     */
    public function expectException($expected = false, $message = '%s'): void
    {
        $queue = SimpleTest::getContext()->get('SimpleExceptionTrap');
        $line  = $this->getAssertionLine();
        $queue->expectException($expected, $message . $line);
    }

    /**
     * Tells SimpleTest to ignore an upcoming exception as not relevant to the current test.
     * It doesn't affect the test, whether thrown or not.
     *
     * @param mixed $ignored The error to ignore
     */
    public function ignoreException($ignored = false): void
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
            return new TrueExpectation;
        }

        if (\is_a($expected, 'SimpleExpectation')) {
            return $expected;
        }

        $v = \is_string($expected) ? \str_replace('%', '%%', $expected) : $expected;

        return new EqualExpectation($v);
    }
}
