<?php
    // $Id$
    
    class TestOfShell extends UnitTestCase {
        function TestOfShell() {
            $this->UnitTestCase();
        }
        function testEcho() {
            $shell = &new SimpleShell();
            $this->assertIdentical($shell->execute('echo Hello'), 0);
            $this->assertWantedPattern('/Hello/', $shell->getOutput());
        }
        function testBadCommand() {
            $shell = &new SimpleShell();
            $this->assertNotEqual($ret = $shell->execute('blurgh! 2>&1'), 0);
        }
    }
    
    class TestOfShellTesterAndShell extends ShellTestCase {
        function TestOfShellTesterAndShell() {
            $this->ShellTestCase();
        }
        function testEcho() {
            $this->assertTrue($this->execute('echo Hello'));
            $this->assertExitCode(0);
            $this->assertoutput('Hello');
        }
        function testFileExistence() {
            $this->assertFileExists(SIMPLE_TEST . 'test/all_tests.php');
            $this->assertFileNotExists('wibble');
        }
        function testFilePatterns() {
            $this->assertFilePattern(
                    '/simple_test/i',
                    SIMPLE_TEST . 'test/all_tests.php');
            $this->assertNoFilePattern(
                    '/sputnik/i',
                    SIMPLE_TEST . 'test/all_tests.php');
        }
    }
?>