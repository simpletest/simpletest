<?php
    // $Id$
    
    /**
     *    Stashes an error for later. Useful for constructors
     *    until PHP gets exceptions.
     */
    class StickyError {
        var $_error = "Constructor not chained";
        
        /**
         *    Sets the error to empty.
         *    @access public
         */
        function StickyError() {
            $this->_clearError();
        }
        
        /**
         *    Test for an outstanding error.
         *    @return            True if there is an error.
         *    @access public
         */
        function isError() {
            return ($this->_error != "");
        }
        
        /**
         *    Accessor for an outstanding error.
         *    @return            Empty string if no error otherwise
         *                       the error message.
         *    @access public
         */
        function getError() {
            return $this->_error;
        }
        
        /**
         *    Sets the internal error.
         *    @param        Error message to stash.
         *    @access protected
         */
        function _setError($error) {
            $this->_error = $error;
        }
        
        /**
         *    Resets the error state to no error.
         *    @access protected
         */
        function _clearError() {
            $this->_setError("");
        }
    }
    
    /**
     *    Wrapper for TCP/IP socket.
     */
    class SimpleSocket extends StickyError {
        var $_handle;
        var $_is_open;
        
        /**
         *    Opens a socket for reading and writing.
         *    @param $url        URL as string.
         *    @access public
         */
        function SimpleSocket($url, $port = 80) {
            $this->StickyError();
            $this->_is_open = false;
            if (!($this->_handle = @fsockopen($url, $port, $errorNumber, $error, 15))) {
                $this->_setError("Cannot open [$url] with [$error]");
            } else {
                $this->_is_open = true;
            }
        }
        
        /**
         *    Writes some data to the socket.
         *    @param $message       String to send to socket.
         *    @return               True if successful.
         *    @access public
         */
        function write($message) {
            if ($this->isError() || !$this->isOpen()) {
                return false;
            }
            if (!fwrite($this->_handle, $message)) {
                return false;
            }
            fflush($this->_handle);
            return true;
        }
        
        /**
         *    Reads data from the socket.
         *    @param $block_size        Size of chunk to read.
         *    @return                   Incoming bytes. False
         *                              on error.
         *    @access public
         */
        function read($block_size = 255) {
            if ($this->isError() || !$this->isOpen()) {
                return false;
            }
            return fread($this->_handle, $block_size);
        }
        
        /**
         *    Accessor for socket open state.
         *    @return            True if open.
         *    @access public
         */
        function isOpen() {
            return $this->_is_open;
        }
        
        /**
         *    Closes the socket preventing further reads.
         *    Cannot be reopened once closed.
         *    @access public
         */
        function close() {
            $this->_is_open = false;
            return fclose($this->_handle);
        }
    }
?>