<?php
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'errors.php');
    
    class TestOfErrorQueue extends UnitTestCase {
        function TestOfErrorQueue() {
            $this->UnitTestCase();
        }
        function setUp() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }
        function tearDown() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }
        function testSingleton() {
            $this->assertReference(
                    SimpleErrorQueue::instance(),
                    SimpleErrorQueue::instance());
            $this->assertIsA(SimpleErrorQueue::instance(), 'SimpleErrorQueue');
        }
        function testEmpty() {
            $queue = &SimpleErrorQueue::instance();
            $this->assertTrue($queue->isEmpty());
            $this->assertFalse($queue->extract());
        }
        function testOrder() {
            $queue = &SimpleErrorQueue::instance();
            $queue->add(1024, 'Ouch', 'here.php', 100, array());
            $this->assertFalse($queue->isEmpty());
            $queue->add(512, 'Yuk', 'there.php', 101, array());
            $this->assertEqual(
                    $queue->extract(),
                    array(1024, 'Ouch', 'here.php', 100, array()));
            $this->assertEqual(
                    $queue->extract(),
                    array(512, 'Yuk', 'there.php', 101, array()));
            $this->assertFalse($queue->extract());
        }
    }
    
    class TestOfErrorTrap extends UnitTestCase {
        function TestOfErrorTrap() {
            $this->UnitTestCase();
        }
        function setUp() {
            set_error_handler('simpleTestErrorHandler');
        }
        function tearDown() {
            restore_error_handler();
        }
        function testTrapping() {
            $queue = &SimpleErrorQueue::instance();
            $this->assertFalse($queue->extract());
            trigger_error('Ouch!');
            list($severity, $message, $file, $line, $globals) = $queue->extract();
            $this->assertEqual($message, 'Ouch!');
            $this->assertEqual($file, __FILE__);
            $this->assertFalse($queue->extract());
        }
    }
?>