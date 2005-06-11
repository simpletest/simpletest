<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id$
     */
     
    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/socket.php');
    /**#@-*/

    /**
     *    Bundle of GET/POST parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleEncoding {
        var $_request;
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleEncoding($query = false) {
            if (! $query) {
                $query = array();
            }
            $this->clear();
            $this->merge($query);
        }
        
        /**
         *    Empties the request of parameters.
         *    @access public
         */
        function clear() {
            $this->_request = array();
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
         *    @access protected
         */
        function _encode() {
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
     *    Bundle of GET parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleGetEncoding extends SimpleEncoding {
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleGetEncoding($query = false) {
            $this->SimpleEncoding($query);
        }
        
        /**
         *    HTTP request method.
         *    @return string        Always GET.
         *    @access public
         */
        function getMethod() {
            return 'GET';
        }
        
        /**
         *    Writes no extra headers.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeHeadersTo(&$socket) {
        }
        
        /**
         *    No data is sent to teh socket as the data is encoded into
         *    the URL.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeTo(&$socket) {
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part for attaching to a URL.
         *    @return string        Part of URL.
         *    @access public
         */
        function asUrlRequest() {
            return $this->_encode();
        }
    }
    
    /**
     *    Bundle of URL parameters for a HEAD request.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleHeadEncoding extends SimpleGetEncoding {
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleHeadEncoding($query = false) {
            $this->SimpleGetEncoding($query);
        }
        
        /**
         *    HTTP request method.
         *    @return string        Always HEAD.
         *    @access public
         */
        function getMethod() {
            return 'HEAD';
        }
    }
    
    /**
     *    Bundle of POST parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimplePostEncoding extends SimpleEncoding {
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimplePostEncoding($query = false) {
            $this->SimpleEncoding($query);
        }
        
        /**
         *    HTTP request method.
         *    @return string        Always POST.
         *    @access public
         */
        function getMethod() {
            return 'POST';
        }
        
        /**
         *    Dispatches the form headers down the socket.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeHeadersTo(&$socket) {
            $socket->write("Content-Length: " . (integer)strlen($this->_encode()) . "\r\n");
            $socket->write("Content-Type: application/x-www-form-urlencoded\r\n");
        }
        
        /**
         *    Dispatches the form data down the socket.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeTo(&$socket) {
            $socket->write($this->_encode());
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part for attaching to a URL.
         *    @return string        Part of URL.
         *    @access public
         */
        function asUrlRequest() {
            return '';
        }
    }

    /**
     *    Bundle of POST parameters in the multipart
     *    format. Can include file uploads.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleMultipartEncoding extends SimplePostEncoding {
        var $_boundary;
        
        /**
         *    Starts empty.
         *    @param array $query       Hash of parameters.
         *                              Multiple values are
         *                              as lists on a single key.
         *    @access public
         */
        function SimpleMultipartEncoding($query = false, $boundary = false) {
            $this->SimplePostEncoding($query);
            $this->_boundary = ($boundary === false ? uniqid('st') : $boundary);
        }
        
        /**
         *    Dispatches the form headers down the socket.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeHeadersTo(&$socket) {
            $socket->write("Content-Length: " . (integer)strlen($this->_encode()) . "\r\n");
            $socket->write("Content-Type: multipart/form-data, boundary=" . $this->_boundary . "\r\n");
        }
        
        /**
         *    Dispatches the form data down the socket.
         *    @param SimpleSocket $socket        Socket to write to.
         *    @access public
         */
        function writeTo(&$socket) {
            $socket->write($this->_encode());
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part.
         *    @return string        Part of URL.
         *    @access public
         */
        function _encode() {
            $stream = '';
            foreach ($this->_request as $key => $values) {
                foreach ($values as $value) {
                    $stream .= "--" . $this->_boundary . "\r\n";
                    $stream .= "Content-Disposition: form-data; name=\"$key\"\r\n";
                    $stream .= "\r\n$value\r\n";
                }
            }
            $stream .= "--" . $this->_boundary . "--\r\n";
            return $stream;
        }
    }
?>