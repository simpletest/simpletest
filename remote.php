<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    originally defined in simple_test.php
     */
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'browser.php');
    require_once(SIMPLE_TEST . 'xml.php');
    require_once(SIMPLE_TEST . 'simple_test.php');

    /**
     *    Runs an XML formated test on a remote server.
	 *	  @package SimpleTest
	 *	  @subpackage UnitTester
     */
    class RemoteTestCase extends RunnableTest {
        var $_url;
        var $_dry_url;
        var $_size;
        
        /**
         *    Sets the location of the remote test.
         *    @param string $url       Test location.
         *    @param string $dry_url   Location for dry run.
         *    @access public
         */
        function RemoteTestCase($url, $dry_url = false) {
            $this->RunnableTest($url);
            $this->_url = $url;
            $this->_dry_url = $dry_url ? $dry_url : $url;
            $this->_size = false;
        }

        /**
         *    Runs the top level test for this class. Currently
         *    reads the data as a single chunk. I'll fix this
         *    once I have added iteration to the browser.
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
         *    @return SimpleTestXmlListener      XML reader.
         *    @access protected
         */
        function &_createParser(&$reporter) {
            return new SimpleTestXmlParser($reporter);
        }
        
        /**
         *    Accessor for the number of subtests.
         *    @return integer           Number of test cases.
         *    @access public
         */
        function getSize() {
            if ($this->_size === false) {
                $browser = &$this->_createBrowser();
                $xml = $browser->get($this->_dry_url);
                if (! $xml) {
                    trigger_error('Cannot read remote test URL [' . $this->_dry_url . ']');
                    return false;
                }
                $reporter = &new SimpleReporter();
                $parser = &$this->_createParser($reporter);
                if (! $parser->parse($xml)) {
                    trigger_error('Cannot parse incoming XML from [' . $this->_dry_url . ']');
                    return false;
                }
                $this->_size = $reporter->getTestCaseCount();
            }
            return $this->_size;
        }
    }
?>