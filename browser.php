<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'unit_tester.php');
    
    define('DEFAULT_MAX_REDIRECTS', 3);
    
    /**
     *    Repository for cookies. The semantics are a bit
     *    ropy until I can go through the cookie spec with
     *    a fine tooth combe.
     */
    class CookieJar {
        var $_cookies;
        
        /**
         *    Constructor. Jar starts empty.
         *    @access public
         */
        function CookieJar() {
            $this->_cookies = array();
        }
        
        /**
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened.
         *    @param string/integer $now   Time to test expiry against.
         *    @access public
         */
        function restartSession($date = false) {
            $surviving_cookies = array();
            for ($i = 0; $i < count($this->_cookies); $i++) {
                if (! $this->_cookies[$i]->getValue()) {
                    continue;
                }
                if (! $this->_cookies[$i]->getExpiry()) {
                    continue;
                }
                if ($date && $this->_cookies[$i]->isExpired($date)) {
                    continue;
                }
                $surviving_cookies[] = $this->_cookies[$i];
            }
            $this->_cookies = $surviving_cookies;
        }
        
        /**
         *    Ages all cookies in the cookie jar.
         *    @param integer $interval     The old session is moved
         *                                 into the past by this number
         *                                 of seconds. Cookies now over
         *                                 age will be removed.
         *    @access public
         */
        function agePrematurely($interval) {
            for ($i = 0; $i < count($this->_cookies); $i++) {
                $this->_cookies[$i]->agePrematurely($interval);
            }
        }
        
        /**
         *    Adds a cookie to the jar. This will overwrite
         *    cookies with matching host, paths and keys.
         *    @param $cookie        New cookie.
         *    @access public
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
         *    @access public
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
         *    @access private
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
        var $_current_url;
        var $_max_redirects;
        
        /**
         *    Starts with a fresh browser with no
         *    cookie or any other state information.
         *    @access public
         */
        function SimpleBrowser() {
            $this->_cookie_jar = new CookieJar();
            $this->_response = false;
            $this->_current_url = false;
            $this->setMaximumRedirects(DEFAULT_MAX_REDIRECTS);
        }
        
        /**
         *    Accessor for base URL worked out from the current URL.
         *    @return        Base URL as string.
         *    @access public
         */
        function getBaseUrl() {
            if (!$this->_current_url) {
                return false;
            }
            return $this->_current_url->getScheme('http') . '://' .
                    $this->_current_url->getHost() . $this->_current_url->getBasePath();
        }
        
        /**
         *    Accessor for the current browser location.
         *    @return        Current URL as string.
         *    @access public
         */
        function getCurrentUrl() {
            if (!$this->_current_url) {
                return false;
            }
            return $this->_current_url->getScheme('http') . '://' .
                    $this->_current_url->getHost() . $this->_current_url->getPath();
        }
        
        /**
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened.
         *    @param string/integer $date   Time when session restarted.
         *                                  If ommitted then all persistent
         *                                  cookies are kept.
         *    @access public
         */
        function restartSession($date = false) {
            $this->_cookie_jar->restartSession($date);
        }
        
        /**
         *    Ages the cookies by the specified time.
         *    @param integer $interval    Amount in seconds.
         *    @access public
         */
        function ageCookies($interval) {
            $this->_cookie_jar->agePrematurely($interval);
        }
        
        /**
         *    Sets an additional cookie. If a cookie has
         *    the same name and path it is replaced.
         *    @param $name            Cookie key.
         *    @param $value           Value of cookie.
         *    @param $host            Host upon which the cookie is valid.
         *    @param $path            Cookie path if not host wide.
         *    @param $expiry          Expiry date as string.
         *    @access public
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
         *    @access public
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
         *    @access public
         */
        function getBaseCookieValue($name) {
            if (! $this->getBaseUrl()) {
                return null;
            }
            $url = new SimpleUrl($this->getBaseUrl());
            return $this->getCookieValue($url->getHost(), $url->getPath(), $name);
        }
        
        /**
         *    Sets the maximum number of redirects before
         *    a page will be loaded anyway.
         *    @param $max        Most hops allowed.
         *    @access public
         */
        function setMaximumRedirects($max) {
            $this->_max_redirects = $max;
        }
        
        /**
         *    Test to see if the redirect limit is passed.
         *    @param $redirects        Count so far.
         *    @return                  True if over.
         *    @access private
         */
        function _isTooManyRedirects($redirects) {
            return ($redirects > $this->_max_redirects);
        }
        
        /**
         *    Fetches a URL as a response object. Will
         *    keep trying if redirected.
         *    @param $method       GET, POST, etc.
         *    @param $url          Target to fetch as Url object.
         *    @param $parameters   Additional parameters for request.
         *    @return              Response object.
         *    @access protected
         */
        function &fetchResponse($method, $url, $parameters) {
            $redirects = 0;
            do {
                $request = &$this->_createCookieRequest($method, $url, $parameters);
                $response = &$request->fetch();
                if ($response->isError()) {
                    $this->_response = false;
                    return $response;
                }
                $this->_addCookies($url, $response->getNewCookies());
                if (!$response->isRedirect()) {
                    break;
                }
                $url = new SimpleUrl($response->getRedirect());
            } while (! $this->_isTooManyRedirects(++$redirects));
            $this->_response = &$response;
            return $response;
        }
        
        /**
         *    Accessor for last response.
         *    @return      Response object or false if none.
         *    @access protected
         */
        function &_getLastResponse() {
            return $this->_response;
        }
        
        /**
         *    Fetches the page content with a simple GET request.
         *    @param $raw_url      Target to fetch as string.
         *    @param $parameters   Additional parameters for GET request.
         *    @return              Content of page.
         *    @access public
         */
        function get($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, $parameters);
            $response = &$this->fetchResponse('GET', $url, $parameters);
            if ($response->isError()) {
                return false;
            }
            $this->_current_url = $url;
            return $response->getContent();
        }
        
        /**
         *    Fetches the page content with a HEAD request.
         *    Will affect cookies, but will not change the base URL.
         *    @param $raw_url      Target to fetch as string.
         *    @param $parameters   Additional parameters for GET request.
         *    @return              Content of page.
         *    @access public
         */
        function head($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, $parameters);
            $response = &$this->fetchResponse('HEAD', $url, $parameters);
            return !$response->isError();
        }
        
        /**
         *    Fetches the page content with a POST request.
         *    @param $raw_url      Target to fetch as string.
         *    @param $parameters   POST parameters.
         *    @return              Content of page.
         *    @access public
         */
        function post($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, array());
            $response = &$this->fetchResponse('POST', $url, $parameters);
            if ($response->isError()) {
                return false;
            }
            $this->_current_url = $url;
            return $response->getContent();
        }
        
        /**
         *    Creates a page request with the browser cookies
         *    added.
         *    @param $method       Fetching method.
         *    @param $url          Target to fetch as url object.
         *    @param $parameters   POST/GET parameters.
         *    @return              New request object.
         *    @access private
         */
        function &_createCookieRequest($method, $url, $parameters = false) {
            if (!$parameters) {
                $parameters = array();
            }
            $request = &$this->_createRequest($method, $url, $parameters);
            $cookies = $this->_cookie_jar->getValidCookies($url->getHost(), $url->getPath());
            foreach ($cookies as $cookie) {
                $request->setCookie($cookie);
            }
            return $request;
        }
        
        /**
         *    Builds the appropriate HTTP request object.
         *    @param $method       Fetching method.
         *    @param $url          Target to fetch as url object.
         *    @param $parameters   POST/GET parameters.
         *    @return              New request object.
         *    @access protected
         */
        function &_createRequest($method, $url, $parameters) {
            if ($method == 'POST') {
                $request = &new SimpleHttpPushRequest(
                        $url,
                        SimpleUrl::encodeRequest($parameters),
                        'POST');
                $request->addHeaderLine('Content-Type: application/x-www-form-urlencoded');
                return $request;
            }
            return new SimpleHttpRequest($url, $method);
        }
        
        /**
         *    Extracts new cookies into the cookie jar.
         *    @param $url        Target to fetch as url object.
         *    @param $cookies    New cookies.
         *    @access private
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
         *    @param $base_url       Browser current URL as string.
         *    @param $raw_url        URL as string.
         *    @param $parameters     Additional request, parameters.
         *    @return                Absolute URL as object.
         *    @access public
         *    @static
         */
        function createAbsoluteUrl($base_url, $raw_url, $parameters = false) {
            $url = new SimpleUrl($raw_url);
            $url->addRequestParameters($parameters);
            $url->makeAbsolute($base_url);
            return $url;
        }
    }
    
    /**
     *    Testing version of web browser. Can be set up to
     *    automatically test cookies.
     */
    class TestBrowser extends SimpleBrowser {
        var $_test;
        var $_expected_cookies;
        
        /**
         *    Starts the browser empty.
         *    @param $test     Test case with assertTrue().
         *    @access public
         */
        function TestBrowser(&$test) {
            $this->SimpleBrowser();
            $this->_test = &$test;
            $this->_clearExpectations();
        }
        
        /**
         *    Resets all expectations.
         *    @access protected
         */
        function _clearExpectations() {
            $this->_expected_cookies = array();
        }
        
        /**
         *    Fetches a URL as a response object performing
         *    tests set in expectations.
         *    @param $method     GET, POST, etc.
         *    @param $url        Target to fetch as SimpleUrl.
         *    @param $parameters Additional parameters for request.
         *    @return            Reponse object.
         *    @access public
         */
        function &fetchResponse($method, $url, $parameters) {
            $response = &parent::fetchResponse($method, $url, $parameters);
            $this->_checkExpectations($url, $response);
            $this->_clearExpectations();
            return $response;
        }
        
        /**
         *    Sets an expectation for a cookie.
         *    @param $name        Cookie key.
         *    @param $value       Expected value of incoming cookie.
         *                        An empty string corresponds to a
         *                        cleared cookie.
         *    @param $message     Message to display.
         *    @access public
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
         *    @access private
         */
        function _checkExpectations($url, &$response) {
            $this->_checkAllExpectedCookies($response);
        }
        
        /**
         *    Checks all incoming cookies against expectations.
         *    @param $reponse     HTTP response from the fetch.
         *    @access private
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
         *    @access private
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
         *    @access private
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
         *    Checks the response code against a list
         *    of possible values.
         *    @param $responses    Possible responses for a pass.
         *    @access public
         */
        function assertResponse($responses, $message = "%s") {
            $responses = (is_array($responses) ? $responses : array($responses));
            $response = &$this->_getLastResponse();
            $code = ($response ? $response->getResponseCode() : "None");
            $message = sprintf($message, "Expecting response in [" .
                    implode(", ", $responses) . "] got [$code]");
            $this->_assertTrue(in_array($code, $responses), $message);
        }
        
        /**
         *    Checks the mime type against a list
         *    of possible values.
         *    @param $types    Possible mime types for a pass.
         *    @access public
         */
        function assertMime($types, $message = "%s") {
            $types = (is_array($types) ? $types : array($types));
            $response = &$this->_getLastResponse();
            $type = ($response ? $response->getMimeType() : "None");
            $message = sprintf($message, "Expecting mime type in [" .
                    implode(", ", $types) . "] got [$type]");
            $this->_assertTrue(in_array($type, $types), $message);
        }
        
        /**
         *    Sends an assertion to the held test case.
         *    @param $result        True on success.
         *    @param $message       Message to send to test.
         *    @access protected
         */
        function _assertTrue($result, $message) {
            $this->_test->assertTrue($result, $message);
        }
    }
?>