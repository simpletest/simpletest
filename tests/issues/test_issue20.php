<?php declare(strict_types=1);

require_once \dirname(__DIR__, 2) . '/src/autorun.php';

require_once \dirname(__DIR__, 2) . '/src/test_case.php';

require_once \dirname(__DIR__, 2) . '/src/simpletest.php';

class CurrentTestMethodTest extends SimpleTestCase
{
    private $seenInSetUp;

    protected function setUp(): void
    {
        // getCurrentTest() should be available during setUp()
        $this->seenInSetUp = $this->getCurrentTest();
    }

    public function skip(): void
    {
        // demonstrate that skip() can read the current test name to decide
        if ($this->getCurrentTest() === 'testWillBeSkipped') {
            $this->skipIf(true, 'Intentional skip in test suite');
        }
    }

    public function testSetUpSeesMethod(): void
    {
        $this->assert(new EqualExpectation('testSetUpSeesMethod'), $this->seenInSetUp, 'setUp saw current test method');
    }

    public function testWillBeSkipped(): void
    {
        // This will be skipped by skip(); if it runs, assert true.
        $this->assert(new TrueExpectation, true, 'This should not run');
    }
}
