<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'simple_unit.php');
    
    /**
     *    Repository for cookies. The semantics are a bit
     *    ropy until I can go through the cookie spec with
     *    a fine tooth combe.
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
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened.
         *    @param $date        Time when session restarted.
         *                        If ommitted then all persistent
         *                        cookies are kept. Time is either
         *                        Cookie format string or timestamp.
         *    @public
         */
        function restartSession($date = false) {
            $surviving = array();
            for ($i = 0; $i < count($this->_cookies); $i++) {
                if (!$this->_cookies[$i]->getValue()) {
                    continue;
                }
                if (!$this->_cookies[$i]->getExpiry()) {
                    continue;
                }
                if ($date && $this->_cookies[$i]->isExpired($date)) {
                    continue;
                }
                $surviving[] = $this->_cookies[$i];
            }
            $this->_cookies = $surviving;
        }
        
        /**
         *    Adds a cookie to the jar. This will overwrite
         *    cookies with matching host, paths and keys.
         *    @param $cookie        New cookie.
         *    @public
         */
        function setCookie($cookie) {
            for ($i = 0; $i < count($this->_cookies); $i++) {
                $is_match = $this->_isMatch(
                        $cookie,
                        $this->_cookies[$i]->getHost(),
                        $this->_cookies[$i]->getPath(),
                        $this->_cookies[$i]->getName());
                if ($is_match) {
                    $this->_cookies[$i] = $cookie;
                    return;
                }
            }
            $this->_cookies[] = $cookie;
        }
        
        /**
         *    Fetches a hash of all valid cookies filtered
         *    by host, path and keyed by name
         *    Any cookies with missing categories will not
         *    be filtered out by that category. Expired
         *    cookies must be cleared by restarting the session.
         *    @param $host        Host name requirement.
         *    @param $path        Path encompassing cookies.
         *    @return             Hash of valid cookie objects keyed
         *                        on the cookie name.
         *    @public
         */
        function getValidCookies($host = false, $path = "/") {
            $valid_cookies = array();
            foreach ($this->_cookies as $cookie) {
                if ($this->_isMatch($cookie, $host, $path, $cookie->getName())) {
                    $valid_cookies[] = $cookie;
                }
            }
            return $valid_cookies;
        }
        
        /**
         *    Tests cookie for matching against search
         *    criteria.
         *    @param $cookie        Cookie to test.
         *    @param $host          Host must match.
         *    @param $path          Cookie path must be shorter than
         *                          this path.
         *    @param $name          Name must match.
         *    @return               True if matched.
         *    @private
         */
        function _isMatch($cookie, $host, $path, $name) {
            if ($cookie->getName() != $name) {
                return false;
            }
            if ($host && $cookie->getHost() && !$cookie->isValidHost($host)) {
                return false;
            }
            if (!$cookie->isValidPath($path)) {
                return false;
            }
            return true;
        }
    }
    
    /**
     *    Simulated web browser.
     */
    class SimpleBrowser {
        var $_cookie_jar;
        var $_response;
        var $_base_url;
        
        /**
         *    Starts with a fresh browser with no
         *    cookie or any other state information.
         *    @public
         */
        function SimpleBrowser() {
            $this->_cookie_jar = new CookieJar();
            $this->_response = false;
            $this->_base_url = false;
        }
        
        /**
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened.
         *    @param $date        Time when session restarted.
         *                        If ommitted then all persistent
         *                        cookies are kept.
         *    @public
         */
        function restartSession($date = false) {
            $this->_cookie_jar->restartSession($date);
        }
        
        /**
         *    Sets an additional cookie. If a cookie has
         *    the same name and path it is replaced.
         *    @param $name            Cookie key.
         *    @param $value           Value of cookie.
         *    @param $host            Host upon which the cookie is valid.
         *    @param $path            Cookie path if not host wide.
         *    @param $expiry          Expiry date as string.
         *    @public
         */
        function setCookie($name, $value, $host = false, $path = "/", $expiry = false) {
            $cookie = new SimpleCookie($name, $value, $path, $expiry);
            if ($host) {
                $cookie->setHost($host);
            }
            $this->_cookie_jar->setCookie($cookie);
        }
        
        /**
         *    Reads the most specific cookie value from the
         *    browser cookies.
         *    @param $host        Host to search.
         *    @param $path        Applicable path.
         *    @param $name        Name of cookie to read.
         *    @return             False if not present, else the
         *                        value as a string.
         *    @public
         */
        function getCookieValue($host, $path, $name) {
            $longest_path = "";
            foreach ($this->_cookie_jar->getValidCookies($host, $path) as $cookie) {
                if ($name == $cookie->getName()) {
                    if (strlen($cookie->getPath()) > strlen($longest_path)) {
                        $value = $cookie->getValue();
                        $longest_path = $cookie->getPath();
                    }
                }
            }
            return (isset($value) ? $value : false);
        }
        
        /**
         *    Reads the current cookies for the base URL.
         *    @param $name   Key of cookie to find.
         *    @return        Null if there is no base URL, false
         *                   if the cookie is not set.
         *    @public
         */
        function getBaseCookieValue($name) {
            if (!$this->_base_url) {
                return null;
            }
            $url = new SimpleUrl($this->_base_url);
            return $this->getCookieValue($url->getHost(), $url->getPath(), $name);
        }
        
        /**
         *    Fetches a URL as a response object.
         *    @param $url        Target to fetch as Url object.
         *    @param $request    SimpleHttpRequest to send.
         *    @return            Reponse object.
         *    @public
         */
        function &fetchResponse($url, &$request) {
            $cookies = $this->_cookie_jar->getValidCookies($url->getHost(), $url->getPath());
            foreach ($cookies as $cookie) {
                $request->setCookie($cookie);
            }
            $response = &$request->fetch();
            if ($response->isError()) {
                return $response;
            }
            $this->_addCookies($url, $response->getNewCookies());
            return $response;
        }
        
        /**
         *    Fetches the page content with a simple GET request.
         *    @param $raw_url      Target to fetch as string.
         *    @param $parameters   Additional parameters for GET request.
         *    @param $request      Test version of SimpleHttpRequest.
         *    @return              Content of page.
         *    @public
         */
        function get($raw_url, $parameters = false, $request = false) {
            $url = $this->_createAbsoluteUrl($raw_url, $parameters);
            if (!is_object($request)) {
                $request = &new SimpleHttpRequest($url);
            }
            $response = &$this->fetchResponse($url, $request);
            if (!$response->isError()) {
                $this->_extractBaseUrl($url);
                return $response->getContent();
            }
            return false;
        }
        
        /**
         *    Fetches the page content with a HEAD request.
         *    Will affect cookies, but will not change the base URL.
         *    @param $raw_url      Target to fetch as string.
         *    @param $parameters   Additional parameters for GET request.
         *    @param $request      Test version of SimpleHttpRequest.
         *    @return              Content of page.
         *    @public
         */
        function head($raw_url, $parameters = false, $request = false) {
            $url = $this->_createAbsoluteUrl($raw_url, $parameters);
            if (!is_object($request)) {
                $request = &new SimpleHttpRequest($url);
            }
            $response = &$this->fetchResponse($url, $request);
            return !$response->isError();
        }
        
        /**
         *    Extracts new cookies into the cookie jar.
         *    @param $url        Target to fetch as url object.
         *    @param $cookies    New cookies.
         *    @private
         */
        function _addCookies($url, $cookies) {
            foreach ($cookies as $cookie) {
                if ($url->getHost()) {
                    $cookie->setHost($url->getHost());
                }
                $this->_cookie_jar->setCookie($cookie);
            }
        }
        
        /**
         *    Turns an incoming URL string into a
         *    URL object, filling the relative URL if
         *    a base URL is present.
         *    @param $raw_url        URL as string.
         *    @param $parameters     Additional request, parameters.
         *    @return                Absolute URL as object.
         *    @private
         */
        function _createAbsoluteUrl($raw_url, $parameters = false) {
            $url = new SimpleUrl($raw_url);
            if ($parameters) {
                $url->addRequestParameters($parameters);
            }
            $url->makeAbsolute($this->_base_url);
            return $url;
        }
        
        /**
         *    Extracts the host and directory path so
         *    as to set the base URL.
         *    @param $url        URL object to read.
         *    @private
         */
        function _extractBaseUrl($url) {
            $this->_base_url = $url->getScheme("http") . "://" .
                    $url->getHost() . $url->getBasePath();
        }
        
        /**
         *    Accessor for base URL.
         *    @return        Base URL as string.
         *    @public
         */
        function getBaseUrl() {
            return $this->_base_url;
        }
    }
    
    /**
     *    Testing version of web browser. Can be set up to
     *    automatically test reponses.
     */
    class TestBrowser extends SimpleBrowser {
        var $_test;
        var $_expect_connection;
        var $_expected_response_codes;
        var $_expected_cookies;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @public
         */
        function TestBrowser(&$test) {
            $this->SimpleBrowser();
            $this->_test = &$test;
            $this->_clearExpectations();
        }
        
        /**
         *    Resets all expectations.
         *    @protected
         */
        function _clearExpectations() {
            $this->_expect_connection = null;
            $this->_expected_response_codes = null;
            $this->_expected_mime_types = null;
            $this->_expected_cookies = array();
        }
        
        /**
         *    Fetches a URL as a response object performing
         *    tests set in expectations.
         *    @param $url        Target to fetch as SimpleUrl.
         *    @param $request    Test override of SimpleHttpRequest.
         *    @return            Reponse object.
         *    @public
         */
        function &fetchResponse($url, &$request) {
            $response = &parent::fetchResponse($url, $request);
            $this->_checkExpectations($url, $response);
            return $response;
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
        function expectResponseCodes($codes) {
            $this->_expected_response_codes = $codes;
        }
        
        /**
         *    Sets the allowed mime types and adds the
         *    necessary request headers.
         *    @param $types        Array of allowed types.
         *    @public
         */
        function expectMimeTypes($types) {
            $this->_expected_mime_types = $types;
        }
        
        /**
         *    Sets an expectation for a cookie.
         *    @param $name        Cookie key.
         *    @param $value       Expected value of incoming cookie.
         *                        An empty string corresponds to a
         *                        cleared cookie.
         *    @param $message     Message to display.
         *    @public
         */
        function expectCookie($name, $value = false, $message = "%s") {
            $this->_expected_cookies[] = array(
                    "name" => $name,
                    "value" => $value,
                    "message" => $message);
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
            $this->_checkAllExpectedCookies($response);
        }
        
        /**
         *    Checks all incoming cookies against expectations.
         *    @param $reponse     HTTP response from the fetch.
         *    @private
         */
        function _checkAllExpectedCookies(&$response) {
            $cookies = $response->getNewCookies();
            foreach($this->_expected_cookies as $expected) {
                if ($expected["value"] === false) {
                    $this->_checkExpectedCookie($expected, $cookies);
                } else {
                    $this->_checkExpectedCookieValue($expected, $cookies);
                }
            }
        }
        
        /**
         *    Checks that an expected cookie was present
         *    in the incoming cookie list. The cookie
         *    should appear only once.
         *    @param $expected    Expected cookie values as
         *                        simple hash with the message
         *                        to show on failure.
         *    @param $cookies     Incoming cookies.
         *    @return             True if expectation met.
         *    @private
         */
        function _checkExpectedCookie($expected, $cookies) {
            $is_match = false;
            $message = "Expecting cookie [" . $expected["name"] . "]";
            foreach ($cookies as $cookie) {
                if ($is_match = ($cookie->getName() == $expected["name"])) {
                    break;
                }
            }
            $this->_assertTrue($is_match, sprintf($expected["message"], $message));
        }
        
        /**
         *    Checks that an expected cookie was present
         *    in the incoming cookie list and has the
         *    expected value. The cookie should appear once.
         *    @param $expected    Expected cookie values as
         *                        simple hash with the message
         *                        to show on failure.
         *    @param $cookies     Incoming cookies.
         *    @return             True if expectation met.
         *    @private
         */
        function _checkExpectedCookieValue($expected, $cookies) {
            $is_match = false;
            $message = "Expecting cookie " . $expected["name"] .
                    " value [" . $expected["value"] . "]";
            foreach ($cookies as $cookie) {
                if ($cookie->getName() == $expected["name"]) {
                    $is_match = ($cookie->getValue() == $expected["value"]);
                    $message .= " got [" . $cookie->getValue() . "]";
                    if (!$is_match) {
                        break;
                    }
                }
            }
            $this->_assertTrue($is_match, sprintf($expected["message"], $message));
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