<?php

require_once __DIR__.'/../../src/autorun.php';

class SampleTestForRecorder extends UnitTestCase
{
    public function testTrueIsTrue()
    {
        $this->assertTrue(true);
    }

    public function testFalseIsTrue()
    {
        $this->assertFalse(true);
    }
}
