<?php

require_once __DIR__.'/errors.php';
require_once __DIR__.'/compatibility.php';
require_once __DIR__.'/scorer.php';
require_once __DIR__.'/expectation.php';
require_once __DIR__.'/dumper.php';

// define the root constant for dependent libraries.
if (!defined('SIMPLE_TEST')) {
    define('SIMPLE_TEST', __DIR__.'/');
}

/**
 * This is called by the class runner to run a single test method.
 * Will also run the setUp() and tearDown() methods.
 */
class SimpleInvoker
{
    /** @var SimpleTestCase */
    private $test_case;

    /**
     * Stashes the test case for later.
     *
     * @param SimpleTestCase $test_case test case to run
     */
    public function __construct($test_case)
    {
        $this->test_case = $test_case;
    }

    /**
     * Accessor for test case being run.
     *
     * @return SimpleTestCase test case
     */
    public function getTestCase()
    {
        return $this->test_case;
    }

    /**
     * Runs test level set up. Used for changing the mechanics of base test cases.
     *
     * @param string $method test method to call
     *
     * @return void
     */
    public function before($method)
    {
        $this->test_case->before($method);
    }

    /**
     * Invokes a test method and buffered with setUp() and tearDown() calls.
     *
     * @param string $method test method to call
     *
     * @return void
     */
    public function invoke($method)
    {
        //$this->test_case->setUp();
        $r_setUp = new ReflectionMethod($this->test_case::class, 'setUp');
        $r_setUp->setAccessible(true);
        $r_setUp->invoke($this->test_case);

        $this->test_case->$method();

        //$this->test_case->tearDown();
        $r_tearDown = new ReflectionMethod($this->test_case::class, 'tearDown');
        $r_tearDown->setAccessible(true);
        $r_tearDown->invoke($this->test_case);
    }

    /**
     * Runs test level clean up. Used for changing the mechanics of base test cases.
     *
     * @param string $method test method to call
     *
     * @return void
     */
    public function after($method)
    {
        $this->test_case->after($method);
    }
}

/**
 * Do nothing decorator. Just passes the invocation straight through.
 */
class SimpleInvokerDecorator
{
    private $invoker;

    /**
     * Stores the invoker to wrap.
     *
     * @param SimpleInvoker $invoker test method runner
     */
    public function __construct($invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * Accessor for test case being run.
     *
     * @return SimpleTestCase test case
     */
    public function getTestCase()
    {
        return $this->invoker->getTestCase();
    }

    /**
     * Runs test level set up. Used for changing the mechanics of base test cases.
     *
     * @param string $method test method to call
     */
    public function before($method)
    {
        $this->invoker->before($method);
    }

    /**
     * Invokes a test method and buffered with setUp() and tearDown() calls.
     *
     * @param string $method test method to call
     */
    public function invoke($method)
    {
        $this->invoker->invoke($method);
    }

    /**
     * Runs test level clean up. Used for changing the mechanics of base test cases.
     *
     * @param string $method test method to call
     */
    public function after($method)
    {
        $this->invoker->after($method);
    }
}
