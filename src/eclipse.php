<?php declare(strict_types=1);

require_once 'unit_tester.php';

require_once 'test_case.php';

require_once 'invoker.php';

require_once 'socket.php';

require_once 'mock_objects.php';

/**
 * base reported class for eclipse plugin.
 */
class EclipseReporter extends SimpleScorer
{
    /**
     * C style escaping.
     *
     * @param string $raw string with backslashes, quotes and whitespace
     *
     * @return string replaced with C backslashed tokens
     */
    public static function escapeVal($raw)
    {
        $needle  = ['\\', '"', '/', "\b", "\f", "\n", "\r", "\t"];
        $replace = ['\\\\', '\"', '\/', '\b', '\f', '\n', '\r', '\t'];

        return \str_replace($needle, $replace, $raw);
    }

    /**
     * Reporter to be run inside of Eclipse interface.
     *
     * @param object $listener eclipse listener (?)
     * @param bool   $cc       whether to include test coverage
     */
    public function __construct(&$listener, $cc = false)
    {
        $this->listener = $listener;
        parent::__construct();
        $this->case   = '';
        $this->group  = '';
        $this->method = '';
        $this->cc     = $cc;
        $this->error  = false;
        $this->fail   = false;
    }

    /**
     * Means to display human readable object comparisons.
     *
     * @return SimpleDumper visual comparer
     */
    public function getDumper()
    {
        return new SimpleDumper;
    }

    /**
     * Localhost connection from Eclipse.
     *
     * @param int    $port port to connect to Eclipse
     * @param string $host normally localhost
     *
     * @return SimpleSocket connection to Eclipse
     */
    public function createListener($port, $host = '127.0.0.1')
    {
        return new SimpleSocket($host, $port, 5);
    }

    /**
     * Wraps the test in an output buffer.
     *
     * @param SimpleInvoker $invoker current test runner
     *
     * @return EclipseInvoker decorator with output buffering
     */
    public function createInvoker(&$invoker)
    {
        $eclinvoker = new EclipseInvoker($invoker, $this->listener);

        return $eclinvoker;
    }

    /**
     * Stash the first passing item. Clicking the test item goes to first pass.
     *
     * @param string $message test message, but we only wnat the first
     */
    public function paintPass($message): void
    {
        if (!$this->pass) {
            $this->message = self::escapeVal($message);
        }
        $this->pass = true;
    }

    /**
     * Stash the first failing item. Clicking the test item goes to first fail.
     *
     * @param string $message test message, but we only wnat the first
     */
    public function paintFail($message): void
    {
        // only get the first failure or error
        if (!$this->fail && !$this->error) {
            $this->fail    = true;
            $this->message = self::escapeVal($message);
            $this->listener->write(
                '{status:"fail",message:"' . $this->message . '",group:"' . $this->group .
                '",case:"' . $this->case . '",method:"' . $this->method . '"}',
            );
        }
    }

    /**
     * Stash the first error. Clicking the test item goes to first error.
     *
     * @param string $message test message, but we only wnat the first
     */
    public function paintError($message): void
    {
        if (!$this->fail && !$this->error) {
            $this->error   = true;
            $this->message = self::escapeVal($message);
            $this->listener->write(
                '{status:"error",message:"' . $this->message . '",group:"' . $this->group .
                '",case:"' . $this->case . '",method:"' . $this->method . '"}',
            );
        }
    }

    /**
     * Stash the first exception. Clicking the test item goes to first message.
     */
    public function paintException($exception): void
    {
        if (!$this->fail && !$this->error) {
            $this->error = true;
            $message     = 'Unexpected exception of type[' . $exception::class .
                    '] with message [' . $exception->getMessage() . '] in [' .
                    $exception->getFile() . ' line ' . $exception->getLine() . ']';
            $this->message = self::escapeVal($message);
            $this->listener->write(
                '{status:"error",message:"' . $this->message . '",group:"' . $this->group .
                    '",case:"' . $this->case . '",method:"' . $this->method . '"}',
            );
        }
    }

    /**
     * We don't display any special header.
     *
     * @param string $test_name first test top level to start
     */
    public function paintHeader($test_name): void
    {
    }

    /**
     * We don't display any special footer.
     *
     * @param string $test_name the top level test
     */
    public function paintFooter($test_name): void
    {
    }

    /**
     * Paints nothing at the start of a test method, but stash the method name for later.
     */
    public function paintMethodStart($method): void
    {
        $this->pass   = false;
        $this->fail   = false;
        $this->error  = false;
        $this->method = self::escapeVal($method);
    }

    /**
     * Only send one message if the test passes, after that suppress the message.
     */
    public function paintMethodEnd($method): void
    {
        if ($this->fail || $this->error || !$this->pass) {
        } else {
            $this->listener->write(
                '{status:"pass",message:"' . $this->message . '",group:"' .
                        $this->group . '",case:"' . $this->case . '",method:"' .
                        $this->method . '"}',
            );
        }
    }

    /**
     * Stashes the test case name for the later failure message.
     */
    public function paintCaseStart($case): void
    {
        $this->case = self::escapeVal($case);
    }

    /**
     * Drops the name.
     */
    public function paintCaseEnd($case): void
    {
        $this->case = '';
    }

    /**
     * Stashes the name of the test suite. Starts test coverage if enabled.
     *
     * @param string $group name of test or other label
     * @param int    $size  number of test cases starting
     */
    public function paintGroupStart($group, $size): void
    {
        $this->group = self::escapeVal($group);

        if ($this->cc) {
            if (\extension_loaded('xdebug')) {
                xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
            }
        }
    }

    /**
     * Paints coverage report if enabled.
     *
     * @param string $group name of test or other label
     */
    public function paintGroupEnd($group): void
    {
        $this->group = '';
        $cc          = '';

        if ($this->cc) {
            if (\extension_loaded('xdebug')) {
                $arrfiles = xdebug_get_code_coverage();
                xdebug_stop_code_coverage();
                $thisdir    = __DIR__;
                $thisdirlen = \strlen($thisdir);

                foreach ($arrfiles as $index => $file) {
                    if (\substr($index, 0, $thisdirlen) === $thisdir) {
                        continue;
                    }
                    $lcnt = 0;
                    $ccnt = 0;

                    foreach ($file as $line) {
                        if (-2 == $line) {
                            continue;
                        }
                        $lcnt++;

                        if (1 == $line) {
                            $ccnt++;
                        }
                    }

                    if ($lcnt > 0) {
                        $cc .= \round(($ccnt / $lcnt) * 100, 2) . '%';
                    } else {
                        $cc .= '0.00%';
                    }
                    $cc .= "\t" . $index . "\n";
                }
            }
        }

        $this->listener->write(
            '{status:"coverage",message:"' . self::escapeVal($cc) . '"}',
        );
    }
}

/**
 * Invoker decorator for Eclipse. Captures output until the end of the test.
 */
class EclipseInvoker extends SimpleInvokerDecorator
{
    public function __construct(&$invoker, &$listener)
    {
        $this->listener = $listener;
        parent::__construct($invoker);
    }

    /**
     * Starts output buffering.
     *
     * @param string $method test method to call
     */
    public function before($method): void
    {
        \ob_start();
        $this->invoker->before($method);
    }

    /**
     * Stops output buffering and send the captured output to the listener.
     *
     * @param string $method test method to call
     */
    public function after($method): void
    {
        $this->invoker->after($method);
        $output = \ob_get_contents();
        \ob_end_clean();

        if ('' !== $output) {
            $result = $this->listener->write(
                '{status:"info",message:"' . EclipseReporter::escapeVal($output) . '"}',
            );
        }
    }
}
