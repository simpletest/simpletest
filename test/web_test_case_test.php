<?php
    // $Id$
    
    Mock::generate("TestObserver");
    
    class TestOfWebEvents extends UnitTestCase {
        var $_observer;
        
        function TestOfWebEvents() {
            $this->UnitTestCase();
        }
        function setUp() {
            $this->_observer = &new MockTestObserver($this);
            $this->_observer = &new MockTestObserver($this);
            $this->_observer->expectArgumentsAt(0, "notify", array(new TestResult(true, "1")));
            $this->_observer->expectArgumentsAt(1, "notify", array(new TestResult(false, "2")));
            $this->_observer->expectCallCount("notify", 2);
        }
        function tearDown() {
            $this->_observer->tally();
        }
        function testSimpleEvents() {
            $test = &new WebTestCase();
            $test->attachObserver($this->_observer);
            $test->assertTrue(true, "1");
            $test->assertTrue(false, "2");
        }
        function testWantedPatterns() {
            $test = &new WebTestCase();
            $test->attachObserver($this->_observer);
            $test->assertWantedPattern('/hello/i', 'Hello world', '1');
            $test->assertWantedPattern('/hello/i', 'Goodbye world', '2');
        }
        function testUnwantedPatterns() {
            $test = &new WebTestCase();
            $test->attachObserver($this->_observer);
            $test->assertNoUnwantedPattern('/goodbye/i', 'Hello world', '1');
            $test->assertNoUnwantedPattern('/goodbye/i', 'Goodbye world', '2');
        }
    }
?>