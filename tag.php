<?php
    // $Id$
    
    /**
     *    HTML or XML tag.
     */
    class SimpleTag {
        var $_name;
        var $_attributes;
        var $_content;
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param $name        Tag name.
         *    @param $attributes  Hash of attribute names and
         *                        string values.
         */
        function SimpleTag($name, $attributes) {
            $this->_name = $name;
            $this->_attributes = $attributes;
            $this->_content = "";
        }
        
        /**
         *    Appends string content to the current content.
         *    @param $content        Additional text.
         *    @public
         */
        function addContent($content) {
            $this->_content .= (string)$content;
        }
        
        /**
         *    Accessor for tag name.
         *    @return        Name as string.
         *    @public
         */
        function getName() {
            return $this->_name;
        }
        
        /**
         *    Accessor for an attribute.
         *    @param $label      Attribute name.
         *    @return            Attribute value as string.
         *    @public
         */
        function getAttribute($label) {
            if (!isset($this->_attributes[$label])) {
                return false;
            }
            if ($this->_attributes[$label] === true) {
                return true;
            }
            return (string)$this->_attributes[$label];
        }
        
        /**
         *    Accessor for the whole content so far.
         *    @return        Content as big string.
         *    @public
         */
        function getContent() {
            return $this->_content;
        }
    }
    
    /**
     *    Form tag class to hold widgets.
     */
    class SimpleHtmlForm {
        
        /**
         *    Starts with no held controls/widgets.
         *    @param $tag        Form tag to read.
         */
        function SimpleHtmlForm($tag) {
        }
        
        /**
         *    Accessor for form action.
         *    @return            Either get or post.
         *    @public
         */
        function getMethod() {
        }
        
        /**
         *    Relative URL of the target.
         *    @return            URL as string.
         *    @public
         */
        function getAction() {
        }
        
        /**
         *    Adds a tag internally to the form.
         *    @param $tag        Input tag to add.
         *    @public
         */
        function addWidget($tag) {
        }
        
        /**
         *    Sets a widget value within the form.
         *    @param $name     Name of widget tag.
         *    @param $value    Value to input into the widget.
         *    @return          True if value is legal, false
         *                     otherwise. The value will still
         *                     be set.
         *    @public
         */
        function setValue($name, $value) {
        }
        
        /**
         *    Reads the current form values as a hash
         *    of submitted parameters.
         *    @param $name     Name of submit button.
         *    @param $value    Value of simulated submit.
         *    @return          Hash of submitted values.
         *    @public
         */
        function submit($name, $value) {
        }
        
        /**
         *    Submits a button with a particular label.
         *    @param $label    Button label to search for.
         *    @return          Hash of submitted values or false
         *                     if there is no such button in the
         *                     form.
         *    @public
         */
        function submitButton($label) {
        }
    }
?>