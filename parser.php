<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    
    /**
     *    Compounded regular expression. Any of
     *    the contained patterns could match and
     *    when one does it's label is returned.
     */
    class ParallelRegex {
        var $_patterns;
        var $_labels;
        var $_regex;
        
        /**
         *    Constructor. Starts with no patterns.
         */
        function ParallelRegex() {
            $this->_patterns = array();
            $this->_labels = array();
            $this->_regex = null;
        }
        
        /**
         *    Adds a pattern with an optional label.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $label        Label of regex to be returned
         *                         on a match.
         *    @public
         */
        function addPattern($pattern, $label = true) {
            $count = count($this->_patterns);
            $this->_patterns[$count] = $pattern;
            $this->_labels[$count] = $label;
            $this->_regex = null;
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
            if (!preg_match($this->_getCompoundedRegex(), $subject, $matches)) {
                $match = "";
                return false;
            }
            $match = $matches[0];
            for ($i = 1; $i < count($matches); $i++) {
                if ($matches[$i]) {
                    return $this->_labels[$i - 1];
                }
            }
            return true;
        }
        
        /**
         *    Compounds the patterns into a single
         *    regular expression separated with the
         *    "or" operator. Caches the regex.
         *    @param $patterns    List of patterns in order.
         *    @private
         */
        function _getCompoundedRegex() {
            if ($this->_regex != null) {
                return $this->_regex;
            }
            for ($i = 0; $i < count($this->_patterns); $i++) {
                $this->_patterns[$i] = '(' . str_replace(
                        array('/', '(', ')'),
                        array('\/', '\(', '\)'),
                        $this->_patterns[$i]) . ')';
            }
            return ($this->_regex = "/" . implode("|", $this->_patterns) . "/ms");
        }
    }
    
    /**
     *    States for a stack machine.
     */
    class StateStack {
        var $_stack;
        
        /**
         *    Constructor. Starts in named state.
         *    @param $start        Starting state name.
         *    @public
         */
        function StateStack($start) {
            $this->_stack = array($start);
        }
        
        /**
         *    Accessor for current state.
         *    @return        State as string.
         *    @public
         */
        function getCurrent() {
            return $this->_stack[count($this->_stack) - 1];
        }
        
        /**
         *    Adds a state to the stack and sets it
         *    to be the current state.
         *    @param $state        New state.
         *    @public
         */
        function enter($state) {
            array_push($this->_stack, $state);
        }
        
        /**
         *    Leaves the current state and reverts
         *    to the previous one.
         *    @return     False if we drop off
         *                the bottom of the list.
         *    @public
         */
        function leave() {
            if (count($this->_stack) == 1) {
                return false;
            }
            array_pop($this->_stack);
            return true;
        }
    }
    
    /**
     *    Accepts text and breaks it into tokens.
     */
    class SimpleLexer {
        var $_regexes;
        var $_handler;
        var $_mode_stack;
        
        /**
         *    Sets up the lexer.
         *    @param $handler    Handling strategy by
         *                       reference.
         *    @public
         */
        function SimpleLexer(&$handler, $starting_mode = "_default") {
            $this->_regexes = array();
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
            if (!isset($this->_regexes[$mode])) {
                $this->_regexes[$mode] = new ParallelRegex();
            }
            $this->_regexes[$mode]->addPattern($pattern);
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
            $length = strlen($raw);
            while (list($unparsed, $match) = $this->_reduce($raw)) {
                if ($unparsed && !$this->_handler->acceptUnparsed($unparsed)) {
                    return false;
                }
                if ($match && !$this->_handler->acceptToken($match)) {
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
         *    Tries to match a chunk of text and if successful
         *    removes the recognised chunk and any leading
         *    unparsed data.
         *    @param $raw         The subject to parse.
         *    @return             Two item list of unparsed
         *                        content followed by the
         *                        recognised token. False
         *                        if no match.
         *    @private
         */
        function _reduce(&$raw) {
            if (!isset($this->_regexes[$this->getCurrentMode()])) {
                return false;
            }
            if (!$this->_regexes[$this->getCurrentMode()]->match($raw, $match)) {
                return false;
            }
            $count = strpos($raw, $match);
            $unparsed = substr($raw, 0, $count);
            $raw = substr($raw, $count + strlen($match));
            return array($unparsed, $match);
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