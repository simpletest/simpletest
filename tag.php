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
     *    Form tag class to hold widget values.
     */
    class SimpleForm {
        var $_method;
        var $_action;
        var $_defaults;
        var $_values;
        var $_buttons;
        
        /**
         *    Starts with no held controls/widgets.
         *    @param $tag        Form tag to read.
         */
        function SimpleForm($tag) {
            $this->_method = $tag->getAttribute("method");
            $this->_action = $tag->getAttribute("action");
            $this->_defaults = array();
            $this->_values = array();
            $this->_buttons = array();
        }
        
        /**
         *    Accessor for form action.
         *    @return            Either get or post.
         *    @public
         */
        function getMethod() {
            return ($this->_method ? strtolower($this->_method) : 'get');
        }
        
        /**
         *    Relative URL of the target.
         *    @return            URL as string.
         *    @public
         */
        function getAction() {
            return $this->_action;
        }
        
        /**
         *    Adds a tag contents to the form.
         *    @param $tag        Input tag to add.
         *    @public
         */
        function addWidget($tag) {
            if ($tag->getName() == "input") {
                if ($tag->getAttribute("type") == "submit") {
                    $this->_buttons[$tag->getAttribute("name") ? $tag->getAttribute("name") : 'submit'] =
                            $tag->getAttribute("value") ? $tag->getAttribute("value") : 'Submit';
                    return;
                }
                if ($tag->getAttribute("name")) {
                    $this->_defaults[$tag->getAttribute("name")] = $tag->getAttribute("value");
                }
            }
        }
        
        /**
         *    Extracts current value from form.
         *    @param $name        Keyed by widget name.
         *    @return             Value as string or false
         *                        if not set.
         *    @public
         */
        function getValue($name) {
            if (isset($this->_values[$name])) {
                return $this->_values[$name];
            }
            if (isset($this->_defaults[$name])) {
                return $this->_defaults[$name];
            }
            return false;
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
            $this->_values[$name] = $value;
        }
        
        /**
         *    Reads the current form values as a hash
         *    of submitted parameters.
         *    @return          Hash of submitted values.
         *    @public
         */
        function getValues() {
            return array_merge(
                    $this->_defaults,
                    $this->_values);
        }
        
        /**
         *    Gets the submit values for a named button.
         *    @param $name     Button label to search for.
         *    @return          Hash of submitted values or false
         *                     if there is no such button in the
         *                     form.
         *    @public
         */
        function submitButton($name) {
            if (!isset($this->_buttons[$name])) {
                return false;
            }
            return array_merge(
                    array($name => $this->_buttons[$name]),
                    $this->_defaults,
                    $this->_values);            
        }
        
        /**
         *    Gets the submit values for a button with a particular
         *    label.
         *    @param $label    Button label to search for.
         *    @return          Hash of submitted values or false
         *                     if there is no such button in the
         *                     form.
         *    @public
         */
        function submitButtonByLabel($label) {
            foreach ($this->_buttons as $name => $value) {
                if ($value == $label) {
                    return $this->submitButton($name);
                }
            }
            return false;
        }
    }
?>