<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'page.php');
    require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'user_agent.php');
    /**#@-*/
    
    /**
     *    Builds composite pages.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFramesetBuilder {
        var $_builder;
        
        /**
         *    Sets the parser for HTML pages.
         *    @param SimplePageBuilder $builder    Parser.
         */
        function SimpleFramesetBuilder(&$builder) {
            $this->_builder = &$builder;
        }
        
        /**
         *    Parses the frames into pages and adds them
         *    to a composite frame set.
         *    @param SimplePage $page             Initial framset page.
         *    @param SimpleUserAgent $user_agent  Current user agent to
         *                                        fetch the pages with.
         *    @return SimnpleFrameset             Composite page.
         *    @access public
         */
        function &fetch(&$page, &$user_agent) {
            $frameset = &new SimpleFrameset($page);
            return $frameset;
        }
    }
    
    /**
     *    A composite page. Wraps a frameset page and
     *    adds subframes. The original page will be
     *    mostly ignored. Implements the SimplePage
     *    interface so as to be interchangeable.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFrameset {
        var $_frameset;
        var $_frames;
        
        /**
         *    Stashes the frameset page. Will make use of the
         *    browser to fetch the sub frames recursively.
         *    @param SimplePage $page        Frameset page.
         */
        function SimpleFrameset(&$page) {
            $this->_frameset = &$page;
            $this->_frames = array();
        }
        
        /**
         *    Adds a parsed page to the frameset.
         *    @param SimplePage $page        Frame page.
         *    @access public
         */
        function addFrame(&$page) {
            $this->_frames[] = &$page;
        }
        
        /**
         *    Accessor for raw text of either all the pages or
         *    the frame in focus.
         *    @return string        Raw unparsed content.
         *    @access public
         */
        function getRaw() {
            $raw = '';
            for ($i = 0; $i < count($this->_frames); $i++) {
                $raw .= $this->_frames[$i]->getRaw();
            }
            return $raw;
        }
        
        /**
         *    Accessor for parsed title.
         *    @return string     Title or false if no title is present.
         *    @access public
         */
        function getTitle() {
            return $this->_frameset->getTitle();
        }
    }
?>