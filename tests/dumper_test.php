<?php

require_once __DIR__.'/../src/autorun.php';

class DumperDummy
{
}

class TestOfTextFormatting extends UnitTestCase
{
    public function testClipping()
    {
        $dumper = new SimpleDumper();
        $this->assertEqual($dumper->clipString('Hello', 6), 'Hello', 'Hello, 6->%s');
        $this->assertEqual($dumper->clipString('Hello', 5), 'Hello', 'Hello, 5->%s');
        $this->assertEqual($dumper->clipString('Hello world', 3), 'Hel...', 'Hello world, 3->%s');
        $this->assertEqual($dumper->clipString('Hello world', 6, 3), 'Hello ...', 'Hello world, 6, 3->%s');
        $this->assertEqual($dumper->clipString('Hello world', 3, 6), '...o w...', 'Hello world, 3, 6->%s');
        $this->assertEqual($dumper->clipString('Hello world', 4, 11), '...orld', 'Hello world, 4, 11->%s');
        $this->assertEqual($dumper->clipString('Hello world', 4, 12), '...orld', 'Hello world, 4, 12->%s');
        $this->assertEqual(
            $dumper->clipString('Seine Majestät, der König von Zamunda', 29),
            'Seine Majestät, der König von...',
            'Seine Majestät, der König von Zamunda, 29, 29->%s'
        );
        $this->assertEqual(
            $dumper->clipString('Seine Majestet, der Konig von Zamunda', 29),
            'Seine Majestet, der Konig von...',
            'Seine Majestat, der Konig von Zamunda, 29, 29->%s'
        );
    }

    public function testDescribeNull()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/null/i', $dumper->describeValue(null));
    }

    public function testDescribeBoolean()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/bool/i', $dumper->describeValue(true));
        $this->assertPattern('/true/i', $dumper->describeValue(true));
        $this->assertPattern('/false/i', $dumper->describeValue(false));
    }

    public function testDescribeString()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/string/i', $dumper->describeValue('Hello'));
        $this->assertPattern('/Hello/', $dumper->describeValue('Hello'));
    }

    public function testDescribeInteger()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/integer/i', $dumper->describeValue(35));
        $this->assertPattern('/35/', $dumper->describeValue(35));
    }

    public function testDescribeFloat()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/float/i', $dumper->describeValue(0.99));
        $this->assertPattern('/0\.99/', $dumper->describeValue(0.99));
    }

    public function testDescribeArray()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern('/array/i', $dumper->describeValue([1, 4]));
        $this->assertPattern('/2/i', $dumper->describeValue([1, 4]));
    }

    public function testDescribeObject()
    {
        $dumper = new SimpleDumper();
        $this->assertPattern(
            '/object/i',
            $dumper->describeValue(new DumperDummy())
        );
        $this->assertPattern(
            '/DumperDummy/i',
            $dumper->describeValue(new DumperDummy())
        );
    }
}
