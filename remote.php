<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'xml.php');
    require_once(SIMPLE_TEST . 'simple_test.php');

    /**
     *    Runs an XML formated test on a remote server.
     */
    class RemoteTestCase extends RunnableTest {
        var $_url;
        
        /**
         *    Sets the location of the remote test.
         *    @param string $url        Test location.
         *    @param string $label      Name of test. Will default
         *                              to the URL if omitted.
         *    @access public
         */
        function RemoteTestCase($url, $label = false) {
            $this->RunnableTest($label ? $label : $url);
            $this->_url = $url;
        }

        /**
         *    Runs the top level test for this class.
         *    @param SimpleReporter $reporter    Target of test results.
         *    @returns boolean                   True if no failures.
         *    @access public
         */
        function run(&$reporter) {
            $browser = &$this->_createBrowser();
            $xml = $browser->get($this->_url);
            if (! $xml) {
                trigger_error('Cannot read remote test URL [' . $this->_url . ']');
                return false;
            }
            $parser = &$this->_createParser($reporter);
            if (! $parser->parse($xml)) {
                trigger_error('Cannot parse incoming XML from [' . $this->_url . ']');
                return false;
            }
            return true;
        }
        
        /**
         *    Creates a new web browser object for fetching
         *    the XML report.
         *    @return SimpleBrowser           New browser.
         *    @access protected
         */
        function &_createBrowser() {
            return new SimpleBrowser();
        }
        
        /**
         *    Creates the XML parser.
         *    @param SimpleReporter $reporter    Target of test results.
         *    @access protected
         */
        function &_createParser(&$reporter) {
            return new SimpleXmlImporter($reporter);
        }
    }
?>