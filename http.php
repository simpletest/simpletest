<?php
    // $Id$

    /**
     *    Cookie data holder. A passive class.
     */
    class SimpleCookie {
        var $_host;
        var $_name;
        var $_value;
        var $_path;
        var $_expiry;
        
        /**
         *    Constructor. Sets the stored values.
         *    @param $name            Cookie key.
         *    @param $value           Value of cookie.
         *    @param $path            Cookie path if not host wide.
         *    @param $expiry          Expiry date as string.
         */
        function SimpleCookie($name, $value = "", $path = "", $expiry = "") {
            $this->_host = "localhost";
            $this->_name = $name;
            $this->_value = $value;
            $this->_path = ($path ? $path : "/");
            $this->_expiry = ($expiry ? $expiry : "");
        }
        
        /**
         *    Sets the hostname and optional port.
         *    @param $host            New hostname.
         *    @public
         */
        function setHost($host) {
            $this->_host = $host;
        }
        
        /**
         *    Accessor for the host to which this cookie applies.
         *    @return        Hostname[:port] formatted string.
         *    @public
         */
        function getHost() {
            return $this->_host;
        }
        
        /**
         *    Accessor for name.
         *    @return        Cookie key.
         *    @public
         */
        function getName() {
            return $this->_name;
        }
        
        /**
         *    Accessor for value. A deleted cookie will
         *    have an empty string for this.
         *    @return        Cookie value.
         *    @public
         */
        function getValue() {
            return $this->_value;
        }
        
        /**
         *    Accessor for path.
         *    @return        Valid cookie path.
         *    @public
         */
        function getPath() {
            return $this->_path;
        }
        
        /**
         *    Accessor for expiry.
         *    @return        Expiry string.
         *    @public
         */
        function getExpiry() {
            return $this->_expiry;
        }
    }

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
        function &fetch($socket = "") {
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
            return $this->_createResponse($socket);
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
        
        /**
         *    Wraps the socket in a response parser.
         *    @param $socket        Responding socket.
         *    @return               Parsed response object.
         *    @protected
         */
        function &_createResponse(&$socket) {
            return new SimpleHttpResponse($socket);
        }
    }
    
    /**
     *    Basic HTTP response.
     */
    class SimpleHttpResponse extends StickyError {
        var $_content;
        var $_response_code;
        var $_http_version;
        var $_mime_type;
        var $_cookies;
        
        /**
         *    Constructor. Reads and parses the incoming
         *    content and headers.
         *    @param $socket        Network connection to fetch
         *                          response text from.
         *    @public
         */
        function SimpleHttpResponse(&$socket) {
            $this->StickyError();
            $this->_content = "";
            $this->_response_code = 0;
            $this->_http_version = 0;
            $this->_mime_type = "";
            $this->_cookies = array();
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
         *    Accessor for parsed HTTP protocol version.
         *    @return            HTTP error code integer.
         *    @public
         */
        function getHttpVersion() {
            return $this->_http_version;            
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
         *    Accessor for any new cookies.
         *    @return        Hash of cookie names and values.
         *    @public
         */
        function getNewCookies() {
            return $this->_cookies;
        }
        
        /**
         *    Called on each header line. Subclasses should
         *    add behaviour to this method for their
         *    particular header types.
         *    @param $header_line        One line of header.
         *    @protected
         */
        function _parseHeaderLine($header_line) {
            if (preg_match('/HTTP\/(\d+\.\d+)\s+(.*?)\s/i', $header_line, $matches)) {
                $this->_http_version = $matches[1];
                $this->_response_code = $matches[2];
            }
            if (preg_match('/Content-type:\s*(.*)/i', $header_line, $matches)) {
                $this->_mime_type = trim($matches[1]);
            }
            if (preg_match('/Set-cookie:(.*)/i', $header_line, $matches)) {
                $this->_cookies[] = $this->_parseCookie($matches[1]);
            }
        }
        
        /**
         *    Parse the Set-cookie content.
         *    @param $cookie_line    Text after "Set-cookie:"
         *    @return                New cookie object.
         *    @private
         */
        function _parseCookie($cookie_line) {
            $parts = split(";", $cookie_line);
            $cookie = array();
            preg_match('/\s*(.*?)\s*=(.*)/', array_shift($parts), $cookie);
            foreach ($parts as $part) {
                if (preg_match('/\s*(.*?)\s*=(.*)/', $part, $matches)) {
                    $cookie[$matches[1]] = trim($matches[2]);
                }
            }
            return new SimpleCookie(
                    $cookie[1],
                    trim($cookie[2]),
                    isset($cookie["path"]) ? $cookie["path"] : "",
                    isset($cookie["expires"]) ? $cookie["expires"] : "");
        }
        
        /**
         *    Reads the whole of the socket output into a
         *    single string.
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