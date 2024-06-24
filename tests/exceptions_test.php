<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/exceptions.php';

require_once __DIR__ . '/../src/expectation.php';

require_once __DIR__ . '/../src/test_case.php';

Mock::generate('SimpleTestCase');
Mock::generate('SimpleExpectation');

class MyTestException extends Exception
{
}
class HigherTestException extends MyTestException
{
}
class OtherTestException extends Exception
{
}

class TestOfExceptionExpectation extends UnitTestCase
{
    public function testExceptionClassAsStringWillMatchExceptionsRootedOnThatClass(): void
    {
        $expectation = new ExceptionExpectation('MyTestException');
        $this->assertTrue($expectation->test(new MyTestException));
        $this->assertTrue($expectation->test(new HigherTestException));
        $this->assertFalse($expectation->test(new OtherTestException));
    }

    public function testMatchesClassAndMessageWhenExceptionExpected(): void
    {
        $expectation = new ExceptionExpectation(new MyTestException('Hello'));
        $this->assertTrue($expectation->test(new MyTestException('Hello')));
        $this->assertFalse($expectation->test(new HigherTestException('Hello')));
        $this->assertFalse($expectation->test(new OtherTestException('Hello')));
        $this->assertFalse($expectation->test(new MyTestException('Goodbye')));
        $this->assertFalse($expectation->test(new MyTestException));
    }

    public function testMessagelessExceptionMatchesOnlyOnClass(): void
    {
        $expectation = new ExceptionExpectation(new MyTestException);
        $this->assertTrue($expectation->test(new MyTestException));
        $this->assertFalse($expectation->test(new HigherTestException));
    }
}

class TestOfExceptionTrap extends UnitTestCase
{
    public function testNoExceptionsInQueueMeansNoTestMessages(): void
    {
        $test = new MockSimpleTestCase;
        $test->expectNever('assert');
        $queue = new SimpleExceptionTrap;
        $this->assertFalse($queue->isExpected($test, new Exception));
    }

    public function testMatchingExceptionGivesTrue(): void
    {
        $expectation = new MockSimpleExpectation;
        $expectation->returnsByValue('test', true);
        $test = new MockSimpleTestCase;
        $test->returnsByValue('assert', true);
        $queue = new SimpleExceptionTrap;
        $queue->expectException($expectation, 'message');
        $this->assertTrue($queue->isExpected($test, new Exception));
    }

    public function testMatchingExceptionTriggersAssertion(): void
    {
        $test = new MockSimpleTestCase;
        $test->expectOnce('assert', [
            '*',
            new ExceptionExpectation(new Exception),
            'message', ]);
        $queue = new SimpleExceptionTrap;
        $queue->expectException(new ExceptionExpectation(new Exception), 'message');
        $queue->isExpected($test, new Exception);
    }
}

class TestOfCatchingExceptions extends UnitTestCase
{
    public function testCanCatchAnyExpectedException(): void
    {
        $this->expectException();

        throw new Exception;
    }

    public function testCanMatchExceptionByClass(): void
    {
        $this->expectException('MyTestException');

        throw new HigherTestException;
    }

    public function testCanMatchExceptionExactly(): void
    {
        $this->expectException(new Exception('Ouch'));

        throw new Exception('Ouch');
    }

    public function testLastListedExceptionIsTheOneThatCounts(): void
    {
        $this->expectException('OtherTestException');
        $this->expectException('MyTestException');

        throw new HigherTestException;
    }
}

class TestOfIgnoringExceptions extends UnitTestCase
{
    public function testCanIgnoreAnyException(): void
    {
        $this->ignoreException();

        throw new Exception;
    }

    public function testCanIgnoreSpecificException(): void
    {
        $this->ignoreException('MyTestException');

        throw new MyTestException;
    }

    public function testCanIgnoreExceptionExactly(): void
    {
        $this->ignoreException(new Exception('Ouch'));

        throw new Exception('Ouch');
    }

    public function testIgnoredExceptionsDoNotMaskExpectedExceptions(): void
    {
        $this->ignoreException('Exception');
        $this->expectException('MyTestException');

        throw new MyTestException;
    }

    public function testCanIgnoreMultipleExceptions(): void
    {
        $this->ignoreException('MyTestException');
        $this->ignoreException('OtherTestException');

        throw new OtherTestException;
    }
}

class TestOfCallingTearDownAfterExceptions extends UnitTestCase
{
    private $debri = 0;

    protected function tearDown(): void
    {
        $this->debri--;
    }

    public function testLeaveSomeDebri(): void
    {
        $this->debri++;
        $this->expectException();

        throw new Exception(__FUNCTION__);
    }

    public function testDebriWasRemovedOnce(): void
    {
        $this->assertEqual($this->debri, 0);
    }
}

class TestOfExceptionThrownInSetUpDoesNotRunTestBody extends UnitTestCase
{
    protected function setUp(): void
    {
        $this->expectException();

        throw new Exception;
    }

    public function testShouldNotBeRun(): void
    {
        $this->fail('This test body should not be run');
    }

    public function testShouldNotBeRunEither(): void
    {
        $this->fail('This test body should not be run either');
    }
}

class TestOfExpectExceptionWithSetUp extends UnitTestCase
{
    protected function setUp(): void
    {
        $this->expectException();
    }

    public function testThisExceptionShouldBeCaught(): void
    {
        throw new Exception;
    }

    public function testJustThrowingMyTestException(): void
    {
        throw new MyTestException;
    }
}

class TestOfThrowingExceptionsInTearDown extends UnitTestCase
{
    protected function tearDown(): void
    {
        throw new Exception;
    }

    public function testDoesntFatal(): void
    {
        $this->expectException();
    }
}
