<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
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
         *    @param SimplePage $page       Target of the events.
         *    @access public
         */
        function SimplePageBuilder(&$page) {
            $this->SimpleSaxListener();
            $this->_page = &$page;
            $this->_tags = array();
        }
        
        /**
         *    Reads the raw content and send events
         *    into the page to be built.
         *    @param string $raw                 Unparsed text.
         *    @param SimpleSaxParser $parser     Event generator.
         *    @access public
         */
        function parse($raw, &$parser) {
            return $parser->parse($raw);
        }
        
        /**
         *    Start of element event. Opens a new tag.
         *    @param string $name         Element name.
         *    @param hash $attributes     Attributes without content
         *                                are marked as true.
         *    @return boolean             False on parse error.
         *    @access public
         */
        function startElement($name, $attributes) {
            $tag = &$this->_createTag($name, $attributes);
            if ($name =='form') {
                $this->_page->acceptFormStart($tag);
                return true;
            }            
            if ($tag->expectEndTag()) {
                $this->_openTag($tag);
                return true;
            }
            $this->_page->acceptTag($tag);
            return true;
        }
        
        /**
         *    End of element event.
         *    @param string $name        Element name.
         *    @return boolean            False on parse error.
         *    @access public
         */
        function endElement($name) {
            if ($name == 'form') {
                $this->_page->acceptFormEnd();
                return true;
            }            
            if (isset($this->_tags[$name]) && (count($this->_tags[$name]) > 0)) {
                $tag = array_pop($this->_tags[$name]);
                $this->_addContentTagToOpenTags($tag);
                $this->_page->acceptTag($tag);
                return true;
            }
            return true;
        }
        
        /**
         *    Unparsed, but relevant data. The data is added
         *    to every open tag.
         *    @param string $text        May include unparsed tags.
         *    @return boolean            False on parse error.
         *    @access public
         */
        function addContent($text) {
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0; $i < count($this->_tags[$name]); $i++) {
                    $this->_tags[$name][$i]->addContent($text);
                }
            }
            return true;
        }
        
        /**
         *    Parsed relevant data. The parsed tag is added
         *    to every open tag.
         *    @param SimpleTag $tag        May include unparsed tags.
         *    @access private
         */
        function _addContentTagToOpenTags(&$tag) {
            if (! in_array($tag->getTagName(), array('option'))) {
                return;
            }
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0; $i < count($this->_tags[$name]); $i++) {
                    $this->_tags[$name][$i]->addTag($tag);
                }
            }
        }
        
        /**
         *    Opens a tag for receiving content.
         *    @param SimpleTag $tag        New content tag.
         *    @access private
         */
        function _openTag(&$tag) {
            $name = $tag->getTagName();
            if (! in_array($name, array_keys($this->_tags))) {
                $this->_tags[$name] = array();
            }
            array_push($this->_tags[$name], $tag);
        }
        
        /**
         *    Factory for the tag objects. Creates the
         *    appropriate tag object for the incoming tag name.
         *    @param string $name        HTML tag name.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createTag($name, $attributes) {
            if ($name == 'a') {
                return new SimpleAnchorTag($attributes);
            } elseif ($name == 'title') {
                return new SimpleTitleTag($attributes);
            } elseif ($name == 'input') {
                if (isset($attributes['type']) && ($attributes['type'] == 'submit')) {
                    return new SimpleSubmitTag($attributes);
                } else {
                    return new SimpleTextTag($attributes);
                }
            } elseif ($name == 'textarea') {
                return new SimpleTextAreaTag($attributes);
            } elseif ($name == 'select') {
                return new SimpleSelectionTag($attributes);
            } elseif ($name == 'option') {
                return new SimpleOptionTag($attributes);
            } elseif ($name == 'form') {
                return new SimpleFormTag($attributes);
            }
            return new SimpleTag($attributes);
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
        var $_open_forms;
        var $_closed_forms;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @param string $raw            Raw unparsed text.
         *    @access public
         */
        function SimplePage($raw) {
            $this->_absolute_links = array();
            $this->_relative_links = array();
            $this->_link_ids = array();
            $this->_title = false;
            $this->_open_forms = array();
            $this->_closed_forms = array();
            $builder = &$this->_createBuilder($this);
            $builder->parse($raw, $this->_createParser($builder));
        }
        
        /**
         *    Creates the parser used with the builder.
         *    @param SimplePageBuilder $builder    Parser listener.
         *    @return SimpleSaxParser              Parser to generate events for
         *                                         the builder.
         *    @access protected
         */
        function &_createParser(&$builder) {
            return new SimpleSaxParser($builder);
        }
        
        /**
         *    Creates the parser used with the builder.
         *    @param SimplePage $page      Target of incoming tag information.
         *    @return SimplePageBuilder    Builder to feed events to this page.
         *    @access protected
         */
        function &_createBuilder(&$page) {
            return new SimplePageBuilder($page);
        }
        
        /**
         *    Adds a tag to the page.
         *    @param SimpleTag $tag        Tag to accept.
         *    @access public
         */
        function acceptTag(&$tag) {
            if ($tag->getTagName() == "a") {
                $this->_addLink(
                        $tag->getAttribute("href"),
                        $tag->getContent(),
                        $tag->getAttribute("id"));
            } elseif ($tag->getTagName() == "title") {
                $this->_setTitle($tag);
            } elseif ($this->_isFormElement($tag->getTagName())) {
                for ($i = 0; $i < count($this->_open_forms); $i++) {
                    $this->_open_forms[$i]->addWidget($tag);
                }
            }
        }
        
        /**
         *    Tests to see if a tag is a possible form
         *    element.
         *    @param string $name     HTML element name.
         *    @return boolean         True if form element.
         *    @access private
         */
        function _isFormElement($name) {
            return in_array($name, array('input', 'textarea', 'select'));
        }
        
        /**
         *    Opens a form.
         *    @param SimpleFormTag $tag      Tag to accept.
         *    @access public
         */
        function acceptFormStart(&$tag) {
            $this->_open_forms[] = &new SimpleForm($tag);
        }
        
        /**
         *    Closes the most recently opened form.
         *    @access public
         */
        function acceptFormEnd() {
            $this->_closed_forms[] = array_pop($this->_open_forms);
        }
        
        /**
         *    Adds a link to the page. Partially fixes
         *    expandomatic links.
         *    @param string $url        Address.
         *    @param string $label      Text label of link.
         *    @param string $id         Id attribute of link.
         *    @access protected
         */
        function _addLink($url, $label, $id) {
            $parsed_url = new SimpleUrl($url);
            if ($parsed_url->getScheme() && $parsed_url->getHost()) {
                $this->_addAbsoluteLink($url, $label, $id);
                return;
            }
            $this->_addRelativeLink($url, $label, $id);
        }
        
        /**
         *    Adds an absolute link to the page.
         *    @param SimpleUrl $url    Address.
         *    @param string $label     Text label of link.
         *    @param string $id        Id attribute of link.
         *    @access private
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
         *    @param SimpleUrl $url     Address.
         *    @param string $label      Text label of link.
         *    @param string $id         Id attribute of link.
         *    @access private
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
         *    @param SimpleUrl $url     Address.
         *    @param string $id         Id attribute of link.
         *    @access private
         */
        function _addLinkId($url, $id) {
            if ($id !== false) {
                $this->_link_ids[(string)$id] = $url;
            }
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return array   List of urls with scheme of
         *                    http or https and hostname.
         *    @access public
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
         *    @return array      List of urls without hostname.
         *    @access public
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
         *    @param string $label    Text of link.
         *    @return array           List of links with that label.
         *    @access public
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
         *    @param string $id       Id attribute of link.
         *    @return string          URL with that id.
         *    @access public
         */
        function getUrlById($id) {
            if (in_array((string)$id, array_keys($this->_link_ids))) {
                return $this->_link_ids[(string)$id];
            }
            return false;
        }
        
        /**
         *    Sets the title tag contents.
         *    @param SimpleTitleTag $tag    Title of page.
         *    @access protected
         */
        function _setTitle(&$tag) {
            $this->_title = &$tag;
        }
        
        /**
         *    Accessor for parsed title.
         *    @return string     Title or false if no title is present.
         *    @access public
         */
        function getTitle() {
            if ($this->_title) {
                return $this->_title->getContent();
            }
            return false;
        }
        
        /**
         *    Gets a list of all of the held forms.
         *    @return array       Array of SimpleForm objects.
         *    @access public
         */
        function getForms() {
            return array_merge($this->_open_forms, $this->_closed_forms);
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms.
         *    @param string $label       Button label, default 'Submit'.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitLabel($label) {
            for ($i = 0; $i < count($this->_closed_forms); $i++) {
                if ($this->_closed_forms[$i]->getSubmitName($label)) {
                    return $this->_closed_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by the form ID. A way of
         *    identifying a specific form when we have control
         *    of the HTML code.
         *    @param string $id     Form label.
         *    @return SimpleForm    Form object containing the matching ID.
         *    @access public
         */
        function &getFormById($id) {
            for ($i = 0; $i < count($this->_closed_forms); $i++) {
                if ($this->_closed_forms[$i]->getId() == $id) {
                    return $this->_closed_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Sets a field on each form in which the field is
         *    available.
         *    @param string $name        Field name.
         *    @param string $value       Value to set field to.
         *    @access public
         */
        function setField($name, $value) {
            $is_set = false;
            for ($i = 0; $i < count($this->_closed_forms); $i++) {
                if ($this->_closed_forms[$i]->setField($name, $value)) {
                    $is_set = true;
                }
            }
            return $is_set;
        }
    }
?>