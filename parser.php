<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    
    /**
     *    Compounded regular expression. Any of
     *    the contained patterns could match.
     */
    class CompoundRegex {
        var $_patterns;
        
        /**
         *    Constructor. Starts with no patterns.
         */
        function CompoundRegex() {
            $this->_patterns = array();
        }
        
        /**
         *    Adds a pattern.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @public
         */
        function addPattern($pattern) {
            $this->_patterns[] = $pattern;
        }
        
        /**
         *    Attempts to match all patterns at once against
         *    a string.
         *    @param $subject      String to match against.
         *    @param $match        First matched portion of
         *                         subject.
         *    @return              True on success.
         *    @public
         */
        function match($subject, &$match) {
            if (count($this->_patterns) == 0) {
                return false;
            }
            $result = preg_match($this->_getCompoundedRegex(),$subject, $matches);
            $match = $matches[0];
            return (boolean)$result;
        }
        
        /**
         *    Compounds the patterns into a single
         *    regular expression separated with the
         *    "or" operator.
         *    @param $patterns    List of patterns in order.
         *    @private
         */
        function _getCompoundedRegex() {
            for ($i = 0; $i < count($this->_patterns); $i++) {
                $this->_patterns[$i] = '(' . str_replace(
                        array('/', '(', ')'),
                        array('\/', '\(', '\)'),
                        $this->_patterns[$i]) . ')';
            }
            return "/" . implode("|", $this->_patterns) . "/ms";
        }
    }
    
    /**
     *    Accepts text and breaks it into tokens.
     */
    class SimpleLexer {
        var $_pattern_groups;
        var $_handler;
        var $_mode_stack;
        
        /**
         *    Sets up the lexer.
         *    @param $handler    Handling strategy by
         *                       reference.
         *    @public
         */
        function SimpleLexer(&$handler, $starting_mode = "_default") {
            $this->_pattern_groups = array();
            $this->_handler = &$handler;
            $this->_mode_stack = array($starting_mode);
        }
        
        /**
         *    Adds a splitting pattern.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $mode         Should only apply this
         *                         pattern when dealing with
         *                         this type of input.
         *    @public
         */
        function addPattern($pattern, $mode = "_default") {
            $this->_pattern_groups[$mode][] = $pattern;
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
            if (!isset($this->_pattern_groups[$this->getCurrentMode()]) || count($this->_pattern_groups[$this->getCurrentMode()]) == 0) {
                return (!$raw || $this->_handler->acceptUnparsed($raw));
            }
            $length = strlen($raw);
            while ($raw && preg_match($this->_getRegex($this->getCurrentMode()), $raw, $matches)) {
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
         *    Gets the compounded regex.
         *    @param $mode   Patterns for this mode only.
         *    @return        Regex of combined patterns.
         *    @private
         */
        function _getRegex($mode) {
            if (!isset($this->_pattern_groups[$mode])) {
                $this->_pattern_groups[$mode] = array();
            }
            return $this->_compoundRegex($this->_pattern_groups[$mode]);
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
        
        /**
         *    Accessor for the current parsing mode.
         *    @return        Mode label currntly in use.
         *    @public
         */
        function getCurrentMode() {
            return $this->_mode_stack[count($this->_mode_stack) - 1];
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