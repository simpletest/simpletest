<?php declare(strict_types=1);

class test1 extends UnitTestCase
{
    public function test_pass(): void
    {
        $this->assertEqual(3, 1 + 2, 'pass1');
    }
}
