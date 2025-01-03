<?php

require_once __DIR__.'/xml.php';
require_once __DIR__.'/shell_tester.php';

/**
 * Runs an XML formated test in a separate process.
 */
class DetachedTestCase
{
    /** @var string */
    private $command;
    /** @var string */
    private $dry_command;
    /** @var false|int */
    private $size;

    /**
     * Sets the location of the remote test.
     *
     * @param string $command test script
     * @param string $dry_command script for dry run
     */
    public function __construct($command, $dry_command = '')
    {
        $this->command = $command;
        $this->dry_command = empty($dry_command) ? $command : $dry_command;
        $this->size = false;
    }

    /**
     * Accessor for the test name for subclasses.
     *
     * @return string name of the test
     */
    public function getLabel()
    {
        return $this->command;
    }

    /**
     * Runs the top level test for this class.
     * Currently reads the data as a single chunk.
     * I'll fix this once I have added iteration to the browser.
     *
     * @param SimpleReporter $reporter target of test results
     *
     * @return bool True, if no failures.
     */
    public function run(&$reporter)
    {
        $shell = new SimpleShell();
        $shell->execute($this->command);
        $parser = $this->createParser($reporter);
        if (!$parser->parse($shell->getOutput())) {
            simpletest_trigger_error('Cannot parse incoming XML from ['.$this->command.']');

            return false;
        }

        return true;
    }

    /**
     * Accessor for the number of subtests.
     *
     * @return bool|int number of test cases
     */
    public function getSize()
    {
        if (false === $this->size) {
            $shell = new SimpleShell();
            $shell->execute($this->dry_command);
            $reporter = new SimpleReporter();
            $parser = $this->createParser($reporter);
            if (!$parser->parse($shell->getOutput())) {
                simpletest_trigger_error('Cannot parse incoming XML from ['.$this->dry_command.']');

                return false;
            }
            $this->size = $reporter->getTestCaseCount();
        }

        return $this->size;
    }

    /**
     * Creates the XML parser.
     *
     * @param SimpleReporter $reporter target of test results
     *
     * @return SimpleTestXmlParser XML reader
     */
    protected function createParser(&$reporter)
    {
        return new SimpleTestXmlParser($reporter);
    }
}
