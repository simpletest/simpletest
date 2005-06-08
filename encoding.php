<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */

    /**
     *    Bundle of GET/POST parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFormEncoding {
        var $_request;
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleFormEncoding($query = false) {
            if (! $query) {
                $query = array();
            }
            $this->_request = array();
            $this->merge($query);
        }
        
        /**
         *    Adds a parameter to the query.
         *    @param string $key            Key to add value to.
         *    @param string/array $value    New data.
         *    @access public
         */
        function add($key, $value) {
            if ($value === false) {
                return;
            }
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
         *    Adds a set of parameters to this query.
         *    @param array/SimpleQueryString $query  Multiple values are
         *                                           as lists on a single key.
         *    @access public
         */
        function merge($query) {
            if (is_object($query)) {
                foreach ($query->getKeys() as $key) {
                    $this->add($key, $query->getValue($key));
                }
            } elseif (is_array($query)) {
                foreach ($query as $key => $value) {
                    $this->add($key, $value);
                }
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
         *    Accessor for key list.
         *    @return array        List of keys present.
         *    @access public
         */
        function getKeys() {
            return array_keys($this->_request);
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

    /**
     *    Bundle of POST parameters in the multipart
     *    format. Can include file uploads.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleMultipartFormEncoding extends SimpleFormEncoding {
        var $_boundary;
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleMultipartFormEncoding($query = false, $boundary = false) {
            $this->SimpleFormEncoding($query);
            $this->_boundary = '----' . ($boundary === false ? uniqid('st') : $boundary);
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part.
         *    @return string        Part of URL.
         *    @access public
         */
        function asString() {
            $stream = $this->_boundary . "\r\n";
            foreach ($this->_request as $key => $values) {
                foreach ($values as $value) {
                    $stream .= "Content-Disposition: form-data; name=\"$key\"\r\n";
                    $stream .= "\r\n$value\r\n";
                    $stream .= $this->_boundary . "\r\n";
                }
            }
            return $stream;
        }
    }
?>