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
         *    @return             Hash of valid cookie objects keyed
         *                        on the cookie name.
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
     *    Fake web browser. Can be set up to automatically
     *    test reponses.
     */
    class TestBrowser {
        var $_test;
        var $_response;
        var $_expect_connection;
        var $_expected_response_codes;
        var $_expected_cookies;
        var $_cookie_jar;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @public
         */
        function TestBrowser(&$test) {
            $this->_test = &$test;
            $this->_response = false;
            $this->clearExpectations();
            $this->_cookie_jar = new CookieJar();
        }
        
        /**
         *    Resets all expectations.
         *    @public
         */
        function clearExpectations() {
            $this->_expect_connection = null;
            $this->_expected_response_codes = null;
            $this->_expected_mime_types = null;
            $this->_expected_cookies = array();
        }
        
        /**
         *    Fetches a URL performing the standard tests.
         *    @param $url        Target to fetch as string.
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
            $this->_checkExpectations($url, $this->_response);
            foreach ($this->_response->getNewCookies() as $cookie) {
                $this->setCookie($cookie);
            }
            return $this->_response->getContent();
        }
        
        /**
         *    Set the next fetch to expect a connection
         *    failure.
         *    @param $is_expected        True if failure wanted.
         *    @public
         */
        function expectConnection($is_expected = true) {
            $this->_expect_connection = $is_expected;
        }
        
        /**
         *    Sets the allowed response codes.
         *    @param $codes        Array of allowed codes.
         *    @public
         */
        function setExpectedResponseCodes($codes) {
            $this->_expected_response_codes = $codes;
        }
        
        /**
         *    Sets the allowed mime types and adds the
         *    necessary request headers.
         *    @param $types        Array of allowed types.
         *    @public
         */
        function setExpectedMimeTypes($types) {
            $this->_expected_mime_types = $types;
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
         *    Sets an expectation for a cookie.
         *    @param $name        Cookie key.
         *    @param $value       Expected value of incoming cookie.
         *    @public
         */
        function expectCookie($name, $value = false) {
            $this->_expected_cookies[] = array("name" => $name, "value" => $value);
        }
        
        /**
         *    Reads a cookie value from the browser cookies.
         *    @param $host        Host to search.
         *    @param $path        Applicable path.
         *    @param $name        Name of cookie to read.
         *    @return             Null if not present, else the
         *                        value as a string.
         *    @public
         */
        function getCookieValue($host, $path, $name) {
            $cookies = $this->_cookie_jar->getValidCookies();
            return $cookies[$name]->getValue();
        }
        
        /**
         *    Checks that the headers are as expected.
         *    Each expectation sends a test event.
         *    @param $url         Target URL.
         *    @param $reponse     HTTP response from the fetch.
         *    @private
         */
        function _checkExpectations($url, &$response) {
            if (isset($this->_expect_connection)) {
                $this->_assertTrue(
                        $response->isError() != $this->_expect_connection,
                        "Fetching $url with error [" . $response->getError() . "]");
            }
            if (isset($this->_expected_response_codes)) {
                $this->_assertTrue(
                        in_array($response->getResponseCode(), $this->_expected_response_codes),
                        "Fetching $url with response code [" . $response->getResponseCode() . "]");
            }
            if (isset($this->_expected_mime_types)) {
                $this->_assertTrue(
                        in_array($response->getMimeType(), $this->_expected_mime_types),
                        "Fetching $url with mime type [" . $response->getMimeType() . "]");
            }
            $cookies = $response->getNewCookies();
            foreach($this->_expected_cookies as $expectation) {
                $this->_checkExpectedCookie($expectation, $cookies);
            }
        }
        
        /**
         *    Checks that an expected cookie was present
         *    in the incoming cookie list.
         *    @param $expected    Expected cookie.
         *    @param $cookies     Incoming.
         *    @return             True if expectation met.
         */
        function _checkExpectedCookie($expected, $cookies) {
            foreach ($cookies as $cookie) {
                if ($cookie->getName() == $expected["name"]) {
                    $message = "Expected cookie " . $expected["name"] . " value " . $expected["value"] . " should be [" . $cookie->getValue() . "]";
                    $this->_assertTrue($cookie->getValue() == $expected["value"], $message);
                }
            }
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