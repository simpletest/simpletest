<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'simple_unit.php');
    
    /**
     *    Repository for cookies.
     */
    class CookieJar {
        var $_cookies;
        
        /**
         *    Constructor. Jar starts empty.
         *    @public
         */
        function CookieJar() {
            $this->_cookies = array();
        }
        
        /**
         *    Adds a cookie to the jar.
         *    @param $cookie        New cookie.
         *    @public
         */
        function setCookie($cookie) {
            $this->_cookies[] = $cookie;
        }
        
        /**
         *    Fetches a hash of all valid cookies filtered
         *    by host, path and date and keyed by name
         *    Any cookies with missing categories will not
         *    be filtered out by that category.         
         *    @param $host        Host name requirement.
         *    @param $path        Path encompasing cookies.
         *    @param $date        Date to test expiries against,
         *                        either a timestamp or as a
         *                        cookie formatted date string.
         *    @return             Array of valid cookie objects.
         *    @public
         */
        function getValidCookies($host = false, $path = "/", $date = false) {
            $valid_cookies = array();
            foreach ($this->_cookies as $cookie) {
                if ($host && $cookie->getHost() && ($cookie->getHost() != $host)) {
                    continue;
                }
                if (!$this->_isSubpath($cookie->getPath(), $path)) {
                    continue;
                }
                if ($cookie->isExpired($date)) {
                    continue;
                }
                if (isset($valid_cookies[$cookie->getName()])) {
                    if (strlen($cookie->getPath()) < strlen($valid_cookies[$cookie->getName()]->getPath())) {
                        continue;
                    }
                }
                $valid_cookies[$cookie->getName()] = $cookie;
            }
            return $valid_cookies;
        }
        
        /**
         *    Tests to see if one path contains another.
         *    @param $subpath     Path nearer to the root.
         *    @param $path        Precise path.
         *    @private
         */
        function _isSubpath($subpath, $path) {
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
            return (strncmp($path, $subpath, strlen($subpath)) == 0);
        }
    }
    
    /**
     *    Fake web browser.
     */
    class TestBrowser {
        var $_test;
        var $_response;
        var $_expect_error;
        var $_cookie_jar;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @public
         */
        function TestBrowser(&$test) {
            $this->_test = &$test;
            $this->_response = false;
            $this->_expect_error = false;
            $this->_cookie_jar = new CookieJar();
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
            foreach ($this->_cookie_jar->getValidCookies() as $cookie) {
                $request->setCookie($cookie);
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
        function expectBadConnection() {
            $this->_expect_error = true;
        }
        
        /**
         *    Sets an additional cookie. If a cookie has
         *    the same name and path it is replaced.
         *    @param $cookie        Cookie object.
         *    @public
         */
        function setCookie($cookie) {
            $this->_cookie_jar->setCookie($cookie);
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