<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
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
         *    @param $label      Name of test case. Will use
         *                       the class name if none specified.
         *    @public
         */
        function WebTestCase($label = false) {
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Gets a current browser reference for setting
         *    special expectations or for detailed
         *    examination of page fetches.
         *    @param $browser        Test browser object.
         *    @public
         */
        function &getBrowser() {
            return $this->_current_browser;
        }
        
        /**
         *    Creates a new default web browser object.
         *    Will be cleared at the end of the test method.
         *    @return            New TestBrowser object.
         *    @public
         */
        function &createBrowser() {
            return new TestBrowser($this);
        }
        
        /**
         *    Creates a new default page builder object and
         *    uses it to parse the current content. Caches
         *    the page content once it is parsed.
         *    @return            New SimplePage object.
         *    @private
         */
        function &_getHtml() {
            if (!$this->_html_cache) {
                $this->_html_cache = &new SimplePage($this->_current_content);
            }
            return $this->_html_cache;
        }
        
        /**
         *    Resets the parsed HTML page cache.
         *    @private
         */
        function _clearHtmlCache() {
            $this->_html_cache = false;
        }
        
        /**
         *    Sets up a browser for the start of the
         *    test method.
         *    @param $method        Name of test method.
         *    @private
         */
        function _testMethodStart($method) {
            parent::_testMethodStart($method);
            $this->_current_content = false;
            $this->_clearHtmlCache();
            $this->_current_browser = &$this->createBrowser();
        }
        
        /**
         *    Fetches a page into the page buffer. If
         *    there is no base for the URL then the
         *    current base URL is used. All other context
         *    remains the same.
         *    @param $url        URL to fetch.
         *    @public
         */
        function fetch($url) {
            $this->_current_content = $this->_current_browser->fetchContent($url);
            $this->_clearHtmlCache();
        }
        
        /**
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given.
         *    @param $label     Text between the anchor tags.
         *    @param $index     Link position counting from zero.
         *    @return           True if link present.
         *    @public
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
            $this->fetch($urls[$index]);
            return true;
        }
        
        /**
         *    Follows a link by name. Will click the first link
         *    found with this link text by default, or a later
         *    one if an index is given.
         *    @param $id        ID attribute value.
         *    @return           True if link present.
         *    @public
         */
        function clickLinkId($id) {
            $page = &$this->_getHtml();
            if (!($url = $page->getUrlById($id))) {
                return false;
            }
            $this->fetch($url);
            return true;
        }
        
        /**
         *    Tests the text between the title tags.
         *    @param $title          Expected title or empty
         *                           if expecting no title.
         *    @param $message        Message to display.
         *    @public
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
         *    @public
         */
        function assertWantedPattern($pattern, $message = "%s") {
            $this->assertAssertion(
                    new WantedPatternAssertion($pattern),
                    $this->_current_content,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in raw content.
         *    @param $pattern        Perl regex to look for including
         *                           the regex delimiters.
         *    @param $message        Message to display.
         *    @public
         */
        function assertNoUnwantedPattern($pattern, $message = "%s") {
            $this->assertAssertion(
                    new UnwantedPatternAssertion($pattern),
                    $this->_current_content,
                    $message);
        }
    }
?>