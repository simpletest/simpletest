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
         *    @param string $name        Tag name.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTag($name, $attributes) {
            $this->_name = $name;
            $this->_attributes = $attributes;
            $this->_content = "";
        }
        
        /**
         *    Check to see if the tag can have both start and
         *    end tags with content in between.
         *    @return boolean        True if content allowed.
         *    @access public
         */
        function expectEndTag() {
            return true;
        }
        
        /**
         *    Appends string content to the current content.
         *    @param string $content        Additional text.
         *    @access public
         */
        function addContent($content) {
            $this->_content .= (string)$content;
        }
        
        /**
         *    Adds an enclosed tag to the content.
         *    @param SimpleTag $tag    New tag.
         *    @access public
         */
        function addTag(&$tag) {
        }
        
        /**
         *    Accessor for tag name.
         *    @return string       Name of tag.
         *    @access public
         */
        function getTagName() {
            return $this->_name;
        }
        
        /**
         *    List oflegal child elements.
         *    @return array        List of element names.
         *    @access public
         */
        function getChildElements() {
            return array();
        }
        
        /**
         *    Accessor for an attribute.
         *    @param string $label    Attribute name.
         *    @return string          Attribute value.
         *    @access public
         */
        function getAttribute($label) {
            if (! isset($this->_attributes[$label])) {
                return false;
            }
            if ($this->_attributes[$label] === '') {
                return true;
            }
            return (string)$this->_attributes[$label];
        }
        
        /**
         *    Accessor for the whole content so far.
         *    @return string       Content as big string.
         *    @access public
         */
        function getContent() {
            return $this->_content;
        }
    }
    
    /**
     *    Page title.
     */
    class SimpleTitleTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTitleTag($attributes) {
            $this->SimpleTag('title', $attributes);
        }
    }
    
    /**
     *    Link.
     */
    class SimpleAnchorTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleAnchorTag($attributes) {
            $this->SimpleTag('a', $attributes);
        }
    }
    
    /**
     *    Form element.
     */
    class SimpleWidget extends SimpleTag {
        var $_value;
        var $_is_set;
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param string $name        Tag name.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleWidget($name, $attributes) {
            $this->SimpleTag($name, $attributes);
            $this->_value = false;
            $this->_is_set = false;
        }
        
        /**
         *    Accessor for name submitted as the key in
         *    GET/POST varaibles haah.
         *    @return string        Parsed value.
         *    @access public
         */
        function getName() {
            return $this->getAttribute('name');
        }
        
        /**
         *    Accessor for default value parsed with the tag.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            $default = $this->getAttribute('value');
            if ($default === true) {
                $default = '';
            }
            if ($default === false) {
                $default = '';
            }
            return $default;
        }
        
        /**
         *    Accessor for currently set value or default if
         *    none.
         *    @return string      Value set by form or default
         *                        if none.
         *    @access public
         */
        function getValue() {
            if (! $this->_is_set) {
                return $this->getDefault();
            }
            return $this->_value;
        }
        
        /**
         *    Sets the current form element value.
         *    @param string $value       New value.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            $this->_value = $value;
            $this->_is_set = true;
            return true;
        }
        
        /**
         *    Resets the form element value back to the
         *    default.
         *    @access public
         */
        function resetValue() {
            $this->_is_set = false;
        }
    }
    
    /**
     *    Text, password and hidden field.
     */
    class SimpleTextTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTextTag($attributes) {
            if (! isset($attributes['value'])) {
                $attributes['value'] = '';
            }
            $this->SimpleWidget('input', $attributes);
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
    }
    
    /**
     *    Text, password and hidden field.
     */
    class SimpleSubmitTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleSubmitTag($attributes) {
            if (! isset($attributes['name'])) {
                $attributes['name'] = 'submit';
            }
            if (! isset($attributes['value'])) {
                $attributes['value'] = 'Submit';
            }
            $this->SimpleWidget('input', $attributes);
        }
        
        /**
         *    Tag contains no end element.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    Disables the setting of the button value.
         *    @param string $value        Ignored.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
    }
    
    /**
     *    Content tag for text area.
     */
    class SimpleTextAreaTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTextAreaTag($attributes) {
            $this->SimpleWidget('textarea', $attributes);
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->_wrapIsEnabled()) {
                return wordwrap(
                        $this->getContent(),
                        (integer)$this->getAttribute('cols'),
                        "\n");
            }
            return $this->getContent();
        }
        
        /**
         *    Applies word wrapping if needed.
         *    @param string $value      New value.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($this->_wrapIsEnabled()) {
                $value = wordwrap(
                        $value,
                        (integer)$this->getAttribute('cols'),
                        "\n");
            }
            return parent::setValue($value);
        }
        
        /**
         *    Test to see if text should be wrapped.
         *    @return boolean        True if wrapping on.
         *    @access private
         */
        function _wrapIsEnabled() {
            if ($this->getAttribute('cols')) {
                $wrap = $this->getAttribute('wrap');
                if (($wrap == 'physical') || ($wrap == 'hard')) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     *    Checkbox widget.
     */
    class SimpleCheckboxTag extends SimpleWidget {
        
        /**
         *    Starts with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleCheckboxTag($attributes) {
            if (! isset($attributes['value'])) {
                $attributes['value'] = 'on';
            }
            $this->SimpleWidget('input', $attributes);
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    The only allowed value in the one in the
         *    "value" attribute.
         *    @param string $value      New value.
         *    @return boolean           True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($value === false) {
                return parent::setValue($value);
            }
            if ($value != $this->getAttribute('value')) {
                return false;
            }
            return parent::setValue($value);
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('checked')) {
                return $this->getAttribute('value');
            }
            return false;
        }
    }
    
    /**
     *    Drop down widget.
     */
    class SimpleSelectionTag extends SimpleWidget {
        var $_options;
        var $_choice;
        
        /**
         *    Starts with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleSelectionTag($attributes) {
            $this->SimpleWidget('select', $attributes);
            $this->_options = array();
            $this->_choice = false;
        }
        
        /**
         *    Adds an option tag to a selection field.
         *    @param SimpleOptionTag $tag     New option.
         *    @access public
         */
        function addTag(&$tag) {
            if ($tag->getTagName() == 'option') {
                $this->_options[] = &$tag;
            }
        }
        
        /**
         *    Text within the selection element is ignored.
         *    @param string $content        Ignored.
         *    @access public
         */
        function addContent($content) {
        }
        
        /**
         *    Scans options for defaults. If none, then
         *    the first option is selected.
         *    @return string        Selected field.
         *    @access public
         */
        function getDefault() {
            for ($i = 0; $i < count($this->_options); $i++) {
                if ($this->_options[$i]->getAttribute('selected')) {
                    return $this->_options[$i]->getDefault();
                }
            }
            if (count($this->_options) > 0) {
                return $this->_options[0]->getDefault();
            }
            return '';
        }
        
        /**
         *    Can only set allowed values.
         *    @param string $value       New choice.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            for ($i = 0; $i < count($this->_options); $i++) {
                if ($this->_options[$i]->getContent() == $value) {
                    $this->_choice = $i;
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Accessor for current selection value.
         *    @return string      Value attribute or
         *                        content of opton.
         *    @access public
         */
        function getValue() {
            if ($this->_choice === false) {
                return $this->getDefault();
            }
            return $this->_options[$this->_choice]->getValue();
        }
    }
    
    /**
     *    Option for selection field.
     */
    class SimpleOptionTag extends SimpleWidget {
        
        /**
         *    Stashes the attributes.
         */
        function SimpleOptionTag($attributes) {
            $this->SimpleWidget('option', $attributes);
        }
        
        /**
         *    Does nothing.
         *    @param string $value      Ignored.
         *    @return boolean           Not allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('value')) {
                return $this->getAttribute('value');
            }
            return $this->getContent();
        }
    }
    
    /**
     *    Radio button.
     */
    class SimpleRadioButtonTag extends SimpleWidget {
        
        /**
         *    Stashes the attributes.
         */
        function SimpleRadioButtonTag($attributes) {
            if (! isset($attributes['value'])) {
                $attributes['value'] = 'on';
            }
            $this->SimpleWidget('input', $attributes);
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    The only allowed value in the one in the
         *    "value" attribute.
         *    @param string $value      New value.
         *    @return boolean           True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($value === false) {
                return parent::setValue($value);
            }
            if ($value != $this->getAttribute('value')) {
                return false;
            }
            return parent::setValue($value);
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('checked')) {
                return $this->getAttribute('value');
            }
            return false;
        }
    }
    
    /**
     *    A group of tags with the same name within a form.
     *    Used for radio buttons.
     */
    class SimpleTagGroup {
        var $_widgets;
        
        /**
         *    Starts empty.
         *    @access public
         */
        function SimpleTagGroup() {
            $this->_widgets = array();
        }
        
        /**
         *    Adds a tag to the group.
         *    @param SimpleWidget $widget
         *    @access public
         */
        function addWidget(&$widget) {
            $this->_widgets[] = &$widget;
        }
        
        /**
         *    Each tag is tried in turn until one is
         *    successfully set. The others will be
         *    unchecked if successful.
         *    @param string $value      New value.
         *    @return boolean           True if any allowed.
         *    @access public
         */
        function setValue($value) {
            $index = false;
            for ($i = 0; $i < count($this->_widgets); $i++) {
                if ($this->_widgets[$i]->setValue($value)) {
                    $index = $i;
                    break;
                }
            }
            if ($index === false) {
                return false;
            }
            for ($i = 0; $i < count($this->_widgets); $i++) {
                if ($index == $i) {
                    continue;
                }
                $this->_widgets[$i]->setValue(false);
            }
            return true;
        }
        
        /**
         *    Accessor for current selected widget or false
         *    if none.
         *    @return string/boolean   Value attribute or
         *                             content of opton.
         *    @access public
         */
        function getValue() {
            for ($i = 0; $i < count($this->_widgets); $i++) {
                if ($this->_widgets[$i]->getValue()) {
                    return $this->_widgets[$i]->getValue();
                }
            }
            return false;
        }
        
        /**
         *    Accessor for starting value that is active.
         *    @return string/boolean      Value of first checked
         *                                widget or false if none.
         *    @access public
         */
        function getDefault() {
            for ($i = 0; $i < count($this->_widgets); $i++) {
                if ($this->_widgets[$i]->getDefault()) {
                    return $this->_widgets[$i]->getDefault();
                }
            }
            return false;
        }
    }
    
    /**
     *    Tag to aid parsing the form.
     */
    class SimpleFormTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleFormTag($attributes) {
            $this->SimpleTag('form', $attributes);
        }
    }
    
    /**
     *    Form tag class to hold widget values.
     */
    class SimpleForm {
        var $_method;
        var $_action;
        var $_id;
        var $_buttons;
        var $_widgets;
        
        /**
         *    Starts with no held controls/widgets.
         *    @param SimpleTag $tag        Form tag to read.
         */
        function SimpleForm($tag) {
            $this->_method = $tag->getAttribute("method");
            $this->_action = $tag->getAttribute("action");
            $this->_id = $tag->getAttribute("id");
            $this->_buttons = array();
            $this->_widgets = array();
        }
        
        /**
         *    Accessor for form action.
         *    @return string           Either get or post.
         *    @access public
         */
        function getMethod() {
            return ($this->_method ? strtolower($this->_method) : 'get');
        }
        
        /**
         *    Relative URL of the target.
         *    @return string           URL target.
         *    @access public
         */
        function getAction() {
            return $this->_action;
        }
        
        /**
         *    ID field of form for unique identification.
         *    @return string           Unique tag ID.
         *    @access public
         */
        function getId() {
            return $this->_id;
        }
        
        /**
         *    Adds a tag contents to the form.
         *    @param SimpleWidget $tag        Input tag to add.
         *    @access public
         */
        function addWidget($tag) {
            if ($tag->getAttribute("type") == "submit") {
                $this->_buttons[$tag->getName()] = &$tag;
            } else {
                if ($tag->getName()) {
                    $this->_setWidget($tag);
                }
            }
        }
        
        /**
         *    Sets the widget into the form, grouping radio
         *    buttons if any.
         *    @access private
         */
        function _setWidget($tag) {
            if ($tag->getAttribute("type") == "radio") {
                if (! isset($this->_widgets[$tag->getName()])) {
                    $this->_widgets[$tag->getName()] = &new SimpleTagGroup();
                }
                $this->_widgets[$tag->getName()]->addWidget($tag);
            } else {
                $this->_widgets[$tag->getName()] = &$tag;
            }
        }
        
        /**
         *    Extracts current value from form.
         *    @param string $name        Keyed by widget name.
         *    @return string             Value as string or null
         *                               if not set.
         *    @access public
         */
        function getValue($name) {
            if (isset($this->_widgets[$name])) {
                return $this->_widgets[$name]->getValue();
            }
            return null;
        }
        
        /**
         *    Sets a widget value within the form.
         *    @param string $name     Name of widget tag.
         *    @param string $value    Value to input into the widget.
         *    @return boolean         True if value is legal, false
         *                            otherwise. If the field is not
         *                            present, nothing will be set.
         *    @access public
         */
        function setField($name, $value) {
            if (isset($this->_widgets[$name])) {
                return $this->_widgets[$name]->setValue($value);
            }
            return false;
        }
        
        /**
         *    Reads the current form values as a hash
         *    of submitted parameters.
         *    @return hash         Submitted values.
         *    @access public
         */
        function getValues() {
            $values = array();
            foreach (array_keys($this->_widgets) as $name) {
                $value = $this->_widgets[$name]->getValue();
                if (is_string($value)) {
                    $values[$name] = $value;
                } elseif ($value === true) {
                    $values[$name] = '';
                }
            }
            return $values;
        }
        
        /**
         *    Gets a button name from the label.
         *    @param string $label    Button label to search for.
         *    @return string          Name of button.
         *    @access public
         */
        function getSubmitName($label) {
            foreach (array_keys($this->_buttons) as $name) {
                if ($this->_buttons[$name]->getValue() == $label) {
                    return $name;
                }
            }
        }
        
        /**
         *    Gets the submit values for a named button.
         *    @param string $name    Button label to search for.
         *    @return hash           Submitted values or false
         *                           if there is no such button in the
         *                           form.
         *    @access public
         */
        function submitButton($name) {
            if (!isset($this->_buttons[$name])) {
                return false;
            }
            return array_merge(
                    array($name => $this->_buttons[$name]->getValue()),
                    $this->getValues());            
        }
        
        /**
         *    Gets the submit values for a button with a particular
         *    label.
         *    @param string $label    Button label to search for.
         *    @return hash            Submitted values or false
         *                            if there is no such button in the
         *                            form.
         *    @access public
         */
        function submitButtonByLabel($label) {
            if ($name = $this->getSubmitName($label)) {
                return $this->submitButton($name);
            }
            return false;
        }
        
        /**
         *    Simply submits the form without the submit button
         *    value. Used when there is only one button or it
         *    is unimportant.
         *    @return hash           Submitted values.
         *    @access public
         */
        function submit() {
            return $this->getValues();            
        }
    }
?>