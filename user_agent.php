<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    originally defined in simple_test.php
     */
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'page.php');
    
    define('DEFAULT_MAX_REDIRECTS', 3);
    
    /**
     *    Repository for cookies. This stuff is a
     *    tiny bit browser dependent.
	 *    @package SimpleTest
	 *    @subpackage WebTester
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
         *    @param SimpleCookie $cookie        New cookie.
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
         *    @param string $host   Host name requirement.
         *    @param string $path   Path encompassing cookies.
         *    @return hash          Valid cookie objects keyed
         *                          on the cookie name.
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
         *    @param SimpleTest $cookie    Cookie to test.
         *    @param string $host          Host must match.
         *    @param string $path          Cookie path must be shorter than
         *                                 this path.
         *    @param string $name          Name must match.
         *    @return boolean              True if matched.
         *    @access private
         */
        function _isMatch($cookie, $host, $path, $name) {
            if ($cookie->getName() != $name) {
                return false;
            }
            if ($host && $cookie->getHost() && !$cookie->isValidHost($host)) {
                return false;
            }
            if (! $cookie->isValidPath($path)) {
                return false;
            }
            return true;
        }
    }
    
    /**
     *    Fetches web pages whilst keeping track of
     *    cookies.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleUserAgent {
        var $_cookie_jar;
        var $_max_redirects;
        var $_current_url;
        
        /**
         *    Starts with no cookies.
         *    @access public
         */
        function SimpleUserAgent() {
            $this->_cookie_jar = new CookieJar();
            $this->setMaximumRedirects(DEFAULT_MAX_REDIRECTS);
            $this->_current_url = false;
        }
        
        /**
         *    Accessor for base URL worked out from the current URL.
         *    @return string       Base URL.
         *    @access public
         */
        function getBaseUrl() {
            if (! $this->_current_url) {
                return false;
            }
            return $this->_current_url->getScheme('http') . '://' .
                    $this->_current_url->getHost() . $this->_current_url->getBasePath();
        }
        
        /**
         *    Accessor for the current browser location.
         *    @return string       Current URL.
         *    @access public
         */
        function getCurrentUrl() {
            if (! $this->_current_url) {
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
         *    @param string $name            Cookie key.
         *    @param string $value           Value of cookie.
         *    @param string $host            Host upon which the cookie is valid.
         *    @param string $path            Cookie path if not host wide.
         *    @param string $expiry          Expiry date.
         *    @access public
         */
        function setCookie($name, $value, $host = false, $path = '/', $expiry = false) {
            $cookie = new SimpleCookie($name, $value, $path, $expiry);
            if ($host) {
                $cookie->setHost($host);
            }
            $this->_cookie_jar->setCookie($cookie);
        }
        
        /**
         *    Reads the most specific cookie value from the
         *    browser cookies.
         *    @param string $host        Host to search.
         *    @param string $path        Applicable path.
         *    @param string $name        Name of cookie to read.
         *    @return string             False if not present, else the
         *                               value as a string.
         *    @access public
         */
        function getCookieValue($host, $path, $name) {
            $longest_path = '';
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
         *    @param string $name   Key of cookie to find.
         *    @return string        Null if there is no base URL, false
         *                          if the cookie is not set.
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
         *    @param integer $max        Most hops allowed.
         *    @access public
         */
        function setMaximumRedirects($max) {
            $this->_max_redirects = $max;
        }
        
        /**
         *    Test to see if the redirect limit is passed.
         *    @param integer $redirects        Count so far.
         *    @return boolean                  True if over.
         *    @access private
         */
        function _isTooManyRedirects($redirects) {
            return ($redirects > $this->_max_redirects);
        }
        
        /**
         *    Fetches a URL as a response object. Will
         *    keep trying if redirected.
         *    @param string $method       GET, POST, etc.
         *    @param string $raw_url      Target to fetch.
         *    @param hash $parameters     Additional parameters for request.
         *    @return              Response object.
         *    @access public
         */
        function &fetchResponse($method, $raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, $parameters);
            $redirects = 0;
            do {
                $response = &$this->_fetch($method, $url, $parameters);
                if ($response->isError()) {
                    return $response;
                }
                $headers = $response->getHeaders();
                $this->_addCookies($url, $headers->getNewCookies());
                if (! $headers->isRedirect()) {
                    break;
                }
                $url = new SimpleUrl($headers->getLocation());
            } while (! $this->_isTooManyRedirects(++$redirects));
            $this->_current_url = $url;
            return $response;
        }
        
        /**
         *    Actually make the web request.
         *    @param string $method       GET, POST, etc.
         *    @param SimpleUrl $url       Target to fetch.
         *    @param hash $parameters     Additional parameters for request.
         *    @return SimpleHttpResponse  Headers and hopefully content.
         *    @access protected
         */
        function &_fetch($method, $url, $parameters) {
            if (! $parameters) {
                $parameters = array();
            }
            $request = &$this->_createCookieRequest($method, $url, $parameters);
            return $request->fetch();
        }
        
        /**
         *    Creates a page request with the browser cookies
         *    added.
         *    @param string $method       Fetching method.
         *    @param SimpleUrl $url       Target to fetch as url object.
         *    @param hash $parameters     POST/GET parameters.
         *    @return SimpleHttpRequest   New request.
         *    @access private
         */
        function &_createCookieRequest($method, $url, $parameters) {
            $request = &$this->_createRequest($method, $url, $parameters);
            $cookies = $this->_cookie_jar->getValidCookies($url->getHost(), $url->getPath());
            foreach ($cookies as $cookie) {
                $request->setCookie($cookie);
            }
            return $request;
        }
        
        /**
         *    Builds the appropriate HTTP request object.
         *    @param string $method       Fetching method.
         *    @param SimpleUrl $url       Target to fetch as url object.
         *    @param hash $parameters     POST/GET parameters.
         *    @return SimpleHttpRequest   New request object.
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
         *    @param SimpleUrl $url     Target to fetch as url object.
         *    @param array $cookies     New cookies.
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
         *    @param string $base_url       Browser current URL.
         *    @param string $raw_url        Incoming URL.
         *    @param hash $parameters       Additional request, parameters.
         *    @return SimpleUrl             Absolute URL.
         *    @access public
         *    @static
         */
        function createAbsoluteUrl($base_url, $raw_url, $parameters = false) {
            $url = new SimpleUrl($raw_url);
            if ($parameters) {
                $url->addRequestParameters($parameters);
            }
            $url->makeAbsolute($base_url);
            return $url;
        }
    }
?>