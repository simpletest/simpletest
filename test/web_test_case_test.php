<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'observer.php');
    require_once(SIMPLE_TEST . 'browser.php');
    
    Mock::generate("TestObserver");
    Mock::generate("TestBrowser");
    
    class TestOfBrowserAccess extends UnitTestCase {
        function TestOfBrowserAccess() {
            $this->UnitTestCase();
        }
        function testBrowserOverride() {
            $browser = &new MockTestBrowser($this);
            $test = &new WebTestCase();
            $test->setBrowser($browser);
            $this->assertReference($browser, $test->getBrowser());
        }
    }
    
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