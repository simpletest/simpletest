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
    define('DEFAULT_CONNECTION_TIMEOUT', 15);
    
    /**
     *    Repository for cookies. This stuff is a
     *    tiny bit browser dependent.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleCookieJar {
        var $_cookies;
        
        /**
         *    Constructor. Jar starts empty.
         *    @access public
         */
        function SimpleCookieJar() {
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
        
        /**
         *    Adds the current cookies to a request.
         *    @param SimpleHttpRequest $request    Request to modify.
         *    @param SimpleUrl $url                Cookie selector.
         *    @access private
         */
        function addHeaders(&$request, $url) {
            $cookies = $this->getValidCookies($url->getHost(), $url->getPath());
            foreach ($cookies as $cookie) {
                $request->setCookie($cookie);
            }
        }
    }
    
    /**
     *    Manages security realms.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleAuthenticator {
        var $_realms;
        
        /**
         *    Starts with no realms set up.
         *    @access public
         */
        function SimpleAuthenticator() {
            $this->_realms = array();
        }
        
        /**
         *    Adds a new realm centered the current URL.
         *    Browsers vary wildly on their behaviour in this
         *    regard. Mozilla ignores the realm and presents
         *    only when challenged, wasting bandwidth. IE
         *    just carries on presenting until a new challenge
         *    occours. SimpleTest tries to follow the spirit of
         *    the original standards committee and treats the
         *    base URL as the root of a file tree shaped realm.
         *    @param SimpleUrl $url    Base of realm.
         *    @param string $type      Authentication type for this
         *                             realm. Only Basic authentication
         *                             is currently supported.
         *    @param string $realm     Name of realm.
         *    @access public
         */
        function addRealm($url, $type, $realm) {
            $this->_realms[$realm]['root'] = $url->getHost() . $url->getBasePath();
            $this->_realms[$realm]['type'] = $type;
        }
        
        /**
         *    Sets the current identity to be presented
         *    against that realm.
         *    @param string $realm       Name of realm.
         *    @param string $username    Username for realm.
         *    @param string $password    Password for realm.
         *    @access public
         */
        function setIdentityForRealm($realm, $username, $password) {
            if (isset($this->_realms[$realm])) {
                $this->_realms[$realm]['username'] = $username;
                $this->_realms[$realm]['password'] = $password;
            }
        }
        
        /**
         *    Finds the name of the realm by comparing URLs.
         *    @param SimpleUrl $url        URL to test.
         *    @access private
         */
        function _findRealmFromUrl($url) {
            foreach ($this->_realms as $realm => $authentication) {
                if ($this->_isWithin($url, $authentication['root'])) {
                    return $realm;
                }
            }
            return false;
        }
        
        /**
         *    Compares two URLs to see if the first is within
         *    the realm of the second.
         *    @param SimpleUrl $url    URL to test.
         *    @param string $root      Root of realm.
         *    @access private
         */
        function _isWithin($url, $root) {
            $stem = $url->getHost() . $url->getBasePath();
            return (strpos($stem, $root) === 0);
        }
        
        /**
         *    Presents the appropriate headers for this location.
         *    @param SimpleHttpRequest $request  Request to modify.
         *    @param SimpleUrl $url              Base of realm.
         *    @access public
         */
        function addHeaders(&$request, $url) {
            if ($url->getUsername() && $url->getPassword()) {
                $username = $url->getUsername();
                $password = $url->getPassword();
            } elseif ($realm = $this->_findRealmFromUrl($url)) {
                $username = $this->_realms[$realm]['username'];
                $password = $this->_realms[$realm]['password'];
            } else {
                return;
            }
            $this->addBasicHeaders($request, $username, $password);
        }
        
        /**
         *    Presents the appropriate headers for this
         *    location for basic authentication.
         *    @param SimpleHttpRequest $request  Request to modify.
         *    @param string $username            Username for realm.
         *    @param string $password            Password for realm.
         *    @access public
         *    @static
         */
        function addBasicHeaders(&$request, $username, $password) {
            if ($username && $password) {
                $request->addHeaderLine(
                        'Authorization: Basic ' . base64_encode("$username:$password"));
            }
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
        var $_authenticator;
        var $_max_redirects;
        var $_connection_timeout;
        var $_current_request;
        
        /**
         *    Starts with no cookies.
         *    @access public
         */
        function SimpleUserAgent() {
            $this->_cookie_jar = &new SimpleCookieJar();
            $this->_authenticator = &new SimpleAuthenticator();
            $this->setMaximumRedirects(DEFAULT_MAX_REDIRECTS);
            $this->setConnectionTimeout(DEFAULT_CONNECTION_TIMEOUT);
            $this->_current_request = false;
        }
        
        /**
         *    Sets the current request information.
         *    @param string $method      GET or POST only.
         *    @param SimpleUrl $url      Current URL.
         *    @param array/string $post  POST data if any.
         */
        function _setCurrentRequest($method, $url, $post) {
            if ($method == 'HEAD') {
                return;
            }
            $this->_current_request = array(
                    'method' => $method,
                    'url' => $url,
                    'post' => $post);
        }
        
        /**
         *    Accessor for base URL worked out from the current URL.
         *    @return string       Base URL.
         *    @access public
         */
        function getBaseUrl() {
            if (! $this->_current_request) {
                return false;
            }
            return $this->_current_request['url']->getScheme('http') . '://' .
                    $this->_current_request['url']->getHost() .
                    $this->_current_request['url']->getBasePath();
        }
        
        /**
         *    Accessor for the current browser location.
         *    @return string       Current URL.
         *    @access public
         */
        function getCurrentUrl() {
            if (! $this->_current_request) {
                return false;
            }
            $authorisation = '';
            if ($this->_current_request['url']->getUsername()) {
                $authorisation = $this->_current_request['url']->getUsername() . ':' .
                        $this->_current_request['url']->getPassword() . '@';
            }
            return $this->_current_request['url']->getScheme('http') . '://' .
                    $authorisation .
                    $this->_current_request['url']->getHost() .
                    $this->_current_request['url']->getPath() .
                    $this->_current_request['url']->getEncodedRequest();
        }
        
        /**
         *    Accessor for method of last request.
         *    @return string       POST or GET.
         *    @access public
         */
        function getCurrentMethod() {
            if (! $this->_current_request) {
                return false;
            }
            return $this->_current_request['method'];
        }
        
        /**
         *    Accessor for method of last request.
         *    @return string       POST or GET.
         *    @access public
         */
        function getCurrentPostData() {
            if (! $this->_current_request) {
                return false;
            }
            return $this->_current_request['post'];
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
         *    Sets the socket timeout for opening a connection.
         *    @param integer $timeout      Maximum time in seconds.
         *    @acces public
         */
        function setConnectionTimeout($timeout) {
            $this->_connection_timeout = $timeout;
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
         *    Sets the identity for the current realm.
         *    @param string $realm       Full name of realm.
         *    @param string $username    Username for realm.
         *    @param string $password    Password for realm.
         *    @access public
         */
        function setIdentity($realm, $username, $password) {
            $this->_authenticator->setIdentityForRealm($realm, $username, $password);
        }
        
        /**
         *    Fetches a URL as a response object. Will
         *    keep trying if redirected.
         *    @param string $method         GET, POST, etc.
         *    @param string/SimpleUrl $url  Target to fetch.
         *    @param hash $parameters       Additional parameters for request.
         *    @return SimpleHttpResponse    Hopefully the target page.
         *    @access public
         */
        function &fetchResponse($method, $url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $url);
            if ($method != 'POST') {
                $url->addRequestParameters($parameters);
                $parameters = false;
            }
            $response = &$this->_fetchWhileRedirected($method, $url, $parameters);
            if ($headers = $response->getHeaders()) {
                if ($headers->isChallenge()) {
                    $this->_authenticator->addRealm(
                            $url,
                            $headers->getAuthentication(),
                            $headers->getRealm());
                }
            }
            return $response;
        }
        
        /**
         *    Fetches the page until no longer redirected or
         *    until the redirect limit runs out.
         *    @param string $method         GET, POST, etc.
         *    @param string/SimpleUrl $url  Target to fetch.
         *    @param hash $parameters       Additional parameters for request.
         *    @return SimpleHttpResponse    Hopefully the target page.
         *    @access private
         */
        function &_fetchWhileRedirected($method, $url, $parameters) {
            $redirects = 0;
            do {
                $response = &$this->_fetch($method, $url, $parameters);
                if ($response->isError()) {
                    return $response;
                }
                $headers = $response->getHeaders();
                $this->_addCookiesToJar($url, $headers->getNewCookies());
                if (! $headers->isRedirect()) {
                    break;
                }
                $url = new SimpleUrl($headers->getLocation());
                $method = 'GET';
                $parameters = false;
            } while (! $this->_isTooManyRedirects(++$redirects));
            $this->_setCurrentRequest($method, $url, $parameters);
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
            $request = &$this->_createRequest($method, $url, $parameters);
            return $request->fetch($this->_connection_timeout);
        }
        
        /**
         *    Creates a full page request.
         *    @param string $method       Fetching method.
         *    @param SimpleUrl $url       Target to fetch as url object.
         *    @param hash $parameters     POST/GET parameters.
         *    @return SimpleHttpRequest   New request.
         *    @access private
         */
        function &_createRequest($method, $url, $parameters) {
            $request = &$this->_createHttpRequest($method, $url, $parameters);
            $this->_cookie_jar->addHeaders($request, $url);
            $this->_authenticator->addHeaders($request, $url);
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
        function &_createHttpRequest($method, $url, $parameters) {
            if ($method == 'POST') {
                $request = &new SimpleHttpPostRequest($url, $parameters);
                return $request;
            }
            if ($parameters) {
                $url->addRequestParameters($parameters);
            }
            return new SimpleHttpRequest($url, $method);
        }
        
        /**
         *    Extracts new cookies into the cookie jar.
         *    @param SimpleUrl $url     Target to fetch as url object.
         *    @param array $cookies     New cookies.
         *    @access private
         */
        function _addCookiesToJar($url, $cookies) {
            foreach ($cookies as $cookie) {
                if ($url->getHost()) {
                    $cookie->setHost($url->getHost());
                }
                $this->_cookie_jar->setCookie($cookie);
            }
        }
        
        /**
         *    Turns an incoming URL string or object into a
         *    URL object, filling the relative URL if
         *    a base URL is present.
         *    @param string/SimpleUrl $base Browser current URL.
         *    @param string/SimpleUrl $url  Incoming URL.
         *    @return SimpleUrl             Absolute URL.
         *    @access public
         *    @static
         */
        function createAbsoluteUrl($base, $url) {
            if (! is_object($url)) {
                $url = new SimpleUrl($url);
            }
            $url->makeAbsolute($base);
            return $url;
        }
    }
?>