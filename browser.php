<?php
    // $Id$
    
    /**
     *    Fake web browser.
     */
    class TestBrowser {
        var $_test;
        var $_response;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @public
         */
        function TestBrowser(&$test) {
            $this->_test = &$test;
            $this->_response = false;
        }
        
        /**
         *    Fetches a URL performing the standard tests.
         *    @param $url        Target to fetch.
         *    @param $request    Test version of SimpleHttpRequest.
         *    @return            Content of page.
         *    @public
         */
        function fetchUrl($url, $request = "") {
            if (!is_object($request)) {
                $request = new SimpleHttpRequest($url);
            }
            $this->_response = &$request->fetch();
            $this->_assertTrue(
                    !$this->_response->isError(),
                    "Fetching [$url] error [" . $this->_response->getError() . "]");
            return $this->_response->getContent();
        }
        
        /**
         *    Sends an assertion to the held test case.
         *    @param $result        True on success.
         *    @param $message       Message to send to test.
         *    @protected
         */
        function _assertTrue($result, $message) {
            $this->_test->assertTrue($result, $message);
        }
    }
?>