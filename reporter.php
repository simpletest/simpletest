<?php
    // $Id$
    
    if (!defined("SIMPLE_TEST")) {
        define("SIMPLE_TEST", "simpletest/");
    }
    require_once(SIMPLE_TEST . 'runner.php');
    
    /**
     *    Sample minimal test displayer. Generates only
     *    failure messages and a pass count.
     */
    class HtmlReporter extends SimplePageReporter {
        
        /**
         *    Does nothing yet. The first output will
         *    be sent on the first test start. For use
         *    by a web browser.
         *    @access public
         */
        function HtmlReporter() {
            $this->SimplePageReporter();
        }
        
        /**
         *    Paints the top of the web page setting the
         *    title to the name of the starting test.
         *    @param $test_name        Name class of test.
         *    @access public
         */
        function paintHeader($test_name) {
            $this->sendNoCacheHeaders();
            print "<html>\n<head>\n<title>$test_name</title>\n";
            print "<style type=\"text/css\">\n";
            print $this->_getCss() . "\n";
            print "</style>\n";
            print "</head>\n<body>\n";
            print "<h1>$test_name</h1>\n";
            flush();
        }
        
        /**
         *    Send the headers necessary to ensure the page is
         *    reloaded on every request. Otherwise you could be
         *    scratching your head over out of date test data.
         */
        function sendNoCacheHeaders() {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
        }
        
        /**
         *    Paints the CSS. Add additional styles here.
         *    @return             CSS code as text.
         *    @access protected
         */
        function _getCss() {
            return ".fail { color: red; } pre { background-color: lightgray; }";
        }
        
        /**
         *    Paints the end of the test with a summary of
         *    the passes and failures.
         *    @param $test_name        Name class of test.
         *    @access public
         */
        function paintFooter($test_name) {
            $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
            print "<div style=\"";
            print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
            print "\">";
            print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
            print " test cases complete:\n";
            print "<strong>" . $this->getPassCount() . "</strong> passes, ";
            print "<strong>" . $this->getFailCount() . "</strong> fails and ";
            print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
            print "</div>\n";
            print "</body>\n</html>\n";
        }
        
        /**
         *    Paints the test failure with a breadcrumbs
         *    trail of the nesting test suites below the
         *    top level test.
         *    @param $message        Failure message displayed in
         *                           the context of the other tests.
         *    @access public
         */
        function paintFail($message) {
            parent::paintFail($message);
            print "<span class=\"fail\">Fail</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode("-&gt;", $breadcrumb);
            print "-&gt;" . htmlentities($message) . "<br />\n";
        }
        
        /**
         *    Paints a PHP error or exception.
         *    @param $message        Message is ignored.
         *    @access public
         *    @abstract
         */
        function paintException($message) {
            parent::paintException($message);
            print "<span class=\"fail\">Exception</span>: ";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode("-&gt;", $breadcrumb);
            print "-&gt;<strong>" . htmlentities($message) . "</strong><br />\n";
        }
        
        /**
         *    Paints formatted text such as dumped variables.
         *    @param $message        Text to show.
         *    @access public
         */
        function paintFormattedMessage($message) {
            print "<pre>$message</pre>";
        }
    }
    
    /**
     *    Sample minimal test displayer. Generates only
     *    failure messages and a pass count. For command
     *    line use. I've tried to make it look like JUnit,
     *    but I wanted to output the errors as they arrived
     *    which meant dropping the dots.
     */
    class TextReporter extends SimplePageReporter {
        
        /**
         *    Does nothing yet. The first output will
         *    be sent on the first test start.
         *    @access public
         */
        function TextReporter() {
            $this->SimplePageReporter();
        }
        
        /**
         *    Paints the title only.
         *    @param $test_name        Name class of test.
         *    @access public
         */
        function paintHeader($test_name) {
            print "$test_name\n";
            flush();
        }
        
        /**
         *    Paints the end of the test with a summary of
         *    the passes and failures.
         *    @param $test_name        Name class of test.
         *    @access public
         */
        function paintFooter($test_name) {
            if ($this->getFailCount() + $this->getExceptionCount() == 0) {
                print "OK\n";
            } else {
                print "FAILURES!!!\n";
            }
            print "Test cases run: " . $this->getTestCaseProgress() .
                    "/" . $this->getTestCaseCount() .
                    ", Failures: " . $this->getFailCount() .
                    ", Exceptions: " . $this->getExceptionCount() . "\n";
                    
        }
        
        /**
         *    Paints the test failure as a stack trace.
         *    @param $message        Failure message displayed in
         *                           the context of the other tests.
         *    @access public
         */
        function paintFail($message) {
            parent::paintFail($message);
            print $this->getFailCount() . ") $message\n";
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
            print "\n";
        }
        
        /**
         *    Paints a PHP error or exception.
         *    @param $message        Message is ignored.
         *    @access public
         *    @abstract
         */
        function paintException($message) {
            parent::paintException($message);
        }
        
        /**
         *    Paints formatted text such as dumped variables.
         *    @param $message        Text to show.
         *    @access public
         */
        function paintFormattedMessage($message) {
            print "$message\n";
        }
        
        /**
         *    Static check for running in the comand line.
         *    @return Boolean        True if CLI.
         *    @access public
         *    @static
         */
        function inCli() {
            return array_key_exists('_', $_SERVER);
        }
    }
    
    /**
     *    @deprecated
     */
    class CommandLineReporter extends TextReporter {
        function CommandLineReporter() {
            $this->TextReporter();
        }
    }
    
    /**
     *    Sample minimal test displayer. Creates the XML
     *    needed for remote communication by SimpleTest.
     */
    class XmlReporter extends SimplePageReporter {
        var $_indent;
        
        /**
         *    Does nothing yet.
         *    @access public
         */
        function XmlReporter($indent = '  ') {
            $this->SimplePageReporter();
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
         *    Paints the start of a test. Will also paint
         *    the document headerif this is the
         *    first test. Will stash the size if the first
         *    start.
         *    @param string $test_name   Name of test that is starting.
         *    @param integer $size       Number of test cases starting.
         *    @access public
         */
        function paintStart($test_name, $size) {
            parent::paintStart($test_name, $size);
            print $this->_getIndent();
            print "<st:start>\n";
            print $this->_getIndent(1);
            print "<st:name>" . $this->toParsedXml($test_name) . "</st:name>\n";
        }
        
        /**
         *    Paints the end of a test. Will paint the page
         *    footer if the stack of tests has unwound.
         *    @param string $test_name   Name of test that is ending.
         *    @param integer $progress   Number of test cases ending.
         *    @access public
         */
        function paintEnd($test_name, $progress) {
            print $this->_getIndent();
            print "</st:start>\n";
            parent::paintEnd($test_name, $progress);
        }
        
        /**
         *    Increments the pass count.
         *    @param string $message        Message is ignored.
         *    @access public
         */
        function paintPass($message) {
            parent::paintPass($message);
            print $this->_getIndent();
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
            print $this->_getIndent();
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
            print $this->_getIndent();
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
