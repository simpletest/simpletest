<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	MockObjects
     *	@version	$Id$
     */
    
    /**
     * @ignore    originally defined in simple_test.php
     */
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    
    /**
     *    Bundle of GET/POST parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
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
            if (! isset($this->_request[$key])) {
                $this->_request[$key] = array();
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $this->_request[$key][] = $item;
                }
            } else {
                $this->_request[$key][] = $value;
            }
        }
        
        /**
         *    Accessor for single value.
         *    @return string/array    False if missing, string
         *                            if present and array if
         *                            multiple entries.
         *    @access public
         */
        function getValue($key) {
            if (! isset($this->_request[$key])) {
                return false;
            } elseif (count($this->_request[$key]) == 1) {
                return $this->_request[$key][0];
            } else {
                return $this->_request[$key];
            }
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part.
         *    @return string        Part of URL.
         *    @access public
         */
        function asString() {
            $statements = array();
            foreach ($this->_request as $key => $values) {
                foreach ($values as $value) {
                    $statements[] = "$key=" . urlencode($value);
                }
            }
            return implode('&', $statements);
        }
    }
?>