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
     *    Some optimisation to make the sure the
     *    content is only scanned by the PHP regex
     *    parser once.
     */
    class SimpleLexer {
        var $_regexes;
        var $_parser;
        var $_mode;
        
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
            $this->_regexes[$mode]->addPattern($pattern, "_exit");
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
            return $this->_invokeParser($raw, false);
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
            if (!$this->_invokeParser($unmatched, false)) {
                return false;
            }
            if ($mode === "_exit") {
                if (!$this->_invokeParser($matched, true)) {
                    return false;
                }
                return $this->_mode->leave();
            }
            if (is_string($mode)) {
                $this->_mode->enter($mode);
            }
            return $this->_invokeParser($matched, true);
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
            return $this->_parser->{$this->_mode->getCurrent()}($content, $is_match);
        }
        
        /**
         *    Tries to match a chunk of text and if successful
         *    removes the recognised chunk and any leading
         *    unparsed data.
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
     *    Accepts HTML and breaks it into tokens.
     */
    class SimpleHtmlLexer extends SimpleLexer {
        
        /**
         *    Sets up the lexer.
         *    @param $parser     Handling strategy by
         *                       reference.
         *    @param $start      Starting mode.
         *    @public
         */
        function SimpleHtmlLexer(&$parser, $start = "ignore") {
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
         *    @param $page       Page to set information in
         *                       for testing purposes.
         *    @return            A page object or false on fail.
         *    @public
         */
        function parse($raw, $page = false) {
            return $page;
        }
    }
    
    /**
     *    A wrapper for a web page.
     */
    class HtmlPage {
        var $_absolute_links;
        var $_relative_links;
        
        /**
         *    Parses a page ready to access it's contents.
         */
        function HtmlPage() {
            $this->_absolute_links = array();
            $this->_relative_links = array();
        }
        
        /**
         *    Adds a link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @param $is_strict  Will accept only correct
         *                       relative URLs: must start
         *                       "/", "./" or "../" or have
         *                       a scheme.
         *    @public
         */
        function addLink($url, $label, $is_strict = false) {
            $parsed_url = new SimpleUrl($url);
            if ($parsed_url->getScheme() && $parsed_url->getHost()) {
                $this->_addAbsoluteLink($url, $label);
                return;
            }
            if (!$is_strict && !$parsed_url->getScheme()) {
                if (!preg_match('/^(\/|\.\/|\.\.\/)/', $url)) {
                    $url = "./" . $url;
                    $parsed_url = new SimpleUrl($url);
                }
            }
            if (!$parsed_url->getHost()) {
                $this->_addRelativeLink($url, $label);
            }
        }
        
        /**
         *    Adds an absolute link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @private
         */
        function _addAbsoluteLink($url, $label) {
            if (!isset($this->_absolute_links[$label])) {
                $this->_absolute_links[$label] = array();
            }
            array_push($this->_absolute_links[$label], $url);
        }
        
        /**
         *    Adds a relative link to the page.
         *    @param $url        Address.
         *    @param $label      Text label of link.
         *    @private
         */
        function _addRelativeLink($url, $label) {
            if (!isset($this->_relative_links[$label])) {
                $this->_relative_links[$label] = array();
            }
            array_push($this->_relative_links[$label], $url);
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return       List of links with scheme of
         *                  http or https and hostname.
         *    @public
         */
        function getAbsoluteLinks() {
            $all = array();
            foreach ($this->_absolute_links as $label => $links) {
                $all = array_merge($all, $links);
            }
            return $all;
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return       List of links without hostname.
         *    @public
         */
        function getRelativeLinks() {
            $all = array();
            foreach ($this->_relative_links as $label => $links) {
                $all = array_merge($all, $links);
            }
            return $all;
        }
        
        /**
         *    Accessor for a URLs by the link label.
         *    @param $label    Text of link.
         *    @return          List of links with that
         *                     label.
         *    @public
         */
        function getUrls($label) {
            $all = array();
            if (isset($this->_absolute_links[$label])) {
                $all = $this->_absolute_links[$label];
            }
            if (isset($this->_relative_links[$label])) {
                $all = array_merge($all, $this->_relative_links[$label]);
            }
            return $all;
        }
    }
?>