<?php declare(strict_types=1);

require_once __DIR__ . '/scorer.php';
// require_once __DIR__ . '/arguments.php';

/**
 * Sample minimal test displayer.
 * Generates only failure messages and a pass count.
 */
class HtmlReporter extends SimpleReporter
{
    /** @var string */
    private $charset;

    /**
     * Does nothing yet.
     * The first output will be sent on the first test start.
     * For use by a web browser.
     *
     * @param string $charset
     */
    public function __construct($charset = 'utf-8')
    {
        parent::__construct();
        $this->charset = $charset;
    }

    /**
     * Send the headers necessary to ensure the page is reloaded on every request.
     * Otherwise you could be scratching your head over out of date test data.
     */
    public function sendNoCacheHeaders(): void
    {
        if (!\headers_sent()) {
            \header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            \header('Last-Modified: ' . \gmdate('D, d M Y H:i:s') . ' GMT');
            \header('Cache-Control: no-store, no-cache, must-revalidate');
            \header('Cache-Control: post-check=0, pre-check=0', false);
            \header('Pragma: no-cache');
        }
    }

    /**
     * Paints the top of the web page setting the title to the name of the starting test.
     *
     * @param string $test_name name class of test
     */
    public function paintHeader($test_name): void
    {
        $this->sendNoCacheHeaders();
        print '<!DOCTYPE html>';
        print "<html>\n<head>\n<title>{$test_name}</title>\n";
        print '<meta http-equiv="Content-Type" content="text/html; charset=' . $this->charset . "\">\n";
        print "<style type=\"text/css\">\n";
        print $this->getCss() . "\n";
        print "</style>\n";
        print "</head>\n<body>\n";
        print "<h1>{$test_name}</h1>\n";
        \flush();
    }

    /**
     * Paints the end of the test with a summary of the passes and failures.
     *
     * @param string $test_name name class of test
     */
    public function paintFooter($test_name): void
    {
        $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? 'red' : 'green');
        print '<div style="';
        print "padding: 8px; margin-top: 1em; background-color: {$colour}; color: white;";
        print '">';
        print $this->getTestCaseProgress() . '/' . $this->getTestCaseCount();
        print " test cases complete:\n";
        print '<strong>' . $this->getPassCount() . '</strong> passes, ';
        print '<strong>' . $this->getFailCount() . '</strong> fails and ';
        print '<strong>' . $this->getExceptionCount() . '</strong> exceptions.';
        print "</div>\n";
        print "</body>\n</html>\n";
    }

    /**
     * Paints the test failure with a breadcrumbs trail
     * of the nesting test suites below the top level test.
     *
     * @param string $message failure message displayed in the context of the other tests
     */
    public function paintFail($message): void
    {
        parent::paintFail($message);
        print '<span class="fail">Fail</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        print ' -&gt; ' . $this->htmlEntities($message) . "<br />\n";
    }

    /**
     * Paints a PHP error.
     *
     * @param string $message message is ignored
     */
    public function paintError($message): void
    {
        parent::paintError($message);
        print '<span class="fail">Exception</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        print ' -&gt; <strong>' . $this->htmlEntities($message) . "</strong><br />\n";
    }

    /**
     * Paints a PHP exception.
     *
     * @param Exception $exception exception to display
     */
    public function paintException($exception): void
    {
        parent::paintException($exception);
        print '<span class="fail">Exception</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        $exceptionClass = $exception::class;
        $message        = 'Unexpected exception of type [' . $exceptionClass .
                '] with message [' . $exception->getMessage() .
                '] in [' . $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
        print ' -&gt; <strong>' . $this->htmlEntities($message) . "</strong><br />\n";
    }

    /**
     * Prints the message for skipping tests.
     *
     * @param string $message text of skip condition
     */
    public function paintSkip($message): void
    {
        parent::paintSkip($message);
        print '<span class="pass">Skipped</span>: ';
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print \implode(' -&gt; ', $breadcrumb);
        print ' -&gt; ' . $this->htmlEntities($message) . "<br />\n";
    }

    /**
     * Paints formatted text such as dumped privateiables.
     *
     * @param string $message text to show
     */
    public function paintFormattedMessage($message): void
    {
        print '<pre>' . $this->htmlEntities($message) . '</pre>';
    }

    /**
     * Paints the CSS. Add additional styles here.
     *
     * @return string CSS code as text
     */
    protected function getCss()
    {
        $fail = ' .fail { background-color: inherit; color: red; }';
        $pass = ' .pass { background-color: inherit; color: green; }';
        $pre  = ' pre { background-color: lightgray; color: inherit; }';

        return $fail . $pass . $pre;
    }

    /**
     * Character set adjusted entity conversion.
     *
     * @param string $message plain text or Unicode message
     *
     * @return string browser readable message
     */
    protected function htmlEntities($message)
    {
        return \htmlentities($message, ENT_COMPAT, $this->charset);
    }
}

/**
 * Sample minimal test displayer.
 * Generates only failure messages and a pass count.
 * For command line use.
 *
 * Note: I've tried to make it look like JUnit,
 *       but I wanted to output the errors as they arrived, which meant dropping the dots.
 */
class TextReporter extends SimpleReporter
{
    /**
     * Does nothing yet. The first output will be sent on the first test start.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Paints the title only.
     *
     * @param string $test_name name class of test
     */
    public function paintHeader($test_name): void
    {
        if (!SimpleReporter::inCli()) {
            \header('Content-type: text/plain');
        }
        print "{$test_name}\n";
        \flush();
    }

