<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'parser.php');
    require_once(SIMPLE_TEST . 'tag.php');
    
    /**
     *    SAX event handler. Maintains a list of
     *    open tags and dispatches them as they close.
     */
    class SimplePageBuilder extends SimpleSaxListener {
        var $_page;
        var $_tags;
        
        /**
         *    Sets the document to write to.
         *    @param $page       Target of the events.
         *    @public
         */
        function SimplePageBuilder(&$page) {
            $this->SimpleSaxListener();
            $this->_page = &$page;
            $this->_tags = array();
        }
        
        /**
         *    Reads the raw content and send events
         *    into the page to be built.
         *    @param $raw        Unparsed text.
         *    @param $parser     Event generator.
         *    @public
         */
        function parse($raw, &$parser) {
            return $parser->parse($raw);
        }
        
        /**
         *    Start of element event. Opens a new tag.
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
            array_push($this->_tags[$name], new SimpleTag($name, $attributes));
            return true;
        }
        
        /**
         *    End of element event. An unexpected event
         *    triggers a parsing error.
         *    @param $name        Element name.
         *    @return             False on parse error.
         *    @public
         */
        function endElement($name) {
            if (!isset($this->_tags[$name]) || (count($this->_tags[$name]) == 0)) {
                return false;
            }
            $tag = array_pop($this->_tags[$name]);
            $this->_page->acceptTag($tag);
            return true;
        }
        
        /**
         *    Unparsed, but relevant data. The data is added
         *    to every open tag.
         *    @param $text        May include unparsed tags.
         *    @return             False on parse error.
         *    @public
         */
        function addContent($text) {
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0; $i < count($this->_tags[$name]); $i++) {
                    $this->_tags[$name][$i]->addContent($text);
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
        var $_link_ids;
        var $_title;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @param $raw            Raw unparsed text.
         *    @public
         */
        function SimplePage($raw) {
            $this->_absolute_links = array();
            $this->_relative_links = array();
            $this->_link_ids = array();
            $this->_title = false;
            $builder = &$this->_createBuilder($this);
            $builder->parse($raw, $this->_createParser($builder));
        }
        
        /**
         *    Creates the parser used with the builder.
         *    @param $builder    Parser listener.
         *    @return            Parser to generate events for
         *                       the builder.
         *    @protected
         */
        function &_createParser(&$builder) {
            return new SimpleSaxParser($builder);
        }
        
        /**
         *    Creates the parser used with the builder.
         *    @param $page   Target of incoming tag information.
         *    @return        Builder to feed events to this page.
         *    @protected
         */
        function &_createBuilder(&$page) {
            return new SimplePageBuilder($page);
        }
        
        /**
         *    Adds a tag to the page.
         *    @param $tag        Tag to accept.
         *    @public
         */
        function acceptTag($tag) {
            if ($tag->getName() == "a") {
                $this->_addLink(
                        $tag->getAttribute("href"),
                        $tag->getContent(),
                        $tag->getAttribute("id"));
            } elseif ($tag->getName() == "title") {
                $this->_setTitle($tag->getContent());
            }
        }
        
        /**
         *    Adds a link to the page. Partially fixes
         *    expandomatic links.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @param $id         Id attribute of link.
         *    @protected
         */
        function _addLink($url, $label, $id) {
            $parsed_url = new SimpleUrl($url);
            if ($parsed_url->getScheme() && $parsed_url->getHost()) {
                $this->_addAbsoluteLink($url, $label, $id);
                return;
            }
            if (!$parsed_url->getScheme()) {
                if (!preg_match('/^(\/|\.\/|\.\.\/)/', $url)) {
                    $url = "./" . $url;
                    $parsed_url = new SimpleUrl($url);
                }
            }
            if (!$parsed_url->getHost()) {
                $this->_addRelativeLink($url, $label, $id);
            }
        }
        
        /**
         *    Adds an absolute link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @param $id         Id attribute of link.
         *    @private
         */
        function _addAbsoluteLink($url, $label, $id) {
            $this->_addLinkId($url, $id);
            if (!isset($this->_absolute_links[$label])) {
                $this->_absolute_links[$label] = array();
            }
            array_push($this->_absolute_links[$label], $url);
        }
        
        /**
         *    Adds a relative link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @param $id         Id attribute of link.
         *    @private
         */
        function _addRelativeLink($url, $label, $id) {
            $this->_addLinkId($url, $id);
            if (!isset($this->_relative_links[$label])) {
                $this->_relative_links[$label] = array();
            }
            array_push($this->_relative_links[$label], $url);
        }
        
        /**
         *    Adds a URL by id attribute.
         *    @param $url        Address.
         *    @param $id         Id attribute of link.
         *    @private
         */
        function _addLinkId($url, $id) {
            if (!($id === false)) {
                $this->_link_ids[(integer)$id] = $url;
            }
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return       List of urls with scheme of
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
         *    Accessor for a list of all relative links.
         *    @return       List of urls without hostname.
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
         *    @return          List of links with that label.
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
        
        /**
         *    Accessor for a URL by the id attribute.
         *    @param $id       Id attribute of link.
         *    @return          URL as string with that id.
         *    @public
         */
        function getUrlById($id) {
            if (in_array($id, array_keys($this->_link_ids))) {
                return $this->_link_ids[$id];
            }
            return false;
        }
        
        /**
         *    Sets the title tag contents.
         *    @param $title        Title of page.
         *    @protected
         */
        function _setTitle($title) {
            $this->_title = $title;
        }
        
        /**
         *    Accessor for parsed title.
         *    @return        Title as string or boolean
         *                   false if no title is present.
         *    @public
         */
        function getTitle() {
            return $this->_title;
        }
    }
?>