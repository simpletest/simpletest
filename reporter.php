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
    class TextReporter extends TestDisplay {
        
        /**
         *    Does nothing yet. The first output will
         *    be sent on the first test start.
         *    @access public
         */
        function TextReporter() {
            $this->TestDisplay();
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
    class XmlReporter extends TestDisplay {
        
        /**
         *    Does nothing yet.
         *    @access public
         */
        function XmlReporter() {
            $this->TestDisplay();
        }
        
        /**
         *    Paints the start of a test.
         *    @param $test_name     Name of test or other label.
         *    @param $size          Number of test cases starting.
         *    @access public
         */
        function paintStart($test_name, $size) {
        }
        
        /**
         *    Paints the end of a test.
         *    @param $test_name     Name of test or other label.
         *    @param $progress      Number of cases just finished.
         *    @access public
         */
        function paintEnd($test_name, $progress) {
        }
        
        /**
         *    Paints a pass. This will often output nothing.
         *    @param $message        Passing message.
         *    @access public
         */
        function paintPass($message) {
        }
        
        /**
         *    Paints a failure.
         *    @param $message        Failure message from test.
         *    @access public
         */
        function paintFail($message) {
        }
        
        /**
         *    Paints a simple supplementary message.
         *    @param $message        Text to display.
         *    @access public
         */
        function paintMessage($message) {
        }
        
        /**
         *    Paints a formatted ASCII message such as a
         *    variable dump.
         *    @param $message        Text to display.
         *    @access public
         */
        function paintFormattedMessage($message) {
        }
        
        /**
         *    By default just ignores user generated
         *    events.
         *    @param $type       Event type as text.
         *    @param $payload    Message or object.
         *    @return boolean    Should return false if this
         *                       type of signal should fail the
         *                       test suite.
         *    @access public
         */
        function paintSignal($type, &$payload) {
            return true;
        }
    }
?>
