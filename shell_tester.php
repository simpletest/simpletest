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
    
    /**
     *    Wrapper for exec() functionality.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class SimpleShell {
        var $_output;
        
        /**
         *    Executes the shell comand and stashes the output.
         *    @access public
         */
        function SimpleShell() {
            $this->_output = false;
        }
        
        /**
         *    Actually runs the command. Does not trap the
         *    error stream output as this need PHP 4.3+.
         *    @param string $command    The actual command line
         *                              to run.
         *    @return integer           Exit code.
         *    @access public
         */
        function execute($command) {
            exec($command, $this->_output, $ret);
            return $ret;
        }
        
        /**
         *    Accessor for the last output.
         *    @return string        Output as text.
         *    @access public
         */
        function getOutput() {
            return implode("\n", $this->_output);
        }
    }
    
    /**
     *    Test case for testing of command line scripts and
     *    utilities. Usually scripts taht are external to the
     *    PHP code, but support it in some way.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class ShellTestCase extends SimpleTestCase {
        var $_current_shell;
        var $_last_status;
        var $_last_command;
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param string $label     Name of test case. Will use
         *                             the class name if none specified.
         *    @access public
         */
        function ShellTestCase($label = false) {
            $this->SimpleTestCase($label);
            $this->_current_shell = &$this->_createShell();
            $this->_last_status = false;
            $this->_last_command = '';
        }
        
        /**
         *    Executes a command and buffers the results.
         *    @param string $command     Command to run.
         *    @return boolean            True if zero exit code.
         *    @access public
         */
        function execute($command) {
            $shell = &$this->_getShell();
            $this->_last_status = $shell->execute($command);
            $this->_last_command = $command;
            return ($this->_last_status === 0);
        }
        
        /**
         *    Tests the last status code from the shell.
         *    @param integer $status   Expected status of last
         *                             command.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertExitCode($status, $message = "%s") {
            $message = sprintf($message, "Expected status code of [$status] from [" .
                            $this->_last_command . "], but got [" .
                            $this->_last_status . "]");
            $this->assertTrue($status === $this->_last_status, $message);
        }
        
        /**
         *    Attempt to exactly match the combined STDERR and
         *    STDOUT output.
         *    @param string $expected  Expected output.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertOutput($expected, $message = "%s") {
            $shell = &$this->_getShell();
            $this->assertExpectation(
                    new EqualExpectation($expected),
                    $shell->getOutput(),
                    $message);
        }
        
        /**
         *    Scans the output for a Perl regex. If found
         *    anywhere it passes, else it fails.
         *    @param string $pattern    Regex to search for.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertOutputPattern($pattern, $message = "%s") {
            $shell = &$this->_getShell();
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    $shell->getOutput(),
                    $message);
        }
        
        /**
         *    If a Perl regex is found anywhere in the current
         *    output then a failure is generated, else a pass.
         *    @param string $pattern    Regex to search for.
         *    @param $message           Message to display.
         *    @access public
         */
        function assertNoOutputPattern($pattern, $message = "%s") {
            $shell = &$this->_getShell();
            $this->assertExpectation(
                    new UnwantedPatternExpectation($pattern),
                    $shell->getOutput(),
                    $message);
        }
        
        /**
         *    File existence check.
         *    @param string $path    Full filename and path.
         *    @param string $message Message to display.
         *    @access public
         */
        function assertFileExists($path, $message = "%s") {
            $message = sprintf($message, "File [$path] should exist");
            $this->assertTrue(file_exists($path), $message);
        }
        
        /**
         *    File non-existence check.
         *    @param string $path    Full filename and path.
         *    @param string $message Message to display.
         *    @access public
         */
        function assertFileNotExists($path, $message = "%s") {
            $message = sprintf($message, "File [$path] should not exist");
            $this->assertFalse(file_exists($path), $message);
        }
        
        /**
         *    Scans a file for a Perl regex. If found
         *    anywhere it passes, else it fails.
         *    @param string $pattern    Regex to search for.
         *    @param string $path       Full filename and path.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertFilePattern($pattern, $path, $message = "%s") {
            $shell = &$this->_getShell();
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    implode('', file($path)),
                    $message);
        }
        
        /**
         *    If a Perl regex is found anywhere in the named
         *    file then a failure is generated, else a pass.
         *    @param string $pattern    Regex to search for.
         *    @param string $path       Full filename and path.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertNoFilePattern($pattern, $path, $message = "%s") {
            $shell = &$this->_getShell();
            $this->assertExpectation(
                    new UnwantedPatternExpectation($pattern),
                    implode('', file($path)),
                    $message);
        }
        
        /**
         *    Accessor for current shell. Used for testing the
         *    the tester itself.
         *    @return Shell        Current shell.
         *    @access protected
         */
        function &_getShell() {
            return $this->_current_shell;
        }
        
        /**
         *    Factory for the shell to run the command on.
         *    @return Shell        New shell object.
         *    @access protected
         */
        function &_createShell() {
            return new SimpleShell();
        }
    }
?>