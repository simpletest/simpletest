<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    Originally defined in simple_test.php file.
     */
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'page.php');
    
    /**
     *    A composite page. Wraps a frameset page and
     *    loads subframes. The original page will be
     *    mostly ignored. Implements the SimplePage
     *    interface so as to be interchangeable.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFrameset {
        var $_frameset;
        
        /**
         *    Stashes the frameset page. Will make use of the
         *    browser to fetch the sub frames recusively.
         *    @param SimplePage $page        Frameset page.
         */
        function SimpleFrameset(&$page) {
            $this->_frameset = &$page;
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