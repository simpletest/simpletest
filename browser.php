<?php
    // $Id$
    
    /**
     *    Fake web browser.
     */
    class TestBrowser {
        var $_test;
        var $_response;
        var $_expect_error;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @public
         */
        function TestBrowser(&$test) {
            $this->_test = &$test;
            $this->_response = false;
            $this->_expect_error = false;
        }
        
        /**
         *    Fetches a URL performing the standard tests.
         *    @param $url        Target to fetch.
         *    @param $request    Test version of SimpleHttpRequest.
         *    @return            Content of page.
         *    @public
         */
        function fetchUrl($url, $request = false) {
            if (!is_object($request)) {
                $request = new SimpleHttpRequest($url);
            }
            $this->_response = &$request->fetch();
            $this->_checkConnection($url, $this->_response);
            return $this->_response->getContent();
        }
        
        /**
         *    Set the next fetch to expect a connection
         *    failure.
         *    @public
         */
        function expectFail() {
            $this->_expect_error = true;
        }
        
        /**
         *    Checks that the connection is as expected.
         *    If correct then a test event is sent.
         *    @param $url         Target URL.
         *    @param $reponse     HTTP response from the fetch.
         *    @private
         */
        function _checkConnection($url, &$response) {
            $this->_assertTrue(
                    $response->isError() == $this->_expect_error,
                    "Fetching $url with error [" . $response->getError() . "]");
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