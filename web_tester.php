<?php
    /**
     *	Base include file for SimpleTest.
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/simple_test.php');
    require_once(dirname(__FILE__) . '/browser.php');
    require_once(dirname(__FILE__) . '/page.php');
    /**#@-*/
    
    /**
     *    Test case for testing of web pages. Allows
     *    fetching of pages, parsing of HTML and
     *    submitting forms.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class WebTestCase extends SimpleTestCase {
        var $_browser;
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param string $label     Name of test case. Will use
         *                             the class name if none specified.
         *    @access public
         */
        function WebTestCase($label = false) {
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Dumps the current request for debugging.
         *    @access public
         */
        function showRequest() {
            $this->dump($this->_browser->getRequest());
        }
        
        /**
         *    Dumps the current HTML source for debugging.
         *    @access public
         */
        function showSource() {
            $this->dump($this->_browser->getContent());
        }
        
        /**
         *    Dumps the current HTTP headers for debugging.
         *    @access public
         */
        function showHeaders() {
            $this->dump($this->_browser->getHeaders());
        }
        
        /**
         *    Gets the last response error.
         *    @return string    Last low level HTTP error.
         *    @access public
         */
        function getTransportError() {
            return $this->_browser->getTransportError();
        }
        
        /**
         *    Simulates the closing and reopening of the browser.
         *    Temporary cookies will be discarded and timed
         *    cookies will be expired if later than the
         *    specified time.
         *    @param string/integer $date Time when session restarted.
         *                                If ommitted then all persistent
         *                                cookies are kept. Time is either
         *                                Cookie format string or timestamp.
         *    @access public
         */
        function restartSession($date = false) {
            if ($date === false) {
                $date = time();
            }
            $this->_browser->restartSession($date);
        }
        
        /**
         *    Moves cookie expiry times back into the past.
         *    Useful for testing timeouts and expiries.
         *    @param integer $interval    Amount to age in seconds.
         *    @access public
         */
        function ageCookies($interval) {
            $this->_browser->ageCookies($interval);
        }
        
        /**
         *    Gets a current browser reference for setting
         *    special expectations or for detailed
         *    examination of page fetches.
         *    @param SimpleBrowser $browser    Test browser object.
         *    @access public
         */
        function &getBrowser() {
            return $this->_browser;
        }
        
        /**
         *    Creates a new default web browser object.
         *    Will be cleared at the end of the test method.
         *    @return TestBrowser           New browser.
         *    @access public
         */
        function &createBrowser() {
            return new SimpleBrowser();
        }
        
        /**
         *    Sets up a browser for the start of each
         *    test method.
         *    @param string $method    Name of test method.
         *    @access protected
         */
        function invoke($method) {
            $this->_browser = &$this->createBrowser();
            parent::invoke($method);
        }
        
        /**
         *    Disables frames support. Frames will not be fetched
         *    and the frameset page will be used instead.
         *    @access public
         */
        function ignoreFrames() {
            $this->_browser->ignoreFrames();
        }
        
        /**
         *    Sets a cookie in the current browser.
         *    @param string $name          Name of cookie.
         *    @param string $value         Cookie value.
         *    @param string $host          Host upon which the cookie is valid.
         *    @param string $path          Cookie path if not host wide.
         *    @param string $expiry        Expiry date.
         *    @access public
         */
        function setCookie($name, $value, $host = false, $path = "/", $expiry = false) {
            $this->_browser->setCookie($name, $value, $host, $path, $expiry);
        }

        /**
         *    Adds a header to every fetch.
         *    @param string $header       Header line to add to every
         *                                request until cleared.
         *    @access public
         */
        function addHeader($header) {
            $this->_browser->addHeader($header);
        }
        
        /**
         *    Sets the maximum number of redirects before
         *    the web page is loaded regardless.
         *    @param integer $max        Maximum hops.
         *    @access public
         */
        function setMaximumRedirects($max) {
            if (! $this->_browser) {
                trigger_error(
                        'Can only set maximum redirects in a test method, setUp() or tearDown()');
            }
            $this->_browser->setMaximumRedirects($max);
        }
        
        /**
         *    Sets the socket timeout for opening a connection and
         *    receiving at least one byte of information.
         *    @param integer $timeout      Maximum time in seconds.
         *    @access public
         */
        function setConnectionTimeout($timeout) {
            $this->_browser->setConnectionTimeout($timeout);
        }
        
        /**
         *    Sets proxy to use on all requests for when
         *    testing from behind a firewall. Set URL
         *    to false to disable.
         *    @param string $proxy        Proxy URL.
         *    @param string $username     Proxy username for authentication.
         *    @param string $password     Proxy password for authentication.
         *    @access public
         */
        function useProxy($proxy, $username = false, $password = false) {
            $this->_browser->useProxy($proxy, $username, $password);
        }
        
        /**
         *    Fetches a page into the page buffer. If
         *    there is no base for the URL then the
         *    current base URL is used. After the fetch
         *    the base URL reflects the new location.
         *    @param string $url          URL to fetch.
         *    @param hash $parameters     Optional additional GET data.
         *    @return boolean             True on success.
         *    @access public
         */
        function get($url, $parameters = false) {
            $content = $this->_browser->get($url, $parameters);
            if ($content === false) {
                return false;
            }
            return true;
        }
        
        /**
         *    Fetches a page by POST into the page buffer.
         *    If there is no base for the URL then the
         *    current base URL is used. After the fetch
         *    the base URL reflects the new location.
         *    @param string $url          URL to fetch.
         *    @param hash $parameters     Optional additional GET data.
         *    @return boolean             True on success.
         *    @access public
         */
        function post($url, $parameters = false) {
            $content = $this->_browser->post($url, $parameters);
            if ($content === false) {
                return false;
            }
            return true;
        }
        
        /**
         *    Does a HTTP HEAD fetch, fetching only the page
         *    headers. The current base URL is unchanged by this.
         *    @param string $url          URL to fetch.
         *    @param hash $parameters     Optional additional GET data.
         *    @return boolean             True on success.
         *    @access public
         */
        function head($url, $parameters = false) {
            return $this->_browser->head($url, $parameters);
        }
        
        /**
         *    Equivalent to hitting the retry button on the
         *    browser. Will attempt to repeat the page fetch.
         *    @return boolean     True if fetch succeeded.
         *    @access public
         */
        function retry() {
            return $this->_browser->retry();
        }
        
        /**
         *    Equivalent to hitting the back button on the
         *    browser.
         *    @return boolean     True if history entry and
         *                        fetch succeeded.
         *    @access public
         */
        function back() {
            return $this->_browser->back();
        }
        
        /**
         *    Equivalent to hitting the forward button on the
         *    browser.
         *    @return boolean     True if history entry and
         *                        fetch succeeded.
         *    @access public
         */
        function forward() {
            return $this->_browser->forward();
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
            return $this->_browser->authenticate($username, $password);
        }
        
        /**
         *    Accessor for current frame focus. Will be
         *    false if no frame has focus.
         *    @return integer/string/boolean    Label if any, otherwise
         *                                      the position in the frameset
         *                                      or false if none.
         *    @access public
         */
        function getFrameFocus() {
            return $this->_browser->getFrameFocus();
        }
        
        /**
         *    Sets the focus by index. The integer index starts from 1.
         *    @param integer $choice    Chosen frame.
         *    @return boolean           True if frame exists.
         *    @access public
         */
        function setFrameFocusByIndex($choice) {
            return $this->_browser->setFrameFocusByIndex($choice);
        }
        
        /**
         *    Sets the focus by name.
         *    @param string $name    Chosen frame.
         *    @return boolean        True if frame exists.
         *    @access public
         */
        function setFrameFocus($name) {
            return $this->_browser->setFrameFocus($name);
        }
        
        /**
         *    Clears the frame focus. All frames will be searched
         *    for content.
         *    @access public
         */
        function clearFrameFocus() {
            return $this->_browser->clearFrameFocus();
        }
        
        /**
         *    Clicks the submit button by label. The owning
         *    form will be submitted by this.
         *    @param string $label    Button label. An unlabeled
         *                            button can be triggered by 'Submit'.
         *    @return boolean         True on success.
         *    @access public
         */
        function clickSubmit($label = 'Submit') {
            return $this->_browser->clickSubmit($label);
        }
        
        /**
         *    Clicks the submit button by name attribute. The owning
         *    form will be submitted by this.
         *    @param string $name    Name attribute of button.
         *    @return boolean        True on success.
         *    @access public
         */
        function clickSubmitByName($name) {
            return $this->_browser->clickSubmitByName($name);
        }
        
        /**
         *    Clicks the submit button by ID attribute. The owning
         *    form will be submitted by this.
         *    @param string $id      ID attribute of button.
         *    @return boolean        True on successful submit.
         *    @access public
         */
        function clickSubmitById($id) {
            return $this->_browser->clickSubmitById($id);
        }
        
        /**
         *    Clicks the submit image by some kind of label. Usually
         *    the alt tag or the nearest equivalent. The owning
         *    form will be submitted by this. Clicking outside of
         *    the boundary of the coordinates will result in
         *    a failure.
         *    @param string $label   Alt attribute of button.
         *    @param integer $x      X-coordinate of imaginary click.
         *    @param integer $y      Y-coordinate of imaginary click.
         *    @return boolean        True on successful submit.
         *    @access public
         */
        function clickImage($label, $x = 1, $y = 1) {
            return $this->_browser->clickImage($label, $x, $y);
        }
        
        /**
         *    Clicks the submit image by the name. Usually
         *    the alt tag or the nearest equivalent. The owning
         *    form will be submitted by this. Clicking outside of
         *    the boundary of the coordinates will result in
         *    a failure.
         *    @param string $name    Name attribute of button.
         *    @param integer $x      X-coordinate of imaginary click.
         *    @param integer $y      Y-coordinate of imaginary click.
         *    @return boolean        True on successful submit.
         *    @access public
         */
        function clickImageByName($name, $x = 1, $y = 1) {
            return $this->_browser->clickImageByName($name, $x, $y);
        }
        
        /**
         *    Clicks the submit image by ID attribute. The owning
         *    form will be submitted by this. Clicking outside of
         *    the boundary of the coordinates will result in
         *    a failure.
         *    @param integer/string $id   ID attribute of button.
         *    @param integer $x           X-coordinate of imaginary click.
         *    @param integer $y           Y-coordinate of imaginary click.
         *    @return boolean             True on successful submit.
         *    @access public
         */
        function clickImageById($id, $x = 1, $y = 1) {
            return $this->_browser->clickImageById($id, $x, $y);
        }
        
        /**
         *    Submits a form by the ID.
         *    @param string $id    Form ID. No button information
         *                         is submitted this way.
         *    @return boolean      True on success.
         *    @access public
         */
        function submitFormById($id) {
            return $this->_browser->submitFormById($id);
        }
        
        /**
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given. Match is case insensitive
         *    with normalised space.
         *    @param string $label     Text between the anchor tags.
         *    @param integer $index    Link position counting from zero.
         *    @return boolean          True if link present.
         *    @access public
         */
        function clickLink($label, $index = 0) {
            return $this->_browser->clickLink($label, $index);
        }
        
        /**
         *    Follows a link by id attribute.
         *    @param string $id        ID attribute value.
         *    @return boolean          True if successful.
         *    @access public
         */
        function clickLinkById($id) {
            return $this->_browser->clickLinkById($id);
        }
        
        /**
         *    Tests for the presence of a link label. Match is
         *    case insensitive with normalised space.
         *    @param string $label     Text between the anchor tags.
         *    @param string $message   Message to display. Default
         *                             can be embedded with %s.
         *    @return boolean          True if link present.
         *    @access public
         */
        function assertLink($label, $message = "%s") {
            $this->assertTrue(
                    $this->_browser->isLink($label),
                    sprintf($message, "Link [$label] should exist"));
        }
        
        /**
         *    Tests for the presence of a link id attribute.
         *    @param string $id        Id attribute value.
         *    @param string $message   Message to display. Default
         *                             can be embedded with %s.
         *    @return boolean          True if link present.
         *    @access public
         */
        function assertLinkById($id, $message = "%s") {
            $this->assertTrue(
                    $this->_browser->isLinkById($id),
                    sprintf($message, "Link ID [$id] should exist"));
        }
        
        /**
         *    Sets all form fields with that name.
         *    @param string $name    Name of field in forms.
         *    @param string $value   New value of field.
         *    @return boolean        True if field exists, otherwise false.
         *    @access public
         */
        function setField($name, $value) {
            return $this->_browser->setField($name, $value);
        }
          
        /**
         *    Sets all form fields with that name.
         *    @param string/integer $id   Id of field in forms.
         *    @param string $value        New value of field.
         *    @return boolean             True if field exists, otherwise false.
         *    @access public
         */
        function setFieldById($id, $value) {
            return $this->_browser->setFieldById($id, $value);
        }
        
        /**
         *    Confirms that the form element is currently set
         *    to the expected value. A missing form will always
         *    fail. If no value is given then only the existence
         *    of the field is checked.
         *    @param string $name       Name of field in forms.
         *    @param mixed $expected    Expected string/aray value or
         *                              false for unset fields.
         *    @param string $message    Message to display. Default
         *                              can be embedded with %s.
         *    @access public
         */
        function assertField($name, $expected = true, $message = "%s") {
            $value = $this->_browser->getField($name);
            if ($expected === true) {
                $this->assertTrue(
                        isset($value),
                        sprintf($message, "Field [$name] should exist"));
            } else {
                $this->assertExpectation(
                        new IdenticalExpectation($expected),
                        $value,
                        sprintf($message, "Field [$name] should match with [%s]"));
            }
        }
         
        /**
         *    Confirms that the form element is currently set
         *    to the expected value. A missing form will always
         *    fail. If no ID is given then only the existence
         *    of the field is checked.
         *    @param string/integer $id  Name of field in forms.
         *    @param mixed $expected     Expected string/aray value or
         *                               false for unset fields.
         *    @param string $message     Message to display. Default
         *                               can be embedded with %s.
         *    @access public
         */
        function assertFieldById($id, $expected = true, $message = "%s") {
            $value = $this->_browser->getFieldById($id);
            if ($expected === true) {
                $this->assertTrue(
                        isset($value),
                        sprintf($message, "Field of ID [$id] should exist"));
            } else {
                $this->assertExpectation(
                        new IdenticalExpectation($expected),
                        $value,
                        sprintf($message, "Field of ID [$id] should match with [%s]"));
            }
        }
       
        /**
         *    Checks the response code against a list
         *    of possible values.
         *    @param array $responses    Possible responses for a pass.
         *    @param string $message     Message to display. Default
         *                               can be embedded with %s.
         *    @access public
         */
        function assertResponse($responses, $message = '%s') {
            $responses = (is_array($responses) ? $responses : array($responses));
            $code = $this->_browser->getResponseCode();
            $message = sprintf($message, "Expecting response in [" .
                    implode(", ", $responses) . "] got [$code]");
            $this->assertTrue(in_array($code, $responses), $message);
        }
        
        /**
         *    Checks the mime type against a list
         *    of possible values.
         *    @param array $types      Possible mime types for a pass.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertMime($types, $message = '%s') {
            $types = (is_array($types) ? $types : array($types));
            $type = $this->_browser->getMimeType();
            $message = sprintf($message, "Expecting mime type in [" .
                    implode(", ", $types) . "] got [$type]");
            $this->assertTrue(in_array($type, $types), $message);
        }
        
        /**
         *    Attempt to match the authentication type within
         *    the security realm we are currently matching.
         *    @param string $authentication   Usually basic.
         *    @param string $message          Message to display.
         *    @access public
         */
        function assertAuthentication($authentication = false, $message = '%s') {
            if (! $authentication) {
                $message = sprintf($message, "Expected any authentication type, got [" .
                        $this->_browser->getAuthentication() . "]");
                $this->assertTrue($this->_browser->getAuthentication(), $message);
            } else {
                $message = sprintf($message, "Expected authentication [$authentication] got [" .
                        $this->_browser->getAuthentication() . "]");
                $this->assertTrue(
                        strtolower($this->_browser->getAuthentication()) == strtolower($authentication),
                        $message);
            }
        }
        
        /**
         *    Checks that no authentication is necessary to view
         *    the desired page.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertNoAuthentication($message = '%s') {
            $message = sprintf($message, "Expected no authentication type, got [" .
                    $this->_browser->getAuthentication() . "]");
            $this->assertFalse($this->_browser->getAuthentication(), $message);
        }
        
        /**
         *    Attempts to match the current security realm.
         *    @param string $realm     Name of security realm.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertRealm($realm, $message = '%s') {
            $message = sprintf($message, "Expected realm [$realm] got [" .
                    $this->_browser->getRealm() . "]");
            $this->assertTrue(
                    strtolower($this->_browser->getRealm()) == strtolower($realm),
                    $message);
        }
        
        /**
         *    Tests the text between the title tags.
         *    @param string $title     Expected title or empty
         *                             if expecting no title.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertTitle($title = false, $message = '%s') {
            $this->assertTrue(
                    $title === $this->_browser->getTitle(),
                    sprintf(
                            $message,
                            "Expecting title [$title] got [" . $this->_browser->getTitle() . "]"));
        }
        
        /**
         *    Will trigger a pass if the Perl regex pattern
         *    is found in the raw content.
         *    @param string $pattern    Perl regex to look for including
         *                              the regex delimiters.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertWantedPattern($pattern, $message = '%s') {
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    $this->_browser->getContent(),
                    $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in raw content.
         *    @param string $pattern    Perl regex to look for including
         *                              the regex delimiters.
         *    @param string $message    Message to display.
         *    @access public
         */
        function assertNoUnwantedPattern($pattern, $message = "%s") {
            $this->assertExpectation(
                    new UnwantedPatternExpectation($pattern),
                    $this->_browser->getContent(),
                    $message);
        }
        
        /**
         *    Checks that a cookie is set for the current page
         *    and optionally checks the value.
         *    @param string $name        Name of cookie to test.
         *    @param string $expected    Expected value as a string or
         *                               false if any value will do.
         *    @param string $message     Message to display.
         *    @access public
         */
        function assertCookie($name, $expected = false, $message = "%s") {
            $value = $this->_browser->getBaseCookieValue($name);
            if ($expected) {
                $this->assertTrue($value === $expected, sprintf(
                        $message,
                        "Expecting cookie [$name] value [$expected], got [$value]"));
            } else {
                $this->assertTrue(
                        $value,
                        sprintf($message, "Expecting cookie [$name]"));
            }
        }
        
        /**
         *    Checks that no cookie is present or that it has
         *    been successfully cleared.
         *    @param string $name        Name of cookie to test.
         *    @param string $message     Message to display.
         *    @access public
         */
        function assertNoCookie($name, $message = "%s") {
            $this->assertTrue(
                    $this->_browser->getBaseCookieValue($name) === false,
                    sprintf($message, "Not expecting cookie [$name]"));
        }
    }
?>