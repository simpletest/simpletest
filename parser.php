<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
    }
    define("LEXER_ENTER", 1);
    define("LEXER_MATCHED", 2);
    define("LEXER_UNMATCHED", 3);
    define("LEXER_EXIT", 4);
    define("LEXER_SPECIAL", 5);
    
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
            return ($this->_regex = "/" . implode("|", $this->_patterns) . "/msS");
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
     *    Some optimisation to make the sure the
     *    content is only scanned by the PHP regex
     *    parser once. Lexer modes must not start
     *    with leading underscores.
     */
    class SimpleLexer {
        var $_regexes;
        var $_parser;
        var $_mode;
        var $_mode_handlers;
        
        /**
         *    Sets up the lexer.
         *    @param $parser     Handling strategy by
         *                       reference.
         *    @param $start      Starting handler.
         *    @public
         */
        function SimpleLexer(&$parser, $start = "accept") {
            $this->_regexes = array();
            $this->_parser = &$parser;
            $this->_mode = new StateStack($start);
            $this->_mode_handlers = array();
        }
        
        /**
         *    Adds a token search pattern for a particular
         *    parsing mode. The pattern does not change the
         *    current mode.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $mode         Should only apply this
         *                         pattern when dealing with
         *                         this type of input.
         *    @public
         */
        function addPattern($pattern, $mode = "accept") {
            if (!isset($this->_regexes[$mode])) {
                $this->_regexes[$mode] = new ParallelRegex();
            }
            $this->_regexes[$mode]->addPattern($pattern);
        }
        
        /**
         *    Adds a pattern that will enter a new parsing
         *    mode. Useful for entering parenthesis, strings,
         *    tags, etc.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $mode         Should only apply this
         *                         pattern when dealing with
         *                         this type of input.
         *    @param $new_mode     Change parsing to this new
         *                         nested mode.
         *    @public
         */
        function addEntryPattern($pattern, $mode, $new_mode) {
            if (!isset($this->_regexes[$mode])) {
                $this->_regexes[$mode] = new ParallelRegex();
            }
            $this->_regexes[$mode]->addPattern($pattern, $new_mode);
        }
        
        /**
         *    Adds a pattern that will exit the current mode
         *    and re-enter the previous one.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $mode         Mode to leave.
         *    @public
         */
        function addExitPattern($pattern, $mode) {
            if (!isset($this->_regexes[$mode])) {
                $this->_regexes[$mode] = new ParallelRegex();
            }
            $this->_regexes[$mode]->addPattern($pattern, "__exit");
        }
        
        /**
         *    Adds a pattern that has a special mode.
         *    Acts as an entry and exit pattern in one go.
         *    @param $pattern      Perl style regex, but ( and )
         *                         lose the usual meaning.
         *    @param $mode         Should only apply this
         *                         pattern when dealing with
         *                         this type of input.
         *    @param $special      Use this mode for this one token.
         *    @public
         */
        function addSpecialPattern($pattern, $mode, $special) {
            if (!isset($this->_regexes[$mode])) {
                $this->_regexes[$mode] = new ParallelRegex();
            }
            $this->_regexes[$mode]->addPattern($pattern, "_$special");
        }
        
        /**
         *    Adds a mapping from a mode to another handler.
         *    @param $mode        Mode to be remapped.
         *    @param $handler     New target handler.
         *    @public
         */
        function mapHandler($mode, $handler) {
            $this->_mode_handlers[$mode] = $handler;
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
            if (!isset($this->_parser)) {
                return false;
            }
            $length = strlen($raw);
            while (is_array($parsed = $this->_reduce($raw))) {
                list($unmatched, $matched, $mode) = $parsed;
                if (!$this->_dispatchTokens($unmatched, $matched, $mode)) {
                    return false;
                }
                if (strlen($raw) == $length) {
                    return false;
                }
                $length = strlen($raw);
            }
            if (!$parsed) {
                return false;
            }
            return $this->_invokeParser($raw, LEXER_UNMATCHED);
        }
        
        /**
         *    Sends the matched token and any leading unmatched
         *    text to the parser changing the lexer to a new
         *    mode if one is listed.
         *    @param $unmatched    Unmatched leading portion.
         *    @param $matched      Actual token match.
         *    @param $mode         Mode after match. The "_exit"
         *                         mode causes a stack pop. An
         *                         false mode causes no change.
         *    @return              False if there was any error
         *                         from the parser.
         *    @private
         */
        function _dispatchTokens($unmatched, $matched, $mode = false) {
            if (!$this->_invokeParser($unmatched, LEXER_UNMATCHED)) {
                return false;
            }
            if ($mode === "__exit") {
                if (!$this->_invokeParser($matched, LEXER_EXIT)) {
                    return false;
                }
                return $this->_mode->leave();
            }
            if (strncmp($mode, "_", 1) == 0) {
                $mode = substr($mode, 1);
                $this->_mode->enter($mode);
                if (!$this->_invokeParser($matched, LEXER_SPECIAL)) {
                    return false;
                }
                return $this->_mode->leave();
            }
            if (is_string($mode)) {
                $this->_mode->enter($mode);
                return $this->_invokeParser($matched, LEXER_ENTER);
            }
            return $this->_invokeParser($matched, LEXER_MATCHED);
        }
        
        /**
         *    Calls the parser method named after the current
         *    mode. Empty content will be ignored.
         *    @param $content        Text parsed.
         *    @param $is_match       Token is recognised rather
         *                           than unparsed data.
         *    @private
         */
        function _invokeParser($content, $is_match) {
            if (!$content) {
                return true;
            }
            $handler = $this->_mode->getCurrent();
            if (isset($this->_mode_handlers[$handler])) {
                $handler = $this->_mode_handlers[$handler];
            }
            return $this->_parser->$handler($content, $is_match);
        }
        
        /**
         *    Tries to match a chunk of text and if successful
         *    removes the recognised chunk and any leading
         *    unparsed data. Empty strings will not be matched.
         *    @param $raw         The subject to parse. This is the
         *                        content that will be eaten.
         *    @return             Three item list of unparsed
         *                        content followed by the
         *                        recognised token. True
         *                        if no match, false if there
         *                        is an parsing error.
         *    @private
         */
        function _reduce(&$raw) {
            if (!isset($this->_regexes[$this->_mode->getCurrent()])) {
                return false;
            }
            if ($raw == "") {
                return true;
            }
            if ($action = $this->_regexes[$this->_mode->getCurrent()]->match($raw, $match)) {
                $count = strpos($raw, $match);
                $unparsed = substr($raw, 0, $count);
                $raw = substr($raw, $count + strlen($match));
                return array($unparsed, $match, $action);
            }
            return true;
        }
    }
    
    /**
     *    Converts HTML tokens into selected SAX events.
     */
    class HtmlSaxParser {
        
        /**
         *    Sets the listener and lexer.
         *    @param $listener SAX event handler.
         *    @public
         */
        function HtmlSaxParser(&$listener) {
        }
        
        /**
         *    Sets up the matching lexer.
         *    @param $parser    Event generator, usually $self.
         *    @return           Lexer suitable for this parser.
         *    @public
         */
        function &createLexer(&$parser) {
            
            $lexer = &new SimpleLexer($parser, "ignore");
            $lexer->mapHandler("tag", "acceptStartToken");
            $lexer->addSpecialPattern("</a>", "ignore", "acceptEndToken");
            $lexer->addEntryPattern("<a", "ignore", "tag");
            $lexer->addExitPattern(">", "tag");
            return $lexer;
        }
        
        /**
         *    Runs the content through the held lexer.
         *    @param $raw      Page text to parse.
         *    @return          False if parse error.
         *    @public
         */
        function parse($raw) {
        }
        
        /**
         *    Accepts a token from the tag mode.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function acceptStartToken($token) {
        }
        
        /**
         *    Accepts a token from the end tag mode.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function acceptEndToken($token) {
        }
        
        /**
         *    Part of the tag data.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function acceptAttributeToken($token) {
        }
        
        /**
         *    A character entity.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function acceptEntityToken($token) {
        }
        
        /**
         *    Character data between tags regarded as
         *    important.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function acceptTextToken($token) {
        }
        
        /**
         *    Incoming data to be ignored.
         *    @param $token    Incoming characters.
         *    @return          False if parse error.
         *    @public
         */
        function ignore($token) {
        }
    }
?>