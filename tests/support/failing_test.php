<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/autorun.php';

class FailingTest extends UnitTestCase
{
    public function test_fail(): void
    {
        $this->assertEqual(1, 2);
    }
}
