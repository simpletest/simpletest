<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'simple_test.php');
    
    /**
     *    Test case for testing of web pages. Allows
     *    fetching of pages, parsing of HTML and
     *    submitting forms.
     */
    class WebTestCase extends SimpleTestCase {
        var $_current_browser;
        var $_current_content;
        
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
         *    Sets up a browser for the start of the
         *    test method.
         */
        function _testMethodStart($method) {
            parent::_testMethodStart($method);
            $this->_current_content = false;
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