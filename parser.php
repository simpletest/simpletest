<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    
    /**
     *    Accepts text and breaks it into tokens.
     */
    class SimpleLexer {
        var $_patterns;
        var $_handler;
        
        /**
         *    Sets up the lexer.
         *    @public
         */
        function SimpleLexer() {
            $this->_patterns = array();
            $this->_handler = null;
        }
        
        /**
         *    Adds a token handler.
         *    @param $handler    Handling strategy by
         *                       reference.
         */
        function setHandler(&$handler) {
            $this->_handler = &$handler;
        }
        
        /**
         *    Adds a splitting pattern.
         *    @param $pattern     Perl style regex, but ( and )
         *                        lose the usual meaning.
         *    @public
         */
        function addPattern($pattern) {
            $this->_patterns[] = $pattern;
        }
        
        /**
         *    Splits the page text into tokens. Will fail
         *    if the handlers report an error or if no
         *    content is consumed.
         *    @param $raw        Raw HTML text.
         *    @return            Array of tokens.
         *    @public
         */
        function parse($raw) {
            if (!isset($this->_handler)) {
                return false;
            }
            if (count($this->_patterns) == 0) {
                return (!$raw || $this->_handler->acceptUnparsed($raw));
            }
            $regex = $this->_compoundRegex($this->_patterns);
            $length = strlen($raw);
            while ($raw && preg_match($regex, $raw, $matches)) {
                $count = strpos($raw, $matches[0]);
                $unparsed = substr($raw, 0, $count);
                $raw = substr($raw, $count + strlen($matches[0]));
                if ($unparsed && !$this->_handler->acceptUnparsed($unparsed)) {
                    return false;
                }
                if ($matches[0] && !$this->_handler->acceptToken($matches[0])) {
                    return false;
                }
                if (strlen($raw) == $length) {
                    return false;
                }
                $length = strlen($raw);
            }
            if ($raw && !$this->_handler->acceptUnparsed($raw)) {
                return false;
            }
            return true;
        }
        
        /**
         *    Compounds the patterns into a single
         *    regular expression.
         *    @param $patterns    List of patterns in order.
         *    @private
         */
        function _compoundRegex($patterns) {
            for ($i = 0; $i < count($patterns); $i++) {
                $patterns[$i] = '(' . str_replace(
                        array('/', '(', ')'),
                        array('\/', '\(', '\)'),
                        $patterns[$i]) . ')';
            }
            return "/" . implode("|", $patterns) . "/ms";
        }
    }
    
    /**
     *    Strategy for dealing with a stream of lexer
     *    tokens.
     */
    class TokenHandler {
        
        /**
         *    Do nothing constructor.
         */
        function TokenHandler() {
        }
        
        /**
         *    Handler for unparsed text preceeding
         *    the next token match.
         *    @param $unparsed    Unparsed content.
         *    @return             False if bad input, true
         *                        if successfully handled.
         *    @public
         */
        function acceptUnparsed($unparsed) {
        }
        
        /**
         *    Handler for next matched token.
         *    @param $token       Matched content.
         *    @return             False if bad input, true
         *                        if successfully handled.
         *    @public
         */
        function acceptToken($token) {
        }
    }
    
    /**
     *    Accepts an array of tokens and uses it to
     *    build a web page model.
     */
    class HtmlParser {
        
        /**
         *    Sets up the parser to receive the input.
         *    @public
         */
        function HtmlParser() {
        }
        
        /**
         *    Parses the page text to create a new web
         *    page document model.
         *    @param $raw        Raw HTML text.
         *    @param $page       Page to set information in.
         *    @return            True if page was parsed
         *                       successfully.
         *    @public
         */
        function parse($raw, &$page) {
            return true;
        }
    }
    
    /**
     *    A container for web page information.
     */
    class HtmlPage {
        
        /**
         *    Creates an empty model.
         */
        function HtmlPage() {
        }
        
        /**
         *    Adds a link to the page.
         */
        function addLink() {
        }
        
        /**
         *    Adds a form element.
         */
        function addFormElement() {
        }
    }
?>