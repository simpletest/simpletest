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
    class SimpleQueryString {
        var $_request;
        
        /**
         *    Starts empty.
         *    @param array $query/SimpleQueryString  Hash of parameters.
         *                                           Multiple values are
         *                                           as lists on a single key.
         *    @access public
         */
        function SimpleQueryString($query = false) {
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
         *    @param array $query/SimpleQueryString  Hash of parameters.
         *                                           Multiple values are
         *                                           as lists on a single key.
         *    @access public
         */
        function merge($query) {
            if (is_object($query)) {
                foreach ($query->getKeys() as $key) {
                    $this->add($key, $query->getValue($key));
                }
            } else {
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
         *    Gets all parameters as structured hash. Repeated
         *    values are list values.
         *    @return array        Hash of keys and value sets.
         *    @access public
         */
        function getAll() {
            $values = array();
            foreach ($this->_request as $key => $value) {
                $values[$key] = (count($value) == 1 ? $value[0] : $value);
            }
            return $values;
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
     *    URL parser to replace parse_url() PHP function which
     *    got broken in PHP 4.3.0. Adds some browser specific
     *    functionality such as expandomatic expansion.
     *    Guesses a bit trying to separate the host from
     *    the path.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleUrl {
        var $_scheme;
        var $_username;
        var $_password;
        var $_host;
        var $_port;
        var $_path;
        var $_request;
        
        /**
         *    Constructor. Parses URL into sections.
         *    @param string $url        Incoming URL.
         *    @access public
         */
        function SimpleUrl($url) {
            $this->_scheme = $this->_chompScheme($url);
            list($this->_username, $this->_password) = $this->_chompLogin($url);
            $this->_host = $this->_chompHost($url);
            $this->_port = false;
            if (preg_match('/(.*?):(.*)/', $this->_host, $host_parts)) {
                $this->_host = $host_parts[1];
                $this->_port = (integer)$host_parts[2];
            }
            $this->_path = $this->_chompPath($url);
            $this->_request = $this->_parseRequest($this->_chompRequest($url));
            $this->_fragment = (strncmp($url, "#", 1) == 0 ? substr($url, 1) : false);
        }
        
        /**
         *    Extracts the scheme part of an incoming URL.
         *    @param string $url   URL so far. The scheme will be
         *                         removed.
         *    @return string       Scheme part or false.
         *    @access private
         */
        function _chompScheme(&$url) {
            if (preg_match('/(.*?):(\/\/)(.*)/', $url, $matches)) {
                $url = $matches[2] . $matches[3];
                return $matches[1];
            }
            return false;
        }
        
        /**
         *    Extracts the username and password from the
         *    incoming URL. The // prefix will be reattached
         *    to the URL after the doublet is extracted.
         *    @param string $url    URL so far. The username and
         *                          password are removed.
         *    @return array         Two item list of username and
         *                          password. Will urldecode() them.
         *    @access private
         */
        function _chompLogin(&$url) {
            $prefix = '';
            if (preg_match('/(\/\/)(.*)/', $url, $matches)) {
                $prefix = $matches[1];
                $url = $matches[2];
            }
            if (preg_match('/(.*?)@(.*)/', $url, $matches)) {
                $url = $prefix . $matches[2];
                $parts = split(":", $matches[1]);
                return array(
                        urldecode($parts[0]),
                        isset($parts[1]) ? urldecode($parts[1]) : false);
            }
            $url = $prefix . $url;
            return array(false, false);
        }
        
        /**
         *    Extracts the host part of an incoming URL.
         *    Includes the port number part. Will extract
         *    the host if it starts with // or it has
         *    a top level domain or it has at least two
         *    dots.
         *    @param string $url    URL so far. The host will be
         *                          removed.
         *    @return string        Host part guess or false.
         *    @access private
         */
        function _chompHost(&$url) {
            if (preg_match('/(\/\/)(.*?)(\/.*|\?.*|#.*|$)/', $url, $matches)) {
                $url = $matches[3];
                return $matches[2];
            }
            if (preg_match('/(.*?)(\.\.\/|\.\/|\/|\?|#|$)(.*)/', $url, $matches)) {
                if (preg_match('/[a-z0-9\-]+\.(com|edu|net|org|gov|mil|int)/i', $matches[1])) {
                    $url = $matches[2] . $matches[3];
                    return $matches[1];
                } elseif (preg_match('/[a-z0-9\-]+\.[a-z0-9\-]+\.[a-z0-9\-]+/i', $matches[1])) {
                    $url = $matches[2] . $matches[3];
                    return $matches[1];
                }
            }
            return false;
        }
        
        /**
         *    Extracts the path information from the incoming
         *    URL. Strips this path from the URL.
         *    @param string $url     URL so far. The host will be
         *                           removed.
         *    @return string         Path part or '/'.
         *    @access private
         */
        function _chompPath(&$url) {
            if (preg_match('/(.*?)(\?|#|$)(.*)/', $url, $matches)) {
                $url = $matches[2] . $matches[3];
                return ($matches[1] ? $matches[1] : '');
            }
            return '';
        }
        
        /**
         *    Strips off the request data.
         *    @param string $url  URL so far. The request will be
         *                        removed.
         *    @return string      Raw request part.
         *    @access private
         */
        function _chompRequest(&$url) {
            if (preg_match('/\?(.*?)(#|$)(.*)/', $url, $matches)) {
                $url = $matches[2] . $matches[3];
                return $matches[1];
            }
            return '';
        }
         
        /**
         *    Breaks the request down into an object.
         *    @param string $raw           Raw request.
         *    @return SimpleQueryString    Parsed data.
         *    @access private
         */
        function _parseRequest($raw) {
            $request = new SimpleQueryString();
            foreach (split("&", $raw) as $pair) {
                if (preg_match('/(.*?)=(.*)/', $pair, $matches)) {
                    $request->add($matches[1], urldecode($matches[2]));
                } elseif ($pair) {
                    $request->add($pair, '');
                }
            }
            return $request;
        }
        
        /**
         *    Accessor for protocol part.
         *    @param string $default    Value to use if not present.
         *    @return string            Scheme name, e.g "http".
         *    @access public
         */
        function getScheme($default = false) {
            return $this->_scheme ? $this->_scheme : $default;
        }
        
        /**
         *    Accessor for user name.
         *    @return string    Username preceding host.
         *    @access public
         */
        function getUsername() {
            return $this->_username;
        }
        
        /**
         *    Accessor for password.
         *    @return string    Password preceding host.
         *    @access public
         */
        function getPassword() {
            return $this->_password;
        }
        
        /**
         *    Accessor for hostname and port.
         *    @param string $default    Value to use if not present.
         *    @return string            Hostname only.
         *    @access public
         */
        function getHost($default = false) {
            return $this->_host ? $this->_host : $default;
        }
        
        /**
         *    Accessor for top level domain.
         *    @return string       Last part of host.
         *    @access public
         */
        function getTld() {
            $path_parts = pathinfo($this->getHost());
            return (isset($path_parts['extension']) ? $path_parts['extension'] : false);
        }
        
        /**
         *    Accessor for port number.
         *    @return integer    TCP/IP port number.
         *    @access public
         */
        function getPort() {
            return $this->_port;
        }        
                
       /**
         *    Accessor for path.
         *    @return string    Full path including leading slash if implied.
         *    @access public
         */
        function getPath() {
            if (! $this->_path && $this->_host) {
                return '/';
            }
            return $this->_path;
        }
        
        /**
         *    Accessor for page if any. This may be a
         *    directory name if ambiguious.
         *    @return            Page name.
         *    @access public
         */
        function getPage() {
            if (! preg_match('/([^\/]*?)$/', $this->getPath(), $matches)) {
                return false;
            }
            return $matches[1];
        }
        
        /**
         *    Gets the path to the page.
         *    @return string       Path less the page.
         *    @access public
         */
        function getBasePath() {
            if (! preg_match('/(.*\/)[^\/]*?$/', $this->getPath(), $matches)) {
                return false;
            }
            return $matches[1];
        }
        
        /**
         *    Accessor for fragment at end of URL after the "#".
         *    @return string    Part after "#".
         *    @access public
         */
        function getFragment() {
            return $this->_fragment;
        }
        
        /**
         *    Accessor for current request parameters
         *    in URL string form
         *    @return string   Form is string "?a=1&b=2", etc.
         *    @access public
         */
        function getEncodedRequest() {
            $query = $this->_request;
            $encoded = $query->asString();
            if ($encoded) {
                return "?$encoded";
            }
            return '';
        }
        
        /**
         *    Encodes parameters as HTTP request parameters.
         *    @param hash $parameters    Request as hash.
         *    @return string             Encoded request.
         *    @access public
         *    @static
         */
        function encodeRequest($parameters) {
            if (! $parameters) {
                return '';
            }
            $query = &new SimpleQueryString();
            foreach ($parameters as $key => $value) {
                $query->add($key, $value);
            }
            return $query->asString();
        }
        
        /**
         *    Accessor for current request parameters
         *    as an object.
         *    @return array   Hash of name and value pairs. The
         *                    values will be lists for repeated items.
         *    @access public
         */
        function getRequest() {
            return $this->_request->getAll();
        }
        
        /**
         *    Adds an additional parameter to the request.
         *    @param string $key            Name of parameter.
         *    @param string $value          Value as string.
         *    @access public
         */
        function addRequestParameter($key, $value) {
            $this->_request->add($key, $value);
        }
        
        /**
         *    Adds additional parameters to the request.
         *    @param hash $parameters   Hash of additional parameters.
         *    @access public
         */
        function addRequestParameters($parameters) {
            if ($parameters) {
                $this->_request->merge($parameters);
            }
        }
        
        /**
         *    Clears down all parameters.
         *    @access public
         */
        function clearRequest() {
            $this->_request = &new SimpleQueryString();
        }
        
        /**
         *    Replaces unknown sections to turn a relative
         *    URL into an absolute one. The base URL can
         *    be either a string or a SimpleUrl object.
         *    @param string/SimpleUrl $base       Base URL.
         *    @access public
         */
        function makeAbsolute($base) {
            if (! is_object($base)) {
                $base = new SimpleUrl($base);
            }
            $scheme = $this->getScheme() ? $this->getScheme() : $base->getScheme();
            $host = $this->getHost() ? $this->getHost() : $base->getHost();
            if (substr($this->_path, 0, 1) == "/") {
                $path = $this->normalisePath($this->_path);
            } else {
                $path = $this->normalisePath($base->getBasePath() . $this->_path);
            }
            $identity = '';
            if ($this->_username && $this->_password) {
                $identity = $this->_username . ':' . $this->_password . '@';
            }
            $encoded = $this->getEncodedRequest();
            $fragment = $this->getFragment() ? '#'. $this->getFragment() : '';
            return new SimpleUrl("$scheme://$identity$host$path$encoded$fragment");
        }
        
        /**
         *    Replaces . and .. sections of the path.
         *    @param string $path    Unoptimised path.
         *    @return string         Path with dots removed if possible.
         *    @access public
         */
        function normalisePath($path) {
            $path = preg_replace('|/[^/]+/\.\./|', '/', $path);
            return preg_replace('|/\./|', '/', $path);
        }
    }
?>