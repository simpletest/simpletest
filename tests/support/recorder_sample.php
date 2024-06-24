<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/autorun.php';

class SampleTestForRecorder extends UnitTestCase
{
    public function testTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }

    public function testFalseIsTrue(): void
    {
        $this->assertFalse(true);
    }
}
