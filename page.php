<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'parser.php');
    
    /**
     *    SAX event handler. Maintains a list of
     *    open tags and dispatches them as they close.
     */
    class SimplePageBuilder extends HtmlSaxListener {
        
        /**
         *    Sets the document to write to.
         *    @param $page    Page to add information to.
         *    @public
         */
        function SimplePageBuilder(&$page) {
        }
    }
    
    /**
     *    A wrapper for a web page.
     */
    class HtmlPage {
        var $_absolute_links;
        var $_relative_links;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @public
         */
        function HtmlPage() {
            $this->_absolute_links = array();
            $this->_relative_links = array();
        }
        
        /**
         *    Adds a link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @param $is_strict  Will accept only correct
         *                       relative URLs: must start
         *                       "/", "./" or "../" or have
         *                       a scheme.
         *    @public
         */
        function addLink($url, $label, $is_strict = false) {
            $parsed_url = new SimpleUrl($url);
            if ($parsed_url->getScheme() && $parsed_url->getHost()) {
                $this->_addAbsoluteLink($url, $label);
                return;
            }
            if (!$is_strict && !$parsed_url->getScheme()) {
                if (!preg_match('/^(\/|\.\/|\.\.\/)/', $url)) {
                    $url = "./" . $url;
                    $parsed_url = new SimpleUrl($url);
                }
            }
            if (!$parsed_url->getHost()) {
                $this->_addRelativeLink($url, $label);
            }
        }
        
        /**
         *    Adds an absolute link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @private
         */
        function _addAbsoluteLink($url, $label) {
            if (!isset($this->_absolute_links[$label])) {
                $this->_absolute_links[$label] = array();
            }
            array_push($this->_absolute_links[$label], $url);
        }
        
        /**
         *    Adds a relative link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @private
         */
        function _addRelativeLink($url, $label) {
            if (!isset($this->_relative_links[$label])) {
                $this->_relative_links[$label] = array();
            }
            array_push($this->_relative_links[$label], $url);
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return       List of links with scheme of
         *                  http or https and hostname.
         *    @public
         */
        function getAbsoluteLinks() {
            $all = array();
            foreach ($this->_absolute_links as $label => $links) {
                $all = array_merge($all, $links);
            }
            return $all;
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return       List of links without hostname.
         *    @public
         */
        function getRelativeLinks() {
            $all = array();
            foreach ($this->_relative_links as $label => $links) {
                $all = array_merge($all, $links);
            }
            return $all;
        }
        
        /**
         *    Accessor for a URLs by the link label.
         *    @param $label    Text of link.
         *    @return          List of links with that
         *                     label.
         *    @public
         */
        function getUrls($label) {
            $all = array();
            if (isset($this->_absolute_links[$label])) {
                $all = $this->_absolute_links[$label];
            }
            if (isset($this->_relative_links[$label])) {
                $all = array_merge($all, $this->_relative_links[$label]);
            }
            return $all;
        }
    }
?>