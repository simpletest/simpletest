<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "../");
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'http.php');
    Mock::generate("UnitTestCase");
    Mock::generate("SimpleHttpRequest");
    Mock::generate("SimpleHttpResponse");

    class TestOfBrowser extends UnitTestCase {
        function TestOfBrowser() {
            $this->UnitTestCase();
        }
        function testAssertionChaining() {
            $test = &new MockUnitTestCase($this);
            $test->setExpectedArgumentsSequence(0, "assertTrue", array(true, "Good"));
            $test->setExpectedArgumentsSequence(1, "assertTrue", array(false, "Bad"));
            $test->setExpectedCallCount("assertTrue", 2);
            $browser = &new TestBrowser($test);
            $browser->_assertTrue(true, "Good");
            $browser->_assertTrue(false, "Bad");
            $test->tally();
        }
        function testDetectingBadConnection() {
            $test = &new MockTestCase($this);
            $test->setExpectedArgumentsSequence(0, "assertTrue", array(false, '*'));
            $test->setExpectedCallCount("assertTrue", 1);
            $browser = &new TestBrowser($test);
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", true);
            $response->setReturnValue("getError", "Bad socket");
            $response->setReturnValue("getContent", false);
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            $this->assertIdentical(
                    $browser->fetchUrl("http://this.host/this/path/page.html", &$request),
                    false);
            $test->tally();
        }
    }
?>