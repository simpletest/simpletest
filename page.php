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
         *    @param string $raw                 Unparsed text.
         *    @param SimpleSaxParser $parser     Event generator.
         *    @public
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
         *    @public
         */
        function startElement($name, $attributes) {
            $tag = &$this->_createTag($name, $attributes);
            if ($name =='form') {
                $this->_page->acceptFormStart($tag);
                return true;
            }            
            if ($tag->expectEndTag()) {
                if (!in_array($name, array_keys($this->_tags))) {
                    $this->_tags[$name] = array();
                }
                array_push($this->_tags[$name], $tag);
                return true;
            }
            $this->_page->acceptTag($tag);
            return true;
        }
        
        /**
         *    End of element event. An unexpected event
         *    triggers a parsing error.
         *    @param string $name        Element name.
         *    @return boolean            False on parse error.
         *    @public
         */
        function endElement($name) {
            if ($name == 'form') {
                $this->_page->acceptFormEnd();
            }            
            if (isset($this->_tags[$name]) && (count($this->_tags[$name]) > 0)) {
                $tag = array_pop($this->_tags[$name]);
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
        
        /**
         *    Factory for the tag objects. Creates the
         *    appropriate tag object for the incoming tag name.
         *    @param string $name        HTML tag name.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @protected
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
         *    @param $raw            Raw unparsed text.
         *    @public
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
         *    @param SimplePage $page      Target of incoming tag information.
         *    @return SimplePageBuilder    Builder to feed events to this page.
         *    @protected
         */
        function &_createBuilder(&$page) {
            return new SimplePageBuilder($page);
        }
        
        /**
         *    Adds a tag to the page.
         *    @param SimpleTag $tag        Tag to accept.
         *    @public
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
         *    @private
         */
        function _isFormElement($name) {
            return in_array($name, array('input', 'textarea'));
        }
        
        /**
         *    Opens a form.
         *    @param SimpleFormTag $tag      Tag to accept.
         *    @public
         */
        function acceptFormStart(&$tag) {
            $this->_open_forms[] = &new SimpleForm($tag);
        }
        
        /**
         *    Closes the most recently opened form.
         *    @public
         */
        function acceptFormEnd() {
            $this->_closed_forms[] = array_pop($this->_open_forms);
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
            $this->_addRelativeLink($url, $label, $id);
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
            if ($id !== false) {
                $this->_link_ids[(string)$id] = $url;
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
            if (in_array((string)$id, array_keys($this->_link_ids))) {
                return $this->_link_ids[(string)$id];
            }
            return false;
        }
        
        /**
         *    Sets the title tag contents.
         *    @param SimpleTitleTag $tag    Title of page.
         *    @protected
         */
        function _setTitle(&$tag) {
            $this->_title = &$tag;
        }
        
        /**
         *    Accessor for parsed title.
         *    @return        Title as string or boolean
         *                   false if no title is present.
         *    @public
         */
        function getTitle() {
            if ($this->_title) {
                return $this->_title->getContent();
            }
            return false;
        }
        
        /**
         *    Gets a list of all of the held forms.
         *    @return        Array of SimpleForm objects.
         *    @public
         */
        function getForms() {
            return array_merge($this->_open_forms, $this->_closed_forms);
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms.
         *    @param $label    Button label, default 'Submit'.
         *    @return          Form object containing the button.
         *    @public
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
         *    @param $id  Form label.
         *    @return     Form object containing the matching ID.
         *    @public
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
         *    @param $name        Field name.
         *    @param $value       Value to set field to.
         *    @public
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