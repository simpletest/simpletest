<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/simpletest.php';

SimpleTest::ignore('ShouldNeverBeRunEither');

class ShouldNeverBeRun extends UnitTestCase
{
    public function testWithNoChanceOfSuccess(): void
    {
        $this->fail('Should be ignored');
    }
}

class ShouldNeverBeRunEither extends ShouldNeverBeRun
{
}

class TestOfStackTrace extends UnitTestCase
{
    public function testCanFindAssertInTrace(): void
    {
        $trace = new SimpleStackTrace(['assert']);
        $this->assertEqual(
            $trace->traceMethod([[
                'file'     => '/my_test.php',
                'line'     => 24,
                'function' => 'assertSomething', ]]),
            ' at [/my_test.php line 24]',
        );
    }
}

class DummyResource
{
}

class TestOfContext extends UnitTestCase
{
    public function testCurrentContextIsUnique(): void
    {
        $this->assertSame(
            SimpleTest::getContext(),
            SimpleTest::getContext(),
        );
    }

    public function testContextHoldsCurrentTestCase(): void
    {
        $context = SimpleTest::getContext();
        $this->assertSame($this, $context->getTest());
    }

    public function testResourceIsSingleInstanceWithContext(): void
    {
        $context = new SimpleTestContext;
        $this->assertSame(
            $context->get('DummyResource'),
            $context->get('DummyResource'),
        );
    }

    public function testClearingContextResetsResources(): void
    {
        $context  = new SimpleTestContext;
        $resource = $context->get('DummyResource');
        $context->clear();
        $this->assertClone($resource, $context->get('DummyResource'));
    }
}
