<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    
    /**
     *    Bundle of GET/POST parameters. Can include
     *    repeated parameters.
     */
    class SimpleQueryString {
        var $_request;
        
        /**
         *    Starts empty.
         *    @access public
         */
        function SimpleQueryString() {
            $this->_request = array();
        }
        
        /**
         *    Adds a parameter to the query.
         *    @param string $key            Key to add value to.
         *    @param string/array $value    New data.
         *    @access public
         */
        function add($key, $value) {
            $this->_request[$key] = $value;
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part.
         *    @return string        Part of URL.
         *    @access public
         */
        function asString() {
            $statements = array();
            foreach ($this->_request as $key => $value) {
                $statements[] = "$key=$value";
            }
            return implode('&', $statements);
        }
    }
?>