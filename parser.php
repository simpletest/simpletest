<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "./");
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