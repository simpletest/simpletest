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
        
        /**
         *    Sets up the lexer.
         *    @public
         */
        function SimpleLexer() {
            $this->_patterns = array();
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
         *    Splits the page text into tokens.
         *    @param $raw        Raw HTML text.
         *    @return            Array of tokens.
         *    @public
         */
        function parse($raw) {
            $tokens = array();
            $regex = $this->_compoundRegex($this->_patterns);
            while ($raw && preg_match($regex, $raw, $matches)) {
                $count = strpos($raw, $matches[0]);
                $unwanted = substr($raw, 0, $count);
                $raw = substr($raw, $count + strlen($matches[0]));
                $this->_handleToken($tokens, $unwanted, $matches[0]);
            }
            $this->_handleToken($tokens, $raw, "");
            return $tokens;
        }
        
        /**
         *    Parses unwanted preamble and a matched tag.
         *    @param $tokens     Output buffer by reference.
         *    @param $unwanted   Unwanted text before match.
         *    @param $match      Matched item.
         *    @protected
         *    @abstract
         */
        function _handleToken(&$tokens, $unwanted, $match) {
            if ($unwanted) {
                array_push($tokens, $unwanted);
            }
            if ($match) {
                array_push($tokens, $match);
            }
        }
        
        /**
         *    Compounds the patterns into a single
         *    regular expression.
         *    @param $patterns    List of patterns in order.
         *    @private
         */
        function _compoundRegex($patterns) {
            if (count($patterns) == 0) {
                return '/(.*)/';
            }
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