<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/dumper.php';

require_once __DIR__ . '/../../src/compatibility.php';

require_once __DIR__ . '/../../src/test_case.php';

require_once __DIR__ . '/../../src/expectation.php';

/**
 * Bridge Adapter for a PHPUnit test case class.
 * This allows PHPUnit tests to be used with SimpleTest.
 */
class PHPUnitTestCase extends SimpleTestCase
{
    private $_loosely_typed = false;

    /**
     * Constructor. Sets the test name.
     *
     * @param $label Test name to display
     */
    public function __construct($label = false)
    {
        parent::__construct($label);
    }

    /**
     * Will test straight equality if set to loose typing, or identity if not.
     *
     * @param $first   First value
     * @param $second  Comparison value
     * @param $message Message to display
     */
    public function assertEquals($first, $second, $message = '%s', $delta = 0): void
    {
        $expectation = $this->_loosely_typed ? new EqualExpectation($first) : new IdenticalExpectation($first);
        $this->assert($expectation, $second, $message);
    }

    /**
     * Passes if the value tested is not null.
     *
     * @param $value   Value to test against
     * @param $message Message to display
     */
    public function assertNotNull($value, $message = '%s'): void
    {
        parent::assert(new TrueExpectation, isset($value), $message);
    }

    /**
     * Passes if the value tested is null.
     *
     * @param $value   Value to test against
     * @param $message Message to display
     */
    public function assertNull($value, $message = '%s'): void
    {
        parent::assert(new TrueExpectation, !isset($value), $message);
    }

    /**
     * Identity test tests for the same object.
     *
     * @param $first   First object handle
     * @param $second  Hopefully the same handle
     * @param $message Message to display
     */
    public function assertSame(&$first, &$second, $message = '%s')
    {
        $dumper  = new SimpleDumper;
        $message = \sprintf(
            $message,
            '[' . $dumper->describeValue($first) .
                        '] and [' . $dumper->describeValue($second) .
                        '] should reference the same object',
        );

        return $this->assert(
            new TrueExpectation,
            SimpleTestCompatibility::isReference($first, $second),
            $message,
        );
    }

    /**
     * Inverted identity test.
     *
     * @param $first   First object handle
     * @param $second  Hopefully a different handle
     * @param $message Message to display
     */
    public function assertNotSame($first, $second, $message = '%s')
    {
        $dumper  = new SimpleDumper;
        $message = \sprintf(
            $message,
            '[' . $dumper->describeValue($first) .
                        '] and [' . $dumper->describeValue($second) .
                        '] should not be the same object',
        );

        return $this->assert(
            new falseExpectation,
            SimpleTestCompatibility::isReference($first, $second),
            $message,
        );
    }

    /**
     * Sends pass if the test condition resolves true, a fail otherwise.
     *
     * @param $condition Condition to test true
     * @param $message   Message to display
     */
    public function assertTrue($condition, $message = '%s'): void
    {
        parent::assert(new TrueExpectation, $condition, $message);
    }

    /**
     * Sends pass if the test condition resolves false, a fail otherwise.
     *
     * @param $condition Condition to test false
     * @param $message   Message to display
     */
    public function assertFalse($condition, $message = '%s'): void
    {
        parent::assert(new FalseExpectation, $condition, $message);
    }

    /**
     * Tests a regex match. Needs refactoring.
     *
     * @param $pattern Regex to match
     * @param $subject String to search in
     * @param $message Message to display
     */
    public function assertRegExp($pattern, $subject, $message = '%s'): void
    {
        $this->assert(new PatternExpectation($pattern), $subject, $message);
    }

    /**
     * Tests the type of a value.
     *
     * @param $value   Value to take type of
     * @param $type    Hoped for type
     * @param $message Message to display
     */
    public function assertType($value, $type, $message = '%s'): void
    {
        parent::assert(new TrueExpectation, \gettype($value) === \strtolower($type), $message);
    }

    /**
     * Sets equality operation to act as a simple equal comparison only,
     * allowing a broader range of matches.
     *
     * @param $loosely_typed True for broader comparison
     */
    public function setLooselyTyped($loosely_typed): void
    {
        $this->_loosely_typed = $loosely_typed;
    }

    /**
     * For progress indication during a test amongst other things.
     *
     * @return Usually one
     */
    public function countTestCases()
    {
        return $this->getSize();
    }

    /**
     * Accessor for name, normally just the class name.
     */
    public function getName()
    {
        return $this->getLabel();
    }

    /**
     * Does nothing. For compatibility only.
     *
     * @param $name Dummy
     */
    public function setName($name): void
    {
    }
}
