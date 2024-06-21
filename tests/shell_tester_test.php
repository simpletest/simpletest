<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/shell_tester.php';

Mock::generate('SimpleShell');

class TestOfShellTestCase extends ShellTestCase
{
    private $mock_shell = false;

    public function getShell()
    {
        return $this->mock_shell;
    }

    public function testGenericEquality(): void
    {
        $this->assertEqual('a', 'a');
        $this->assertNotEqual('a', 'A');
    }

    public function testExitCode(): void
    {
        $this->mock_shell = new MockSimpleShell;
        $this->mock_shell->returnsByValue('execute', 0);
        $this->mock_shell->expectOnce('execute', ['ls']);
        $this->assertTrue($this->execute('ls'));
        $this->assertExitCode(0);
    }

    public function testOutput(): void
    {
        $this->mock_shell = new MockSimpleShell;
        $this->mock_shell->returnsByValue('execute', 0);
        $this->mock_shell->returnsByValue('getOutput', "Line 1\nLine 2\n");
        $this->assertOutput("Line 1\nLine 2\n");
    }

    public function testOutputPatterns(): void
    {
        $this->mock_shell = new MockSimpleShell;
        $this->mock_shell->returnsByValue('execute', 0);
        $this->mock_shell->returnsByValue('getOutput', "Line 1\nLine 2\n");
        $this->assertOutputPattern('/line/i');
        $this->assertNoOutputPattern('/line 2/');
    }
}
