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
    require_once(SIMPLE_TEST . 'fetcher.php');
    
    /**
     *    Simulated web browser.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleBrowser {
        var $_cookie_jar;
        var $_response;
        var $_page;
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
            $this->_page = false;
            $this->_current_url = false;
            $this->setMaximumRedirects(DEFAULT_MAX_REDIRECTS);
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
         *    @param SimpleUrl $url       Target to fetch as Url object.
         *    @param hash $parameters     Additional parameters for request.
         *    @return              Response object.
         *    @access protected
         */
        function &fetchResponse($method, $url, $parameters) {
            $redirects = 0;
            do {
                $request = &$this->_createCookieRequest($method, $url, $parameters);
                $response = &$request->fetch();
                if ($response->isError()) {
                    $this->_clearResponse();
                    return $response;
                }
                $headers = $response->getHeaders();
                $this->_addCookies($url, $headers->getNewCookies());
                if (! $headers->isRedirect()) {
                    break;
                }
                $url = new SimpleUrl($headers->getLocation());
            } while (! $this->_isTooManyRedirects(++$redirects));
            $this->_setResponse($response);
            return $response;
        }
        
        /**
         *    Clears the current response.
         *    @access protected
         */
        function _clearResponse() {
            $this->_response = false;
        }
        
        /**
         *    Preserves the current response.
         *    @param SimpleHttpResponse $response    Latest fetch.
         *    @access protected
         */
        function _setResponse(&$response) {
            $this->_response = &$response;
        }
        
        /**
         *    Accessor for last response.
         *    @return SimpleHttpResponse     Response object or
         *                                   false if none.
         *    @access protected
         */
        function &_getResponse() {
            return $this->_response;
        }
        
        /**
         *    Parses the raw content into a page.
         *    @param string $raw    Text of fetch.
         *    @return SimplePage    Parsed HTML.
         *    @access protected
         */
        function &_parse($raw) {
            return new SimplePage($raw);
        }
        
        /**
         *    Fetches the page content with a simple GET request.
         *    @param string $raw_url    Target to fetch.
         *    @param hash $parameters   Additional parameters for GET request.
         *    @return string            Content of page or false.
         *    @access public
         */
        function get($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, $parameters);
            $response = &$this->fetchResponse('GET', $url, $parameters);
            if ($response->isError()) {
                $this->_page = &new SimplePage(false);
                return false;
            }
            $this->_current_url = $url;
            $this->_page = &$this->_parse($response->getContent());
            return $response->getContent();
        }
        
        /**
         *    Fetches the page content with a HEAD request.
         *    Will affect cookies, but will not change the base URL.
         *    @param string $raw_url    Target to fetch as string.
         *    @param hash $parameters   Additional parameters for GET request.
         *    @return string            Content of page.
         *    @access public
         */
        function head($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, $parameters);
            $response = &$this->fetchResponse('HEAD', $url, $parameters);
            return ! $response->isError();
        }
        
        /**
         *    Fetches the page content with a POST request.
         *    @param string $raw_url    Target to fetch as string.
         *    @param hash $parameters   POST parameters.
         *    @return string            Content of page.
         *    @access public
         */
        function post($raw_url, $parameters = false) {
            $url = $this->createAbsoluteUrl($this->getBaseUrl(), $raw_url, array());
            $response = &$this->fetchResponse('POST', $url, $parameters);
            if ($response->isError()) {
                $this->_page = &new SimplePage(false);
                return false;
            }
            $this->_current_url = $url;
            $this->_page = &$this->_parse($response->getContent());
            return $response->getContent();
        }
        
        /**
         *    Accessor for current MIME type.
         *    @return string    MIME type as string; e.g. 'text/html'
         *    @access public
         */
        function getMimeType() {
            $response = &$this->_getResponse();
            if (! $response) {
                return false;
            }
            $headers = &$response->getHeaders();
            return $headers->getMimeType();
        }
        
        /**
         *    Accessor for last response code.
         *    @return integer    Last HTTP response code received.
         *    @access public
         */
        function getResponseCode() {
            $response = &$this->_getResponse();
            if (! $response) {
                return false;
            }
            $headers = &$response->getHeaders();
            return $headers->getResponseCode();
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
        function &_createCookieRequest($method, $url, $parameters = false) {
            if (! $parameters) {
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
        
        /**
         *    Accessor for raw page information.
         *    @return string      Original text content of web page.
         *    @access public
         */
        function getContent() {
            return $this->_page->getRaw();
        }
        
        /**
         *    Accessor for parsed title.
         *    @return string     Title or false if no title is present.
         *    @access public
         */
        function getTitle() {
            return $this->_page->getTitle();
        }
        
        /**
         *    Sets all form fields with that name.
         *    @param string $name    Name of field in forms.
         *    @param string $value   New value of field.
         *    @return boolean        True if field exists, otherwise false.
         *    @access public
         */
        function setField($name, $value) {
            return $this->_page->setField($name, $value);
        }
        /**
         *    Accessor for a form element value within the page.
         *    Finds the first match.
         *    @param string $name        Field name.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getField($name) {
            return $this->_page->getField($name);
        }
        
        /**
         *    Clicks the submit button by label. The owning
         *    form will be submitted by this.
         *    @param string $label    Button label. An unlabeled
         *                            button can be triggered by 'Submit'.
         *    @return boolean         true on success.
         *    @access public
         */
        function clickSubmit($label = "Submit") {
            if (! ($form = &$this->_page->getFormBySubmitLabel($label))) {
                return false;
            }
            $action = $form->getAction();
            if (! $action) {
                $action = $this->getCurrentUrl();
            }
            $method = $form->getMethod();
            return $this->$method($action, $form->submitButtonByLabel($label));
        }
        
        /**
         *    Submits a form by the ID.
         *    @param string $label    Button label. An unlabeled
         *                            button can be triggered by 'Submit'.
         *    @return boolean         true on success.
         *    @access public
         */
        function submitFormById($id) {
            if (! ($form = &$this->_page->getFormById($id))) {
                return false;
            }
            $action = $form->getAction();
            if (! $action) {
                $action = $this->getCurrentUrl();
            }
            $method = $form->getMethod();
            return $this->$method($action, $form->submit());
        }
        
        /**
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given.
         *    @param string $label     Text between the anchor tags.
         *    @param integer $index    Link position counting from zero.
         *    @return boolean          True if link present.
         *    @access public
         */
        function clickLink($label, $index = 0) {
            $urls = $this->_page->getUrls($label);
            if (count($urls) == 0) {
                return false;
            }
            if (count($urls) < $index + 1) {
                return false;
            }
            $this->get($urls[$index]);
            return true;
        }
        
        /**
         *    Follows a link by id attribute.
         *    @param string $id        ID attribute value.
         *    @return boolean          True if link present.
         *    @access public
         */
        function clickLinkById($id) {
            if (! ($url = $this->_page->getUrlById($id))) {
                return false;
            }
            $this->get($url);
            return true;
        }
    }
?>