<?php

require_once __DIR__.'/../src/autorun.php';
require_once __DIR__.'/../src/errors.php';
require_once __DIR__.'/../src/expectation.php';
require_once __DIR__.'/../src/test_case.php';

Mock::generate('SimpleTestCase');
Mock::generate('SimpleExpectation');
SimpleTest::ignore('MockSimpleTestCase');

class TestOfErrorQueue extends UnitTestCase
{
    public function setUp()
    {
        $context = SimpleTest::getContext();
        $queue = $context->get('SimpleErrorQueue');
        $queue->clear();
    }

    public function tearDown()
    {
        $context = SimpleTest::getContext();
        $queue = $context->get('SimpleErrorQueue');
        $queue->clear();
    }

    public function testExpectationMatchCancelsIncomingError()
    {
        $test = new MockSimpleTestCase();
        $test->expectOnce('assert', [
                new IdenticalExpectation(new AnythingExpectation()),
                'B',
                'a message', ]);
        $test->returnsByValue('assert', true);
        $test->expectNever('error');
        $queue = new SimpleErrorQueue();
        $queue->setTestCase($test);
        $queue->expectError(new AnythingExpectation(), 'a message');
        $queue->add(1024, 'B', 'b.php', 100);
    }
}

class TestOfErrorTrap extends UnitTestCase
{
    private $old;

    public function setUp()
    {
        $this->old = error_reporting(E_ALL);
        set_error_handler('SimpleTestErrorHandler');
    }

    public function tearDown()
    {
        restore_error_handler();
        error_reporting($this->old);
    }

    public function testQueueStartsEmpty()
    {
        $context = SimpleTest::getContext();
        $queue = $context->get('SimpleErrorQueue');
        $this->assertFalse($queue->extract());
    }

    public function testErrorsAreSwallowedByMatchingExpectation()
    {
        $this->expectError('Ouch!');
        simpletest_trigger_error('Ouch!');
    }

    public function testErrorsAreSwallowedInOrder()
    {
        $this->expectError('a');
        $this->expectError('b');
        simpletest_trigger_error('a');
        simpletest_trigger_error('b');
    }

    public function testAnyErrorCanBeSwallowed()
    {
        $this->expectError();
        simpletest_trigger_error('Ouch!');
    }

    public function testErrorCanBeSwallowedByPatternMatching()
    {
        $this->expectError(new PatternExpectation('/ouch/i'));
        simpletest_trigger_error('Ouch!');
    }

    public function testErrorWithPercentsPassesWithNoSprintfError()
    {
        $this->expectError('%');
        simpletest_trigger_error('%');
    }
}

class TestOfErrors extends UnitTestCase
{
    private $old;

    public function setUp()
    {
        $this->old = error_reporting(E_ALL);
    }

    public function tearDown()
    {
        error_reporting($this->old);
    }

    public function testDefaultWhenAllReported()
    {
        error_reporting(E_ALL);
        $this->expectError('Ouch!');
        simpletest_trigger_error('Ouch!');
    }

    public function testNoticeWhenReported()
    {
        error_reporting(E_ALL);
        $this->expectError('Ouch!');
        simpletest_trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testWarningWhenReported()
    {
        error_reporting(E_ALL);
        $this->expectError('Ouch!');
        simpletest_trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testErrorWhenReported()
    {
        error_reporting(E_ALL);
        if (PHP_VERSION_ID < 80400) {
            $this->expectError('Ouch!');
        } else {
            $this->expectException(ErrorException::class, 'Ouch!');
        }
        simpletest_trigger_error('Ouch!', E_USER_ERROR);
    }

    public function testNoNoticeWhenNotReported()
    {
        error_reporting(0);
        simpletest_trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testNoWarningWhenNotReported()
    {
        error_reporting(0);
        simpletest_trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testNoticeSuppressedWhenReported()
    {
        error_reporting(E_ALL);
        @simpletest_trigger_error('Ouch!', E_USER_NOTICE);
    }

    public function testWarningSuppressedWhenReported()
    {
        error_reporting(E_ALL);
        @trigger_error('Ouch!', E_USER_WARNING);
    }

    public function testErrorWithPercentsReportedWithNoSprintfError()
    {
        $this->expectError('%');
        simpletest_trigger_error('%');
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
    public function testExpectTwoErrorsThrowOne()
    {
        $this->expectError('Error 1');
        simpletest_trigger_error('Error 1');
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
    public function testExpectOneErrorGetTwo()
    {
        $this->expectError('Error 1');
        simpletest_trigger_error('Error 1');
        simpletest_trigger_error('Error 2');
    }
}

class TestRunnerForLeftOverAndNotEnoughErrors extends UnitTestCase
{
    public function testRunLeftOverErrorsTestCase()
    {
        $test = new TestOfLeftOverErrors();
        $this->assertFalse($test->run(new SimpleReporter()));
    }

    public function testRunNotEnoughErrors()
    {
        $test = new TestOfNotEnoughErrors();
        $this->assertFalse($test->run(new SimpleReporter()));
    }
}

// @todo add stacked error handler test
