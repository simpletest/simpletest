<?php
    /**
     *	Base include file for SimpleTest.
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    Originally defined in simple_test.php file.
     */
    if (!defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'page.php');
    
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
         *    Dumps the current HTML source for debugging.
         *    @access public
         */
        function showSource() {
            $this->dump(htmlentities($this->_browser->getContent()));
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
         *    @param TestBrowser $browser    Test browser object.
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
         *    Clicks the submit button by label. The owning
         *    form will be submitted by this.
         *    @param string $label    Button label. An unlabeled
         *                            button can be triggered by 'Submit'.
         *    @return boolean         true on success.
         *    @access public
         */
        function clickSubmit($label = "Submit") {
            return $this->_browser->clickSubmit($label);
        }
        
        /**
         *    Submits a form by the ID.
         *    @param string $label    Button label. An unlabeled
         *                            button can be triggered by 'Submit'.
         *    @return boolean         true on success.
         *    @access public
         */
        function submitFormById($id) {
            return $this->_browser->submitFormById($id);
        }
        
        /**
         *    @deprecated
         */
        function clickSubmitByFormId($id) {
            return $this->submitFormById($id);
        }
        
        /**
         *    @deprecated
         */
        function submit($label = 'Submit') {
            $this->clickSubmit($label);
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
            return $this->_browser->clickLink($label, $index);
        }
        
        /**
         *    Follows a link by id attribute.
         *    @param string $id        ID attribute value.
         *    @return boolean          True if link present.
         *    @access public
         */
        function clickLinkById($id) {
            return $this->_browser->clickLinkById($id);
        }
        
        /**
         *    @deprecated
         */
        function clickLinkId($id) {
            return clickLinkById($id);
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
         *    Confirms that the form element is currently set
         *    to the expected value. A missing form will always
         *    fail. If no value is given then only the existence
         *    of the field is checked.
         *    @param string $name       Name of field in forms.
         *    @param mixed $expected    Expected string/aray value or
         *                              false for unset fields.
         *    @access public
         */
        function assertField($name, $expected = true) {
            $value = $this->_browser->getField($name);
            if ($expected === true) {
                $this->assertTrue(isset($value), "Field [$name] should exist");
            } else {
                $this->assertExpectation(
                        new IdenticalExpectation($expected),
                        $value,
                        "Field [$name] should match with [%s]");
            }
        }
        
        /**
         *    Checks the response code against a list
         *    of possible values.
         *    @param array $responses    Possible responses for a pass.
         *    @access public
         */
        function assertResponse($responses, $message = "%s") {
            $responses = (is_array($responses) ? $responses : array($responses));
            $code = $this->_browser->getResponseCode();
            $message = sprintf($message, "Expecting response in [" .
                    implode(", ", $responses) . "] got [$code]");
            $this->assertTrue(in_array($code, $responses), $message);
        }
        
        /**
         *    Checks the mime type against a list
         *    of possible values.
         *    @param array $types    Possible mime types for a pass.
         *    @access public
         */
        function assertMime($types, $message = "%s") {
            $types = (is_array($types) ? $types : array($types));
            $type = $this->_browser->getMimeType();
            $message = sprintf($message, "Expecting mime type in [" .
                    implode(", ", $types) . "] got [$type]");
            $this->assertTrue(in_array($type, $types), $message);
        }
        
        /**
         *    Tests the text between the title tags.
         *    @param string $title     Expected title or empty
         *                             if expecting no title.
         *    @param string $message   Message to display.
         *    @access public
         */
        function assertTitle($title = false, $message = "%s") {
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
        function assertWantedPattern($pattern, $message = "%s") {
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