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
     *    Browser history list.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleBrowserHistory {
        var $_sequence;
        var $_position;
        
        /**
         *    Starts empty.
         *    @access public
         */
        function SimpleBrowserHistory() {
            $this->_sequence = array();
            $this->_position = -1;
        }
        
        /**
         *    Test for no entries yet.
         *    @return boolean        True if empty.
         *    @access private
         */
        function _isEmpty() {
            return ($this->_position == -1);
        }
        
        /**
         *    Test for being at the beginning.
         *    @return boolean        True if first.
         *    @access private
         */
        function _atBeginning() {
            return ($this->_position == 0) && ! $this->_isEmpty();
        }
        
        /**
         *    Test for being at the last entry.
         *    @return boolean        True if last.
         *    @access private
         */
        function _atEnd() {
            return ($this->_position + 1 >= count($this->_sequence)) && ! $this->_isEmpty();
        }
        
        /**
         *    Adds a successfully fetched page to the history.
         *    @param string $method    GET or POST.
         *    @param SimpleUrl $url    URL of fetch.
         *    @param array $parameters Any post data with the fetch.
         *    @access public
         */
        function recordEntry($method, $url, $parameters) {
            $this->_dropFuture();
            array_push(
                    $this->_sequence,
                    array('method' => $method, 'url' => $url, 'parameters' => $parameters));
            $this->_position++;
        }
        
        /**
         *    Last fetching method for current history
         *    position.
         *    @return string      GET or POST for this point in
         *                        the history.
         *    @access public
         */
        function getMethod() {
            if ($this->_isEmpty()) {
                return false;
            }
            return $this->_sequence[$this->_position]['method'];
        }
        
        /**
         *    Last fully qualified URL for current history
         *    position.
         *    @return SimpleUrl        URL for this position.
         *    @access public
         */
        function getUrl() {
            if ($this->_isEmpty()) {
                return false;
            }
            return $this->_sequence[$this->_position]['url'];
        }
        
        /**
         *    Parameters of last fetch from current history
         *    position.
         *    @return array        Hash of post parameters.
         *    @access public
         */
        function getParameters() {
            if ($this->_isEmpty()) {
                return false;
            }
            return $this->_sequence[$this->_position]['parameters'];
        }
        
        /**
         *    Step back one place in the history. Stops at
         *    the first page.
         *    @return boolean     True if any previous entries.
         *    @access public
         */
        function back() {
            if ($this->_isEmpty() || $this->_atBeginning()) {
                return false;
            }
            $this->_position--;
            return true;
        }
        
        /**
         *    Step forward one place. If already at the
         *    latest entry then nothing will happen.
         *    @return boolean     True if any future entries.
         *    @access public
         */
        function forward() {
            if ($this->_isEmpty() || $this->_atEnd()) {
                return false;
            }
            $this->_position++;
            return true;
        }
        
        /**
         *    Ditches all future entries beyond the current
         *    point.
         *    @access private
         */
        function _dropFuture() {
            if ($this->_isEmpty()) {
                return;
            }
            while (! $this->_atEnd()) {
                array_pop($this->_sequence);
            }
        }
    }
    
    /**
     *    Simulated web browser. This is an aggregate of
     *    the user agent, the HTML parsing, request history
     *    and the last header set.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleBrowser {
        var $_user_agent;
        var $_headers;
        var $_transport_error;
        var $_page;
        var $_history;
        
        /**
         *    Starts with a fresh browser with no
         *    cookie or any other state information.
         *    @access public
         */
        function SimpleBrowser() {
            $this->_user_agent = &$this->_createUserAgent();
            $this->_headers = false;
            $this->_transport_error = false;
            $this->_page = false;
            $this->_history = &$this->_createHistory();
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
         *    Creates a new empty history list.
         *    @return SimpleBrowserHistory    New list.
         *    @access protected
         */
        function &_createHistory() {
            return new SimpleBrowserHistory();
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
         *    Sets the socket timeout for opening a connection.
         *    @param integer $timeout      Maximum time in seconds.
         *    @acces public
         */
        function setConnectionTimeout($timeout) {
            $this->_user_agent->setConnectionTimeout($timeout);
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
         *    Fetches the page content with a HEAD request.
         *    Will affect cookies, but will not change the base URL.
         *    @param string/SimpleUrl $url  Target to fetch as string.
         *    @param hash $parameters       Additional parameters for GET request.
         *    @return boolean               True if successful.
         *    @access public
         */
        function head($url, $parameters = false) {
            $response = &$this->_user_agent->fetchResponse('HEAD', $url, $parameters);
            return ! $response->isError();
        }
        
        /**
         *    Fetches the page content with a simple GET request.
         *    @param string/SimpleUrl $url  Target to fetch.
         *    @param hash $parameters       Additional parameters for GET request.
         *    @return string                Content of page or false.
         *    @access public
         */
        function get($url, $parameters = false) {
            return $this->_fetch('GET', $url, $parameters, true);
        }
        
        /**
         *    Fetches the page content with a POST request.
         *    @param string/SimpleUrl $url  Target to fetch as string.
         *    @param hash $parameters       POST parameters.
         *    @return string                Content of page.
         *    @access public
         */
        function post($url, $parameters = false) {
            return $this->_fetch('POST', $url, $parameters, true);
        }
        
        /**
         *    Fetches a page.
         *    @param string $method         GET or POST.
         *    @param string/SimpleUrl $url  Target to fetch as string.
         *    @param hash $parameters       POST parameters.
         *    @param boolean $record        Whether to record in the history.
         *    @return string                Content of page.
         *    @access private
         */
        function _fetch($method, $url, $parameters, $record) {
            $this->_headers = false;
            $this->_transport_error = false;
            $response = &$this->_user_agent->fetchResponse($method, $url, $parameters);
            if ($response->isError()) {
                $this->_page = &new SimplePage(false);
                $this->_transport_error = $response->getError();
                return false;
            }
            if ($record) {
                $this->_history->recordEntry(
                        $this->_user_agent->getCurrentMethod(),
                        $this->_user_agent->getCurrentUrl(),
                        $this->_user_agent->getCurrentPostData());
            }
            $this->_headers = $response->getHeaders();
            $this->_page = &$this->_parse($response->getContent());
            return $response->getContent();
        }
        
        /**
         *    Equivalent to hitting the retry button on the
         *    browser. Will attempt to repeat the page fetch. If
         *    there is no history to repeat it will  give false.
         *    @return string/boolean   Content if fetch succeeded
         *                             else false.
         *    @access public
         */
        function retry() {
            if ($method = $this->_history->getMethod()) {
                return $this->_fetch(
                        $method,
                        $this->_history->getUrl(),
                        $this->_history->getParameters(),
                        false);
            }
            return false;
        }
        
        /**
         *    Equivalent to hitting the back button on the
         *    browser. The browser history is unchanged on
         *    failure.
         *    @return boolean     True if history entry and
         *                        fetch succeeded
         *    @access public
         */
        function back() {
            if (! $this->_history->back()) {
                return false;
            }
            $is_success = $this->retry();
            if (! $is_success) {
                $this->_history->forward();
            }
            return $is_success;
        }
        
        /**
         *    Equivalent to hitting the forward button on the
         *    browser. The browser history is unchanged on
         *    failure.
         *    @return boolean     True if history entry and
         *                        fetch succeeded
         *    @access public
         */
        function forward() {
            if (! $this->_history->forward()) {
                return false;
            }
            $is_success = $this->retry();
            if (! $is_success) {
                $this->_history->back();
            }
            return $is_success;
        }
        
        /**
         *    Retries a request after setting the authentication
         *    for the current realm.
         *    @param string $username    Username for realm.
         *    @param string $password    Password for realm.
         *    @return boolean            True if successful fetch. Note
         *                               that authentication may still have
         *                               failed.
         *    @access public
         */
        function authenticate($username, $password) {
            if (! $this->_history->getUrl()) {
                return false;
            }
            if (! $this->_headers || ! $this->_headers->getRealm()) {
                return false;
            }
            $url = new SimpleUrl($this->_history->getUrl());
            $this->_user_agent->setIdentity(
                    $url->getHost(),
                    $this->_headers->getRealm(),
                    $username,
                    $password);
            return $this->retry();
        }
        
        /**
         *    Accessor for last error.
         *    @return string        Error from last response.
         *    @access public
         */
        function getTransportError() {
            return $this->_transport_error;
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
         *    Accessor for last Authentication type. Only valid
         *    straight after a challenge (401).
         *    @return string    Description of challenge type.
         *    @access public
         */
        function getAuthentication() {
            if (! $this->_headers) {
                return false;
            }
            return $this->_headers->getAuthentication();
        }
        
        /**
         *    Accessor for last Authentication realm. Only valid
         *    straight after a challenge (401).
         *    @return string    Name of security realm.
         *    @access public
         */
        function getRealm() {
            if (! $this->_headers) {
                return false;
            }
            return $this->_headers->getRealm();
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
         *    Accessor for raw header information.
         *    @return string      Header block.
         *    @access public
         */
        function getHeaders() {
            if (! $this->_headers) {
                return false;
            }
            return $this->_headers->getRaw();
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
         *    one if an index is given. The match ignores case and
         *    space issues.
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
         *    Tests to see if a link is present by label.
         *    @param string $label     Text between the anchor tags.
         *    @return boolean          True if link present.
         *    @access public
         */
        function isLink($label) {
            return (count($this->_page->getUrls($label)) > 0);
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