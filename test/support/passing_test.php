<?php

require_once __DIR__.'/../../src/autorun.php';

class PassingTest extends UnitTestCase
{
    public function test_pass()
    {
        $this->assertEqual(2, 2);
    }
}
