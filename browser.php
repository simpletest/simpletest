<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    Originally defined in simple_test.php
     */
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'page.php');
    require_once(SIMPLE_TEST . 'user_agent.php');
    
    /**
     *    Simulated web browser.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleBrowser {
        var $_user_agent;
        var $_headers;
        var $_page;
        
        /**
         *    Starts with a fresh browser with no
         *    cookie or any other state information.
         *    @access public
         */
        function SimpleBrowser() {
            $this->_user_agent = &$this->_createUserAgent();
            $this->_headers = false;
            $this->_page = false;
        }
        
        /**
         *    Creates the underlying user agent.
         *    @return SimpleFetcher    Content fetcher.
         *    @access protected
         */
        function &_createUserAgent() {
            return new SimpleUserAgent();
        }
        
        /**
         *    Accessor for base URL worked out from the current URL.
         *    @return string       Base URL.
         *    @access public
         */
        function getBaseUrl() {
            return $this->_user_agent->getBaseUrl();
        }
        
        /**
         *    Accessor for the current browser location.
         *    @return string       Current URL.
         *    @access public
         */
        function getCurrentUrl() {
            return $this->_user_agent->getCurrentUrl();
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
            $this->_user_agent->restartSession($date);
        }
        
        /**
         *    Ages the cookies by the specified time.
         *    @param integer $interval    Amount in seconds.
         *    @access public
         */
        function ageCookies($interval) {
            $this->_user_agent->ageCookies($interval);
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
            $this->_user_agent->setCookie($name, $value, $host, $path, $expiry);
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
            return $this->_user_agent->getCookieValue($host, $path, $name);
        }
        
        /**
         *    Reads the current cookies for the base URL.
         *    @param string $name   Key of cookie to find.
         *    @return string        Null if there is no base URL, false
         *                          if the cookie is not set.
         *    @access public
         */
        function getBaseCookieValue($name) {
            return $this->_user_agent->getBaseCookieValue($name);
        }
        
        /**
         *    Sets the maximum number of redirects before
         *    a page will be loaded anyway.
         *    @param integer $max        Most hops allowed.
         *    @access public
         */
        function setMaximumRedirects($max) {
            $this->_user_agent->setMaximumRedirects($max);
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
            $response = &$this->_user_agent->fetchResponse('GET', $raw_url, $parameters);
            if ($response->isError()) {
                $this->_page = &new SimplePage(false);
                return false;
            }
            $this->_headers = $response->getHeaders();
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
            $response = &$this->_user_agent->fetchResponse('HEAD', $raw_url, $parameters);
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
            $response = &$this->_user_agent->fetchResponse('POST', $raw_url, $parameters);
            if ($response->isError()) {
                $this->_page = &new SimplePage(false);
                return false;
            }
            $this->_headers = $response->getHeaders();
            $this->_page = &$this->_parse($response->getContent());
            return $response->getContent();
        }
        
        /**
         *    Accessor for current MIME type.
         *    @return string    MIME type as string; e.g. 'text/html'
         *    @access public
         */
        function getMimeType() {
            if (! $this->_headers) {
                return false;
            }
            return $this->_headers->getMimeType();
        }
        
        /**
         *    Accessor for last response code.
         *    @return integer    Last HTTP response code received.
         *    @access public
         */
        function getResponseCode() {
            if (! $this->_headers) {
                return false;
            }
            return $this->_headers->getResponseCode();
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
         *    @return boolean         True on success.
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
         *    @return boolean         True on success.
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