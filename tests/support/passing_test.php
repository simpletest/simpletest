<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/autorun.php';

class PassingTest extends UnitTestCase
{
    public function test_pass(): void
    {
        $this->assertEqual(2, 2);
    }
}
