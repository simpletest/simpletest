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
        
        /**
         *    Does nothing yet.
         *    @access public
         */
        function XmlReporter($indent = '  ') {
            $this->SimpleReporter();
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
            print "<st:group size=\"$size\">\n";
            print $this->_getIndent(1);
            print "<st:name>" . $this->toParsedXml($test_name) . "</st:name>\n";
        }
        
        /**
         *    Paints the end of a group test.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintGroupEnd($test_name) {
            print $this->_getIndent();
            print "</st:group>\n";
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
            print "<st:case>\n";
            print $this->_getIndent(1);
            print "<st:name>" . $this->toParsedXml($test_name) . "</st:name>\n";
        }
        
        /**
         *    Paints the end of a test case.
         *    @param string $test_name   Name of test that is ending.
         *    @access public
         */
        function paintCaseEnd($test_name) {
            print $this->_getIndent();
            print "</st:case>\n";
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
            print "<st:test>\n";
            print $this->_getIndent(1);
            print "<st:name>" . $this->toParsedXml($test_name) . "</st:name>\n";
        }
        
        /**
         *    Paints the end of a test method.
         *    @param string $test_name   Name of test that is ending.
         *    @param integer $progress   Number of test cases ending.
         *    @access public
         */
        function paintMethodEnd($test_name) {
            print $this->_getIndent();
            print "</st:test>\n";
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
            print "<st:pass>";
            print $this->toParsedXml($message);
            print "</st:pass>\n";
        }
        
        /**
         *    Increments the fail count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintFail($message) {
            parent::paintFail($message);
            print $this->_getIndent(1);
            print "<st:fail>";
            print $this->toParsedXml($message);
            print "</st:fail>\n";
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
            print "<st:exception>";
            print $this->toParsedXml($message);
            print "</st:exception>\n";
        }
        
        /**
         *    Paints the test document header.
         *    @param string $test_name     First test top level
         *                                 to start.
         *    @access public
         *    @abstract
         */
        function paintHeader($test_name) {
            print "<?xml version=\"1.0\" xmlns:st=\"www.lastcraft.com/SimpleTest/Beta3/Report\"?>\n";
            print "<st:run>\n";
        }
        
        /**
         *    Paints the test document footer.
         *    @param string $test_name        The top level test.
         *    @access public
         *    @abstract
         */
        function paintFooter($test_name) {
            print "</st:run>\n";
        }
    }
?>
