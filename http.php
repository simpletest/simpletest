<?php
    // $Id$
    
    /**
     *    HTTP request for a web page. Factory for
     *    HttpResponse object.
     */
    class SimpleHttpRequest {
        var $_host;
        var $_path;
        var $_cookies;
        
        /**
         *    Saves the URL ready for fetching.
         *    @param $url        URL as string.
         *    @public
         */
        function SimpleHttpRequest($url) {
            $url = parse_url($url);
            $this->_host = (isset($url["host"]) ? $url["host"] : "localhost");
            $this->_path = (isset($url["path"]) ? $url["path"] : "");
            $this->_cookies = array();
        }
        
        /**
         *    Fetches the content and parses the headers.
         *    @param $socket        Test override.
         *    @return               Either false or a HttpResponse.
         *    @public
         */
        function fetch($socket = "") {
            if (!is_object($socket)) {
                $socket = new SimpleSocket($this->_host);
            }
            if ($socket->isError()) {
                return false;
            }
            $socket->write("GET " . $this->_host . $this->_path . " HTTP/1.0\r\n");
            $socket->write("Host: localhost\r\n");
            if (count($this->_cookies) > 0) {
                $socket->write("Cookie: " . $this->_marshallCookies($this->_cookies) . "\r\n");
            }
            $socket->write("Connection: close\r\n");
            $socket->write("\r\n");
            return new SimpleHttpResponse($socket);
        }
        
        /**
         *    Adds cookies to the request overriding previous
         *    cookies that have the same key.
         *    @param $cookies     Hash of cookie names and values.
         *    @public
         */
        function setCookies($cookies) {
            $this->_cookies = array_merge($this->_cookies, $cookies);
        }
        
        /**
         *    Serialises the cookie hash ready for
         *    transmission.
         *    @param $cookies     Cookies as hash.
         *    @return             Cookies in header form.
         *    @private
         */
        function _marshallCookies($cookies) {
            $cookie_pairs = array();
            foreach ($cookies as $key => $value) {
                $cookie_pairs[] = "$key=$value";
            }
            return implode(";", $cookie_pairs);
        }
    }
    
    /**
     *    Basic HTTP response.
     */
    class SimpleHttpResponse extends StickyError {
        var $_content;
        var $_mime_type;
        var $_response_code;
        
        /**
         *    Constructor. Reads and parses the incoming
         *    content and headers.
         *    @param $socket        Network connection to fetch
         *                          response text from.
         *    @public
         */
        function SimpleHttpResponse(&$socket) {
            $this->StickyError();
            if ($socket->isError()) {
                $this->_setError("Bad socket [" . $socket->getError() . "]");
                return;
            }
            $raw = $this->_readAll($socket);
            if ($socket->isError()) {
                $this->_setError("Error reading socket [" . $socket->getError() . "]");
                return;
            }
            if (!strstr($raw, "\r\n\r\n")) {
                $this->_setError("Could not parse headers");
                return;
            }
            list($headers, $this->_content) = split("\r\n\r\n", $raw, 2);
            foreach (split("\r\n", $headers) as $header_line) {
                $this->_parseHeaderLine($header_line);
            }
        }
        
        /**
         *    Accessor for the content after the last
         *    header line.
         *    @return            All content as string.
         *    @public
         */
        function getContent() {
            return $this->_content;
        }
        
        /**
         *    Accessor for parsed HTTP error code.
         *    @return            HTTP error code integer.
         *    @public
         */
        function getResponseCode() {
            return (integer)$this->_response_code;            
        }
        
        /**
         *    Accessor for MIME type header information.
         *    @return            MIME type as string.
         *    @public
         */
        function getMimeType() {
            return $this->_mime_type;            
        }
        
        /**
         *    Called on each header line. Subclasses should
         *    add behaviour to this method for their
         *    particular header types.
         *    @param $header_line        One line of header.
         *    @protected
         */
        function _parseHeaderLine($header_line) {
            if (preg_match('/HTTP\/\d+\.\d+\s+(.*)\s+OK/i', $header_line, $matches)) {
                $this->_response_code = $matches[1];
            }
            if (preg_match('/Content-type: (.*)/i', $header_line, $matches)) {
                $this->_mime_type = $matches[1];
            }
        }
        
        /**
         *    Reads the whole of the socket into a single
         *    string.
         *    @param $socket        Unread socket.
         *    @return               String if successful else
         *                          false.
         *    @private
         */
        function _readAll(&$socket) {
            $all = "";
            while ($next = $socket->read()) {
                $all .= $next;
            }
            return $all;
        }
    }
?>