    /**
     * Paints the end of the test with a summary of the passes and failures.
     *
     * @param string $test_name name class of test
     */
    public function paintFooter($test_name): void
    {
        if (0 === $this->getFailCount() + $this->getExceptionCount()) {
            print "OK\n";
        } else {
            print "FAILURES!!!\n";
        }
        print 'Test cases run: ' . $this->getTestCaseProgress() .
                '/' . $this->getTestCaseCount() .
                ', Passes: ' . $this->getPassCount() .
                ', Failures: ' . $this->getFailCount() .
                ', Exceptions: ' . $this->getExceptionCount() . "\n";
    }

    /**
     * Paints the test failure as a stack trace.
     *
     * @param string $message failure message displayed in the context of the other tests
     */
    public function paintFail($message): void
    {
        parent::paintFail($message);
        print $this->getFailCount() . ") {$message}\n";
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print "\tin " . \implode("\n\tin ", \array_reverse($breadcrumb));
        print "\n";
    }

    /**
     * Paints a PHP error or exception.
     *
     * @param string $message message to be shown
     */
    public function paintError($message): void
    {
        parent::paintError($message);
        print 'Exception ' . $this->getExceptionCount() . "!\n{$message}\n";
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print "\tin " . \implode("\n\tin ", \array_reverse($breadcrumb));
        print "\n";
    }

    /**
     * Paints a PHP error or exception.
     *
     * @param Exception $exception exception to describe
     */
    public function paintException($exception): void
    {
        parent::paintException($exception);
        $exceptionClass = $exception::class;
        $message        = 'Unexpected exception of type [' . $exceptionClass .
                '] with message [' . $exception->getMessage() .
                '] in [' . $exception->getFile() .
                ' line ' . $exception->getLine() . ']';
        print 'Exception ' . $this->getExceptionCount() . "!\n{$message}\n";
        $breadcrumb = $this->getTestList();
        \array_shift($breadcrumb);
        print "\tin " . \implode("\n\tin ", \array_reverse($breadcrumb));
        print "\n";
    }

    /**
     * Prints the message for skipping tests.
     *
     * @param string $message text of skip condition
     */
    public function paintSkip($message): void
    {
        parent::paintSkip($message);
        print "Skip: {$message}\n";
    }

    /**
     * Paints formatted text such as dumped privateiables.
     *
     * @param string $message text to show
     */
    public function paintFormattedMessage($message): void
    {
        print "{$message}\n";
        \flush();
    }
}

/**
 * Runs just a single test group, a single case or even a single test within that case.
 */
class SelectiveReporter extends SimpleReporterDecorator
{
    /** @var bool|string */
    private $just_this_case = false;

    /** @var bool|string */
    private $just_this_test = false;

    /** @var bool */
    private $on;

    /**
     * Selects the test case or group to be run, and optionally a specific test.
     *
     * @param SimpleReporter $reporter       Reporter to receive events
     * @param bool|string    $just_this_case only this case or group will run
     * @param bool|string    $just_this_test only this test method will run
     */
    public function __construct($reporter, $just_this_case = false, $just_this_test = false)
    {
        if (isset($just_this_case) && $just_this_case) {
            $this->just_this_case = \strtolower($just_this_case);
            $this->off();
        } else {
            $this->on();
        }

        if (isset($just_this_test) && $just_this_test) {
            $this->just_this_test = \strtolower($just_this_test);
        }
        parent::__construct($reporter);
    }

    /**
     * Veto everything that doesn't match the method wanted.
     *
     * @param string $test_case name of test case
     * @param string $method    name of test method
     *
     * @return bool true if test should be run
     */
    public function shouldInvoke($test_case, $method)
    {
        if ($this->shouldRunTest($test_case, $method)) {
            return $this->reporter->shouldInvoke($test_case, $method);
        }

        return false;
    }

    /**
     * Paints the start of a group test.
     *
     * @param string $test_case name of test or other label
     * @param int    $size      number of test cases starting
     */
    public function paintGroupStart($test_case, $size): void
    {
        if ($this->just_this_case && $this->matchesTestCase($test_case)) {
            $this->on();
        }
        $this->reporter->paintGroupStart($test_case, $size);
    }

    /**
     * Paints the end of a group test.
     *
     * @param string $test_case name of test or other label
     */
    public function paintGroupEnd($test_case): void
    {
        $this->reporter->paintGroupEnd($test_case);

        if ($this->just_this_case && $this->matchesTestCase($test_case)) {
            $this->off();
        }
    }

    /**
     * Compares criteria to actual the case/group name.
     *
     * @param string $test_case the incoming test
     *
     * @return bool true if matched
     */
    protected function matchesTestCase($test_case)
    {
        return $this->just_this_case == \strtolower($test_case);
    }

    /**
     * Compares criteria to actual the test name.
     * If no name was specified at the beginning, then all tests can run.
     *
     * @param string $test_case
     * @param string $method    the incoming test method
     *
     * @return bool true if matched
     */
    protected function shouldRunTest($test_case, $method)
    {
        if ($this->isOn() || $this->matchesTestCase($test_case)) {
            if ($this->just_this_test) {
                return $this->just_this_test == \strtolower($method);
            }

            return true;

        }

        return false;
    }

    /**
     * Switch on testing for the group or subgroup.
     */
    protected function on(): void
    {
        $this->on = true;
    }

    /**
     * Switch off testing for the group or subgroup.
     */
    protected function off(): void
    {
        $this->on = false;
    }

    /**
     * Is this group actually being tested?
     *
     * @return bool true if the current test group is active
     */
    protected function isOn()
    {
        return $this->on;
    }
}

/**
 * Suppresses skip messages.
 */
class NoSkipsReporter extends SimpleReporterDecorator
{
    /**
     * Does nothing.
     *
     * @param string $message text of skip condition
     */
    public function paintSkip($message): void
    {
    }
}
