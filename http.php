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
    class SimpleHttpResponse {
        
        /**
         *    Constructor.
         *    @param $socket        Network connection to fetch
         *                          response text from.
         *    @public
         */
        function SimpleHttpResponse(&$socket) {
        }
    }
?>