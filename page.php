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
    class SimplePageBuilder extends SimpleSaxListener {
        var $_page;
        var $_tags;
        
        /**
         *    Sets the document to write to.
         *    @param $parser     Event generator.
         *    @param $page       Target of the events.
         *    @public
         */
        function SimplePageBuilder(&$parser, &$page) {
            $this->SimpleSaxListener($parser);
            $this->_page = &$page;
            $this->_tags = array();
        }
        
        /**
         *    Reads the raw content and send events
         *    into the page to be built.
         *    @param $raw        Unparsed text.
         *    @public
         */
        function parse($raw) {
            $parser = &$this->getParser();
            return $parser->parse($raw);
        }
        
        /**
         *    Start of element event.
         *    @param $name        Element name.
         *    @param $attributes  Hash of name value pairs.
         *                        Attributes without content
         *                        are marked as true.
         *    @return             False on parse error.
         *    @public
         */
        function startElement($name, $attributes) {
            if (!in_array($name, array_keys($this->_tags))) {
                $this->_tags[$name] = array();
            }
            array_push($this->_tags[$name], array(
                    "attributes" => $attributes,
                    "content" => ""));
            return true;
        }
        
        /**
         *    End of element event.
         *    @param $name        Element name.
         *    @return             False on parse error.
         *    @public
         */
        function endElement($name) {
            $tag = array_pop($this->_tags[$name]);
            $this->_page->addLink($tag["attributes"]["href"], $tag["content"]);
            return true;
        }
        
        /**
         *    Unparsed, but relevant data.
         *    @param $text        May include unparsed tags.
         *    @return             False on parse error.
         *    @public
         */
        function addContent($text) {
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0; $i < count($this->_tags[$name]); $i++) {
                    $this->_tags[$name][$i]["content"] .= $text;
                }
            }
            return true;
        }
    }
    
    /**
     *    A wrapper for a web page.
     */
    class SimplePage {
        var $_absolute_links;
        var $_relative_links;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @param $raw            Raw unparsed text.
         *    @public
         */
        function SimplePage($raw) {
            $this->_absolute_links = array();
            $this->_relative_links = array();
        }
        
        /**
         *    Sets up the parser ready to parse itself.
         *    @protected
         */
        function _createParser() {
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