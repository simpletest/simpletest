<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
    
    /**
     * @ignore    originally defined in simple_test.php
     */
    if (! defined('SIMPLE_TEST')) {
        define('SIMPLE_TEST', 'simpletest/');
    }
    require_once(SIMPLE_TEST . 'http.php');
    require_once(SIMPLE_TEST . 'parser.php');
    require_once(SIMPLE_TEST . 'tag.php');
    
    /**
     *    SAX event handler. Maintains a list of
     *    open tags and dispatches them as they close.
	 *    @package SimpleTest
	 *    @subpackage WebTester
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
            if ($name == 'form') {
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
                return $this->_createInputTag($attributes);
            } elseif ($name == 'textarea') {
                return new SimpleTextAreaTag($attributes);
            } elseif ($name == 'select') {
                return $this->_createSelectionTag($attributes);
            } elseif ($name == 'option') {
                return new SimpleOptionTag($attributes);
            } elseif ($name == 'form') {
                return new SimpleFormTag($attributes);
            }
            return new SimpleTag($name, $attributes);
        }
        
        /**
         *    Factory for selection fields.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createSelectionTag($attributes) {
            if (isset($attributes['multiple'])) {
                return new MultipleSelectionTag($attributes);
            }
            return new SimpleSelectionTag($attributes);
        }
        
        /**
         *    Factory for input tags.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createInputTag($attributes) {
            if (! isset($attributes['type'])) {
                return new SimpleTextTag($attributes);
            }
            if ($attributes['type'] == 'submit') {
                return new SimpleSubmitTag($attributes);
            } elseif ($attributes['type'] == 'checkbox') {
                return new SimpleCheckboxTag($attributes);
            } elseif ($attributes['type'] == 'radio') {
                return new SimpleRadioButtonTag($attributes);
            } else {
                return new SimpleTextTag($attributes);
            }
        }
    }
    
    /**
     *    A wrapper for a web page.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimplePage {
        var $_links;
        var $_title;
        var $_open_forms;
        var $_complete_forms;
        var $_raw;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @param string $raw            Raw unparsed text.
         *    @access public
         */
        function SimplePage($raw) {
            $this->_links = array();
            $this->_title = false;
            $this->_raw = $raw;
            $this->_open_forms = array();
            $this->_complete_forms = array();
            $builder = &$this->_createBuilder($this);
            $builder->parse($this->_raw, $this->_createParser($builder));
        }
        
        /**
         *    Accessor for raw text of page.
         *    @return string        Raw unparsed content.
         *    @access public
         */
        function getRaw() {
            return $this->_raw;
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
                $this->_addLink($tag);
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
            $this->_complete_forms[] = array_pop($this->_open_forms);
        }
        
        /**
         *    Test to see if link is an absolute one.
         *    @param string $url     Url to test.
         *    @return boolean           True if absolute.
         *    @access protected
         */
        function _linkIsAbsolute($url) {
            $parsed = new SimpleUrl($url);
            return (boolean)($parsed->getScheme() && $parsed->getHost());
        }
        
        /**
         *    Adds a link to the page. Partially fixes
         *    expandomatic links.
         *    @param SimpleAnchorTag $tag      Link to accept.
         *    @access protected
         */
        function _addLink($tag) {
            $this->_links[] = $tag;
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return array   List of urls with scheme of
         *                    http or https and hostname.
         *    @access public
         */
        function getAbsoluteLinks() {
            $all = array();
            foreach ($this->_links as $link) {
                if ($this->_linkIsAbsolute($link->getAttribute('href'))) {
                    $all[] = $link->getAttribute('href');
                }
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
            foreach ($this->_links as $link) {
                if (! $this->_linkIsAbsolute($link->getAttribute('href'))) {
                    $all[] = $link->getAttribute('href');
                }
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
            foreach ($this->_links as $link) {
                if ($link->getContent() == $label) {
                    $all[] = $link->getAttribute('href');
                }
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
            foreach ($this->_links as $link) {
                if ($link->getAttribute('id') === (string)$id) {
                    return $link->getAttribute('href');
                }
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
            return array_merge($this->_open_forms, $this->_complete_forms);
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms.
         *    @param string $label       Button label, default 'Submit'.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitLabel($label) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->getSubmitName($label)) {
                    return $this->_complete_forms[$i];
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
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->getId() == $id) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Accessor for a form element value within a page.
         *    Finds the first match.
         *    @param string $name        Field name.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getField($name) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                $value = $this->_complete_forms[$i]->getValue($name);
                if (isset($value)) {
                    return $value;
                }
            }
            return null;
        }
        
        /**
         *    Sets a field on each form in which the field is
         *    available.
         *    @param string $name        Field name.
         *    @param string $value       Value to set field to.
         *    @return boolean            True if value is valid.
         *    @access public
         */
        function setField($name, $value) {
            $is_set = false;
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->setField($name, $value)) {
                    $is_set = true;
                }
            }
            return $is_set;
        }
    }
?>