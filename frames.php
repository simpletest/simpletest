<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/page.php');
    require_once(dirname(__FILE__) . '/user_agent.php');
    /**#@-*/
    
    /**
     *    A composite page. Wraps a frameset page and
     *    adds subframes. The original page will be
     *    mostly ignored. Implements the SimplePage
     *    interface so as to be interchangeable.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFrameset {
        var $_frameset;
        var $_frames;
        var $_focus;
        var $_names;
        
        /**
         *    Stashes the frameset page. Will make use of the
         *    browser to fetch the sub frames recursively.
         *    @param SimplePage $page        Frameset page.
         */
        function SimpleFrameset(&$page) {
            $this->_frameset = &$page;
            $this->_frames = array();
            $this->_focus = false;
            $this->_names = array();
        }
        
        /**
         *    Adds a parsed page to the frameset.
         *    @param SimplePage $page    Frame page.
         *    @param string name         Name of frame in frameset.
         *    @access public
         */
        function addParsedFrame(&$page, $name = false) {
            $this->_frames[] = &$page;
            if ($name) {
                $this->_names[$name] = count($this->_frames) - 1;
            }
        }
        
        /**
         *    Accessor for current frame focus. Will be
         *    false if no frame has focus.
         *    @return integer/string/boolean    Label if any, otherwise
         *                                      the position in the frameset
         *                                      or false if none.
         *    @access public
         */
        function getFrameFocus() {
            if ($this->_focus === false) {
                return false;
            }
            foreach ($this->_names as $name => $index) {
                if ($this->_focus == $index) {
                    return $name;
                }
            }
            return $this->_focus + 1;
        }
        
        /**
         *    Sets the focus by index. The integer index starts from 1.
         *    @param integer $choice    Chosen frame.
         *    @return boolean           True if frame exists.
         *    @access public
         */
        function setFrameFocusByIndex($choice) {
            if (($choice < 1) || ($choice > count($this->_frames))) {
                return false;
            }
            $this->_focus = $choice - 1;
            return true;
        }
        
        /**
         *    Sets the focus by name.
         *    @param string $name    Chosen frame.
         *    @return boolean        True if frame exists.
         *    @access public
         */
        function setFrameFocus($name) {
            if (in_array($name, array_keys($this->_names))) {
                $this->_focus = $this->_names[$name];
                return true;
            }
            return false;
        }
        
        /**
         *    Clears the frame focus.
         *    @access public
         */
        function clearFrameFocus() {
            $this->_focus = false;
        }
        
        /**
         *    Test for the presence of a frameset.
         *    @return boolean        True if frameset.
         *    @access public
         */
        function hasFrames() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->hasFrames();
            }
            return true;;
        }
        
        /**
         *    Accessor for frames information.
         *    @return boolean/array     False if no frameset or
         *                              otherwise a hash of frame URLs.
         *                              The key is either a numerical
         *                              index or the name attribute.
         *    @access public
         */
        function getFrames() {
            return $this->_frameset->getFrames();
        }
        
        /**
         *    Accessor for raw text of either all the pages or
         *    the frame in focus.
         *    @return string        Raw unparsed content.
         *    @access public
         */
        function getRaw() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRaw();
            }
            $raw = '';
            for ($i = 0; $i < count($this->_frames); $i++) {
                $raw .= $this->_frames[$i]->getRaw();
            }
            return $raw;
        }
        
        /**
         *    Accessor for last error.
         *    @return string        Error from last response.
         *    @access public
         */
        function getTransportError() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getTransportError();
            }
            return $this->_frameset->getTransportError();
        }
        
        /**
         *    Request method used to fetch this frame.
         *    @return string      GET, POST or HEAD.
         *    @access public
         */
        function getRequestMethod() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRequestMethod();
            }
            return $this->_frameset->getRequestMethod();
        }
        
        /**
         *    Original resource name.
         *    @return SimpleUrl        Current url.
         *    @access public
         */
        function getRequestUrl() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRequestUrl();
            }
            return $this->_frameset->getRequestUrl();
        }
        
        /**
         *    Original request data.
         *    @return mixed              Sent content.
         *    @access public
         */
        function getRequestData() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRequestData();
            }
            return $this->_frameset->getRequestData();
        }
        
        /**
         *    Accessor for current MIME type.
         *    @return string    MIME type as string; e.g. 'text/html'
         *    @access public
         */
        function getMimeType() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getMimeType();
            }
            return $this->_frameset->getMimeType();
        }
        
        /**
         *    Accessor for last response code.
         *    @return integer    Last HTTP response code received.
         *    @access public
         */
        function getResponseCode() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getResponseCode();
            }
            return $this->_frameset->getResponseCode();
        }
        
        /**
         *    Accessor for last Authentication type. Only valid
         *    straight after a challenge (401).
         *    @return string    Description of challenge type.
         *    @access public
         */
        function getAuthentication() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getAuthentication();
            }
            return $this->_frameset->getAuthentication();
        }
        
        /**
         *    Accessor for last Authentication realm. Only valid
         *    straight after a challenge (401).
         *    @return string    Name of security realm.
         *    @access public
         */
        function getRealm() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRealm();
            }
            return $this->_frameset->getRealm();
        }
        
        /**
         *    Accessor for outgoing header information.
         *    @return string      Header block.
         *    @access public
         */
        function getRequest() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRequest();
            }
            return $this->_frameset->getRequest();
        }
        
        /**
         *    Accessor for raw header information.
         *    @return string      Header block.
         *    @access public
         */
        function getHeaders() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getHeaders();
            }
            return $this->_frameset->getHeaders();
        }
        
        /**
         *    Accessor for parsed title.
         *    @return string     Title or false if no title is present.
         *    @access public
         */
        function getTitle() {
            return $this->_frameset->getTitle();
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return array   List of urls with scheme of
         *                    http or https and hostname.
         *    @access public
         */
        function getAbsoluteUrls() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getAbsoluteUrls();
            }
            $urls = array();
            foreach ($this->_frames as $frame) {
                $urls = array_merge($urls, $frame->getAbsoluteUrls());
            }
            return array_values(array_unique($urls));
        }
        
        /**
         *    Accessor for a list of all relative links.
         *    @return array      List of urls without hostname.
         *    @access public
         */
        function getRelativeUrls() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRelativeUrls();
            }
            $urls = array();
            foreach ($this->_frames as $frame) {
                $urls = array_merge($urls, $frame->getRelativeUrls());
            }
            return array_values(array_unique($urls));
        }
        
        /**
         *    Accessor for URLs by the link label. Label will match
         *    regardess of whitespace issues and case.
         *    @param string $label    Text of link.
         *    @return array           List of links with that label.
         *    @access public
         */
        function getUrlsByLabel($label) {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getUrlsByLabel($label);
            }
            $urls = array();
            foreach ($this->_frames as $frame) {
                $urls = array_merge($urls, $frame->getUrlsByLabel($label));
            }
            return array_values(array_unique($urls));
        }
        
        /**
         *    Accessor for a URL by the id attribute. If in a frameset
         *    then the first link found with taht ID attribute is
         *    returned only. Focus on a frame if you want one from
         *    a specific part of the frameset.
         *    @param string $id       Id attribute of link.
         *    @return string          URL with that id.
         *    @access public
         */
        function getUrlById($id) {
            foreach ($this->_frames as $frame) {
                if ($url = $frame->getUrlById($id)) {
                    return $url;
                }
            }
            return false;
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $label       Button label, default 'Submit'.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitLabel($label) {
            return $this->_findForm('getFormBySubmitLabel', $label);
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $name        Button name attribute.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitName($name) {
            return $this->_findForm('getFormBySubmitName', $name);
        }
        
        /**
         *    Finds a held form by button id. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $id          Button ID attribute.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitId($id) {
            return $this->_findForm('getFormBySubmitId', $id);
        }
        
        /**
         *    Finds a held form by image label. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $label       Usually the alt attribute.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageLabel($label) {
            return $this->_findForm('getFormByImageLabel', $label);
        }
        
        /**
         *    Finds a held form by image button id. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $name        Image name.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageName($name) {
            return $this->_findForm('getFormByImageName', $name);
        }
        
        /**
         *    Finds a held form by image button id. Will only
         *    search correctly built forms. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $id          Image ID attribute.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageId($id) {
            return $this->_findForm('getFormByImageId', $id);
        }
        
        /**
         *    Finds a held form by the form ID. A way of
         *    identifying a specific form when we have control
         *    of the HTML code. The first form found
         *    either within the focused frame, or across frames,
         *    will be the one returned.
         *    @param string $id     Form label.
         *    @return SimpleForm    Form object containing the matching ID.
         *    @access public
         */
        function &getFormById($id) {
            return $this->_findForm('getFormById', $id);
        }
        
        /**
         *    General form finder. Will serach all the frames or
         *    just the one in focus.
         *    @param string $method    Method to use to find in a page.
         *    @param string $attribute Label, name or ID.
         *    @return SimpleForm    Form object containing the matching ID.
         *    @access private         
         */
        function &_findForm($method, $attribute) {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->$method($attribute);
            }
            for ($i = 0; $i < count($this->_frames); $i++) {
                $form = &$this->_frames[$i]->$method($attribute);
                if (isset($form)) {
                    return $form;
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
            if (is_integer($this->_focus)) {
                $this->_frames[$this->_focus]->setField($name, $value);
            } else {
                for ($i = 0; $i < count($this->_frames); $i++) {
                    $this->_frames[$i]->setField($name, $value);
                }
            }
        }
         
        /**
         *    Sets a field on the form in which the unique field is
         *    available.
         *    @param string/integer $id  Field ID attribute.
         *    @param string $value       Value to set field to.
         *    @return boolean            True if value is valid.
         *    @access public
         */
        function setFieldById($id, $value) {
            if (is_integer($this->_focus)) {
                $this->_frames[$this->_focus]->setFieldById($id, $value);
            } else {
                for ($i = 0; $i < count($this->_frames); $i++) {
                    $this->_frames[$i]->setFieldById($id, $value);
                }
            }
        }
       
        /**
         *    Accessor for a form element value within a frameset.
         *    Finds the first match amongst the frames.
         *    @param string $name        Field name.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getField($name) {
            for ($i = 0; $i < count($this->_frames); $i++) {
                $value = $this->_frames[$i]->getField($name);
                if (isset($value)) {
                    return $value;
                }
            }
            return null;
        }
         
        /**
         *    Accessor for a form element value within a page.
         *    Finds the first match.
         *    @param string/integer $id  Field ID attribute.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getFieldById($id) {
            for ($i = 0; $i < count($this->_frames); $i++) {
                $value = $this->_frames[$i]->getFieldById($id);
                if (isset($value)) {
                    return $value;
                }
            }
            return null;
        }
    }
?>