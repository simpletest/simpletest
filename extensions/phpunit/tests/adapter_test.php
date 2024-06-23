<?php declare(strict_types=1);

require_once __DIR__ . '/../../../src/autorun.php';

require_once __DIR__ . '/../PHPUnitTestCase.php';

class SameTestClass
{
}

class TestOfPHPUnitAdapter extends PHPUnitTestCase
{
    public function testBoolean(): void
    {
        $this->assertTrue(true, 'PHPUnit true');
        $this->assertFalse(false, 'PHPUnit false');
    }

    public function testName(): void
    {
        $this->assertTrue($this->getName() === static::class);
    }

    public function testPass(): void
    {
        $this->pass('PHPUnit pass');
    }

    public function testNulls(): void
    {
        $value = null;
        $this->assertNull($value, 'PHPUnit null');
        $value = 0;
        $this->assertNotNull($value, 'PHPUnit not null');
    }

    public function testType(): void
    {
        $this->assertType('Hello', 'string', 'PHPUnit type');
    }

    public function testEquals(): void
    {
        $this->assertEquals(12, 12, 'PHPUnit identity');
        $this->setLooselyTyped(true);
        $this->assertEquals('12', 12, 'PHPUnit equality');
    }

    public function testSame(): void
    {
        $same = new SameTestClass;
        $this->assertSame($same, $same, 'PHPUnit same');
    }

    public function testRegExp(): void
    {
        $this->assertRegExp('/hello/', 'A big hello from me', 'PHPUnit regex');
    }
}
