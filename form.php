<?php
    /**
     *	Base include file for SimpleTest.
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
     
    /**#@+
     * include SimpleTest files
     */
    require_once(dirname(__FILE__) . '/tag.php');
    /**#@-*/
   
    /**
     *    Form tag class to hold widget values.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleForm {
        var $_method;
        var $_action;
        var $_default_target;
        var $_id;
        var $_buttons;
        var $_images;
        var $_widgets;
        
        /**
         *    Starts with no held controls/widgets.
         *    @param SimpleTag $tag        Form tag to read.
         *    @param SimpleUrl $url        Location of holding page.
         */
        function SimpleForm($tag, $url) {
            $this->_method = $tag->getAttribute('method');
            $this->_action = $this->_createAction($tag->getAttribute('action'), $url);
            $this->_default_target = false;
            $this->_id = $tag->getAttribute('id');
            $this->_buttons = array();
            $this->_images = array();
            $this->_widgets = array();
        }
        
        /**
         *    Sets the frame target within a frameset.
         *    @param string $frame        Name of frame.
         *    @access public
         */
        function setDefaultTarget($frame) {
            $this->_default_target = $frame;
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
         *    Combined action attribute with current location
         *    to get an absolute form target.
         *    @param string $action    Action attribute from form tag.
         *    @param SimpleUrl $base   Page location.
         *    @return SimpleUrl        Absolute form target.
         */
        function _createAction($action, $base) {
            if ($action === false) {
                return $base;
            }
            if ($action === true) {
                $url = new SimpleUrl('');
            } else {
                $url = new SimpleUrl($action);
            }
            return $url->makeAbsolute($base);
        }
        
        /**
         *    Absolute URL of the target.
         *    @return SimpleUrl           URL target.
         *    @access public
         */
        function getAction() {
            $url = $this->_action;
            if ($this->_default_target && ! $url->getTarget()) {
                $url->setTarget($this->_default_target);
            }
            return $url;
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
            if (strtolower($tag->getAttribute('type')) == 'submit') {
                $this->_buttons[] = &$tag;
            } elseif (strtolower($tag->getAttribute('type')) == 'image') {
                $this->_images[] = &$tag;
            } else {
                if ($tag->getName()) {
                    $this->_setWidget($tag);
                }
            }
        }
        
        /**
         *    Sets the widget into the form, grouping radio
         *    buttons if any.
         *    @param SimpleWidget $tag   Incoming form control.
         *    @access private
         */
        function _setWidget($tag) {
            if (strtolower($tag->getAttribute('type')) == 'radio') {
                $this->_addRadioButton($tag);
            } elseif (strtolower($tag->getAttribute('type')) == 'checkbox') {
                $this->_addCheckbox($tag);
            } else {
                $this->_widgets[$tag->getName()] = &$tag;
            }
        }
        
        /**
         *    Adds a radio button, building a group if necessary.
         *    @param SimpleRadioButtonTag $tag   Incoming form control.
         *    @access private
         */
        function _addRadioButton($tag) {
            if (! isset($this->_widgets[$tag->getName()])) {
                $this->_widgets[$tag->getName()] = &new SimpleRadioGroup();
            }
            $this->_widgets[$tag->getName()]->addWidget($tag);
        }
        
        /**
         *    Adds a checkbox, making it a group on a repeated name.
         *    @param SimpleCheckboxTag $tag   Incoming form control.
         *    @access private
         */
        function _addCheckbox($tag) {
            if (! isset($this->_widgets[$tag->getName()])) {
                $this->_widgets[$tag->getName()] = &$tag;
            } elseif (! SimpleTestCompatibility::isA($this->_widgets[$tag->getName()], 'SimpleCheckboxGroup')) {
                $previous = &$this->_widgets[$tag->getName()];
                $this->_widgets[$tag->getName()] = &new SimpleCheckboxGroup();
                $this->_widgets[$tag->getName()]->addWidget($previous);
                $this->_widgets[$tag->getName()]->addWidget($tag);
            } else {
                $this->_widgets[$tag->getName()]->addWidget($tag);
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
         *    Extracts current value from form by the ID.
         *    @param string/integer $id  Keyed by widget ID attribute.
         *    @return string             Value as string or null
         *                               if not set.
         *    @access public
         */
        function getValueById($id) {
            foreach ($this->_widgets as $widget) {
                if ($widget->getAttribute('id') == $id) {
                    return $widget->getValue();
                }
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
         *    Sets a widget value within the form by using the ID.
         *    @param string/integer $id   Name of widget tag.
         *    @param string $value        Value to input into the widget.
         *    @return boolean             True if value is legal, false
         *                                otherwise. If the field is not
         *                                present, nothing will be set.
         *    @access public
         */
        function setFieldById($id, $value) {
            foreach (array_keys($this->_widgets) as $name) {
                if ($this->_widgets[$name]->getAttribute('id') == $id) {
                    return $this->setField($name, $value);
                }
            }
            return false;
        }
       
        /**
         *    Reads the current form values as a hash
         *    of submitted parameters. Repeated parameters
         *    appear as a list.
         *    @return hash         Submitted values.
         *    @access public
         */
        function getValues() {
            $values = array();
            foreach (array_keys($this->_widgets) as $name) {
                $new = $this->_widgets[$name]->getValue();
                if (is_string($new)) {
                    $values[$name] = $new;
                } elseif (is_array($new)) {
                    $values[$name] = $new;
                }
            }
            return $values;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    name attribute.
         *    @param string $name        Name to look for.
         *    @return boolean            True if present.
         *    @access public
         */
        function hasSubmitName($name) {
            foreach ($this->_buttons as $button) {
                if ($button->getName() == $name) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    value attribute.
         *    @param string $label    Button label to search for.
         *    @return boolean         True if present.
         *    @access public
         */
        function hasSubmitLabel($label) {
            foreach ($this->_buttons as $button) {
                if ($button->getValue() == $label) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    ID attribute.
         *    @param string $id      Button ID attribute to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasSubmitId($id) {
            foreach ($this->_buttons as $button) {
                if ($button->getAttribute('id') == $id) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    name attribute.
         *    @param string $label   Button alt attribute to search for
         *                           or nearest equivalent.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageLabel($label) {
            foreach ($this->_images as $image) {
                if ($image->getAttribute('alt') == $label) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submittable image with this
         *    field name.
         *    @param string $name    Image name to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageName($name) {
            foreach ($this->_images as $image) {
                if ($image->getName() == $name) {
                    return true;
                }
            }
            return false;
        }
         
        /**
         *    Test to see if a form has a submittable image with this
         *    ID attribute.
         *    @param string $id      Button ID attribute to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageId($id) {
            foreach ($this->_images as $image) {
                if ($image->getAttribute('id') == $id) {
                    return true;
                }
            }
            return false;
        }
       
        /**
         *    Gets the submit values for a named button.
         *    @param string $name    Button label to search for.
         *    @return hash           Submitted values or false
         *                           if there is no such button in the
         *                           form.
         *    @access public
         */
        function submitButtonByName($name) {
            foreach ($this->_buttons as $button) {
                if ($button->getName() == $name) {
                    return array_merge(
                            array($button->getName() => $button->getValue()),
                            $this->getValues());            
                }
            }
            return false;
        }
        
        /**
         *    Gets the submit values for a named button.
         *    @param string $label   Button label to search for.
         *    @return hash           Submitted values or false
         *                           if there is no such button in the
         *                           form.
         *    @access public
         */
        function submitButtonByLabel($label) {
            foreach ($this->_buttons as $button) {
                if ($button->getValue() == $label) {
                    return array_merge(
                            array($button->getName() => $button->getValue()),
                            $this->getValues());            
                }
            }
            return false;
        }
        
        /**
         *    Gets the submit values for a button identified by the ID.
         *    @param string $id      Button ID attribute to search for.
         *    @return hash           Submitted values or false
         *                           if there is no such button in the
         *                           form.
         *    @access public
         */
        function submitButtonById($id) {
            foreach ($this->_buttons as $button) {
                if ($button->getAttribute('id') == $id) {
                    return array_merge(
                            array($button->getName() => $button->getValue()),
                            $this->getValues());            
                }
            }
            return false;
        }
         
        /**
         *    Gets the submit values for an image identified by the alt
         *    tag or nearest equivalent.
         *    @param string $label  Button label to search for.
         *    @param integer $x     X-coordinate of click.
         *    @param integer $y     Y-coordinate of click.
         *    @return hash          Submitted values or false
         *                          if there is no such button in the
         *                          form.
         *    @access public
         */
        function submitImageByLabel($label, $x, $y) {
            foreach ($this->_images as $image) {
                if ($image->getAttribute('alt') == $label) {
                    return array_merge(
                            array(
                                    $image->getName() . '.x' => $x,
                                    $image->getName() . '.y' => $y),
                            $this->getValues());            
                }
            }
            return false;
        }
         
        /**
         *    Gets the submit values for an image identified by the ID.
         *    @param string $name   Image name to search for.
         *    @param integer $x     X-coordinate of click.
         *    @param integer $y     Y-coordinate of click.
         *    @return hash          Submitted values or false
         *                          if there is no such button in the
         *                          form.
         *    @access public
         */
        function submitImageByName($name, $x, $y) {
            foreach ($this->_images as $image) {
                if ($image->getName() == $name) {
                    return array_merge(
                            array(
                                    $image->getName() . '.x' => $x,
                                    $image->getName() . '.y' => $y),
                            $this->getValues());            
                }
            }
            return false;
        }
          
        /**
         *    Gets the submit values for an image identified by the ID.
         *    @param string/integer $id  Button ID attribute to search for.
         *    @param integer $x          X-coordinate of click.
         *    @param integer $y          Y-coordinate of click.
         *    @return hash               Submitted values or false
         *                               if there is no such button in the
         *                               form.
         *    @access public
         */
        function submitImageById($id, $x, $y) {
            foreach ($this->_images as $image) {
                if ($image->getAttribute('id') == $id) {
                    return array_merge(
                            array(
                                    $image->getName() . '.x' => $x,
                                    $image->getName() . '.y' => $y),
                            $this->getValues());            
                }
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