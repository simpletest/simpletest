<?php
    // $Id$

    Mock::generate('SimpleShell');
    
    class TestOfShellTestCase extends ShellTestCase {
        var $_mock_shell;
        
        function TestOfShellTestCase() {
            $this->ShellTestCase();
            $this->_mock_shell = false;
        }
        function &_getShell() {
            return $this->_mock_shell;
        }
        function testExitCode() {
            $this->_mock_shell = &new MockSimpleShell($this);
            $this->_mock_shell->setReturnValue('execute', 0);
            $this->_mock_shell->expectOnce('execute', array('ls'));
            $this->assertTrue($this->execute('ls'));
            $this->assertExitCode(0);
            $this->_mock_shell->tally();
        }
    }
?>