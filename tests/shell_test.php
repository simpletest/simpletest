<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/shell_tester.php';

class TestOfShell extends UnitTestCase
{
    public function testEcho(): void
    {
        $shell = new SimpleShell;
        $this->assertIdentical($shell->execute('echo Hello'), 0);
        $this->assertPattern('/Hello/', $shell->getOutput());
    }

    public function testBadCommand(): void
    {
        $shell = new SimpleShell;
        $this->assertNotEqual($ret = $shell->execute('blurgh! 2>&1'), 0);
    }
}

class TestOfShellTesterAndShell extends ShellTestCase
{
    public function testEcho(): void
    {
        $this->assertTrue($this->execute('echo Hello'));
        $this->assertExitCode(0);
        $this->assertoutput('Hello');
    }

    public function testFileExistence(): void
    {
        $this->assertFileExists(__DIR__ . '/all_tests.php');
        $this->assertFileNotExists('wibble');
    }

    public function testFilePatterns(): void
    {
        $this->assertFilePattern('/all[_ ]tests/i', __DIR__ . '/all_tests.php');
        $this->assertNoFilePattern('/sputnik/i', __DIR__ . '/all_tests.php');
    }
}
