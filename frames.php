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
         *    Accessor for raw page information.
         *    @return string      Original text content of web page.
         *    @access public
         */
        function getContent() {
            if (is_integer($this->_focus)) {
                return $this->_frames[$this->_focus]->getRaw();
            }
            return $this->_frameset->getRaw();
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
    }
?>