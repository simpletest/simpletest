<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'page.php');
    
    /**
     *    Test case for testing of web pages. Allows
     *    fetching of pages, parsing of HTML and
     *    submitting forms.
     */
    class WebTestCase extends SimpleTestCase {
        var $_current_browser;
        var $_current_content;
        var $_html_cache;
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param string $label     Name of test case. Will use
         *                             the class name if none specified.
         *    @access public
         */
        function WebTestCase($label = false) {
            $this->SimpleTestCase($label);
            $this->_clearHtmlCache();
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
            $this->_current_browser->restartSession($date);
        }
        
        /**
         *    Moves cookie expiry times back into the past.
         *    Useful for testing timeouts and expiries.
         *    @param integer $interval    Amount to age in seconds.
         *    @access public
         */
        function ageCookies($interval) {
            $this->_current_browser->ageCookies($interval);
        }
        
        /**
         *    Gets a current browser reference for setting
         *    special expectations or for detailed
         *    examination of page fetches.
         *    @param $browser        Test browser object.
         *    @access public
         */
        function &getBrowser() {
            return $this->_current_browser;
        }
        
        /**
         *    Creates a new default web browser object.
         *    Will be cleared at the end of the test method.
         *    @return            New TestBrowser object.
         *    @access public
         */
        function &createBrowser() {
            return new TestBrowser($this);
        }
        
        /**
         *    Creates a new default page builder object and
         *    uses it to parse the current content. Caches
         *    the page content once it is parsed.
         *    @return            New web page object.
         *    @access private
         */
        function &_getHtml() {
            if (!$this->_html_cache) {
                $this->_html_cache = &new SimplePage($this->_current_content);
            }
            return $this->_html_cache;
        }
        
        /**
         *    Resets the parsed HTML page cache.
         *    @access private
         */
        function _clearHtmlCache() {
            $this->_html_cache = false;
        }
        
        /**
         *    Sets up a browser for the start of each
         *    test method.
         *    @param $method    Name of test method.
         *    @access protected
         */
        function invoke($method) {
            $this->_current_content = false;
            $this->_clearHtmlCache();
            $this->_current_browser = &$this->createBrowser();
            parent::invoke($method);
        }
        
        /**
         *    Sets a cookie in the current browser.
         *    @param $name          Name of cookie.
         *    @param $value         Cookie value as string.
         *    @param $host          Host upon which the cookie is valid.
         *    @param $path          Cookie path if not host wide.
         *    @param $expiry        Expiry date as string.
         *    @access public
         */
        function setCookie($name, $value, $host = false, $path = "/", $expiry = false) {
            $this->_current_browser->setCookie($name, $value, $host, $path, $expiry);
        }
        
        /**
         *    Sets the maximum number of redirects before
         *    the web page is loaded regardless.
         *    @param $max        Maximum hops.
         *    @access public
         */
        function setMaximumRedirects($max) {
            if (!$this->_current_browser) {
                trigger_error(
                        'Can only set maximum redirects in a test method, setUp() or tearDown()');
            }
            $this->_current_browser->setMaximumRedirects($max);
        }
        
        /**
         *    Fetches a page into the page buffer. If
         *    there is no base for the URL then the
         *    current base URL is used. After the fetch
         *    the base URL reflects the new location.
         *    @param $url          URL to fetch.
         *    @param $parameters   Optional additional GET data.
         *    @return              True on success.
         *    @access public
         */
        function get($url, $parameters = false) {
            $this->_current_content = $this->_current_browser->get($url, $parameters);
            $this->_clearHtmlCache();
            return ($this->_current_content !== false);
        }
        
        /**
         *    Fetches a page by POST into the page buffer.
         *    If there is no base for the URL then the
         *    current base URL is used. After the fetch
         *    the base URL reflects the new location.
         *    @param $url          URL to fetch.
         *    @param $parameters   Optional additional GET data.
         *    @return              True on success.
         *    @access public
         */
        function post($url, $parameters = false) {
            $this->_current_content = $this->_current_browser->post($url, $parameters);
            $this->_clearHtmlCache();
            return ($this->_current_content !== false);
        }
        
        /**
         *    Clicks the submit button by label. The owning
         *    form will be submitted by this.
         *    @param $label    Button label. An unlabeled
         *                     button can be triggered by 'Submit'.
         *    @return          true on success.
         *    @access public
         */
        function clickSubmit($label = "Submit") {
            $page = &$this->_getHtml();
            if (! ($form = &$page->getFormBySubmitLabel($label))) {
                return false;
            }
            $action = $form->getAction();
            if (!$action) {
                $action = $this->_current_browser->getCurrentUrl();
            }
            $method = $form->getMethod();
            return $this->$method($action, $form->submitButtonByLabel($label));
        }
        
        /**
         *    Clicks the submit button by label. The owning
         *    form will be submitted by this.
         *    @param $label    Button label. An unlabeled
         *                     button can be triggered by 'Submit'.
         *    @return          true on success.
         *    @access public
         */
        function clickSubmitByFormId($id) {
            $page = &$this->_getHtml();
            if (! ($form = &$page->getFormById($id))) {
                return false;
            }
            $action = $form->getAction();
            if (!$action) {
                $action = $this->_current_browser->getCurrentUrl();
            }
            $method = $form->getMethod();
            return $this->$method($action, $form->submit());
        }
        
        /**
         *    @deprecated
         */
        function submit($label = "Submit") {
            $this->clickSubmit($label);
        }
        
        /**
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given.
         *    @param $label     Text between the anchor tags.
         *    @param $index     Link position counting from zero.
         *    @return           True if link present.
         *    @access public
         */
        function clickLink($label, $index = 0) {
            $page = &$this->_getHtml();
            $urls = $page->getUrls($label);
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
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given.
         *    @param $id        ID attribute value.
         *    @return           True if link present.
         *    @access public
         */
        function clickLinkById($id) {
            $page = &$this->_getHtml();
            if (! ($url = $page->getUrlById($id))) {
                return false;
            }
            $this->get($url);
            return true;
        }
        
        /**
         *    @deprecated
         */
        function clickLinkId($id) {
            return clickLinkById($id);
        }
        
        /**
         *    Sets all form fields with that name.
         *    @param $name    Name of field in forms.
         *    @param $value   New value of field.
         *    @return         True if field exists, otherwise false.
         *    @access public
         */
        function setField($name, $value) {
            $page = &$this->_getHtml();
            $page->setField($name, $value);
        }
        
        /**
         *    Checks the response code against a list
         *    of possible values.
         *    @param $responses    Possible responses for a pass.
         *    @access public
         */
        function assertResponse($responses, $message = "%s") {
            $this->_current_browser->assertResponse($responses, $message);
        }
        
        /**
         *    Checks the mime type against a list
         *    of possible values.
         *    @param $types    Possible mime types for a pass.
         *    @access public
         */
        function assertMime($types, $message = "%s") {
            $this->_current_browser->assertMime($types, $message);
        }
        
        /**
         *    Tests the text between the title tags.
         *    @param $title          Expected title or empty
         *                           if expecting no title.
         *    @param $message        Message to display.
         *    @access public
         */
        function assertTitle($title = false, $message = "%s") {
            $page = &$this->_getHtml();
            $this->assertTrue(
                    $title === $page->getTitle(),
                    sprintf($message, "Expecting title [$title] got [" . $page->getTitle() . "]"));
        }
        
        /**
         *    Will trigger a pass if the Perl regex pattern
         *    is found in the raw content.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $message        Message to display.
         *    @access public
         */
        function assertWantedPattern($pattern, $message = "%s") {
            $this->assertExpectation(
                    new WantedPatternExpectation($pattern),
                    $this->_current_content,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in raw content.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $message        Message to display.
         *    @access public
         */
        function assertNoUnwantedPattern($pattern, $message = "%s") {
            $this->assertExpectation(
                    new UnwantedPatternExpectation($pattern),
                    $this->_current_content,
                    $message);
        }
        
        /**
         *    Checks that a cookie is set for the current page
         *    and optionally checks the value.
         *    @param $name        Name of cookie to test.
         *    @param $expect      Expected value as a string or
         *                        false if any value will do.
         *    @param $message     Message to display.
         *    @access public
         */
        function assertCookie($name, $expect = false, $message = "%s") {
            $value = $this->_current_browser->getBaseCookieValue($name);
            if ($expect) {
                $this->assertTrue($value === $expect, sprintf(
                        $message,
                        "Expecting cookie [$name] value [$expect], got [$value]"));
            } else {
                $this->assertTrue(
                        $value,
                        sprintf($message, "Expecting cookie [$name]"));
            }
        }
        
        /**
         *    Checks that no cookie is present or that it has
         *    been successfully cleared.
         *    @param $name        Name of cookie to test.
         *    @param $message     Message to display.
         *    @access public
         */
        function assertNoCookie($name, $message = "%s") {
            $this->assertTrue(
                    $this->_current_browser->getBaseCookieValue($name) === false,
                    sprintf($message, "Not expecting cookie [$name]"));
        }
        
        /**
         *    Sets an expectation of a cookie being set on the
         *    next fetching operation.
         *    @param $name        Name of cookie to expect.
         *    @param $expect      Expected value as a string or
         *                        false if any value will do.
         *                        An empty string for cookie
         *                        clearing.
         *    @param $message     Message to display.
         *    @access public
         */
        function expectCookie($name, $expect = false, $message = "%s") {
            $this->_current_browser->expectCookie($name, $expect, $message);
        }
    }
?>