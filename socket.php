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
         *    @public
         */
        function StickyError() {
            $this->_clearError();
        }
        
        /**
         *    Test for an outstanding error.
         *    @return            True if there is an error.
         *    @public
         */
        function isError() {
            return ($this->_error != "");
        }
        
        /**
         *    Accessor for an outstanding error.
         *    @return            Empty string if no error otherwise
         *                       the error message.
         *    @public
         */
        function getError() {
            return $this->_error;
        }
        
        /**
         *    Sets the internal error.
         *    @param        Error message to stash.
         */
        function _setError($error) {
            $this->_error = $error;
        }
        
        /**
         *    Resets the error state to no error.
         */
        function _clearError() {
            $this->_setError("");
        }
    }
    
    /**
     *    Wrapper for TCP/IP socket.
     */
    class Socket extends StickyError {
        var $_handle;
        
        /**
         *    Opens a socket for reading and writing.
         *    @param $url        URL as string.
         *    @public
         */
        function Socket($url) {
            $this->StickyError();
            if (!($this->_handle = fsockopen($url, 80, $errorNumber, $error, 15))) {
                $this->_setError("Cannot open [$url] with [$error]");
            }
        }
    }
?>