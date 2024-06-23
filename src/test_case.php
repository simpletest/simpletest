<?php declare(strict_types=1);

require_once __DIR__ . '/invoker.php';

require_once __DIR__ . '/errors.php';

require_once __DIR__ . '/compatibility.php';

require_once __DIR__ . '/scorer.php';

require_once __DIR__ . '/expectation.php';

require_once __DIR__ . '/dumper.php';

require_once __DIR__ . '/simpletest.php';

require_once __DIR__ . '/exceptions.php';

require_once __DIR__ . '/reflection.php';

// define root constant for dependent libraries
if (!\defined('SIMPLE_TEST')) {
    \define('SIMPLE_TEST', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Basic test case. This is the smallest unit of a test suite.
 * It searches for all methods that start with the the string "test" and runs them.
 * Working test cases extend this class.
 */
class SimpleTestCase
{
    protected $reporter;
    private $label = false;
    private $observers;
    private $should_skip = false;

    /**
     * Sets up the test with no display.
     *
     * @param string $label if no test name is given then the class name is used
     */
    public function __construct($label = false)
    {
        if ($label) {
            $this->label = $label;
        }
    }

    /**
     * Sets up unit test wide variables at the start of each test method.
     * To be overridden in actual user test cases.
     */
    protected function setUp(): void
    {
    }

    /**
     * Clears the data set in the setUp() method call.
     * To be overridden by the user in actual user test cases.
     */
    protected function tearDown(): void
    {
    }

    /**
     * Accessor for the test name for subclasses.
     *
     * @return string name of the test
     */
    public function getLabel()
    {
        return $this->label ?: static::class;
    }

    /**
     * This is a placeholder for skipping tests.
     * In this method you place skipIf() and skipUnless() calls to set the skipping state.
     */
    public function skip(): void
    {
    }

    /**
     * Will issue a message to the reporter and tell the test case to skip if the incoming flag is true.
     *
     * @param string $should_skip condition causing the tests to be skipped
     * @param string $message     text of skip condition
     */
    public function skipIf($should_skip, $message = '%s'): void
    {
        if ($should_skip && !$this->should_skip) {
            $this->should_skip = true;
            $message           = \sprintf($message, 'Skipping [' . static::class . ']');
            $this->reporter->paintSkip($message . $this->getAssertionLine());
        }
    }

    /**
     * Accessor for the private variable $_shoud_skip.
     */
    public function shouldSkip()
    {
        return $this->should_skip;
    }

    /**
     * Will issue a message to the reporter and tell the test case to skip if the incoming flag is false.
     *
     * @param string $shouldnt_skip condition causing the tests to be run
     * @param string $message       text of skip condition
     */
    public function skipUnless($shouldnt_skip, $message = false): void
    {
        $this->skipIf(!$shouldnt_skip, $message);
    }

    /**
     * Used to invoke the single tests.
     *
     * @return SimpleInvoker individual test runner
     */
    public function createInvoker()
    {
        return new SimpleErrorTrappingInvoker(
            new SimpleExceptionTrappingInvoker(new SimpleInvoker($this)),
        );
    }

    /**
     * Uses reflection to run every method within itself starting
     * with the string "test" unless a method is specified.
     *
     * @param SimpleReporter $reporter current test reporter
     *
     * @return bool true if all tests passed
     */
    public function run($reporter)
    {
        $context = SimpleTest::getContext();
        $context->setTest($this);
        $context->setReporter($reporter);
        $this->reporter = $reporter;
        $started        = false;

        foreach ($this->getTests() as $method) {
            if ($reporter->shouldInvoke($this->getLabel(), $method)) {
                $this->skip();

                if ($this->should_skip) {
                    break;
                }

                if (!$started) {
                    $reporter->paintCaseStart($this->getLabel());
                    $started = true;
                }
                $invoker = $this->reporter->createInvoker($this->createInvoker());
                $invoker->before($method);
                $invoker->invoke($method);
                $invoker->after($method);
            }
        }

        if ($started) {
            $reporter->paintCaseEnd($this->getLabel());
        }
        $this->reporter = null;
        $context->setTest(null);

        return $reporter->getStatus();
    }

    /**
     * Gets a list of test names. Normally that will be all internal methods that start with the
     * name "test". This method should be overridden if you want a different rule.
     *
     * @return array list of test names
     */
    public function getTests()
    {
        $methods = [];

        foreach (\get_class_methods(static::class) as $method) {
            if ($this->isTest($method)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * Announces the start of the test.
     *
     * @param string $method test method just started
     */
    public function before($method): void
    {
        $this->reporter->paintMethodStart($method);
        $this->observers = [];
    }

    /**
     * Announces the end of the test. Includes private clean up.
     *
     * @param string $method test method just finished
     */
    public function after($method): void
    {
        foreach ($this->observers as $observer) {
            $observer->atTestEnd($method, $this);
        }
        $this->reporter->paintMethodEnd($method);
    }

    /**
     * Sets up an observer for the test end.
     *
     * @param object $observer must have atTestEnd() method
     */
    public function tell($observer): void
    {
        $this->observers[] = $observer;
    }

    /**
     * @deprecated
     */
    public function pass($message = 'Pass')
    {
        if ($this->reporter === null) {
            \trigger_error('Can only make assertions within test methods');
        }
        $this->reporter->paintPass($message . $this->getAssertionLine());

        return true;
    }

    /**
     * Sends a fail event with a message.
     *
     * @param string $message message to send
     */
    public function fail($message = 'Fail')
    {
        if ($this->reporter === null) {
            \trigger_error('Can only make assertions within test methods');
        }
        $this->reporter->paintFail($message . $this->getAssertionLine());

        return false;
    }

    /**
     * Formats a PHP error and dispatches it to the reporter.
     *
     * @param int    $severity PHP error code
     * @param string $message  text of error
     * @param string $file     file error occoured in
     * @param int    $line     line number of error
     */
    public function error($severity, $message, $file, $line): void
    {
        if ($this->reporter === null) {
            \trigger_error('Can only make assertions within test methods');
        }
        $this->reporter->paintError("Unexpected PHP Error [{$message}] severity [{$severity}] in [{$file} line {$line}]");
    }

    /**
     * Formats an exception and dispatches it to the reporter.
     *
     * @param Exception $exception object thrown
     */
    public function exception($exception): void
    {
        $this->reporter->paintException($exception);
    }

    /**
     * For user defined expansion of the available messages.
     *
     * @param string $type    tag for sorting the signals
     * @param mixed  $payload extra user specific information
     */
    public function signal($type, $payload): void
    {
        if ($this->reporter === null) {
            \trigger_error('Can only make assertions within test methods');
        }
        $this->reporter->paintSignal($type, $payload);
    }

    /**
     * Runs an expectation directly, for extending the tests with new expectation classes.
     *
     * @param SimpleExpectation $expectation expectation subclass
     * @param mixed             $compare     value to compare
     * @param string            $message     message to display
     *
     * @return bool True on pass
     */
    public function assert($expectation, $compare, $message = '%s')
    {
        $message = $this->escapeIncidentalPrintfSyntax($message);

        if ($expectation->test($compare)) {
            return $this->pass(
                \sprintf($message, $expectation->overlayMessage($compare, $this->reporter->getDumper())),
            );
        }

        return $this->fail(
            \sprintf($message, $expectation->overlayMessage($compare, $this->reporter->getDumper())),
        );

    }

    /**
     * Uses a stack trace to find the line of an assertion.
     *
     * @return string line number of first assert method embedded in format string
     */
    public function getAssertionLine()
    {
        $trace = new SimpleStackTrace(['assert', 'expect', 'pass', 'fail', 'skip']);

        return $trace->traceMethod();
    }

    /**
     * Sends a formatted dump of a variable to the test suite for those emergency debugging situations.
     *
     * @param mixed       $variable variable to display
     * @param bool|string $message  message to display
     *
     * @return mixed the original variable
     */
    public function dump($variable, $message = false)
    {
        $dumper    = $this->reporter->getDumper();
        $formatted = $dumper->dump($variable);

        if ($message) {
            $formatted = $message . "\n" . $formatted;
        }
        $this->reporter->paintFormattedMessage($formatted);

        return $variable;
    }

    /**
     * Accessor for the number of subtests including myelf.
     *
     * @return int number of test cases
     */
    public function getSize()
    {
        return 1;
    }

    /**
     * Tests to see if the method is a test that should be run.
     * Currently any method that starts with 'test' is a candidate unless it is the constructor.
     *
     * @param string $method method name to try
     *
     * @return bool true if test method
     */
    protected function isTest($method)
    {
        if ('test' === \strtolower(\substr($method, 0, 4))) {
            return !\is_a($this, \strtolower($method));
        }

        return false;
    }

    /**
     * Escapes all percentage signs so *printf functions don't do any special processing...
     * ... except for the first one if it forms %s, which is used to reference the auto-generated assert message.
     *
     * Get the position of the first percentage sign.
     * Skip over escaping, if none is found.
     * Else escape part of string after first percentage sign.
     * Then concat unescaped first part and escaped part.
     *
     * @param string $string
     *
     * @return string
     */
    protected function escapeIncidentalPrintfSyntax($string)
    {
        $pos = \strpos($string, '%s');

        if ($pos !== false) {
            return \substr($string, 0, $pos + 2) . \str_replace('%', '%%', \substr($string, $pos + 2));
        }

        return \str_replace('%', '%%', $string);
    }
}

/**
 * Helps to extract test cases automatically from a file.
 */
class SimpleFileLoader
{
    /**
     * Builds a test suite from a library of test cases.
     * The new suite is composed into this one.
     *
     * @param string $test_file file name of library with test case classes
     *
     * @return BadTestSuite|TestSuite the new test suite
     */
    public function load($test_file)
    {
        $existing_classes = \get_declared_classes();
        $existing_globals = \get_defined_vars();

        include_once $test_file;
        $new_globals = \get_defined_vars();
        $this->makeFileVariablesGlobal($existing_globals, $new_globals);
        $new_classes = \array_diff(\get_declared_classes(), $existing_classes);

        if ($new_classes === []) {
            $new_classes = $this->scrapeClassesFromFile($test_file);
        }
        $classes = $this->selectRunnableTests($new_classes);

        return $this->createSuiteFromClasses($test_file, $classes);
    }

    /**
     * Calculates the incoming test cases. Skips abstract and ignored classes.
     *
     * @param array $candidates candidate classes
     *
     * @return array new classes which are test cases that shouldn't be ignored
     */
    public function selectRunnableTests($candidates)
    {
        $classes = [];

        foreach ($candidates as $class) {
            if (TestSuite::getBaseTestCase($class)) {
                $reflection = new SimpleReflection($class);

                if ($reflection->isAbstract()) {
                    SimpleTest::ignore($class);
                } else {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }

    /**
     * Builds a test suite from a class list.
     *
     * @param string $title   title of new group
     * @param array  $classes test classes
     *
     * @return BadTestSuite|TestSuite group loaded with the new test cases
     */
    public function createSuiteFromClasses($title, $classes)
    {
        if (0 == \count($classes)) {
            return new BadTestSuite($title, "No runnable test cases in [{$title}]");
        }
        SimpleTest::ignoreParentsIfIgnored($classes);
        $suite = new TestSuite($title);

        foreach ($classes as $class) {
            if (!SimpleTest::isIgnored($class)) {
                $suite->add($class);
            }
        }

        return $suite;
    }

    /**
     * Imports new variables into the global namespace.
     *
     * @param array $existing variables before the file was loaded
     * @param array $new      variables after the file was loaded
     */
    protected function makeFileVariablesGlobal($existing, $new): void
    {
        $globals = \array_diff(\array_keys($new), \array_keys($existing));

        foreach ($globals as $global) {
            $GLOBALS[$global] = $new[$global];
        }
    }

    /**
     * Lookup classnames from file contents, in case the file may have been included before.
     *
     * Note: This is probably too clever by half.
     *       Figuring this  out after a failed test case is going to be tricky for us,
     *       never mind the user. A test case should not be included twice anyway.
     *
     * @param string $file test File name with classes
     *
     * @return string classnames
     */
    protected function scrapeClassesFromFile($file)
    {
        $content = \file_get_contents($file);
        \preg_match_all('~^\s*class\s+(\w+)(\s+(extends|implements)\s+\w+)*\s*\{~mi', $content, $matches);

        return $matches[1];
    }
}

/**
 * This is a composite test class for combining
 * test cases and other RunnableTest classes into a group test.
 */
class TestSuite
{
    /** @var false|string */
    private $label = '';

    /** @var array */
    private $test_cases = [];

    /**
     * Test to see if a class is derived from the SimpleTestCase class.
     *
     * @param string $class class name
     *
     * @return bool|mixed|SimpleTestCase|TestSuite
     */
    public static function getBaseTestCase($class)
    {
        while ($class = \get_parent_class($class)) {
            $class = \strtolower($class);

            if ('simpletestcase' === $class || 'testsuite' === $class) {
                return $class;
            }
        }

        return false;
    }

    /**
     * Sets the name of the test suite.
     *
     * @param string $label name sent at the start and end of the test
     */
    public function __construct($label = false)
    {
        $this->label = $label;
    }

    /**
     * Accessor for the test name for subclasses. If the suite
     * wraps a single test case the label defaults to the name of that test.
     *
     * @return string name of the test
     */
    public function getLabel()
    {
        if (!$this->label) {
            return (1 === $this->getSize()) ?
                    \get_class($this->test_cases[0]) : static::class;
        }

        return $this->label;

    }

    /**
     * Adds a test into the suite by instance or class.
     * The class will be instantiated if it's a test suite.
     *
     * @param SimpleTestCase $test_case suite or individual test
     *                                  case implementing the runnable test interface
     */
    public function add($test_case): void
    {
        if (!\is_string($test_case)) {
            $this->test_cases[] = $test_case;
        } elseif ('testsuite' === self::getBaseTestCase($test_case)) {
            $this->test_cases[] = new $test_case;
        } else {
            $this->test_cases[] = $test_case;
        }
    }

    /**
     * Builds a test suite from a library of test cases.
     * The new suite is composed into this one.
     *
     * @param string $test_file file name of library with test case classes
     */
    public function addFile($test_file): void
    {
        $extractor = new SimpleFileLoader;
        $this->add($extractor->load($test_file));
    }

    /**
     * Delegates to a visiting collector to add test files.
     *
     * @param string          $path      path to scan from
     * @param SimpleCollector $collector directory scanner
     */
    public function collect($path, $collector): void
    {
        $collector->collect($this, $path);
    }

    /**
     * Invokes run() on all of the held test cases, instantiating them if necessary.
     *
     * @param SimpleReporter $reporter current test reporter
     *
     * @return bool
     */
    public function run($reporter)
    {
        $reporter->paintGroupStart($this->getLabel(), $this->getSize());

        for ($i = 0, $count = \count($this->test_cases); $i < $count; $i++) {
            if (\is_string($this->test_cases[$i])) {
                $class = $this->test_cases[$i];
                $test  = new $class;
                $test->run($reporter);
                unset($test);
            } else {
                $this->test_cases[$i]->run($reporter);
            }
        }
        $reporter->paintGroupEnd($this->getLabel());

        return $reporter->getStatus();
    }

    /**
     * Number of contained test cases.
     *
     * @return int total count of cases in the group
     */
    public function getSize()
    {
        $count = 0;

        foreach ($this->test_cases as $case) {
            if (\is_string($case)) {
                if (!SimpleTest::isIgnored($case)) {
                    $count++;
                }
            } else {
                $count += $case->getSize();
            }
        }

        return $count;
    }
}

/**
 * This is a failing group test for when a test suite hasn't loaded properly.
 */
class BadTestSuite
{
    /** @var string */
    private $label;

    /** @var mixed */
    private $error;

    /**
     *  Sets the name of the test suite and error message.
     *
     * @param string $label name sent at the start and end of the test
     * @param string $error
     */
    public function __construct($label, $error)
    {
        $this->label = $label;
        $this->error = $error;
    }

    /**
     * Accessor for the test name for subclasses.
     *
     * @return string name of the test
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sends a single error to the reporter.
     *
     * @param SimpleReporter $reporter current test reporter
     */
    public function run($reporter)
    {
        $label = $this->getLabel();

        $msg_tpl = 'Bad TestSuite [%s] with error [%s]';
        $message = \sprintf($msg_tpl, $label, $this->error);

        $reporter->paintGroupStart($label, $this->getSize());
        $reporter->paintFail($message);
        $reporter->paintGroupEnd($label);

        return $reporter->getStatus();
    }

    /**
     * Number of contained test cases. Always zero.
     *
     * @return int total count of cases in the group
     */
    public function getSize()
    {
        return 0;
    }
}
