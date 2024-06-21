<?php declare(strict_types=1);

require_once __DIR__ . '/../src/autorun.php';

require_once __DIR__ . '/../src/errors.php';

require_once __DIR__ . '/../src/expectation.php';

require_once __DIR__ . '/../src/test_case.php';

Mock::generate('SimpleTestCase');
Mock::generate('SimpleExpectation');
SimpleTest::ignore('MockSimpleTestCase');

class TestOfErrorQueue extends UnitTestCase
{
    protected function setUp(): void
    {
        $context = SimpleTest::getContext();
        $queue   = $context->get('SimpleErrorQueue');
        $queue->clear();
    }

    protected function tearDown(): void
    {
        $context = SimpleTest::getContext();
        $queue   = $context->get('SimpleErrorQueue');
        $queue->clear();
    }

    public function testExpectationMatchCancelsIncomingError(): void
    {
        $test = new MockSimpleTestCase;
        $test->expectOnce('assert', [
            new IdenticalExpectation(new AnythingExpectation),
            'B',
            'a message', ]);
        $test->returnsByValue('assert', true);
        $test->expectNever('error');
        $queue = new SimpleErrorQueue;
        $queue->setTestCase($test);
        $queue->expectError(new AnythingExpectation, 'a message');
        $queue->add(1024, 'B', 'b.php', 100);
    }
}

class TestOfErrorTrap extends UnitTestCase
{
    private $old;

    protected function setUp(): void
    {
        $this->old = \error_reporting(E_ALL);
        \set_error_handler('SimpleTestErrorHandler');
    }

    protected function tearDown(): void
    {
        \restore_error_handler();
        \error_reporting($this->old);
    }

    public function testQueueStartsEmpty(): void
    {
        $context = SimpleTest::getContext();
        $queue   = $context->get('SimpleErrorQueue');
        $this->assertFalse($queue->extract());
    }

    public function testErrorsAreSwallowedByMatchingExpectation(): void
    {
        $this->expectError('Ouch!');
        \trigger_error('Ouch!');
    }

    public function testErrorsAreSwallowedInOrder(): void
    {
        $this->expectError('a');
        $this->expectError('b');
        \trigger_error('a');
        \trigger_error('b');
    }

    public function testAnyErrorCanBeSwallowed(): void
    {
        $this->expectError();
        \trigger_error('Ouch!');
    }

    public function testErrorCanBeSwallowedByPatternMatching(): void
    {
        $this->expectError(new PatternExpectation('/ouch/i'));
        \trigger_error('Ouch!');
    }

    public function testErrorWithPercentsPassesWithNoSprintfError(): void
    {
        $this->expectError('%');
        \trigger_error('%');
    }
}

class TestOfErrors extends UnitTestCase
{
    private $old;

    protected function setUp(): void
    {
        $this->old = \error_reporting(E_ALL);
    }

    protected function tearDown(): void
    {
        \error_reporting($this->old);
    }

    public function testDefaultWhenAllReported(): void
    {
        \error_reporting(E_ALL);
        $this->expectError('Ouch!');
        \trigger_error('Ouch!');
    }

    public function testNoticeWhenReported(): void
    {
        \error_reporting(E_ALL);
        $this->expectError('Ouch!');
        \trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testWarningWhenReported(): void
    {
        \error_reporting(E_ALL);
        $this->expectError('Ouch!');
        \trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testErrorWhenReported(): void
    {
        \error_reporting(E_ALL);
        $this->expectError('Ouch!');
        \trigger_error('Ouch!', E_USER_ERROR);
    }

    public function testNoNoticeWhenNotReported(): void
    {
        \error_reporting(0);
        \trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testNoWarningWhenNotReported(): void
    {
        \error_reporting(0);
        \trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testNoticeSuppressedWhenReported(): void
    {
        \error_reporting(E_ALL);
        @\trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testWarningSuppressedWhenReported(): void
    {
        \error_reporting(E_ALL);
        @\trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testErrorWithPercentsReportedWithNoSprintfError(): void
    {
        $this->expectError('%');
        \trigger_error('%');
    }
}

SimpleTest::ignore('TestOfNotEnoughErrors');

/**
 * This test is ignored as it is used by {@link TestRunnerForLeftOverAndNotEnoughErrors}
 * to verify that it fails as expected.
 *
 * @ignore
 */
class TestOfNotEnoughErrors extends UnitTestCase
{
    public function testExpectTwoErrorsThrowOne(): void
    {
        $this->expectError('Error 1');
        \trigger_error('Error 1');
        $this->expectError('Error 2');
    }
}

SimpleTest::ignore('TestOfLeftOverErrors');

/**
 * This test is ignored as it is used by {@link TestRunnerForLeftOverAndNotEnoughErrors}
 * to verify that it fails as expected.
 *
 * @ignore
 */
class TestOfLeftOverErrors extends UnitTestCase
{
    public function testExpectOneErrorGetTwo(): void
    {
        $this->expectError('Error 1');
        \trigger_error('Error 1');
        \trigger_error('Error 2');
    }
}

class TestRunnerForLeftOverAndNotEnoughErrors extends UnitTestCase
{
    public function testRunLeftOverErrorsTestCase(): void
    {
        $test = new TestOfLeftOverErrors;
        $this->assertFalse($test->run(new SimpleReporter));
    }

    public function testRunNotEnoughErrors(): void
    {
        $test = new TestOfNotEnoughErrors;
        $this->assertFalse($test->run(new SimpleReporter));
    }
}

// @todo add stacked error handler test
