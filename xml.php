<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'runner.php');
    
    /**
     *    Creates the XML needed for remote communication
     *    by SimpleTest.
     */
    class XmlReporter extends SimpleReporter {
        var $_indent;
        var $_namespace;
        
        /**
         *    Does nothing yet.
         *    @access public
         */
        function XmlReporter($namespace = 'st', $indent = '  ') {
            $this->SimpleReporter();
            $this->_namespace = $namespace;
            $this->_indent = $indent;
        }
        
        /**
         *    Calculates the pretty printing indent level
         *    from the current level of nesting.
         *    @param integer $offset  Extra indenting level.
         *    @return string          Leading space.
         *    @access protected
         */
        function _getIndent($offset = 0) {
            return str_repeat(
                    $this->_indent,
                    count($this->getTestList()) + $offset);
        }
        
        /**
         *    Converts character string to parsed XML
         *    entities string.
         *    @param string text        Unparsed character data.
         *    @return string            Parsed character data.
         *    @access public
         */
        function toParsedXml($text) {
            return str_replace(
                    array('&', '<', '>', '"', '\''),
                    array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;'),
                    $text);
        }
        
        /**
         *    Paints the start of a group test.
         *    @param string $test_name   Name of test that is starting.
         *    @param integer $size       Number of test cases starting.
         *    @access public
         */
        function paintGroupStart($test_name, $size) {
            parent::paintGroupStart($test_name, $size);
            print $this->_getIndent();
            print "<" . $this->_namespace . ":group size=\"$size\">\n";
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":name>" .
                    $this->toParsedXml($test_name) .
                    "</" . $this->_namespace . ":name>\n";
        }
        
        /**
         *    Paints the end of a group test.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintGroupEnd($test_name) {
            print $this->_getIndent();
            print "</" . $this->_namespace . ":group>\n";
            parent::paintGroupEnd($test_name);
        }
        
        /**
         *    Paints the start of a test case.
         *    @param string $test_name   Name of test that is starting.
         *    @access public
         */
        function paintCaseStart($test_name) {
            parent::paintCaseStart($test_name);
            print $this->_getIndent();
            print "<" . $this->_namespace . ":case>\n";
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":name>" .
                    $this->toParsedXml($test_name) .
                    "</" . $this->_namespace . ":name>\n";
        }
        
        /**
         *    Paints the end of a test case.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintCaseEnd($test_name) {
            print $this->_getIndent();
            print "</" . $this->_namespace . ":case>\n";
            parent::paintCaseEnd($test_name);
        }
        
        /**
         *    Paints the start of a test method.
         *    @param string $test_name   Name of test that is starting.
         *    @access public
         */
        function paintMethodStart($test_name) {
            parent::paintMethodStart($test_name);
            print $this->_getIndent();
            print "<" . $this->_namespace . ":test>\n";
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":name>" .
                    $this->toParsedXml($test_name) .
                    "</" . $this->_namespace . ":name>\n";
        }
        
        /**
         *    Paints the end of a test method.
         *    @param string $test_name   Name of test that is ending.
         *    @param integer $progress   Number of test cases ending.
         *    @access public
         */
        function paintMethodEnd($test_name) {
            print $this->_getIndent();
            print "</" . $this->_namespace . ":test>\n";
            parent::paintMethodEnd($test_name);
        }
        
        /**
         *    Increments the pass count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintPass($message) {
            parent::paintPass($message);
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":pass>";
            print $this->toParsedXml($message);
            print "</" . $this->_namespace . ":pass>\n";
        }
        
        /**
         *    Increments the fail count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintFail($message) {
            parent::paintFail($message);
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":fail>";
            print $this->toParsedXml($message);
            print "</" . $this->_namespace . ":fail>\n";
        }
        
        /**
         *    Paints a PHP error or exception.
         *    @param string $message        Message is ignored.
         *    @access public
         *    @abstract
         */
        function paintException($message) {
            parent::paintException($message);
            print $this->_getIndent(1);
            print "<" . $this->_namespace . ":exception>";
            print $this->toParsedXml($message);
            print "</" . $this->_namespace . ":exception>\n";
        }
        
        /**
         *    Paints the test document header.
         *    @param string $test_name     First test top level
         *                                 to start.
         *    @access public
         *    @abstract
         */
        function paintHeader($test_name) {
            print "<?xml version=\"1.0\" xmlns:" . $this->_namespace .
                    "=\"www.lastcraft.com/SimpleTest/Beta3/Report\"?>\n";
            print "<" . $this->_namespace . ":run>\n";
        }
        
        /**
         *    Paints the test document footer.
         *    @param string $test_name        The top level test.
         *    @access public
         *    @abstract
         */
        function paintFooter($test_name) {
            print "</" . $this->_namespace . ":run>\n";
        }
    }
    
    /**
     *    Parser for importing the output of the XmlReporter.
     */
    class SimpleXmlImporter {
        var $_listener;
        var $_expat;
        
        /**
         *    Loads a listener with the SimpleReporter
         *    interface.
         *    @param SimpleReporter        Listener of tag events.
         *    @acces public
         */
        function SimpleXmlImporter(&$listener) {
            $this->_listener = &$listener;
            $this->_expat = &$this->_createParser();
        }
        
        /**
         *    Parses a block of XML sending the results to
         *    the listener.
         *    @param string $chunk        Block of text to read.
         *    @return boolean             True if valid XML.
         *    @access public
         */
        function parse($chunk) {
            if (! xml_parse($this->_expat, $chunk)) {
                trigger_error(xml_error_string(xml_get_error_code($this->_expat)));
                return false;
            }
            return true;
        }
        
        /**
         *    Sets up expat as the XML parser.
         *    @return resource        Expat handle.
         *    @access protected
         */
        function &_createParser() {
            $expat = xml_parser_create();
            xml_set_object($expat, $this);
            xml_set_element_handler($expat, '_startElement', '_endElement');
            xml_set_character_data_handler($expat, '_addContent');
            xml_set_processing_instruction_handler($expat, "_processingInstruction");
            xml_set_default_handler($expat, "_default");
            xml_set_external_entity_ref_handler($expat, "_externalEntityReference");
            return $expat;
        }

        /**
         *    Start of element event.
         *    @param resource $expat     Parser handle.
         *    @param string $tag         Element name.
         *    @param hash $attributes    Name value pairs.
         *                               Attributes without content
         *                               are marked as true.
         *    @access protected
         */
        function _startElement($expat, $tag, $attributes) {
            if ($tag == "GROUP") {
                $this->_listener->paintGroupStart();
            }
        }
        
        /**
         *    End of element event.
         *    @param resource $expat     Parser handle.
         *    @param string $tag         Element name.
         *    @access protected
         */
        function _endElement($expat, $tag) {
            if ($tag == "GROUP") {
                $this->_listener->paintGroupEnd();
            }
        }
        
        /**
         *    Content between start and end elements.
         *    @param resource $expat     Parser handle.
         *    @param string $text        Usually output messages.
         *    @access protected
         */
        function _addContent($expat, $text) {
            return true;
        }
        
        /**
         *    XML and Doctype handler.
         *    @param resource $expat     Parser handle.
         *    @param string $default     Text of default content.
         *    @access protected
         */
        function _default($expat, $default) {
        }
    }
?>
