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
            $this->assertFalse($queue->extract());
        }
        function testOrder() {
            $queue = &SimpleErrorQueue::instance();
            $queue->add(1024, 'Ouch', 'here.php', 100, array());
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
?>