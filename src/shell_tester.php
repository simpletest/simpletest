<?php declare(strict_types=1);

require_once __DIR__ . '/test_case.php';

/**
 * Wrapper for exec() functionality.
 */
class SimpleShell
{
    /** @var array|bool|mixed */
    private $output = false;

    /**
     * Executes the shell comand and stashes the output.
     */
    public function __construct()
    {
    }

    /**
     * Actually runs the command.
     *
     * @param string $command the actual command line to run
     *
     * @return int exec result_code
     */
    public function execute($command)
    {
        $this->output = false;
        \exec($command, $this->output, $result_code);

        return $result_code;
    }

    /**
     * Accessor for the last output.
     *
     * @return string output as text
     */
    public function getOutput()
    {
        return \implode("\n", $this->output);
    }

    /**
     * Accessor for the last output.
     *
     * @return array output as array of lines
     */
    public function getOutputAsList()
    {
        return $this->output;
    }
}

/**
 * Test case for testing of command line scripts and utilities.
 * Usually scripts that are external to the PHP code, but support it in some way.
 */
class ShellTestCase extends SimpleTestCase
{
    /** @var SimpleShell */
    private $current_shell;

    /** @var bool */
    private $last_status = false;

    /** @var string */
    private $last_command = '';

    /**
     * Creates an empty test case.
     * Should be subclassed with test methods for a functional test case.
     *
     * @param string $label Name of test case. Will use the class name if none specified.
     */
    public function __construct($label = false)
    {
        parent::__construct($label);
        $this->current_shell = $this->createShell();
    }

    /**
     * Executes a command and buffers the results.
     *
     * @param string $command command to run
     *
     * @return bool true if zero exit code
     */
    public function execute($command)
    {
        $shell              = $this->getShell();
        $this->last_status  = $shell->execute($command);
        $this->last_command = $command;

        return 0 === $this->last_status;
    }

    /**
     * Dumps the output of the last command.
     */
    public function dumpOutput(): void
    {
        $this->dump($this->getOutput());
    }

    /**
     * Accessor for the last output.
     *
     * @return string output as text
     */
    public function getOutput()
    {
        $shell = $this->getShell();

        return $shell->getOutput();
    }

    /**
     * Accessor for the last output.
     *
     * @return array output as array of lines
     */
    public function getOutputAsList()
    {
        $shell = $this->getShell();

        return $shell->getOutputAsList();
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
        return $this->assert(new FalseExpectation, $result, $message);
    }

    /**
     * Will trigger a pass if the two parameters have the same value only.
     * This is for testing hand extracted text, etc.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, Otherwise a fail
     */
    public function assertEqual($first, $second, $message = '%s')
    {
        return $this->assert(
            new EqualExpectation($first),
            $second,
            $message,
        );
    }

    /**
     * Will trigger a pass if the two parameters have a different value.
     * This is for testing hand extracted text, etc.
     *
     * @param mixed  $first   value to compare
     * @param mixed  $second  value to compare
     * @param string $message message to display
     *
     * @return bool true on pass, Otherwise a fail
     */
    public function assertNotEqual($first, $second, $message = '%s')
    {
        return $this->assert(
            new NotEqualExpectation($first),
            $second,
            $message,
        );
    }

    /**
     * Tests the last status code from the shell.
     *
     * @param int    $status  expected status of last command
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertExitCode($status, $message = '%s')
    {
        $errormsg = \sprintf(
            'Expected status code of [%s] from [%s], but got [%s]',
            $status,
            $this->last_command,
            $this->last_status,
        );

        $message = \sprintf($message, $errormsg);

        return $this->assertTrue($status === $this->last_status, $message);
    }

    /**
     * Attempt to exactly match the combined STDERR and STDOUT output.
     *
     * @param string $expected expected output
     * @param string $message  message to display
     *
     * @return bool true if pass
     */
    public function assertOutput($expected, $message = '%s')
    {
        $shell = $this->getShell();

        return $this->assert(
            new EqualExpectation($expected),
            $shell->getOutput(),
            $message,
        );
    }

    /**
     * Scans the output for a Perl regex. If found anywhere it passes, else it fails.
     *
     * @param string $pattern regex to search for
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertOutputPattern($pattern, $message = '%s')
    {
        $shell = $this->getShell();

        return $this->assert(
            new PatternExpectation($pattern),
            $shell->getOutput(),
            $message,
        );
    }

    /**
     * If a Perl regex is found anywhere in the current output
     * then a failure is generated, else a pass.
     *
     * @param string $pattern regex to search for
     * @param        $message message to display
     *
     * @return bool true if pass
     */
    public function assertNoOutputPattern($pattern, $message = '%s')
    {
        $shell = $this->getShell();

        return $this->assert(
            new NoPatternExpectation($pattern),
            $shell->getOutput(),
            $message,
        );
    }

    /**
     * File existence check.
     *
     * @param string $path    full filename and path
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertFileExists($path, $message = '%s')
    {
        $errormsg = \sprintf('File [%s] should exist', $path);

        $message = \sprintf($message, $errormsg);

        return $this->assertTrue(\file_exists($path), $message);
    }

    /**
     * File non-existence check.
     *
     * @param string $path    full filename and path
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertFileNotExists($path, $message = '%s')
    {
        $errormsg = \sprintf('File [%s] should not exist', $path);

        $message = \sprintf($message, $errormsg);

        return $this->assertFalse(\file_exists($path), $message);
    }

    /**
     * Scans a file for a Perl regex. If found anywhere it passes, else it fails.
     *
     * @param string $pattern regex to search for
     * @param string $path    full filename and path
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertFilePattern($pattern, $path, $message = '%s')
    {
        return $this->assert(
            new PatternExpectation($pattern),
            \implode('', \file($path)),
            $message,
        );
    }

    /**
     * If a Perl regex is found anywhere in the named file
     * then a failure is generated, else a pass.
     *
     * @param string $pattern regex to search for
     * @param string $path    full filename and path
     * @param string $message message to display
     *
     * @return bool true if pass
     */
    public function assertNoFilePattern($pattern, $path, $message = '%s')
    {
        return $this->assert(
            new NoPatternExpectation($pattern),
            \implode('', \file($path)),
            $message,
        );
    }

    /**
     * Accessor for current shell. Used for testing the the tester itself.
     *
     * @return SimpleShell current shell
     */
    protected function getShell()
    {
        return $this->current_shell;
    }

    /**
     * Factory for the shell to run the command on.
     *
     * @return SimpleShell new shell object
     */
    protected function createShell()
    {
        return new SimpleShell;
    }
}
